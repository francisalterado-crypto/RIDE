<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Sql;
use PDO;

final class Extension
{
    public static function summary(int $proposalId): array
    {
        $beneficiaries = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(beneficiary_count), 0) FROM community_beneficiaries WHERE proposal_id = ?'
        );
        $beneficiaries->execute([$proposalId]);
        $trained = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(people_trained), 0) FROM impact_metrics WHERE proposal_id = ?'
        );
        $trained->execute([$proposalId]);

        return [
            'beneficiary_groups' => self::count('community_beneficiaries', $proposalId),
            'total_beneficiaries' => (int) $beneficiaries->fetchColumn(),
            'partner_mous' => self::count('partner_mous', $proposalId),
            'people_trained' => (int) $trained->fetchColumn(),
        ];
    }

    private static function count(string $table, int $proposalId): int
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE proposal_id = ?");
        $stmt->execute([$proposalId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return list<array> */
    public static function list(string $table, int $proposalId): array
    {
        $allowed = ['community_beneficiaries', 'partner_mous', 'impact_metrics'];
        if (!in_array($table, $allowed, true)) {
            return [];
        }
        $stmt = Database::pdo()->prepare("SELECT * FROM {$table} WHERE proposal_id = ? ORDER BY created_at DESC");
        $stmt->execute([$proposalId]);
        return $stmt->fetchAll() ?: [];
    }

    public static function create(string $table, int $proposalId, array $data): int
    {
        return match ($table) {
            'community_beneficiaries' => self::insertBeneficiary($proposalId, $data),
            'partner_mous' => self::insertMou($proposalId, $data),
            'impact_metrics' => self::insertImpact($proposalId, $data),
            default => 0,
        };
    }

    /** Accreditation report: extension beneficiaries by year range */
    public static function beneficiaryReport(int $years = 3): array
    {
        $stmt = Database::pdo()->prepare(
            "SELECT p.project_code, p.title, c.name AS college_name, cb.period_year,
                    SUM(cb.beneficiary_count) AS total_beneficiaries
             FROM community_beneficiaries cb
             INNER JOIN proposals p ON p.id = cb.proposal_id
             INNER JOIN colleges c ON c.id = p.college_id
             WHERE p.project_type = 'extension'
               AND p.status IN ('ongoing','completed','approved')
               AND cb.period_year >= " . Sql::currentYear() . ' - ?
             GROUP BY p.id, cb.period_year
             ORDER BY cb.period_year DESC, p.title"
        );
        $stmt->execute([$years - 1]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function insertBeneficiary(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO community_beneficiaries (proposal_id, group_name, beneficiary_count, location, period_year, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId, $d['group_name'], (int) ($d['beneficiary_count'] ?? 0),
            $d['location'] ?? null, $d['period_year'] ?? date('Y'), $d['notes'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    private static function insertMou(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO partner_mous (proposal_id, partner_name, valid_from, valid_until, notes)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId, $d['partner_name'], $d['valid_from'] ?: null, $d['valid_until'] ?: null, $d['notes'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    private static function insertImpact(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO impact_metrics (proposal_id, period_year, people_trained, income_generated, households_served, notes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId, $d['period_year'] ?? date('Y'), (int) ($d['people_trained'] ?? 0),
            (float) ($d['income_generated'] ?? 0), (int) ($d['households_served'] ?? 0), $d['notes'] ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }
}
