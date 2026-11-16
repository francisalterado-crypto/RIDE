<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Fills the first worksheet of an office Excel form (.xlsx) without changing anything else in the workbook:
 * the letterhead (header picture), page setup, styles and merges stay as the template has them.
 * Values are written as inline strings or numbers; extra data rows are inserted by copying a template row.
 */
final class XlsxTemplate
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const SHEET = 'xl/worksheets/sheet1.xml';

    private ZipPackage $package;
    private DOMDocument $dom;
    private DOMXPath $xpath;
    private DOMElement $sheetData;

    private function __construct(string $templatePath)
    {
        $this->package = ZipPackage::fromFile($templatePath);
        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = true;
        $this->dom->loadXML((string) $this->package->get(self::SHEET));
        $this->xpath = new DOMXPath($this->dom);
        $this->xpath->registerNamespace('s', self::NS);
        $sheetData = $this->xpath->query('//s:sheetData')->item(0);
        if (!$sheetData instanceof DOMElement) {
            throw new \RuntimeException('The template has no worksheet data.');
        }
        $this->sheetData = $sheetData;
    }

    public static function open(string $templatePath): self
    {
        return new self($templatePath);
    }

    /** The filled workbook as .xlsx bytes. */
    public function render(): string
    {
        $package = clone $this->package;
        $package->set(self::SHEET, (string) $this->dom->saveXML());
        // Rows may have moved and formulas changed: drop the calculation chain so Excel rebuilds it, instead of
        // finding it out of date and refusing or repairing the workbook.
        if ($package->get('xl/calcChain.xml') !== null) {
            $package->remove('xl/calcChain.xml');
            $types = (string) $package->get('[Content_Types].xml');
            $package->set('[Content_Types].xml', (string) preg_replace('#<Override[^>]*PartName="/xl/calcChain\.xml"[^>]*/>#', '', $types));
            $rels = (string) $package->get('xl/_rels/workbook.xml.rels');
            $package->set('xl/_rels/workbook.xml.rels', (string) preg_replace('#<Relationship[^>]*Target="calcChain\.xml"[^>]*/>#', '', $rels));
        }

        return $package->toString();
    }

    /** Writes a text (or number) value into a cell, keeping the cell's style. Empty clears the value. */
    public function setCell(string $ref, string|int|float|null $value): void
    {
        $cell = $this->cell($ref, true);
        while ($cell->firstChild !== null) {
            $cell->removeChild($cell->firstChild);
        }
        $cell->removeAttribute('t');
        if ($value === null || $value === '') {
            return;
        }
        if (is_int($value) || is_float($value)) {
            $v = $this->dom->createElementNS(self::NS, 'v', (string) $value);
            $cell->appendChild($v);
            return;
        }
        $cell->setAttribute('t', 'inlineStr');
        $is = $this->dom->createElementNS(self::NS, 'is');
        $t = $this->dom->createElementNS(self::NS, 't');
        $t->setAttribute('xml:space', 'preserve');
        $t->appendChild($this->dom->createTextNode($value));
        $is->appendChild($t);
        $cell->appendChild($is);
    }

    /**
     * Inserts $count rows right after row $afterRow, styled like $afterRow, and moves everything below down
     * (rows, cells, merges, the sheet dimension).
     */
    public function insertRowsAfter(int $afterRow, int $count): void
    {
        if ($count <= 0) {
            return;
        }
        $rows = iterator_to_array($this->xpath->query('s:row', $this->sheetData));
        $model = null;
        foreach (array_reverse($rows) as $row) {
            /** @var DOMElement $row */
            $r = (int) $row->getAttribute('r');
            if ($r > $afterRow) {
                $this->renumberRow($row, $r + $count);
            } elseif ($r === $afterRow) {
                $model = $row;
            }
        }
        if ($model === null) {
            return;
        }
        $next = $model->nextSibling;
        for ($i = 1; $i <= $count; $i++) {
            $copy = $model->cloneNode(true);
            /** @var DOMElement $copy */
            $this->renumberRow($copy, $afterRow + $i);
            foreach ($this->xpath->query('s:c', $copy) as $c) {
                /** @var DOMElement $c */
                while ($c->firstChild !== null) {
                    $c->removeChild($c->firstChild);
                }
                $c->removeAttribute('t');
            }
            $this->sheetData->insertBefore($copy, $next);
        }
        foreach ($this->xpath->query('//s:mergeCells/s:mergeCell') as $merge) {
            /** @var DOMElement $merge */
            $merge->setAttribute('ref', $this->shiftRange($merge->getAttribute('ref'), $afterRow, $count));
        }
        $dimension = $this->xpath->query('//s:dimension')->item(0);
        if ($dimension instanceof DOMElement) {
            $dimension->setAttribute('ref', $this->shiftRange($dimension->getAttribute('ref'), $afterRow, $count));
        }
    }

    /**
     * Sets a cell's formula and its cached result (shown before Excel recalculates), keeping the cell's style.
     * Keeps the formula cell the workbook's calculation chain expects.
     */
    public function setFormula(string $ref, string $formula, int|float $result): void
    {
        $cell = $this->cell($ref, true);
        while ($cell->firstChild !== null) {
            $cell->removeChild($cell->firstChild);
        }
        $cell->removeAttribute('t');
        $cell->appendChild($this->dom->createElementNS(self::NS, 'f', $formula));
        $cell->appendChild($this->dom->createElementNS(self::NS, 'v', (string) $result));
    }

    /** Merges a range (e.g. "B22:D22"), as the template does for its own rows; used on rows added by insertRowsAfter(). */
    public function addMerge(string $ref): void
    {
        $merges = $this->xpath->query('//s:mergeCells')->item(0);
        if (!$merges instanceof DOMElement) {
            return;
        }
        $merge = $this->dom->createElementNS(self::NS, 'mergeCell');
        $merge->setAttribute('ref', $ref);
        $merges->appendChild($merge);
        $merges->setAttribute('count', (string) $this->xpath->query('s:mergeCell', $merges)->length);
    }

    /** Replaces text in the page header (where the office forms keep "COLLEGE OF ...."). */
    public function replaceInHeader(string $pattern, string $replacement): void
    {
        foreach ($this->xpath->query('//s:headerFooter/*') as $part) {
            /** @var DOMElement $part */
            $text = (string) preg_replace($pattern, $replacement, $part->textContent);
            while ($part->firstChild !== null) {
                $part->removeChild($part->firstChild);
            }
            $part->appendChild($this->dom->createTextNode($text));
        }
    }

    /** Prints the sheet one page wide (as many pages tall as needed) instead of the template's fixed scale. */
    public function fitToPageWidth(int $pagesTall = 0): void
    {
        $setup = $this->xpath->query('//s:pageSetup')->item(0);
        if ($setup instanceof DOMElement) {
            $setup->removeAttribute('scale');
            $setup->setAttribute('fitToWidth', '1');
            // 0: as many pages tall as needed.
            $setup->setAttribute('fitToHeight', (string) max(0, $pagesTall));
        }
        $worksheet = $this->dom->documentElement;
        $sheetPr = $this->xpath->query('s:sheetPr', $worksheet)->item(0);
        if (!$sheetPr instanceof DOMElement) {
            $sheetPr = $this->dom->createElementNS(self::NS, 'sheetPr');
            $worksheet->insertBefore($sheetPr, $worksheet->firstChild);
        }
        $pageSetUpPr = $this->xpath->query('s:pageSetUpPr', $sheetPr)->item(0);
        if (!$pageSetUpPr instanceof DOMElement) {
            $pageSetUpPr = $this->dom->createElementNS(self::NS, 'pageSetUpPr');
            $sheetPr->appendChild($pageSetUpPr);
        }
        $pageSetUpPr->setAttribute('fitToPage', '1');
    }

    /**
     * Wraps the text of a filled data row and sets the row height to fit its longest cell, so long titles and
     * names are printed in full instead of being cut off by the template's narrow columns.
     *
     * @param list<string> $columns column letters of the row's cells
     */
    public function wrapRow(int $row, array $columns): void
    {
        $widths = $this->columnWidths();
        $lines = 1;
        foreach ($columns as $column) {
            $cell = $this->xpath->query('s:row[@r="' . $row . '"]/s:c[@r="' . $column . $row . '"]', $this->sheetData)->item(0);
            if (!$cell instanceof DOMElement) {
                continue;
            }
            $text = trim($cell->textContent);
            if ($text === '') {
                continue;
            }
            // A merged cell (e.g. B:D) wraps at the width of its whole range.
            $width = 0.0;
            [$fromCol, $toCol] = $this->mergedColumns($column, $row);
            for ($c = $fromCol; $c <= $toCol; $c++) {
                $width += $widths[$c] ?? 8.43;
            }
            // Characters per line at the column width, leaving room for the template's padding and font.
            $perLine = max(1, (int) floor($width * 0.95) - 1);
            if (!str_contains($text, ' ')) {
                // One word (a date, an amount, a code): shrink it to the cell rather than breaking it.
                $cell->setAttribute('s', (string) $this->wrappedStyle((int) $cell->getAttribute('s'), 'shrink'));
                continue;
            }
            $cell->setAttribute('s', (string) $this->wrappedStyle((int) $cell->getAttribute('s'), 'wrap'));
            $lines = max($lines, self::wrappedLineCount($text, $perLine));
        }
        $rowEl = $this->xpath->query('s:row[@r="' . $row . '"]', $this->sheetData)->item(0);
        if ($rowEl instanceof DOMElement && $lines > 1) {
            $current = (float) ($rowEl->getAttribute('ht') ?: 15);
            $rowEl->setAttribute('ht', (string) max($current, min(409, $lines * 13.5 + 3)));
            $rowEl->setAttribute('customHeight', '1');
        }
    }

    // ---- Internals --------------------------------------------------------------------------------------------

    /** @return array{0: int, 1: int} column indexes the cell spans: its merge range, or just itself */
    private function mergedColumns(string $column, int $row): array
    {
        $index = self::columnIndex($column);
        foreach ($this->xpath->query('//s:mergeCells/s:mergeCell') as $merge) {
            /** @var DOMElement $merge */
            if (preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/', $merge->getAttribute('ref'), $m) === 1
                && $m[1] === $column && (int) $m[2] === $row && (int) $m[4] === $row) {
                return [$index, self::columnIndex($m[3])];
            }
        }

        return [$index, $index];
    }

    /** @var array<int, float>|null column index => width in characters */
    private ?array $columnWidthCache = null;

    /** @var array<string, int> "mode:original style index" => copy with wrapped or shrunk text */
    private array $wrappedStyles = [];

    /** Lines a text takes when wrapped word by word at $perLine characters. */
    private static function wrappedLineCount(string $text, int $perLine): int
    {
        $lines = 1;
        $used = 0;
        foreach (preg_split('/\s+/', trim($text)) ?: [] as $word) {
            $length = mb_strlen($word);
            if ($length > $perLine) {
                // A word longer than a line starts on a new line and breaks over several.
                $lines += ($used > 0 ? 1 : 0) + (int) ceil($length / $perLine) - 1;
                $used = $length % $perLine ?: $perLine;
            } elseif ($used === 0) {
                $used = $length;
            } elseif ($used + 1 + $length <= $perLine) {
                $used += 1 + $length;
            } else {
                $lines++;
                $used = $length;
            }
        }

        return $lines;
    }

    private ?DOMDocument $styles = null;

    /** @return array<int, float> */
    private function columnWidths(): array
    {
        if ($this->columnWidthCache !== null) {
            return $this->columnWidthCache;
        }
        $widths = [];
        foreach ($this->xpath->query('//s:cols/s:col') as $col) {
            /** @var DOMElement $col */
            for ($i = (int) $col->getAttribute('min'); $i <= (int) $col->getAttribute('max'); $i++) {
                $widths[$i] = (float) $col->getAttribute('width');
            }
        }

        return $this->columnWidthCache = $widths;
    }

    /** Index of a cell style like $index but with wrapped (or shrink-to-fit), top-aligned text, added to styles.xml once. */
    private function wrappedStyle(int $index, string $mode = 'wrap'): int
    {
        $key = $mode . ':' . $index;
        if (isset($this->wrappedStyles[$key])) {
            return $this->wrappedStyles[$key];
        }
        if ($this->styles === null) {
            $this->styles = new DOMDocument();
            $this->styles->preserveWhiteSpace = true;
            $this->styles->loadXML((string) $this->package->get('xl/styles.xml'));
        }
        $xp = new DOMXPath($this->styles);
        $xp->registerNamespace('s', self::NS);
        $cellXfs = $xp->query('//s:cellXfs')->item(0);
        $source = $xp->query('//s:cellXfs/s:xf')->item($index);
        if (!$cellXfs instanceof DOMElement || !$source instanceof DOMElement) {
            return $index;
        }
        $copy = $source->cloneNode(true);
        /** @var DOMElement $copy */
        $alignment = $xp->query('s:alignment', $copy)->item(0);
        if (!$alignment instanceof DOMElement) {
            $alignment = $this->styles->createElementNS(self::NS, 'alignment');
            $copy->insertBefore($alignment, $copy->firstChild);
        }
        if ($mode === 'shrink') {
            $alignment->removeAttribute('wrapText');
            $alignment->setAttribute('shrinkToFit', '1');
        } else {
            $alignment->setAttribute('wrapText', '1');
        }
        $alignment->setAttribute('vertical', 'top');
        $copy->setAttribute('applyAlignment', '1');
        $cellXfs->appendChild($copy);
        $count = $xp->query('s:xf', $cellXfs)->length;
        $cellXfs->setAttribute('count', (string) $count);
        $this->package->set('xl/styles.xml', (string) $this->styles->saveXML());

        return $this->wrappedStyles[$key] = $count - 1;
    }

    private function cell(string $ref, bool $create): DOMElement
    {
        if (preg_match('/^([A-Z]+)(\d+)$/', $ref, $m) !== 1) {
            throw new \InvalidArgumentException('Bad cell reference ' . $ref);
        }
        $rowNumber = (int) $m[2];
        $row = $this->xpath->query('s:row[@r="' . $rowNumber . '"]', $this->sheetData)->item(0);
        if (!$row instanceof DOMElement) {
            $row = $this->dom->createElementNS(self::NS, 'row');
            $row->setAttribute('r', (string) $rowNumber);
            $before = null;
            foreach ($this->xpath->query('s:row', $this->sheetData) as $existing) {
                /** @var DOMElement $existing */
                if ((int) $existing->getAttribute('r') > $rowNumber) {
                    $before = $existing;
                    break;
                }
            }
            $this->sheetData->insertBefore($row, $before);
        }
        $cell = $this->xpath->query('s:c[@r="' . $ref . '"]', $row)->item(0);
        if ($cell instanceof DOMElement) {
            return $cell;
        }
        $cell = $this->dom->createElementNS(self::NS, 'c');
        $cell->setAttribute('r', $ref);
        $column = self::columnIndex($m[1]);
        $before = null;
        foreach ($this->xpath->query('s:c', $row) as $existing) {
            /** @var DOMElement $existing */
            if (preg_match('/^([A-Z]+)/', $existing->getAttribute('r'), $em) === 1 && self::columnIndex($em[1]) > $column) {
                $before = $existing;
                break;
            }
        }
        $row->insertBefore($cell, $before);

        return $cell;
    }

    private function renumberRow(DOMElement $row, int $newNumber): void
    {
        $row->setAttribute('r', (string) $newNumber);
        foreach ($this->xpath->query('s:c', $row) as $c) {
            /** @var DOMElement $c */
            $c->setAttribute('r', (string) preg_replace('/\d+$/', (string) $newNumber, $c->getAttribute('r')));
        }
    }

    private function shiftRange(string $range, int $afterRow, int $count): string
    {
        return (string) preg_replace_callback('/([A-Z]+)(\d+)/', static function (array $m) use ($afterRow, $count): string {
            $row = (int) $m[2];
            return $m[1] . ($row > $afterRow ? $row + $count : $row);
        }, $range);
    }

    private static function columnIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }
}
