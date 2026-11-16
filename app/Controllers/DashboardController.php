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
use App\Support\AccessControl;
use App\Support\QuarterlyReporting;

final class DashboardController
{
    public function index(): void
    {
        Notification::checkOverdueAlerts();
        $user = Auth::user();
        $isFaculty = Auth::hasRole('faculty');
        $facultyTypes = ['research', 'extension'];
        $scopeType = AccessControl::proposalScopeType();
        $all = AccessControl::isVpride();
        $collegeId = $user['college_id'] ? (int) $user['college_id'] : null;
        $splitDashboard = $scopeType === null && (
            $all
            || $isFaculty
            || Auth::hasRole('dean')
        );

        // Reviewers (college coordinator, dean, RIDE coordinator, director, OVPRIDE, admin) get an action-focused
        // dashboard for their step and scope; College Coordinators are faculty too and see their own drafts there.
        $reviewerContext = self::reviewerContext($user);

        // Faculty without a review role get a dashboard about their own work.
        $facultyOnly = $reviewerContext === null && $isFaculty;

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

        $isOwnScoped = $facultyOnly;
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
        if ($isFaculty || AccessControl::isRideCoordinator()) {
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
                'facultyReportingNotice' => $facultyReportingNotice,
                'pendingCoAuthorInvitations' => $pendingCoAuthorInvitations,
                'ownOverdueMilestones' => $isOwnScoped ? $overdueMilestones : [],
                'ownOverdueReports' => $isOwnScoped ? $overdueReports : [],
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
     * collegeIds: null for university-wide roles, one college for college roles, the cluster colleges for RIDE
     * coordinators. collegeId is set only when the scope is a single college.
     *
     * @return array{role: string, title: string, step: string, types: list<string>, collegeId: ?int, collegeIds: ?list<int>}|null
     */
    private static function reviewerContext(array $user): ?array
    {
        $collegeId = (int) ($user['college_id'] ?? 0) ?: null;
        $context = static fn (string $role, string $title, string $step, array $types, ?int $college, ?array $colleges = null): array => [
            'role' => $role,
            'title' => $title,
            'step' => $step,
            'types' => $types,
            'collegeId' => $college,
            'collegeIds' => $colleges ?? ($college !== null ? [$college] : null),
        ];

        if (AccessControl::isAdmin()) {
            // The Administrator sees everything but approves nothing: no queue.
            return $context('vpride', 'Administrator', '', ['research', 'extension'], null);
        }
        if (AccessControl::isOvpride()) {
            return $context('vpride', 'OVPRIDE', AccessControl::VPRIDE, ['research', 'extension'], null);
        }
        if (AccessControl::isDirectorResearch()) {
            return $context('director', 'RDO Director', AccessControl::DIRECTOR_RESEARCH, ['research'], null);
        }
        if (AccessControl::isDirectorExtension()) {
            return $context('director', 'ESO Director', AccessControl::DIRECTOR_EXTENSION, ['extension'], null);
        }
        if (AccessControl::isRideCoordinatorResearch()) {
            $colleges = AccessControl::clusterCollegeIds('research');
            return $context('ride', 'Research Coordinator (RDO)', AccessControl::RIDE_COORDINATOR_RESEARCH, ['research'], count($colleges) === 1 ? $colleges[0] : null, $colleges);
        }
        if (AccessControl::isRideCoordinatorExtension()) {
            $colleges = AccessControl::clusterCollegeIds('extension');
            return $context('ride', 'Field Extensionist (ESO)', AccessControl::RIDE_COORDINATOR_EXTENSION, ['extension'], count($colleges) === 1 ? $colleges[0] : null, $colleges);
        }
        if ($collegeId === null) {
            return null;
        }
        if (AccessControl::isDean()) {
            return $context('dean', 'College Dean', AccessControl::DEAN, ['research', 'extension'], $collegeId);
        }
        if (AccessControl::isCoordinatorResearch()) {
            return $context('coordinator', 'College Coordinator - Research', AccessControl::COORDINATOR_RESEARCH, ['research'], $collegeId);
        }
        if (AccessControl::isCoordinatorExtension()) {
            return $context('coordinator', 'College Coordinator - Extension', AccessControl::COORDINATOR_EXTENSION, ['extension'], $collegeId);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private static function reviewerDashboardData(array $context, int $userId): array
    {
        $types = $context['types'];
        $collegeId = $context['collegeId'];
        $scopeColleges = $context['collegeIds'] ?? null;
        $singleType = count($types) === 1 ? $types[0] : null;
        $inScope = static fn (array $row): bool => in_array((string) ($row['project_type'] ?? ''), $types, true)
            && ($scopeColleges === null || in_array((int) ($row['college_id'] ?? 0), $scopeColleges, true));
        // Queries take one college or all; a cluster (several colleges) is queried for all and filtered.
        $queryCollege = $scopeColleges === null ? null : (count($scopeColleges) === 1 ? $scopeColleges[0] : null);

        // Proposals waiting on this user, oldest first.
        $queue = $context['step'] === '' || $scopeColleges === []
            ? []
            : array_values(array_filter(Proposal::pendingAtStep($context['step'], $queryCollege, $singleType), $inScope));

        // Status counts, summed over the colleges in scope.
        $byStatus = [];
        $pendingByStep = [];
        foreach ($scopeColleges === null ? [null] : $scopeColleges as $statsCollege) {
            $stats = Proposal::monitoringStats($statsCollege, $singleType);
            foreach ($stats['by_status'] ?? [] as $key => $count) {
                $byStatus[$key] = ($byStatus[$key] ?? 0) + (int) $count;
            }
            foreach ($stats['pending_by_step'] ?? [] as $key => $count) {
                $pendingByStep[$key] = ($pendingByStep[$key] ?? 0) + (int) $count;
            }
        }

        // Pipeline: how many proposals sit at each review step, in workflow order.
        $stepOrder = [];
        foreach ($types as $type) {
            $stepOrder[] = AccessControl::coordinatorStepForType($type);
        }
        $stepOrder[] = AccessControl::DEAN;
        foreach ($types as $type) {
            $stepOrder[] = AccessControl::rideCoordinatorStepForType($type);
        }
        foreach ($types as $type) {
            $stepOrder[] = AccessControl::directorStepForType($type);
        }
        $stepOrder[] = AccessControl::VPRIDE;
        $shortStepLabels = [
            AccessControl::COORDINATOR_RESEARCH => 'College Coordinator - Research',
            AccessControl::COORDINATOR_EXTENSION => 'College Coordinator - Extension',
            AccessControl::DEAN => 'College Dean',
            AccessControl::RIDE_COORDINATOR_RESEARCH => 'Research Coordinator (RDO)',
            AccessControl::RIDE_COORDINATOR_EXTENSION => 'Field Extensionist (ESO)',
            AccessControl::DIRECTOR_RESEARCH => 'RDO Director',
            AccessControl::DIRECTOR_EXTENSION => 'ESO Director',
            AccessControl::VPRIDE => 'OVPRIDE',
        ];
        $pipeline = [];
        foreach ($stepOrder as $step) {
            $pipeline[] = [
                'label' => $shortStepLabels[$step] ?? $step,
                'count' => (int) ($pendingByStep[$step] ?? 0),
                'isMine' => $step === $context['step'],
            ];
        }

        $ongoing = array_values(array_filter(Proposal::ongoing($queryCollege, null, $singleType), static fn (array $p): bool => in_array((string) $p['status'], ['ongoing', 'approved'], true) && $inScope($p)));
        $overdueMilestones = array_values(array_filter(Milestone::overdueForUser($queryCollege, null, $queryCollege === null), $inScope));
        $overdueReports = array_values(array_filter(ProgressReport::overdue(null, $queryCollege), $inScope));

        $collegeNames = [];
        $collegeIds = [];
        foreach (\App\Models\College::all() as $college) {
            $collegeNames[(int) $college['id']] = (string) ($college['code'] ?? '') !== '' ? (string) $college['code'] : (string) $college['name'];
            if ((string) ($college['unit_type'] ?? 'college') === 'college'
                && ($scopeColleges === null || in_array((int) $college['id'], $scopeColleges, true))) {
                $collegeIds[] = (int) $college['id'];
            }
        }

        // Directors and VPRIDE compare colleges; deans compare their two tracks.
        $breakdown = [];
        if ($collegeId === null) {
            // Every college in scope is listed, so colleges with no activity stand out; centers appear only when they have proposals.
            foreach ($collegeIds as $id) {
                $breakdown[$collegeNames[$id]] = ['in_review' => 0, 'ongoing' => 0, 'completed' => 0];
            }
            foreach (Proposal::forMonitoring(null, null, $singleType) as $p) {
                // Proposals only; reports and request forms are not projects.
                if (!$inScope($p) || !proposal_is_proposal_form($p)) {
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
                $breakdown[ucfirst($type)] = ['in_review' => 0, 'ongoing' => 0, 'completed' => 0];
            }
            foreach (Proposal::forMonitoring($collegeId, null, null) as $p) {
                $key = ucfirst((string) $p['project_type']);
                if (!isset($breakdown[$key]) || !proposal_is_proposal_form($p)) {
                    continue;
                }
                $status = (string) $p['status'];
                if (in_array($status, ['submitted', 'under_review'], true)) {
                    $breakdown[$key]['in_review']++;
                } elseif (in_array($status, ['ongoing', 'approved'], true)) {
                    $breakdown[$key]['ongoing']++;
                } elseif ($status === 'completed') {
                    $breakdown[$key]['completed']++;
                }
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

        // College Coordinators file their own proposals; RIDE coordinators prepare the quarterly reports.
        $myDrafts = [];
        if (in_array($context['role'], ['coordinator', 'ride'], true)) {
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
            // VPRIDE: who is submitting, university-wide (formerly on the Monitoring page).
            'submitters' => $context['role'] === 'vpride' ? \App\Models\User::researchSubmitters($singleType) : [],
            'followUps' => array_values($followUps),
            'myDrafts' => $myDrafts,
            'monthlyActivity' => self::monthlyActivityFor($scopeColleges, $types),
            'scopeLabel' => ($scopeColleges === null
                    ? 'All colleges'
                    : ($scopeColleges === [] ? 'No colleges assigned' : implode(', ', array_map(static fn (int $id): string => $collegeNames[$id] ?? '', $scopeColleges))))
                . ', ' . ($singleType !== null ? ucfirst($singleType) : 'Research and Extension'),
        ];
    }

    /**
     * Monthly submitted/approved counts for a set of colleges (null = all), summed per month.
     *
     * @param list<int>|null $collegeIds
     * @param list<string> $types
     * @return array<string, list<mixed>>
     */
    private static function monthlyActivityFor(?array $collegeIds, array $types): array
    {
        if ($collegeIds === null || count($collegeIds) === 1) {
            return Proposal::monthlyWorkflowActivity(null, $collegeIds[0] ?? null, $types);
        }
        $total = null;
        foreach ($collegeIds as $id) {
            $series = Proposal::monthlyWorkflowActivity(null, $id, $types);
            if ($total === null) {
                $total = $series;
                continue;
            }
            foreach ($series as $key => $values) {
                if ($key === 'labels' || !is_array($values)) {
                    continue;
                }
                foreach ($values as $i => $value) {
                    $total[$key][$i] = (int) ($total[$key][$i] ?? 0) + (int) $value;
                }
            }
        }

        return $total ?? Proposal::monthlyWorkflowActivity(null, -1, $types);
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
            'ongoingProjects' => array_values(array_filter(
                array_merge(
                    Proposal::ongoing(null, $userId, 'research'),
                    Proposal::ongoing(null, $userId, 'extension')
                ),
                static fn (array $p): bool => in_array((string) $p['status'], ['ongoing', 'approved'], true)
            )),
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
            'pageTitle' => 'Messages | RIDE Hub',
            'pageHeading' => 'Messages',
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
