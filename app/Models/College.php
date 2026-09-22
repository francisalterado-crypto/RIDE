<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class College
{
    /** @return list<array> */
    public static function all(): array
    {
        return Database::pdo()->query(
            'SELECT colleges.*, clusters.name AS cluster_name
             FROM colleges
             LEFT JOIN clusters ON clusters.id = colleges.cluster_id
             ORDER BY colleges.name'
        )->fetchAll() ?: [];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT colleges.*, clusters.name AS cluster_name
             FROM colleges
             LEFT JOIN clusters ON clusters.id = colleges.cluster_id
             WHERE colleges.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $code, string $name, ?int $clusterId = null): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO colleges (code, name, cluster_id) VALUES (?, ?, ?)');
        $stmt->execute([$code, $name, $clusterId]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $code, string $name, ?int $clusterId = null): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE colleges SET code = ?, name = ?, cluster_id = ? WHERE id = ?');
        $stmt->execute([$code, $name, $clusterId, $id]);

        return $stmt->rowCount() > 0;
    }

    public static function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM colleges WHERE code = ?';
        $params = [$code];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = Database::pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /** @return array{users: int, proposals: int, roles: int} */
    public static function usageCounts(int $id): array
    {
        $pdo = Database::pdo();

        $users = $pdo->prepare('SELECT COUNT(*) FROM users WHERE college_id = ?');
        $users->execute([$id]);

        $proposals = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE college_id = ?');
        $proposals->execute([$id]);

        $roles = $pdo->prepare('SELECT COUNT(*) FROM user_roles WHERE college_id = ?');
        $roles->execute([$id]);

        return [
            'users' => (int) $users->fetchColumn(),
            'proposals' => (int) $proposals->fetchColumn(),
            'roles' => (int) $roles->fetchColumn(),
        ];
    }

    public static function deleteBlockingReason(int $id): ?string
    {
        $usage = self::usageCounts($id);

        if ($usage['proposals'] > 0) {
            return 'This college has linked proposals and cannot be deleted.';
        }

        if ($usage['users'] > 0) {
            return 'This college is assigned to one or more accounts. Reassign those accounts first.';
        }

        if ($usage['roles'] > 0) {
            return 'This college is assigned to one or more role records. Reassign those accounts first.';
        }

        return null;
    }

    public static function delete(int $id): bool
    {
        if (self::deleteBlockingReason($id) !== null) {
            return false;
        }

        $stmt = Database::pdo()->prepare('DELETE FROM colleges WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /** @return list<array> */
    public static function campuses(int $collegeId): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM campuses WHERE college_id = ? ORDER BY name');
        $stmt->execute([$collegeId]);
        return $stmt->fetchAll() ?: [];
    }
}
