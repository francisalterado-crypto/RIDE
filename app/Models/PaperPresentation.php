<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;

final class PaperPresentation
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM paper_presentations WHERE user_id = ? ORDER BY presentation_date DESC, created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public static function forMonitoring(?int $collegeId = null): array
    {
        $sql = 'SELECT pr.*,
                       u.first_name, u.last_name, u.email, u.program,
                       c.name AS college_name
                FROM paper_presentations pr
                INNER JOIN users u ON u.id = pr.user_id
                LEFT JOIN colleges c ON c.id = u.college_id
                WHERE u.is_active = 1';
        $params = [];

        if ($collegeId !== null) {
            $sql .= ' AND u.college_id = ?';
            $params[] = $collegeId;
        }

        $sql .= ' ORDER BY pr.presentation_date DESC, pr.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array{total: int, this_year: int, international: int, faculty_count: int} */
    public static function stats(?int $collegeId = null): array
    {
        $year = (int) date('Y');
        $sql = 'SELECT COUNT(*) AS total,
                       SUM(CASE WHEN " . Sql::year('pr.presentation_date') . ' = ? THEN 1 ELSE 0 END) AS this_year,
                       SUM(CASE WHEN pr.is_international = 1 THEN 1 ELSE 0 END) AS international,
                       COUNT(DISTINCT pr.user_id) AS faculty_count
                FROM paper_presentations pr
                INNER JOIN users u ON u.id = pr.user_id
                WHERE u.is_active = 1';
        $params = [$year];

        if ($collegeId !== null) {
            $sql .= ' AND u.college_id = ?';
            $params[] = $collegeId;
        }

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'this_year' => (int) ($row['this_year'] ?? 0),
            'international' => (int) ($row['international'] ?? 0),
            'faculty_count' => (int) ($row['faculty_count'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM paper_presentations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO paper_presentations
             (user_id, title, conference_name, presentation_type, presentation_date, location, is_international, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $data['title'],
            $data['conference_name'],
            $data['presentation_type'] ?? 'oral',
            $data['presentation_date'] ?: null,
            $data['location'] ?: null,
            !empty($data['is_international']) ? 1 : 0,
            $data['notes'] ?: null,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        ScholarlyAttachment::deleteForRecord(ScholarlyAttachment::TYPE_PRESENTATION, $id);

        $stmt = Database::pdo()->prepare('DELETE FROM paper_presentations WHERE id = ?');

        return $stmt->execute([$id]);
    }
}
