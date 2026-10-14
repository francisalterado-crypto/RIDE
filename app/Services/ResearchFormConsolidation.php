<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\College;
use App\Models\Proposal;
use App\Models\User;
use App\Support\MonitoringRoles;

/**
 * Consolidates faculty quarterly research forms into coordinator drafts after RDO Director approval.
 */
final class ResearchFormConsolidation
{
    public const FORM_KEYS = [
        'commercialized',
        'resulted_in_extension',
        'journal_citation',
        'book_citation',
        'inventions_um_copyrights',
        'linkages',
    ];

    private const FUNDING_SECTIONS = ['external', 'inhouse', 'personal'];

    private const INVENTIONS_SECTIONS = [
        'inventions_patented',
        'inventions_applied_for_patenting',
        'inventions_not_patented_but_utilized',
        'utility_models_registered',
        'utility_models_applied_for_registration',
        'copyrights',
    ];

    /** @var array<string, array<string, mixed>> */
    private const CONFIG = [
        'commercialized' => [
            'faculty_form_type' => 'commercialized',
            'consolidated_form_type' => 'consolidated_commercialized',
            'consolidated_title' => 'Consolidated Commercialized',
            'title_phrase' => 'commercialized',
            'layout' => 'sectioned',
            'sections' => ['external', 'inhouse', 'personal'],
            'fields' => [
                'research_title', 'researchers', 'date_started', 'date_completed',
                'product_name', 'adopter', 'date_adopted', 'google_drive_link',
            ],
        ],
        'resulted_in_extension' => [
            'faculty_form_type' => 'resulted_in_extension',
            'consolidated_form_type' => 'consolidated_resulted_in_extension',
            'consolidated_title' => 'Consolidated Resulted in Extension',
            'title_phrase' => 'resulted in extension',
            'layout' => 'sectioned',
            'sections' => ['external', 'inhouse', 'personal'],
            'fields' => [
                'research_title', 'researchers', 'date_started', 'date_completed',
                'extension_program_activity', 'faculty_staff_involved', 'budget_source',
                'budget_amount', 'venue', 'date', 'google_drive_link',
            ],
        ],
        'journal_citation' => [
            'faculty_form_type' => 'journal_citation',
            'consolidated_form_type' => 'consolidated_journal_citation',
            'consolidated_title' => 'Consolidated Journal Citation',
            'title_phrase' => 'journal citation',
            'layout' => 'flat',
            'fields' => [
                'authors_original_article', 'title_original_article_cited', 'title_refereed_journal_original',
                'title_new_research_article', 'authors_new_article', 'title_refereed_journal_new',
                'volume_issue', 'pages', 'year_publication', 'publisher', 'google_drive_link',
            ],
        ],
        'book_citation' => [
            'faculty_form_type' => 'book_citation',
            'consolidated_form_type' => 'consolidated_book_citation',
            'consolidated_title' => 'Consolidated Book Citation',
            'title_phrase' => 'book citation',
            'layout' => 'flat',
            'fields' => [
                'title_original_article_cited', 'authors_original_article', 'title_publication_original',
                'title_new_book_chapter', 'authors_book_chapter', 'title_book_chapter_published',
                'volume_issue', 'pages', 'year_publication', 'isbn', 'publisher', 'google_drive_link',
            ],
        ],
        'inventions_um_copyrights' => [
            'faculty_form_type' => 'inventions_um_copyrights',
            'consolidated_form_type' => 'consolidated_inventions_um_copyrights',
            'consolidated_title' => 'Consolidated Inventions, UM, Copyrights',
            'title_phrase' => 'inventions',
            'layout' => 'sectioned',
            'sections' => [
                'inventions_patented',
                'inventions_applied_for_patenting',
                'inventions_not_patented_but_utilized',
                'utility_models_registered',
                'utility_models_applied_for_registration',
                'copyrights',
            ],
            'fields' => [
                'research_title', 'date_started', 'date_developed_completed', 'inventors_researchers',
                'patent_registration_copyright_number', 'date_of_issue_application', 'adopter',
                'commercial_product_name', 'google_drive_link',
            ],
        ],
        'linkages' => [
            'faculty_form_type' => 'linkages',
            'consolidated_form_type' => 'consolidated_linkages',
            'consolidated_title' => 'Consolidated Linkages',
            'title_phrase' => 'linkages',
            'layout' => 'flat',
            'fields' => [
                'program', 'partner', 'linkage_forged', 'institution_type', 'deliverables',
                'date_started', 'date_completed', 'personnel', 'beneficiaries', 'google_drive_link',
            ],
        ],
    ];

    public static function depositOnDirectorApproval(string $formKey, int $facultyProposalId): void
    {
        $config = self::config($formKey);
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::isFacultyForm($facultyProposal, $config) || !self::isFacultySubmitter($facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($formKey, $facultyProposalId, null);
    }

    public static function syncFacultyReportToConsolidated(string $formKey, int $facultyProposalId, ?int $preferredCoordinatorUserId = null): void
    {
        $facultyProposal = Proposal::find($facultyProposalId);
        if ($facultyProposal === null || !self::qualifiesForConsolidation($formKey, $facultyProposal)) {
            return;
        }

        self::syncToCollegeConsolidated($formKey, $facultyProposalId, $preferredCoordinatorUserId);
    }

    public static function syncAllFacultyReportsForCollege(string $formKey, int $collegeId, ?int $preferredCoordinatorUserId = null): void
    {
        if ($collegeId <= 0 || self::resolveCoordinator($collegeId, $preferredCoordinatorUserId) === null) {
            return;
        }

        foreach (self::facultyReportsForCollege($formKey, $collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($formKey, $proposal)) {
                continue;
            }

            self::syncToCollegeConsolidated($formKey, (int) $proposal['id'], $preferredCoordinatorUserId);
        }
    }

    public static function refreshConsolidatedProposal(string $formKey, int $consolidatedProposalId): void
    {
        $config = self::config($formKey);
        $consolidated = Proposal::find($consolidatedProposalId);
        if ($consolidated === null) {
            return;
        }

        $summary = self::decodeSummary($consolidated);
        if (($summary['form_type'] ?? '') !== $config['consolidated_form_type']) {
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
        $manualEntries = self::normalizeConsolidatedEntries($formKey, $summary['manual_entries'] ?? []);
        $sourceEntries = [];

        foreach (self::facultyReportsForCollege($formKey, $collegeId) as $facultyProposal) {
            if (!self::qualifiesForConsolidation($formKey, $facultyProposal)) {
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
                $formKey,
                self::normalizeFacultyEntries($formKey, $facultySummary['entries'] ?? []),
                self::collegeLabelForProposal($facultyProposal, $facultySummary),
                self::campusLabelForProposal($facultyProposal)
            );
        }

        $summary['source_entries'] = $sourceEntries;
        $summary['entries'] = self::appendEntries(
            $formKey,
            self::entriesFromSourceMap($formKey, $sourceEntries),
            $manualEntries
        );
        $summary['form_type'] = $config['consolidated_form_type'];
        $summary['manual_entries'] = $manualEntries;

        Proposal::updateConsolidatedDraft(
            $consolidatedProposalId,
            self::buildTitle(
                (string) $config['consolidated_title'],
                trim((string) ($summary['college_name'] ?? '')),
                trim((string) ($summary['report_as_of'] ?? ''))
            ),
            json_encode($summary, JSON_UNESCAPED_SLASHES) ?: ''
        );
    }

    /** @param mixed $entries */
    public static function entriesHaveRows(string $formKey, mixed $entries): bool
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            if (!is_array($entries)) {
                return false;
            }

            foreach ($entries as $row) {
                if (is_array($row) && self::rowHasContent($formKey, $row)) {
                    return true;
                }
            }

            return false;
        }

        if (!is_array($entries)) {
            return false;
        }

        foreach ($config['sections'] as $section) {
            if (($entries[$section] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /** @return mixed */
    public static function mergedEntriesFromApprovedFaculty(string $formKey, int $collegeId, ?string $reportAsOf = null): mixed
    {
        $merged = self::emptyEntries($formKey);
        foreach (self::facultyReportsForCollege($formKey, $collegeId) as $proposal) {
            if (!self::qualifiesForConsolidation($formKey, $proposal)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if ($reportAsOf !== null && $reportAsOf !== '') {
                if (trim((string) ($summary['report_as_of'] ?? '')) !== $reportAsOf) {
                    continue;
                }
            }

            $merged = self::appendEntries(
                $formKey,
                $merged,
                self::mapFacultyEntriesToConsolidated(
                    $formKey,
                    self::normalizeFacultyEntries($formKey, $summary['entries'] ?? []),
                    self::collegeLabelForProposal($proposal, $summary),
                    self::campusLabelForProposal($proposal)
                )
            );
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $existingSummary
     * @param mixed $postedEntries
     * @return array<string, mixed>
     */
    public static function summaryForCoordinatorSave(string $formKey, array $existingSummary, mixed $postedEntries): array
    {
        $config = self::config($formKey);
        $sourceEntries = is_array($existingSummary['source_entries'] ?? null)
            ? $existingSummary['source_entries']
            : [];
        $fromSources = self::entriesFromSourceMap($formKey, $sourceEntries);
        $manualEntries = self::subtractDuplicateRows(
            $formKey,
            self::normalizeConsolidatedEntries($formKey, $postedEntries),
            $fromSources
        );
        $entries = self::appendEntries($formKey, $fromSources, $manualEntries);

        $existingSummary['form_type'] = $config['consolidated_form_type'];
        $existingSummary['source_entries'] = $sourceEntries;
        $existingSummary['manual_entries'] = $manualEntries;
        $existingSummary['entries'] = $entries;

        return $existingSummary;
    }

    public static function consolidatedFormType(string $formKey): string
    {
        return (string) self::config($formKey)['consolidated_form_type'];
    }

    public static function routeSlug(string $formKey): string
    {
        return 'consolidated-' . str_replace('_', '-', $formKey);
    }

    public static function viewSlug(string $formKey): string
    {
        return self::routeSlug($formKey);
    }

    /** @return array<string, mixed> */
    public static function config(string $formKey): array
    {
        if (!isset(self::CONFIG[$formKey])) {
            throw new \InvalidArgumentException('Unknown consolidation form key: ' . $formKey);
        }

        return self::CONFIG[$formKey];
    }

    private static function syncToCollegeConsolidated(string $formKey, int $facultyProposalId, ?int $preferredCoordinatorUserId): void
    {
        $config = self::config($formKey);
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
            $formKey,
            self::normalizeFacultyEntries($formKey, $facultySummary['entries'] ?? []),
            $collegeName,
            self::campusLabelForProposal($facultyProposal)
        );

        $consolidated = self::findEditableConsolidatedDraft(
            $formKey,
            (int) $coordinator['id'],
            $collegeId,
            $reportAsOf
        );

        if ($consolidated === null) {
            self::createConsolidatedDraft(
                $formKey,
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
            $formKey,
            (int) $consolidated['id'],
            $facultyProposalId,
            $facultyEntries,
            $reportAsOf,
            $collegeName
        );
    }

    /** @param array<string, mixed> $proposal */
    private static function qualifiesForConsolidation(string $formKey, array $proposal): bool
    {
        $config = self::config($formKey);
        if (!self::isFacultyForm($proposal, $config) || !self::isFacultySubmitter($proposal)) {
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

    /**
     * @param array<string, mixed> $proposal
     * @param array<string, mixed> $config
     */
    private static function isFacultyForm(array $proposal, array $config): bool
    {
        if ((string) ($proposal['project_type'] ?? '') !== 'research') {
            return false;
        }

        $summary = self::decodeSummary($proposal);
        $formType = (string) ($summary['form_type'] ?? '');
        $facultyType = (string) $config['faculty_form_type'];
        if ($formType === $facultyType) {
            return true;
        }

        if ($formType !== '') {
            return false;
        }

        $title = strtolower((string) ($proposal['title'] ?? ''));
        $phrase = (string) $config['title_phrase'];

        return str_contains($title, $phrase) && !str_contains($title, 'consolidated');
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
    private static function facultyReportsForCollege(string $formKey, int $collegeId): array
    {
        $config = self::config($formKey);
        $matched = [];
        foreach (Proposal::forCollege($collegeId) as $proposal) {
            if (self::isFacultyForm($proposal, $config)) {
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

    /** @return mixed */
    private static function emptyEntries(string $formKey): mixed
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            return [];
        }

        $entries = [];
        foreach ($config['sections'] as $section) {
            $entries[$section] = [];
        }

        return $entries;
    }

    /**
     * @param mixed $entries
     * @return mixed
     */
    private static function normalizeFacultyEntries(string $formKey, mixed $entries): mixed
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            return self::normalizeFlatRows($formKey, $entries, false);
        }

        $normalized = self::emptyEntries($formKey);
        if (!is_array($entries)) {
            return $normalized;
        }

        foreach ($config['sections'] as $section) {
            $rows = $entries[$section] ?? [];
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $entry = self::extractFields($formKey, $row);
                if (self::rowHasContent($formKey, $entry)) {
                    $normalized[$section][] = $entry;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $entries
     * @return mixed
     */
    private static function normalizeConsolidatedEntries(string $formKey, mixed $entries): mixed
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            return self::normalizeFlatRows($formKey, $entries, true);
        }

        $normalized = self::emptyEntries($formKey);
        if (!is_array($entries)) {
            return $normalized;
        }

        foreach ($config['sections'] as $section) {
            $rows = $entries[$section] ?? [];
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $entry = self::extractFields($formKey, $row, true);
                if (self::rowHasContent($formKey, $entry)) {
                    $normalized[$section][] = $entry;
                }
            }
        }

        return $normalized;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function normalizeFlatRows(string $formKey, mixed $entries, bool $includeCollegeCampus): array
    {
        if (!is_array($entries)) {
            return [];
        }

        $normalized = [];
        foreach ($entries as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = self::extractFields($formKey, $row, $includeCollegeCampus);
            if (self::rowHasContent($formKey, $entry)) {
                $normalized[] = $entry;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private static function extractFields(string $formKey, array $row, bool $includeCollegeCampus = false): array
    {
        $config = self::config($formKey);
        $entry = [];
        foreach ($config['fields'] as $field) {
            $entry[$field] = trim((string) ($row[$field] ?? ''));
        }

        if ($includeCollegeCampus) {
            $entry['college'] = trim((string) ($row['college'] ?? ''));
            $entry['campus'] = trim((string) ($row['campus'] ?? ''));
        }

        return $entry;
    }

    /** @param array<string, string> $row */
    private static function rowHasContent(string $formKey, array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $facultyEntries
     * @return mixed
     */
    private static function mapFacultyEntriesToConsolidated(
        string $formKey,
        mixed $facultyEntries,
        string $collegeName,
        string $campusName = ''
    ): mixed {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            $mapped = [];
            if (!is_array($facultyEntries)) {
                return $mapped;
            }

            foreach ($facultyEntries as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $entry = self::extractFields($formKey, $row);
                $entry['college'] = $collegeName;
                $entry['campus'] = $campusName;
                $mapped[] = $entry;
            }

            return $mapped;
        }

        $mapped = self::emptyEntries($formKey);
        if (!is_array($facultyEntries)) {
            return $mapped;
        }

        foreach ($config['sections'] as $section) {
            foreach ($facultyEntries[$section] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $entry = self::extractFields($formKey, $row);
                $entry['college'] = $collegeName;
                $entry['campus'] = $campusName;
                $mapped[$section][] = $entry;
            }
        }

        return $mapped;
    }

    /**
     * @param mixed $target
     * @param mixed $addition
     * @return mixed
     */
    private static function appendEntries(string $formKey, mixed $target, mixed $addition): mixed
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            $result = is_array($target) ? $target : [];
            if (is_array($addition)) {
                foreach ($addition as $row) {
                    if (is_array($row)) {
                        $result[] = $row;
                    }
                }
            }

            return $result;
        }

        $result = is_array($target) ? $target : self::emptyEntries($formKey);
        if (!is_array($addition)) {
            return $result;
        }

        foreach ($config['sections'] as $section) {
            foreach ($addition[$section] ?? [] as $row) {
                if (is_array($row)) {
                    $result[$section][] = $row;
                }
            }
        }

        return $result;
    }

    private static function findEditableConsolidatedDraft(
        string $formKey,
        int $coordinatorUserId,
        int $collegeId,
        string $reportAsOf
    ): ?array {
        $config = self::config($formKey);
        foreach (Proposal::forUser($coordinatorUserId) as $proposal) {
            if ((int) ($proposal['college_id'] ?? 0) !== $collegeId) {
                continue;
            }

            if (!in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                continue;
            }

            $summary = self::decodeSummary($proposal);
            if (($summary['form_type'] ?? '') !== $config['consolidated_form_type']) {
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

    /** @param mixed $facultyEntries */
    private static function createConsolidatedDraft(
        string $formKey,
        int $coordinatorUserId,
        int $collegeId,
        ?int $campusId,
        string $reportAsOf,
        string $collegeName,
        int $facultyProposalId,
        mixed $facultyEntries
    ): void {
        $config = self::config($formKey);
        $sourceEntries = [
            (string) $facultyProposalId => $facultyEntries,
        ];
        $entries = self::entriesFromSourceMap($formKey, $sourceEntries);

        $summary = [
            'form_type' => $config['consolidated_form_type'],
            'version' => 1,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
            'source_entries' => $sourceEntries,
            'manual_entries' => self::emptyEntries($formKey),
        ];

        Proposal::create([
            'user_id' => $coordinatorUserId,
            'college_id' => $collegeId,
            'campus_id' => $campusId,
            'project_type' => 'research',
            'title' => self::buildTitle((string) $config['consolidated_title'], $collegeName, $reportAsOf),
            'summary' => json_encode($summary, JSON_UNESCAPED_SLASHES) ?: '',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ]);
    }

    /** @param mixed $facultyEntries */
    private static function mergeIntoConsolidated(
        string $formKey,
        int $consolidatedId,
        int $facultyProposalId,
        mixed $facultyEntries,
        string $reportAsOf,
        string $collegeName
    ): void {
        $config = self::config($formKey);
        $consolidated = Proposal::find($consolidatedId);
        if ($consolidated === null) {
            return;
        }

        $summary = self::decodeSummary($consolidated);
        $sourceEntries = is_array($summary['source_entries'] ?? null) ? $summary['source_entries'] : [];
        $sourceEntries[(string) $facultyProposalId] = $facultyEntries;
        $manualEntries = self::normalizeConsolidatedEntries($formKey, $summary['manual_entries'] ?? []);
        $entries = self::appendEntries($formKey, self::entriesFromSourceMap($formKey, $sourceEntries), $manualEntries);

        if ($reportAsOf !== '' && trim((string) ($summary['report_as_of'] ?? '')) === '') {
            $summary['report_as_of'] = $reportAsOf;
        }

        if ($collegeName !== '' && trim((string) ($summary['college_name'] ?? '')) === '') {
            $summary['college_name'] = $collegeName;
        }

        $summary['entries'] = $entries;
        $summary['source_entries'] = $sourceEntries;
        $summary['manual_entries'] = $manualEntries;
        $summary['form_type'] = $config['consolidated_form_type'];

        Proposal::updateConsolidatedDraft(
            $consolidatedId,
            self::buildTitle(
                (string) $config['consolidated_title'],
                trim((string) ($summary['college_name'] ?? '')),
                trim((string) ($summary['report_as_of'] ?? ''))
            ),
            json_encode($summary, JSON_UNESCAPED_SLASHES) ?: ''
        );
    }

    /**
     * @param array<string, mixed> $sourceEntries
     * @return mixed
     */
    private static function entriesFromSourceMap(string $formKey, array $sourceEntries): mixed
    {
        $entries = self::emptyEntries($formKey);
        foreach ($sourceEntries as $facultyEntries) {
            if (!is_array($facultyEntries)) {
                continue;
            }

            $entries = self::appendEntries(
                $formKey,
                $entries,
                self::normalizeConsolidatedEntries($formKey, $facultyEntries)
            );
        }

        return $entries;
    }

    private static function buildTitle(string $base, string $collegeName, string $reportAsOf): string
    {
        $title = $base;
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
     * @param mixed $posted
     * @param mixed $autoSynced
     * @return mixed
     */
    private static function subtractDuplicateRows(string $formKey, mixed $posted, mixed $autoSynced): mixed
    {
        $config = self::config($formKey);
        if ($config['layout'] === 'flat') {
            $manual = [];
            if (!is_array($posted)) {
                return $manual;
            }

            $haystack = is_array($autoSynced) ? $autoSynced : [];
            foreach ($posted as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if (!self::rowExistsInFlat($row, $haystack)) {
                    $manual[] = $row;
                }
            }

            return $manual;
        }

        $manual = self::emptyEntries($formKey);
        if (!is_array($posted)) {
            return $manual;
        }

        foreach ($config['sections'] as $section) {
            foreach ($posted[$section] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $haystack = is_array($autoSynced) ? ($autoSynced[$section] ?? []) : [];
                if (!self::rowExistsIn($row, $haystack)) {
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

    /** @param array<string, string> $row @param list<array<string, string>> $haystack */
    private static function rowExistsInFlat(array $row, array $haystack): bool
    {
        return self::rowExistsIn($row, $haystack);
    }
}
