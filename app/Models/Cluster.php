<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Cluster
{
    /** Research (RDO) and extension (ESO) group units differently; each unit sits in at most one cluster per track. */
    public const TRACKS = ['research' => 'Research', 'extension' => 'Extension'];

    /** @return list<array> */
    public static function all(?string $track = null): array
    {
        if ($track !== null) {
            $stmt = Database::pdo()->prepare('SELECT * FROM clusters WHERE track = ? ORDER BY name');
            $stmt->execute([$track]);

            return $stmt->fetchAll() ?: [];
        }

        return Database::pdo()->query('SELECT * FROM clusters ORDER BY track, name')->fetchAll() ?: [];
    }

    public static function normalizeTrack(string $track): string
    {
        $track = strtolower(trim($track));

        return array_key_exists($track, self::TRACKS) ? $track : 'extension';
    }

    /** The colleges column that holds membership for a track. */
    public static function memberColumn(string $track): string
    {
        return self::normalizeTrack($track) === 'research' ? 'research_cluster_id' : 'extension_cluster_id';
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM clusters WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(string $name, string $track = 'extension'): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO clusters (name, track) VALUES (?, ?)');
        $stmt->execute([$name, self::normalizeTrack($track)]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function updateDetails(int $id, ?string $leaderName, ?string $notes): void
    {
        Database::pdo()->prepare('UPDATE clusters SET leader_name = ?, notes = ? WHERE id = ?')->execute([$leaderName, $notes, $id]);
    }

    public static function update(int $id, string $name, ?string $track = null): bool
    {
        $cluster = self::findById($id);
        if ($cluster === null) {
            return false;
        }
        $track = $track !== null ? self::normalizeTrack($track) : (string) $cluster['track'];
        if ($track !== (string) $cluster['track']) {
            // Members were assigned under the old track; they do not carry over.
            $old = self::memberColumn((string) $cluster['track']);
            Database::pdo()->prepare("UPDATE colleges SET {$old} = NULL WHERE {$old} = ?")->execute([$id]);
        }
        $stmt = Database::pdo()->prepare('UPDATE clusters SET name = ?, track = ? WHERE id = ?');
        $stmt->execute([$name, $track, $id]);

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
        $cluster = self::findById($id);
        if ($cluster === null) {
            return [];
        }
        $column = self::memberColumn((string) $cluster['track']);
        $stmt = Database::pdo()->prepare("SELECT * FROM colleges WHERE {$column} = ? ORDER BY name");
        $stmt->execute([$id]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Sync cluster membership for the cluster's track: colleges in $collegeIds are assigned to $id
     * (moving them out of another cluster of the same track), any other college in $id is unassigned.
     *
     * @param list<int> $collegeIds
     */
    public static function syncColleges(int $id, array $collegeIds): void
    {
        $cluster = self::findById($id);
        if ($cluster === null) {
            return;
        }
        $column = self::memberColumn((string) $cluster['track']);
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $pdo->prepare("UPDATE colleges SET {$column} = NULL WHERE {$column} = ?")->execute([$id]);

            if ($collegeIds !== []) {
                $placeholders = implode(',', array_fill(0, count($collegeIds), '?'));
                $stmt = $pdo->prepare("UPDATE colleges SET {$column} = ? WHERE id IN ($placeholders)");
                $stmt->execute([$id, ...$collegeIds]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
