<?php



declare(strict_types=1);



namespace App\Support;



use App\Core\Auth;

use App\Models\User;



final class DirectMessaging

{

    /** @var list<string> */

    public const STAFF_ROLE_SLUGS = [

        MonitoringRoles::COORDINATOR_RESEARCH,

        MonitoringRoles::COORDINATOR_EXTENSION,

        MonitoringRoles::DEAN,

        MonitoringRoles::DIRECTOR_RESEARCH,

        MonitoringRoles::DIRECTOR_EXTENSION,

        MonitoringRoles::VPRIDE,

    ];



    /** @var array<string, string> */

    public const STAFF_ROLE_LABELS = [

        MonitoringRoles::COORDINATOR_RESEARCH => 'Research Coordinator',

        MonitoringRoles::COORDINATOR_EXTENSION => 'Field Extensionist',

        MonitoringRoles::DEAN => 'College Dean',

        MonitoringRoles::DIRECTOR_RESEARCH => 'RDO Director',

        MonitoringRoles::DIRECTOR_EXTENSION => 'ESO Director',

        MonitoringRoles::VPRIDE => 'VPRIDE',

    ];



    /** @var list<string> */

    private const COLLEGE_SCOPED_ROLE_SLUGS = [

        MonitoringRoles::COORDINATOR_RESEARCH,

        MonitoringRoles::COORDINATOR_EXTENSION,

        MonitoringRoles::DEAN,

    ];



    public static function isEnabledForCurrentUser(): bool

    {

        if (Auth::hasRole('faculty')) {

            return true;

        }



        return Auth::hasRole(...self::STAFF_ROLE_SLUGS) || Auth::hasRole('ride_admin');

    }



    /** @return list<string> */

    public static function roleSlugsForUser(int $userId): array

    {

        $rows = User::roleSlugsForUserId($userId);

        $allowed = array_flip(self::STAFF_ROLE_SLUGS);

        $roles = array_values(array_filter($rows, static fn (string $slug): bool => isset($allowed[$slug])));



        if ($roles === [] && in_array('ride_admin', $rows, true)) {

            return [MonitoringRoles::VPRIDE];

        }



        return $roles;

    }



    public static function isFacultyUser(int $userId): bool

    {

        return in_array('faculty', User::roleSlugsForUserId($userId), true);

    }



    public static function canExchange(int $userIdA, int $userIdB): bool

    {

        if ($userIdA === $userIdB || $userIdA <= 0 || $userIdB <= 0) {

            return false;

        }



        $aIsFaculty = self::isFacultyUser($userIdA);

        $bIsFaculty = self::isFacultyUser($userIdB);



        if ($aIsFaculty === $bIsFaculty) {

            return false;

        }



        $facultyId = $aIsFaculty ? $userIdA : $userIdB;

        $staffId = $aIsFaculty ? $userIdB : $userIdA;



        return self::facultyCanMessageStaff($facultyId, $staffId);

    }



    public static function facultyCanMessageStaff(int $facultyId, int $staffId): bool

    {

        $faculty = User::findById($facultyId);

        $staff = User::findById($staffId);

        if ($faculty === null || $staff === null) {

            return false;

        }



        $staffRoles = self::roleSlugsForUser($staffId);

        if ($staffRoles === []) {

            return false;

        }



        $facultyCollegeId = (int) ($faculty['college_id'] ?? 0);



        foreach ($staffRoles as $roleSlug) {

            if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {

                if ($facultyCollegeId > 0 && self::staffServesCollege($staffId, $roleSlug, $facultyCollegeId)) {

                    return true;

                }

                continue;

            }



            if (in_array($roleSlug, [MonitoringRoles::DIRECTOR_RESEARCH, MonitoringRoles::DIRECTOR_EXTENSION, MonitoringRoles::VPRIDE], true)) {

                return true;

            }

        }



        return false;

    }



    private static function staffServesCollege(int $staffId, string $roleSlug, int $collegeId): bool

    {

        return User::hasActiveRoleForCollege($staffId, $roleSlug, $collegeId);

    }



    /**

     * Contacts a faculty member can start a conversation with (one per role when assigned).

     *

     * @return list<array{role_slug: string, label: string, user: ?array<string, mixed>}>

     */

    public static function facultyContactOptions(array $facultyUser): array

    {

        $collegeId = (int) ($facultyUser['college_id'] ?? 0);

        $options = [];



        foreach (self::STAFF_ROLE_SLUGS as $roleSlug) {

            $user = self::staffContactForRole($roleSlug, $collegeId);

            if ($user === null) {

                continue;

            }



            $options[] = [

                'role_slug' => $roleSlug,

                'label' => self::STAFF_ROLE_LABELS[$roleSlug] ?? $roleSlug,

                'user' => $user,

            ];

        }



        return $options;

    }



    /**

     * Faculty members the current staff user may message.

     *

     * @return list<array<string, mixed>>

     */

    public static function facultyRecipientsForStaff(array $staffUser): array

    {

        $staffId = (int) ($staffUser['id'] ?? 0);

        $staffRoles = self::roleSlugsForUser($staffId);

        if ($staffRoles === []) {

            return [];

        }



        $collegeScoped = false;

        foreach ($staffRoles as $roleSlug) {

            if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {

                $collegeScoped = true;

                break;

            }

        }



        if ($collegeScoped) {

            $collegeId = (int) ($staffUser['college_id'] ?? 0);

            if ($collegeId <= 0) {

                return [];

            }



            return User::facultyForCollege($collegeId);

        }



        return User::allFaculty();

    }



    public static function staffRoleLabelForUser(int $userId): string

    {

        $roles = self::roleSlugsForUser($userId);

        if ($roles === []) {

            return 'Staff';

        }



        $primary = $roles[0];



        return self::STAFF_ROLE_LABELS[$primary] ?? ucwords(str_replace('_', ' ', $primary));

    }



    /** @return array<string, mixed>|null */

    private static function staffContactForRole(string $roleSlug, int $collegeId): ?array

    {

        if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {

            return $collegeId > 0 ? User::findActiveByRoleAndCollege($roleSlug, $collegeId) : null;

        }



        if ($roleSlug === MonitoringRoles::VPRIDE) {

            return User::findActiveByRoleSlugs([MonitoringRoles::VPRIDE, 'ride_admin']);

        }



        return User::findActiveByRole($roleSlug);

    }

}


