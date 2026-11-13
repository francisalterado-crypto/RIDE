<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Catalog of extension projects under a program (from the ESO monitoring sheet or added by the admin), with the
 * planned activities of each. A RIDE proposal and the Conducted Activities entries point to a catalog project, so a
 * project's paperwork and its actual activities can be traced back to program, college and cluster.
 */
final class ExtensionProject
{
    public const STATUSES = ['planned' => 'Planned', 'ongoing' => 'Ongoing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    /**
     * @param array{college_id?: ?int, program_id?: ?int, year?: ?int, status?: ?string} $filters
     * @return list<array>
     */
    public static function all(array $filters = []): array
    {
        $sql = 'SELECT ex.*, ep.name AS program_name, ep.acronym AS program_acronym,
                       c.code AS college_code, c.name AS college_name, ap.name AS academic_program_name,
                       (SELECT COUNT(*) FROM extension_project_activities pa WHERE pa.extension_project_id = ex.id) AS activity_count,
                       (SELECT COUNT(*) FROM extension_activities ea WHERE ea.extension_project_id = ex.id) AS conducted_count
                FROM extension_projects ex
                INNER JOIN extension_programs ep ON ep.id = ex.extension_program_id
                LEFT JOIN colleges c ON c.id = ex.college_id
                LEFT JOIN academic_programs ap ON ap.id = ex.academic_program_id';
        $where = [];
        $params = [];
        foreach (['college_id' => 'ex.college_id', 'program_id' => 'ex.extension_program_id', 'year' => 'ex.year', 'status' => 'ex.status'] as $key => $column) {
            if (($filters[$key] ?? null) !== null && $filters[$key] !== '') {
                $where[] = $column . ' = ?';
                $params[] = $filters[$key];
            }
        }
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY c.code, COALESCE(ep.acronym, ep.name), ex.title, ex.campus');
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ex.*, ep.name AS program_name, ep.acronym AS program_acronym,
                    c.code AS college_code, c.name AS college_name, ap.name AS academic_program_name
             FROM extension_projects ex
             INNER JOIN extension_programs ep ON ep.id = ex.extension_program_id
             LEFT JOIN colleges c ON c.id = ex.college_id
             LEFT JOIN academic_programs ap ON ap.id = ex.academic_program_id
             WHERE ex.id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** "THERMACAD | METAL-TECH (Main)" style label for dropdowns. */
    public static function label(array $project): string
    {
        $program = trim((string) ($project['program_acronym'] ?? '')) ?: trim((string) ($project['program_name'] ?? ''));
        $title = (string) ($project['title'] ?? '');
        if (strlen($title) > 90) {
            $title = substr($title, 0, 87) . '...';
        }
        $campus = trim((string) ($project['campus'] ?? ''));

        return ($program !== '' ? $program . ' | ' : '') . $title . ($campus !== '' ? ' (' . $campus . ')' : '')
            . (!empty($project['year']) ? ' ' . (int) $project['year'] : '');
    }

    /** @param array<string, mixed> $data */
    public static function create(array $data): int
    {
        Database::pdo()->prepare(
            'INSERT INTO extension_projects (extension_program_id, college_id, academic_program_id, campus, title, leader_name,
                                             leader_user_id, year, total_budget, target_participants, status, source)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute(self::row($data, true));

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data): void
    {
        Database::pdo()->prepare(
            'UPDATE extension_projects SET extension_program_id = ?, college_id = ?, academic_program_id = ?, campus = ?, title = ?,
                    leader_name = ?, leader_user_id = ?, year = ?, total_budget = ?, target_participants = ?, status = ?
             WHERE id = ?'
        )->execute([...self::row($data, false), $id]);
    }

    /** @param array<string, mixed> $data @return list<mixed> */
    private static function row(array $data, bool $withSource): array
    {
        $values = [
            (int) $data['extension_program_id'],
            $data['college_id'] ?? null,
            $data['academic_program_id'] ?? null,
            $data['campus'] ?? null,
            (string) $data['title'],
            $data['leader_name'] ?? null,
            $data['leader_user_id'] ?? null,
            $data['year'] ?? null,
            $data['total_budget'] ?? null,
            $data['target_participants'] ?? null,
            $data['status'] ?? 'planned',
        ];
        if ($withSource) {
            $values[] = $data['source'] ?? 'Added in RIDE';
        }

        return $values;
    }

    public static function deleteBlockingReason(int $id): ?string
    {
        $pdo = Database::pdo();
        foreach (['proposals' => 'Proposals', 'extension_activities' => 'Conducted activities'] as $table => $label) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE extension_project_id = ?");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                return $label . ' are linked to this project. Mark it cancelled instead.';
            }
        }

        return null;
    }

    public static function delete(int $id): bool
    {
        if (self::deleteBlockingReason($id) !== null) {
            return false;
        }
        $stmt = Database::pdo()->prepare('DELETE FROM extension_projects WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }

    /** @return list<array> */
    public static function plannedActivities(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM extension_project_activities WHERE extension_project_id = ? ORDER BY sort_order, id'
        );
        $stmt->execute([$projectId]);

        return $stmt->fetchAll() ?: [];
    }

    /** @param array{title: string, coordinators: ?string, participants: ?int, budget: ?float, schedule: ?string} $data */
    public static function addPlannedActivity(int $projectId, array $data): void
    {
        $stmt = Database::pdo()->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM extension_project_activities WHERE extension_project_id = ?');
        $stmt->execute([$projectId]);
        Database::pdo()->prepare(
            'INSERT INTO extension_project_activities (extension_project_id, title, coordinators, participants, budget, schedule, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$projectId, $data['title'], $data['coordinators'], $data['participants'], $data['budget'], $data['schedule'], (int) $stmt->fetchColumn()]);
    }

    public static function deletePlannedActivity(int $projectId, int $activityId): void
    {
        Database::pdo()->prepare('DELETE FROM extension_project_activities WHERE id = ? AND extension_project_id = ?')
            ->execute([$activityId, $projectId]);
    }

    /** @return list<array> Conducted Activities entries linked to this project. */
    public static function conductedActivities(int $projectId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ea.*, u.first_name, u.last_name FROM extension_activities ea
             LEFT JOIN users u ON u.id = ea.user_id
             WHERE ea.extension_project_id = ? ORDER BY ea.activity_date DESC, ea.id DESC'
        );
        $stmt->execute([$projectId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Projects for a dropdown, optionally limited to a college. Cancelled ones are left out.
     *
     * @return list<array>
     */
    public static function options(?int $collegeId = null): array
    {
        return array_values(array_filter(
            self::all(['college_id' => $collegeId]),
            static fn (array $p): bool => (string) $p['status'] !== 'cancelled'
        ));
    }
}
