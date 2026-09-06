<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;

final class Milestone
{
    public static function seedDefaults(int $proposalId): void
    {
        $defaults = [
            ['Project kickoff', 30],
            ['Mid-term progress review', 180],
            ['Final report submission', 365],
        ];
        $stmt = Database::pdo()->prepare(
            'INSERT INTO milestones (proposal_id, title, due_date) VALUES (?, ?, ' . Sql::dateAddDaysFromToday('?') . ')'
        );
        foreach ($defaults as [$title, $days]) {
            $stmt->execute([$proposalId, $title, $days]);
        }
        self::refreshOverdue();
    }

    public static function refreshOverdue(): void
    {
        Database::pdo()->exec(
            "UPDATE milestones SET status = 'overdue'
             WHERE status = 'pending' AND due_date < " . Sql::currentDate()
        );
    }

    /** @return list<array> */
    public static function forProposal(int $proposalId): array
    {
        self::refreshOverdue();
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM milestones WHERE proposal_id = ? ORDER BY due_date ASC'
        );
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(int $proposalId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO milestones (proposal_id, title, description, due_date) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId,
            $data['title'],
            $data['description'] ?? null,
            $data['due_date'],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function complete(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE milestones SET status = 'completed', completed_at = " . Sql::currentDate() . ' WHERE id = ?'
        )->execute([$id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM milestones WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return list<array> */
    public static function overdueForUser(?int $collegeId, ?int $userId, bool $all = false): array
    {
        self::refreshOverdue();
        $sql = "SELECT m.*, p.title AS project_title, p.project_code
                FROM milestones m
                INNER JOIN proposals p ON p.id = m.proposal_id
                WHERE m.status = 'overdue' AND p.status IN ('ongoing','approved')";
        $params = [];
        if (!$all && $userId) {
            $sql .= ' AND p.user_id = ?';
            $params[] = $userId;
        } elseif (!$all && $collegeId) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        $sql .= ' ORDER BY m.due_date ASC LIMIT 20';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
