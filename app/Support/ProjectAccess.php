<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Auth;
use App\Models\Proposal;

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
        if (AccessControl::canViewProposal($proposal)) {
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
        if (AccessControl::isVpride()) {
            return true;
        }
        return (int) $proposal['user_id'] === (int) $user['id'];
    }

    /**
     * Who reviews submitted progress reports: the approvers over the project (college coordinator, dean, RIDE
     * coordinator, the director for its track, OVPRIDE). Never the project leader.
     */
    public static function canReviewReports(array $proposal): bool
    {
        $user = Auth::user();
        if ($user === null || !self::isMonitoring($proposal) || (int) $proposal['user_id'] === (int) $user['id']) {
            return false;
        }
        if (AccessControl::isOvpride()) {
            return true;
        }

        return (AccessControl::isCoordinator()
                || AccessControl::isDean()
                || AccessControl::isRideCoordinator()
                || AccessControl::isDirectorResearch()
                || AccessControl::isDirectorExtension())
            && AccessControl::canViewProposal($proposal);
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

        return (AccessControl::isDirectorExtension()
                || AccessControl::isCoordinatorExtension()
                || AccessControl::isRideCoordinatorExtension())
            && AccessControl::canViewProposal($proposal);
    }

    public static function isMonitoring(array $proposal): bool
    {
        return in_array($proposal['status'], self::MONITORING_STATUSES, true)
            && \App\Support\ProposalRegistry::kindOf($proposal) === 'proposal';
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
