<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;

/**
 * Fills a Word (.docx) form template in place: only word/document.xml is changed, so the template's headers,
 * footers, logos, fonts and page setup stay exactly as the office issued them.
 *
 * Tables and paragraphs are addressed the way they appear in the body (0-based table index, row, cell), or
 * by the text of a label cell / heading paragraph. Values keep the formatting of the text they replace.
 */
final class DocxTemplate
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    private const UNCHECKED = '☐';
    private const CHECKED = '☒';

    private DOMDocument $dom;
    private DOMXPath $xpath;
    private DOMElement $body;

    private ZipPackage $package;

    private function __construct(string $templatePath)
    {
        $this->package = ZipPackage::fromFile($templatePath);
        $xml = $this->package->get('word/document.xml');
        if (!is_string($xml)) {
            throw new RuntimeException('Form template has no document body: ' . basename($templatePath));
        }

        $this->dom = new DOMDocument();
        $this->dom->preserveWhiteSpace = true;
        $this->dom->loadXML($xml, LIBXML_NONET | LIBXML_PARSEHUGE);
        $this->xpath = new DOMXPath($this->dom);
        $this->xpath->registerNamespace('w', self::W);
        $body = $this->xpath->query('/w:document/w:body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException('Form template has no document body: ' . basename($templatePath));
        }
        $this->body = $body;
    }

    public static function open(string $templatePath): self
    {
        return new self($templatePath);
    }

    /** The filled document as .docx bytes; everything but the body stays as the template has it. */
    public function render(): string
    {
        $package = clone $this->package;
        $package->set('word/document.xml', (string) $this->dom->saveXML());

        return $package->toString();
    }

    // ---- Locating ---------------------------------------------------------------------------------------------

    /** Top-level body table by its position among the body's tables. */
    public function table(int $index): ?DOMElement
    {
        $node = $this->xpath->query('w:tbl', $this->body)->item($index);

        return $node instanceof DOMElement ? $node : null;
    }

    /** @return list<DOMElement> */
    public function rows(DOMElement $table): array
    {
        return $this->elements($this->xpath->query('w:tr', $table));
    }

    /** @return list<DOMElement> */
    public function cells(DOMElement $row): array
    {
        return $this->elements($this->xpath->query('w:tc', $row));
    }

    public function cell(int $table, int $row, int $cell): ?DOMElement
    {
        $tbl = $this->table($table);
        if ($tbl === null) {
            return null;
        }
        $tr = $this->rows($tbl)[$row] ?? null;

        return $tr !== null ? ($this->cells($tr)[$cell] ?? null) : null;
    }

    /** First row of a table whose first cell's text starts with $label (case-insensitive). */
    public function rowByLabel(DOMElement $table, string $label): ?DOMElement
    {
        foreach ($this->rows($table) as $row) {
            $first = $this->cells($row)[0] ?? null;
            if ($first !== null && $this->startsWith($this->text($first), $label)) {
                return $row;
            }
        }

        return null;
    }

    public function text(DOMNode $node): string
    {
        $out = '';
        foreach ($this->xpath->query('.//w:t', $node) as $t) {
            $out .= $t->textContent;
        }

        return $out;
    }

    // ---- Writing ----------------------------------------------------------------------------------------------

    /**
     * Replaces a cell's content with $text (newlines become separate paragraphs), keeping the format of its
     * first paragraph and run. Hint colours/italics of a placeholder are dropped. Empty $text leaves the cell
     * as the template has it, unless $clearWhenEmpty.
     */
    public function setCell(?DOMElement $cell, string $text, bool $clearWhenEmpty = false): void
    {
        if ($cell === null || (trim($text) === '' && !$clearWhenEmpty)) {
            return;
        }
        $paragraphs = $this->elements($this->xpath->query('w:p', $cell));
        $model = $paragraphs[0] ?? null;
        foreach ($this->elements($this->xpath->query('*[not(self::w:tcPr)]', $cell)) as $child) {
            $cell->removeChild($child);
        }
        foreach ($this->lines($text) as $line) {
            $cell->appendChild($this->paragraphLike($model, $line));
        }
    }

    /** Appends " value" to the text of a cell (e.g. "RIDE Control No.: RIDE-2026-CET-001"). */
    public function appendToCell(?DOMElement $cell, string $value): void
    {
        if ($cell === null || trim($value) === '') {
            return;
        }
        $paragraph = $this->xpath->query('w:p', $cell)->item(0);
        if (!$paragraph instanceof DOMElement) {
            return;
        }
        $paragraph->appendChild($this->run($this->lastRunProps($paragraph), ' ' . $value, false));
    }

    /** Puts $text in the first empty paragraph after the heading paragraph that starts with $heading. */
    public function fillAfterHeading(string $heading, string $text): void
    {
        if (trim($text) === '') {
            return;
        }
        $children = $this->elements($this->body->childNodes);
        $found = false;
        foreach ($children as $child) {
            if ($child->localName !== 'p') {
                if ($found) {
                    return;
                }
                continue;
            }
            $content = trim($this->text($child));
            if (!$found) {
                $found = $content !== '' && $this->startsWith($content, $heading);
                continue;
            }
            if ($content === '') {
                $this->replaceParagraph($child, $text);
                return;
            }
        }
    }

    /**
     * Fills the underscore blanks ("_____") that follow the paragraph starting with $heading, in order. A null
     * value skips that blank; filling stops when the values run out.
     *
     * @param list<string|null> $values
     */
    public function fillBlanksAfter(string $heading, array $values): void
    {
        $found = false;
        foreach ($this->elements($this->body->childNodes) as $child) {
            if ($values === []) {
                return;
            }
            if ($child->localName !== 'p') {
                continue;
            }
            $content = trim($this->text($child));
            if (!$found) {
                $found = $content !== '' && $this->startsWith($content, $heading);
                continue;
            }
            if ($content !== '' && preg_match('/^_{5,}$/', $content) === 1) {
                $value = array_shift($values);
                if ($value !== null && trim($value) !== '') {
                    $this->replaceParagraph($child, $value, true);
                }
            }
        }
    }

    /** Replaces the text of the first body paragraph starting with $prefix (keeps its formatting). */
    public function replaceParagraphStarting(string $prefix, string $text): bool
    {
        foreach ($this->elements($this->body->childNodes) as $child) {
            if ($child->localName === 'p' && $this->startsWith(trim($this->text($child)), $prefix)) {
                $this->replaceParagraph($child, $text);
                return true;
            }
        }

        return false;
    }

    /** Ticks the ☐ box in front of the option whose label starts with $label, inside $scope (default: body). */
    public function tick(string $label, ?DOMNode $scope = null): void
    {
        $scope ??= $this->body;
        foreach ($this->xpath->query('.//w:p', $scope) as $paragraph) {
            // Each box with the text that follows it, up to the next box.
            $boxes = [];
            foreach ($this->xpath->query('.//w:t', $paragraph) as $t) {
                if (str_contains($t->textContent, self::UNCHECKED)) {
                    $parts = explode(self::UNCHECKED, $t->textContent);
                    $boxes[] = ['node' => $t, 'after' => (string) end($parts)];
                } elseif ($boxes !== []) {
                    $boxes[count($boxes) - 1]['after'] .= $t->textContent;
                }
            }
            foreach ($boxes as $box) {
                if ($this->startsWith($box['after'], $label)) {
                    $box['node']->textContent = str_replace(self::UNCHECKED, self::CHECKED, $box['node']->textContent);
                    return;
                }
            }
        }
    }

    /** Replaces each "20__" in a node's text, in order, with the given years (missing years stay blank). */
    public function fillYears(?DOMNode $scope, string ...$years): void
    {
        if ($scope === null) {
            return;
        }
        foreach ($this->xpath->query('.//w:t', $scope) as $t) {
            $t->textContent = (string) preg_replace_callback('/20__/', static function () use (&$years): string {
                $year = array_shift($years);
                return $year !== null && $year !== '' ? $year : '20__';
            }, $t->textContent);
        }
    }

    /**
     * Writes $data into the $blankRows blank rows of a table starting at $firstRow (default: all rows to the
     * end); more data rows are added as copies of the previous one. Each data row is a list of cell texts, or
     * an array with 'cells' and 'shade' (cell indexes to shade, for time-frame grids).
     *
     * @param list<list<string>|array{cells: list<string>, shade?: list<int>}> $data
     */
    public function fillRows(?DOMElement $table, int $firstRow, array $data, ?int $blankRows = null, string $shadeFill = '7F7F7F'): void
    {
        if ($table === null || $data === []) {
            return;
        }
        $rows = $this->rows($table);
        if ($firstRow >= count($rows)) {
            return;
        }
        $blankRows ??= count($rows) - $firstRow;
        $previous = null;
        foreach (array_values($data) as $i => $entry) {
            $row = $i < $blankRows ? ($rows[$firstRow + $i] ?? null) : null;
            if ($row === null) {
                $source = $previous ?? $rows[$firstRow];
                $row = $source->cloneNode(true);
                $source->parentNode?->insertBefore($row, $source->nextSibling);
                foreach ($this->cells($row) as $cell) {
                    $this->setCell($cell, '', true);
                    $this->shade($cell, null);
                }
            }
            $cellsText = isset($entry['cells']) ? $entry['cells'] : $entry;
            $cells = $this->cells($row);
            foreach (array_values($cellsText) as $c => $value) {
                $this->setCell($cells[$c] ?? null, (string) $value);
            }
            foreach ($entry['shade'] ?? [] as $c) {
                $this->shade($cells[$c] ?? null, $shadeFill);
            }
            $previous = $row;
        }
    }

    public function removeRow(?DOMElement $row): void
    {
        $row?->parentNode?->removeChild($row);
    }

    // ---- Internals --------------------------------------------------------------------------------------------

    private function shade(?DOMElement $cell, ?string $fill): void
    {
        if ($cell === null) {
            return;
        }
        $tcPr = $this->xpath->query('w:tcPr', $cell)->item(0);
        if (!$tcPr instanceof DOMElement) {
            $tcPr = $this->dom->createElementNS(self::W, 'w:tcPr');
            $cell->insertBefore($tcPr, $cell->firstChild);
        }
        foreach ($this->elements($this->xpath->query('w:shd', $tcPr)) as $old) {
            $tcPr->removeChild($old);
        }
        if ($fill === null) {
            return;
        }
        $shd = $this->dom->createElementNS(self::W, 'w:shd');
        $shd->setAttributeNS(self::W, 'w:val', 'clear');
        $shd->setAttributeNS(self::W, 'w:color', 'auto');
        $shd->setAttributeNS(self::W, 'w:fill', $fill);
        // CT_TcPr order: tcW, gridSpan, vMerge, tcBorders, shd, noWrap, tcMar, textDirection, tcFitText, vAlign...
        $before = $this->xpath->query('w:noWrap|w:tcMar|w:textDirection|w:tcFitText|w:vAlign|w:hideMark', $tcPr)->item(0);
        $before !== null ? $tcPr->insertBefore($shd, $before) : $tcPr->appendChild($shd);
    }

    /** Replaces a body paragraph with one paragraph per line of $text, in the same format. */
    private function replaceParagraph(DOMElement $paragraph, string $text, bool $underline = false): void
    {
        $parent = $paragraph->parentNode;
        if ($parent === null) {
            return;
        }
        foreach ($this->lines($text) as $line) {
            $parent->insertBefore($this->paragraphLike($paragraph, $line, $underline), $paragraph);
        }
        $parent->removeChild($paragraph);
    }

    private function paragraphLike(?DOMElement $model, string $text, bool $underline = false): DOMElement
    {
        $p = $this->dom->createElementNS(self::W, 'w:p');
        $pPr = $model !== null ? $this->xpath->query('w:pPr', $model)->item(0) : null;
        if ($pPr instanceof DOMElement) {
            $p->appendChild($pPr->cloneNode(true));
        }
        $p->appendChild($this->run($model !== null ? $this->firstRunProps($model) : null, $text, $underline));

        return $p;
    }

    private function run(?DOMElement $rPr, string $text, bool $underline): DOMElement
    {
        $r = $this->dom->createElementNS(self::W, 'w:r');
        $props = $rPr !== null ? $rPr->cloneNode(true) : $this->dom->createElementNS(self::W, 'w:rPr');
        foreach ($this->elements($this->xpath->query('w:color|w:i|w:iCs|w:highlight|w:u', $props)) as $hint) {
            $props->removeChild($hint);
        }
        if ($underline) {
            $u = $this->dom->createElementNS(self::W, 'w:u');
            $u->setAttributeNS(self::W, 'w:val', 'single');
            $props->appendChild($u);
        }
        $r->appendChild($props);
        $t = $this->dom->createElementNS(self::W, 'w:t');
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
        $t->appendChild($this->dom->createTextNode($text));
        $r->appendChild($t);

        return $r;
    }

    private function firstRunProps(DOMElement $paragraph): ?DOMElement
    {
        $rPr = $this->xpath->query('.//w:r/w:rPr', $paragraph)->item(0)
            ?? $this->xpath->query('w:pPr/w:rPr', $paragraph)->item(0);

        return $rPr instanceof DOMElement ? $rPr : null;
    }

    private function lastRunProps(DOMElement $paragraph): ?DOMElement
    {
        $all = $this->xpath->query('.//w:r[w:t]/w:rPr', $paragraph);
        $rPr = $all->length > 0 ? $all->item($all->length - 1) : null;

        return $rPr instanceof DOMElement ? $rPr : $this->firstRunProps($paragraph);
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [''];

        return $lines === [] ? [''] : $lines;
    }

    private function startsWith(string $text, string $prefix): bool
    {
        return stripos(trim($text), trim($prefix)) === 0;
    }

    /** @return list<DOMElement> */
    private function elements(iterable $nodes): array
    {
        $out = [];
        foreach ($nodes as $node) {
            if ($node instanceof DOMElement) {
                $out[] = $node;
            }
        }

        return $out;
    }
}
