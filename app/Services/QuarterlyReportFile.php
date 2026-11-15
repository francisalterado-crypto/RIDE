<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ExtensionActivityTypes;

/**
 * The printable office file of a saved quarterly report: the ESO Excel forms (.xlsx) and the RDO Word forms
 * (.docx), filled from the report's rows. The templates keep the letterhead in their page header, so the file
 * prints exactly like the office's own form. Templates live in resources/form-templates/.
 */
final class QuarterlyReportFile
{
    private const DIR = BASE_PATH . '/resources/form-templates/';

    /** ESO-06 columns, shared by both training categories. 'weighted_persons' is computed (see value()). */
    private const TRAINING_COLUMNS = [
        'A' => 'extension_program', 'B' => 'training_title', 'C' => 'venue', 'D' => 'training_date',
        'E' => 'participant_type', 'F' => 'persons_trained', 'G' => 'weighted_persons', 'H' => 'personnel_name',
        'I' => 'personnel_role', 'J' => 'budget_source', 'K' => 'budget_amount', 'L' => 'trainees_surveyed',
        'M' => 'quality_poor', 'N' => 'quality_fair', 'O' => 'quality_good', 'P' => 'quality_better', 'Q' => 'quality_best',
        'R' => 'timeliness_poor', 'S' => 'timeliness_fair', 'T' => 'timeliness_good', 'U' => 'timeliness_better', 'V' => 'timeliness_best',
    ];

    /** Weight of a training by its length (ESO-06 note 3). */
    private const TRAINING_WEIGHTS = ['lt_8h' => 0.5, '8h_1d' => 1.0, '2d' => 1.25, '3_4d' => 1.5, '5d_plus' => 2.0];

    /**
     * ESO Excel forms. 'tables': data rows per section (first data row, number of blank rows in the template,
     * column => field); filled bottom-up so inserting rows never moves a section that is still to be filled.
     */
    private const XLSX = [
        'trainings_conducted' => [
            'file' => 'eso/eso-06-trainings-conducted.xlsx',
            'as_of' => 'A3',
            'tables' => [
                'other' => ['first' => 12, 'rows' => 3, 'columns' => self::TRAINING_COLUMNS],
                'need_based' => ['first' => 7, 'rows' => 3, 'columns' => self::TRAINING_COLUMNS],
            ],
            'counts' => [],
            // Totals rows: persons trained and persons trained weighted by length, per category and overall.
            'sums' => [
                'F10' => ['need_based', 'persons_trained'], 'G10' => ['need_based', 'weighted_persons'],
                'F15' => ['other', 'persons_trained'], 'G15' => ['other', 'weighted_persons'],
                'F16' => ['', 'persons_trained'], 'G16' => ['', 'weighted_persons'],
            ],
            'narrative' => ['A21' => 'challenges', 'A24' => 'best_practices', 'A27' => 'lessons_learned'],
            'prepared_by' => 'D53',
            'dean' => 'Q53',
        ],
        'outreach_activities' => [
            'file' => 'eso/eso-09-outreach-activities.xlsx',
            'as_of' => 'A3',
            'tables' => [
                '' => ['first' => 6, 'rows' => 10, 'columns' => [
                    'A' => 'title', 'B' => 'venue', 'C' => 'start_date', 'D' => 'end_date', 'E' => 'beneficiaries',
                ]],
            ],
            // D16 holds the number of outreach activities, E16 the total beneficiaries.
            'counts' => ['D16' => ''],
            'sums' => ['E16' => ['', 'beneficiaries']],
            'narrative' => ['A19' => 'challenges', 'A21' => 'best_practices', 'A23' => 'lessons_learned'],
            'prepared_by' => 'A36',
            'dean' => 'E36',
        ],
        'technical_advisory' => [
            'file' => 'eso/eso-07-technical-advisory.xlsx',
            'as_of' => 'A3',
            'tables' => [
                '' => ['first' => 6, 'rows' => 8, 'columns' => [
                    'A' => 'extension_program', 'B' => 'technical_advisory_conducted', 'C' => 'venue', 'D' => 'advisory_date',
                    'E' => 'client_type', 'F' => 'clients_served', 'G' => 'personnel_name', 'H' => 'trainees_surveyed',
                    'I' => 'quality_poor', 'J' => 'quality_fair', 'K' => 'quality_good', 'L' => 'quality_better', 'M' => 'quality_best',
                    'N' => 'timeliness_poor', 'O' => 'timeliness_fair', 'P' => 'timeliness_good', 'Q' => 'timeliness_better', 'R' => 'timeliness_best',
                ]],
            ],
            'counts' => ['E14' => ''],
            'narrative' => ['A16' => 'challenges', 'A19' => 'best_practices', 'A22' => 'lessons_learned'],
            'prepared_by' => 'C34',
            'dean' => 'M34',
        ],
        'extension_linkages' => [
            'file' => 'eso/eso-08-extension-linkages.xlsx',
            'as_of' => 'A3',
            'tables' => [
                '' => ['first' => 6, 'rows' => 11, 'columns' => [
                    'A' => 'partner', 'B' => 'linkage_forged', 'C' => 'description', 'D' => 'effectivity_from', 'E' => 'effectivity_to',
                    'F' => 'extension_activities', 'G' => 'conducted_from', 'H' => 'conducted_to',
                ]],
            ],
            'counts' => [],
            'narrative' => [],
            'prepared_by' => 'A27',
            'dean' => 'F27',
        ],
        'technology_adoption' => [
            'file' => 'eso/eso-10-technology-adoption.xlsx',
            'as_of' => 'A3',
            'tables' => [
                'training' => ['first' => 14, 'rows' => 3, 'number' => 'A', 'columns' => [
                    'B' => 'extension_personnel', 'C' => 'extension_service_title', 'D' => 'date_conducted', 'E' => 'technology_demonstrated',
                    'F' => 'adopter_name', 'G' => 'adopter_location', 'H' => 'date_adopted', 'I' => 'date_commercialized', 'J' => 'net_income',
                ]],
                'demonstration_farm' => ['first' => 7, 'rows' => 4, 'number' => 'A', 'columns' => [
                    'B' => 'extension_personnel', 'C' => 'demonstration_farm', 'D' => 'year_established', 'E' => 'technology_demonstrated',
                    'F' => 'adopter_name', 'G' => 'adopter_location', 'H' => 'date_adopted', 'I' => 'date_commercialized', 'J' => 'net_income',
                ]],
            ],
            'counts' => ['F17' => 'demonstration_farm', 'F18' => ''],
            'narrative' => ['A21' => 'challenges', 'A23' => 'best_practices', 'A25' => 'lessons_learned'],
            'prepared_by' => 'B37',
            'dean' => 'H37',
        ],
    ];

    /**
     * RDO Word forms: one table; each section's rows follow its heading row (a list of headings for nested
     * groups, e.g. Inventions > A. Patented). 'skip' lists fields the template has no column for.
     */
    private const DOCX = [
        'completed_researches' => ['file' => 'rdo/rdo-05-completed-researches.docx', 'title' => 'COMPLETED RESEARCH'],
        'ongoing_researches' => ['file' => 'rdo/rdo-06-ongoing-researches.docx', 'skip' => ['progress_report_date']],
        'research_output_published' => ['file' => 'rdo/rdo-07-research-output-published.docx', 'skip' => ['date_published']],
        'research_output_presented' => ['file' => 'rdo/rdo-08-research-output-presented.docx'],
        'commercialized' => ['file' => 'rdo/rdo-09-commercialized.docx'],
        'resulted_in_extension' => ['file' => 'rdo/rdo-10-resulted-in-extension.docx'],
        'journal_citation' => ['file' => 'rdo/rdo-11-journal-citation.docx'],
        'book_citation' => ['file' => 'rdo/rdo-12-book-citation.docx'],
        'inventions_um_copyrights' => ['file' => 'rdo/rdo-13-inventions-um-copyrights.docx', 'headings' => [
            'inventions_patented' => ['Inventions', 'A. Patented'],
            'inventions_applied_for_patenting' => ['Inventions', 'B. Applied for Patenting'],
            'inventions_not_patented_but_utilized' => ['Inventions', 'C. Not Patented'],
            'utility_models_registered' => ['Utility Models', 'A. Registered'],
            'utility_models_applied_for_registration' => ['Utility Models', 'B. Applied for Registration'],
            'copyrights' => ['Copyrights'],
        ]],
        'linkages' => ['file' => 'rdo/rdo-14-linkages.docx'],
    ];

    /** Whether the office template of this report type is available. */
    public static function available(string $type): bool
    {
        $file = self::XLSX[$type]['file'] ?? self::DOCX[$type]['file'] ?? null;

        return $file !== null && is_file(self::DIR . $file);
    }

    /** 'xlsx', 'docx' or '' */
    public static function format(string $type): string
    {
        return isset(self::XLSX[$type]) ? 'xlsx' : (isset(self::DOCX[$type]) ? 'docx' : '');
    }

    /**
     * @return array{bytes: string, name: string, mime: string}|null
     */
    public static function build(array $proposal): ?array
    {
        $type = proposal_form_type($proposal);
        if (!self::available($type)) {
            return null;
        }
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $summary = is_array($summary) ? $summary : [];
        $format = self::format($type);
        $bytes = $format === 'xlsx' ? self::buildXlsx($type, $summary, $proposal) : self::buildDocx($type, $summary, $proposal);
        $label = proposal_form_label($proposal);
        $college = trim((string) ($summary['college_name'] ?? $proposal['college_name'] ?? ''));
        $asOf = trim((string) ($summary['report_as_of'] ?? ''));
        $name = trim(preg_replace('/[^A-Za-z0-9 ._()-]+/', '', $label . ($college !== '' ? ' - ' . $college : '') . ($asOf !== '' ? ' - ' . $asOf : '')) ?? 'Report');

        return [
            'bytes' => $bytes,
            'name' => $name . '.' . $format,
            'mime' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    // ---- Excel (ESO) ------------------------------------------------------------------------------------------

    private static function buildXlsx(string $type, array $summary, array $proposal): string
    {
        $config = self::XLSX[$type];
        $book = XlsxTemplate::open(self::DIR . $config['file']);
        $definition = ExtensionActivityTypes::get($type) ?? ['sections' => []];

        $college = trim((string) ($summary['college_name'] ?? $proposal['college_name'] ?? ''));
        if ($college !== '') {
            $book->replaceInHeader('/COLLEGE OF\s*\.{2,}/i', mb_strtoupper($college));
        }
        $book->setCell($config['as_of'], 'As of ' . self::asOfText($summary));

        $rowsBySection = [];
        foreach ($definition['sections'] as $key => $section) {
            $rowsBySection[(string) $key] = self::rowsAt($summary, $section['path']);
        }
        $allRows = array_merge(...array_values($rowsBySection ?: [[]]));

        // Counts and narrative cells sit below the tables, so write them before rows are inserted, then move.
        $shiftAfter = [];
        foreach ($config['tables'] as $key => $table) {
            $extra = max(0, count($rowsBySection[$key] ?? []) - $table['rows']);
            $shiftAfter[] = [$table['first'] + $table['rows'] - 1, $extra];
        }
        $moved = static function (string $ref) use ($shiftAfter): string {
            preg_match('/^([A-Z]+)(\d+)$/', $ref, $m);
            $row = (int) $m[2];
            foreach ($shiftAfter as [$after, $extra]) {
                if ($row > $after) {
                    $row += $extra;
                }
            }
            return $m[1] . $row;
        };

        foreach ($config['tables'] as $key => $table) {
            $rows = $rowsBySection[$key] ?? [];
            $extra = count($rows) - $table['rows'];
            if ($extra > 0) {
                $book->insertRowsAfter($table['first'] + $table['rows'] - 1, $extra);
            }
            foreach ($rows as $i => $row) {
                $r = $table['first'] + $i;
                if (isset($table['number'])) {
                    $book->setCell($table['number'] . $r, $i + 1);
                }
                foreach ($table['columns'] as $col => $field) {
                    $book->setCell($col . $r, self::value($type, (string) $key, $field, $row));
                }
            }
        }

        foreach ($config['counts'] as $ref => $sectionKey) {
            $count = $sectionKey === '' ? count($allRows) : count($rowsBySection[$sectionKey] ?? []);
            $book->setCell($moved($ref), $count);
        }
        foreach ($config['sums'] ?? [] as $ref => [$sectionKey, $field]) {
            $rows = $sectionKey === '' ? $allRows : ($rowsBySection[$sectionKey] ?? []);
            $sectionOf = static function (array $row) use ($rowsBySection): string {
                foreach ($rowsBySection as $key => $list) {
                    if (in_array($row, $list, true)) {
                        return (string) $key;
                    }
                }
                return '';
            };
            $total = 0.0;
            foreach ($rows as $row) {
                $total += (float) str_replace(',', '', self::value($type, $sectionKey !== '' ? $sectionKey : $sectionOf($row), $field, $row));
            }
            $book->setCell($moved($ref), $rows === [] ? '' : $total + 0);
        }
        foreach ($config['narrative'] as $ref => $field) {
            $book->setCell($moved($ref), trim((string) ($summary[$field] ?? '')));
        }
        $book->setCell($moved($config['prepared_by']), self::preparedBy($proposal));
        $dean = self::deanName($proposal);
        if ($dean !== '') {
            $book->setCell($moved($config['dean']), $dean);
        }

        return $book->render();
    }

    // ---- Word (RDO) -------------------------------------------------------------------------------------------

    private static function buildDocx(string $type, array $summary, array $proposal): string
    {
        $config = self::DOCX[$type];
        $doc = DocxTemplate::open(self::DIR . $config['file']);
        $definition = ExtensionActivityTypes::get($type) ?? ['sections' => []];
        $table = $doc->table(0);

        $college = trim((string) ($summary['college_name'] ?? $proposal['college_name'] ?? ''));
        $doc->replaceParagraphStarting('As of', 'As of ' . self::asOfText($summary));
        if ($college !== '') {
            $doc->replaceParagraphStarting('College/Unit/Center', 'College/Unit/Center: ' . $college);
        }

        // Template columns follow the widest section's fields, minus fields the template has no column for.
        $columns = [];
        foreach ($definition['sections'] as $key => $section) {
            $fields = array_keys(ExtensionActivityTypes::fieldsFor($type, (string) $key));
            if (count($fields) > count($columns)) {
                $columns = $fields;
            }
        }
        $columns = array_values(array_diff($columns, $config['skip'] ?? []));

        foreach ($definition['sections'] as $key => $section) {
            $rows = self::rowsAt($summary, $section['path']);
            if ($rows === [] || $table === null) {
                continue;
            }
            $headings = $config['headings'][$key] ?? ((string) $section['label'] !== '' ? [(string) $section['label']] : []);
            [$first, $blank] = self::docxSlot($doc, $table, $headings, count($columns));
            if ($first === null) {
                continue;
            }
            $data = [];
            foreach ($rows as $row) {
                $data[] = array_map(static fn (string $field): string => self::value($type, (string) $key, $field, $row), $columns);
            }
            $doc->fillRows($table, $first, $data, $blank);
        }

        $dean = self::deanName($proposal);
        $doc->fillBlanksAfter('Prepared by', [self::preparedBy($proposal), $dean !== '' ? $dean : null]);

        return $doc->render();
    }

    /**
     * First blank data row after the section's heading(s) and how many blank rows follow it.
     *
     * @param list<string> $headings
     * @return array{0: int|null, 1: int}
     */
    private static function docxSlot(DocxTemplate $doc, \DOMElement $table, array $headings, int $columnCount): array
    {
        $rows = $doc->rows($table);
        $start = 0;
        foreach ($headings as $heading) {
            $found = null;
            for ($i = $start; $i < count($rows); $i++) {
                if (count($doc->cells($rows[$i])) <= 2 && str_starts_with(trim($doc->text($rows[$i])), $heading)) {
                    $found = $i;
                    break;
                }
            }
            if ($found === null) {
                return [null, 0];
            }
            $start = $found + 1;
        }
        // No heading (single-section forms): the first empty row with a cell per column.
        for ($i = $start; $i < count($rows); $i++) {
            if (count($doc->cells($rows[$i])) >= $columnCount && trim($doc->text($rows[$i])) === '') {
                $blank = 0;
                for ($j = $i; $j < count($rows) && count($doc->cells($rows[$j])) >= $columnCount && trim($doc->text($rows[$j])) === ''; $j++) {
                    $blank++;
                }
                return [$i, $blank];
            }
        }

        return [null, 0];
    }

    // ---- Shared -----------------------------------------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    private static function rowsAt(array $summary, array $path): array
    {
        foreach ($path as $key) {
            $summary = is_array($summary[$key] ?? null) ? $summary[$key] : [];
        }

        return array_values(array_filter($summary, 'is_array'));
    }

    private static function value(string $type, string $section, string $field, array $row): string
    {
        if ($field === 'weighted_persons') {
            $persons = trim((string) ($row['persons_trained'] ?? ''));
            $weight = self::TRAINING_WEIGHTS[(string) ($row['training_length'] ?? '')] ?? null;
            return $persons !== '' && is_numeric($persons) && $weight !== null ? (string) ((float) $persons * $weight + 0) : '';
        }
        $value = trim((string) ($row[$field] ?? ''));
        if ($value === '') {
            return '';
        }
        $fieldDef = ExtensionActivityTypes::fieldsFor($type, $section)[$field] ?? [];
        if (isset($fieldDef['options'][$value])) {
            return (string) $fieldDef['options'][$value];
        }
        if (($fieldDef['type'] ?? '') === 'date' && strtotime($value) !== false) {
            return date('m/d/Y', (int) strtotime($value));
        }

        return $value;
    }

    private static function asOfText(array $summary): string
    {
        $asOf = trim((string) ($summary['report_as_of'] ?? ''));

        return $asOf !== '' && strtotime($asOf) !== false ? date('F j, Y', (int) strtotime($asOf)) : $asOf;
    }

    private static function preparedBy(array $proposal): string
    {
        return trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''));
    }

    /** The Dean who attested the report, once the Dean step is done. */
    private static function deanName(array $proposal): string
    {
        $id = (int) ($proposal['id'] ?? 0);
        if ($id <= 0 || \App\Models\Proposal::approvalAtStep($id, 'dean') === null) {
            return '';
        }

        return trim((string) (proposal_step_endorsement($id, 'dean', 'College Dean')['name'] ?? ''));
    }
}
