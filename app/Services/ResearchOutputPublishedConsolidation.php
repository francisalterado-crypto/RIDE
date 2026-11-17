<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\College;
use App\Models\Proposal;
use App\Models\User;
use App\Support\AccessControl;

final class ResearchOutputPublishedConsolidation
{
    private const FUNDING_SECTIONS = ['external', 'inhouse', 'personal'];

    public static function depositOnDirectorApproval(int $facultyProposalId): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::isFacultyPublishedForm($facultyProposal) || !self::isFacultySubmitter($facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($facultyProposalId, null);
    }

    public static function syncFacultyReportToConsolidated(int $facultyProposalId, ?int $preferredCoordinatorUserId = null): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::qualifiesForConsolidation($facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($facultyProposalId, $preferredCoordinatorUserId);
    }

    public static function syncAllFacultyReportsForCollege(int $collegeId, ?int $preferredCoordinatorUserId = null): void
    {
        if ($collegeId <= 0 || self::resolveCoordinator($collegeId, $preferredCoordinatorUserId) === null) {
            return;
        }

        foreach (self::facultyPublishedReportsForCollege($collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            self::syncToCollegeConsolidated((int) $proposal['id'], $preferredCoordinatorUserId);
        }
    }

    public static function refreshConsolidatedProposal(int $consolidatedProposalId): void
    {
        $consolidated = Proposal::find($consolidatedProposalId);
        if ($consolidated === null) {
            return;
        }

        $summary = self::decodeSummary($consolidated);
        if (($summary['form_type'] ?? '') !== 'consolidated_research_output_published') {
            return;
        }

        if (!in_array((string) ($consolidated['status'] ?? ''), ['draft', 'returned'], true)) {
            return;
        }

        $collegeId = (int) ($consolidated['college_id'] ?? 0);
        if ($collegeId === 0) {
            return;
        }

        $reportAsOf = trim((string) ($summary['report_as_of'] ?? ''));
        $manualEntries = self::normalizeConsolidatedEntries($summary['manual_entries'] ?? []);
        $sourceEntries = [];

        foreach (self::facultyPublishedReportsForCollege($collegeId) as $facultyProposal) {
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

            $sourceEntries[(string) $facultyProposal['id']] = self::mapFacultyEntriesToConsolidated(
                self::normalizeFacultyEntries($facultySummary['entries'] ?? []),
                self::collegeLabelForProposal($facultyProposal, $facultySummary),
                self::campusLabelForProposal($facultyProposal)
            );
        }

        $summary['source_entries'] = $sourceEntries;
        $summary['entries'] = self::appendEntries(self::entriesFromSourceMap($sourceEntries), $manualEntries);
        $summary['form_type'] = 'consolidated_research_output_published';
        $summary['manual_entries'] = $manualEntries;

        Proposal::updateConsolidatedDraft(
            $consolidatedProposalId,
            self::buildTitle(
                trim((string) ($summary['college_name'] ?? '')),
                trim((string) ($summary['report_as_of'] ?? ''))
            ),
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
        foreach (self::facultyPublishedReportsForCollege($collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($proposal)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if ($reportAsOf !== null && $reportAsOf !== '') {
                if (trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                    continue;
                }
            }

            $merged = self::appendEntries(
                $merged,
                self::mapFacultyEntriesToConsolidated(
                    self::normalizeFacultyEntries($summary['entries'] ?? []),
                    self::collegeLabelForProposal($proposal, $summary),
                    self::campusLabelForProposal($proposal)
                )
            );
        }

        return $merged;
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
        $manualEntries = self::subtractDuplicateRows(
            self::normalizeConsolidatedEntries($postedEntries),
            $fromSources
        );
        $entries = self::appendEntries($fromSources, $manualEntries);

        $existingSummary['form_type'] = 'consolidated_research_output_published';
        $existingSummary['source_entries'] = $sourceEntries;
        $existingSummary['manual_entries'] = $manualEntries;
        $existingSummary['entries'] = $entries;

        return $existingSummary;
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
        $collegeName = self::collegeLabelForProposal($facultyProposal, $facultySummary);
        $facultyEntries = self::mapFacultyEntriesToConsolidated(
            self::normalizeFacultyEntries($facultySummary['entries'] ?? []),
            $collegeName,
            self::campusLabelForProposal($facultyProposal)
        );

        $consolidated = self::findEditableConsolidatedDraft(
            (int) $coordinator['id'],
            $collegeId,
            $reportAsOf
        );

        if ($consolidated === null) {
            self::createConsolidatedDraft(
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

    /** @param array<string, mixed> $proposal */
    private static function qualifiesForConsolidation(array $proposal): bool
    {
        if (!self::isFacultyPublishedForm($proposal) || !self::isFacultySubmitter($proposal)) {
            return false;
        }

        $proposalId = (int) ($proposal['id'] ?? 0);
        if ($proposalId <= 0) {
            return false;
        }

        if (Proposal::approvalAtStep($proposalId, AccessControl::DIRECTOR_RESEARCH) !== null) {
            return true;
        }

        return in_array((string) ($proposal['status'] ?? ''), ['ongoing', 'approved', 'completed'], true);
    }

    /** @param array<string, mixed> $proposal */
    private static function isFacultyPublishedForm(array $proposal): bool
    {
        if ((string) ($proposal['project_type'] ?? '') !== 'research') {
            return false;
        }

        $summary = self::decodeSummary($proposal);
        $formType = (string) ($summary['form_type'] ?? '');
        if ($formType === 'research_output_published') {
            return true;
        }

        if ($formType !== '') {
            return false;
        }

        $title = strtolower((string) ($proposal['title'] ?? ''));

        return str_contains($title, 'research output published') && !str_contains($title, 'consolidated');
    }

    /** @param array<string, mixed> $proposal */
    private static function isFacultySubmitter(array $proposal): bool
    {
        $userId = (int) ($proposal['user_id'] ?? 0);

        return $userId > 0 && in_array('faculty', User::roleSlugs($userId), true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function facultyPublishedReportsForCollege(int $collegeId): array
    {
        $matched = [];
        foreach (Proposal::forCollege($collegeId) as $proposal) {
            if (self::isFacultyPublishedForm($proposal)) {
                $matched[] = $proposal;
            }
        }

        return $matched;
    }

    /** @param array<string, mixed> $proposal @param array<string, mixed> $summary */
    private static function collegeLabelForProposal(array $proposal, array $summary): string
    {
        $collegeName = trim((string) ($summary['college_name'] ?? ''));
        if ($collegeName !== '') {
            return $collegeName;
        }

        if (!empty($proposal['college_name'])) {
            return (string) $proposal['college_name'];
        }

        return self::collegeName((int) ($proposal['college_id'] ?? 0));
    }

    /** @param array<string, mixed> $proposal */
    private static function campusLabelForProposal(array $proposal): string
    {
        return trim((string) ($proposal['campus_name'] ?? ''));
    }

    /** @return array<string, mixed> */
    private static function resolveCoordinator(int $collegeId, ?int $preferredUserId = null): ?array
    {
        if ($preferredUserId !== null && $preferredUserId > 0) {
            if (User::hasActiveRoleForCollege($preferredUserId, AccessControl::COORDINATOR_RESEARCH, $collegeId)) {
                return User::findById($preferredUserId);
            }
        }

        return User::findActiveByRoleAndCollege(AccessControl::COORDINATOR_RESEARCH, $collegeId);
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
    private static function normalizeFacultyEntries(mixed $entries): array
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
                    'date_started' => trim((string) ($row['date_started'] ?? '')),
                    'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                    'duration_months' => trim((string) ($row['duration_months'] ?? '')),
                    'authors_researchers' => trim((string) ($row['authors_researchers'] ?? '')),
                    'article_title' => trim((string) ($row['article_title'] ?? '')),
                    'journal_book_title' => trim((string) ($row['journal_book_title'] ?? '')),
                    'editors' => trim((string) ($row['editors'] ?? '')),
                    'volume_issue' => trim((string) ($row['volume_issue'] ?? '')),
                    'pages' => trim((string) ($row['pages'] ?? '')),
                    'publication_year' => trim((string) ($row['publication_year'] ?? '')),
                    'indexing' => trim((string) ($row['indexing'] ?? '')),
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
     * @param array<string, list<array<string, string>>> $facultyEntries
     * @return array<string, list<array<string, string>>>
     */
    private static function mapFacultyEntriesToConsolidated(
        array $facultyEntries,
        string $collegeName,
        string $campusName = ''
    ): array {
        $mapped = self::emptyEntries();

        foreach (self::FUNDING_SECTIONS as $section) {
            foreach ($facultyEntries[$section] ?? [] as $row) {
                $mapped[$section][] = [
                    'research_title' => $row['research_title'],
                    'date_started' => $row['date_started'],
                    'date_completed' => $row['date_completed'],
                    'duration_months' => $row['duration_months'],
                    'authors_researchers' => $row['authors_researchers'],
                    'article_title' => $row['article_title'],
                    'journal_book_title' => $row['journal_book_title'],
                    'volume_issue' => $row['volume_issue'],
                    'pages' => $row['pages'],
                    'publication_year' => $row['publication_year'],
                    'type_of_publication' => self::publicationTypeLabel($row),
                    'college' => $collegeName,
                    'campus' => $campusName,
                ];
            }
        }

        return $mapped;
    }

    /** @param array<string, string> $row */
    private static function publicationTypeLabel(array $row): string
    {
        $indexing = $row['indexing'] ?? '';
        $editors = $row['editors'] ?? '';
        if ($indexing === '') {
            return $editors;
        }

        if ($editors === '') {
            return $indexing;
        }

        return $indexing . '; Editors: ' . $editors;
    }

    /**
     * @param mixed $entries
     * @return array<string, list<array<string, string>>>
     */
    private static function normalizeConsolidatedEntries(mixed $entries): array
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
                    'date_started' => trim((string) ($row['date_started'] ?? '')),
                    'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                    'duration_months' => trim((string) ($row['duration_months'] ?? '')),
                    'authors_researchers' => trim((string) ($row['authors_researchers'] ?? '')),
                    'article_title' => trim((string) ($row['article_title'] ?? '')),
                    'journal_book_title' => trim((string) ($row['journal_book_title'] ?? '')),
                    'volume_issue' => trim((string) ($row['volume_issue'] ?? '')),
                    'pages' => trim((string) ($row['pages'] ?? '')),
                    'publication_year' => trim((string) ($row['publication_year'] ?? '')),
                    'type_of_publication' => trim((string) ($row['type_of_publication'] ?? '')),
                    'college' => trim((string) ($row['college'] ?? '')),
                    'campus' => trim((string) ($row['campus'] ?? '')),
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

    private static function findEditableConsolidatedDraft(int $coordinatorUserId, int $collegeId, string $reportAsOf): ?array
    {
        foreach (Proposal::editableForUserByFormType($coordinatorUserId, 'consolidated_research_output_published') as $proposal) {
            if ((int) ($proposal['college_id'] ?? 0) !== $collegeId) {
                continue;
            }

            if (!in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if (($summary['form_type'] ?? '') !== 'consolidated_research_output_published') {
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
    private static function createConsolidatedDraft(
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

        $summary = [
            'form_type' => 'consolidated_research_output_published',
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
            'title' => self::buildTitle($collegeName, $reportAsOf),
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES) ?: '',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ]);
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
        $manualEntries = self::normalizeConsolidatedEntries($summary['manual_entries'] ?? []);
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
        $summary['form_type'] = 'consolidated_research_output_published';

        Proposal::updateConsolidatedDraft(
            $consolidatedId,
            self::buildTitle(
                trim((string) ($summary['college_name'] ?? '')),
                trim((string) ($summary['report_as_of'] ?? ''))
            ),
            json_encode($summary, JSON_UNESCAPED_SLASHES) ?: ''
        );
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

            $entries = self::appendEntries($entries, self::normalizeConsolidatedEntries($facultyEntries));
        }

        return $entries;
    }

    private static function buildTitle(string $collegeName, string $reportAsOf): string
    {
        $title = 'Consolidated Research Output Published';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
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
