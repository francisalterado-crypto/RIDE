<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * A college's extension program from the approved RIDE agenda (e.g. CET: THERMACAD). Projects are proposed under
 * a program; a program is not the same as the college's academic (degree) program.
 */
final class ExtensionProgram
{
    /** @return list<array> */
    public static function all(?int $collegeId = null, bool $activeOnly = false): array
    {
        $sql = 'SELECT ep.*, c.code AS college_code, c.name AS college_name,
                       (SELECT COUNT(*) FROM extension_projects ex WHERE ex.extension_program_id = ep.id) AS project_count
                FROM extension_programs ep
                LEFT JOIN colleges c ON c.id = ep.college_id';
        $where = [];
        $params = [];
        if ($collegeId !== null) {
            $where[] = 'ep.college_id = ?';
            $params[] = $collegeId;
        }
        if ($activeOnly) {
            $where[] = 'ep.is_active = 1';
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY c.code, COALESCE(ep.acronym, ep.name)');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ep.*, c.code AS college_code, c.name AS college_name
             FROM extension_programs ep LEFT JOIN colleges c ON c.id = ep.college_id WHERE ep.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** "THERMACAD - Thermal System, ..." or just the name when there is no acronym. */
    public static function label(array $program): string
    {
        $acronym = trim((string) ($program['acronym'] ?? ''));
        $name = trim((string) ($program['name'] ?? ''));

        return $acronym !== '' && strcasecmp($acronym, $name) !== 0 ? $acronym . ' - ' . $name : $name;
    }

    public static function nameExists(?int $collegeId, string $name, ?int $exceptId = null): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id FROM extension_programs WHERE ' . ($collegeId === null ? 'college_id IS NULL' : 'college_id = ?') . ' AND name = ?'
        );
        $stmt->execute($collegeId === null ? [trim($name)] : [$collegeId, trim($name)]);
        $id = $stmt->fetchColumn();

        return $id !== false && (int) $id !== (int) $exceptId;
    }

    /** @param array{college_id: ?int, name: string, acronym: ?string, description: ?string, agenda_period: ?string, is_active?: bool} $data */
    public static function create(array $data): int
    {
        Database::pdo()->prepare(
            'INSERT INTO extension_programs (college_id, name, acronym, description, agenda_period, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([
            $data['college_id'], $data['name'], $data['acronym'], $data['description'], $data['agenda_period'],
            ($data['is_active'] ?? true) ? 1 : 0,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array{college_id: ?int, name: string, acronym: ?string, description: ?string, agenda_period: ?string, is_active: bool} $data */
    public static function update(int $id, array $data): void
    {
        Database::pdo()->prepare(
            'UPDATE extension_programs SET college_id = ?, name = ?, acronym = ?, description = ?, agenda_period = ?, is_active = ? WHERE id = ?'
        )->execute([
            $data['college_id'], $data['name'], $data['acronym'], $data['description'], $data['agenda_period'],
            $data['is_active'] ? 1 : 0, $id,
        ]);
    }

    public static function deleteBlockingReason(int $id): ?string
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE extension_program_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return 'Proposals are filed under this program. Mark it inactive instead.';
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM extension_projects WHERE extension_program_id = ?');
        $stmt->execute([$id]);
        if ((int) $stmt->fetchColumn() > 0) {
            return 'This program still has projects. Move or delete them first, or mark the program inactive.';
        }

        return null;
    }

    public static function delete(int $id): bool
    {
        if (self::deleteBlockingReason($id) !== null) {
            return false;
        }
        $stmt = Database::pdo()->prepare('DELETE FROM extension_programs WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
