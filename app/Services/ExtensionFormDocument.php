<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Builds the filled Word form of an ESO-03 Extension Project Proposal from the office's own template
 * (resources/form-templates/eso/eso-03-extension-proposal.docx). The template's sample rows are cleared, and
 * the Line-Item Budget tables are copied or removed to match the proposal. This is what Print Preview downloads
 * (opened in Word to view, print or save as PDF).
 */
final class ExtensionFormDocument
{
    private const TEMPLATE = BASE_PATH . '/resources/form-templates/eso/eso-03-extension-proposal.docx';

    /** Body tables of the template, by position. */
    private const T_IDENTIFYING = 0;
    private const T_PARTNERSHIP = 1;
    private const T_TEAM = 2;
    private const T_FRAMEWORK = 3;
    private const T_WORK_PLAN = 4;
    private const T_BUDGET_GENERAL = 5;
    private const T_LINE_ITEMS = 6;

    /** @var array<string, mixed> */
    private array $summary;

    private function __construct(private readonly array $proposal, private readonly DocxTemplate $doc)
    {
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $this->summary = is_array($summary) ? $summary : [];
    }

    public static function build(array $proposal): string
    {
        $self = new self($proposal, DocxTemplate::open(self::TEMPLATE));
        $self->fill();

        return $self->doc->render();
    }

    /** Download file name, e.g. "Vermifeed Production - ESO-03.docx". */
    public static function fileName(array $proposal): string
    {
        $title = trim((string) preg_replace('/[^A-Za-z0-9 _.-]+/', '', (string) ($proposal['title'] ?? '')));

        return ($title !== '' ? substr($title, 0, 80) : 'Extension Proposal') . ' - ESO-03.docx';
    }

    private function fill(): void
    {
        $doc = $this->doc;

        // I. Identifying information: one value per row, in the template's row order.
        $leader = $this->text('project_team_leader');
        $identifying = [
            (string) ($this->proposal['title'] ?? ''),
            $this->text('college_extension_program'),
            $leader !== '' ? $leader : trim((string) ($this->proposal['first_name'] ?? '') . ' ' . (string) ($this->proposal['last_name'] ?? '')),
            implode("\n", $this->lines($this->text('members_trainers'))),
            $this->text('implementing_college_department') !== '' ? $this->text('implementing_college_department') : (string) ($this->proposal['college_name'] ?? ''),
            $this->text('collaborating_organizations'),
            $this->text('beneficiaries'),
            $this->text('male_beneficiaries'),
            $this->text('female_beneficiaries'),
            $this->duration(),
            $this->text('location'),
            $this->money($this->text('budget')),
            $this->text('source_of_fund') !== '' ? $this->text('source_of_fund') : (string) ($this->proposal['funding_source'] ?? ''),
        ];
        foreach ($identifying as $row => $value) {
            $doc->setCell($doc->cell(self::T_IDENTIFYING, $row, 1), $value);
        }

        // II. to IV. Narrative sections.
        $doc->fillAfterHeading('RATIONALE', $this->text('rationale'));
        $doc->insertAfterParagraph('General objective:', $this->text('general_objective'));
        $doc->insertAfterParagraph('Specific objectives:', $this->text('specific_objectives'));
        $doc->fillAfterHeading('Community Analysis:', $this->text('community_analysis'));
        $doc->fillAfterHeading('Problem Analysis:', $this->text('problem_analysis'));
        $doc->fillAfterHeading('Description of the Target Group:', $this->text('target_group_description'));

        // D. Partnership, E. Team duties, logical framework, work plan, general budget.
        $this->table(self::T_PARTNERSHIP, 1, 'partnerships', ['partner', 'task_description', 'area_of_responsibility', 'resource_sharing']);
        $team = array_map(static fn (array $row): array => [
            trim(implode("\n", array_filter([trim((string) ($row['member'] ?? '')), trim((string) ($row['role'] ?? ''))]))),
            trim((string) ($row['task_description'] ?? '')),
            trim((string) ($row['responsibility'] ?? '')),
        ], $this->rows('team_duties'));
        $this->fillTable(self::T_TEAM, 1, $team);
        $this->table(self::T_FRAMEWORK, 1, 'logical_framework', ['inputs', 'activities', 'outputs', 'effects', 'outcomes', 'impact', 'sdg']);
        $this->table(self::T_WORK_PLAN, 1, 'work_plan', ['activities', 'objective', 'indicator', 'strategies', 'time_frame', 'responsible_persons', 'budget_needed', 'outputs'], ['budget_needed']);
        $this->table(self::T_BUDGET_GENERAL, 1, 'budget_general', ['item', 'particulars', 'amount'], ['amount']);

        // VI. Methodology, VII. Activities (the template has no blank line under VII, so text is inserted).
        $doc->fillAfterHeading('VI. METHODOLOGY', $this->text('methodology'));
        $doc->insertAfterParagraph('VII. ACTIVITIES', $this->text('activities_narrative'));

        $this->lineItemBudgets();

        $doc->fillAfterHeading('X. REFERENCES', $this->text('references'));

        // Sign-off, from the record (see eso_extension_signoff).
        $signoff = eso_extension_signoff($this->proposal, $this->summary);
        $doc->fillUnderscores('Submitted by:', [$signoff['proponent']['name'], $signoff['proponent']['date']], 0);
        $doc->fillUnderscores('Endorsed by:', [$signoff['dean']['name']]);
        $doc->fillUnderscores('Received by:', [$signoff['received']['name']]);
        $doc->replaceLastWord('Dean', 'Date', $signoff['dean']['date']);
        $doc->replaceLastWord('Extension Admin Staff', 'Date', $signoff['received']['date']);
    }

    /**
     * The template has two "Specific Breakdown of Budget of Line-Item Budget" tables. They are filled in order;
     * further groups get copies of the first, and an unused second table is removed.
     */
    private function lineItemBudgets(): void
    {
        $doc = $this->doc;
        $groups = [];
        foreach ((is_array($this->summary['budget_line_groups'] ?? null) ? $this->summary['budget_line_groups'] : []) as $group) {
            $items = array_values(array_filter(
                is_array($group['items'] ?? null) ? $group['items'] : [],
                static fn ($item): bool => is_array($item) && trim(implode('', array_map('strval', array_diff_key($item, ['item_no' => ''])))) !== ''
            ));
            if ($items !== []) {
                $groups[] = ['label' => trim((string) ($group['label'] ?? '')), 'items' => $items];
            }
        }
        if ($groups === []) {
            $groups[] = ['label' => '', 'items' => []];
        }

        $tables = [$doc->table(self::T_LINE_ITEMS), $doc->table(self::T_LINE_ITEMS + 1)];
        if (count($groups) === 1) {
            $doc->removeTableWithHeading('2. Specific Breakdown of Budget');
            $tables = [$tables[0]];
        }
        $headingFor = static fn (int $i): string => ($i + 1) . '. Specific Breakdown of Budget of '
            . ($groups[$i]['label'] !== '' ? $groups[$i]['label'] : 'Line-Item Budget ' . ($i + 1));
        for ($i = 2; $i < count($groups); $i++) {
            $last = end($tables);
            $tables[] = $last instanceof \DOMElement
                ? $doc->duplicateTableWithHeading('1. Specific Breakdown of Budget', $last, $headingFor($i))
                : null;
        }

        foreach ($groups as $i => $group) {
            if ($i < 2) {
                $doc->replaceParagraphStarting(($i + 1) . '. Specific Breakdown of Budget', $headingFor($i));
            }
            $table = $tables[$i] ?? null;
            if ($table === null) {
                continue;
            }
            $rows = [];
            $subtotal = 0.0;
            foreach ($group['items'] as $n => $item) {
                $total = trim((string) ($item['total_cost'] ?? ''));
                $subtotal += $this->amount($total);
                $rows[] = [
                    (string) ($n + 1),
                    trim((string) ($item['particulars'] ?? '')),
                    trim((string) ($item['qty'] ?? '')),
                    trim((string) ($item['unit'] ?? '')),
                    $this->money(trim((string) ($item['cost_unit'] ?? ''))),
                    $this->money($total),
                ];
            }
            // Rows 1-3 hold items (sample rows cleared); the last row is the sub-total.
            $tableRows = $doc->rows($table);
            $subtotalRow = end($tableRows);
            $doc->clearRows($table, 1, count($tableRows) - 2);
            $doc->fillRows($table, 1, $rows, count($tableRows) - 2);
            if ($subtotalRow instanceof \DOMElement && $rows !== []) {
                $doc->setCell($doc->cells($subtotalRow)[1] ?? null, number_format($subtotal, 2));
            }
        }
    }

    /**
     * Fills a table from a list of summary rows; the template's sample rows are cleared first.
     *
     * @param list<string> $keys
     * @param list<string> $moneyKeys columns printed as amounts
     */
    private function table(int $index, int $firstRow, string $summaryKey, array $keys, array $moneyKeys = []): void
    {
        $data = array_map(
            fn (array $row): array => array_map(
                fn (string $key): string => in_array($key, $moneyKeys, true) ? $this->money(trim((string) ($row[$key] ?? ''))) : trim((string) ($row[$key] ?? '')),
                $keys
            ),
            $this->rows($summaryKey)
        );
        $this->fillTable($index, $firstRow, $data);
    }

    /** @param list<list<string>> $data */
    private function fillTable(int $index, int $firstRow, array $data): void
    {
        $table = $this->doc->table($index);
        if ($table === null) {
            return;
        }
        $this->doc->clearRows($table, $firstRow);
        $this->doc->fillRows($table, $firstRow, $data);
    }

    /** @return list<array<string, mixed>> rows of a summary list that have any content */
    private function rows(string $key): array
    {
        $list = is_array($this->summary[$key] ?? null) ? $this->summary[$key] : [];

        return array_values(array_filter($list, static function ($row): bool {
            if (!is_array($row)) {
                return false;
            }
            foreach ($row as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return true;
                }
            }

            return false;
        }));
    }

    private function text(string $key): string
    {
        $value = $this->summary[$key] ?? '';

        return is_scalar($value) ? trim((string) $value) : '';
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $text) ?: [])));
    }

    private function duration(): string
    {
        $format = static function (string $date): string {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
            return $parsed instanceof \DateTimeImmutable ? $parsed->format('F j, Y') : $date;
        };
        $dates = array_filter([$this->text('duration_start_date'), $this->text('duration_end_date')]);

        return $dates !== [] ? implode(' to ', array_map($format, $dates)) : $this->text('duration_inclusive_dates');
    }

    private function amount(string $raw): float
    {
        $cleaned = preg_replace('/[^\d.-]/', '', str_replace(',', '', $raw)) ?? '';

        return is_numeric($cleaned) ? (float) $cleaned : 0.0;
    }

    /** "5000" becomes "PHP 5,000.00"; text that is not a plain amount is kept as typed. */
    private function money(string $raw): string
    {
        $plain = str_replace([',', ' '], '', $raw);

        return $raw !== '' && is_numeric($plain) ? 'PHP ' . number_format((float) $plain, 2) : $raw;
    }
}
