<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Sql;

final class Notification
{
    public static function notifyProjectLeader(int $userId, string $title, string $message, string $link): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $title, $message, $link]);
    }

    /**
     * Tells whoever acts on a record's current step that it is waiting for them: the College Coordinator or
     * Dean of its college, the RIDE coordinator of its college's cluster, or the Director / OVPRIDE.
     */
    public static function notifyStepHolders(array $proposal, string $step): void
    {
        $collegeId = (int) ($proposal['college_id'] ?? 0);
        $sql = 'SELECT DISTINCT u.id FROM users u
                INNER JOIN user_roles ur ON ur.user_id = u.id
                INNER JOIN roles r ON r.id = ur.role_id AND r.slug = ?';
        $params = [$step];
        if (in_array($step, [\App\Support\AccessControl::COORDINATOR_RESEARCH, \App\Support\AccessControl::COORDINATOR_EXTENSION, \App\Support\AccessControl::DEAN], true)) {
            $sql .= ' WHERE (ur.college_id = ? OR u.college_id = ?)';
            array_push($params, $collegeId, $collegeId);
        } elseif (in_array($step, \App\Support\AccessControl::RIDE_COORDINATOR_STEPS, true)) {
            $column = $step === \App\Support\AccessControl::RIDE_COORDINATOR_EXTENSION ? 'extension_cluster_id' : 'research_cluster_id';
            $sql .= ' INNER JOIN user_clusters uc ON uc.user_id = u.id
                      INNER JOIN colleges c ON c.' . $column . ' = uc.cluster_id AND c.id = ?
                      WHERE 1 = 1';
            $params[] = $collegeId;
        } else {
            $sql .= ' WHERE 1 = 1';
        }
        $sql .= ' AND u.is_active = 1 AND u.id <> ?';
        $params[] = (int) ($proposal['user_id'] ?? 0);
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $title = 'Waiting for your review';
        $message = '"' . (string) ($proposal['title'] ?? '') . '" is waiting at ' . \App\Support\AccessControl::stepLabel($step) . '.';
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [] as $userId) {
            self::notifyProjectLeader((int) $userId, $title, $message, 'proposals/' . (int) ($proposal['id'] ?? 0));
        }
    }

    public static function checkOverdueAlerts(): void
    {
        Milestone::refreshOverdue();
        $stmt = Database::pdo()->query(
            "SELECT m.id, m.title, p.id AS proposal_id, p.title AS project_title, p.user_id
             FROM milestones m
             INNER JOIN proposals p ON p.id = m.proposal_id
             WHERE m.status = 'overdue' AND p.status = 'ongoing'"
        );
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $exists = Database::pdo()->prepare(
                "SELECT 1 FROM notifications WHERE user_id = ? AND link = ? AND title LIKE 'Overdue milestone%' AND created_at > " . Sql::nowMinusDays(7) . ' LIMIT 1'
            );
            $link = 'projects/' . $row['proposal_id'];
            $exists->execute([(int) $row['user_id'], $link]);
            if ($exists->fetchColumn()) {
                continue;
            }
            self::notifyProjectLeader(
                (int) $row['user_id'],
                'Overdue milestone: ' . $row['title'],
                'Milestone "' . $row['title'] . '" on project "' . $row['project_title'] . '" is overdue.',
                $link
            );
        }
    }

    /** @return list<array> */
    public static function forCurrentUser(int $limit = 10): array
    {
        $userId = (int) (Auth::user()['id'] ?? 0);
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public static function unreadCount(): int
    {
        $userId = (int) (Auth::user()['id'] ?? 0);
        $stmt = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0'
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(int $id): void
    {
        $userId = (int) (Auth::user()['id'] ?? 0);
        Database::pdo()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
    }
}
