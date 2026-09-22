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
        $proposals = array_values(array_filter(
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
        view('proposals.index', [
            'proposals' => $proposals,
            'currentUserId' => (int) $user['id'],
        ]);
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
                'pageTitle' => 'New Extension Program/Project Proposal — RIDE IMS',
                'pageHeading' => 'Research Extension',
                'pageSubtitle' => 'Complete the Office of Extension Services program/project proposal (WPU-QSF-RIDE-ESO-03).',
                'facultyTeamOptions' => $this->facultyTeamOptions(),
                'collegeExtensionProgramOptions' => $this->collegeExtensionProgramOptions(),
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

        if (proposal_is_accomplishment_report($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->accomplishmentReportRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->accomplishmentReportRequiredFileCategories()),
            ]);
            return;
        }

        if (proposal_is_technical_advisory_ar($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->technicalAdvisoryArRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->technicalAdvisoryArRequiredFileCategories()),
            ]);
            return;
        }

        if (proposal_is_outreach_activities_ar($proposal)) {
            view('proposals.required-files-form', [
                'proposal' => $proposal,
                'requiredFileList' => $this->outreachActivitiesArRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey($id, $this->outreachActivitiesArRequiredFileCategories()),
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

        if (proposal_is_accomplishment_report($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->accomplishmentReportRequiredFileCategories()
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

        if (proposal_is_technical_advisory_ar($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->technicalAdvisoryArRequiredFileCategories()
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

        if (proposal_is_outreach_activities_ar($proposal)) {
            $uploadErrors = $this->storeRequiredFiles(
                $id,
                (int) Auth::user()['id'],
                false,
                $this->outreachActivitiesArRequiredFileCategories()
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

    public function createManuscript(): void
    {
        if (!$this->allowManuscriptAccess()) {
            return;
        }

        view('proposals.manuscript-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'lockedProjectType' => proposal_nav_scope(),
        ]);
    }

    public function storeManuscript(): void
    {
        if (!$this->allowManuscriptAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/manuscript' . proposal_nav_redirect_suffix());
        }

        $user = Auth::user();
        $data = $this->validatedManuscriptInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0 || $data['title'] === '') {
            set_flash('error', 'College and title are required.');
            redirect('proposals/create/manuscript' . proposal_nav_redirect_suffix());
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Manuscript proposal saved as draft.');
        redirect('proposals/' . $id);
    }

    public function updateManuscript(int $id): void
    {
        if (!$this->allowManuscriptAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_manuscript($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedManuscriptInput());
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Manuscript proposal updated.');
        redirect('proposals/' . $id);
    }

    public function createCompletedResearches(): void
    {
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/create/consolidated-completed-researches');
        }

        if (!$this->allowCompletedResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.completed-researches-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/create/consolidated-ongoing-researches');
        }

        if (!$this->allowOngoingResearchesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.ongoing-researches-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/create/consolidated-research-output-published');
        }

        if (!$this->allowResearchOutputPublishedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.research-output-published-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            redirect('proposals/create/consolidated-research-output-presented');
        }

        if (!$this->allowResearchOutputPresentedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.research-output-presented-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('commercialized');
        }

        if (!$this->allowCommercializedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.commercialized-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('resulted_in_extension');
        }

        if (!$this->allowResultedInExtensionAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.resulted-in-extension-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('journal_citation');
        }

        if (!$this->allowJournalCitationAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.journal-citation-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
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
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('book_citation');
        }

        if (!$this->allowBookCitationAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.book-citation-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function createInventionsUmCopyrights(): void
    {
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('inventions_um_copyrights');
        }

        if (!$this->allowInventionsUmCopyrightsAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.inventions-um-copyrights-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function createLinkages(): void
    {
        if (MonitoringRoles::isCoordinatorResearch()) {
            $this->redirectFacultyToConsolidatedCreate('linkages');
        }

        if (!$this->allowLinkagesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.linkages-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
        ]);
    }

    public function createTrainingsConducted(): void
    {
        if (!$this->allowTrainingsConductedAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.trainings-conducted-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createTechnicalAdvisory(): void
    {
        if (!$this->allowTechnicalAdvisoryAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.technical-advisory-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createExtensionLinkages(): void
    {
        if (!$this->allowExtensionLinkagesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.extension-linkages-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createOutreachActivities(): void
    {
        if (!$this->allowOutreachActivitiesAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.outreach-activities-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createTechnologyAdoption(): void
    {
        if (!$this->allowTechnologyAdoptionAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.technology-adoption-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createAccomplishmentReport(): void
    {
        if (!$this->allowAccomplishmentReportAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.accomplishment-report-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->accomplishmentReportRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createTechnicalAdvisoryAr(): void
    {
        if (!$this->allowTechnicalAdvisoryArAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.technical-advisory-ar-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->technicalAdvisoryArRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createOutreachActivitiesAr(): void
    {
        if (!$this->allowOutreachActivitiesArAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.outreach-activities-ar-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'requiredFileList' => $this->outreachActivitiesArRequiredFileCategories(),
            'requiredDocuments' => [],
        ]);
    }

    public function createEbalwasyonNgGawain(): void
    {
        if (!$this->allowEbalwasyonNgGawainAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.ebalwasyon-ng-gawain-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'pageTitle' => 'New Ebalwasyon ng Gawain — RIDE IMS',
            'pageHeading' => 'Ebalwasyon ng Gawain',
            'pageSubtitle' => 'Complete the activity evaluation (WPU-QSF-RIDE-ESO-16).',
        ]);
    }

    public function createAttendanceSheet(): void
    {
        if (!$this->allowAttendanceSheetAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.attendance-sheet-form', [
            'proposal' => null,
            'colleges' => College::all(),
            'collegeName' => $this->collegeNameForUser($user),
            'pageTitle' => 'New Attendance Sheet — RIDE IMS',
            'pageHeading' => 'Attendance Sheet',
            'pageSubtitle' => 'Complete the attendance form (WPU-QSF-RIDE-ESO-17).',
        ]);
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

    public function createObrMatrix(): void
    {
        if (!$this->allowObrMatrixAccess()) {
            return;
        }

        $user = Auth::user();
        view('proposals.obr-matrix-form', [
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

    public function storeAccomplishmentReport(): void
    {
        if (!$this->allowAccomplishmentReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/accomplishment-report');
        }

        $user = Auth::user();
        $data = $this->validatedAccomplishmentReportInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/accomplishment-report');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeAccomplishmentReportSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Accomplishment Report saved as draft.');
        } else {
            set_flash(
                'success',
                'Accomplishment Report saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeTechnicalAdvisoryAr(): void
    {
        if (!$this->allowTechnicalAdvisoryArAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/technical-advisory-ar');
        }

        $user = Auth::user();
        $data = $this->validatedTechnicalAdvisoryArInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/technical-advisory-ar');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeTechnicalAdvisoryArSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Technical Advisory AR saved as draft.');
        } else {
            set_flash(
                'success',
                'Technical Advisory AR saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeOutreachActivitiesAr(): void
    {
        if (!$this->allowOutreachActivitiesArAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/outreach-activities-ar');
        }

        $user = Auth::user();
        $data = $this->validatedOutreachActivitiesArInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/outreach-activities-ar');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        $uploadErrors = $this->storeOutreachActivitiesArSupportingDocuments($id, (int) $user['id']);
        if ($uploadErrors === []) {
            set_flash('success', 'Outreach Activities AR saved as draft.');
        } else {
            set_flash(
                'success',
                'Outreach Activities AR saved as draft. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function storeEbalwasyonNgGawain(): void
    {
        if (!$this->allowEbalwasyonNgGawainAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/ebalwasyon-ng-gawain');
        }

        $user = Auth::user();
        $data = $this->validatedEbalwasyonNgGawainInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/ebalwasyon-ng-gawain');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Ebalwasyon ng Gawain saved as draft.');
        redirect('proposals/' . $id . '/edit');
    }

    public function storeAttendanceSheet(): void
    {
        if (!$this->allowAttendanceSheetAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/attendance-sheet');
        }

        $user = Auth::user();
        $data = $this->validatedAttendanceSheetInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/attendance-sheet');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'Attendance sheet saved as draft.');
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

    public function storeObrMatrix(): void
    {
        if (!$this->allowObrMatrixAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/create/obr-matrix');
        }

        $user = Auth::user();
        $data = $this->validatedObrMatrixInput();
        $data['user_id'] = (int) $user['id'];
        $data['college_id'] = (int) ($user['college_id'] ?? $_POST['college_id'] ?? 0);
        $data['campus_id'] = (int) ($user['campus_id'] ?? $_POST['campus_id'] ?? 0) ?: null;

        if ($data['college_id'] === 0) {
            set_flash('error', 'College is required.');
            redirect('proposals/create/obr-matrix');
        }

        $id = Proposal::create($data);
        AuditLog::record('proposal', $id, 'created');
        set_flash('success', 'OBR Matrix saved as draft.');
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
        if (!$this->allowTrainingsConductedAccess()) {
            return;
        }

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
        if (!$this->allowTechnicalAdvisoryAccess()) {
            return;
        }

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
        if (!$this->allowExtensionLinkagesAccess()) {
            return;
        }

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
        if (!$this->allowOutreachActivitiesAccess()) {
            return;
        }

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
        if (!$this->allowTechnologyAdoptionAccess()) {
            return;
        }

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

    public function updateAccomplishmentReport(int $id): void
    {
        if (!$this->allowAccomplishmentReportAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_accomplishment_report($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedAccomplishmentReportInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeAccomplishmentReportSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Accomplishment Report updated.');
        } else {
            set_flash(
                'success',
                'Accomplishment Report updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateTechnicalAdvisoryAr(int $id): void
    {
        if (!$this->allowTechnicalAdvisoryArAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_technical_advisory_ar($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedTechnicalAdvisoryArInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeTechnicalAdvisoryArSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Technical Advisory AR updated.');
        } else {
            set_flash(
                'success',
                'Technical Advisory AR updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateOutreachActivitiesAr(int $id): void
    {
        if (!$this->allowOutreachActivitiesArAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_outreach_activities_ar($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedOutreachActivitiesArInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        $uploadErrors = $this->storeOutreachActivitiesArSupportingDocuments($id, (int) Auth::user()['id']);
        if ($uploadErrors !== []) {
            AuditLog::record('proposal', $id, 'supporting_documents_updated');
        }
        if ($uploadErrors === []) {
            set_flash('success', 'Outreach Activities AR updated.');
        } else {
            set_flash(
                'success',
                'Outreach Activities AR updated. Some supporting documents were not uploaded: '
                . implode(' ', $uploadErrors)
            );
        }
        redirect('proposals/' . $id . '/edit');
    }

    public function updateEbalwasyonNgGawain(int $id): void
    {
        if (!$this->allowEbalwasyonNgGawainAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_ebalwasyon_ng_gawain($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedEbalwasyonNgGawainInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Ebalwasyon ng Gawain updated.');
        redirect('proposals/' . $id . '/edit');
    }

    public function updateAttendanceSheet(int $id): void
    {
        if (!$this->allowAttendanceSheetAccess()) {
            return;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session.');
            redirect('proposals/' . $id . '/edit');
        }

        $proposal = $this->authorizeView($id);
        if (!$proposal || !$this->canEdit($proposal) || !proposal_is_attendance_sheet($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        Proposal::update($id, $this->validatedAttendanceSheetInput($proposal));
        AuditLog::record('proposal', $id, 'updated');
        set_flash('success', 'Attendance sheet updated.');
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
        if (proposal_is_manuscript($proposal)) {
            $this->showManuscript($id, $proposal);
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
            'pageTitle' => ((string) ($proposal['title'] ?? 'Extension Program/Project Proposal')) . ' — RIDE IMS',
            'pageHeading' => (string) ($proposal['title'] ?? 'Extension Program/Project Proposal'),
            'pageSubtitle' => 'Review the submitted Extension Program/Project Proposal (WPU-QSF-RIDE-ESO-03).',
        ]);
    }

    private function showManuscript(int $id, array $proposal): void
    {
        view('proposals.manuscript-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
        ]);
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
            'canEdit' => $this->canEdit($proposal),
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
            'canEdit' => $this->canEdit($proposal),
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
            'canEdit' => $this->canEdit($proposal),
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
            'pageTitle' => ((string) ($proposal['title'] ?? 'Ebalwasyon ng Gawain')) . ' — RIDE IMS',
            'pageHeading' => (string) ($proposal['title'] ?? 'Ebalwasyon ng Gawain'),
            'pageSubtitle' => 'Review the submitted activity evaluation (WPU-QSF-RIDE-ESO-16).',
        ]);
    }

    private function showAttendanceSheet(int $id, array $proposal): void
    {
        view('proposals.attendance-sheet-show', [
            'proposal' => $proposal,
            'comments' => Proposal::comments($id),
            'canEdit' => $this->canEdit($proposal),
            'canApprove' => $this->canApprove($proposal),
            'approveLabel' => MonitoringRoles::actionLabel((string) ($proposal['current_step'] ?? '')),
            'stepLabel' => MonitoringRoles::stepLabel((string) ($proposal['current_step'] ?? '')),
            'pageTitle' => ((string) ($proposal['title'] ?? 'Attendance Sheet')) . ' — RIDE IMS',
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
        if (!$proposal || !$this->canEdit($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        if (proposal_is_manuscript($proposal)) {
            view('proposals.manuscript-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
            ]);
            return;
        }

        if (proposal_is_completed_researches($proposal)) {
            view('proposals.completed-researches-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_ongoing_researches($proposal)) {
            view('proposals.ongoing-researches-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_research_output_published($proposal)) {
            view('proposals.research-output-published-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_research_output_presented($proposal)) {
            view('proposals.research-output-presented-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if ($this->tryEditConsolidatedResearchForm($id, $proposal)) {
            return;
        }

        if (proposal_is_commercialized($proposal)) {
            view('proposals.commercialized-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_resulted_in_extension($proposal)) {
            view('proposals.resulted-in-extension-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_journal_citation($proposal)) {
            view('proposals.journal-citation-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_book_citation($proposal)) {
            view('proposals.book-citation-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_inventions_um_copyrights($proposal)) {
            view('proposals.inventions-um-copyrights-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_linkages($proposal)) {
            view('proposals.linkages-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
            ]);
            return;
        }

        if (proposal_is_trainings_conducted($proposal)) {
            view('proposals.trainings-conducted-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->trainingsConductedRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->trainingsConductedRequiredFileCategories()
                ),
            ]);
            return;
        }

        if (proposal_is_technical_advisory($proposal)) {
            view('proposals.technical-advisory-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->technicalAdvisoryRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->technicalAdvisoryRequiredFileCategories()
                ),
            ]);
            return;
        }

        if (proposal_is_extension_linkages($proposal)) {
            view('proposals.extension-linkages-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->extensionLinkagesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->extensionLinkagesRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_outreach_activities($proposal)) {
            view('proposals.outreach-activities-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->outreachActivitiesRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->outreachActivitiesRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_technology_adoption($proposal)) {
            view('proposals.technology-adoption-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->technologyAdoptionRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->technologyAdoptionRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_accomplishment_report($proposal)) {
            view('proposals.accomplishment-report-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->accomplishmentReportRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->accomplishmentReportRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_technical_advisory_ar($proposal)) {
            view('proposals.technical-advisory-ar-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->technicalAdvisoryArRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->technicalAdvisoryArRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_outreach_activities_ar($proposal)) {
            view('proposals.outreach-activities-ar-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'requiredFileList' => $this->outreachActivitiesArRequiredFileCategories(),
                'requiredDocuments' => $this->requiredDocumentsByKey(
                    (int) $proposal['id'],
                    $this->outreachActivitiesArRequiredFileCategories()
                ),
            ]);
            return;
        }
        if (proposal_is_ebalwasyon_ng_gawain($proposal)) {
            view('proposals.ebalwasyon-ng-gawain-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'pageTitle' => 'Edit Ebalwasyon ng Gawain — RIDE IMS',
                'pageHeading' => 'Ebalwasyon ng Gawain',
                'pageSubtitle' => 'Update the activity evaluation before saving or resubmitting.',
            ]);
            return;
        }
        if (proposal_is_attendance_sheet($proposal)) {
            view('proposals.attendance-sheet-form', [
                'proposal' => $proposal,
                'colleges' => College::all(),
                'collegeName' => (string) ($proposal['college_name'] ?? ''),
                'pageTitle' => 'Edit Attendance Sheet — RIDE IMS',
                'pageHeading' => 'Attendance Sheet',
                'pageSubtitle' => 'Update the attendance form before saving or resubmitting.',
            ]);
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
                'pageTitle' => 'Edit Extension Program/Project Proposal — RIDE IMS',
                'pageHeading' => 'Research Extension',
                'pageSubtitle' => 'Update your Extension Program/Project Proposal before saving or resubmitting.',
                'facultyTeamOptions' => $this->facultyTeamOptions(),
                'collegeExtensionProgramOptions' => $this->collegeExtensionProgramOptions(),
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
        if (!$proposal || !$this->canEdit($proposal)) {
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

        if (!Auth::hasRole('faculty')) {
            http_response_code(403);
            view('errors.403');
            return;
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

        $structuredSummary = [
            'version' => 1,
            'applicant_last_name' => trim($_POST['applicant_last_name'] ?? ''),
            'applicant_first_name' => trim($_POST['applicant_first_name'] ?? ''),
            'applicant_middle_name' => trim($_POST['applicant_middle_name'] ?? ''),
            'applicant_title_prefix' => trim($_POST['applicant_title_prefix'] ?? ''),
            'applicant_sex' => trim($_POST['applicant_sex'] ?? ''),
            'applicant_position' => trim($_POST['applicant_position'] ?? ''),
            'applicant_email' => trim($_POST['applicant_email'] ?? ''),
            'applicant_contact_number' => trim($_POST['applicant_contact_number'] ?? ''),
            'applicant_college_department' => trim($_POST['applicant_college_department'] ?? ''),
            'applicant_program' => trim($_POST['applicant_program'] ?? ''),
            'applicant_campus' => trim($_POST['applicant_campus'] ?? ''),
            'applicant_google_scholar_link' => trim($_POST['applicant_google_scholar_link'] ?? ''),
            'applicant_researchgate_link' => trim($_POST['applicant_researchgate_link'] ?? ''),
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
        $structuredSummary = [
            'form_type' => 'extension_program_proposal',
            'version' => 2,
            'college_extension_program' => trim($_POST['college_extension_program'] ?? ''),
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
            'proponent_name' => trim($_POST['proponent_name'] ?? ''),
            'proponent_date' => trim($_POST['proponent_date'] ?? ''),
            'dean_name' => trim($_POST['dean_name'] ?? ''),
            'dean_date' => trim($_POST['dean_date'] ?? ''),
            'extension_admin_name' => trim($_POST['extension_admin_name'] ?? ''),
            'extension_admin_date' => trim($_POST['extension_admin_date'] ?? ''),
        ];

        return [
            'title' => trim($_POST['title'] ?? ''),
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => 'extension',
            'funding_source' => $sourceOfFund,
            'risk_level' => 'low',
            'ethics_required' => false,
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

            $items = $this->validatedKeyedRows($group['items'] ?? [], [
                'item_no',
                'particulars',
                'qty',
                'unit',
                'cost_unit',
                'total_cost',
            ], 30);

            if ($items === []) {
                continue;
            }

            $out[] = [
                'label' => $label,
                'items' => $items,
            ];
        }

        return $out;
    }

    private function validatedManuscriptInput(): array
    {
        $structuredSummary = [
            'form_type' => 'manuscript',
            'version' => 1,
            'study_origin_details' => trim($_POST['study_origin_details'] ?? ''),
            'study_origin_status' => trim($_POST['study_origin_status'] ?? ''),
            'study_origin_completion_date' => trim($_POST['study_origin_completion_date'] ?? ''),
            'lead_researchers' => trim($_POST['lead_researchers'] ?? ''),
            'co_researchers' => trim($_POST['co_researchers'] ?? ''),
            'duration_writing' => trim($_POST['duration_writing'] ?? ''),
            'target_journal' => trim($_POST['target_journal'] ?? ''),
            'sdgs' => $this->validatedManuscriptOptions($_POST['sdgs'] ?? [], $this->manuscriptSdgOptions()),
            'ched_achieve' => $this->validatedManuscriptOptions($_POST['ched_achieve'] ?? [], $this->manuscriptAchieveOptions()),
            'brief_rationale' => trim($_POST['brief_rationale'] ?? ''),
            'objectives' => trim($_POST['objectives'] ?? ''),
            'general_methodology' => trim($_POST['general_methodology'] ?? ''),
            'highlights_of_results' => trim($_POST['highlights_of_results'] ?? ''),
        ];

        return [
            'title' => trim($_POST['title'] ?? ''),
            'summary' => json_encode($structuredSummary, JSON_UNESCAPED_SLASHES) ?: '',
            'project_type' => $this->resolvedProjectType('research'),
            'funding_source' => trim($_POST['funding_source'] ?? ''),
            'risk_level' => 'low',
            'ethics_required' => false,
        ];
    }

    private function resolvedProjectType(string $fallback = 'research'): string
    {
        $locked = proposal_nav_scope();
        if ($locked !== null) {
            return $locked;
        }

        $type = trim((string) ($_POST['project_type'] ?? $fallback));

        return in_array($type, ['research', 'innovation', 'development', 'extension'], true) ? $type : 'research';
    }

    /** @return list<string> */
    private function validatedManuscriptOptions(mixed $values, array $allowed): array
    {
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_intersect($allowed, array_map('strval', $values)));
    }

    /** @return list<string> */
    private function manuscriptSdgOptions(): array
    {
        return [
            'sdg_1', 'sdg_2', 'sdg_3', 'sdg_4', 'sdg_5', 'sdg_6', 'sdg_7', 'sdg_8', 'sdg_9',
            'sdg_10', 'sdg_11', 'sdg_12', 'sdg_13', 'sdg_14', 'sdg_15', 'sdg_16', 'sdg_17',
        ];
    }

    /** @return list<string> */
    private function manuscriptAchieveOptions(): array
    {
        return ['access', 'connectivity', 'human_capital', 'innovation', 'excellence', 'value', 'engagement'];
    }

    private function validatedCompletedResearchesInput(?array $existingProposal = null): array
    {
        return $this->validatedQuarterlyResearchesReportInput('completed_researches', 'Completed Researches', $existingProposal);
    }

    private function validatedOngoingResearchesInput(?array $existingProposal = null): array
    {
        return $this->validatedQuarterlyResearchesReportInput('ongoing_researches', 'Ongoing Researches', $existingProposal);
    }

    private function validatedResearchOutputPublishedInput(?array $existingProposal = null): array
    {
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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

    private function validatedAccomplishmentReportInput(?array $existingProposal = null): array
    {
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            $title = 'Accomplishment Report';
            if ($collegeName !== '') {
                $title .= ' — ' . $collegeName;
            }
        }

        $structuredSummary = [
            'form_type' => 'accomplishment_report',
            'version' => 1,
            'college_name' => $collegeName,
            'report_title' => $title,
            'authors' => trim((string) ($_POST['authors'] ?? '')),
            'keywords' => trim((string) ($_POST['keywords'] ?? '')),
            'introduction' => trim((string) ($_POST['introduction'] ?? '')),
            'methodology' => trim((string) ($_POST['methodology'] ?? '')),
            'results' => trim((string) ($_POST['results'] ?? '')),
            'table_caption' => trim((string) ($_POST['table_caption'] ?? '')),
            'figure_caption' => trim((string) ($_POST['figure_caption'] ?? '')),
            'references' => trim((string) ($_POST['references'] ?? '')),
            'acknowledgement' => trim((string) ($_POST['acknowledgement'] ?? '')),
        ];

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
    private function validatedTechnicalAdvisoryArInput(?array $existingProposal = null): array
    {
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $advisory = trim((string) ($_POST['technical_advisory_conducted'] ?? ''));
        $title = $advisory !== '' ? $advisory : 'Technical Advisory AR';
        if ($collegeName !== '' && $advisory === '') {
            $title .= ' — ' . $collegeName;
        }

        $structuredSummary = [
            'form_type' => 'technical_advisory_ar',
            'version' => 1,
            'college_name' => $collegeName,
            'technical_advisory_conducted' => $advisory,
            'client_name_type' => trim((string) ($_POST['client_name_type'] ?? '')),
            'venue' => trim((string) ($_POST['venue'] ?? '')),
            'advisory_date' => trim((string) ($_POST['advisory_date'] ?? '')),
            'report' => trim((string) ($_POST['report'] ?? '')),
            'resource_person_name' => trim((string) ($_POST['resource_person_name'] ?? '')),
        ];

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
    private function validatedOutreachActivitiesArInput(?array $existingProposal = null): array
    {
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $activityTitle = trim((string) ($_POST['outreach_activity_title'] ?? ''));
        $title = $activityTitle !== '' ? $activityTitle : 'Outreach Activities AR';
        if ($collegeName !== '' && $activityTitle === '') {
            $title .= ' — ' . $collegeName;
        }

        $beneficiaries = trim((string) ($_POST['beneficiaries_count'] ?? ''));
        if ($beneficiaries !== '' && !preg_match('/^\d+$/', $beneficiaries)) {
            $beneficiaries = '';
        }

        $structuredSummary = [
            'form_type' => 'outreach_activities_ar',
            'version' => 1,
            'college_name' => $collegeName,
            'outreach_activity_title' => $activityTitle,
            'beneficiaries_count' => $beneficiaries,
            'venue' => trim((string) ($_POST['venue'] ?? '')),
            'activity_date' => trim((string) ($_POST['activity_date'] ?? '')),
            'narrative_report' => trim((string) ($_POST['narrative_report'] ?? '')),
            'resource_person_name' => trim((string) ($_POST['resource_person_name'] ?? '')),
        ];

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
    private function validatedEbalwasyonNgGawainInput(?array $existingProposal = null): array
    {
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $activityTitle = trim((string) ($_POST['paksang_gawain'] ?? ''));
        $title = $activityTitle !== '' ? $activityTitle : 'Ebalwasyon ng Gawain';
        if ($collegeName !== '' && $activityTitle === '') {
            $title .= ' — ' . $collegeName;
        }

        $gender = trim((string) ($_POST['kasarian'] ?? ''));
        if (!in_array($gender, ['babae', 'lalaki'], true)) {
            $gender = '';
        }

        $postedRatings = $_POST['ratings'] ?? [];
        $ratings = [];
        if (is_array($postedRatings)) {
            foreach (ebalwasyon_ng_gawain_item_keys() as $key) {
                $value = trim((string) ($postedRatings[$key] ?? ''));
                if (preg_match('/^[1-5]$/', $value) === 1) {
                    $ratings[$key] = $value;
                }
            }
        }

        $average = ebalwasyon_ng_gawain_average($ratings);
        $marka = $average !== null ? number_format($average, 2, '.', '') : '';

        $structuredSummary = [
            'form_type' => 'ebalwasyon_ng_gawain',
            'version' => 1,
            'college_name' => $collegeName,
            'paksang_gawain' => $activityTitle,
            'pangalan_ng_dumalo' => trim((string) ($_POST['pangalan_ng_dumalo'] ?? '')),
            'petsa' => trim((string) ($_POST['petsa'] ?? '')),
            'kasarian' => $gender,
            'marka' => $marka,
            'ratings' => $ratings,
            'karagdagang_komento' => trim((string) ($_POST['karagdagang_komento'] ?? '')),
            'lagda' => trim((string) ($_POST['lagda'] ?? '')),
        ];

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
    private function validatedAttendanceSheetInput(?array $existingProposal = null): array
    {
        $collegeName = trim((string) ($_POST['college_name'] ?? ''));
        $activityTitle = trim((string) ($_POST['activity_title'] ?? ''));
        $title = $activityTitle !== '' ? $activityTitle : 'Attendance Sheet';
        if ($collegeName !== '' && $activityTitle === '') {
            $title .= ' — ' . $collegeName;
        }

        $structuredSummary = [
            'form_type' => 'attendance_sheet',
            'version' => 1,
            'college_name' => $collegeName,
            'activity_title' => $activityTitle,
            'venue' => trim((string) ($_POST['venue'] ?? '')),
            'activity_date' => trim((string) ($_POST['activity_date'] ?? '')),
            'time_am' => trim((string) ($_POST['time_am'] ?? '')),
            'time_pm' => trim((string) ($_POST['time_pm'] ?? '')),
            'attendees' => $this->validatedAttendanceSheetRows($_POST['attendees'] ?? []),
        ];

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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($reportAsOf !== '') {
            $title .= ' — ' . $reportAsOf;
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
            $title .= ' — ' . $collegeName;
        }
        if ($date !== '') {
            $title .= ' — ' . $date;
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
            $title .= ' — ' . $collegeName;
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
                'conference_type' => trim((string) ($row['conference_type'] ?? '')),
                'presentation_type' => trim((string) ($row['presentation_type'] ?? '')),
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

    /** @return list<array<string, string>> */
    private function validatedAttendanceSheetRows(mixed $rows): array
    {
        if (!is_array($rows)) {
            return [];
        }

        $sexOptions = array_keys(attendance_sheet_sex_options());
        $validated = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $sex = trim((string) ($row['sex'] ?? ''));
            if (!in_array($sex, $sexOptions, true)) {
                $sex = '';
            }

            $entry = [
                'name' => trim((string) ($row['name'] ?? '')),
                'position' => trim((string) ($row['position'] ?? '')),
                'sex' => $sex,
                'contact_number' => trim((string) ($row['contact_number'] ?? '')),
                'office' => trim((string) ($row['office'] ?? '')),
                'email' => trim((string) ($row['email'] ?? '')),
                'signature' => trim((string) ($row['signature'] ?? '')),
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
                'conference_type' => trim((string) ($row['conference_type'] ?? '')),
                'presentation_type' => trim((string) ($row['presentation_type'] ?? '')),
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

    /** @return list<string> */
    private function collegeExtensionProgramOptions(): array
    {
        $programs = [];
        foreach (Proposal::all(null, 'extension') as $proposal) {
            if (!proposal_is_wpu_funded_extension($proposal)) {
                continue;
            }
            $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
            $program = is_array($summary) ? trim((string) ($summary['college_extension_program'] ?? '')) : '';
            if ($program !== '') {
                $programs[$program] = $program;
            }
        }
        $programs = array_values($programs);
        sort($programs, SORT_NATURAL | SORT_FLAG_CASE);

        return $programs;
    }

    private function facultyTeamOptions(): array
    {
        $options = [];
        foreach (User::allFaculty() as $row) {
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
                'label' => implode(' · ', $labelParts) !== '' ? implode(' · ', $labelParts) : $fullName,
                'name' => $fullName,
                'college_name' => $collegeName,
                'department' => $department,
                'department_label' => $departmentLabel,
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

    private function allowManuscriptAccess(): bool
    {
        if (MonitoringRoles::canAccessManuscript()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
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

    private function allowAccomplishmentReportAccess(): bool
    {
        if (MonitoringRoles::canAccessAccomplishmentReport()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowTechnicalAdvisoryArAccess(): bool
    {
        if (MonitoringRoles::canAccessTechnicalAdvisoryAr()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowOutreachActivitiesArAccess(): bool
    {
        if (MonitoringRoles::canAccessOutreachActivitiesAr()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowEbalwasyonNgGawainAccess(): bool
    {
        if (MonitoringRoles::canAccessEbalwasyonNgGawain()) {
            return true;
        }

        http_response_code(403);
        view('errors.403');
        return false;
    }

    private function allowAttendanceSheetAccess(): bool
    {
        if (MonitoringRoles::canAccessAttendanceSheet()) {
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
        if (MonitoringRoles::isCoordinatorResearch() || MonitoringRoles::isCoordinatorExtension()) {
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
        return proposal_is_manuscript($proposal)
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

    /** @return list<string> */
    private function storeAccomplishmentReportSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->accomplishmentReportRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeTechnicalAdvisoryArSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->technicalAdvisoryArRequiredFileCategories()
        );
    }

    /** @return list<string> */
    private function storeOutreachActivitiesArSupportingDocuments(int $proposalId, int $userId): array
    {
        return $this->storeRequiredFiles(
            $proposalId,
            $userId,
            false,
            $this->outreachActivitiesArRequiredFileCategories()
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
