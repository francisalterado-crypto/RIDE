<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Auth;
use App\Core\Database;

/**
 * Roles, scopes and the approval chain.
 *
 * Roles: Faculty; College Coordinator - Research / - Extension (faculty members of one college); College Dean;
 * Research Coordinator (RIDE-RDO) and Field Extensionist (RIDE-ESO), each over the colleges of their clusters;
 * RDO Director; ESO Director; OVPRIDE; Administrator (sees everything and configures the system, but is not an
 * approval step).
 *
 * Approval chain (see reviewChain()):
 * - proposals, requests, progress and terminal reports: College Coordinator > College Dean > RIDE Coordinator >
 *   Director > OVPRIDE (a College Coordinator's own record skips their own endorsement);
 * - quarterly reports, prepared by the RIDE Coordinator for one college: College Dean > Director > OVPRIDE.
 */
final class AccessControl
{
    public const VPRIDE = 'vpride';

    public const ADMIN = 'ride_admin';

    public const COORDINATOR_RESEARCH = 'coordinator_research';

    public const COORDINATOR_EXTENSION = 'coordinator_extension';

    public const DEAN = 'dean';

    public const RIDE_COORDINATOR_RESEARCH = 'rdo_coordinator';

    public const RIDE_COORDINATOR_EXTENSION = 'field_extensionist';

    public const DIRECTOR_RESEARCH = 'director_research';

    public const DIRECTOR_EXTENSION = 'director_extension';

    /** Route stored in proposals.coordinator_filed at submission. */
    public const ROUTE_FULL = 0;

    public const ROUTE_COORDINATOR_FILED = 1;

    public const ROUTE_RIDE_PREPARED = 2;

    /** @var list<string> */
    public const MONITORING_TYPES = ['research', 'extension', 'innovation', 'development'];

    /** @var list<string> */
    public const STAFF_ROLES = [
        self::VPRIDE,
        self::COORDINATOR_RESEARCH,
        self::COORDINATOR_EXTENSION,
        self::DEAN,
        self::RIDE_COORDINATOR_RESEARCH,
        self::RIDE_COORDINATOR_EXTENSION,
        self::DIRECTOR_RESEARCH,
        self::DIRECTOR_EXTENSION,
    ];

    /** @var list<string> */
    public const COORDINATOR_STEPS = [
        self::COORDINATOR_RESEARCH,
        self::COORDINATOR_EXTENSION,
    ];

    /** @var list<string> */
    public const RIDE_COORDINATOR_STEPS = [
        self::RIDE_COORDINATOR_RESEARCH,
        self::RIDE_COORDINATOR_EXTENSION,
    ];

    /** @var array<int, list<int>> cluster college ids per user, for the request */
    private static array $clusterColleges = [];

    // ---- Who the user is ----------------------------------------------------------------------------------------

    /** OVPRIDE or Administrator: sees everything university-wide. Use isOvpride() for the approval step. */
    public static function isVpride(): bool
    {
        return Auth::hasRole(self::VPRIDE, self::ADMIN);
    }

    /** The OVPRIDE account itself (final approval). */
    public static function isOvpride(): bool
    {
        return Auth::hasRole(self::VPRIDE);
    }

    public static function isAdmin(): bool
    {
        return Auth::hasRole(self::ADMIN);
    }

    /** A College Coordinator (research or extension). */
    public static function isCoordinator(): bool
    {
        return Auth::hasRole(...self::COORDINATOR_STEPS);
    }

    public static function isCoordinatorResearch(): bool
    {
        return Auth::hasRole(self::COORDINATOR_RESEARCH);
    }

    public static function isCoordinatorExtension(): bool
    {
        return Auth::hasRole(self::COORDINATOR_EXTENSION);
    }

    public static function isDean(): bool
    {
        return Auth::hasRole(self::DEAN);
    }

    /** A RIDE-side coordinator (Research Coordinator or Field Extensionist). */
    public static function isRideCoordinator(): bool
    {
        return Auth::hasRole(...self::RIDE_COORDINATOR_STEPS);
    }

    public static function isRideCoordinatorResearch(): bool
    {
        return Auth::hasRole(self::RIDE_COORDINATOR_RESEARCH);
    }

    public static function isRideCoordinatorExtension(): bool
    {
        return Auth::hasRole(self::RIDE_COORDINATOR_EXTENSION);
    }

    public static function isDirectorResearch(): bool
    {
        return Auth::hasRole(self::DIRECTOR_RESEARCH);
    }

    public static function isDirectorExtension(): bool
    {
        return Auth::hasRole(self::DIRECTOR_EXTENSION);
    }

    public static function isUniversityWide(): bool
    {
        return self::isVpride() || self::isDirectorResearch() || self::isDirectorExtension();
    }

    public static function isStaff(): bool
    {
        return Auth::hasRole(self::ADMIN, ...self::STAFF_ROLES);
    }

    // ---- Scope ----------------------------------------------------------------------------------------------------

    /**
     * @return 'research'|'extension'|null The only track the user works on (directors and RIDE coordinators);
     *         null for everyone who works on both tracks (faculty, college coordinators, deans, OVPRIDE, admin).
     */
    public static function proposalScopeType(): ?string
    {
        if (self::isVpride()) {
            return null;
        }
        if (self::isDirectorExtension() || self::isRideCoordinatorExtension()) {
            return 'extension';
        }
        if (self::isDirectorResearch() || self::isRideCoordinatorResearch()) {
            return 'research';
        }

        return null;
    }

    /**
     * Colleges in the clusters a user covers, for a track ('research' uses colleges.research_cluster_id,
     * 'extension' colleges.extension_cluster_id).
     *
     * @return list<int>
     */
    public static function clusterCollegeIds(string $track, ?int $userId = null): array
    {
        $userId ??= (int) (Auth::user()['id'] ?? 0);
        if ($userId <= 0) {
            return [];
        }
        $key = $userId * 10 + ($track === 'extension' ? 1 : 0);
        if (!isset(self::$clusterColleges[$key])) {
            $column = $track === 'extension' ? 'extension_cluster_id' : 'research_cluster_id';
            $stmt = Database::pdo()->prepare(
                'SELECT c.id FROM colleges c JOIN user_clusters uc ON uc.cluster_id = c.' . $column . ' WHERE uc.user_id = ? ORDER BY c.unit_type = "center", c.name'
            );
            $stmt->execute([$userId]);
            self::$clusterColleges[$key] = array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        return self::$clusterColleges[$key];
    }

    /** RIDE coordinators work across several colleges; so does the Administrator (all of them). */
    public static function worksAcrossColleges(): bool
    {
        return self::isRideCoordinator() || self::isAdmin();
    }

    /** Colleges a RIDE coordinator covers, all colleges for the Administrator, empty for everyone else. @return list<int> */
    public static function rideCoordinatorCollegeIds(): array
    {
        if (self::isAdmin()) {
            return array_values(array_map(
                static fn (array $college): int => (int) $college['id'],
                array_filter(\App\Models\College::all(), static fn (array $college): bool => (string) ($college['unit_type'] ?? 'college') === 'college')
            ));
        }
        if (self::isRideCoordinatorResearch()) {
            return self::clusterCollegeIds('research');
        }
        if (self::isRideCoordinatorExtension()) {
            return self::clusterCollegeIds('extension');
        }

        return [];
    }

    /**
     * Whether a record is within the user's oversight: their own; OVPRIDE/admin (all); a director (their track);
     * a RIDE coordinator (their track, colleges of their clusters); a College Coordinator (their track, their
     * college); a Dean (their college). Co-author access is checked separately.
     */
    public static function canViewProposal(array $proposal): bool
    {
        $user = Auth::user();
        if ($user === null) {
            return false;
        }
        if (self::isVpride() || (int) ($proposal['user_id'] ?? 0) === (int) $user['id']) {
            return true;
        }
        $type = (string) ($proposal['project_type'] ?? '');
        $collegeId = (int) ($proposal['college_id'] ?? 0);
        if ((self::isDirectorResearch() && $type === 'research') || (self::isDirectorExtension() && $type === 'extension')) {
            return true;
        }
        if ((self::isRideCoordinatorResearch() && $type === 'research') || (self::isRideCoordinatorExtension() && $type === 'extension')) {
            return in_array($collegeId, self::rideCoordinatorCollegeIds(), true);
        }
        $ownCollege = $collegeId > 0 && $collegeId === (int) ($user['college_id'] ?? 0);
        if ((self::isCoordinatorResearch() && $type === 'research') || (self::isCoordinatorExtension() && $type === 'extension')) {
            return $ownCollege;
        }

        return self::isDean() && $ownCollege;
    }

    /**
     * Records the user can see: everything in their oversight (canViewProposal) plus their own. Co-authored
     * records are added by the caller where they apply.
     *
     * @return list<array<string, mixed>>
     */
    public static function visibleProposals(?array $where = null): array
    {
        if (self::isVpride()) {
            return \App\Models\Proposal::all(null, null, $where);
        }
        $user = Auth::user();
        if ($user === null) {
            return [];
        }
        $oversees = self::isUniversityWide() || self::isRideCoordinator() || self::isCoordinator() || self::isDean();
        if (!$oversees) {
            return \App\Models\Proposal::forUser((int) $user['id'], $where);
        }
        $type = self::proposalScopeType();

        return array_values(array_filter(
            \App\Models\Proposal::all(null, $type, $where),
            static fn (array $proposal): bool => self::canViewProposal($proposal)
        ));
    }

    /** Projects (approved proposals) the user can see: their oversight scope, or their own and co-authored. @return list<array<string, mixed>> */
    public static function visibleProjects(): array
    {
        $user = Auth::user();
        if ($user === null) {
            return [];
        }
        if (self::isVpride()) {
            return \App\Models\Proposal::ongoing();
        }
        $own = \App\Models\Proposal::ongoing(null, (int) $user['id']);
        if (!(self::isUniversityWide() || self::isRideCoordinator() || self::isCoordinator() || self::isDean())) {
            return $own;
        }
        $rows = [];
        foreach (\App\Models\Proposal::ongoing(null, null, self::proposalScopeType()) as $proposal) {
            if (self::canViewProposal($proposal)) {
                $rows[(int) $proposal['id']] = $proposal;
            }
        }
        foreach ($own as $proposal) {
            $rows[(int) $proposal['id']] ??= $proposal;
        }

        return array_values($rows);
    }

    // ---- Approval chain -------------------------------------------------------------------------------------------

    public static function coordinatorStepForType(string $projectType): string
    {
        return $projectType === 'extension' ? self::COORDINATOR_EXTENSION : self::COORDINATOR_RESEARCH;
    }

    public static function rideCoordinatorStepForType(string $projectType): string
    {
        return $projectType === 'extension' ? self::RIDE_COORDINATOR_EXTENSION : self::RIDE_COORDINATOR_RESEARCH;
    }

    public static function directorStepForType(string $projectType): string
    {
        return $projectType === 'extension' ? self::DIRECTOR_EXTENSION : self::DIRECTOR_RESEARCH;
    }

    /** Whether a record is a quarterly report the RIDE coordinator prepares (ESO-06..10, RDO-05..14 and legacy consolidated). */
    public static function isPreparedQuarterlyReport(array $proposal): bool
    {
        return proposal_is_quarterly_researches_report($proposal)
            && !proposal_uses_faculty_submitted_monitoring_workflow($proposal);
    }

    /** The route a record takes when submitted now (stored in proposals.coordinator_filed). */
    public static function submitRoute(array $proposal): int
    {
        if (self::isPreparedQuarterlyReport($proposal)) {
            return self::ROUTE_RIDE_PREPARED;
        }
        $coordinatorStep = self::coordinatorStepForType((string) ($proposal['project_type'] ?? 'research'));

        return in_array($coordinatorStep, \App\Models\User::roleSlugs((int) ($proposal['user_id'] ?? 0)), true)
            ? self::ROUTE_COORDINATOR_FILED
            : self::ROUTE_FULL;
    }

    /** The route of a record: the one stored at submission, or what it would be if submitted now. */
    public static function route(array $proposal): int
    {
        if (isset($proposal['coordinator_filed']) && $proposal['coordinator_filed'] !== '' && in_array((string) ($proposal['status'] ?? ''), ['submitted', 'under_review', 'approved', 'ongoing', 'completed', 'returned', 'suspended'], true)) {
            $stored = (int) $proposal['coordinator_filed'];
            if (in_array($stored, [self::ROUTE_FULL, self::ROUTE_COORDINATOR_FILED, self::ROUTE_RIDE_PREPARED], true)) {
                return $stored;
            }
        }

        return self::submitRoute($proposal);
    }

    /** @return list<string> the review steps of a record, in order */
    public static function reviewChain(array $proposal): array
    {
        $type = (string) ($proposal['project_type'] ?? 'research');
        $tail = [self::directorStepForType($type), self::VPRIDE];

        return match (self::route($proposal)) {
            self::ROUTE_RIDE_PREPARED => [self::DEAN, ...$tail],
            self::ROUTE_COORDINATOR_FILED => [self::DEAN, self::rideCoordinatorStepForType($type), ...$tail],
            default => [self::coordinatorStepForType($type), self::DEAN, self::rideCoordinatorStepForType($type), ...$tail],
        };
    }

    public static function firstStep(array $proposal): string
    {
        return self::reviewChain($proposal)[0];
    }

    /** The step after $current, or 'approved' after the last one. */
    public static function nextStep(string $current, array $proposal): string
    {
        $chain = self::reviewChain($proposal);
        $index = array_search($current, $chain, true);
        if ($index === false) {
            // A step no longer in this record's chain: continue from the same step of the full chain.
            $type = (string) ($proposal['project_type'] ?? 'research');
            $full = [self::coordinatorStepForType($type), self::DEAN, self::rideCoordinatorStepForType($type), self::directorStepForType($type), self::VPRIDE];
            $index = array_search($current, $full, true);
            $chain = $full;
            if ($index === false) {
                return self::VPRIDE;
            }
        }

        return $chain[$index + 1] ?? 'approved';
    }

    /** Whether the signed-in user acts on a step (scope is checked separately with canViewProposal()). */
    public static function holdsStep(string $step): bool
    {
        return match ($step) {
            self::COORDINATOR_RESEARCH => self::isCoordinatorResearch(),
            self::COORDINATOR_EXTENSION => self::isCoordinatorExtension(),
            self::DEAN => self::isDean(),
            self::RIDE_COORDINATOR_RESEARCH => self::isRideCoordinatorResearch(),
            self::RIDE_COORDINATOR_EXTENSION => self::isRideCoordinatorExtension(),
            self::DIRECTOR_RESEARCH => self::isDirectorResearch(),
            self::DIRECTOR_EXTENSION => self::isDirectorExtension(),
            self::VPRIDE => self::isOvpride(),
            default => false,
        };
    }

    public static function stepLabel(string $step): string
    {
        return match ($step) {
            self::COORDINATOR_RESEARCH => 'College Coordinator - Research (Endorsement)',
            self::COORDINATOR_EXTENSION => 'College Coordinator - Extension (Endorsement)',
            self::DEAN => 'College Dean (Approval)',
            self::RIDE_COORDINATOR_RESEARCH => 'Research Coordinator, RDO (Review)',
            self::RIDE_COORDINATOR_EXTENSION => 'Field Extensionist, ESO (Review)',
            self::DIRECTOR_RESEARCH => 'RDO Director (Approval)',
            self::DIRECTOR_EXTENSION => 'ESO Director (Approval)',
            self::VPRIDE => 'OVPRIDE (Final Approval)',
            default => ucwords(str_replace('_', ' ', $step)),
        };
    }

    public static function actionLabel(string $step): string
    {
        return match ($step) {
            self::COORDINATOR_RESEARCH, self::COORDINATOR_EXTENSION => 'Endorse & Forward to Dean',
            self::DEAN => 'Approve & Forward',
            self::RIDE_COORDINATOR_RESEARCH, self::RIDE_COORDINATOR_EXTENSION => 'Review & Forward to Director',
            self::DIRECTOR_RESEARCH, self::DIRECTOR_EXTENSION => 'Approve & Forward to OVPRIDE',
            self::VPRIDE => 'Grant Final Approval',
            default => 'Approve',
        };
    }

    // ---- What the user can open -----------------------------------------------------------------------------------

    /** Single-track extension staff (ESO Director, Field Extensionist): no research forms. */
    private static function isExtensionScoped(): bool
    {
        return self::isDirectorExtension() || self::isRideCoordinatorExtension();
    }

    /** The 10 RDO quarterly reports are prepared by the Research Coordinator (RIDE-RDO), one per college. */
    public static function canPrepareResearchQuarterlyReports(): bool
    {
        return self::isRideCoordinatorResearch() || self::isAdmin();
    }

    /** The 5 ESO quarterly reports are prepared by the Field Extensionist (RIDE-ESO), one per college. */
    public static function canPrepareExtensionQuarterlyReports(): bool
    {
        return self::isRideCoordinatorExtension() || self::isAdmin();
    }

    public static function canAccessCompletedResearches(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessOngoingResearches(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessResearchOutputPublished(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessResearchOutputPresented(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessCommercialized(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessResultedInExtension(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessJournalCitation(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessBookCitation(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessInventionsUmCopyrights(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessLinkages(): bool
    {
        return self::canPrepareResearchQuarterlyReports();
    }

    public static function canAccessTrainingsConducted(): bool
    {
        return self::canPrepareExtensionQuarterlyReports();
    }

    public static function canAccessTechnicalAdvisory(): bool
    {
        return self::canPrepareExtensionQuarterlyReports();
    }

    public static function canAccessExtensionLinkages(): bool
    {
        return self::canPrepareExtensionQuarterlyReports();
    }

    public static function canAccessOutreachActivities(): bool
    {
        return self::canPrepareExtensionQuarterlyReports();
    }

    public static function canAccessTechnologyAdoption(): bool
    {
        return self::canPrepareExtensionQuarterlyReports();
    }

    /** Legacy "Consolidated ..." research forms: university-wide research offices only. Old records stay viewable. */
    private static function canAccessConsolidated(): bool
    {
        return self::isVpride() || self::isDirectorResearch();
    }

    public static function canAccessConsolidatedOngoingResearches(): bool
    {
        return self::canAccessConsolidated();
    }

    public static function canAccessConsolidatedResearchOutputPresented(): bool
    {
        return self::canAccessConsolidated();
    }

    public static function canAccessConsolidatedCompletedResearches(): bool
    {
        return self::canAccessConsolidated();
    }

    public static function canAccessConsolidatedResearchOutputPublished(): bool
    {
        return self::canAccessConsolidated();
    }

    public static function canAccessConsolidatedResearchForm(string $formKey): bool
    {
        return self::canAccessConsolidated() && in_array($formKey, \App\Services\ResearchFormConsolidation::FORM_KEYS, true);
    }

    /** Project progress/terminal reports and the assessment: research side (faculty, approvers, RDO). */
    public static function canAccessProgressReport(): bool
    {
        return !self::isExtensionScoped();
    }

    public static function canAccessTerminalReport(): bool
    {
        return !self::isExtensionScoped();
    }

    public static function canAccessObrMatrix(): bool
    {
        return !self::isExtensionScoped();
    }

    /** RDO request forms (Critical Activities, Program/Project/Study/Center, External Funding, Paper Presentation, Manuscript). */
    public static function canFileResearchRequests(): bool
    {
        return Auth::hasRole('faculty') || self::isCoordinator() || self::isDean();
    }

    /** Research Accomplishments (source of the RDO quarterly reports). */
    public static function canEnterResearchAccomplishments(): bool
    {
        return Auth::hasRole('faculty') || self::isCoordinator() || self::isDean() || self::isRideCoordinatorResearch() || self::isAdmin();
    }

    /** Conducted Activities (source of the ESO quarterly reports). */
    public static function canEnterConductedActivities(): bool
    {
        return Auth::hasRole('faculty') || self::isCoordinator() || self::isDean() || self::isRideCoordinatorExtension() || self::isAdmin();
    }

    public static function canAccessEbalwasyonNgGawain(): bool
    {
        return self::isExtensionScoped();
    }

    public static function roleTitle(): string
    {
        return user_role_label();
    }
}
