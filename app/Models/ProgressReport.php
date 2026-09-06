<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;

final class ProgressReport
{
    /** @return list<array> */
    public static function forProposal(int $proposalId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT pr.*, u.first_name, u.last_name FROM progress_reports pr
             INNER JOIN users u ON u.id = pr.user_id
             WHERE pr.proposal_id = ? ORDER BY pr.created_at DESC'
        );
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM progress_reports WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $proposalId, int $userId, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO progress_reports (proposal_id, user_id, period_label, report_type, narrative, financial_summary, outputs, due_date, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId,
            $userId,
            $data['period_label'],
            $data['report_type'] ?? 'quarterly',
            $data['narrative'] ?? null,
            $data['financial_summary'] ?? null,
            $data['outputs'] ?? null,
            $data['due_date'] ?? null,
            'draft',
        ]);
        $reportId = (int) Database::pdo()->lastInsertId();
        self::saveFinancialLines($reportId, $data['financial_lines'] ?? []);
        return $reportId;
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE progress_reports SET period_label = ?, report_type = ?, narrative = ?, financial_summary = ?, outputs = ?, due_date = ?
             WHERE id = ? AND status = \'draft\''
        );
        $stmt->execute([
            $data['period_label'],
            $data['report_type'] ?? 'quarterly',
            $data['narrative'] ?? null,
            $data['financial_summary'] ?? null,
            $data['outputs'] ?? null,
            $data['due_date'] ?? null,
            $id,
        ]);
        Database::pdo()->prepare('DELETE FROM report_financial_lines WHERE report_id = ?')->execute([$id]);
        self::saveFinancialLines($id, $data['financial_lines'] ?? []);
    }

    public static function submit(int $id): void
    {
        Database::pdo()->prepare(
            "UPDATE progress_reports SET status = 'submitted', submitted_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    /** @param list<array{description: string, budgeted: float, spent: float}> $lines */
    private static function saveFinancialLines(int $reportId, array $lines): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO report_financial_lines (report_id, description, budgeted, spent) VALUES (?, ?, ?, ?)'
        );
        foreach ($lines as $line) {
            if (trim($line['description'] ?? '') === '') {
                continue;
            }
            $stmt->execute([
                $reportId,
                $line['description'],
                (float) ($line['budgeted'] ?? 0),
                (float) ($line['spent'] ?? 0),
            ]);
        }
    }

    /** @return list<array> */
    public static function financialLines(int $reportId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM report_financial_lines WHERE report_id = ? ORDER BY id'
        );
        $stmt->execute([$reportId]);
        return $stmt->fetchAll() ?: [];
    }

    /** @return list<array> */
    public static function overdue(?int $userId = null, ?int $collegeId = null): array
    {
        $sql = "SELECT pr.*, p.title AS project_title, p.project_code
                FROM progress_reports pr
                INNER JOIN proposals p ON p.id = pr.proposal_id
                WHERE pr.status = 'draft' AND pr.due_date IS NOT NULL AND pr.due_date < " . Sql::currentDate();
        $params = [];
        if ($userId) {
            $sql .= ' AND p.user_id = ?';
            $params[] = $userId;
        } elseif ($collegeId) {
            $sql .= ' AND p.college_id = ?';
            $params[] = $collegeId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }
}
