<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Proposal;
use App\Models\User;
use App\Support\MonitoringRoles;

final class MonitoringController
{
    public function index(): void
    {
        if (!MonitoringRoles::isStaff()) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        $user = Auth::user();
        $collegeId = $user['college_id'] ? (int) $user['college_id'] : null;
        $scopeCollegeId = MonitoringRoles::isUniversityWide() ? null : $collegeId;
        $scopeProjectType = MonitoringRoles::proposalScopeType();

        if (MonitoringRoles::isVpride()) {
            $requestedScope = trim((string) ($_GET['scope'] ?? ''));
            if ($requestedScope === '') {
                redirect(base_url('monitoring?scope=research'));
            }
            if (in_array($requestedScope, ['research', 'extension'], true)) {
                $scopeProjectType = $requestedScope;
            }
        }

        if (!$scopeCollegeId && !MonitoringRoles::isUniversityWide()) {
            http_response_code(403);
            view('errors.403');
            return;
        }

        $stats = Proposal::monitoringStats($scopeCollegeId, $scopeProjectType);
        $submissions = Proposal::forMonitoring($scopeCollegeId, null, $scopeProjectType);
        $pendingAction = [];
        $submitters = [];

        if (MonitoringRoles::isCoordinatorResearch()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::COORDINATOR_RESEARCH, $collegeId, 'research');
        } elseif (MonitoringRoles::isCoordinatorExtension()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::COORDINATOR_EXTENSION, $collegeId, 'extension');
        } elseif (MonitoringRoles::isDean()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::DEAN, $collegeId);
        } elseif (MonitoringRoles::isDirectorResearch()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::DIRECTOR_RESEARCH, null, 'research');
        } elseif (MonitoringRoles::isDirectorExtension()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::DIRECTOR_EXTENSION, null, 'extension');
        } elseif (MonitoringRoles::isVpride()) {
            $pendingAction = Proposal::pendingAtStep(MonitoringRoles::VPRIDE, null, $scopeProjectType);
            $submitters = User::researchSubmitters($scopeProjectType);
        }

        $scopeLabel = match ($scopeProjectType) {
            'research' => 'Research',
            'extension' => 'Extension',
            default => 'All Proposal',
        };
        $roleTitle = MonitoringRoles::roleTitle();
        $pageHeading = $scopeLabel . ' Monitoring';
        $pageSubtitle = 'Signed in as ' . $roleTitle
            . ($user['college_name'] && $scopeProjectType === null && !MonitoringRoles::isVpride() ? ' · ' . $user['college_name'] : '');

        view('monitoring.index', [
            'user' => $user,
            'roleTitle' => $roleTitle,
            'stats' => $stats,
            'submissions' => $submissions,
            'pendingAction' => $pendingAction,
            'submitters' => $submitters,
            'isVpride' => MonitoringRoles::isVpride(),
            'isCoordinator' => MonitoringRoles::isCoordinator(),
            'isCoordinatorResearch' => MonitoringRoles::isCoordinatorResearch(),
            'isCoordinatorExtension' => MonitoringRoles::isCoordinatorExtension(),
            'isDean' => MonitoringRoles::isDean(),
            'isDirectorResearch' => MonitoringRoles::isDirectorResearch(),
            'isDirectorExtension' => MonitoringRoles::isDirectorExtension(),
            'scopeProjectType' => $scopeProjectType,
            'pageTitle' => $pageHeading . ' — RIDE IMS',
            'pageHeading' => $pageHeading,
            'pageSubtitle' => $pageSubtitle,
        ]);
    }
}
