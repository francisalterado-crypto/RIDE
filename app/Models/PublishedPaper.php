<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;

final class PublishedPaper
{
    /** @return list<array<string, mixed>> */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM published_papers WHERE user_id = ? ORDER BY publication_date DESC, publication_year DESC, created_at DESC'
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array<string, mixed>> */
    public static function forMonitoring(?int $collegeId = null): array
    {
        $sql = 'SELECT pp.*,
                       u.first_name, u.last_name, u.email, u.program,
                       c.name AS college_name
                FROM published_papers pp
                INNER JOIN users u ON u.id = pp.user_id
                LEFT JOIN colleges c ON c.id = u.college_id
                WHERE u.is_active = 1';
        $params = [];

        if ($collegeId !== null) {
            $sql .= ' AND u.college_id = ?';
            $params[] = $collegeId;
        }

        $sql .= ' ORDER BY pp.publication_date DESC, pp.publication_year DESC, pp.created_at DESC';

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array{total: int, this_year: int, faculty_count: int} */
    public static function stats(?int $collegeId = null): array
    {
        $year = (int) date('Y');
        $sql = 'SELECT COUNT(*) AS total,
                       SUM(CASE WHEN pp.publication_year = ? OR ' . Sql::year('pp.publication_date') . ' = ? THEN 1 ELSE 0 END) AS this_year,
                       COUNT(DISTINCT pp.user_id) AS faculty_count
                FROM published_papers pp
                INNER JOIN users u ON u.id = pp.user_id
                WHERE u.is_active = 1';
        $params = [$year, $year];

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
            'faculty_count' => (int) ($row['faculty_count'] ?? 0),
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM published_papers WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO published_papers
             (user_id, title, authors, journal_name, publication_date, publication_year, doi, indexing, status, link, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $data['title'],
            $data['authors'] ?: null,
            $data['journal_name'],
            $data['publication_date'] ?: null,
            $data['publication_year'] ?: null,
            $data['doi'] ?: null,
            $data['indexing'] ?: null,
            $data['status'] ?? 'published',
            $data['link'] ?: null,
            $data['notes'] ?: null,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        ScholarlyAttachment::deleteForRecord(ScholarlyAttachment::TYPE_PAPER, $id);

        $stmt = Database::pdo()->prepare('DELETE FROM published_papers WHERE id = ?');

        return $stmt->execute([$id]);
    }
}
