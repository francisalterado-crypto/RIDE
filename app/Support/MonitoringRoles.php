<?php



declare(strict_types=1);



namespace App\Support;



use App\Core\Auth;



final class MonitoringRoles

{

    public const VPRIDE = 'vpride';

    public const COORDINATOR_RESEARCH = 'coordinator_research';

    public const COORDINATOR_EXTENSION = 'coordinator_extension';

    public const DEAN = 'dean';

    public const DIRECTOR_RESEARCH = 'director_research';

    public const DIRECTOR_EXTENSION = 'director_extension';

    public const EXTENSION_WORKER = 'extension_worker';



    /** @var list<string> */

    public const MONITORING_TYPES = ['research', 'extension', 'innovation', 'development'];



    /** @var list<string> */

    public const STAFF_ROLES = [

        self::VPRIDE,

        self::COORDINATOR_RESEARCH,

        self::COORDINATOR_EXTENSION,

        self::DEAN,

        self::DIRECTOR_RESEARCH,

        self::DIRECTOR_EXTENSION,

    ];



    /** @var list<string> */

    public const COORDINATOR_STEPS = [

        self::COORDINATOR_RESEARCH,

        self::COORDINATOR_EXTENSION,

    ];



    public static function isVpride(): bool

    {

        return Auth::hasRole(self::VPRIDE, 'ride_admin');

    }



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



    public static function isDirectorResearch(): bool

    {

        return Auth::hasRole(self::DIRECTOR_RESEARCH);

    }



    public static function isDirectorExtension(): bool

    {

        return Auth::hasRole(self::DIRECTOR_EXTENSION);

    }



    public static function isExtensionWorker(): bool

    {

        return Auth::hasRole(self::EXTENSION_WORKER);

    }



    public static function isUniversityWide(): bool

    {

        return self::isVpride() || self::isDirectorResearch() || self::isDirectorExtension();

    }



    public static function coordinatorStepForType(string $projectType): string

    {

        return $projectType === 'extension' ? self::COORDINATOR_EXTENSION : self::COORDINATOR_RESEARCH;

    }



    /** @return 'research'|'extension'|null Type filter for scoped roles; null = no filter. */

    public static function proposalScopeType(): ?string

    {

        if (self::isDirectorExtension() || self::isCoordinatorExtension() || self::isExtensionWorker()) {

            return 'extension';

        }

        if (self::isDirectorResearch() || self::isCoordinatorResearch()) {

            return 'research';

        }



        return null;

    }



    public static function canViewProposal(array $proposal): bool

    {

        $scope = self::proposalScopeType();

        if ($scope === null) {

            return true;

        }



        if ((string) ($proposal['project_type'] ?? '') !== $scope) {

            return false;

        }



        if (self::isCoordinatorResearch() || self::isCoordinatorExtension() || self::isExtensionWorker()) {

            $user = Auth::user();

            return $user !== null && (int) ($user['college_id'] ?? 0) === (int) ($proposal['college_id'] ?? 0);

        }



        return true;

    }



    private static function isExtensionScoped(): bool

    {

        return self::isDirectorExtension() || self::isCoordinatorExtension() || self::isExtensionWorker();

    }



    public static function canAccessManuscript(): bool

    {

        return !self::isExtensionScoped();

    }



    public static function canAccessCompletedResearches(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessOngoingResearches(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessConsolidatedOngoingResearches(): bool

    {

        return !self::isExtensionScoped() && !Auth::hasRole('faculty');

    }



    public static function canAccessResearchOutputPublished(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessResearchOutputPresented(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessConsolidatedResearchOutputPresented(): bool

    {

        return !self::isExtensionScoped() && !Auth::hasRole('faculty');

    }



    public static function canAccessCommercialized(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessResultedInExtension(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessJournalCitation(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessBookCitation(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessInventionsUmCopyrights(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessConsolidatedResearchForm(string $formKey): bool

    {

        if (self::isExtensionScoped() || Auth::hasRole('faculty')) {

            return false;

        }

        return in_array($formKey, \App\Services\ResearchFormConsolidation::FORM_KEYS, true);

    }



    public static function canAccessLinkages(): bool

    {

        return !self::isExtensionScoped() && !self::isCoordinatorResearch();

    }



    public static function canAccessConsolidatedCompletedResearches(): bool

    {

        return !self::isExtensionScoped() && !Auth::hasRole('faculty');

    }



    public static function canAccessConsolidatedResearchOutputPublished(): bool

    {

        return !self::isExtensionScoped() && !Auth::hasRole('faculty');

    }



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



    public static function canAccessTrainingsConducted(): bool

    {

        return self::isExtensionScoped();

    }



    public static function canAccessTechnicalAdvisory(): bool

    {

        return self::isExtensionScoped();

    }



    public static function canAccessExtensionLinkages(): bool

    {

        return self::isExtensionScoped();

    }



    public static function canAccessOutreachActivities(): bool

    {

        return self::isExtensionScoped();

    }



    public static function canAccessTechnologyAdoption(): bool

    {

        return self::isExtensionScoped();

    }



    public static function canAccessEbalwasyonNgGawain(): bool

    {

        return self::isExtensionScoped();

    }



    public static function isStaff(): bool

    {

        return Auth::hasRole(
            'ride_admin',
            self::VPRIDE,
            self::DEAN,
            self::DIRECTOR_RESEARCH,
            self::DIRECTOR_EXTENSION,
            ...self::COORDINATOR_STEPS
        );

    }



    public static function directorStepForType(string $projectType): string

    {

        return $projectType === 'extension' ? self::DIRECTOR_EXTENSION : self::DIRECTOR_RESEARCH;

    }



    public static function stepLabel(string $step): string

    {

        return match ($step) {

            self::COORDINATOR_RESEARCH => 'Coordinator of Research — Endorsement',

            self::COORDINATOR_EXTENSION => 'Coordinator of Extension — Endorsement',

            self::DEAN => 'College Dean — Approval',

            self::DIRECTOR_RESEARCH => 'Director of Research — Approval',

            self::DIRECTOR_EXTENSION => 'Director of Extension — Approval',

            self::VPRIDE => 'VPRIDE — Final Approval',

            default => ucwords(str_replace('_', ' ', $step)),

        };

    }



    public static function actionLabel(string $step): string

    {

        return match ($step) {

            self::COORDINATOR_RESEARCH, self::COORDINATOR_EXTENSION => 'Endorse & Forward to Dean',

            self::DEAN => 'Approve & Forward to Director',

            self::DIRECTOR_RESEARCH, self::DIRECTOR_EXTENSION => 'Approve & Forward to VPRIDE',

            self::VPRIDE => 'Grant Final Approval',

            default => 'Approve',

        };

    }



    public static function roleTitle(): string

    {

        if (self::isVpride()) {

            return 'Admin / VPRIDE';

        }

        if (self::isDirectorResearch()) {

            return 'Director of Research';

        }

        if (self::isDirectorExtension()) {

            return 'Director of Extension';

        }

        if (self::isCoordinatorResearch()) {

            return 'Coordinator of Research';

        }

        if (self::isCoordinatorExtension()) {

            return 'Coordinator of Extension';

        }

        if (self::isDean()) {

            return 'College Dean';

        }



        return 'Monitoring';

    }

}


