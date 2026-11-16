<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\ResearchRequestForms;

/**
 * The office's "Percentage Contribution for Incentives Claim" workbook (research output with multiple authors),
 * filled from an incentive request (RDO-02 to -04): the authors, their position, specialization, specific
 * contribution and percentage. The conforme column stays blank for the authors' signatures.
 */
final class IncentiveContributionSheet
{
    private const TEMPLATE = BASE_PATH . '/resources/form-templates/rdo/rdo-incentive-contribution.xlsx';

    /** First author row of the template, and the template's author rows (1 to 6, then a "(add row if needed)" row). */
    private const FIRST_ROW = 14;

    private const TEMPLATE_ROWS = 7;

    /** The filled .xlsx bytes, or null when the request has no authors listed. */
    public static function build(array $proposal): ?string
    {
        $type = ResearchRequestForms::typeOf($proposal);
        $definition = $type !== null ? ResearchRequestForms::get($type) : null;
        if (($definition['group'] ?? '') !== 'incentive' || !is_file(self::TEMPLATE)) {
            return null;
        }
        $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
        $summary = is_array($summary) ? $summary : [];
        $authors = array_values(array_filter(
            is_array($summary['contributions'] ?? null) ? $summary['contributions'] : [],
            static fn ($row): bool => is_array($row) && trim((string) ($row['name'] ?? '')) !== ''
        ));
        if ($authors === []) {
            return null;
        }

        $book = XlsxTemplate::open(self::TEMPLATE);
        $book->setCell('D7', implode(', ', array_map(static fn (array $a): string => trim((string) $a['name']), $authors)));
        $book->setCell('D8', (string) ($proposal['title'] ?? ''));
        $book->setCell('D9', self::outputType($type, $definition, $summary));

        $extra = count($authors) - self::TEMPLATE_ROWS;
        $lastTemplateRow = self::FIRST_ROW + self::TEMPLATE_ROWS - 1;
        if ($extra > 0) {
            $book->insertRowsAfter($lastTemplateRow, $extra);
            for ($r = $lastTemplateRow + 1; $r <= $lastTemplateRow + $extra; $r++) {
                $book->addMerge('B' . $r . ':D' . $r);
                $book->addMerge('G' . $r . ':H' . $r);
            }
        }
        $total = 0.0;
        $rowCount = max(count($authors), self::TEMPLATE_ROWS);
        for ($i = 0; $i < $rowCount; $i++) {
            $r = self::FIRST_ROW + $i;
            $author = $authors[$i] ?? null;
            if ($author === null) {
                // Unused rows: keep the numbered ones blank, drop the "(add row if needed)" hint.
                if ($i === self::TEMPLATE_ROWS - 1) {
                    $book->setCell('A' . $r, '');
                    $book->setCell('B' . $r, '');
                }
                continue;
            }
            $percent = (float) str_replace('%', '', (string) ($author['percent'] ?? '0'));
            $total += $percent;
            $book->setCell('A' . $r, $i + 1);
            $book->setCell('B' . $r, trim((string) $author['name']));
            $book->setCell('E' . $r, trim((string) ($author['position_college'] ?? '')));
            $book->setCell('F' . $r, trim((string) ($author['specialization'] ?? '')));
            $book->setCell('G' . $r, trim((string) ($author['contribution'] ?? '')));
            // Column I is formatted as a percentage: 40% is stored as 0.4.
            $book->setCell('I' . $r, round($percent / 100, 4));
            $book->wrapRow($r, ['B', 'E', 'F', 'G']);
        }
        $totalRow = self::FIRST_ROW + $rowCount;
        $book->setFormula('I' . $totalRow, 'SUM(I' . self::FIRST_ROW . ':I' . ($totalRow - 1) . ')', round($total / 100, 4));
        // Prepared by: the applicant.
        $book->setCell('B' . ($totalRow + 6), (string) ($summary['applicant_name'] ?? trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? ''))));

        // One page while the template's rows suffice (wrapped rows are taller than the template's); more authors run on.
        $book->fitToPageWidth(count($authors) <= self::TEMPLATE_ROWS ? 1 : 0);

        return $book->render();
    }

    /** "Type of Research Output", e.g. "Research Publication: Journal Article". */
    private static function outputType(string $type, array $definition, array $summary): string
    {
        [$label, $key] = match ($type) {
            'presentation_incentive' => ['Research Presentation', 'presentation_type'],
            'ipr_incentive' => ['IPR Registration', 'ip_type'],
            default => ['Research Publication', 'publication_type'],
        };
        $option = (string) ($definition['fields'][$key]['options'][(string) ($summary[$key] ?? '')] ?? '');

        return $label . ($option !== '' ? ': ' . $option : '');
    }
}
