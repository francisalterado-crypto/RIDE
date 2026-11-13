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

    // ---- Internals --------------------------------------------------------------------------------------------

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
