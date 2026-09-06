<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;
use PDO;

final class User
{
    /** @return list<array<string, mixed>> */
    public static function allActiveWithRoles(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.college_id, u.program, u.campus_id, u.created_at,
                    c.name AS college_name,
                    ' . Sql::groupConcat('r.name', ', ', 'r.name', true) . ' AS role_names,
                    ' . Sql::groupConcat('r.slug', ', ', 'r.name', true) . ' AS role_slugs
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.is_active = 1
             GROUP BY u.id, u.email, u.first_name, u.last_name, u.college_id, u.program, u.campus_id, u.created_at, c.name
             ORDER BY u.created_at DESC, u.id DESC'
        );

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public static function allInactiveWithRoles(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.college_id, u.program, u.campus_id, u.created_at,
                    c.name AS college_name,
                    ' . Sql::groupConcat('r.name', ', ', 'r.name', true) . ' AS role_names,
                    ' . Sql::groupConcat('r.slug', ', ', 'r.name', true) . ' AS role_slugs
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             INNER JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.is_active = 0
             GROUP BY u.id, u.email, u.first_name, u.last_name, u.college_id, u.program, u.campus_id, u.created_at, c.name
             ORDER BY u.created_at DESC, u.id DESC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmailForAuth(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function hasRoles(int $userId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM user_roles WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function allPendingRegistrations(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.college_id, u.program, u.campus_id, u.created_at,
                    c.name AS college_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.is_active = 0
               AND NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id)
             ORDER BY u.created_at ASC, u.id ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, c.name AS college_name, cp.name AS campus_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             LEFT JOIN campuses cp ON cp.id = u.campus_id
             WHERE u.id = ? AND u.is_active = 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findActiveByRoleAndCollege(string $roleSlug, int $collegeId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = ?
             WHERE u.is_active = 1
               AND (ur.college_id = ? OR u.college_id = ?)
             ORDER BY (ur.college_id = ?) DESC, u.id ASC
             LIMIT 1'
        );
        $stmt->execute([$roleSlug, $collegeId, $collegeId, $collegeId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findActiveByRole(string $roleSlug): ?array
    {
        return self::findActiveByRoleSlugs([$roleSlug]);
    }

    /**
     * First active user matching any role slug (in priority order).
     *
     * @param list<string> $roleSlugs
     */
    public static function findActiveByRoleSlugs(array $roleSlugs): ?array
    {
        foreach ($roleSlugs as $roleSlug) {
            if ($roleSlug === '') {
                continue;
            }
            $stmt = Database::pdo()->prepare(
                'SELECT u.id, u.first_name, u.last_name, u.email
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 INNER JOIN roles r ON r.id = ur.role_id AND r.slug = ?
                 WHERE u.is_active = 1
                 ORDER BY u.id ASC
                 LIMIT 1'
            );
            $stmt->execute([$roleSlug]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function roleSlugsForUserId(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT DISTINCT r.slug
             FROM user_roles ur
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE ur.user_id = ?
             ORDER BY r.name ASC'
        );
        $stmt->execute([$userId]);
        $slugs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return is_array($slugs) ? array_values(array_filter($slugs, 'is_string')) : [];
    }

    public static function hasActiveRoleForCollege(int $userId, string $roleSlug, int $collegeId): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = ?
             WHERE u.id = ?
               AND u.is_active = 1
               AND (ur.college_id = ? OR u.college_id = ?)
             LIMIT 1'
        );
        $stmt->execute([$roleSlug, $userId, $collegeId, $collegeId]);

        return (bool) $stmt->fetchColumn();
    }

    /** @return list<array<string, mixed>> */
    public static function facultyForCollege(int $collegeId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.program, u.college_id, c.name AS college_name
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = \'faculty\'
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.is_active = 1 AND u.college_id = ?
             ORDER BY u.last_name ASC, u.first_name ASC'
        );
        $stmt->execute([$collegeId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function findActiveWithPrimaryRoleById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, c.name AS college_name, cp.name AS campus_name,
                    r.id AS role_id, r.slug AS role_slug, r.name AS role_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             LEFT JOIN campuses cp ON cp.id = u.campus_id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.id = ? AND u.is_active = 1
             ORDER BY r.name ASC
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findAnyById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, c.name AS college_name, cp.name AS campus_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             LEFT JOIN campuses cp ON cp.id = u.campus_id
             WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function emailExists(string $email): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);

        return (bool) $stmt->fetchColumn();
    }

    public static function emailExistsExcept(string $email, int $userId): bool
    {
        $stmt = Database::pdo()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $userId]);

        return (bool) $stmt->fetchColumn();
    }

    public static function create(array $data, bool $active = true): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (email, password_hash, first_name, last_name, college_id, program, campus_id, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['first_name'],
            $data['last_name'],
            $data['college_id'] ?: null,
            ($data['program'] ?? '') !== '' ? $data['program'] : null,
            $data['campus_id'] ?: null,
            $active ? 1 : 0,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function findPendingById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.*, c.name AS college_name
             FROM users u
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.id = ?
               AND u.is_active = 0
               AND NOT EXISTS (SELECT 1 FROM user_roles ur WHERE ur.user_id = u.id)
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function approveRegistration(int $id, int $roleId, ?int $collegeId, ?string $program = null): bool
    {
        if (self::findPendingById($id) === null) {
            return false;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $update = $pdo->prepare(
                'UPDATE users
                 SET college_id = ?, program = ?, is_active = 1
                 WHERE id = ? AND is_active = 0'
            );
            $update->execute([
                $collegeId,
                ($program ?? '') !== '' ? $program : null,
                $id,
            ]);

            if ($update->rowCount() === 0) {
                $pdo->rollBack();

                return false;
            }

            $insert = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, college_id) VALUES (?, ?, ?)'
            );
            $insert->execute([$id, $roleId, $collegeId]);

            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    public static function rejectRegistration(int $id): bool
    {
        if (self::findPendingById($id) === null) {
            return false;
        }

        $stmt = Database::pdo()->prepare('DELETE FROM users WHERE id = ? AND is_active = 0');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function assignRole(int $userId, int $roleId, ?int $collegeId = null): void
    {
        $stmt = Database::pdo()->prepare(
            Sql::insertIgnore('INSERT INTO user_roles (user_id, role_id, college_id) VALUES (?, ?, ?)', 'user_id, role_id')
        );
        $stmt->execute([$userId, $roleId, $collegeId]);
    }

    public static function replaceRoles(int $userId, int $roleId, ?int $collegeId = null): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $delete = $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?');
            $delete->execute([$userId]);

            $insert = $pdo->prepare(
                'INSERT INTO user_roles (user_id, role_id, college_id) VALUES (?, ?, ?)'
            );
            $insert->execute([$userId, $roleId, $collegeId]);

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }
    }

    /** @return list<string> */
    public static function roleSlugs(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT r.slug FROM roles r
             INNER JOIN user_roles ur ON ur.role_id = r.id
             WHERE ur.user_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public static function hasPermission(int $userId, string $permission): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = ? AND p.slug = ? LIMIT 1'
        );
        $stmt->execute([$userId, $permission]);
        return (bool) $stmt->fetchColumn();
    }

    public static function roleIdBySlug(string $slug): ?int
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        return $id ? (int) $id : null;
    }

    public static function updateProfile(int $id, array $data): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users
             SET email = ?, first_name = ?, last_name = ?, college_id = ?, program = ?, campus_id = ?
             WHERE id = ? AND is_active = 1'
        );
        $stmt->execute([
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $data['college_id'] ?: null,
            ($data['program'] ?? '') !== '' ? $data['program'] : null,
            $data['campus_id'] ?: null,
            $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function updatePassword(int $id, string $password): bool
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ? AND is_active = 1'
        );
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function deactivate(int $id): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND is_active = 1');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function activate(int $id): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET is_active = 1 WHERE id = ? AND is_active = 0');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    public static function countActiveUsersByRoleSlug(string $slug): int
    {
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(DISTINCT u.id)
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id
             WHERE u.is_active = 1 AND r.slug = ?'
        );
        $stmt->execute([$slug]);

        return (int) $stmt->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public static function findActiveFacultyById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.program, u.college_id, c.name AS college_name
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = \'faculty\'
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.id = ? AND u.is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public static function allFaculty(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.program, u.college_id, c.name AS college_name
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = \'faculty\'
             LEFT JOIN colleges c ON c.id = u.college_id
             WHERE u.is_active = 1
             ORDER BY c.name ASC, u.last_name ASC, u.first_name ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public static function researchSubmitters(?string $projectType = null): array
    {
        $types = $projectType !== null && in_array($projectType, \App\Support\MonitoringRoles::MONITORING_TYPES, true)
            ? [$projectType]
            : \App\Support\MonitoringRoles::MONITORING_TYPES;
        $placeholders = implode(',', array_fill(0, count($types), '?'));

        $stmt = Database::pdo()->prepare(
            'SELECT u.id, u.email, u.first_name, u.last_name, c.name AS college_name,
                    COUNT(p.id) AS total_submissions,
                    SUM(CASE WHEN p.status IN (\'submitted\', \'under_review\', \'returned\') THEN 1 ELSE 0 END) AS active_submissions,
                    SUM(CASE WHEN p.status IN (\'ongoing\', \'approved\', \'completed\') THEN 1 ELSE 0 END) AS approved_submissions,
                    MAX(p.updated_at) AS last_activity
             FROM users u
             INNER JOIN user_roles ur ON ur.user_id = u.id
             INNER JOIN roles r ON r.id = ur.role_id AND r.slug = \'faculty\'
             LEFT JOIN colleges c ON c.id = u.college_id
             LEFT JOIN proposals p ON p.user_id = u.id AND p.project_type IN (' . $placeholders . ')
             WHERE u.is_active = 1
             GROUP BY u.id, u.email, u.first_name, u.last_name, c.name
             ORDER BY c.name ASC, u.last_name ASC, u.first_name ASC'
        );
        $stmt->execute($types);

        return $stmt->fetchAll() ?: [];
    }
}
