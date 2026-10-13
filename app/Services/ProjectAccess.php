<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\Proposal;
use App\Support\MonitoringRoles;
use App\Support\ProposalCoAuthors;

final class ProjectAccess
{
    /** Statuses that allow monitoring features */
    public const MONITORING_STATUSES = ['approved', 'ongoing', 'completed'];

    public static function canView(int $proposalId): ?array
    {
        $proposal = Proposal::find($proposalId);
        if (!$proposal) {
            return null;
        }
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        if (Auth::hasRole('ride_reporter')) {
            return $proposal;
        }
        if (MonitoringRoles::isVpride()) {
            return $proposal;
        }
        if (MonitoringRoles::proposalScopeType() !== null && MonitoringRoles::canViewProposal($proposal)) {
            return $proposal;
        }
        if (Auth::hasRole('dean') && (int) $user['college_id'] === (int) $proposal['college_id']) {
            return $proposal;
        }
        if ((int) $proposal['user_id'] === (int) $user['id']) {
            return $proposal;
        }
        if (Auth::hasRole('faculty')
            && ProposalCoAuthors::userHasCoauthorAccess(
                (int) $proposal['id'],
                (string) ($proposal['summary'] ?? ''),
                (int) $user['id']
            )) {
            return $proposal;
        }

        return null;
    }

    public static function canManage(array $proposal): bool
    {
        if (!in_array($proposal['status'], self::MONITORING_STATUSES, true)) {
            return false;
        }
        $user = Auth::user();
        if (MonitoringRoles::isVpride()) {
            return true;
        }
        return (int) $proposal['user_id'] === (int) $user['id'];
    }

    /** Beneficiary / MOU / impact records: project owner plus in-scope Extension Services staff. */
    public static function canManageExtensionRecords(array $proposal): bool
    {
        if (($proposal['project_type'] ?? '') !== 'extension' || !self::isMonitoring($proposal)) {
            return false;
        }
        if (self::canManage($proposal)) {
            return true;
        }

        return (MonitoringRoles::isDirectorExtension()
                || MonitoringRoles::isCoordinatorExtension()
                || MonitoringRoles::isExtensionWorker())
            && MonitoringRoles::canViewProposal($proposal);
    }

    public static function isMonitoring(array $proposal): bool
    {
        return in_array($proposal['status'], self::MONITORING_STATUSES, true);
    }

    public static function denyUnlessView(int $proposalId): ?array
    {
        $proposal = self::canView($proposalId);
        if (!$proposal) {
            http_response_code(403);
            view('errors.403');
            return null;
        }
        return $proposal;
    }
}
