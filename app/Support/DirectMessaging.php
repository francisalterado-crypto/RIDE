<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Auth;
use App\Core\Database;
use App\Models\User;

/**
 * Direct messages between faculty and the staff over them: their College Coordinators and Dean, the RIDE
 * coordinators of their college's clusters, the Directors and OVPRIDE. A College Coordinator is a faculty member
 * too, but counts as staff here.
 */
final class DirectMessaging
{
    /** @var list<string> */
    public const STAFF_ROLE_SLUGS = [
        AccessControl::COORDINATOR_RESEARCH,
        AccessControl::COORDINATOR_EXTENSION,
        AccessControl::DEAN,
        AccessControl::RIDE_COORDINATOR_RESEARCH,
        AccessControl::RIDE_COORDINATOR_EXTENSION,
        AccessControl::DIRECTOR_RESEARCH,
        AccessControl::DIRECTOR_EXTENSION,
        AccessControl::VPRIDE,
    ];

    /** @var array<string, string> */
    public const STAFF_ROLE_LABELS = [
        AccessControl::COORDINATOR_RESEARCH => 'College Coordinator - Research',
        AccessControl::COORDINATOR_EXTENSION => 'College Coordinator - Extension',
        AccessControl::DEAN => 'College Dean',
        AccessControl::RIDE_COORDINATOR_RESEARCH => 'Research Coordinator (RDO)',
        AccessControl::RIDE_COORDINATOR_EXTENSION => 'Field Extensionist (ESO)',
        AccessControl::DIRECTOR_RESEARCH => 'RDO Director',
        AccessControl::DIRECTOR_EXTENSION => 'ESO Director',
        AccessControl::VPRIDE => 'OVPRIDE',
    ];

    /** @var list<string> */
    private const COLLEGE_SCOPED_ROLE_SLUGS = [
        AccessControl::COORDINATOR_RESEARCH,
        AccessControl::COORDINATOR_EXTENSION,
        AccessControl::DEAN,
    ];

    /** @var array<string, string> RIDE coordinator role => track of its clusters */
    private const CLUSTER_SCOPED_ROLE_TRACKS = [
        AccessControl::RIDE_COORDINATOR_RESEARCH => 'research',
        AccessControl::RIDE_COORDINATOR_EXTENSION => 'extension',
    ];

    public static function isEnabledForCurrentUser(): bool
    {
        return Auth::hasRole('faculty', AccessControl::ADMIN, ...self::STAFF_ROLE_SLUGS);
    }

    /** @return list<string> */
    public static function roleSlugsForUser(int $userId): array
    {
        $rows = User::roleSlugsForUserId($userId);
        $allowed = array_flip(self::STAFF_ROLE_SLUGS);

        return array_values(array_filter($rows, static fn (string $slug): bool => isset($allowed[$slug])));
    }

    /** Faculty without a staff role (a College Coordinator counts as staff for messaging). */
    public static function isFacultyUser(int $userId): bool
    {
        return in_array('faculty', User::roleSlugsForUserId($userId), true) && self::roleSlugsForUser($userId) === [];
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
        $facultyCollegeId = (int) ($faculty['college_id'] ?? 0);
        foreach (self::roleSlugsForUser($staffId) as $roleSlug) {
            if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {
                if ($facultyCollegeId > 0 && User::hasActiveRoleForCollege($staffId, $roleSlug, $facultyCollegeId)) {
                    return true;
                }
                continue;
            }
            if (isset(self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug])) {
                if (in_array($facultyCollegeId, AccessControl::clusterCollegeIds(self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug], $staffId), true)) {
                    return true;
                }
                continue;
            }

            return true;
        }

        return false;
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
            if ($user === null || (int) $user['id'] === (int) ($facultyUser['id'] ?? 0)) {
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
        $colleges = [];
        foreach ($staffRoles as $roleSlug) {
            if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {
                $colleges[] = (int) ($staffUser['college_id'] ?? 0);
            } elseif (isset(self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug])) {
                array_push($colleges, ...AccessControl::clusterCollegeIds(self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug], $staffId));
            } else {
                return array_values(array_filter(User::allFaculty(), static fn (array $u): bool => self::isFacultyUser((int) $u['id'])));
            }
        }
        $recipients = [];
        foreach (array_unique(array_filter($colleges)) as $collegeId) {
            foreach (User::facultyForCollege($collegeId) as $faculty) {
                if ((int) $faculty['id'] !== $staffId && self::isFacultyUser((int) $faculty['id'])) {
                    $recipients[(int) $faculty['id']] = $faculty;
                }
            }
        }

        return array_values($recipients);
    }

    public static function staffRoleLabelForUser(int $userId): string
    {
        $roles = self::roleSlugsForUser($userId);
        if ($roles === []) {
            return 'Staff';
        }

        return self::STAFF_ROLE_LABELS[$roles[0]] ?? ucwords(str_replace('_', ' ', $roles[0]));
    }

    /** @return array<string, mixed>|null */
    private static function staffContactForRole(string $roleSlug, int $collegeId): ?array
    {
        if (in_array($roleSlug, self::COLLEGE_SCOPED_ROLE_SLUGS, true)) {
            return $collegeId > 0 ? User::findActiveByRoleAndCollege($roleSlug, $collegeId) : null;
        }
        if (isset(self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug])) {
            if ($collegeId <= 0) {
                return null;
            }
            $column = self::CLUSTER_SCOPED_ROLE_TRACKS[$roleSlug] === 'extension' ? 'extension_cluster_id' : 'research_cluster_id';
            $stmt = Database::pdo()->prepare(
                'SELECT u.id, u.first_name, u.last_name, u.email
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.slug = ?
                 INNER JOIN user_clusters uc ON uc.user_id = u.id
                 INNER JOIN colleges c ON c.' . $column . ' = uc.cluster_id AND c.id = ?
                 WHERE u.is_active = 1
                 ORDER BY u.id ASC
                 LIMIT 1'
            );
            $stmt->execute([$roleSlug, $collegeId]);
            $row = $stmt->fetch();

            return $row ?: null;
        }

        return User::findActiveByRole($roleSlug);
    }
}
