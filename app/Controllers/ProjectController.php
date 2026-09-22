<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Extension;
use App\Models\Innovation;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Services\ProjectAccess;
use App\Support\MonitoringRoles;

final class ProjectController
{
    public function index(): void
    {
        Notification::checkOverdueAlerts();
        $user = Auth::user();
        $scopeType = MonitoringRoles::proposalScopeType();
        if (MonitoringRoles::isVpride() || Auth::hasRole('ride_reporter')) {
            $projects = $scopeType !== null ? Proposal::ongoing(null, null, $scopeType) : Proposal::ongoing();
        } elseif (MonitoringRoles::isCoordinatorResearch() || MonitoringRoles::isCoordinatorExtension()) {
            $projects = $user['college_id'] ? Proposal::ongoing((int) $user['college_id'], null, $scopeType) : [];
        } elseif ($scopeType !== null) {
            $projects = Proposal::ongoing(null, null, $scopeType);
        } elseif (Auth::hasRole('dean') && $user['college_id']) {
            $projects = Proposal::ongoing((int) $user['college_id']);
        } else {
            $projects = Proposal::ongoing(null, (int) $user['id']);
        }
        view('projects.index', ['projects' => $projects]);
    }

    public function show(int $id): void
    {
        Notification::checkOverdueAlerts();
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::isMonitoring($proposal)) {
            if ($proposal) {
                set_flash('error', 'Project monitoring is available after approval.');
                redirect('proposals/' . $id);
            }
            return;
        }

        $tab = $_GET['tab'] ?? 'overview';
        $selectedDocumentCategory = trim((string) ($_GET['document_category'] ?? 'completed_researches'));
        if ($selectedDocumentCategory === '') {
            $selectedDocumentCategory = null;
        }
        if ((string) ($proposal['status'] ?? '') === 'ongoing') {
            Document::mirrorRequiredFilesIntoRepository($id);
        }
        view('projects.show', [
            'project' => $proposal,
            'tab' => $tab,
            'canManage' => ProjectAccess::canManage($proposal),
            'milestones' => Milestone::forProposal($id),
            'reports' => ProgressReport::forProposal($id),
            'documents' => Document::forProposal($id, $selectedDocumentCategory),
            'selectedDocumentCategory' => $selectedDocumentCategory ?? 'completed_researches',
            'innovation' => $proposal['project_type'] === 'innovation' ? [
                'summary' => Innovation::summary($id),
                'ip_disclosures' => Innovation::list('ip_disclosures', $id),
                'patents' => Innovation::list('patents', $id),
                'technology_transfers' => Innovation::list('technology_transfers', $id),
                'prototypes' => Innovation::list('prototypes', $id),
            ] : null,
            'extension' => $proposal['project_type'] === 'extension' ? [
                'summary' => Extension::summary($id),
                'beneficiaries' => Extension::list('community_beneficiaries', $id),
                'mous' => Extension::list('partner_mous', $id),
                'impacts' => Extension::list('impact_metrics', $id),
            ] : null,
        ]);
    }

    public function complete(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id);
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        Proposal::markCompleted($id);
        AuditLog::record('proposal', $id, 'completed');
        set_flash('success', 'Project marked as completed.');
        redirect('projects/' . $id);
    }

    public function storeMilestone(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=milestones');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        Milestone::create($id, [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'due_date' => $_POST['due_date'] ?? date('Y-m-d'),
        ]);
        set_flash('success', 'Milestone added.');
        redirect('projects/' . $id . '?tab=milestones');
    }

    public function completeMilestone(int $id, int $milestoneId): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=milestones');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $m = Milestone::find($milestoneId);
        if ($m && (int) $m['proposal_id'] === $id) {
            Milestone::complete($milestoneId);
        }
        redirect('projects/' . $id . '?tab=milestones');
    }

    public function createReport(int $id): void
    {
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        view('projects.report-form', ['project' => $proposal, 'report' => null, 'lines' => []]);
    }

    public function storeReport(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '/reports/create');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $reportId = ProgressReport::create($id, (int) Auth::user()['id'], $this->reportInput());
        AuditLog::record('progress_report', $reportId, 'created');
        set_flash('success', 'Progress report saved as draft.');
        redirect('projects/' . $id . '/reports/' . $reportId);
    }

    public function showReport(int $id, int $reportId): void
    {
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal) {
            return;
        }
        $report = ProgressReport::find($reportId);
        if (!$report || (int) $report['proposal_id'] !== $id) {
            http_response_code(404);
            view('errors.404');
            return;
        }
        $canManage = ProjectAccess::canManage($proposal) && $report['status'] === 'draft';
        view('projects.report-form', [
            'project' => $proposal,
            'report' => $report,
            'lines' => ProgressReport::financialLines($reportId),
            'canManage' => $canManage,
        ]);
    }

    public function updateReport(int $id, int $reportId): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '/reports/' . $reportId);
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        ProgressReport::update($reportId, $this->reportInput());
        set_flash('success', 'Report updated.');
        redirect('projects/' . $id . '/reports/' . $reportId);
    }

    public function submitReport(int $id, int $reportId): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '/reports/' . $reportId);
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        ProgressReport::submit($reportId);
        set_flash('success', 'Progress report submitted.');
        redirect('projects/' . $id . '?tab=reports');
    }

    public function uploadDocument(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=documents');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        try {
            Document::store($id, (int) Auth::user()['id'], $_FILES['document'] ?? [], $_POST['category'] ?? 'other');
            set_flash('success', 'Document uploaded.');
        } catch (\Throwable $e) {
            set_flash('error', $e->getMessage());
        }
        redirect('projects/' . $id . '?tab=documents');
    }

    public function downloadDocument(int $id, int $docId): void
    {
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal) {
            return;
        }
        $doc = Document::find($docId);
        if (!$doc || (int) $doc['proposal_id'] !== $id) {
            http_response_code(404);
            exit;
        }
        $path = Document::filePath($doc);
        if (!is_file($path)) {
            http_response_code(404);
            exit;
        }
        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
        readfile($path);
        exit;
    }

    public function deleteDocument(int $id, int $docId): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=documents');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal)) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $doc = Document::find($docId);
        if (!$doc || (int) $doc['proposal_id'] !== $id) {
            http_response_code(404);
            view('errors.404');
            return;
        }

        $path = Document::filePath($doc);
        if (is_file($path)) {
            @unlink($path);
        }
        Document::delete($docId);
        set_flash('success', 'Document deleted.');
        redirect('projects/' . $id . '?tab=documents');
    }

    public function storeInnovation(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=innovation');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal) || $proposal['project_type'] !== 'innovation') {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $type = $_POST['record_type'] ?? '';
        Innovation::create($type, $id, $_POST);
        set_flash('success', 'Innovation record added.');
        redirect('projects/' . $id . '?tab=innovation');
    }

    public function storeExtension(int $id): void
    {
        if (!verify_csrf()) {
            redirect('projects/' . $id . '?tab=extension');
        }
        $proposal = ProjectAccess::denyUnlessView($id);
        if (!$proposal || !ProjectAccess::canManage($proposal) || $proposal['project_type'] !== 'extension') {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $type = $_POST['record_type'] ?? '';
        Extension::create($type, $id, $_POST);
        set_flash('success', 'Extension record added.');
        redirect('projects/' . $id . '?tab=extension');
    }

    public function accreditationReport(): void
    {
        if (!MonitoringRoles::isVpride() && !Auth::hasRole('ride_reporter')) {
            http_response_code(403);
            view('errors.403');
            return;
        }
        $years = (int) ($_GET['years'] ?? 3);
        $selectedFormType = trim((string) ($_GET['form_type'] ?? ''));
        $selectedProjectType = trim((string) ($_GET['project_type'] ?? ''));
        $selectedStatus = trim((string) ($_GET['status'] ?? ''));
        $selectedCollege = trim((string) ($_GET['college'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $export = trim((string) ($_GET['export'] ?? ''));
        $sortBy = trim((string) ($_GET['sort_by'] ?? 'updated_at'));
        $sortDir = strtolower(trim((string) ($_GET['sort_dir'] ?? 'desc')));
        $allowedSortBy = ['updated_at', 'status', 'form_type', 'college_name'];
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'updated_at';
        }
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }
        $proposalRows = Proposal::all();
        $proposalDetails = array_map(function (array $proposal): array {
            $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
            if (!is_array($summary)) {
                $summary = [];
            }

            $formType = (string) ($summary['form_type'] ?? '');
            $reportAsOf = (string) ($summary['report_as_of'] ?? '');
            $collegeLabel = (string) ($summary['college_name'] ?? '');
            $leaderName = trim((string) (($proposal['first_name'] ?? '') . ' ' . ($proposal['last_name'] ?? '')));

            return [
                'id' => (int) ($proposal['id'] ?? 0),
                'project_code' => (string) ($proposal['project_code'] ?? ''),
                'title' => (string) ($proposal['title'] ?? ''),
                'project_type' => (string) ($proposal['project_type'] ?? ''),
                'status' => (string) ($proposal['status'] ?? ''),
                'current_step' => (string) ($proposal['current_step'] ?? ''),
                'college_name' => $collegeLabel !== '' ? $collegeLabel : (string) ($proposal['college_name'] ?? ''),
                'leader_name' => $leaderName,
                'form_type' => $formType,
                'report_as_of' => $reportAsOf,
                'updated_at' => (string) ($proposal['updated_at'] ?? ''),
            ];
        }, $proposalRows);

        $formTypes = [];
        $projectTypes = [];
        $statuses = [];
        $colleges = [];
        foreach ($proposalDetails as $item) {
            $formTypes[] = (string) ($item['form_type'] ?? '');
            $projectTypes[] = (string) ($item['project_type'] ?? '');
            $statuses[] = (string) ($item['status'] ?? '');
            $colleges[] = (string) ($item['college_name'] ?? '');
        }
        $formTypes = array_values(array_filter(array_unique($formTypes), static fn (string $value): bool => $value !== ''));
        $projectTypes = array_values(array_filter(array_unique($projectTypes), static fn (string $value): bool => $value !== ''));
        $statuses = array_values(array_filter(array_unique($statuses), static fn (string $value): bool => $value !== ''));
        $colleges = array_values(array_filter(array_unique($colleges), static fn (string $value): bool => $value !== ''));
        sort($formTypes);
        sort($projectTypes);
        sort($statuses);
        sort($colleges);

        $proposalDetails = array_values(array_filter(
            $proposalDetails,
            static function (array $item) use ($selectedFormType, $selectedProjectType, $selectedStatus, $selectedCollege): bool {
                if ($selectedFormType !== '' && (string) ($item['form_type'] ?? '') !== $selectedFormType) {
                    return false;
                }
                if ($selectedProjectType !== '' && (string) ($item['project_type'] ?? '') !== $selectedProjectType) {
                    return false;
                }
                if ($selectedStatus !== '' && (string) ($item['status'] ?? '') !== $selectedStatus) {
                    return false;
                }
                if ($selectedCollege !== '' && (string) ($item['college_name'] ?? '') !== $selectedCollege) {
                    return false;
                }

                return true;
            }
        ));

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $proposalDetails = array_values(array_filter(
                $proposalDetails,
                static function (array $item) use ($needle): bool {
                    $haystack = mb_strtolower(implode(' ', [
                        (string) ($item['project_code'] ?? ''),
                        (string) ($item['title'] ?? ''),
                        (string) ($item['leader_name'] ?? ''),
                    ]));

                    return str_contains($haystack, $needle);
                }
            ));
        }

        usort($proposalDetails, static function (array $a, array $b) use ($sortBy, $sortDir): int {
            $left = (string) ($a[$sortBy] ?? '');
            $right = (string) ($b[$sortBy] ?? '');
            $comparison = strcmp(mb_strtolower($left), mb_strtolower($right));
            if ($comparison === 0) {
                $comparison = strcmp((string) ($b['updated_at'] ?? ''), (string) ($a['updated_at'] ?? ''));
            }

            return $sortDir === 'asc' ? $comparison : -$comparison;
        });

        if ($export === 'csv') {
            $this->downloadProposalDetailsCsv($proposalDetails);
            return;
        }

        view('reports.extension-beneficiaries', [
            'rows' => Extension::beneficiaryReport($years),
            'years' => $years,
            'proposalDetails' => $proposalDetails,
            'proposalFilters' => [
                'form_type' => $selectedFormType,
                'project_type' => $selectedProjectType,
                'status' => $selectedStatus,
                'college' => $selectedCollege,
                'q' => $search,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
            'proposalFilterOptions' => [
                'form_types' => $formTypes,
                'project_types' => $projectTypes,
                'statuses' => $statuses,
                'colleges' => $colleges,
            ],
        ]);
    }

    /** @param list<array<string, mixed>> $rows */
    private function downloadProposalDetailsCsv(array $rows): void
    {
        $filename = 'vpaa-proposal-details-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            http_response_code(500);
            exit;
        }

        fputcsv($output, [
            'Project Code',
            'Form Type',
            'Project Type',
            'Title',
            'College',
            'Lead',
            'Status',
            'Current Step',
            'As Of',
            'Updated At',
            'Details URL',
        ]);

        foreach ($rows as $row) {
            fputcsv($output, [
                (string) ($row['project_code'] ?? ''),
                (string) ($row['form_type'] ?? ''),
                (string) ($row['project_type'] ?? ''),
                (string) ($row['title'] ?? ''),
                (string) ($row['college_name'] ?? ''),
                (string) ($row['leader_name'] ?? ''),
                (string) ($row['status'] ?? ''),
                (string) ($row['current_step'] ?? ''),
                (string) ($row['report_as_of'] ?? ''),
                (string) ($row['updated_at'] ?? ''),
                base_url('proposals/' . (int) ($row['id'] ?? 0)),
            ]);
        }

        fclose($output);
        exit;
    }

    private function reportInput(): array
    {
        $lines = [];
        $descriptions = $_POST['line_description'] ?? [];
        $budgeted = $_POST['line_budgeted'] ?? [];
        $spent = $_POST['line_spent'] ?? [];
        foreach ($descriptions as $i => $desc) {
            $lines[] = [
                'description' => trim((string) $desc),
                'budgeted' => (float) ($budgeted[$i] ?? 0),
                'spent' => (float) ($spent[$i] ?? 0),
            ];
        }
        return [
            'period_label' => trim($_POST['period_label'] ?? ''),
            'report_type' => $_POST['report_type'] ?? 'quarterly',
            'narrative' => trim($_POST['narrative'] ?? ''),
            'financial_summary' => trim($_POST['financial_summary'] ?? ''),
            'outputs' => trim($_POST['outputs'] ?? ''),
            'due_date' => $_POST['due_date'] ?: null,
            'financial_lines' => $lines,
        ];
    }
}
