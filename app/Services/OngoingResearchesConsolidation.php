<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\College;
use App\Models\Proposal;
use App\Models\User;
use App\Support\MonitoringRoles;

final class OngoingResearchesConsolidation
{
    private const FUNDING_SECTIONS = ['external', 'inhouse', 'personal'];

    public const SCOPE_COLLEGE = 'college';

    public const SCOPE_VPRIDE = 'vpride';

    /**
     * After RDO Director approves a faculty ongoing report, deposit into
     * the college consolidated draft and the university-wide VPRIDE consolidated draft.
     */
    public static function depositOnDirectorApproval(int $facultyProposalId): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::isFacultyOngoingForm($facultyProposal) || !self::isFacultySubmitter($facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($facultyProposalId, null);
        self::syncToVprideConsolidated($facultyProposalId);
    }

    /** Sync a director-approved faculty report into the college consolidated draft (backfill / refresh). */
    public static function syncFacultyReportToConsolidated(int $facultyProposalId, ?int $preferredCoordinatorUserId = null): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::qualifiesForConsolidation($facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($facultyProposalId, $preferredCoordinatorUserId);
        self::syncToVprideConsolidated($facultyProposalId);
    }

    private static function syncToCollegeConsolidated(int $facultyProposalId, ?int $preferredCoordinatorUserId): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null) {
            return;
        }

        $collegeId = (int) ($facultyProposal['college_id'] ?? 0);
        if ($collegeId === 0) {
            return;
        }

        $coordinator = self::resolveCoordinator($collegeId, $preferredCoordinatorUserId);
        if ($coordinator === null) {
            return;
        }

        $facultySummary = self::decodeSummary($facultyProposal);
        $reportAsOf = trim((string) ($facultySummary['report_as_of'] ?? ''));
        $collegeName = trim((string) ($facultySummary['college_name'] ?? ''));
        if ($collegeName === '') {
            $collegeName = self::collegeName($collegeId);
        }

        $facultyEntries = self::normalizeEntries($facultySummary['entries'] ?? []);
        $consolidated = self::findEditableCollegeConsolidatedDraft(
            (int) $coordinator['id'],
            $collegeId,
            $reportAsOf
        );

        if ($consolidated === null) {
            self::createCollegeConsolidatedDraft(
                (int) $coordinator['id'],
                $collegeId,
                (int) ($facultyProposal['campus_id'] ?? 0) ?: null,
                $reportAsOf,
                $collegeName,
                $facultyProposalId,
                $facultyEntries
            );

            return;
        }

        self::mergeIntoConsolidated(
            (int) $consolidated['id'],
            $facultyProposalId,
            $facultyEntries,
            $reportAsOf,
            $collegeName
        );
    }

    private static function syncToVprideConsolidated(int $facultyProposalId): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null) {
            return;
        }

        $vprideUser = self::resolveVprideUser();
        if ($vprideUser === null) {
            return;
        }

        $collegeId = (int) ($facultyProposal['college_id'] ?? 0);
        $collegeLabel = trim((string) (self::decodeSummary($facultyProposal)['college_name'] ?? ''));
        if ($collegeLabel === '' && $collegeId > 0) {
            $collegeLabel = self::collegeName($collegeId);
        }

        $facultySummary = self::decodeSummary($facultyProposal);
        $reportAsOf = trim((string) ($facultySummary['report_as_of'] ?? ''));
        $facultyEntries = self::tagEntriesWithCollege(
            self::normalizeEntries($facultySummary['entries'] ?? []),
            $collegeLabel
        );

        $consolidated = self::findEditableVprideConsolidatedDraft((int) $vprideUser['id'], $reportAsOf);
        if ($consolidated === null) {
            self::createVprideConsolidatedDraft(
                (int) $vprideUser['id'],
                $reportAsOf,
                $facultyProposalId,
                $facultyEntries
            );

            return;
        }

        self::mergeIntoConsolidated(
            (int) $consolidated['id'],
            $facultyProposalId,
            $facultyEntries,
            $reportAsOf,
            'University-wide (All Colleges)'
        );
    }

    /** Backfill: sync every director-approved faculty report for a college. */
    public static function syncAllFacultyReportsForCollege(int $collegeId, ?int $preferredCoordinatorUserId = null): void
    {
        if ($collegeId <= 0) {
            return;
        }

        if (self::resolveCoordinator($collegeId, $preferredCoordinatorUserId) === null) {
            return;
        }

        foreach (self::facultyOngoingReportsForCollege($collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            self::syncToCollegeConsolidated((int) $proposal['id'], $preferredCoordinatorUserId);
        }
    }

    /** Backfill: sync all director-approved faculty reports into the VPRIDE consolidated draft. */
    public static function syncAllDirectorApprovedForVpride(?int $preferredVprideUserId = null): void
    {
        if (self::resolveVprideUser($preferredVprideUserId) === null) {
            return;
        }

        foreach (self::allFacultyOngoingReports() as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            self::syncToVprideConsolidated((int) $proposal['id']);
        }
    }

    public static function containerCollegeId(): int
    {
        $colleges = College::all();

        return (int) ($colleges[0]['id'] ?? 1);
    }

    /** Rebuild a consolidated draft from all qualifying faculty reports in the college. */
    public static function refreshConsolidatedProposal(int $consolidatedProposalId): void
    {
        $consolidated = Proposal::find($consolidatedProposalId);
        if ($consolidated === null) {
            return;
        }

        $summary = self::decodeSummary($consolidated);
        if (($summary['form_type'] ?? '') !== 'consolidated_ongoing_researches') {
            return;
        }

        if (!in_array((string) ($consolidated['status'] ?? ''), ['draft', 'returned'], true)) {
            return;
        }

        $scope = (string) ($summary['consolidation_scope'] ?? self::SCOPE_COLLEGE);
        $reportAsOf = trim((string) ($summary['report_as_of'] ?? ''));
        $manualEntries = self::normalizeEntries($summary['manual_entries'] ?? []);
        $sourceEntries = [];

        $facultyReports = $scope === self::SCOPE_VPRIDE
            ? self::allFacultyOngoingReports()
            : self::facultyOngoingReportsForCollege((int) ($consolidated['college_id'] ?? 0));

        foreach ($facultyReports as $facultyProposal) {
            if (!self::qualifiesForConsolidation($facultyProposal)) {
                continue;
            }

            $facultySummary = self::decodeSummary($facultyProposal);
            $facultyReportAsOf = trim((string) ($facultySummary['report_as_of'] ?? ''));
            if ($reportAsOf !== '' && $facultyReportAsOf !== '' && $facultyReportAsOf !== $reportAsOf) {
                continue;
            }

            if ($reportAsOf === '' && $facultyReportAsOf !== '') {
                continue;
            }

            $entries = self::normalizeEntries($facultySummary['entries'] ?? []);
            if ($scope === self::SCOPE_VPRIDE) {
                $collegeId = (int) ($facultyProposal['college_id'] ?? 0);
                $collegeLabel = trim((string) ($facultySummary['college_name'] ?? ''));
                if ($collegeLabel === '' && $collegeId > 0) {
                    $collegeLabel = self::collegeName($collegeId);
                }
                $entries = self::tagEntriesWithCollege($entries, $collegeLabel);
            }

            $sourceEntries[(string) $facultyProposal['id']] = $entries;
        }

        $summary['source_entries'] = $sourceEntries;
        $summary['entries'] = self::appendEntries(self::entriesFromSourceMap($sourceEntries), $manualEntries);
        $summary['form_type'] = 'consolidated_ongoing_researches';

        $title = self::buildTitle(
            trim((string) ($summary['college_name'] ?? '')),
            trim((string) ($summary['report_as_of'] ?? ''))
        );

        Proposal::updateConsolidatedDraft(
            $consolidatedProposalId,
            $title,
            json_encode($summary, JSON_UNESCAPED_SLASHES) ?: ''
        );
    }

    /**
     * @param array<string, list<array<string, string>>> $entries
     */
    public static function entriesHaveRows(array $entries): bool
    {
        foreach (self::FUNDING_SECTIONS as $section) {
            if (($entries[$section] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, list<array<string, string>>>
     */
    public static function mergedEntriesFromApprovedFaculty(int $collegeId, ?string $reportAsOf = null): array
    {
        $merged = self::emptyEntries();
        foreach (self::facultyOngoingReportsForCollege($collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if ($reportAsOf !== null && $reportAsOf !== '') {
                if (trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                    continue;
                }
            }

            $merged = self::appendEntries($merged, self::normalizeEntries($summary['entries'] ?? []));
        }

        return $merged;
    }

    /**
     * @return array<string, list<array<string, string>>>
     */
    public static function mergedEntriesFromDirectorApprovedForVpride(?string $reportAsOf = null): array
    {
        $merged = self::emptyEntries();
        foreach (self::allFacultyOngoingReports() as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if ($reportAsOf !== null && $reportAsOf !== '') {
                if (trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                    continue;
                }
            }

            $collegeId = (int) ($proposal['college_id'] ?? 0);
            $collegeLabel = trim((string) ($summary['college_name'] ?? ''));
            if ($collegeLabel === '' && $collegeId > 0) {
                $collegeLabel = self::collegeName($collegeId);
            }

            $merged = self::appendEntries(
                $merged,
                self::tagEntriesWithCollege(self::normalizeEntries($summary['entries'] ?? []), $collegeLabel)
            );
        }

        return $merged;
    }

    /** @param array<string, mixed> $proposal */
    private static function qualifiesForConsolidation(array $proposal): bool
    {
        if (!self::isFacultyOngoingForm($proposal) || !self::isFacultySubmitter($proposal)) {
            return false;
        }

        $proposalId = (int) ($proposal['id'] ?? 0);
        if ($proposalId <= 0) {
            return false;
        }

        if (Proposal::approvalAtStep($proposalId, MonitoringRoles::DIRECTOR_RESEARCH) !== null) {
            return true;
        }

        return in_array((string) ($proposal['status'] ?? ''), ['ongoing', 'approved', 'completed'], true);
    }

    /** @param array<string, mixed> $proposal */
    private static function isFacultyOngoingForm(array $proposal): bool
    {
        if ((string) ($proposal['project_type'] ?? '') !== 'research') {
            return false;
        }

        $summary = self::decodeSummary($proposal);
        $formType = (string) ($summary['form_type'] ?? '');
        if ($formType === 'ongoing_researches') {
            return true;
        }

        if ($formType !== '') {
            return false;
        }

        $title = strtolower((string) ($proposal['title'] ?? ''));

        return str_contains($title, 'ongoing researches') && !str_contains($title, 'consolidated');
    }

    /** @param array<string, mixed> $proposal */
    private static function isFacultySubmitter(array $proposal): bool
    {
        $userId = (int) ($proposal['user_id'] ?? 0);
        if ($userId === 0) {
            return false;
        }

        return in_array('faculty', User::roleSlugs($userId), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function facultyOngoingReportsForCollege(int $collegeId): array
    {
        $matched = [];
        foreach (Proposal::forCollege($collegeId) as $proposal) {
            if (!self::isFacultyOngoingForm($proposal)) {
                continue;
            }

            $matched[] = $proposal;
        }

        return $matched;
    }

    /** @return list<array<string, mixed>> */
    private static function allFacultyOngoingReports(): array
    {
        $matched = [];
        foreach (Proposal::all(null, 'research') as $proposal) {
            if (!self::isFacultyOngoingForm($proposal)) {
                continue;
            }

            $matched[] = $proposal;
        }

        return $matched;
    }

    /** @return array<string, mixed> */
    private static function resolveVprideUser(?int $preferredUserId = null): ?array
    {
        if ($preferredUserId !== null && $preferredUserId > 0) {
            $roles = User::roleSlugs($preferredUserId);
            if (in_array(MonitoringRoles::VPRIDE, $roles, true) || in_array('ride_admin', $roles, true)) {
                return User::findById($preferredUserId);
            }
        }

        $user = User::findActiveByRole(MonitoringRoles::VPRIDE);
        if ($user !== null) {
            return $user;
        }

        return User::findActiveByRoleSlugs([MonitoringRoles::VPRIDE, 'ride_admin']);
    }

    /** @return array<string, mixed> */
    private static function resolveCoordinator(int $collegeId, ?int $preferredUserId = null): ?array
    {
        if ($preferredUserId !== null && $preferredUserId > 0) {
            if (User::hasActiveRoleForCollege($preferredUserId, MonitoringRoles::COORDINATOR_RESEARCH, $collegeId)) {
                return User::findById($preferredUserId);
            }
        }

        return User::findActiveByRoleAndCollege(MonitoringRoles::COORDINATOR_RESEARCH, $collegeId);
    }

    /** @return array<string, mixed> */
    private static function decodeSummary(array $proposal): array
    {
        $decoded = json_decode((string) ($proposal['summary'] ?? ''), true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, list<array<string, string>>> */
    private static function emptyEntries(): array
    {
        $entries = [];
        foreach (self::FUNDING_SECTIONS as $section) {
            $entries[$section] = [];
        }

        return $entries;
    }

    /**
     * @param mixed $entries
     * @return array<string, list<array<string, string>>>
     */
    private static function normalizeEntries(mixed $entries): array
    {
        $normalized = self::emptyEntries();
        if (!is_array($entries)) {
            return $normalized;
        }

        foreach (self::FUNDING_SECTIONS as $section) {
            $rows = $entries[$section] ?? [];
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $entry = [
                    'research_title' => trim((string) ($row['research_title'] ?? '')),
                    'researcher_names' => trim((string) ($row['researcher_names'] ?? '')),
                    'date_started' => trim((string) ($row['date_started'] ?? '')),
                    'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                    'duration_months' => trim((string) ($row['duration_months'] ?? '')),
                    'budget_source' => trim((string) ($row['budget_source'] ?? '')),
                    'budget_amount' => trim((string) ($row['budget_amount'] ?? '')),
                    'category' => trim((string) ($row['category'] ?? '')),
                    'remarks' => trim((string) ($row['remarks'] ?? '')),
                    'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
                ];

                foreach ($entry as $value) {
                    if ($value !== '') {
                        $normalized[$section][] = $entry;
                        break;
                    }
                }
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, list<array<string, string>>> $target
     * @param array<string, list<array<string, string>>> $addition
     * @return array<string, list<array<string, string>>>
     */
    private static function appendEntries(array $target, array $addition): array
    {
        foreach (self::FUNDING_SECTIONS as $section) {
            foreach ($addition[$section] ?? [] as $row) {
                $target[$section][] = $row;
            }
        }

        return $target;
    }

    private static function findEditableCollegeConsolidatedDraft(int $coordinatorUserId, int $collegeId, string $reportAsOf): ?array
    {
        foreach (Proposal::forUser($coordinatorUserId) as $proposal) {
            if ((int) ($proposal['college_id'] ?? 0) !== $collegeId) {
                continue;
            }

            if (!in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if (($summary['form_type'] ?? '') !== 'consolidated_ongoing_researches') {
                continue;
            }

            if ((string) ($summary['consolidation_scope'] ?? self::SCOPE_COLLEGE) === self::SCOPE_VPRIDE) {
                continue;
            }

            if ($reportAsOf !== '' && trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                continue;
            }

            if ($reportAsOf === '' && trim((string) ($summary['report_as_of'] ?? '')) !== '') {
                continue;
            }

            return $proposal;
        }

        return null;
    }

    private static function findEditableVprideConsolidatedDraft(int $vprideUserId, string $reportAsOf): ?array
    {
        foreach (Proposal::forUser($vprideUserId) as $proposal) {
            if (!in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if (($summary['form_type'] ?? '') !== 'consolidated_ongoing_researches') {
                continue;
            }

            if ((string) ($summary['consolidation_scope'] ?? '') !== self::SCOPE_VPRIDE) {
                continue;
            }

            if ($reportAsOf !== '' && trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                continue;
            }

            if ($reportAsOf === '' && trim((string) ($summary['report_as_of'] ?? '')) !== '') {
                continue;
            }

            return $proposal;
        }

        return null;
    }

    /**
     * @param array<string, list<array<string, string>>> $facultyEntries
     */
    private static function createCollegeConsolidatedDraft(
        int $coordinatorUserId,
        int $collegeId,
        ?int $campusId,
        string $reportAsOf,
        string $collegeName,
        int $facultyProposalId,
        array $facultyEntries
    ): void {
        $sourceEntries = [
            (string) $facultyProposalId => $facultyEntries,
        ];
        $entries = self::entriesFromSourceMap($sourceEntries);

        $title = self::buildTitle($collegeName, $reportAsOf);
        $summary = [
            'form_type' => 'consolidated_ongoing_researches',
            'consolidation_scope' => self::SCOPE_COLLEGE,
            'version' => 1,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
            'source_entries' => $sourceEntries,
            'manual_entries' => self::emptyEntries(),
        ];

        Proposal::create([
            'user_id' => $coordinatorUserId,
            'college_id' => $collegeId,
            'campus_id' => $campusId,
            'project_type' => 'research',
            'title' => $title,
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES) ?: '',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ]);
    }

    /**
     * @param array<string, list<array<string, string>>> $facultyEntries
     */
    private static function createVprideConsolidatedDraft(
        int $vprideUserId,
        string $reportAsOf,
        int $facultyProposalId,
        array $facultyEntries
    ): void {
        $sourceEntries = [
            (string) $facultyProposalId => $facultyEntries,
        ];
        $entries = self::entriesFromSourceMap($sourceEntries);
        $label = 'University-wide (All Colleges)';

        $summary = [
            'form_type' => 'consolidated_ongoing_researches',
            'consolidation_scope' => self::SCOPE_VPRIDE,
            'version' => 1,
            'report_as_of' => $reportAsOf,
            'college_name' => $label,
            'entries' => $entries,
            'source_entries' => $sourceEntries,
            'manual_entries' => self::emptyEntries(),
        ];

        Proposal::create([
            'user_id' => $vprideUserId,
            'college_id' => self::containerCollegeId(),
            'campus_id' => null,
            'project_type' => 'research',
            'title' => self::buildTitle($label, $reportAsOf),
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES) ?: '',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ]);
    }

    /**
     * @param array<string, list<array<string, string>>> $entries
     * @return array<string, list<array<string, string>>>
     */
    private static function tagEntriesWithCollege(array $entries, string $collegeLabel): array
    {
        if ($collegeLabel === '') {
            return $entries;
        }

        foreach (self::FUNDING_SECTIONS as $section) {
            foreach ($entries[$section] as $index => $row) {
                $entries[$section][$index]['category'] = $collegeLabel;
            }
        }

        return $entries;
    }

    /**
     * @param array<string, list<array<string, string>>> $facultyEntries
     */
    private static function mergeIntoConsolidated(
        int $consolidatedId,
        int $facultyProposalId,
        array $facultyEntries,
        string $reportAsOf,
        string $collegeName
    ): void {
        $consolidated = Proposal::find($consolidatedId);
        if ($consolidated === null) {
            return;
        }

        $summary = self::decodeSummary($consolidated);
        $sourceEntries = is_array($summary['source_entries'] ?? null) ? $summary['source_entries'] : [];
        $sourceEntries[(string) $facultyProposalId] = $facultyEntries;
        $manualEntries = self::normalizeEntries($summary['manual_entries'] ?? []);
        $entries = self::appendEntries(self::entriesFromSourceMap($sourceEntries), $manualEntries);

        if ($reportAsOf !== '' && trim((string) ($summary['report_as_of'] ?? '')) === '') {
            $summary['report_as_of'] = $reportAsOf;
        }

        if ($collegeName !== '' && trim((string) ($summary['college_name'] ?? '')) === '') {
            $summary['college_name'] = $collegeName;
        }

        $summary['entries'] = $entries;
        $summary['source_entries'] = $sourceEntries;
        $summary['manual_entries'] = $manualEntries;
        $summary['form_type'] = 'consolidated_ongoing_researches';
        if (!isset($summary['consolidation_scope'])) {
            $summary['consolidation_scope'] = self::SCOPE_COLLEGE;
        }

        $title = self::buildTitle(
            trim((string) ($summary['college_name'] ?? '')),
            trim((string) ($summary['report_as_of'] ?? ''))
        );

        Proposal::updateConsolidatedDraft($consolidatedId, $title, json_encode($summary, JSON_UNESCAPED_SLASHES) ?: '');
    }

    /**
     * @param array<string, mixed> $sourceEntries
     * @return array<string, list<array<string, string>>>
     */
    private static function entriesFromSourceMap(array $sourceEntries): array
    {
        $entries = self::emptyEntries();
        foreach ($sourceEntries as $facultyEntries) {
            if (!is_array($facultyEntries)) {
                continue;
            }

            $entries = self::appendEntries($entries, self::normalizeEntries($facultyEntries));
        }

        return $entries;
    }

    private static function buildTitle(string $collegeName, string $reportAsOf): string
    {
        $title = 'Consolidated Ongoing Researches';
        if ($collegeName !== '') {
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
        }

        return $title;
    }

    private static function collegeName(int $collegeId): string
    {
        foreach (College::all() as $college) {
            if ((int) ($college['id'] ?? 0) === $collegeId) {
                return (string) ($college['name'] ?? '');
            }
        }

        return '';
    }

    /**
     * @param array<string, list<array<string, string>>> $postedEntries
     * @return array<string, mixed>
     */
    public static function summaryForCoordinatorSave(array $existingSummary, array $postedEntries): array
    {
        $sourceEntries = is_array($existingSummary['source_entries'] ?? null)
            ? $existingSummary['source_entries']
            : [];
        $fromSources = self::entriesFromSourceMap($sourceEntries);
        $normalizedPosted = self::normalizeEntries($postedEntries);
        $manualEntries = self::subtractDuplicateRows(
            $normalizedPosted,
            $fromSources
        );

        $existingSummary['form_type'] = 'consolidated_ongoing_researches';
        $existingSummary['source_entries'] = $sourceEntries;
        $existingSummary['manual_entries'] = $manualEntries;
        $existingSummary['entries'] = $normalizedPosted;

        return $existingSummary;
    }

    /**
     * @param array<string, list<array<string, string>>> $posted
     * @param array<string, list<array<string, string>>> $autoSynced
     * @return array<string, list<array<string, string>>>
     */
    private static function subtractDuplicateRows(array $posted, array $autoSynced): array
    {
        $manual = self::emptyEntries();

        foreach (self::FUNDING_SECTIONS as $section) {
            foreach ($posted[$section] ?? [] as $row) {
                if (!self::rowExistsIn($row, $autoSynced[$section] ?? [])) {
                    $manual[$section][] = $row;
                }
            }
        }

        return $manual;
    }

    /** @param array<string, string> $row @param list<array<string, string>> $haystack */
    private static function rowExistsIn(array $row, array $haystack): bool
    {
        foreach ($haystack as $candidate) {
            if ($row === $candidate) {
                return true;
            }
        }

        return false;
    }
}
