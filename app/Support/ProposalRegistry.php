<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Proposal;
use App\Services\ResearchFormDocument;

/**
 * The Proposals page registry: what each row shows (form, progress, return note, linked research), which
 * tab it belongs to, and the filters shared by the page and its CSV export.
 *
 * Records are split by kind, each listed on its own page: proposals (RDO-20, ESO-03) on /proposals, RDO request
 * forms on /requests, and reports (quarterly, progress, terminal, assessment) on /reports.
 *
 * Tabs: action (waiting on the viewer), drafts (draft/returned), review (submitted/under review),
 * approved (approved/ongoing/completed), all.
 */
final class ProposalRegistry
{
    public const TABS = [
        'all' => 'All',
        'action' => 'Needs my action',
        'drafts' => 'Drafts & returned',
        'review' => 'In review',
        'approved' => 'Approved',
    ];

    public const KINDS = ['proposal', 'request', 'report'];

    /** Which registry page a record belongs to: 'proposal', 'request' or 'report'. */
    public static function kindOf(array $proposal): string
    {
        if (ResearchRequestForms::typeOf($proposal) !== null) {
            return 'request';
        }
        if (proposal_is_proposal_form($proposal) || proposal_is_obr_matrix($proposal)) {
            return 'proposal';
        }

        return 'report';
    }

    /**
     * @param list<array<string, mixed>> $proposals
     * @param callable(array<string, mixed>): bool $canApprove whether the viewer can act on it now
     * @return list<array<string, mixed>> proposals with a 'registry' entry added
     */
    public static function rows(array $proposals, callable $canApprove): array
    {
        $returnedIds = [];
        foreach ($proposals as $p) {
            if ((string) ($p['status'] ?? '') === 'returned') {
                $returnedIds[] = (int) $p['id'];
            }
        }
        $returnNotes = Proposal::latestReturnComments($returnedIds);

        $rows = [];
        foreach ($proposals as $p) {
            $status = (string) ($p['status'] ?? '');
            $summary = json_decode((string) ($p['summary'] ?? ''), true);
            $summary = is_array($summary) ? $summary : [];
            $requestType = ResearchRequestForms::typeOf($p);
            $progress = null;
            // Share of the required fields filled in: the same check that unlocks Print Preview.
            if (ResearchFormDocument::formKey($p) !== null) {
                $total = count(FormPreviewRequirements::forProposal($p));
                $progress = ['filled' => $total - count(FormPreviewRequirements::missing($p)), 'total' => $total];
            } elseif (proposal_is_wpu_funded_extension($p)) {
                $required = FormPreviewRequirements::forExtensionProposal();
                $total = count($required);
                $progress = ['filled' => $total - count(FormPreviewRequirements::missing($p, $required)), 'total' => $total];
            }
            $created = (string) ($p['created_at'] ?? '');

            $p['registry'] = [
                'form' => self::formLabel($p),
                'is_request' => $requestType !== null,
                'period' => self::periodText($p, $summary),
                'group' => match (true) {
                    in_array($status, ['draft', 'returned'], true) => 'drafts',
                    in_array($status, ['submitted', 'under_review'], true) => 'review',
                    in_array($status, ['approved', 'ongoing', 'completed'], true) => 'approved',
                    default => 'other',
                },
                'needs_action' => $canApprove($p),
                'year' => preg_match('/^(\d{4})/', $created, $m) === 1 ? $m[1] : '',
                'progress' => $progress,
                'return_note' => $status === 'returned' ? ($returnNotes[(int) $p['id']] ?? null) : null,
                'linked_code' => $requestType !== null ? trim((string) ($summary['ride_control_no'] ?? '')) : '',
                'linked_id' => $requestType !== null ? (int) ($summary['linked_proposal_id'] ?? 0) : 0,
            ];
            $rows[] = $p;
        }

        return $rows;
    }

    /** Reporting period of a report, e.g. "March 2027", or '' when it has none. */
    private static function periodText(array $proposal, array $summary): string
    {
        $period = QuarterlyReporting::periodFromSummary($summary);
        if ($period !== '') {
            return QuarterlyReporting::periodLabel($period, proposal_form_type($proposal) ?: null);
        }

        return trim((string) ($summary['report_as_of'] ?? ''));
    }

    /** Form name with its office code where it has one, e.g. "RDO-32 Manuscript Writing for Publication". */
    public static function formLabel(array $proposal): string
    {
        return proposal_form_label($proposal);
    }

    /** Whether a row is in a tab. */
    public static function inTab(array $row, string $tab): bool
    {
        $r = $row['registry'];

        return match ($tab) {
            'action' => $r['needs_action'],
            'drafts', 'review', 'approved' => $r['group'] === $tab,
            default => true,
        };
    }

    /**
     * @param array{tab?: string, form?: string, year?: string, q?: string} $filters
     */
    public static function matches(array $row, array $filters): bool
    {
        $r = $row['registry'];
        if (!self::inTab($row, (string) ($filters['tab'] ?? 'all'))) {
            return false;
        }
        if (($filters['form'] ?? '') !== '' && $r['form'] !== $filters['form']) {
            return false;
        }
        if (($filters['year'] ?? '') !== '' && $r['year'] !== $filters['year']) {
            return false;
        }
        $q = strtolower(trim((string) ($filters['q'] ?? '')));

        return $q === '' || str_contains(self::searchText($row), $q);
    }

    public static function searchText(array $row): string
    {
        return strtolower(implode(' ', array_filter([
            (string) ($row['project_code'] ?? ''),
            (string) ($row['title'] ?? ''),
            $row['registry']['form'],
            trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
            (string) ($row['college_name'] ?? ''),
            status_label((string) ($row['status'] ?? '')),
            !empty($row['current_step']) ? AccessControl::stepLabel((string) $row['current_step']) : '',
            $row['registry']['linked_code'],
        ])));
    }

    /** @param list<array<string, mixed>> $rows */
    public static function csv(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['Code', 'Title', 'Form', 'College', 'Lead Researcher', 'Status', 'Step', 'For research (RIDE Control No.)', 'Created', 'Submitted', 'Approved']);
        foreach ($rows as $row) {
            fputcsv($out, [
                (string) ($row['project_code'] ?? ''),
                (string) ($row['title'] ?? ''),
                $row['registry']['form'],
                (string) ($row['college_name'] ?? ''),
                trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')),
                status_label((string) ($row['status'] ?? '')),
                !empty($row['current_step']) ? AccessControl::stepLabel((string) $row['current_step']) : '',
                $row['registry']['linked_code'],
                (string) ($row['created_at'] ?? ''),
                (string) ($row['submitted_at'] ?? ''),
                (string) ($row['approved_at'] ?? ''),
            ]);
        }
        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        // BOM so Excel opens it as UTF-8.
        return "\xEF\xBB\xBF" . $csv;
    }
}
