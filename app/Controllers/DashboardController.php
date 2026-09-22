<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\AdminMessage;
use App\Models\Milestone;
use App\Models\Notification;
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

        $dashboardSections = [];
        if ($splitDashboard) {
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

        $overdueMilestones = Milestone::overdueForUser(
            $all ? null : ($collegeId && !Auth::hasRole('faculty') ? $collegeId : null),
            Auth::hasRole('faculty') ? (int) $user['id'] : null,
            $all
        );
        $overdueReports = ProgressReport::overdue(
            Auth::hasRole('faculty') ? (int) $user['id'] : null,
            $collegeId && !$all && !Auth::hasRole('faculty') ? $collegeId : null
        );
        $latestAdminMessage = AdminMessage::latest();
        $globalMessage = (string) ($latestAdminMessage['message'] ?? 'Please check this page regularly for the latest dashboard announcements and updates.');

        $pendingCoAuthorInvitations = $isFaculty
            ? ProposalCoAuthorInvitation::pendingForUser((int) $user['id'])
            : [];

        $facultyReportingNotice = null;
        if ($isFaculty || MonitoringRoles::isExtensionWorker()) {
            $currentPeriod = QuarterlyReporting::currentPeriodKey();
            $facultyReportingNotice = [
                'scheduleNotice' => FacultyFormDeadlines::scheduleNoticeText(),
                'submissionOpen' => FacultyFormDeadlines::isSubmissionOpen(),
                'currentPeriodLabel' => $currentPeriod !== null
                    ? QuarterlyReporting::periodLabel($currentPeriod)
                    : '',
            ];
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
            'pageTitle' => 'Message — RIDE IMS',
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
