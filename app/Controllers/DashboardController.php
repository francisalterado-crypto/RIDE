<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AdminMessage;
use App\Models\Milestone;
use App\Models\Notification;
use App\Models\PaperPresentation;
use App\Models\PublishedPaper;
use App\Models\ProgressReport;
use App\Models\Proposal;
use App\Models\ProposalCoAuthorInvitation;
use App\Models\DirectMessage;
use App\Support\DirectMessaging;
use App\Support\FacultyFormDeadlines;
use App\Support\MonitoringRoles;
use App\Support\QuarterlyReporting;

final class DashboardController
{
    public function index(): void
    {
        Notification::checkOverdueAlerts();
        $user = Auth::user();
        $isFaculty = Auth::hasRole('faculty');
        $facultyTypes = ['research', 'extension'];
        $scopeType = MonitoringRoles::proposalScopeType();
        $all = MonitoringRoles::isVpride() || Auth::hasRole('ride_reporter');
        $collegeId = $user['college_id'] ? (int) $user['college_id'] : null;
        $splitDashboard = $scopeType === null && (
            $all
            || $isFaculty
            || Auth::hasRole('dean')
        );

        // Faculty without a monitoring role get a dashboard about their own work instead of workflow analytics.
        $facultyOnly = $isFaculty && $scopeType === null && !$all && !Auth::hasRole('dean', 'ride_admin');

        // Reviewers (coordinator, dean, director, VPRIDE) get an action-focused dashboard for their step and scope.
        $reviewerContext = $facultyOnly ? null : self::reviewerContext($user);

        $dashboardSections = [];
        if ($facultyOnly || $reviewerContext !== null) {
            // Built below, after the shared notices.
        } elseif ($splitDashboard) {
            foreach (['research', 'extension'] as $projectType) {
                $dashboardSections[$projectType] = self::buildDashboardSection(
                    $projectType,
                    $isFaculty,
                    $all,
                    $collegeId,
                    (int) $user['id']
                );
            }
        } else {
            $activeScope = $scopeType ?? 'all';
            $stats = $isFaculty
                ? Proposal::statsForUser((int) $user['id'], $scopeType !== null ? [$scopeType] : $facultyTypes)
                : ($scopeType !== null
                    ? Proposal::statsForScope($scopeType, $all ? null : $collegeId)
                    : Proposal::stats());
            $proposals = self::dashboardProposals($isFaculty, $all, $collegeId, (int) $user['id'], $scopeType);
            $ongoingCount = self::dashboardOngoingCount($isFaculty, $all, $collegeId, (int) $user['id'], $scopeType);
            $monthlyActivity = $isFaculty
                ? Proposal::monthlyWorkflowActivity((int) $user['id'], null, $scopeType !== null ? [$scopeType] : $facultyTypes)
                : Proposal::monthlyWorkflowActivity(null, $all ? null : $collegeId, $scopeType !== null ? [$scopeType] : []);

            $dashboardSections[$activeScope] = [
                'key' => $activeScope,
                'title' => $scopeType === 'research'
                    ? 'Research Monitoring System'
                    : ($scopeType === 'extension'
                        ? 'Extension Monitoring System'
                        : 'Research & Extension Monitoring System'),
                'icon' => $scopeType === 'extension' ? 'fa-hands-helping' : 'fa-chart-line',
                'accent' => $scopeType === 'extension' ? '#1f7840' : '#2B5A8C',
                'stats' => $stats,
                'proposals' => array_slice($proposals, 0, 10),
                'ongoingCount' => $ongoingCount,
                'monthlyActivity' => $monthlyActivity,
            ];
        }

        $isOwnScoped = $isFaculty || MonitoringRoles::isExtensionWorker();
        $overdueMilestones = Milestone::overdueForUser(
            $all ? null : ($collegeId && !$isOwnScoped ? $collegeId : null),
            $isOwnScoped ? (int) $user['id'] : null,
            $all
        );
        $overdueReports = ProgressReport::overdue(
            $isOwnScoped ? (int) $user['id'] : null,
            $collegeId && !$all && !$isOwnScoped ? $collegeId : null
        );
        $latestAdminMessage = AdminMessage::latest();
        $globalMessage = (string) ($latestAdminMessage['message'] ?? 'Please check this page regularly for the latest dashboard announcements and updates.');

        $pendingCoAuthorInvitations = $isFaculty
            ? ProposalCoAuthorInvitation::pendingForUser((int) $user['id'])
            : [];

        $facultyReportingNotice = null;
        if ($isFaculty || MonitoringRoles::isExtensionWorker() || MonitoringRoles::isCoordinator()) {
            $currentPeriod = QuarterlyReporting::currentPeriodKey();
            $facultyReportingNotice = [
                'scheduleNotice' => FacultyFormDeadlines::scheduleNoticeText(),
                'submissionOpen' => FacultyFormDeadlines::isSubmissionOpen(),
                'currentPeriodLabel' => $currentPeriod !== null
                    ? QuarterlyReporting::periodLabel($currentPeriod)
                    : '',
            ];
        }

        if ($reviewerContext !== null) {
            view('dashboard.reviewer', array_merge(self::reviewerDashboardData($reviewerContext, (int) $user['id']), [
                'user' => $user,
                'context' => $reviewerContext,
                'globalMessage' => $globalMessage,
                'facultyReportingNotice' => $reviewerContext['role'] === 'coordinator' ? $facultyReportingNotice : null,
            ]));
            return;
        }

        if ($facultyOnly) {
            view('dashboard.faculty', array_merge(self::facultyDashboardData((int) $user['id']), [
                'user' => $user,
                'overdueMilestones' => $overdueMilestones,
                'overdueReports' => $overdueReports,
                'globalMessage' => $globalMessage,
                'pendingCoAuthorInvitations' => $pendingCoAuthorInvitations,
                'facultyReportingNotice' => $facultyReportingNotice,
            ]));
            return;
        }

        view('dashboard.index', [
            'user' => $user,
            'dashboardSections' => $dashboardSections,
            'splitDashboard' => $splitDashboard,
            'overdueMilestones' => $overdueMilestones,
            'overdueReports' => $overdueReports,
            'globalMessage' => $globalMessage,
            'pendingCoAuthorInvitations' => $pendingCoAuthorInvitations,
            'facultyReportingNotice' => $facultyReportingNotice,
        ]);
    }

    /**
     * Which review step and scope the signed-in user works on, highest role first.
     *
     * @return array{role: string, title: string, step: string, types: list<string>, collegeId: ?int}|null
     */
    private static function reviewerContext(array $user): ?array
    {
        $collegeId = (int) ($user['college_id'] ?? 0) ?: null;
        $context = static fn (string $role, string $title, string $step, array $types, ?int $college): array => [
            'role' => $role,
            'title' => $title,
            'step' => $step,
            'types' => $types,
            'collegeId' => $college,
        ];

        if (MonitoringRoles::isVpride()) {
            return $context('vpride', 'Vice President for RIDE', MonitoringRoles::VPRIDE, ['research', 'extension'], null);
        }
        if (MonitoringRoles::isDirectorResearch()) {
            return $context('director', 'RDO Director', MonitoringRoles::DIRECTOR_RESEARCH, ['research'], null);
        }
        if (MonitoringRoles::isDirectorExtension()) {
            return $context('director', 'ESO Director', MonitoringRoles::DIRECTOR_EXTENSION, ['extension'], null);
        }
        if ($collegeId === null) {
            return null;
        }
        if (MonitoringRoles::isDean()) {
            return $context('dean', 'College Dean', MonitoringRoles::DEAN, ['research', 'extension'], $collegeId);
        }
        if (MonitoringRoles::isCoordinatorResearch()) {
            return $context('coordinator', 'Research Coordinator', MonitoringRoles::COORDINATOR_RESEARCH, ['research'], $collegeId);
        }
        if (MonitoringRoles::isCoordinatorExtension()) {
            return $context('coordinator', 'Field Extensionist', MonitoringRoles::COORDINATOR_EXTENSION, ['extension'], $collegeId);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private static function reviewerDashboardData(array $context, int $userId): array
    {
        $types = $context['types'];
        $collegeId = $context['collegeId'];
        $singleType = count($types) === 1 ? $types[0] : null;
        $inScope = static fn (array $row): bool => in_array((string) ($row['project_type'] ?? ''), $types, true)
            && ($collegeId === null || (int) ($row['college_id'] ?? 0) === $collegeId);

        // Proposals waiting on this user, oldest first.
        $queue = array_values(array_filter(Proposal::pendingAtStep($context['step'], $collegeId, $singleType), $inScope));

        $stats = Proposal::monitoringStats($collegeId, $singleType);
        $byStatus = $stats['by_status'] ?? [];
        $pendingByStep = $stats['pending_by_step'] ?? [];

        // Pipeline: how many proposals sit at each review step, in workflow order.
        $stepOrder = [];
        foreach ($types as $type) {
            $stepOrder[] = MonitoringRoles::coordinatorStepForType($type);
        }
        $stepOrder[] = MonitoringRoles::DEAN;
        foreach ($types as $type) {
            $stepOrder[] = MonitoringRoles::directorStepForType($type);
        }
        $stepOrder[] = MonitoringRoles::VPRIDE;
        $shortStepLabels = [
            MonitoringRoles::COORDINATOR_RESEARCH => 'Research Coordinator',
            MonitoringRoles::COORDINATOR_EXTENSION => 'Field Extensionist',
            MonitoringRoles::DEAN => 'College Dean',
            MonitoringRoles::DIRECTOR_RESEARCH => 'RDO Director',
            MonitoringRoles::DIRECTOR_EXTENSION => 'ESO Director',
            MonitoringRoles::VPRIDE => 'VPRIDE',
        ];
        $pipeline = [];
        foreach ($stepOrder as $step) {
            $pipeline[] = [
                'label' => $shortStepLabels[$step] ?? $step,
                'count' => (int) ($pendingByStep[$step] ?? 0),
                'isMine' => $step === $context['step'],
            ];
        }

        $ongoing = array_values(array_filter(Proposal::ongoing($collegeId, null, $singleType), static fn (array $p): bool => in_array((string) $p['status'], ['ongoing', 'approved'], true)));
        $overdueMilestones = array_values(array_filter(Milestone::overdueForUser($collegeId, null, $collegeId === null), $inScope));
        $overdueReports = array_values(array_filter(ProgressReport::overdue(null, $collegeId), $inScope));

        $collegeNames = [];
        foreach (\App\Models\College::all() as $college) {
            $collegeNames[(int) $college['id']] = (string) ($college['code'] ?? '') !== '' ? (string) $college['code'] : (string) $college['name'];
        }

        // Directors and VPRIDE compare colleges; deans compare their two tracks.
        $breakdown = [];
        if ($collegeId === null) {
            // Every college is listed, so colleges with no activity stand out.
            foreach ($collegeNames as $name) {
                $breakdown[$name] = ['in_review' => 0, 'ongoing' => 0, 'completed' => 0];
            }
            foreach (Proposal::forMonitoring(null, null, $singleType) as $p) {
                if (!in_array((string) $p['project_type'], $types, true)) {
                    continue;
                }
                $key = $collegeNames[(int) $p['college_id']] ?? 'Other';
                $breakdown[$key] ??= ['in_review' => 0, 'ongoing' => 0, 'completed' => 0];
                $status = (string) $p['status'];
                if (in_array($status, ['submitted', 'under_review'], true)) {
                    $breakdown[$key]['in_review']++;
                } elseif (in_array($status, ['ongoing', 'approved'], true)) {
                    $breakdown[$key]['ongoing']++;
                } elseif ($status === 'completed') {
                    $breakdown[$key]['completed']++;
                }
            }
            ksort($breakdown);
        } elseif (count($types) > 1) {
            foreach ($types as $type) {
                $typeStatus = Proposal::monitoringStats($collegeId, $type)['by_status'] ?? [];
                $breakdown[ucfirst($type)] = [
                    'in_review' => (int) ($typeStatus['submitted'] ?? 0) + (int) ($typeStatus['under_review'] ?? 0),
                    'ongoing' => (int) ($typeStatus['ongoing'] ?? 0) + (int) ($typeStatus['approved'] ?? 0),
                    'completed' => (int) ($typeStatus['completed'] ?? 0),
                ];
            }
        }

        // Overdue items grouped by project, so reviewers can follow up with the right team.
        $followUps = [];
        foreach ($overdueMilestones as $m) {
            $id = (int) $m['proposal_id'];
            $followUps[$id] ??= ['id' => $id, 'title' => $m['project_title'], 'code' => $m['project_code'], 'college' => $collegeNames[(int) $m['college_id']] ?? '', 'milestones' => 0, 'reports' => 0];
            $followUps[$id]['milestones']++;
        }
        foreach ($overdueReports as $r) {
            $id = (int) $r['proposal_id'];
            $followUps[$id] ??= ['id' => $id, 'title' => $r['project_title'], 'code' => $r['project_code'], 'college' => $collegeNames[(int) $r['college_id']] ?? '', 'milestones' => 0, 'reports' => 0];
            $followUps[$id]['reports']++;
        }

        // Coordinators also file reports of their own.
        $myDrafts = [];
        if ($context['role'] === 'coordinator') {
            $myDrafts = array_values(array_filter(
                Proposal::forUser($userId),
                static fn (array $p): bool => (int) $p['user_id'] === $userId && in_array((string) $p['status'], ['draft', 'returned'], true)
            ));
        }

        return [
            'queue' => $queue,
            'inReviewCount' => (int) ($byStatus['submitted'] ?? 0) + (int) ($byStatus['under_review'] ?? 0),
            'returnedCount' => (int) ($byStatus['returned'] ?? 0),
            'ongoingCount' => count($ongoing),
            'completedCount' => (int) ($byStatus['completed'] ?? 0),
            'overdueCount' => count($overdueMilestones) + count($overdueReports),
            'pipeline' => $pipeline,
            'breakdown' => $breakdown,
            'followUps' => array_values($followUps),
            'myDrafts' => $myDrafts,
            'monthlyActivity' => Proposal::monthlyWorkflowActivity(null, $collegeId, $types),
            'scopeLabel' => ($collegeId !== null ? ($collegeNames[$collegeId] ?? 'Your college') : 'All colleges')
                . ', ' . ($singleType !== null ? ucfirst($singleType) : 'Research and Extension'),
        ];
    }

    /** @return array<string, mixed> */
    private static function facultyDashboardData(int $userId): array
    {
        $proposals = Proposal::forUserByTypes($userId, ['research', 'extension']);
        $byStatus = static fn (array $statuses): array => array_values(array_filter(
            $proposals,
            static fn (array $p): bool => in_array((string) ($p['status'] ?? ''), $statuses, true)
        ));
        $isLead = static fn (array $p): bool => ($p['membership'] ?? 'lead') === 'lead';

        // Latest reviewer comment on each returned proposal, so faculty see what to fix.
        $returned = array_values(array_filter($byStatus(['returned']), $isLead));
        foreach ($returned as $i => $proposal) {
            $comments = array_reverse(Proposal::comments((int) $proposal['id']));
            foreach ($comments as $comment) {
                if (($comment['action'] ?? '') === 'return') {
                    $returned[$i]['return_comment'] = (string) ($comment['comment'] ?? '');
                    $returned[$i]['return_by'] = trim(($comment['first_name'] ?? '') . ' ' . ($comment['last_name'] ?? ''));
                    break;
                }
            }
        }

        $papers = PublishedPaper::forUser($userId);
        $presentations = PaperPresentation::forUser($userId);
        $currentYear = (int) date('Y');
        $years = range($currentYear - 5, $currentYear);
        $papersPerYear = array_fill_keys($years, 0);
        $presentationsPerYear = array_fill_keys($years, 0);
        foreach ($papers as $paper) {
            $year = (int) ($paper['publication_year'] ?: substr((string) ($paper['publication_date'] ?? ''), 0, 4));
            if (isset($papersPerYear[$year])) {
                $papersPerYear[$year]++;
            }
        }
        foreach ($presentations as $presentation) {
            $year = (int) substr((string) ($presentation['presentation_date'] ?? ''), 0, 4);
            if (isset($presentationsPerYear[$year])) {
                $presentationsPerYear[$year]++;
            }
        }

        return [
            'drafts' => array_values(array_filter($byStatus(['draft']), $isLead)),
            'returnedProposals' => $returned,
            'inReview' => $byStatus(['submitted', 'under_review']),
            'ongoingProjects' => array_merge(
                Proposal::ongoing(null, $userId, 'research'),
                Proposal::ongoing(null, $userId, 'extension')
            ),
            'completedCount' => count($byStatus(['completed'])),
            'paperCount' => count($papers),
            'presentationCount' => count($presentations),
            'recentPapers' => array_slice($papers, 0, 3),
            'outputYears' => $years,
            'papersPerYear' => array_values($papersPerYear),
            'presentationsPerYear' => array_values($presentationsPerYear),
            'monthlyActivity' => Proposal::monthlyWorkflowActivity($userId, null, ['research', 'extension']),
        ];
    }

    /** @return list<array> */
    private static function dashboardProposals(
        bool $isFaculty,
        bool $all,
        ?int $collegeId,
        int $userId,
        ?string $projectType
    ): array {
        if ($isFaculty) {
            return $projectType !== null
                ? Proposal::forUserByTypes($userId, [$projectType])
                : Proposal::forUserByTypes($userId, ['research', 'extension']);
        }

        if ($all) {
            return Proposal::all(null, $projectType);
        }

        if (Auth::hasRole('coordinator_research', 'coordinator_extension', 'dean') && $collegeId) {
            $proposals = Proposal::forCollege($collegeId);
            if ($projectType !== null) {
                return array_values(array_filter(
                    $proposals,
                    static fn (array $proposal): bool => (string) ($proposal['project_type'] ?? '') === $projectType
                ));
            }

            return $proposals;
        }

        if ($projectType !== null) {
            return Proposal::all(null, $projectType);
        }

        return Proposal::forUser($userId);
    }

    private static function dashboardOngoingCount(
        bool $isFaculty,
        bool $all,
        ?int $collegeId,
        int $userId,
        ?string $projectType
    ): int {
        if ($isFaculty) {
            if ($projectType !== null) {
                return count(Proposal::ongoing(null, $userId, $projectType));
            }

            return count(Proposal::ongoing(null, $userId, 'research'))
                + count(Proposal::ongoing(null, $userId, 'extension'));
        }

        if ($projectType !== null) {
            return count(Proposal::ongoing($all ? null : $collegeId, null, $projectType));
        }

        return count(Proposal::ongoing($all ? null : $collegeId, null));
    }

    /** @return array<string, mixed> */
    private static function buildDashboardSection(
        string $projectType,
        bool $isFaculty,
        bool $all,
        ?int $collegeId,
        int $userId
    ): array {
        $stats = $isFaculty
            ? Proposal::statsForUser($userId, [$projectType])
            : Proposal::statsForScope($projectType, $all ? null : $collegeId);
        $proposals = self::dashboardProposals($isFaculty, $all, $collegeId, $userId, $projectType);
        $monthlyActivity = $isFaculty
            ? Proposal::monthlyWorkflowActivity($userId, null, [$projectType])
            : Proposal::monthlyWorkflowActivity(null, $all ? null : $collegeId, [$projectType]);

        return [
            'key' => $projectType,
            'title' => $projectType === 'research'
                ? 'Research Monitoring System'
                : 'Extension Monitoring System',
            'icon' => $projectType === 'research' ? 'fa-flask' : 'fa-hands-helping',
            'accent' => $projectType === 'research' ? '#2B5A8C' : '#1f7840',
            'stats' => $stats,
            'proposals' => array_slice($proposals, 0, 10),
            'ongoingCount' => self::dashboardOngoingCount($isFaculty, $all, $collegeId, $userId, $projectType),
            'monthlyActivity' => $monthlyActivity,
        ];
    }

    public function messages(): void
    {
        Notification::checkOverdueAlerts();
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $latestAdminMessage = AdminMessage::latest();
        $messageHistory = AdminMessage::latestList(10);
        $message = (string) ($latestAdminMessage['message'] ?? 'Please check this page regularly for the latest dashboard announcements and updates.');
        \App\Models\AppSetting::markReadForUser('global_account_message', $userId);

        $directMessagingEnabled = DirectMessaging::isEnabledForCurrentUser();
        $conversations = $directMessagingEnabled ? DirectMessage::conversationSummaries($userId) : [];
        $facultyContacts = Auth::hasRole('faculty') ? DirectMessaging::facultyContactOptions($user) : [];
        $facultyRecipients = !Auth::hasRole('faculty') && $directMessagingEnabled
            ? DirectMessaging::facultyRecipientsForStaff($user)
            : [];
        $directUnreadCount = $directMessagingEnabled ? DirectMessage::unreadCountForUser($userId) : 0;

        view('dashboard.message', [
            'user' => $user,
            'message' => $message,
            'messageHistory' => $messageHistory,
            'isAdmin' => Auth::hasRole('ride_admin'),
            'directMessagingEnabled' => $directMessagingEnabled,
            'conversations' => $conversations,
            'facultyContacts' => $facultyContacts,
            'facultyRecipients' => $facultyRecipients,
            'directUnreadCount' => $directUnreadCount,
            'pageTitle' => 'Message | RIDE',
            'pageHeading' => 'Message',
            'pageSubtitle' => $directMessagingEnabled
                ? 'Announcements and direct messages'
                : 'Announcement for all accounts',
        ]);
    }

    public function updateMessage(): void
    {
        if (!Auth::hasRole('ride_admin')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('messages');
        }

        $message = trim((string) ($_POST['global_message'] ?? ''));
        if ($message === '') {
            set_flash('error', 'Message cannot be empty.');
            redirect('messages');
        }

        if (strlen($message) > 1000) {
            set_flash('error', 'Message must be 1000 characters or less.');
            redirect('messages');
        }

        $currentUser = Auth::user();
        \App\Models\AppSetting::put('global_account_message', $message, (int) ($currentUser['id'] ?? 0));
        AdminMessage::publish($message, (int) ($currentUser['id'] ?? 0));
        set_flash('success', 'Announcement posted successfully.');
        redirect('messages');
    }

    public function updateAnnouncement(int $id): void
    {
        if (!Auth::hasRole('ride_admin')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('messages');
        }

        $existing = AdminMessage::find($id);
        if ($existing === null) {
            set_flash('error', 'Announcement not found.');
            redirect('messages');
        }

        $message = trim((string) ($_POST['announcement_message'] ?? ''));
        if ($message === '') {
            set_flash('error', 'Announcement cannot be empty.');
            redirect('messages');
        }

        if (strlen($message) > 1000) {
            set_flash('error', 'Announcement must be 1000 characters or less.');
            redirect('messages');
        }

        if (!AdminMessage::update($id, $message)) {
            set_flash('error', 'Unable to update announcement.');
            redirect('messages');
        }

        $this->syncGlobalAccountMessageSetting();
        set_flash('success', 'Announcement updated successfully.');
        redirect('messages');
    }

    public function deleteAnnouncement(int $id): void
    {
        if (!Auth::hasRole('ride_admin')) {
            http_response_code(403);
            view('errors.403');
            exit;
        }

        if (!verify_csrf()) {
            set_flash('error', 'Invalid session. Please try again.');
            redirect('messages');
        }

        $existing = AdminMessage::find($id);
        if ($existing === null) {
            set_flash('error', 'Announcement not found.');
            redirect('messages');
        }

        if (!AdminMessage::deactivate($id)) {
            set_flash('error', 'Unable to delete announcement.');
            redirect('messages');
        }

        $this->syncGlobalAccountMessageSetting();
        set_flash('success', 'Announcement deleted successfully.');
        redirect('messages');
    }

    private function syncGlobalAccountMessageSetting(): void
    {
        $latest = AdminMessage::latest();
        $message = (string) ($latest['message'] ?? 'Please check this page regularly for the latest dashboard announcements and updates.');
        $currentUser = Auth::user();
        \App\Models\AppSetting::put('global_account_message', $message, (int) ($currentUser['id'] ?? 0));
    }

}
