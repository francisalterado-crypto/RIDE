<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class InnovationRecord
{
    public static function summary(int $proposalId): array
    {
        return [
            'ip_disclosures' => self::count('ip_disclosures', $proposalId),
            'patents' => self::count('patents', $proposalId),
            'technology_transfers' => self::count('technology_transfers', $proposalId),
            'prototypes' => self::count('prototypes', $proposalId),
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
        $allowed = ['ip_disclosures', 'patents', 'technology_transfers', 'prototypes'];
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
            'ip_disclosures' => self::insertIp($proposalId, $data),
            'patents' => self::insertPatent($proposalId, $data),
            'technology_transfers' => self::insertTransfer($proposalId, $data),
            'prototypes' => self::insertPrototype($proposalId, $data),
            default => 0,
        };
    }

    private static function insertIp(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO ip_disclosures (proposal_id, title, disclosure_date, status, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$proposalId, $d['title'], $d['disclosure_date'] ?: null, $d['status'] ?? 'draft', $d['notes'] ?? null]);
        return (int) Database::pdo()->lastInsertId();
    }

    private static function insertPatent(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO patents (proposal_id, title, application_no, status, filed_date, granted_date) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $proposalId, $d['title'], $d['application_no'] ?? null, $d['status'] ?? 'filed',
            $d['filed_date'] ?: null, $d['granted_date'] ?: null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    private static function insertTransfer(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO technology_transfers (proposal_id, partner_name, transfer_date, status, notes) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$proposalId, $d['partner_name'], $d['transfer_date'] ?: null, $d['status'] ?? 'negotiating', $d['notes'] ?? null]);
        return (int) Database::pdo()->lastInsertId();
    }

    private static function insertPrototype(int $proposalId, array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO prototypes (proposal_id, name, stage, description) VALUES (?, ?, ?, ?)'
        );
        $name = $d['name'] ?? $d['title'] ?? 'Prototype';
        $stmt->execute([$proposalId, $name, $d['stage'] ?? $d['status'] ?? 'concept', $d['description'] ?? $d['notes'] ?? null]);
        return (int) Database::pdo()->lastInsertId();
    }
}
