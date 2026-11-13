<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Proposal;
use App\Services\ResearchFormConsolidation;
use App\Support\MonitoringRoles;

trait ManagesConsolidatedResearchForms
{
    public function createConsolidatedCommercialized(): void
    {
        $this->createConsolidatedResearchForm('commercialized');
    }

    public function createConsolidatedResultedInExtension(): void
    {
        $this->createConsolidatedResearchForm('resulted_in_extension');
    }

    public function createConsolidatedJournalCitation(): void
    {
        $this->createConsolidatedResearchForm('journal_citation');
    }

    public function createConsolidatedBookCitation(): void
    {
        $this->createConsolidatedResearchForm('book_citation');
    }

    public function createConsolidatedInventionsUmCopyrights(): void
    {
        $this->createConsolidatedResearchForm('inventions_um_copyrights');
    }

    public function createConsolidatedLinkages(): void
    {
        $this->createConsolidatedResearchForm('linkages');
    }

    public function storeConsolidatedCommercialized(): void
    {
        $this->storeConsolidatedResearchForm('commercialized');
    }

    public function storeConsolidatedResultedInExtension(): void
    {
        $this->storeConsolidatedResearchForm('resulted_in_extension');
    }

    public function storeConsolidatedJournalCitation(): void
    {
        $this->storeConsolidatedResearchForm('journal_citation');
    }

    public function storeConsolidatedBookCitation(): void
    {
        $this->storeConsolidatedResearchForm('book_citation');
    }

    public function storeConsolidatedInventionsUmCopyrights(): void
    {
        $this->storeConsolidatedResearchForm('inventions_um_copyrights');
    }

    public function storeConsolidatedLinkages(): void
    {
        $this->storeConsolidatedResearchForm('linkages');
    }

    public function updateConsolidatedCommercialized(int $id): void
    {
        $this->updateConsolidatedResearchForm('commercialized', $id);
    }

    public function updateConsolidatedResultedInExtension(int $id): void
    {
        $this->updateConsolidatedResearchForm('resulted_in_extension', $id);
    }

    public function updateConsolidatedJournalCitation(int $id): void
    {
        $this->updateConsolidatedResearchForm('journal_citation', $id);
    }

    public function updateConsolidatedBookCitation(int $id): void
    {
        $this->updateConsolidatedResearchForm('book_citation', $id);
    }

    public function updateConsolidatedInventionsUmCopyrights(int $id): void
    {
        $this->updateConsolidatedResearchForm('inventions_um_copyrights', $id);
    }

    public function updateConsolidatedLinkages(int $id): void
    {
        $this->updateConsolidatedResearchForm('linkages', $id);
    }

    protected function redirectFacultyToConsolidatedCreate(string $formKey): void
    {
        redirect('proposals/create/' . ResearchFormConsolidation::routeSlug($formKey));
    }

    protected function tryShowConsolidatedResearchForm(int $id, array $proposal): bool
    {
        foreach (ResearchFormConsolidation::FORM_KEYS as $formKey) {
            if (proposal_form_type($proposal) !== ResearchFormConsolidation::consolidatedFormType($formKey)) {
                continue;
            }

            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                ResearchFormConsolidation::refreshConsolidatedProposal($formKey, $id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            view('proposals.' . ResearchFormConsolidation::viewSlug($formKey) . '-show', [
                'proposal' => $proposal,
                'comments' => Proposal::comments($id),
                'canEdit' => $this->canEdit($proposal),
                'canApprove' => $this->canApprove($proposal),
                'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
                'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            ]);

            return true;
        }

        return false;
    }

    protected function tryEditConsolidatedResearchForm(int $id, array $proposal): bool
    {
        foreach (ResearchFormConsolidation::FORM_KEYS as $formKey) {
            if (proposal_form_type($proposal) !== ResearchFormConsolidation::consolidatedFormType($formKey)) {
                continue;
            }

            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                ResearchFormConsolidation::refreshConsolidatedProposal($formKey, $id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            $collegeId = (int) ($proposal['college_id'] ?? 0);
            $prefilledEntries = $collegeId > 0
                ? ResearchFormConsolidation::mergedEntriesFromApprovedFaculty($formKey, $collegeId)
                : [];

            view('proposals.' . ResearchFormConsolidation::viewSlug($formKey) . '-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'prefilledEntries' => $prefilledEntries,
            ]);

            return true;
        }

        return false;
    }

    protected function tryUpdateConsolidatedResearchForm(int $id, array $proposal): bool
    {
        foreach (ResearchFormConsolidation::FORM_KEYS as $formKey) {
            if (proposal_form_type($proposal) !== ResearchFormConsolidation::consolidatedFormType($formKey)) {
                continue;
            }

            if (!$this->allowConsolidatedResearchFormAccess($formKey)) {
                return true;
            }

            if (!verify_csrf()) {
                set_flash('error', 'Invalid session.');
                redirect('proposals/' . $id . '/edit');
            }

            if (!$this->canEdit($proposal)) {
                http_response_code(403);
                view('errors.403');
                return true;
            }

            Proposal::update($id, $this->validatedConsolidatedResearchFormInput($formKey, $proposal));
            AuditLog::record('proposal', $id, 'updated');
            $config = ResearchFormConsolidation::config($formKey);
            set_flash('success', $config['consolidated_title'] . ' report updated.');
            redirect('proposals/' . $id);

            return true;
        }

        return false;
    }

    private function createConsolidatedResearchForm(string $formKey): void
    {
        if (!$this->allowConsolidatedResearchFormAccess($formKey)) {
            return;
        }

        $user = Auth::user();
        $collegeId = (int) ($user['college_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        if ($collegeId > 0) {
            ResearchFormConsolidation::syncAllFacultyReportsForCollege($formKey, $collegeId, $userId);
        }

        $prefilledEntries = $collegeId > 0
            ? ResearchFormConsolidation::mergedEntriesFromApprovedFaculty($formKey, $collegeId)
            : [];

        view('proposals.' . ResearchFormConsolidation::viewSlug($formKey) . '-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'prefilledEntries' => $prefilledEntries,
        ]);
    }

    private function storeConsolidatedResearchForm(string $formKey): void
    {
        if (!$this->allowConsolidatedResearchFormAccess($formKey)) {
            return;
        }

        $route = ResearchFormConsolidation::routeSlug($formKey);
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/' . $route);
        }

        $user = Auth::user();
        $data = $this->validatedConsolidatedResearchFormInput($formKey);
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/' . $route);
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $config = ResearchFormConsolidation::config($formKey);
        set_flash('success', $config['consolidated_title'] . ' report saved as draft.');
        redirect('proposals/' . $id);
    }

    private function updateConsolidatedResearchForm(string $formKey, int $id): void
    {
        if (!$this->allowConsolidatedResearchFormAccess($formKey)) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (
            !$proposal
            || !$this->canEdit($proposal)
            || proposal_form_type($proposal) !== ResearchFormConsolidation::consolidatedFormType($formKey)
        ) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedConsolidatedResearchFormInput($formKey, $proposal));
        AuditLog::record('proposal', $id, 'updated');
        $config = ResearchFormConsolidation::config($formKey);
        set_flash('success', $config['consolidated_title'] . ' report updated.');
        redirect('proposals/' . $id);
    }

    private function allowConsolidatedResearchFormAccess(string $formKey): bool
    {
        if (MonitoringRoles::canAccessConsolidatedResearchForm($formKey)) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    /** @param array<string, mixed>|null $existingProposal */
    private function validatedConsolidatedResearchFormInput(string $formKey, ?array $existingProposal = null): array
    {
        $config = ResearchFormConsolidation::config($formKey);
        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        $consolidatedConfig = ResearchFormConsolidation::config($formKey);
        $formType = (string) ($consolidatedConfig['consolidated_form_type'] ?? '');
        $reporting = quarterly_reporting_require(
            $_POST,
            $existingSummary,
            $formType !== '' ? $formType : null
        );
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $postedEntries = $this->validatedConsolidatedResearchFormEntries($formKey, $_POST['entries'] ?? []);

        $structuredSummary = ResearchFormConsolidation::summaryForCoordinatorSave(
            $formKey,
            $existingSummary,
            $postedEntries
        );
        $structuredSummary['version'] = 1;
        $structuredSummary['reporting_period'] = $reportingPeriod;
        $structuredSummary['report_as_of'] = $reportAsOf;
        $structuredSummary['college_name'] = $collegeName;

        $title = (string) $config['consolidated_title'];
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'research',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    /** @return mixed */
    private function validatedConsolidatedResearchFormEntries(string $formKey, mixed $posted): mixed
    {
        return match ($formKey) {
            'commercialized' => $this->validatedConsolidatedCommercializedEntries($posted),
            'resulted_in_extension' => $this->validatedConsolidatedResultedInExtensionEntries($posted),
            'journal_citation' => $this->validatedConsolidatedJournalCitationRows($posted),
            'book_citation' => $this->validatedConsolidatedBookCitationRows($posted),
            'inventions_um_copyrights' => $this->validatedConsolidatedInventionsUmCopyrightsEntries($posted),
            'linkages' => $this->validatedConsolidatedLinkagesRows($posted),
            default => throw new \InvalidArgumentException('Unknown form key: ' . $formKey),
        };
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedConsolidatedCommercializedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedCommercializedRows($rows);
        }

        return $result;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedConsolidatedResultedInExtensionEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedResultedInExtensionRows($rows);
        }

        return $result;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedConsolidatedInventionsUmCopyrightsEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->inventionsUmCopyrightsSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedInventionsUmCopyrightsRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedCommercializedRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'research_title', 'researchers', 'date_started', 'date_completed',
            'product_name', 'adopter', 'date_adopted', 'google_drive_link',
        ]);
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedResultedInExtensionRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'research_title', 'researchers', 'date_started', 'date_completed',
            'extension_program_activity', 'faculty_staff_involved', 'budget_source',
            'budget_amount', 'venue', 'date', 'google_drive_link',
        ]);
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedJournalCitationRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'authors_original_article', 'title_original_article_cited', 'title_refereed_journal_original',
            'title_new_research_article', 'authors_new_article', 'title_refereed_journal_new',
            'volume_issue', 'pages', 'year_publication', 'publisher', 'google_drive_link',
        ]);
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedBookCitationRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'title_original_article_cited', 'authors_original_article', 'title_publication_original',
            'title_new_book_chapter', 'authors_book_chapter', 'title_book_chapter_published',
            'volume_issue', 'pages', 'year_publication', 'isbn', 'publisher', 'google_drive_link',
        ]);
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedInventionsUmCopyrightsRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'research_title', 'date_started', 'date_developed_completed', 'inventors_researchers',
            'patent_registration_copyright_number', 'date_of_issue_application', 'adopter',
            'commercial_product_name', 'google_drive_link',
        ]);
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedLinkagesRows(mixed $rows): array
    {
        return $this->validatedConsolidatedRowsWithExtra($rows, [
            'program', 'partner', 'linkage_forged', 'institution_type', 'deliverables',
            'date_started', 'date_completed', 'personnel', 'beneficiaries', 'google_drive_link',
        ]);
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, string>>
     */
    private function validatedConsolidatedRowsWithExtra(mixed $rows, array $fields): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [];
            foreach ($fields as $field) {
                $entry[$field] = trim((string) ($row[$field] ?? ''));
            }
            $entry['college'] = trim((string) ($row['college'] ?? ''));
            $entry['campus'] = trim((string) ($row['campus'] ?? ''));

            foreach ($entry as $value) {
                if ($value !== '') {
                    $validated[] = $entry;
                    break;
                }
            }
        }

        return $validated;
    }
}
