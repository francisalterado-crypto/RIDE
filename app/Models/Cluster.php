<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Cluster
{
    /** @return list<array> */
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM clusters ORDER BY name')->fetchAll() ?: [];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM clusters WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $name): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO clusters (name) VALUES (?)');
        $stmt->execute([$name]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name): bool
    {
        $stmt = Database::pdo()->prepare('UPDATE clusters SET name = ? WHERE id = ?');
        $stmt->execute([$name, $id]);

        return $stmt->rowCount() > 0;
    }

    public static function nameExists(string $name, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM clusters WHERE name = ?';
        $params = [$name];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = Database::pdo()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::pdo()->prepare('DELETE FROM clusters WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /** @return list<array> colleges currently assigned to this cluster */
    public static function colleges(int $id): array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM colleges WHERE cluster_id = ? ORDER BY name');
        $stmt->execute([$id]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Sync cluster membership: colleges in $collegeIds are assigned to $id,
     * any college currently in $id but not in $collegeIds is unassigned.
     *
     * @param list<int> $collegeIds
     */
    public static function syncColleges(int $id, array $collegeIds): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('UPDATE colleges SET cluster_id = NULL WHERE cluster_id = ?')->execute([$id]);

            if ($collegeIds !== []) {
                $placeholders = implode(',', array_fill(0, count($collegeIds), '?'));
                $stmt = $pdo->prepare("UPDATE colleges SET cluster_id = ? WHERE id IN ($placeholders)");
                $stmt->execute([$id, ...$collegeIds]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
