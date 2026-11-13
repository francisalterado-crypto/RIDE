<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Degree programs offered by a college (BS in Civil Engineering, ...). Users and proposals reference these by id. */
final class AcademicProgram
{
    /** @return list<array> */
    public static function all(?int $collegeId = null, bool $activeOnly = false): array
    {
        $sql = 'SELECT ap.*, c.code AS college_code, c.name AS college_name
                FROM academic_programs ap
                LEFT JOIN colleges c ON c.id = ap.college_id';
        $where = [];
        $params = [];
        if ($collegeId !== null) {
            $where[] = 'ap.college_id = ?';
            $params[] = $collegeId;
        }
        if ($activeOnly) {
            $where[] = 'ap.is_active = 1';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY c.code, ap.name');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM academic_programs WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM academic_programs WHERE name = ?');
        $stmt->execute([trim($name)]);

        return $stmt->fetch() ?: null;
    }

    public static function nameExists(string $name, ?int $exceptId = null): bool
    {
        $stmt = Database::pdo()->prepare('SELECT id FROM academic_programs WHERE name = ?');
        $stmt->execute([trim($name)]);
        $id = $stmt->fetchColumn();

        return $id !== false && (int) $id !== (int) $exceptId;
    }

    public static function create(?int $collegeId, string $name): int
    {
        Database::pdo()->prepare('INSERT INTO academic_programs (college_id, name) VALUES (?, ?)')
            ->execute([$collegeId, trim($name)]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, ?int $collegeId, string $name, bool $active): void
    {
        $pdo = Database::pdo();
        $old = self::find($id);
        $pdo->prepare('UPDATE academic_programs SET college_id = ?, name = ?, is_active = ? WHERE id = ?')
            ->execute([$collegeId, trim($name), $active ? 1 : 0, $id]);
        // users.program keeps the display name for older screens; follow renames.
        if ($old !== null && (string) $old['name'] !== trim($name)) {
            $pdo->prepare('UPDATE users SET program = ? WHERE academic_program_id = ?')->execute([trim($name), $id]);
        }
    }

    public static function usageCount(int $id): int
    {
        $pdo = Database::pdo();
        $count = 0;
        foreach (['users', 'proposals', 'extension_projects'] as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE academic_program_id = ?");
            $stmt->execute([$id]);
            $count += (int) $stmt->fetchColumn();
        }

        return $count;
    }

    public static function delete(int $id): bool
    {
        if (self::usageCount($id) > 0) {
            return false;
        }
        $stmt = Database::pdo()->prepare('DELETE FROM academic_programs WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Active programs grouped for a <select> with <optgroup> per college. A college's own programs come first.
     *
     * @return array<string, list<array{id: int, name: string}>>
     */
    public static function groupedOptions(?int $preferredCollegeId = null): array
    {
        $groups = [];
        foreach (self::all(null, true) as $row) {
            $label = (string) ($row['college_name'] ?? '') !== '' ? (string) $row['college_name'] : 'Other / University-wide';
            $groups[$label][] = ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'college_id' => (int) ($row['college_id'] ?? 0)];
        }
        if ($preferredCollegeId !== null) {
            uksort($groups, static function (string $a, string $b) use ($groups, $preferredCollegeId): int {
                $aMine = ($groups[$a][0]['college_id'] ?? 0) === $preferredCollegeId;
                $bMine = ($groups[$b][0]['college_id'] ?? 0) === $preferredCollegeId;

                return $aMine === $bMine ? strcmp($a, $b) : ($aMine ? -1 : 1);
            });
        }

        return $groups;
    }

    /**
     * Resolve a posted academic_program_id into [id, name] for saving on users/proposals.
     *
     * @return array{0: ?int, 1: ?string}
     */
    public static function resolvePosted(mixed $value): array
    {
        $id = (int) $value;
        if ($id <= 0) {
            return [null, null];
        }
        $row = self::find($id);

        return $row === null ? [null, null] : [(int) $row['id'], (string) $row['name']];
    }
}
