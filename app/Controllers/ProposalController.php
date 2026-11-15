<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\ManagesConsolidatedResearchForms;
use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\College;
use App\Models\Document;
use App\Models\Proposal;
use App\Models\User;
use App\Support\MonitoringRoles;

final class ProposalController
{
    use ManagesConsolidatedResearchForms;

    public function index(): void
    {
        $user = Auth::user();
        view('proposals.index', [
            'proposals' => \App\Support\ProposalRegistry::rows($this->registryProposals($user), fn (array $p): bool => $this->canApprove($p)),
            'currentUserId' => (int) $user['id'],
        ]);
    }

    /** CSV of the registry with the page's current filters (VPRIDE and RIDE reporters). */
    public function exportRegistry(): void
    {
        if (!MonitoringRoles::isVpride() && !Auth::hasRole('ride_reporter')) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $user = Auth::user();
        $filters = [
            'tab' => array_key_exists((string) ($_GET['tab'] ?? ''), \App\Support\ProposalRegistry::TABS) ? (string) $_GET['tab'] : 'all',
            'form' => trim((string) ($_GET['form'] ?? '')),
            'year' => preg_match('/^\d{4}$/', (string) ($_GET['year'] ?? '')) === 1 ? (string) $_GET['year'] : '',
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        $rows = array_values(array_filter(
            \App\Support\ProposalRegistry::rows($this->registryProposals($user), fn (array $p): bool => $this->canApprove($p)),
            static fn (array $row): bool => \App\Support\ProposalRegistry::matches($row, $filters)
        ));
        $csv = \App\Support\ProposalRegistry::csv($rows);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="proposals-' . date('Y-m-d') . '.csv"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: private, no-store');
        echo $csv;
    }

    /** @return list<array<string, mixed>> proposals the registry lists for the viewer */
    private function registryProposals(array $user): array
    {
        return array_values(array_filter(
            $this->visibleProposalsForCurrentUser($user),
            static function (array $proposal): bool {
                if (proposal_is_terminal_report_assessment_form($proposal)) {
                    return false;
                }

                if (!proposal_is_terminal_report($proposal)) {
                    return true;
                }

                $directorApproval = Proposal::approvalAtStep((int) ($proposal['id'] ?? 0), MonitoringRoles::DIRECTOR_RESEARCH);
                return $directorApproval === null;
            }
        ));
    }

    public function terminalReportRegistry(): void
    {
        if (!MonitoringRoles::canAccessTerminalReport()) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        $user = Auth::user();
        $requestedFormType = trim((string) ($_GET['form_type'] ?? ''));
        $allowedRegistryFormTypes = [
            'progress_report',
            'terminal_report',
            'terminal_report_assessment_form',
        ];
        $selectedFormType = in_array($requestedFormType, $allowedRegistryFormTypes, true) ? $requestedFormType : '';
        $proposals = array_values(array_filter(
            $this->visibleProposalsForCurrentUser($user),
            static function (array $proposal) use ($selectedFormType): bool {
                $isProgressReport = proposal_is_progress_report($proposal);
                $isTerminalReport = proposal_is_terminal_report($proposal);
                $isTerminalAssessment = proposal_is_terminal_report_assessment_form($proposal);

                if ($selectedFormType === 'progress_report') {
                    return $isProgressReport;
                }
                if ($selectedFormType === 'terminal_report') {
                    return $isTerminalReport;
                }
                if ($selectedFormType === 'terminal_report_assessment_form') {
                    return $isTerminalAssessment;
                }

                return $isTerminalReport;
            }
        ));

        view('proposals.terminal-report-registry', [
            'proposals' => $proposals,
            'currentUserId' => (int) ($user['id'] ?? 0),
            'selectedFormType' => $selectedFormType,
        ]);
    }

    public function create(): void
    {
        $user = Auth::user();
        if (proposal_nav_scope() === 'extension') {
            view('proposals.wpu-funded-extension-form', [
                'proposal' => null,
                'lockedProjectType' => 'extension',
                'colleges' => College::all(),
                'pageTitle' => 'New Extension Project Proposal | RIDE Hub',
                'pageHeading' => 'Extension Services',
                'pageSubtitle' => 'Complete the Office of Extension Services extension project proposal (WPU-QSF-RIDE-ESO-03).',
                'facultyTeamOptions' => $this->facultyTeamOptions(),
                'extensionPrograms' => \App\Models\ExtensionProgram::all(null, true),
                'extensionProjects' => \App\Models\ExtensionProject::options(),
                'userCollegeId' => $user['college_id'] ? (int) $user['college_id'] : null,
            ]);
            return;
        }

        view('proposals.form', [
            'proposal' => null,
            'colleges' => College::all(),
            'campuses' => $user['college_id'] ? College::campuses((int) $user['college_id']) : [],
            'lockedProjectType' => proposal_nav_scope(),
            'facultyCoAuthorOptions' => $this->facultyCoAuthorOptions(),
        ]);
    }

    public function createRequiredFiles(): void
    {
        $user = Auth::user();
        $proposalId = (int) ($_GET['proposal'] ?? 0);

        if ($proposalId > 0) {
            $proposal = $this->authorizeView($proposalId);
            if ($proposal && $this->canEdit($proposal) && !$this->isStandaloneProposalForm($proposal)) {
                redirect('proposals/' . $proposalId . '/edit/required-files');
            }
        }

        foreach (Proposal::forUser((int) $user['id']) as $row) {
            if (!in_array((string) ($row['status'] ?? ''), ['draft', 'returned'], true)) {
                continue;
            }
            if ($this->isStandaloneProposalForm($row)) {
                continue;
            }
            redirect('proposals/' . (int) $row['id'] . '/edit/required-files');
        }

        view('proposals.required-files-form', [
            'proposal' => null,
            'requiredFileList' => $this->requiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function editRequiredFiles(int $id): void
    {
        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (proposal_is_trainings_conducted($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->trainingsConductedRequiredFileCategories()),
            ]);
            return;
        }

        if (proposal_is_technical_advisory($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technicalAdvisoryRequiredFileCategories()),
            ]);
            return;
        }

        if (proposal_is_extension_linkages($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->extensionLinkagesRequiredFileCategories()),
            ]);
            return;
        }
        if (proposal_is_outreach_activities($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->outreachActivitiesRequiredFileCategories()),
            ]);
            return;
        }

        if (proposal_is_technology_adoption($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technologyAdoptionRequiredFileCategories()),
            ]);
            return;
        }

        if ($this->isStandaloneProposalForm($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        view('proposals.required-files-form', [
            'proposal' => $proposal,
            'requiredFileList' => $this->requiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id),
        ]);
    }

    public function updateRequiredFiles(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit/required-files');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (proposal_is_trainings_conducted($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->trainingsConductedRequiredFileCategories()
            );
            AuditLog::record('proposal', $id, 'required_files_updated');
            if ($uploadErrors === []) {
                set_flash('success', 'Supporting documents saved.');
            } else {
                set_flash('error', 'Some supporting documents were not uploaded: ' . implode(' ', $uploadErrors));
            }
            redirect('proposals/' . $id . '/edit/required-files');
            return;
        }

        if (proposal_is_technical_advisory($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->technicalAdvisoryRequiredFileCategories()
            );
            AuditLog::record('proposal', $id, 'required_files_updated');
            if ($uploadErrors === []) {
                set_flash('success', 'Supporting documents saved.');
            } else {
                set_flash('error', 'Some supporting documents were not uploaded: ' . implode(' ', $uploadErrors));
            }
            redirect('proposals/' . $id . '/edit/required-files');
            return;
        }

        if (proposal_is_extension_linkages($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->extensionLinkagesRequiredFileCategories()
            );
            AuditLog::record('proposal', $id, 'required_files_updated');
            if ($uploadErrors === []) {
                set_flash('success', 'Supporting documents saved.');
            } else {
                set_flash('error', 'Some supporting documents were not uploaded: ' . implode(' ', $uploadErrors));
            }
            redirect('proposals/' . $id . '/edit/required-files');
            return;
        }
        if (proposal_is_outreach_activities($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->outreachActivitiesRequiredFileCategories()
            );
            AuditLog::record('proposal', $id, 'required_files_updated');
            if ($uploadErrors === []) {
                set_flash('success', 'Supporting documents saved.');
            } else {
                set_flash('error', 'Some supporting documents were not uploaded: ' . implode(' ', $uploadErrors));
            }
            redirect('proposals/' . $id . '/edit/required-files');
            return;
        }
        if (proposal_is_technology_adoption($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->technologyAdoptionRequiredFileCategories()
            );
            AuditLog::record('proposal', $id, 'required_files_updated');
            if ($uploadErrors === []) {
                set_flash('success', 'Supporting documents saved.');
            } else {
                set_flash('error', 'Some supporting documents were not uploaded: ' . implode(' ', $uploadErrors));
            }
            redirect('proposals/' . $id . '/edit/required-files');
            return;
        }

        if ($this->isStandaloneProposalForm($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        $uploadErrors = $this->storeRequiredFiles(
            $id,
            (int) Auth::user()['id'],
            (string) ($proposal['status'] ?? '') === 'ongoing'
        );
        AuditLog::record('proposal', $id, 'required_files_updated');
        if ($uploadErrors === []) {
            set_flash('success', 'Required files saved.');
        } else {
            set_flash('error', 'Some required files were not uploaded: ' . implode(' ', $uploadErrors));
        }
        redirect('proposals/' . $id . '/edit/required-files');
    }

    /** Manuscript Writing for Publication is one of the RDO request forms now (ResearchRequestController). */
    public function createManuscript(): void
    {
        redirect('research-requests/new/manuscript');
    }

    public function createCompletedResearches(): void
    {
        if (!$this->allowCompletedResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'completed_researches',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('completed_researches', $user));
    }

    public function storeCompletedResearches(): void
    {
        if (!$this->allowCompletedResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/completed-researches');
        }

        $user = Auth::user();
        $data = $this->validatedCompletedResearchesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/completed-researches');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Completed researches report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateCompletedResearches(int $id): void
    {
        if (!$this->allowCompletedResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_completed_researches($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedCompletedResearchesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Completed researches report updated.');
        redirect('proposals/' . $id);
    }

    public function createOngoingResearches(): void
    {
        if (!$this->allowOngoingResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'ongoing_researches',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('ongoing_researches', $user));
    }

    public function storeOngoingResearches(): void
    {
        if (!$this->allowOngoingResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/ongoing-researches');
        }

        $user = Auth::user();
        $data = $this->validatedOngoingResearchesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/ongoing-researches');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Ongoing researches report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateOngoingResearches(int $id): void
    {
        if (!$this->allowOngoingResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_ongoing_researches($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedOngoingResearchesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Ongoing researches report updated.');
        redirect('proposals/' . $id);
    }

    public function createResearchOutputPublished(): void
    {
        if (!$this->allowResearchOutputPublishedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'research_output_published',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('research_output_published', $user));
    }

    public function storeResearchOutputPublished(): void
    {
        if (!$this->allowResearchOutputPublishedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/research-output-published');
        }

        $user = Auth::user();
        $data = $this->validatedResearchOutputPublishedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/research-output-published');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Research output published report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateResearchOutputPublished(int $id): void
    {
        if (!$this->allowResearchOutputPublishedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_research_output_published($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedResearchOutputPublishedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Research output published report updated.');
        redirect('proposals/' . $id);
    }

    public function createResearchOutputPresented(): void
    {
        if (!$this->allowResearchOutputPresentedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'research_output_presented',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('research_output_presented', $user));
    }

    public function storeResearchOutputPresented(): void
    {
        if (!$this->allowResearchOutputPresentedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/research-output-presented');
        }

        $user = Auth::user();
        $data = $this->validatedResearchOutputPresentedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/research-output-presented');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Research output presented report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateResearchOutputPresented(int $id): void
    {
        if (!$this->allowResearchOutputPresentedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_research_output_presented($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedResearchOutputPresentedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Research output presented report updated.');
        redirect('proposals/' . $id);
    }

    public function createCommercialized(): void
    {
        if (!$this->allowCommercializedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'commercialized',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('commercialized', $user));
    }

    public function storeCommercialized(): void
    {
        if (!$this->allowCommercializedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/commercialized');
        }

        $user = Auth::user();
        $data = $this->validatedCommercializedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/commercialized');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Commercialized report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateCommercialized(int $id): void
    {
        if (!$this->allowCommercializedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_commercialized($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedCommercializedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Commercialized report updated.');
        redirect('proposals/' . $id);
    }

    public function createResultedInExtension(): void
    {
        if (!$this->allowResultedInExtensionAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'resulted_in_extension',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('resulted_in_extension', $user));
    }

    public function storeResultedInExtension(): void
    {
        if (!$this->allowResultedInExtensionAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/resulted-in-extension');
        }

        $user = Auth::user();
        $data = $this->validatedResultedInExtensionInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/resulted-in-extension');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Resulted in extension report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateResultedInExtension(int $id): void
    {
        if (!$this->allowResultedInExtensionAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_resulted_in_extension($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedResultedInExtensionInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Resulted in extension report updated.');
        redirect('proposals/' . $id);
    }

    public function createJournalCitation(): void
    {
        if (!$this->allowJournalCitationAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'journal_citation',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('journal_citation', $user));
    }

    public function storeJournalCitation(): void
    {
        if (!$this->allowJournalCitationAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/journal-citation');
        }

        $user = Auth::user();
        $data = $this->validatedJournalCitationInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/journal-citation');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Journal citation report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateJournalCitation(int $id): void
    {
        if (!$this->allowJournalCitationAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_journal_citation($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedJournalCitationInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Journal citation report updated.');
        redirect('proposals/' . $id);
    }

    public function createBookCitation(): void
    {
        if (!$this->allowBookCitationAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'book_citation',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('book_citation', $user));
    }

    public function createInventionsUmCopyrights(): void
    {
        if (!$this->allowInventionsUmCopyrightsAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'inventions_um_copyrights',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('inventions_um_copyrights', $user));
    }

    public function createLinkages(): void
    {
        if (!$this->allowLinkagesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'linkages',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ] + $this->conductedActivitiesPrefill('linkages', $user));
    }

    public function createTrainingsConducted(): void
    {
        if (!$this->allowTrainingsConductedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'trainings_conducted',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
            'requiredDocuments' => [],
        ] + $this->conductedActivitiesPrefill('trainings_conducted', $user));
    }

    public function createTechnicalAdvisory(): void
    {
        if (!$this->allowTechnicalAdvisoryAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'technical_advisory',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
            'requiredDocuments' => [],
        ] + $this->conductedActivitiesPrefill('technical_advisory', $user));
    }

    public function createExtensionLinkages(): void
    {
        if (!$this->allowExtensionLinkagesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'extension_linkages',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
            'requiredDocuments' => [],
        ] + $this->conductedActivitiesPrefill('extension_linkages', $user));
    }

    public function createOutreachActivities(): void
    {
        if (!$this->allowOutreachActivitiesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'outreach_activities',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
            'requiredDocuments' => [],
        ] + $this->conductedActivitiesPrefill('outreach_activities', $user));
    }

    public function createTechnologyAdoption(): void
    {
        if (!$this->allowTechnologyAdoptionAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.quarterly-report-form', [
                'reportType' => 'technology_adoption',
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
            'requiredDocuments' => [],
        ] + $this->conductedActivitiesPrefill('technology_adoption', $user));
    }

    public function createConsolidatedCompletedResearches(): void
    {
        if (!$this->allowConsolidatedCompletedResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        $collegeId = (int) ($user['college_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        if ($collegeId > 0) {
            \App\Services\CompletedResearchesConsolidation::syncAllFacultyReportsForCollege($collegeId, $userId);
        }

        $prefilledEntries = $collegeId > 0
            ? \App\Services\CompletedResearchesConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
            : [];

        view('proposals.consolidated-completed-researches-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'prefilledEntries' => $prefilledEntries,
        ]);
    }

    public function createConsolidatedOngoingResearches(): void
    {
        if (!$this->allowConsolidatedOngoingResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $isVpride = MonitoringRoles::isVpride();

        if ($isVpride) {
            \App\Services\OngoingResearchesConsolidation::syncAllDirectorApprovedForVpride($userId);
            $prefilledEntries = \App\Services\OngoingResearchesConsolidation::mergedEntriesFromDirectorApprovedForVpride();
            $collegeName = 'University-wide (All Colleges)';
        } else {
            $collegeId = (int) ($user['college_id'] ?? 0);
            if ($collegeId > 0) {
                \App\Services\OngoingResearchesConsolidation::syncAllFacultyReportsForCollege($collegeId, $userId);
            }
            $prefilledEntries = $collegeId > 0
                ? \App\Services\OngoingResearchesConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
                : [];
            $collegeName = $this->collegeNameForUser($user);
        }

        view('proposals.consolidated-ongoing-researches-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $collegeName,
            'prefilledEntries' => $prefilledEntries,
            'isVprideConsolidation' => $isVpride,
        ]);
    }

    public function createConsolidatedResearchOutputPublished(): void
    {
        if (!$this->allowConsolidatedResearchOutputPublishedAccess()) {
            return;
        }

        $user = Auth::user();
        $collegeId = (int) ($user['college_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        if ($collegeId > 0) {
            \App\Services\ResearchOutputPublishedConsolidation::syncAllFacultyReportsForCollege($collegeId, $userId);
        }

        $prefilledEntries = $collegeId > 0
            ? \App\Services\ResearchOutputPublishedConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
            : [];

        view('proposals.consolidated-research-output-published-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'prefilledEntries' => $prefilledEntries,
        ]);
    }

    public function createConsolidatedResearchOutputPresented(): void
    {
        if (!$this->allowConsolidatedResearchOutputPresentedAccess()) {
            return;
        }

        $user = Auth::user();
        $collegeId = (int) ($user['college_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        if ($collegeId > 0) {
            \App\Services\ResearchOutputPresentedConsolidation::syncAllFacultyReportsForCollege($collegeId, $userId);
        }

        $prefilledEntries = $collegeId > 0
            ? \App\Services\ResearchOutputPresentedConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
            : [];

        view('proposals.consolidated-research-output-presented-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'prefilledEntries' => $prefilledEntries,
        ]);
    }

    public function createProgressReport(): void
    {
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/terminal-report-registry?form_type=progress_report');
        }

        if (!$this->allowProgressReportAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.progress-report-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function createTerminalReport(): void
    {
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/terminal-report-registry');
        }

        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.terminal-report-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function createTerminalReportAssessmentForm(): void
    {
        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.terminal-report-assessment-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function storeInventionsUmCopyrights(): void
    {
        if (!$this->allowInventionsUmCopyrightsAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/inventions-um-copyrights');
        }

        $user = Auth::user();
        $data = $this->validatedInventionsUmCopyrightsInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/inventions-um-copyrights');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Inventions, UM, Copyrights report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeLinkages(): void
    {
        if (!$this->allowLinkagesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/linkages');
        }

        $user = Auth::user();
        $data = $this->validatedLinkagesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/linkages');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Linkages report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeTrainingsConducted(): void
    {
        if (!$this->allowTrainingsConductedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/trainings-conducted');
        }

        $user = Auth::user();
        $data = $this->validatedTrainingsConductedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/trainings-conducted');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeTrainingsConductedSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Trainings Conducted report saved as draft.');
        } else {
            set_flash(
                'success',
                'Trainings Conducted report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeTechnicalAdvisory(): void
    {
        if (!$this->allowTechnicalAdvisoryAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/technical-advisory');
        }

        $user = Auth::user();
        $data = $this->validatedTechnicalAdvisoryInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/technical-advisory');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeTechnicalAdvisorySupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Technical Advisory report saved as draft.');
        } else {
            set_flash(
                'success',
                'Technical Advisory report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeExtensionLinkages(): void
    {
        if (!$this->allowExtensionLinkagesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/extension-linkages');
        }

        $user = Auth::user();
        $data = $this->validatedExtensionLinkagesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/extension-linkages');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeExtensionLinkagesSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Extension Linkages report saved as draft.');
        } else {
            set_flash(
                'success',
                'Extension Linkages report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeOutreachActivities(): void
    {
        if (!$this->allowOutreachActivitiesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/outreach-activities');
        }

        $user = Auth::user();
        $data = $this->validatedOutreachActivitiesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/outreach-activities');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeOutreachActivitiesSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Outreach Activities report saved as draft.');
        } else {
            set_flash(
                'success',
                'Outreach Activities report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeTechnologyAdoption(): void
    {
        if (!$this->allowTechnologyAdoptionAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/technology-adoption');
        }

        $user = Auth::user();
        $data = $this->validatedTechnologyAdoptionInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/technology-adoption');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeTechnologyAdoptionSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Technology Adoption report saved as draft.');
        } else {
            set_flash(
                'success',
                'Technology Adoption report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeConsolidatedCompletedResearches(): void
    {
        if (!$this->allowConsolidatedCompletedResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/consolidated-completed-researches');
        }

        $user = Auth::user();
        $data = $this->validatedConsolidatedCompletedResearchesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/consolidated-completed-researches');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Consolidated completed researches report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeConsolidatedOngoingResearches(): void
    {
        if (!$this->allowConsolidatedOngoingResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/consolidated-ongoing-researches');
        }

        $user = Auth::user();
        $data = $this->validatedConsolidatedOngoingResearchesInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        if ($data['college_id'] === 0 && MonitoringRoles::isVpride()) {
            $data['college_id'] = \App\Services\OngoingResearchesConsolidation::containerCollegeId();
        }
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/consolidated-ongoing-researches');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Consolidated ongoing researches report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeConsolidatedResearchOutputPublished(): void
    {
        if (!$this->allowConsolidatedResearchOutputPublishedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/consolidated-research-output-published');
        }

        $user = Auth::user();
        $data = $this->validatedConsolidatedResearchOutputPublishedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/consolidated-research-output-published');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Consolidated research output published report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeConsolidatedResearchOutputPresented(): void
    {
        if (!$this->allowConsolidatedResearchOutputPresentedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/consolidated-research-output-presented');
        }

        $user = Auth::user();
        $data = $this->validatedConsolidatedResearchOutputPresentedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/consolidated-research-output-presented');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Consolidated research output presented report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeProgressReport(): void
    {
        if (!$this->allowProgressReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/progress-report');
        }

        $user = Auth::user();
        $data = $this->validatedProgressReportInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/progress-report');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Progress report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeTerminalReport(): void
    {
        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/terminal-report');
        }

        $user = Auth::user();
        $data = $this->validatedTerminalReportInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/terminal-report');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Terminal report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function storeTerminalReportAssessmentForm(): void
    {
        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/terminal-report-assessment-form');
        }

        $user = Auth::user();
        $data = $this->validatedTerminalReportAssessmentFormInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/terminal-report-assessment-form');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Terminal report assessment form saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateInventionsUmCopyrights(int $id): void
    {
        if (!$this->allowInventionsUmCopyrightsAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_inventions_um_copyrights($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedInventionsUmCopyrightsInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Inventions, UM, Copyrights report updated.');
        redirect('proposals/' . $id);
    }

    public function updateLinkages(int $id): void
    {
        if (!$this->allowLinkagesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_linkages($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedLinkagesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Linkages report updated.');
        redirect('proposals/' . $id);
    }

    public function updateTrainingsConducted(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_trainings_conducted($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTrainingsConductedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeTrainingsConductedSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Trainings Conducted report updated.');
        } else {
            set_flash(
                'success',
                'Trainings Conducted report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateTechnicalAdvisory(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_technical_advisory($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTechnicalAdvisoryInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeTechnicalAdvisorySupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Technical Advisory report updated.');
        } else {
            set_flash(
                'success',
                'Technical Advisory report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateExtensionLinkages(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_extension_linkages($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedExtensionLinkagesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeExtensionLinkagesSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Extension Linkages report updated.');
        } else {
            set_flash(
                'success',
                'Extension Linkages report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateOutreachActivities(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_outreach_activities($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedOutreachActivitiesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeOutreachActivitiesSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Outreach Activities report updated.');
        } else {
            set_flash(
                'success',
                'Outreach Activities report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateTechnologyAdoption(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_technology_adoption($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTechnologyAdoptionInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeTechnologyAdoptionSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Technology Adoption report updated.');
        } else {
            set_flash(
                'success',
                'Technology Adoption report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateConsolidatedCompletedResearches(int $id): void
    {
        if (!$this->allowConsolidatedCompletedResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_consolidated_completed_researches($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedConsolidatedCompletedResearchesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Consolidated completed researches report updated.');
        redirect('proposals/' . $id);
    }

    public function updateConsolidatedOngoingResearches(int $id): void
    {
        if (!$this->allowConsolidatedOngoingResearchesAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_consolidated_ongoing_researches($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedConsolidatedOngoingResearchesInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Consolidated ongoing researches report updated.');
        redirect('proposals/' . $id);
    }

    public function updateConsolidatedResearchOutputPublished(int $id): void
    {
        if (!$this->allowConsolidatedResearchOutputPublishedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_consolidated_research_output_published($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedConsolidatedResearchOutputPublishedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Consolidated research output published report updated.');
        redirect('proposals/' . $id);
    }

    public function updateConsolidatedResearchOutputPresented(int $id): void
    {
        if (!$this->allowConsolidatedResearchOutputPresentedAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_consolidated_research_output_presented($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedConsolidatedResearchOutputPresentedInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Consolidated research output presented report updated.');
        redirect('proposals/' . $id);
    }

    public function updateProgressReport(int $id): void
    {
        if (!$this->allowProgressReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_progress_report($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedProgressReportInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Progress report updated.');
        redirect('proposals/' . $id);
    }

    public function updateTerminalReport(int $id): void
    {
        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_terminal_report($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTerminalReportInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Terminal report updated.');
        redirect('proposals/' . $id);
    }

    public function updateTerminalReportAssessmentForm(int $id): void
    {
        if (!$this->allowTerminalReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_terminal_report_assessment_form($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTerminalReportAssessmentFormInput());
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Terminal report assessment form updated.');
        redirect('proposals/' . $id);
    }

    public function updateObrMatrix(int $id): void
    {
        if (!$this->allowObrMatrixAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_obr_matrix($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedObrMatrixInput());
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'OBR Matrix updated.');
        redirect('proposals/' . $id);
    }

    public function storeBookCitation(): void
    {
        if (!$this->allowBookCitationAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/book-citation');
        }

        $user = Auth::user();
        $data = $this->validatedBookCitationInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/book-citation');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Book citation report saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateBookCitation(int $id): void
    {
        if (!$this->allowBookCitationAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_book_citation($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedBookCitationInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Book citation report updated.');
        redirect('proposals/' . $id);
    }

    public function store(): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create' . proposal_nav_redirect_suffix());
        }

        $user = Auth::user();
        $data = $this->validatedInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0 || $data['title'] === '') {
            set_flash('error', 'College and program title are required.');
            redirect('proposals/create' . proposal_nav_redirect_suffix());
        }

        if ($this->isStandardResearchProposalInput($data)
            && Proposal::hasDuplicateTitleForUser((int) $user['id'], $data['title'])) {
            set_flash('error', 'You already have a proposal with this title. Use a different title or edit your existing draft.');
            redirect('proposals/create' . proposal_nav_redirect_suffix());
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $this->syncCoAuthorInvitations($id, (int) $user['id'], $data['summary'] ?? null, null);
        $created = Proposal::find($id);
        if ($created !== null && $this->isStandaloneProposalForm($created)) {
            set_flash('success', 'Application saved as draft.');
            redirect('proposals/' . $id);
            return;
        }

        set_flash('success', 'Proposal saved as draft.');
        redirect('proposals/' . $id);
    }

    public function show(int $id): void
    {
        $proposal = $this->authorizeView($id);
        if (!$proposal) {
            return;
        }
        if (\App\Support\ResearchRequestForms::typeOf($proposal) !== null) {
            view('requests.show', [
                'proposal' => $proposal,
                'comments' => Proposal::comments($id),
                'canEdit' => $this->canEdit($proposal),
                'canApprove' => $this->canApprove($proposal),
                'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
                'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            ]);
            return;
        }
        // Quarterly reports: app view page; Print Preview downloads the filled office form.
        $quarterlyType = proposal_form_type($proposal);
        if (\App\Support\ExtensionActivityTypes::get($quarterlyType) !== null) {
            view('proposals.quarterly-report-show', [
                'reportType' => $quarterlyType,
                'proposal' => $proposal,
                'comments' => Proposal::comments($id),
                'canEdit' => $this->canEdit($proposal),
                'canApprove' => $this->canApprove($proposal),
                'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
                'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
                'documents' => Document::forProposal($id),
            ]);
            return;
        }
        if (proposal_is_completed_researches($proposal)) {
            $this->showCompletedResearches($id, $proposal);
            return;
        }
        if (proposal_is_ongoing_researches($proposal)) {
            $this->showOngoingResearches($id, $proposal);
            return;
        }
        if (proposal_is_research_output_published($proposal)) {
            $this->showResearchOutputPublished($id, $proposal);
            return;
        }
        if (proposal_is_research_output_presented($proposal)) {
            $this->showResearchOutputPresented($id, $proposal);
            return;
        }
        if ($this->tryShowConsolidatedResearchForm($id, $proposal)) {
            return;
        }
        if (proposal_is_commercialized($proposal)) {
            $this->showCommercialized($id, $proposal);
            return;
        }
        if (proposal_is_resulted_in_extension($proposal)) {
            $this->showResultedInExtension($id, $proposal);
            return;
        }
        if (proposal_is_journal_citation($proposal)) {
            $this->showJournalCitation($id, $proposal);
            return;
        }
        if (proposal_is_book_citation($proposal)) {
            $this->showBookCitation($id, $proposal);
            return;
        }
        if (proposal_is_inventions_um_copyrights($proposal)) {
            $this->showInventionsUmCopyrights($id, $proposal);
            return;
        }
        if (proposal_is_linkages($proposal)) {
            $this->showLinkages($id, $proposal);
            return;
        }
        if (proposal_is_trainings_conducted($proposal)) {
            $this->showTrainingsConducted($id, $proposal);
            return;
        }
        if (proposal_is_technical_advisory($proposal)) {
            $this->showTechnicalAdvisory($id, $proposal);
            return;
        }
        if (proposal_is_extension_linkages($proposal)) {
            $this->showExtensionLinkages($id, $proposal);
            return;
        }
        if (proposal_is_outreach_activities($proposal)) {
            $this->showOutreachActivities($id, $proposal);
            return;
        }
        if (proposal_is_technology_adoption($proposal)) {
            $this->showTechnologyAdoption($id, $proposal);
            return;
        }
        if (proposal_is_accomplishment_report($proposal)) {
            $this->showAccomplishmentReport($id, $proposal);
            return;
        }
        if (proposal_is_technical_advisory_ar($proposal)) {
            $this->showTechnicalAdvisoryAr($id, $proposal);
            return;
        }
        if (proposal_is_outreach_activities_ar($proposal)) {
            $this->showOutreachActivitiesAr($id, $proposal);
            return;
        }
        if (proposal_is_ebalwasyon_ng_gawain($proposal)) {
            $this->showEbalwasyonNgGawain($id, $proposal);
            return;
        }
        if (proposal_is_attendance_sheet($proposal)) {
            $this->showAttendanceSheet($id, $proposal);
            return;
        }
        if (proposal_is_consolidated_completed_researches($proposal)) {
            $this->showConsolidatedCompletedResearches($id, $proposal);
            return;
        }
        if (proposal_is_consolidated_ongoing_researches($proposal)) {
            $this->showConsolidatedOngoingResearches($id, $proposal);
            return;
        }
        if (proposal_is_consolidated_research_output_published($proposal)) {
            $this->showConsolidatedResearchOutputPublished($id, $proposal);
            return;
        }
        if (proposal_is_consolidated_research_output_presented($proposal)) {
            $this->showConsolidatedResearchOutputPresented($id, $proposal);
            return;
        }
        if (proposal_is_progress_report($proposal)) {
            $this->showProgressReport($id, $proposal);
            return;
        }
        if (proposal_is_terminal_report($proposal)) {
            $this->showTerminalReport($id, $proposal);
            return;
        }
        if (proposal_is_terminal_report_assessment_form($proposal)) {
            $this->showTerminalReportAssessmentForm($id, $proposal);
            return;
        }
        if (proposal_is_obr_matrix($proposal)) {
            $this->showObrMatrix($id, $proposal);
            return;
        }
        if (proposal_is_wpu_funded_extension($proposal)) {
            $this->showWpuFundedExtension($id, $proposal);
            return;
        }

        view('proposals.show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showWpuFundedExtension(int $id, array $proposal): void
    {
        view('proposals.wpu-funded-extension-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'pageTitle' => ((string) ($proposal['title'] ?? 'Extension Project Proposal')) . ' | RIDE Hub',
            'pageHeading' => (string) ($proposal['title'] ?? 'Extension Project Proposal'),
            'pageSubtitle' => 'Review the submitted Extension Project Proposal (WPU-QSF-RIDE-ESO-03).',
        ]);
    }

    /** Printable paper layout of a saved ESO-03 proposal (print / save as PDF). */
    public function printProposal(int $id): void
    {
        $proposal = $this->authorizeView($id);
        if (!$proposal) {
            return;
        }
        if (\App\Support\ExtensionActivityTypes::get(proposal_form_type($proposal)) !== null) {
            // Quarterly reports print from the office's own Excel/Word form (letterhead included).
            $file = \App\Services\QuarterlyReportFile::build($proposal);
            if ($file === null) {
                set_flash('error', 'The office form for this report is not available yet.');
                redirect('proposals/' . $id);
            }
            header('Content-Type: ' . $file['mime']);
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file['name']) . '"');
            header('Content-Length: ' . strlen($file['bytes']));
            header('Cache-Control: private, no-store');
            echo $file['bytes'];
            return;
        }
        if (\App\Services\ResearchFormDocument::formKey($proposal) !== null) {
            send_form_docx($proposal);
            return;
        }
        if (!proposal_is_wpu_funded_extension($proposal)) {
            redirect('proposals/' . $id);
        }

        // The filled ESO-03 Word template, like the RDO forms.
        send_form_docx($proposal);
    }

    /** The filled office Word form (.docx) of a saved RDO-20 application, RDO request form or ESO-03 proposal. */
    public function downloadDocx(int $id): void
    {
        $proposal = $this->authorizeView($id);
        if (!$proposal) {
            return;
        }
        if (\App\Services\ResearchFormDocument::formKey($proposal) === null && !proposal_is_wpu_funded_extension($proposal)) {
            redirect('proposals/' . $id);
        }
        send_form_docx($proposal);
    }

    /** Print preview of the Research Proposal Application's current, unsaved values. */
    public function previewResearchApplication(): void
    {
        if (!verify_csrf()) {
            http_response_code(419);
            echo 'Invalid session. Reload the form and try again.';
            return;
        }

        $existing = null;
        $id = (int) ($_POST['preview_proposal_id'] ?? 0);
        if ($id > 0) {
            $existing = $this->authorizeView($id);
            if (!$existing) {
                return;
            }
        }

        $user = Auth::user() ?? [];
        $data = $this->validatedInput($existing);
        $proposal = array_merge($existing ?? [
            'status' => 'draft',
            'project_code' => '',
            'current_step' => '',
            'project_type' => 'research',
            'risk_level' => 'low',
            'ethics_required' => 0,
            'college_name' => (string) ($user['college_name'] ?? ''),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
        ], [
            'title' => $data['title'],
            'summary' => $data['summary'],
            'funding_source' => $data['funding_source'],
            'risk_level' => $data['risk_level'],
            'ethics_required' => $data['ethics_required'] ? 1 : 0,
        ]);

        send_form_docx($proposal, true);
    }

    /** Print preview of the ESO-03 form's current, unsaved values. */
    public function previewWpuFundedExtension(): void
    {
        if (!verify_csrf()) {
            http_response_code(419);
            echo 'Invalid session. Reload the form and try again.';
            return;
        }

        $existing = null;
        $id = (int) ($_POST['preview_proposal_id'] ?? 0);
        if ($id > 0) {
            $existing = $this->authorizeView($id);
            if (!$existing) {
                return;
            }
        }

        $user = Auth::user() ?? [];
        $data = $this->validatedWpuFundedExtensionInput($existing);
        $proposal = array_merge($existing ?? [
            'status' => 'draft',
            'project_code' => '',
            'current_step' => '',
            'college_name' => (string) ($user['college_name'] ?? ''),
            'college_id' => (int) ($user['college_id'] ?? 0),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
        ], [
            'title' => $data['title'],
            'summary' => $data['summary'],
            'funding_source' => $data['funding_source'],
        ]);

        send_form_docx($proposal, true);
    }

    private function showCompletedResearches(int $id, array $proposal): void
    {
        view('proposals.completed-researches-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showOngoingResearches(int $id, array $proposal): void
    {
        view('proposals.ongoing-researches-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showResearchOutputPublished(int $id, array $proposal): void
    {
        view('proposals.research-output-published-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showResearchOutputPresented(int $id, array $proposal): void
    {
        view('proposals.research-output-presented-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showCommercialized(int $id, array $proposal): void
    {
        view('proposals.commercialized-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showResultedInExtension(int $id, array $proposal): void
    {
        view('proposals.resulted-in-extension-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showJournalCitation(int $id, array $proposal): void
    {
        view('proposals.journal-citation-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showBookCitation(int $id, array $proposal): void
    {
        view('proposals.book-citation-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showInventionsUmCopyrights(int $id, array $proposal): void
    {
        view('proposals.inventions-um-copyrights-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showLinkages(int $id, array $proposal): void
    {
        view('proposals.linkages-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showTrainingsConducted(int $id, array $proposal): void
    {
        view('proposals.trainings-conducted-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->trainingsConductedRequiredFileCategories()),
        ]);
    }

    private function showTechnicalAdvisory(int $id, array $proposal): void
    {
        view('proposals.technical-advisory-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technicalAdvisoryRequiredFileCategories()),
        ]);
    }

    private function showExtensionLinkages(int $id, array $proposal): void
    {
        view('proposals.extension-linkages-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->extensionLinkagesRequiredFileCategories()),
        ]);
    }

    private function showOutreachActivities(int $id, array $proposal): void
    {
        view('proposals.outreach-activities-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->outreachActivitiesRequiredFileCategories()),
        ]);
    }

    private function showTechnologyAdoption(int $id, array $proposal): void
    {
        view('proposals.technology-adoption-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technologyAdoptionRequiredFileCategories()),
        ]);
    }

    private function showAccomplishmentReport(int $id, array $proposal): void
    {
        view('proposals.accomplishment-report-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => false,
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->accomplishmentReportRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->accomplishmentReportRequiredFileCategories()),
        ]);
    }

    private function showTechnicalAdvisoryAr(int $id, array $proposal): void
    {
        view('proposals.technical-advisory-ar-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => false,
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->technicalAdvisoryArRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technicalAdvisoryArRequiredFileCategories()),
        ]);
    }

    private function showOutreachActivitiesAr(int $id, array $proposal): void
    {
        view('proposals.outreach-activities-ar-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => false,
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'requiredFileList' => $this->outreachActivitiesArRequiredFileCategories(),
            'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->outreachActivitiesArRequiredFileCategories()),
        ]);
    }

    private function showEbalwasyonNgGawain(int $id, array $proposal): void
    {
        view('proposals.ebalwasyon-ng-gawain-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'pageTitle' => ((string) ($proposal['title'] ?? 'Ebalwasyon ng Gawain')) . ' | RIDE Hub',
            'pageHeading' => (string) ($proposal['title'] ?? 'Ebalwasyon ng Gawain'),
            'pageSubtitle' => 'Review the submitted activity evaluation (WPU-QSF-RIDE-ESO-16).',
        ]);
    }

    private function showAttendanceSheet(int $id, array $proposal): void
    {
        view('proposals.attendance-sheet-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => false,
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'pageTitle' => ((string) ($proposal['title'] ?? 'Attendance Sheet')) . ' | RIDE Hub',
            'pageHeading' => (string) ($proposal['title'] ?? 'Attendance Sheet'),
            'pageSubtitle' => 'Review the submitted attendance form (WPU-QSF-RIDE-ESO-17).',
        ]);
    }

    private function showConsolidatedOngoingResearches(int $id, array $proposal): void
    {
        if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
            \App\Services\OngoingResearchesConsolidation::refreshConsolidatedProposal($id);
            $refreshed = Proposal::find($id);
            if ($refreshed !== null) {
                $proposal = $refreshed;
            }
        }

        view('proposals.consolidated-ongoing-researches-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showConsolidatedCompletedResearches(int $id, array $proposal): void
    {
        if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
            \App\Services\CompletedResearchesConsolidation::refreshConsolidatedProposal($id);
            $refreshed = Proposal::find($id);
            if ($refreshed !== null) {
                $proposal = $refreshed;
            }
        }

        view('proposals.consolidated-completed-researches-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showConsolidatedResearchOutputPublished(int $id, array $proposal): void
    {
        if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
            \App\Services\ResearchOutputPublishedConsolidation::refreshConsolidatedProposal($id);
            $refreshed = Proposal::find($id);
            if ($refreshed !== null) {
                $proposal = $refreshed;
            }
        }

        view('proposals.consolidated-research-output-published-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showConsolidatedResearchOutputPresented(int $id, array $proposal): void
    {
        if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
            \App\Services\ResearchOutputPresentedConsolidation::refreshConsolidatedProposal($id);
            $refreshed = Proposal::find($id);
            if ($refreshed !== null) {
                $proposal = $refreshed;
            }
        }

        view('proposals.consolidated-research-output-presented-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showProgressReport(int $id, array $proposal): void
    {
        view('proposals.progress-report-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showTerminalReport(int $id, array $proposal): void
    {
        view('proposals.terminal-report-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showTerminalReportAssessmentForm(int $id, array $proposal): void
    {
        view('proposals.terminal-report-assessment-form-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    private function showObrMatrix(int $id, array $proposal): void
    {
        view('proposals.obr-matrix-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
    }

    public function edit(int $id): void
    {
        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || proposal_is_retired_downloadable_form($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (\App\Support\ResearchRequestForms::typeOf($proposal) !== null) {
            redirect('research-requests/' . $id . '/edit');
        }

        // Quarterly reports: rows always come from Conducted Activities / Research Accomplishments.
        $generatedVars = [];
        $reportType = proposal_form_type($proposal);
        if (\App\Support\ExtensionActivityTypes::get($reportType) !== null) {
            $savedSummary = json_decode((string) ($proposal['summary'] ?? ''), true);
            $generatedVars = $this->conductedActivitiesPrefill($reportType, Auth::user(), (string) (is_array($savedSummary) ? ($savedSummary['reporting_period'] ?? '') : ''));
            $proposal['summary'] = self::summaryWithGeneratedRows((string) ($proposal['summary'] ?? ''), $generatedVars['prefillSummary'] ?? null, $reportType);
        }

        if (proposal_is_completed_researches($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'completed_researches',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_ongoing_researches($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'ongoing_researches',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_research_output_published($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'research_output_published',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_research_output_presented($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'research_output_presented',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if ($this->tryEditConsolidatedResearchForm($id, $proposal)) {
            return;
        }

        if (proposal_is_commercialized($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'commercialized',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_resulted_in_extension($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'resulted_in_extension',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_journal_citation($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'journal_citation',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_book_citation($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'book_citation',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_inventions_um_copyrights($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'inventions_um_copyrights',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_linkages($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'linkages',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_trainings_conducted($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'trainings_conducted',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->trainingsConductedRequiredFileCategories()
                ),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_technical_advisory($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'technical_advisory',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->technicalAdvisoryRequiredFileCategories()
                ),
            ] + $generatedVars);
            return;
        }

        if (proposal_is_extension_linkages($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'extension_linkages',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->extensionLinkagesRequiredFileCategories()
                ),
            ] + $generatedVars);
            return;
        }
        if (proposal_is_outreach_activities($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'outreach_activities',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->outreachActivitiesRequiredFileCategories()
                ),
            ] + $generatedVars);
            return;
        }
        if (proposal_is_technology_adoption($proposal)) {
            view('proposals.quarterly-report-form', [
                'reportType' => 'technology_adoption',
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->technologyAdoptionRequiredFileCategories()
                ),
            ] + $generatedVars);
            return;
        }
        if (proposal_is_consolidated_completed_researches($proposal)) {
            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                \App\Services\CompletedResearchesConsolidation::refreshConsolidatedProposal($id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            $collegeId = (int) ($proposal['college_id'] ?? 0);
            $prefilledEntries = $collegeId > 0
                ? \App\Services\CompletedResearchesConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
                : [];

            view('proposals.consolidated-completed-researches-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'prefilledEntries' => $prefilledEntries,
            ]);
            return;
        }
        if (proposal_is_consolidated_ongoing_researches($proposal)) {
            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                \App\Services\OngoingResearchesConsolidation::refreshConsolidatedProposal($id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            $isVprideConsolidation = proposal_is_vpride_consolidated_ongoing_researches($proposal);
            if ($isVprideConsolidation) {
                \App\Services\OngoingResearchesConsolidation::syncAllDirectorApprovedForVpride((int) Auth::user()['id']);
                $prefilledEntries = \App\Services\OngoingResearchesConsolidation::mergedEntriesFromDirectorApprovedForVpride();
            } else {
                $collegeId = (int) ($proposal['college_id'] ?? 0);
                $prefilledEntries = $collegeId > 0
                    ? \App\Services\OngoingResearchesConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
                    : [];
            }

            view('proposals.consolidated-ongoing-researches-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'prefilledEntries' => $prefilledEntries,
                'isVprideConsolidation' => $isVprideConsolidation,
            ]);
            return;
        }
        if (proposal_is_consolidated_research_output_published($proposal)) {
            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                \App\Services\ResearchOutputPublishedConsolidation::refreshConsolidatedProposal($id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            $collegeId = (int) ($proposal['college_id'] ?? 0);
            $prefilledEntries = $collegeId > 0
                ? \App\Services\ResearchOutputPublishedConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
                : [];

            view('proposals.consolidated-research-output-published-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'prefilledEntries' => $prefilledEntries,
            ]);
            return;
        }
        if (proposal_is_consolidated_research_output_presented($proposal)) {
            if (in_array((string) ($proposal['status'] ?? ''), ['draft', 'returned'], true)) {
                \App\Services\ResearchOutputPresentedConsolidation::refreshConsolidatedProposal($id);
                $refreshed = Proposal::find($id);
                if ($refreshed !== null) {
                    $proposal = $refreshed;
                }
            }

            $collegeId = (int) ($proposal['college_id'] ?? 0);
            $prefilledEntries = $collegeId > 0
                ? \App\Services\ResearchOutputPresentedConsolidation::mergedEntriesFromApprovedFaculty($collegeId)
                : [];

            view('proposals.consolidated-research-output-presented-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'prefilledEntries' => $prefilledEntries,
            ]);
            return;
        }
        if (proposal_is_progress_report($proposal)) {
            view('proposals.progress-report-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }
        if (proposal_is_terminal_report($proposal)) {
            view('proposals.terminal-report-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }
        if (proposal_is_terminal_report_assessment_form($proposal)) {
            view('proposals.terminal-report-assessment-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }
        if (proposal_is_obr_matrix($proposal)) {
            view('proposals.obr-matrix-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_wpu_funded_extension($proposal)) {
            view('proposals.wpu-funded-extension-form', [
                'proposal' => $proposal,
                'lockedProjectType' => 'extension',
                'colleges' => College::all(),
                'pageTitle' => 'Edit Extension Project Proposal | RIDE Hub',
                'pageHeading' => 'Extension Services',
                'pageSubtitle' => 'Update your Extension Project Proposal before saving or resubmitting.',
                'facultyTeamOptions' => $this->facultyTeamOptions(),
                'extensionPrograms' => \App\Models\ExtensionProgram::all(null, true),
                'extensionProjects' => \App\Models\ExtensionProject::options(),
                'userCollegeId' => (int) $proposal['college_id'],
            ]);
            return;
        }

        view('proposals.form', [
            'proposal' => $proposal,
            'colleges' => College::all(),
            'campuses' => College::campuses((int) $proposal['college_id']),
            'facultyCoAuthorOptions' => $this->facultyCoAuthorOptions(),
        ]);
    }

    public function update(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }
        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || proposal_is_retired_downloadable_form($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $user = Auth::user();
        $data = $this->validatedInput($proposal);
        if ($this->isStandardResearchProposalInput($data)
            && Proposal::hasDuplicateTitleForUser((int) $user['id'], $data['title'], $id)) {
            set_flash('error', 'You already have a proposal with this title. Use a different title or edit your existing draft.');
            redirect('proposals/' . $id . '/edit');
        }
        $previousSummary = (string) ($proposal['summary'] ?? '');
        Proposal::update($id, $data);
        AuditLog::record('proposal', $id, 'updated');
        $this->syncCoAuthorInvitations($id, (int) $user['id'], $data['summary'] ?? null, $previousSummary);
        set_flash('success', 'Proposal updated.');
        redirect('proposals/' . $id);
    }

    public function submit(int $id): void
    {
        if (!verify_csrf()) {
            redirect('proposals/' . $id);
        }
        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        if (!$this->isStandaloneProposalForm($proposal)) {
            $missingRequiredFiles = $this->missingRequiredFileLabels($id);
            if ($missingRequiredFiles !== []) {
                set_flash(
                    'error',
                    'Please upload all required files before submitting for review. Missing: ' . implode('; ', $missingRequiredFiles)
                );
                redirect('proposals/' . $id . '/edit/required-files');
            }
        }
        if (\App\Support\ProposalCoAuthors::hasPendingLinkedCoAuthors($id, (string) ($proposal['summary'] ?? ''))) {
            set_flash(
                'error',
                'Submit for review is unavailable until all registered faculty co-authors accept their invitation.'
            );
            redirect('proposals/' . $id);
        }
        if (\App\Services\ResearchFormDocument::formKey($proposal) !== null) {
            $missing = \App\Support\FormPreviewRequirements::missing($proposal);
            if ($missing !== []) {
                set_flash('error', 'Fill in the form before submitting it for review. Missing: ' . implode(', ', $missing) . '.');
                redirect('proposals/' . $id);
            }
        }
        Proposal::submit($id);
        AuditLog::record('proposal', $id, 'submitted');
        set_flash('success', 'Proposal submitted for review.');
        redirect('proposals/' . $id);
    }

    public function destroy(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (!Proposal::deleteForUser($id, (int) Auth::user()['id'])) {
            set_flash('error', 'Unable to delete proposal right now.');
            redirect('proposals');
        }

        AuditLog::record('proposal', $id, 'deleted');
        set_flash('success', 'Proposal deleted successfully.');
        redirect('proposals');
    }

    public function forceReopen(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id);
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canForceManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (!Proposal::forceReopenForEdit($id)) {
            set_flash('error', 'Unable to reopen this proposal for editing.');
            redirect('proposals/' . $id);
        }

        AuditLog::record('proposal', $id, 'force_reopened_for_edit');
        set_flash('success', 'Proposal reopened for applicant editing.');
        redirect('proposals/' . $id . '/edit');
    }

    public function forceDestroy(int $id): void
    {
        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canForceManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (!Proposal::forceDelete($id)) {
            set_flash('error', 'Unable to force delete this proposal right now.');
            redirect('proposals/' . $id);
        }

        AuditLog::record('proposal', $id, 'force_deleted');
        set_flash('success', 'Proposal force deleted successfully.');
        redirect('proposals');
    }

    public function approve(int $id): void
    {
        $this->workflowAction($id, 'approve');
    }

    public function returnForRevision(int $id): void
    {
        $this->workflowAction($id, 'return', $_POST['comment'] ?? '');
    }

    private function workflowAction(int $id, string $action, string $comment = ''): void
    {
        if (!verify_csrf()) {
            redirect('proposals/' . $id);
        }
        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canApprove($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        if ($action === 'approve' && $comment === '') {
            $comment = 'Approved at ' . ($proposal['current_step'] ?? 'review') . ' step.';
        }
        if ($action === 'return' && trim($comment) === '') {
            set_flash('error', 'Please provide comments when returning for revision.');
            redirect('proposals/' . $id);
        }
        Proposal::advanceWorkflow($id, $action, (int) Auth::user()['id'], $comment);
        AuditLog::record('proposal', $id, $action);
        set_flash('success', 'Action recorded successfully.');
        redirect('proposals/' . $id);
    }

    /**
     * @return array{reporting_period: string, report_as_of: string}
     */
    private function quarterlyReportingFromRequest(?array $existingProposal = null, ?string $formType = null): array
    {
        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        if ($formType === null || $formType === '') {
            $formType = trim((string) ($existingSummary['form_type'] ?? ''));
        }

        return quarterly_reporting_require($_POST, $existingSummary, $formType !== '' ? $formType : null);
    }

    private function validatedInput(?array $proposal = null): array
    {
        if (proposal_nav_scope() === 'extension' || ($proposal !== null && proposal_is_wpu_funded_extension($proposal))) {
            return $this->validatedWpuFundedExtensionInput($proposal);
        }

        // Part I (The Proponent) is the proponent's profile, copied in so the saved form keeps the details it
        // was submitted with.
        $proponentUserId = (int) ($proposal['user_id'] ?? (Auth::user()['id'] ?? 0));
        $structuredSummary = [
            'version' => 2,
        ] + \App\Support\ProponentProfile::applicantFields($proponentUserId) + [
            'coauthors' => $this->validatedCoAuthors($_POST['coauthors'] ?? []),
            'period_covered' => trim($_POST['period_covered'] ?? ''),
            'duration_months' => trim($_POST['duration_months'] ?? ''),
            'abstract' => trim($_POST['abstract'] ?? $_POST['summary'] ?? ''),
            'introduction' => trim($_POST['introduction'] ?? ''),
            'research_gaps' => trim($_POST['research_gaps'] ?? ''),
            'significance' => trim($_POST['significance'] ?? ''),
            'objectives' => trim($_POST['objectives'] ?? ''),
            'methods' => trim($_POST['methods'] ?? ''),
            'gender_development' => trim($_POST['gender_development'] ?? ''),
            'ethical_considerations' => trim($_POST['ethical_considerations'] ?? ''),
            'expected_outcomes' => trim($_POST['expected_outcomes'] ?? ''),
            'literature_cited' => trim($_POST['literature_cited'] ?? ''),
            'resources_available' => trim($_POST['resources_available'] ?? ''),
            'budget_total' => trim($_POST['budget_total'] ?? ''),
            'budget_items' => $this->validatedBudgetItems($_POST['budget_items'] ?? []),
            'six_ps' => $this->validatedSixPs($_POST['six_ps'] ?? []),
            'implementation_plan' => $this->validatedImplementationPlan($_POST['implementation_plan'] ?? []),
            'plan_year_from' => preg_match('/^\d{4}$/', trim((string) ($_POST['plan_year_from'] ?? ''))) === 1 ? trim((string) $_POST['plan_year_from']) : '',
            'plan_year_to' => preg_match('/^\d{4}$/', trim((string) ($_POST['plan_year_to'] ?? ''))) === 1 ? trim((string) $_POST['plan_year_to']) : '',
            'funding_support' => \App\Support\ResearchFormOptions::picked($_POST['funding_support'] ?? [], \App\Support\ResearchFormOptions::FUNDING_SUPPORT),
            'graphical_abstract_link' => trim((string) ($_POST['graphical_abstract_link'] ?? '')),
            'ched_achieve' => \App\Support\ResearchFormOptions::picked($_POST['ched_achieve'] ?? [], \App\Support\ResearchFormOptions::CHED_ACHIEVE),
            'sdgs' => \App\Support\ResearchFormOptions::picked($_POST['sdgs'] ?? [], \App\Support\ResearchFormOptions::SDGS),
            'sustainability_plan' => trim((string) ($_POST['sustainability_plan'] ?? '')),
        ];

        return [
            'title' => trim($_POST['title'] ?? ''),
            'summary' => $this->hasStructuredSummaryContent($structuredSummary)
                ? (json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '')
                : trim($_POST['summary'] ?? ''),
            'project_type' => $this->resolvedProjectType((string) ($_POST['project_type'] ?? 'research')),
            'funding_source' => trim($_POST['funding_source'] ?? ''),
            'risk_level' => $_POST['risk_level'] ?? 'low',
            'ethics_required' => !empty($_POST['ethics_required']),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedWpuFundedExtensionInput(?array $proposal = null): array
    {
        $sourceOfFund = trim($_POST['source_of_fund'] ?? '');
        $durationStartDate = trim($_POST['duration_start_date'] ?? '');
        $durationEndDate = trim($_POST['duration_end_date'] ?? '');
        $durationInclusiveDates = $this->formatDurationInclusiveDates($durationStartDate, $durationEndDate, $proposal);
        // Program and project come from the catalog (Admin > Programs); the summary keeps the program's printed label.
        $extensionProgram = \App\Models\ExtensionProgram::find((int) ($_POST['extension_program_id'] ?? 0));
        $extensionProject = \App\Models\ExtensionProject::find((int) ($_POST['extension_project_id'] ?? 0));
        if ($extensionProject !== null
            && ($extensionProgram === null || (int) $extensionProject['extension_program_id'] !== (int) $extensionProgram['id'])) {
            $extensionProject = null;
        }
        $structuredSummary = [
            'form_type' => 'extension_program_proposal',
            'version' => 2,
            'college_extension_program' => $extensionProgram !== null
                ? \App\Models\ExtensionProgram::label($extensionProgram)
                : trim((string) (json_decode((string) ($proposal['summary'] ?? ''), true)['college_extension_program'] ?? '')),
            'project_team_leader' => trim($_POST['project_team_leader'] ?? ''),
            'members_trainers' => trim($_POST['members_trainers'] ?? ''),
            'implementing_college_department' => trim($_POST['implementing_college_department'] ?? ''),
            'collaborating_organizations' => trim($_POST['collaborating_organizations'] ?? ''),
            'beneficiaries' => trim($_POST['beneficiaries'] ?? ''),
            'male_beneficiaries' => trim($_POST['male_beneficiaries'] ?? ''),
            'female_beneficiaries' => trim($_POST['female_beneficiaries'] ?? ''),
            'duration_start_date' => $durationStartDate,
            'duration_end_date' => $durationEndDate,
            'duration_inclusive_dates' => $durationInclusiveDates,
            'location' => trim($_POST['location'] ?? ''),
            'budget' => trim($_POST['budget'] ?? ''),
            'source_of_fund' => $sourceOfFund,
            'rationale' => trim($_POST['rationale'] ?? ''),
            'general_objective' => trim($_POST['general_objective'] ?? ''),
            'specific_objectives' => trim($_POST['specific_objectives'] ?? ''),
            'community_analysis' => trim($_POST['community_analysis'] ?? ''),
            'problem_analysis' => trim($_POST['problem_analysis'] ?? ''),
            'target_group_description' => trim($_POST['target_group_description'] ?? ''),
            'partnerships' => $this->validatedKeyedRows($_POST['partnerships'] ?? [], [
                'partner',
                'task_description',
                'area_of_responsibility',
                'resource_sharing',
            ]),
            'team_duties' => $this->validatedKeyedRows($_POST['team_duties'] ?? [], [
                'user_id',
                'member',
                'college',
                'department',
                'role',
                'task_description',
                'responsibility',
            ]),
            'logical_framework' => $this->validatedKeyedRows($_POST['logical_framework'] ?? [], [
                'inputs',
                'activities',
                'outputs',
                'effects',
                'outcomes',
                'impact',
                'sdg',
            ]),
            'methodology' => trim($_POST['methodology'] ?? ''),
            'activities_narrative' => trim($_POST['activities_narrative'] ?? ''),
            'work_plan' => $this->validatedKeyedRows($_POST['work_plan'] ?? [], [
                'activities',
                'objective',
                'indicator',
                'strategies',
                'time_frame',
                'responsible_persons',
                'budget_needed',
                'outputs',
            ]),
            'budget_general' => $this->validatedKeyedRows($_POST['budget_general'] ?? [], [
                'item',
                'particulars',
                'amount',
            ]),
            'budget_line_groups' => $this->validatedBudgetLineGroups($_POST['budget_line_groups'] ?? []),
            'references' => trim($_POST['references'] ?? ''),
            // Sign-off names and dates are derived from the record (eso_extension_signoff), not typed in.
        ];

        return [
            'title' => trim($_POST['title'] ?? ''),
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => $sourceOfFund,
            'risk_level' => 'low',
            'ethics_required' => false,
            'extension_program_id' => $extensionProgram !== null ? (int) $extensionProgram['id'] : null,
            'extension_project_id' => $extensionProject !== null ? (int) $extensionProject['id'] : null,
        ];
    }

    /**
     * @param list<string> $keys
     * @return list<array<string, string>>
     */
    private function validatedKeyedRows(mixed $rows, array $keys, int $max = 40): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $out = [];
        foreach (array_slice($rows, 0, $max) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [];
            $hasContent = false;
            foreach ($keys as $key) {
                $value = trim((string) ($row[$key] ?? ''));
                $entry[$key] = $value;
                if ($value !== '') {
                    $hasContent = true;
                }
            }

            if ($hasContent) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * @return list<array{label: string, items: list<array<string, string>>}>
     */
    private function validatedBudgetLineGroups(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $out = [];
        foreach (array_slice($groups, 0, 8) as $index => $group) {
            if (!is_array($group)) {
                continue;
            }

            $label = trim((string) ($group['label'] ?? ''));
            if ($label === '') {
                $label = 'Line-Item Budget ' . ($index + 1);
            }

            // Item numbers are assigned here (1, 2, 3...), so a blank row with only a number is not kept.
            $rawItems = is_array($group['items'] ?? null) ? $group['items'] : [];
            foreach ($rawItems as $key => $item) {
                if (is_array($item)) {
                    unset($rawItems[$key]['item_no']);
                }
            }
            $items = $this->validatedKeyedRows($rawItems, [
                'particulars',
                'qty',
                'unit',
                'cost_unit',
                'total_cost',
            ], 30);

            if ($items === []) {
                continue;
            }
            $items = array_map(
                static fn(array $item, int $position): array => ['item_no' => (string) ($position + 1)] + $item,
                array_values($items),
                array_keys(array_values($items))
            );

            $out[] = [
                'label' => $label,
                'items' => $items,
            ];
        }

        return $out;
    }

    private function resolvedProjectType(string $fallback = 'research'): string
    {
        $locked = proposal_nav_scope();
        if ($locked !== null) {
            return $locked;
        }

        $type = strtolower(trim((string) ($_POST['project_type'] ?? $fallback)));

        return in_array($type, ['research', 'innovation', 'development', 'extension'], true) ? $type : 'research';
    }

    private function validatedCompletedResearchesInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('completed_researches', $existingProposal);
        return $this->validatedQuarterlyResearchesReportInput('completed_researches', 'Completed Researches', $existingProposal);
    }

    private function validatedOngoingResearchesInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('ongoing_researches', $existingProposal);
        return $this->validatedQuarterlyResearchesReportInput('ongoing_researches', 'Ongoing Researches', $existingProposal);
    }

    private function validatedResearchOutputPublishedInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('research_output_published', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'research_output_published');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedResearchOutputPublishedEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'research_output_published',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Research Output Published';
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

    /** @return array<string, mixed> */
    private function validatedQuarterlyResearchesReportInput(string $formType, string $titleBase, ?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, $formType);
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedCompletedResearchesEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => $formType,
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = $titleBase;
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

    /** @return array<string, list<array<string, string>>> */
    private function validatedCompletedResearchesEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedCompletedResearchesRows($rows);
        }

        return $result;
    }

    /** @return array<string, string> */
    private function completedResearchesFundingSections(): array
    {
        return [
            'external' => 'A. Externally Funded',
            'inhouse' => 'B. In-house Funded',
            'personal' => 'C. University Supported through Official time',
        ];
    }

    /** @return list<array<string, string>> */
    private function validatedCompletedResearchesRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
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

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedResearchOutputPublishedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedResearchOutputPublishedRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedResearchOutputPublishedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
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

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    private function validatedResearchOutputPresentedInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('research_output_presented', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'research_output_presented');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedResearchOutputPresentedEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'research_output_presented',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Research Output Presented';
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

    private function validatedCommercializedInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('commercialized', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'commercialized');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedCommercializedEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'commercialized',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Commercialized';
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

    private function validatedResultedInExtensionInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('resulted_in_extension', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'resulted_in_extension');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedResultedInExtensionEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'resulted_in_extension',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Resulted in Extension';
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

    private function validatedJournalCitationInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('journal_citation', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'journal_citation');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedJournalCitationRows($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'journal_citation',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Journal Citation';
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

    private function validatedBookCitationInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('book_citation', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'book_citation');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedBookCitationRows($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'book_citation',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Book Citation';
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

    private function validatedInventionsUmCopyrightsInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('inventions_um_copyrights', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'inventions_um_copyrights');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedInventionsUmCopyrightsEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'inventions_um_copyrights',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Inventions, UM, Copyrights';
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

    private function validatedLinkagesInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('linkages', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'linkages');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $entries = $this->validatedLinkagesRows($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'linkages',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Linkages';
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

    private function validatedTrainingsConductedInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('trainings_conducted', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'trainings_conducted');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $postedEntries = $_POST['entries'] ?? [];
        $entries = [
            'need_based' => $this->validatedTrainingsConductedRows(
                is_array($postedEntries) ? ($postedEntries['need_based'] ?? []) : []
            ),
            'other' => $this->validatedTrainingsConductedRows(
                is_array($postedEntries) ? ($postedEntries['other'] ?? []) : []
            ),
        ];
        $structuredSummary = [
            'form_type' => 'trainings_conducted',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
            'challenges' => trim($_POST['challenges'] ?? ''),
            'best_practices' => trim($_POST['best_practices'] ?? ''),
            'lessons_learned' => trim($_POST['lessons_learned'] ?? ''),
        ];

        $title = 'Trainings Conducted';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    private function validatedTechnicalAdvisoryInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('technical_advisory', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'technical_advisory');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $entries = $this->validatedTechnicalAdvisoryRows($_POST['entries'] ?? []);
        $structuredSummary = [
            'form_type' => 'technical_advisory',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
            'challenges' => trim($_POST['challenges'] ?? ''),
            'best_practices' => trim($_POST['best_practices'] ?? ''),
            'lessons_learned' => trim($_POST['lessons_learned'] ?? ''),
        ];

        $title = 'Technical Advisory';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    private function validatedExtensionLinkagesInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('extension_linkages', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'extension_linkages');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $entries = $this->validatedExtensionLinkagesRows($_POST['entries'] ?? []);
        $structuredSummary = [
            'form_type' => 'extension_linkages',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'Extension Linkages';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    private function validatedOutreachActivitiesInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('outreach_activities', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'outreach_activities');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $entries = $this->validatedOutreachActivitiesRows($_POST['entries'] ?? []);
        $structuredSummary = [
            'form_type' => 'outreach_activities',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'entries' => $entries,
            'challenges' => trim($_POST['challenges'] ?? ''),
            'best_practices' => trim($_POST['best_practices'] ?? ''),
            'lessons_learned' => trim($_POST['lessons_learned'] ?? ''),
        ];

        $title = 'Outreach Activities';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    private function validatedTechnologyAdoptionInput(?array $existingProposal = null): array
    {
        $this->useGeneratedReportRows('technology_adoption', $existingProposal);
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'technology_adoption');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $demonstrationFarmEntries = $this->validatedTechnologyAdoptionRows(
            $_POST['demonstration_farm_entries'] ?? [],
            [
                'extension_personnel',
                'demonstration_farm',
                'year_established',
                'technology_demonstrated',
                'adopter_name',
                'adopter_location',
                'date_adopted',
                'date_commercialized',
                'net_income',
            ]
        );
        $trainingEntries = $this->validatedTechnologyAdoptionRows(
            $_POST['training_entries'] ?? [],
            [
                'extension_personnel',
                'extension_service_title',
                'date_conducted',
                'technology_demonstrated',
                'adopter_name',
                'adopter_location',
                'date_adopted',
                'date_commercialized',
                'net_income',
            ]
        );
        $structuredSummary = [
            'form_type' => 'technology_adoption',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'demonstration_farm_entries' => $demonstrationFarmEntries,
            'training_entries' => $trainingEntries,
            'challenges' => trim($_POST['challenges'] ?? ''),
            'best_practices' => trim($_POST['best_practices'] ?? ''),
            'lessons_learned' => trim($_POST['lessons_learned'] ?? ''),
        ];

        $title = 'Technology Adoption';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' - ' . $reportAsOf;
        }

        return [
            'title' => $title,
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => '',
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    /** @param array<string, mixed>|null $existingProposal */
    /** @param array<string, mixed>|null $existingProposal */
    private function validatedConsolidatedCompletedResearchesInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'consolidated_completed_researches');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $postedEntries = $this->validatedConsolidatedCompletedResearchesEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');

        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        $structuredSummary = \App\Services\CompletedResearchesConsolidation::summaryForCoordinatorSave(
            $existingSummary,
            $postedEntries
        );
        $structuredSummary['version'] = 1;
        $structuredSummary['reporting_period'] = $reportingPeriod;
        $structuredSummary['report_as_of'] = $reportAsOf;
        $structuredSummary['college_name'] = $collegeName;

        $title = 'Consolidated Completed Researches';
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

    /** @param array<string, mixed>|null $existingProposal */
    private function validatedConsolidatedOngoingResearchesInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'consolidated_ongoing_researches');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $postedEntries = $this->validatedCompletedResearchesEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');

        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        $structuredSummary = \App\Services\OngoingResearchesConsolidation::summaryForCoordinatorSave(
            $existingSummary,
            $postedEntries
        );
        $structuredSummary['version'] = 1;
        $structuredSummary['reporting_period'] = $reportingPeriod;
        $structuredSummary['report_as_of'] = $reportAsOf;
        $isVpride = MonitoringRoles::isVpride();
        $structuredSummary['consolidation_scope'] = $isVpride
            ? \App\Services\OngoingResearchesConsolidation::SCOPE_VPRIDE
            : \App\Services\OngoingResearchesConsolidation::SCOPE_COLLEGE;
        if ($isVpride && $collegeName === '') {
            $collegeName = 'University-wide (All Colleges)';
        }
        $structuredSummary['college_name'] = $collegeName;

        $title = 'Consolidated Ongoing Researches';
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

    /** @param array<string, mixed>|null $existingProposal */
    private function validatedConsolidatedResearchOutputPublishedInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'consolidated_research_output_published');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $postedEntries = $this->validatedConsolidatedResearchOutputPublishedEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');

        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        $structuredSummary = \App\Services\ResearchOutputPublishedConsolidation::summaryForCoordinatorSave(
            $existingSummary,
            $postedEntries
        );
        $structuredSummary['version'] = 1;
        $structuredSummary['reporting_period'] = $reportingPeriod;
        $structuredSummary['report_as_of'] = $reportAsOf;
        $structuredSummary['college_name'] = $collegeName;

        $title = 'Consolidated Research Output Published';
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

    /** @param array<string, mixed>|null $existingProposal */
    private function validatedConsolidatedResearchOutputPresentedInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'consolidated_research_output_presented');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $postedEntries = $this->validatedConsolidatedResearchOutputPresentedEntries($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');

        $existingSummary = [];
        if ($existingProposal !== null && !empty($existingProposal['summary'])) {
            $decoded = json_decode((string) $existingProposal['summary'], true);
            if (is_array($decoded)) {
                $existingSummary = $decoded;
            }
        }

        $structuredSummary = \App\Services\ResearchOutputPresentedConsolidation::summaryForCoordinatorSave(
            $existingSummary,
            $postedEntries
        );
        $structuredSummary['version'] = 1;
        $structuredSummary['reporting_period'] = $reportingPeriod;
        $structuredSummary['report_as_of'] = $reportAsOf;
        $structuredSummary['college_name'] = $collegeName;

        $title = 'Consolidated Research Output Presented';
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

    private function validatedProgressReportInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'progress_report');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $entries = $this->validatedProgressReportRows($_POST['entries'] ?? []);
        $coauthors = [];
        $postedCoauthors = $_POST['coauthors'] ?? [];
        if (is_array($postedCoauthors)) {
            foreach ($postedCoauthors as $coauthor) {
                if (!is_array($coauthor)) {
                    continue;
                }
                $entry = [
                    'last_name' => trim((string) ($coauthor['last_name'] ?? '')),
                    'first_name' => trim((string) ($coauthor['first_name'] ?? '')),
                    'middle_name' => trim((string) ($coauthor['middle_name'] ?? '')),
                ];
                if ($entry['last_name'] === '' && $entry['first_name'] === '' && $entry['middle_name'] === '') {
                    continue;
                }
                $coauthors[] = $entry;
            }
        }
        $structuredSummary = [
            'form_type' => 'progress_report',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'title_prefix' => trim((string) ($_POST['title_prefix'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
            'campus' => trim((string) ($_POST['campus'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'contact_number' => trim((string) ($_POST['contact_number'] ?? '')),
            'google_scholar_link' => trim((string) ($_POST['google_scholar_link'] ?? '')),
            'researchgate_link' => trim((string) ($_POST['researchgate_link'] ?? '')),
            'coauthors' => $coauthors,
            'research_title' => trim((string) ($_POST['research_title'] ?? '')),
            'period_covered' => trim((string) ($_POST['period_covered'] ?? '')),
            'duration_months' => trim((string) ($_POST['duration_months'] ?? '')),
            'funding_support' => trim((string) ($_POST['funding_support'] ?? '')),
            'project_overview' => trim((string) ($_POST['project_overview'] ?? '')),
            'entries' => $entries,
            'revised_targets' => trim((string) ($_POST['revised_targets'] ?? '')),
            'general_progress' => trim((string) ($_POST['general_progress'] ?? '')),
        ];

        $title = 'Progress Report';
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

    private function validatedTerminalReportInput(?array $existingProposal = null): array
    {
        $reporting = $this->quarterlyReportingFromRequest($existingProposal, 'terminal_report');
        $reportAsOf = $reporting['report_as_of'];
        $reportingPeriod = $reporting['reporting_period'];
        $collegeName = trim($_POST['college_name'] ?? '');
        $entries = $this->validatedProgressReportRows($_POST['entries'] ?? []);
        $coauthors = [];
        $postedCoauthors = $_POST['coauthors'] ?? [];
        if (is_array($postedCoauthors)) {
            foreach ($postedCoauthors as $coauthor) {
                if (!is_array($coauthor)) {
                    continue;
                }
                $entry = [
                    'last_name' => trim((string) ($coauthor['last_name'] ?? '')),
                    'first_name' => trim((string) ($coauthor['first_name'] ?? '')),
                    'middle_name' => trim((string) ($coauthor['middle_name'] ?? '')),
                ];
                if ($entry['last_name'] === '' && $entry['first_name'] === '' && $entry['middle_name'] === '') {
                    continue;
                }
                $coauthors[] = $entry;
            }
        }
        $structuredSummary = [
            'form_type' => 'terminal_report',
            'version' => 1,
            'reporting_period' => $reportingPeriod,
            'report_as_of' => $reportAsOf,
            'college_name' => $collegeName,
            'title_prefix' => trim((string) ($_POST['title_prefix'] ?? '')),
            'last_name' => trim((string) ($_POST['last_name'] ?? '')),
            'first_name' => trim((string) ($_POST['first_name'] ?? '')),
            'middle_name' => trim((string) ($_POST['middle_name'] ?? '')),
            'campus' => trim((string) ($_POST['campus'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
            'contact_number' => trim((string) ($_POST['contact_number'] ?? '')),
            'google_scholar_link' => trim((string) ($_POST['google_scholar_link'] ?? '')),
            'researchgate_link' => trim((string) ($_POST['researchgate_link'] ?? '')),
            'coauthors' => $coauthors,
            'research_title' => trim((string) ($_POST['research_title'] ?? '')),
            'period_covered' => trim((string) ($_POST['period_covered'] ?? '')),
            'duration_months' => trim((string) ($_POST['duration_months'] ?? '')),
            'funding_support' => trim((string) ($_POST['funding_support'] ?? '')),
            'project_overview' => trim((string) ($_POST['project_overview'] ?? '')),
            'entries' => $entries,
            'revised_targets' => trim((string) ($_POST['revised_targets'] ?? '')),
            'general_progress' => trim((string) ($_POST['general_progress'] ?? '')),
        ];

        $title = 'Terminal Report';
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

    private function validatedTerminalReportAssessmentFormInput(): array
    {
        $date = trim((string) ($_POST['date'] ?? ''));
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $parameters = [
            1 => 'The abstract presents an accurate synopsis of the research.',
            2 => 'The approved methodology in the proposal was followed (i.e. sample size, sampling procedure, data collection, data analysis, etc).',
            3 => 'The approved research ethics protocol was followed.',
            4 => 'Analysis of limitations and errors of the methods were presented.',
            5 => 'Stakeholders validated the data.',
            6 => 'Scientific literature supported the findings.',
            7 => 'The conclusion(s) captures the result of hypotheses test.',
            8 => 'The conclusions validated the theory used.',
            9 => 'The study generated new knowledge in the discipline.',
            10 => 'There are leads to future studies emanating from the current results.',
            11 => 'The paper complied with the standard of technical writing.',
            12 => "There is evidence that scientific literature and stakeholder's view support the recommendations.",
            13 => 'The over-all presentation of report captures the substance of the study.',
        ];

        $scores = [];
        foreach ($parameters as $number => $label) {
            $postedValue = strtolower(trim((string) ($_POST['scores'][$number] ?? '')));
            $scores[(string) $number] = in_array($postedValue, ['yes', 'no', 'na'], true) ? $postedValue : '';
        }

        $structuredSummary = [
            'form_type' => 'terminal_report_assessment_form',
            'version' => 1,
            'date' => $date,
            'control_no' => trim((string) ($_POST['control_no'] ?? '')),
            'college_name' => $collegeName,
            'lead_researcher' => trim((string) ($_POST['lead_researcher'] ?? '')),
            'co_researchers' => trim((string) ($_POST['co_researchers'] ?? '')),
            'research_title' => trim((string) ($_POST['research_title'] ?? '')),
            'duration_of_study' => trim((string) ($_POST['duration_of_study'] ?? '')),
            'proposed_budget' => trim((string) ($_POST['proposed_budget'] ?? '')),
            'scores' => $scores,
            'comments' => trim((string) ($_POST['comments'] ?? '')),
            'judgment' => trim((string) ($_POST['judgment'] ?? '')),
            'reviewer_name' => trim((string) ($_POST['reviewer_name'] ?? '')),
        ];

        $title = 'Terminal Report Assessment Form';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
        }
        if ($date !== '') {
            $title .= ' - ' . $date;
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

    private function validatedObrMatrixInput(): array
    {
        $entries = $this->validatedObrMatrixRows($_POST['entries'] ?? []);
        $collegeName = trim($_POST['college_name'] ?? '');
        $structuredSummary = [
            'form_type' => 'obr_matrix',
            'version' => 1,
            'college_name' => $collegeName,
            'entries' => $entries,
        ];

        $title = 'OBR Matrix';
        if ($collegeName !== '') {
            $title .= ' - ' . $collegeName;
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

    /** @return list<array<string, string>> */
    private function validatedProgressReportRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'activity' => trim((string) ($row['activity'] ?? '')),
                'target_schedule' => trim((string) ($row['target_schedule'] ?? '')),
                'actual_period' => trim((string) ($row['actual_period'] ?? '')),
                'problems' => trim((string) ($row['problems'] ?? '')),
            ];

            if ($entry['activity'] === '' && $entry['target_schedule'] === '' && $entry['actual_period'] === '' && $entry['problems'] === '') {
                continue;
            }

            $validated[] = $entry;
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedConsolidatedCompletedResearchesEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedCompletedResearchesRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedCompletedResearchesRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
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
                'college' => trim((string) ($row['college'] ?? '')),
                'campus' => trim((string) ($row['campus'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedConsolidatedResearchOutputPublishedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedResearchOutputPublishedRows($rows);
        }

        return $result;
    }

    private function validatedConsolidatedResearchOutputPresentedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedConsolidatedResearchOutputPresentedRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedResearchOutputPublishedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
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

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedConsolidatedResearchOutputPresentedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'research_title' => trim((string) ($row['research_title'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                'duration_months' => trim((string) ($row['duration_months'] ?? '')),
                'researchers' => trim((string) ($row['researchers'] ?? '')),
                'paper_title' => trim((string) ($row['paper_title'] ?? '')),
                'presenter_name' => trim((string) ($row['presenter_name'] ?? '')),
                'conference_title' => trim((string) ($row['conference_title'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'presentation_date' => trim((string) ($row['presentation_date'] ?? '')),
                'organizer' => trim((string) ($row['organizer'] ?? '')),
                'conference_type' => strtolower(trim((string) ($row['conference_type'] ?? ''))),
                'presentation_type' => strtolower(trim((string) ($row['presentation_type'] ?? ''))),
                'award_received' => trim((string) ($row['award_received'] ?? '')),
                'college' => trim((string) ($row['college'] ?? '')),
                'campus' => trim((string) ($row['campus'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedLinkagesRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'program' => trim((string) ($row['program'] ?? '')),
                'partner' => trim((string) ($row['partner'] ?? '')),
                'linkage_forged' => trim((string) ($row['linkage_forged'] ?? '')),
                'institution_type' => trim((string) ($row['institution_type'] ?? '')),
                'deliverables' => trim((string) ($row['deliverables'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                'personnel' => trim((string) ($row['personnel'] ?? '')),
                'beneficiaries' => trim((string) ($row['beneficiaries'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedExtensionLinkagesRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'partner' => trim((string) ($row['partner'] ?? '')),
                'linkage_forged' => trim((string) ($row['linkage_forged'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'effectivity_from' => trim((string) ($row['effectivity_from'] ?? '')),
                'effectivity_to' => trim((string) ($row['effectivity_to'] ?? '')),
                'extension_activities' => trim((string) ($row['extension_activities'] ?? '')),
                'conducted_from' => trim((string) ($row['conducted_from'] ?? '')),
                'conducted_to' => trim((string) ($row['conducted_to'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedOutreachActivitiesRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $beneficiaries = max(0, (int) ($row['beneficiaries'] ?? 0));
            $entry = [
                'title' => trim((string) ($row['title'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'start_date' => trim((string) ($row['start_date'] ?? '')),
                'end_date' => trim((string) ($row['end_date'] ?? '')),
                'beneficiaries' => (string) $beneficiaries,
            ];

            $hasContent = false;
            foreach ($entry as $key => $value) {
                if ($key === 'beneficiaries') {
                    if ((string) $value !== '0') {
                        $hasContent = true;
                        break;
                    }
                    continue;
                }
                if ((string) $value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /**
     * @param list<string> $fields
     * @return list<array<string, string>>
     */
    private function validatedTechnologyAdoptionRows(mixed $rows, array $fields): array
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

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '' && $value !== '0') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, mixed>> */
    private function validatedTrainingsConductedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $allowedLengths = array_keys(trainings_conducted_length_weights());
        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $trainingLength = trim((string) ($row['training_length'] ?? ''));
            if (!in_array($trainingLength, $allowedLengths, true)) {
                $trainingLength = '';
            }

            $personsTrained = max(0, (int) ($row['persons_trained'] ?? 0));
            $entry = [
                'extension_program' => trim((string) ($row['extension_program'] ?? '')),
                'training_title' => trim((string) ($row['training_title'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'training_date' => trim((string) ($row['training_date'] ?? '')),
                'participant_type' => trim((string) ($row['participant_type'] ?? '')),
                'persons_trained' => (string) $personsTrained,
                'training_length' => $trainingLength,
                'persons_trained_weighted' => (string) trainings_conducted_weighted_persons($personsTrained, $trainingLength),
                'personnel_name' => trim((string) ($row['personnel_name'] ?? '')),
                'personnel_role' => trim((string) ($row['personnel_role'] ?? '')),
                'budget_source' => trim((string) ($row['budget_source'] ?? '')),
                'budget_amount' => trim((string) ($row['budget_amount'] ?? '')),
                'trainees_surveyed' => (string) max(0, (int) ($row['trainees_surveyed'] ?? 0)),
                'quality_poor' => (string) max(0, (int) ($row['quality_poor'] ?? 0)),
                'quality_fair' => (string) max(0, (int) ($row['quality_fair'] ?? 0)),
                'quality_good' => (string) max(0, (int) ($row['quality_good'] ?? 0)),
                'quality_better' => (string) max(0, (int) ($row['quality_better'] ?? 0)),
                'quality_best' => (string) max(0, (int) ($row['quality_best'] ?? 0)),
                'timeliness_poor' => (string) max(0, (int) ($row['timeliness_poor'] ?? 0)),
                'timeliness_fair' => (string) max(0, (int) ($row['timeliness_fair'] ?? 0)),
                'timeliness_good' => (string) max(0, (int) ($row['timeliness_good'] ?? 0)),
                'timeliness_better' => (string) max(0, (int) ($row['timeliness_better'] ?? 0)),
                'timeliness_best' => (string) max(0, (int) ($row['timeliness_best'] ?? 0)),
            ];

            $hasContent = false;
            foreach ($entry as $key => $value) {
                if ($key === 'persons_trained_weighted') {
                    continue;
                }
                if ((string) $value !== '' && (string) $value !== '0') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, mixed>> */
    private function validatedTechnicalAdvisoryRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'extension_program' => trim((string) ($row['extension_program'] ?? '')),
                'technical_advisory_conducted' => trim((string) ($row['technical_advisory_conducted'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'advisory_date' => trim((string) ($row['advisory_date'] ?? '')),
                'client_type' => trim((string) ($row['client_type'] ?? '')),
                'clients_served' => (string) max(0, (int) ($row['clients_served'] ?? 0)),
                'personnel_name' => trim((string) ($row['personnel_name'] ?? '')),
                'trainees_surveyed' => (string) max(0, (int) ($row['trainees_surveyed'] ?? 0)),
                'quality_poor' => (string) max(0, (int) ($row['quality_poor'] ?? 0)),
                'quality_fair' => (string) max(0, (int) ($row['quality_fair'] ?? 0)),
                'quality_good' => (string) max(0, (int) ($row['quality_good'] ?? 0)),
                'quality_better' => (string) max(0, (int) ($row['quality_better'] ?? 0)),
                'quality_best' => (string) max(0, (int) ($row['quality_best'] ?? 0)),
                'timeliness_poor' => (string) max(0, (int) ($row['timeliness_poor'] ?? 0)),
                'timeliness_fair' => (string) max(0, (int) ($row['timeliness_fair'] ?? 0)),
                'timeliness_good' => (string) max(0, (int) ($row['timeliness_good'] ?? 0)),
                'timeliness_better' => (string) max(0, (int) ($row['timeliness_better'] ?? 0)),
                'timeliness_best' => (string) max(0, (int) ($row['timeliness_best'] ?? 0)),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ((string) $value !== '' && (string) $value !== '0') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedObrMatrixRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'university_thrusts' => trim((string) ($row['university_thrusts'] ?? '')),
                'college_thrusts' => trim((string) ($row['college_thrusts'] ?? '')),
                'research_areas' => trim((string) ($row['research_areas'] ?? '')),
                'study_title' => trim((string) ($row['study_title'] ?? '')),
                'research_results' => trim((string) ($row['research_results'] ?? '')),
                'extension_utilization' => trim((string) ($row['extension_utilization'] ?? '')),
                'outcomes' => trim((string) ($row['outcomes'] ?? '')),
                'six_ps' => trim((string) ($row['six_ps'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedBookCitationRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'title_original_article_cited' => trim((string) ($row['title_original_article_cited'] ?? '')),
                'authors_original_article' => trim((string) ($row['authors_original_article'] ?? '')),
                'title_publication_original' => trim((string) ($row['title_publication_original'] ?? '')),
                'title_new_book_chapter' => trim((string) ($row['title_new_book_chapter'] ?? '')),
                'authors_book_chapter' => trim((string) ($row['authors_book_chapter'] ?? '')),
                'title_book_chapter_published' => trim((string) ($row['title_book_chapter_published'] ?? '')),
                'volume_issue' => trim((string) ($row['volume_issue'] ?? '')),
                'pages' => trim((string) ($row['pages'] ?? '')),
                'year_publication' => trim((string) ($row['year_publication'] ?? '')),
                'isbn' => trim((string) ($row['isbn'] ?? '')),
                'publisher' => trim((string) ($row['publisher'] ?? '')),
                'date_cited' => trim((string) ($row['date_cited'] ?? '')),
                'indexing' => trim((string) ($row['indexing'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array<string, string>> */
    private function validatedJournalCitationRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'authors_original_article' => trim((string) ($row['authors_original_article'] ?? '')),
                'title_original_article_cited' => trim((string) ($row['title_original_article_cited'] ?? '')),
                'title_refereed_journal_original' => trim((string) ($row['title_refereed_journal_original'] ?? '')),
                'title_new_research_article' => trim((string) ($row['title_new_research_article'] ?? '')),
                'authors_new_article' => trim((string) ($row['authors_new_article'] ?? '')),
                'title_refereed_journal_new' => trim((string) ($row['title_refereed_journal_new'] ?? '')),
                'volume_issue' => trim((string) ($row['volume_issue'] ?? '')),
                'pages' => trim((string) ($row['pages'] ?? '')),
                'year_publication' => trim((string) ($row['year_publication'] ?? '')),
                'publisher' => trim((string) ($row['publisher'] ?? '')),
                'date_cited' => trim((string) ($row['date_cited'] ?? '')),
                'issn' => trim((string) ($row['issn'] ?? '')),
                'indexing' => trim((string) ($row['indexing'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedInventionsUmCopyrightsEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->inventionsUmCopyrightsSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedInventionsUmCopyrightsRows($rows);
        }

        return $result;
    }

    /** @return array<string, string> */
    private function inventionsUmCopyrightsSections(): array
    {
        return [
            'inventions_patented' => 'Inventions - A. Patented',
            'inventions_applied_for_patenting' => 'Inventions - B. Applied for Patenting',
            'inventions_not_patented_but_utilized' => 'Inventions - C. Not Patented but Utilized by the Community',
            'utility_models_registered' => 'Utility Models - A. Registered',
            'utility_models_applied_for_registration' => 'Utility Models - B. Applied for Registration',
            'copyrights' => 'Copyrights',
        ];
    }

    /** @return list<array<string, string>> */
    private function validatedInventionsUmCopyrightsRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'research_title' => trim((string) ($row['research_title'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_developed_completed' => trim((string) ($row['date_developed_completed'] ?? '')),
                'inventors_researchers' => trim((string) ($row['inventors_researchers'] ?? '')),
                'patent_registration_copyright_number' => trim((string) ($row['patent_registration_copyright_number'] ?? '')),
                'date_of_issue_application' => trim((string) ($row['date_of_issue_application'] ?? '')),
                'adopter' => trim((string) ($row['adopter'] ?? '')),
                'commercial_product_name' => trim((string) ($row['commercial_product_name'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedCommercializedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedCommercializedRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedCommercializedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'research_title' => trim((string) ($row['research_title'] ?? '')),
                'researchers' => trim((string) ($row['researchers'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                'product_name' => trim((string) ($row['product_name'] ?? '')),
                'adopter' => trim((string) ($row['adopter'] ?? '')),
                'date_adopted' => trim((string) ($row['date_adopted'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedResultedInExtensionEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedResultedInExtensionRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedResultedInExtensionRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'research_title' => trim((string) ($row['research_title'] ?? '')),
                'researchers' => trim((string) ($row['researchers'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                'extension_program_activity' => trim((string) ($row['extension_program_activity'] ?? '')),
                'faculty_staff_involved' => trim((string) ($row['faculty_staff_involved'] ?? '')),
                'budget_source' => trim((string) ($row['budget_source'] ?? '')),
                'budget_amount' => trim((string) ($row['budget_amount'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'date' => trim((string) ($row['date'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return array<string, list<array<string, string>>> */
    private function validatedResearchOutputPresentedEntries(mixed $sections): array
    {
        $result = [];
        foreach ($this->completedResearchesFundingSections() as $key => $label) {
            unset($label);
            $rows = is_array($sections) ? ($sections[$key] ?? []) : [];
            $result[$key] = $this->validatedResearchOutputPresentedRows($rows);
        }

        return $result;
    }

    /** @return list<array<string, string>> */
    private function validatedResearchOutputPresentedRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = [
                'research_title' => trim((string) ($row['research_title'] ?? '')),
                'date_started' => trim((string) ($row['date_started'] ?? '')),
                'date_completed' => trim((string) ($row['date_completed'] ?? '')),
                'duration_months' => trim((string) ($row['duration_months'] ?? '')),
                'researchers' => trim((string) ($row['researchers'] ?? '')),
                'paper_title' => trim((string) ($row['paper_title'] ?? '')),
                'presenter_name' => trim((string) ($row['presenter_name'] ?? '')),
                'conference_title' => trim((string) ($row['conference_title'] ?? '')),
                'venue' => trim((string) ($row['venue'] ?? '')),
                'presentation_date' => trim((string) ($row['presentation_date'] ?? '')),
                'organizer' => trim((string) ($row['organizer'] ?? '')),
                'conference_type' => strtolower(trim((string) ($row['conference_type'] ?? ''))),
                'presentation_type' => strtolower(trim((string) ($row['presentation_type'] ?? ''))),
                'award_received' => trim((string) ($row['award_received'] ?? '')),
                'google_drive_link' => trim((string) ($row['google_drive_link'] ?? '')),
            ];

            $hasContent = false;
            foreach ($entry as $value) {
                if ($value !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                $validated[] = $entry;
            }
        }

        return $validated;
    }

    /** @return list<array{item: string, amount: string, justification: string}> */
    private function validatedBudgetItems(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $items = [];
        foreach (array_slice($rows, 0, 3) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $items[] = [
                'item' => trim((string) ($row['item'] ?? '')),
                'amount' => trim((string) ($row['amount'] ?? '')),
                'justification' => trim((string) ($row['justification'] ?? '')),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{last_name: string, first_name: string, middle_name: string, user_id?: int}>
     */
    private function validatedCoAuthors(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $currentUserId = (int) (Auth::user()['id'] ?? 0);
        $coAuthors = [];
        $linkedUserIds = [];
        foreach (array_slice($rows, 0, 20) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $linkedUserId = (int) ($row['user_id'] ?? 0);
            if ($linkedUserId > 0) {
                if ($linkedUserId === $currentUserId || isset($linkedUserIds[$linkedUserId])) {
                    continue;
                }

                $faculty = User::findActiveFacultyById($linkedUserId);
                if ($faculty === null) {
                    continue;
                }

                $linkedUserIds[$linkedUserId] = true;
                $coAuthors[] = [
                    'user_id' => $linkedUserId,
                    'last_name' => trim((string) ($faculty['last_name'] ?? '')),
                    'first_name' => trim((string) ($faculty['first_name'] ?? '')),
                    'middle_name' => trim((string) ($row['middle_name'] ?? '')),
                ];
                continue;
            }

            $entry = [
                'last_name' => trim((string) ($row['last_name'] ?? '')),
                'first_name' => trim((string) ($row['first_name'] ?? '')),
                'middle_name' => trim((string) ($row['middle_name'] ?? '')),
            ];

            if ($entry['last_name'] === '' && $entry['first_name'] === '' && $entry['middle_name'] === '') {
                continue;
            }

            $coAuthors[] = $entry;
        }

        return $coAuthors;
    }

    /** @return list<array{id: int, label: string, name: string, college_name: string, department: string, department_label: string}> */
    private function formatDurationInclusiveDates(string $startDate, string $endDate, ?array $proposal): string
    {
        $format = static function (string $isoDate): ?string {
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $isoDate);

            return $parsed instanceof \DateTimeImmutable ? $parsed->format('F j, Y') : null;
        };

        $start = $startDate !== '' ? $format($startDate) : null;
        $end = $endDate !== '' ? $format($endDate) : null;

        if ($start !== null && $end !== null) {
            return $start === $end ? $start : $start . ' to ' . $end;
        }
        if ($start !== null) {
            return $start;
        }
        if ($end !== null) {
            return $end;
        }

        if ($proposal !== null && !empty($proposal['summary'])) {
            $existingSummary = json_decode((string) $proposal['summary'], true);
            if (is_array($existingSummary)) {
                return trim((string) ($existingSummary['duration_inclusive_dates'] ?? ''));
            }
        }

        return '';
    }

    /** @return array<string, string> college name => active dean's full name */
    /** @return list<string> */
    private function facultyTeamOptions(): array
    {
        $options = [];
        $faculty = User::allFaculty();
        $specializations = \App\Models\UserSpecialization::forUsers(array_map(static fn (array $row): int => (int) $row['id'], $faculty));
        foreach ($faculty as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $lastName = trim((string) ($row['last_name'] ?? ''));
            $firstName = trim((string) ($row['first_name'] ?? ''));
            $collegeName = trim((string) ($row['college_name'] ?? ''));
            $department = trim((string) ($row['program'] ?? ''));
            $departmentLabel = $department !== '' ? $department : 'No department/program assigned';
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                $fullName = trim($lastName . ', ' . $firstName);
            }

            $labelParts = [];
            if ($lastName !== '' || $firstName !== '') {
                $labelParts[] = trim($lastName . ', ' . $firstName, ', ');
            }
            if ($collegeName !== '') {
                $labelParts[] = $collegeName;
            }
            $labelParts[] = $departmentLabel;

            $options[] = [
                'id' => $id,
                'label' => implode(' - ', $labelParts) !== '' ? implode(' - ', $labelParts) : $fullName,
                'name' => $fullName,
                'college_name' => $collegeName,
                'department' => $department,
                'department_label' => $departmentLabel,
                'specializations' => $specializations[$id] ?? [],
            ];
        }

        return $options;
    }

    /** @return list<array{id: int, label: string, last_name: string, first_name: string, middle_name: string, college_name: string}> */
    private function facultyCoAuthorOptions(): array
    {
        $currentUserId = (int) (Auth::user()['id'] ?? 0);

        return \App\Support\ProposalCoAuthors::optionsForPicker(User::allFaculty(), $currentUserId);
    }

    private function syncCoAuthorInvitations(int $proposalId, int $leadUserId, ?string $summaryJson, ?string $previousSummaryJson): void
    {
        if ($summaryJson === null || trim($summaryJson) === '') {
            return;
        }

        $summary = json_decode($summaryJson, true);
        if (!is_array($summary) || !is_array($summary['coauthors'] ?? null)) {
            return;
        }

        \App\Services\CoAuthorInvitationService::syncAfterProposalSave(
            $proposalId,
            $leadUserId,
            $summary['coauthors'],
            $previousSummaryJson
        );
    }

    /** @return list<string> */
    private function validatedSixPs(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $allowed = [
            'patent_granted',
            'publication',
            'people_trained',
            'partnership_developed',
            'products_processes_developed',
            'policies_formulated',
        ];

        return array_values(array_intersect($allowed, array_map('strval', $values)));
    }

    /** @return list<array{activity: string, months: list<string>}> */
    private function validatedImplementationPlan(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $allowedMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $plan = [];

        foreach (array_slice($rows, 0, 8) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $months = [];
            if (is_array($row['months'] ?? null)) {
                $months = array_values(array_intersect($allowedMonths, array_map('strval', $row['months'])));
            }

            $plan[] = [
                'activity' => trim((string) ($row['activity'] ?? '')),
                'months' => $months,
            ];
        }

        return $plan;
    }

    private function hasStructuredSummaryContent(array $summary): bool
    {
        foreach ($summary as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (is_array($value) && $this->arrayHasContent($value)) {
                return true;
            }
        }

        return false;
    }

    private function arrayHasContent(array $values): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (is_array($value) && $this->arrayHasContent($value)) {
                return true;
            }
        }

        return false;
    }

    private function authorizeView(int $id): ?array
    {
        $proposal = Proposal::find($id);
        if (!$proposal) {
            http_response_code(404);
            view('errors.404');
            return null;
        }
        $user = Auth::user();
        if (MonitoringRoles::isVpride() || Auth::hasRole('ride_admin')) {
            return $proposal;
        }
        if (Auth::hasRole('ride_reporter')) {
            return $proposal;
        }
        if ((int) $proposal['user_id'] === (int) $user['id']) {
            return $proposal;
        }
        if (Auth::hasRole('faculty')
            && \App\Support\ProposalCoAuthors::userHasCoauthorAccess(
                (int) $proposal['id'],
                (string) ($proposal['summary'] ?? ''),
                (int) $user['id']
            )) {
            return $proposal;
        }
        if (MonitoringRoles::proposalScopeType() !== null) {
            if (MonitoringRoles::canViewProposal($proposal)) {
                return $proposal;
            }
            http_response_code(403);
            view('errors.403');
            return null;
        }
        if (Auth::hasRole('coordinator_research', 'coordinator_extension', 'dean') && (int) $user['college_id'] === (int) $proposal['college_id']) {
            return $proposal;
        }
        http_response_code(403);
        view('errors.403');
        return null;
    }

    private function canEdit(array $proposal): bool
    {
        $user = Auth::user();
        return (int) $proposal['user_id'] === (int) $user['id']
            && in_array($proposal['status'], ['draft', 'returned'], true);
    }

    private function canForceManage(array $proposal): bool
    {
        if (!Auth::hasRole('ride_admin', 'vpride', 'dean')) {
            return false;
        }

        return MonitoringRoles::canViewProposal($proposal);
    }

    private function canApprove(array $proposal): bool
    {
        if (!in_array($proposal['status'], ['submitted', 'under_review'], true)) {
            return false;
        }
        if (!MonitoringRoles::canViewProposal($proposal)) {
            return false;
        }
        $step = $proposal['current_step'] ?? '';
        return match ($step) {
            MonitoringRoles::COORDINATOR_RESEARCH => MonitoringRoles::isCoordinatorResearch(),
            MonitoringRoles::COORDINATOR_EXTENSION => MonitoringRoles::isCoordinatorExtension(),
            MonitoringRoles::DEAN => Auth::hasRole('dean'),
            MonitoringRoles::DIRECTOR_RESEARCH => MonitoringRoles::isDirectorResearch(),
            MonitoringRoles::DIRECTOR_EXTENSION => MonitoringRoles::isDirectorExtension(),
            MonitoringRoles::VPRIDE => MonitoringRoles::isVpride(),
            default => false,
        };
    }

    private function allowCompletedResearchesAccess(): bool
    {
        if (MonitoringRoles::canAccessCompletedResearches()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowOngoingResearchesAccess(): bool
    {
        if (MonitoringRoles::canAccessOngoingResearches()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowResearchOutputPublishedAccess(): bool
    {
        if (MonitoringRoles::canAccessResearchOutputPublished()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowResearchOutputPresentedAccess(): bool
    {
        if (MonitoringRoles::canAccessResearchOutputPresented()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowCommercializedAccess(): bool
    {
        if (MonitoringRoles::canAccessCommercialized()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowResultedInExtensionAccess(): bool
    {
        if (MonitoringRoles::canAccessResultedInExtension()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowJournalCitationAccess(): bool
    {
        if (MonitoringRoles::canAccessJournalCitation()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowBookCitationAccess(): bool
    {
        if (MonitoringRoles::canAccessBookCitation()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowInventionsUmCopyrightsAccess(): bool
    {
        if (MonitoringRoles::canAccessInventionsUmCopyrights()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowLinkagesAccess(): bool
    {
        if (MonitoringRoles::canAccessLinkages()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowTrainingsConductedAccess(): bool
    {
        if (MonitoringRoles::canAccessTrainingsConducted()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowTechnicalAdvisoryAccess(): bool
    {
        if (MonitoringRoles::canAccessTechnicalAdvisory()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowExtensionLinkagesAccess(): bool
    {
        if (MonitoringRoles::canAccessExtensionLinkages()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowOutreachActivitiesAccess(): bool
    {
        if (MonitoringRoles::canAccessOutreachActivities()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowTechnologyAdoptionAccess(): bool
    {
        if (MonitoringRoles::canAccessTechnologyAdoption()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowConsolidatedCompletedResearchesAccess(): bool
    {
        if (MonitoringRoles::canAccessConsolidatedCompletedResearches()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowConsolidatedOngoingResearchesAccess(): bool
    {
        if (MonitoringRoles::canAccessConsolidatedOngoingResearches()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowConsolidatedResearchOutputPublishedAccess(): bool
    {
        if (MonitoringRoles::canAccessConsolidatedResearchOutputPublished()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowConsolidatedResearchOutputPresentedAccess(): bool
    {
        if (MonitoringRoles::canAccessConsolidatedResearchOutputPresented()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowProgressReportAccess(): bool
    {
        if (MonitoringRoles::canAccessProgressReport()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowTerminalReportAccess(): bool
    {
        if (MonitoringRoles::canAccessTerminalReport()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    /** @param array<string, mixed> $user */
    private function visibleProposalsForCurrentUser(array $user): array
    {
        $scopeType = MonitoringRoles::proposalScopeType();
        if (MonitoringRoles::isVpride() || Auth::hasRole('ride_reporter')) {
            return $scopeType !== null ? Proposal::all(null, $scopeType) : Proposal::all();
        }
        if (MonitoringRoles::isCoordinatorResearch() || MonitoringRoles::isCoordinatorExtension() || MonitoringRoles::isExtensionWorker()) {
            if (!($user['college_id'] ?? null)) {
                return [];
            }

            return array_values(array_filter(
                Proposal::forCollege((int) $user['college_id']),
                static fn (array $proposal): bool => (string) ($proposal['project_type'] ?? '') === $scopeType
            ));
        }
        if ($scopeType !== null) {
            return Proposal::all(null, $scopeType);
        }
        if (Auth::hasRole('dean') && ($user['college_id'] ?? null)) {
            return Proposal::forCollege((int) $user['college_id']);
        }

        return Proposal::forUser((int) ($user['id'] ?? 0));
    }

    private function allowObrMatrixAccess(): bool
    {
        if (MonitoringRoles::canAccessObrMatrix()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function isStandaloneProposalForm(array $proposal): bool
    {
        return \App\Support\ResearchRequestForms::typeOf($proposal) !== null
            || proposal_is_quarterly_researches_report($proposal)
            || proposal_is_wpu_funded_extension($proposal)
            || proposal_is_accomplishment_report($proposal)
            || proposal_is_technical_advisory_ar($proposal)
            || proposal_is_outreach_activities_ar($proposal)
            || proposal_is_ebalwasyon_ng_gawain($proposal)
            || proposal_is_attendance_sheet($proposal)
            || proposal_is_research_application($proposal);
    }

    /** @param array<string, mixed> $data */
    private function isStandardResearchProposalInput(array $data): bool
    {
        if (($data['project_type'] ?? '') === 'extension') {
            return false;
        }

        $summary = $data['summary'] ?? '';
        if (!is_string($summary) || trim($summary) === '') {
            return true;
        }

        $decoded = json_decode($summary, true);
        if (!is_array($decoded)) {
            return true;
        }

        if (in_array((string) ($decoded['form_type'] ?? ''), ['wpu_funded_extension', 'extension_program_proposal'], true)) {
            return false;
        }

        return array_key_exists('applicant_last_name', $decoded)
            || array_key_exists('abstract', $decoded);
    }

    /** @param array<string, mixed> $user */
    /**
     * View data that starts a new ESO quarterly report from Conducted Activities: the activities dated in the
     * chosen reporting period (?period=, else the locked or current one).
     *
     * @param array<string, mixed>|null $user
     * @return array<string, mixed>
     */
    /**
     * Quarterly reports list the Conducted Activities (ESO) or Research Accomplishments (RDO) of the reporting
     * period. The rows are never typed in: on save they are rebuilt from those records, replacing whatever the
     * form posted, so a report always matches the entries it was generated from.
     */
    private function useGeneratedReportRows(string $formType, ?array $existingProposal = null): void
    {
        $definition = \App\Support\ExtensionActivityTypes::get($formType);
        if ($definition === null) {
            return;
        }
        $period = (string) ($this->quarterlyReportingFromRequest($existingProposal, $formType)['reporting_period'] ?? '');
        if (!\App\Support\QuarterlyReporting::isPeriodKeyValid($period)) {
            return;
        }
        $scope = \App\Controllers\ExtensionActivityController::reportScope(
            Auth::user() ?? [],
            \App\Support\ExtensionActivityTypes::officeOf($formType)
        );
        $generated = \App\Models\ExtensionActivity::reportSummary($formType, $period, $scope)['summary'];
        foreach ($definition['sections'] as $section) {
            self::writePath($_POST, $section['path'], self::readPath($generated, $section['path']));
        }
    }

    /** A saved report with its rows replaced by the current generated rows (for the edit page). */
    private static function summaryWithGeneratedRows(string $savedSummary, ?string $generatedSummary, string $formType): string
    {
        $saved = json_decode($savedSummary, true);
        $generated = $generatedSummary !== null ? json_decode($generatedSummary, true) : null;
        $definition = \App\Support\ExtensionActivityTypes::get($formType);
        if (!is_array($saved) || !is_array($generated) || $definition === null) {
            return $savedSummary;
        }
        foreach ($definition['sections'] as $section) {
            self::writePath($saved, $section['path'], self::readPath($generated, $section['path']));
        }
        foreach (['reporting_period', 'report_as_of'] as $key) {
            if (($generated[$key] ?? '') !== '') {
                $saved[$key] = $generated[$key];
            }
        }

        return json_encode($saved, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: $savedSummary;
    }

    /** @param list<string> $path */
    private static function readPath(array $data, array $path): array
    {
        foreach ($path as $key) {
            $data = is_array($data[$key] ?? null) ? $data[$key] : [];
        }

        return $data;
    }

    /** @param list<string> $path */
    private static function writePath(array &$data, array $path, array $value): void
    {
        $ref = &$data;
        foreach ($path as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
        unset($ref);
    }
    private function conductedActivitiesPrefill(string $formType, ?array $user, string $savedPeriod = ''): array
    {
        $period = '';
        if (\App\Support\FacultyFormDeadlines::locksFacultyReportingPeriod($formType)) {
            $period = \App\Support\QuarterlyReporting::facultyLockedPeriod([], $formType)['reporting_period'] ?? '';
        }
        if ($period === '') {
            $requested = trim((string) ($_GET['period'] ?? ''));
            $period = \App\Support\QuarterlyReporting::isPeriodKeyValid($requested)
                ? $requested
                : (\App\Support\QuarterlyReporting::isPeriodKeyValid($savedPeriod)
                    ? $savedPeriod
                    : (\App\Support\QuarterlyReporting::currentPeriodKey() ?? ''));
        }
        if ($period === '') {
            return [];
        }

        $scope = \App\Controllers\ExtensionActivityController::reportScope(
            $user ?? [],
            \App\Support\ExtensionActivityTypes::officeOf($formType)
        );
        $generated = \App\Models\ExtensionActivity::reportSummary($formType, $period, $scope);

        return [
            'prefillSummary' => json_encode($generated['summary'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null,
            'conductedActivitiesCount' => $generated['count'],
            'conductedActivitiesPeriod' => $period,
            'conductedActivitiesSlug' => (string) (\App\Support\ExtensionActivityTypes::get($formType)['slug'] ?? ''),
            'conductedActivitiesCollegeScope' => isset($scope['college_id']),
        ];
    }

    private function collegeNameForUser(array $user): string
    {
        $collegeId = (int) ($user['college_id'] ?? 0);
        if ($collegeId === 0) {
            return '';
        }

        foreach (College::all() as $college) {
            if ((int) ($college['id'] ?? 0) === $collegeId) {
                return (string) ($college['name'] ?? '');
            }
        }

        return '';
    }

    /** @return array<string, string> */
    private function requiredFileCategories(): array
    {
        return [
            'completed_researches' => 'WPU-QSF-RIDE-RDO Completed Researches',
            'ongoing_researches' => 'WPU-QSF-RIDE-RDO Ongoing Researches',
            'research_output_published' => 'WPU-QSF-RIDE-RDO Research Output Published',
            'research_output_presented' => 'WPU-QSF-RIDE-RDO Research Output Presented',
            'commercialized' => 'WPU-QSF-RIDE-RDO Commercialized',
            'resulted_in_extension' => 'WPU-QSF-RIDE-RDO Resulted in Extension',
            'journal_citation' => 'WPU-QSF-RIDE-RDO Journal Citation',
            'book_citation' => 'WPU-QSF-RIDE-RDO Book Citation',
            'inventions_um_copyrights' => 'WPU-QSF-RIDE-RDO Inventions, UM, Copyrights',
            'linkages' => 'WPU-QSF-RIDE-RDO Linkages',
            'consolidated_linkages' => 'WPU-QSF-RIDE-RDO Consolidated Linkages',
            'consolidated_completed_researches' => 'WPU-QSF-RIDE-RDO Consolidated Completed Researches',
            'consolidated_ongoing_researches' => 'WPU-QSF-RIDE-RDO Consolidated Ongoing Researches',
            'consolidated_research_output_published' => 'WPU-QSF-RIDE-RDO Consolidated Research Output Published',
            'consolidated_research_output_presented' => 'WPU-QSF-RIDE-RDO Consolidated Research Output Presented',
            'consolidated_commercialized' => 'WPU-QSF-RIDE-RDO Consolidated Commercialized',
            'consolidated_resulted_in_extension' => 'WPU-QSF-RIDE-RDO Consolidated Resulted in Extension',
            'consolidated_journal_citation' => 'WPU-QSF-RIDE-RDO Consolidated Journal Citation',
            'consolidated_book_citation' => 'WPU-QSF-RIDE-RDO Consolidated Book Citation',
            'consolidated_inventions_um_copyrights' => 'WPU-QSF-RIDE-RDO Consolidated Inventions, UM, Copyrights',
            'progress_report' => 'WPU-QSF-RIDE-RDO Progress Report',
            'terminal_report' => 'WPU-QSF-RIDE-RDO Terminal Report',
            'terminal_report_assessment_form' => 'WPU-QSF-RIDE-RDO Terminal Report Assessment Form',
            'obr_matrix' => 'WPU-QSF-RIDE-RDO OBR Matrix',
        ];
    }

    /** @return array<string, string> */
    private function trainingsConductedRequiredFileCategories(): array
    {
        return [
            'trainings_tna_report' => '1.1 TNA Report (needs assessment, brief/proposal, assessment results)',
            'trainings_modules' => '1.2 Training Modules',
            'trainings_accomplishment_report' => '1.3 Training Accomplishment Report (proceedings, attendance, program, surveys, evaluation)',
        ];
    }

    /** @return array<string, string> */
    private function technicalAdvisoryRequiredFileCategories(): array
    {
        return [
            'technical_advisory_accomplishment_report' => 'Accomplishment Report with photos',
            'technical_advisory_moa_mou' => 'Notarized MOA/MOU if applicable (counted as active linkage when provided)',
        ];
    }

    /** @return array<string, string> */
    private function extensionLinkagesRequiredFileCategories(): array
    {
        return [
            'extension_linkages_moa_mou' => 'MOA/MOU',
            'extension_linkages_reports_etr' => 'Extension reports / ETR',
        ];
    }

    /** @return array<string, string> */
    private function outreachActivitiesRequiredFileCategories(): array
    {
        return [
            'outreach_activities_accomplishment_report' => 'Accomplishment Report with photos',
        ];
    }

    /** @return array<string, string> */
    private function technologyAdoptionRequiredFileCategories(): array
    {
        return [
            'technology_adoption_accomplishment_report' => 'Accomplishment Report with photos',
            'technology_adoption_abstract' => 'Abstract of technology demonstrated',
            'technology_adoption_moa_mou' => 'Notarized MOA/MOU or its equivalent as proof of adoption',
            'technology_adoption_commercial_proof' => 'Proof of utilization for commercial purposes',
        ];
    }

    /** @return array<string, string> */
    private function accomplishmentReportRequiredFileCategories(): array
    {
        return [
            'ar_results_table' => 'Results table (Table 3.1)',
            'ar_results_figure' => 'Results figure (Fig. 3.1)',
            'ar_activity_pictures' => 'Group picture, training team picture, and pictures during activities',
            'ar_request_letter' => 'Approved request letter',
            'ar_travel_order' => 'Travel order',
            'ar_certificates' => 'Certificates (participant participation, appreciation from client/partner, recognition for training team)',
            'ar_attendance_sheet' => 'Attendance sheet',
            'ar_program' => 'Program',
            'ar_iec_materials' => 'IEC materials',
            'ar_ebalwasyon' => 'Ebalwasyon ng Gawain (1 sample only)',
        ];
    }

    /** @return array<string, string> */
    private function technicalAdvisoryArRequiredFileCategories(): array
    {
        return [
            'ta_ar_documentation' => 'Documentation',
            'ta_ar_moa' => 'Attached MOA (for active linkages)',
        ];
    }

    /** @return array<string, string> */
    private function outreachActivitiesArRequiredFileCategories(): array
    {
        return [
            'oa_ar_documentation' => 'Documentation',
            'oa_ar_moa' => 'Attached MOA (for active linkages)',
            'oa_ar_attendance' => 'Attached attendance sheet',
        ];
    }

    /** @return list<string> */
    private function storeTrainingsConductedSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->trainingsConductedRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeTechnicalAdvisorySupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->technicalAdvisoryRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeExtensionLinkagesSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->extensionLinkagesRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeOutreachActivitiesSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->outreachActivitiesRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeTechnologyAdoptionSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->technologyAdoptionRequiredFileCategories()
        );
    }

    /** @param array<string, string>|null $categories */
    /** @return array<string, list<array<string, mixed>>> */
    private function requiredDocumentsByKey(int $proposalId, ?array $categories = null): array
    {
        $documentsByKey = [];
        $knownKeys = array_keys($categories ?? $this->requiredFileCategories());

        foreach (Document::forProposal($proposalId) as $document) {
            $category = (string) ($document['category'] ?? '');
            if (!str_starts_with($category, 'required_file:')) {
                continue;
            }

            $key = substr($category, strlen('required_file:'));
            if (!in_array($key, $knownKeys, true)) {
                continue;
            }

            if (!isset($documentsByKey[$key])) {
                $documentsByKey[$key] = [];
            }
            $documentsByKey[$key][] = $document;
        }

        return $documentsByKey;
    }

    /** @param array<string, string>|null $categories */
    /** @return list<string> */
    private function storeRequiredFiles(
        int $proposalId,
        int $userId,
        bool $mirrorToDocumentRepository = false,
        ?array $categories = null
    ): array {
        $errors = [];
        $files = $_FILES['required_files'] ?? null;
        if (!is_array($files)) {
            return $errors;
        }

        foreach (($categories ?? $this->requiredFileCategories()) as $key => $label) {
            $file = $this->uploadedFileByKey($files, $key);
            $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($errorCode === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            try {
                $requiredFileDocumentId = Document::store($proposalId, $userId, $file, 'required_file:' . $key);
                if ($mirrorToDocumentRepository) {
                    Document::copyToCategory($requiredFileDocumentId, $key);
                }
            } catch (\Throwable $e) {
                $errors[] = $label . ': ' . $e->getMessage();
            }
        }

        return $errors;
    }

    /** @return list<string> */
    private function missingRequiredFileLabels(int $proposalId): array
    {
        $documentsByKey = $this->requiredDocumentsByKey($proposalId);
        $missing = [];

        foreach ($this->requiredFileCategories() as $key => $label) {
            $documents = $documentsByKey[$key] ?? [];
            if (!is_array($documents) || $documents === []) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /** @param array<string, mixed> $files */
    private function uploadedFileByKey(array $files, string $key): array
    {
        return [
            'name' => $files['name'][$key] ?? '',
            'type' => $files['type'][$key] ?? '',
            'tmp_name' => $files['tmp_name'][$key] ?? '',
            'error' => $files['error'][$key] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$key] ?? 0,
        ];
    }
}
