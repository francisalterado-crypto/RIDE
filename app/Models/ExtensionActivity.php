<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Support\ExtensionActivityTypes;
use App\Support\QuarterlyReporting;

/**
 * One entry that feeds a quarterly report: a conducted extension activity (ESO) or a research
 * accomplishment (RDO). The table keeps its original name, extension_activities.
 */
final class ExtensionActivity
{
    private const COLUMNS = 'a.id, a.user_id, a.college_id, a.activity_type, a.section, a.activity_date, a.title, a.data,
                             a.created_at, a.updated_at, u.first_name, u.last_name';

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM extension_activities a JOIN users u ON u.id = a.user_id WHERE a.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Entries visible in one scope: a single user's, or a whole college's.
     *
     * @param array{user_id?: int, college_id?: int} $scope
     * @return list<array<string, mixed>>
     */
    public static function list(array $scope, ?string $type = null, ?string $from = null, ?string $to = null): array
    {
        [$where, $params] = self::scopeWhere($scope);
        if ($type !== null && $type !== '') {
            $where[] = 'a.activity_type = ?';
            $params[] = $type;
        }
        if ($from !== null) {
            $where[] = 'a.activity_date >= ?';
            $params[] = $from;
        }
        if ($to !== null) {
            $where[] = 'a.activity_date <= ?';
            $params[] = $to;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM extension_activities a JOIN users u ON u.id = a.user_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY a.activity_date ASC, a.id ASC'
        );
        $stmt->execute($params);

        return array_map([self::class, 'hydrate'], $stmt->fetchAll() ?: []);
    }

    /** @param array<string, string> $data */
    public static function create(int $userId, ?int $collegeId, string $type, string $section, array $data): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO extension_activities (user_id, college_id, activity_type, section, activity_date, title, data)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $collegeId,
            $type,
            $section,
            self::activityDate($type, $section, $data),
            self::title($type, $data),
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array<string, string> $data */
    public static function update(int $id, string $section, array $data): void
    {
        $existing = self::find($id);
        if ($existing === null) {
            return;
        }

        $type = (string) $existing['activity_type'];
        $stmt = Database::pdo()->prepare(
            'UPDATE extension_activities SET section = ?, activity_date = ?, title = ?, data = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        );
        $stmt->execute([
            $section,
            self::activityDate($type, $section, $data),
            self::title($type, $data),
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}',
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM extension_activities WHERE id = ?')->execute([$id]);
    }

    /**
     * Quarterly report summary built from the conducted activities of one reporting period, in the same
     * shape the report form reads from a saved report.
     *
     * @param array{user_id?: int, college_id?: int} $scope
     * @return array{summary: array<string, mixed>, count: int}
     */
    public static function reportSummary(string $type, string $periodKey, array $scope): array
    {
        $definition = ExtensionActivityTypes::get($type);
        $summary = [
            'form_type' => $type,
            'reporting_period' => $periodKey,
            'report_as_of' => QuarterlyReporting::reportAsOfForPeriod($periodKey) ?? '',
        ];
        $range = ExtensionActivityTypes::periodDateRange($periodKey);
        if ($definition === null || $range === null) {
            return ['summary' => $summary, 'count' => 0];
        }

        foreach ($definition['sections'] as $sectionDef) {
            self::setPath($summary, $sectionDef['path'], []);
        }

        $count = 0;
        foreach (self::list($scope, $type, $range[0], $range[1]) as $entry) {
            $section = (string) $entry['section'];
            if (!isset($definition['sections'][$section])) {
                continue;
            }

            $row = [];
            foreach (ExtensionActivityTypes::fieldsFor($type, $section) as $field => $fieldDef) {
                $value = (string) ($entry['data'][$field] ?? '');
                // Free-text report columns get the option's label rather than its key.
                if (!empty($fieldDef['report_label'])) {
                    $value = (string) ($fieldDef['options'][$value] ?? $value);
                }
                $row[$field] = $value;
            }
            if (ExtensionActivityTypes::officeOf($type) === 'rdo') {
                // The report's evidence-link column points at the accomplishment and its uploaded files.
                $row['google_drive_link'] = base_url('research-accomplishments/' . (int) $entry['id']);
            }
            if ($type === 'trainings_conducted') {
                $row['persons_trained_weighted'] = (string) trainings_conducted_weighted_persons(
                    (int) ($row['persons_trained'] ?? 0),
                    (string) ($row['training_length'] ?? '')
                );
            }

            $path = $definition['sections'][$section]['path'];
            $rows = self::getPath($summary, $path);
            $rows[] = $row;
            self::setPath($summary, $path, $rows);
            $count++;
        }

        return ['summary' => $summary, 'count' => $count];
    }

    /**
     * @param array{user_id?: int, college_id?: int} $scope
     * @return array{0: list<string>, 1: list<int>}
     */
    private static function scopeWhere(array $scope): array
    {
        if (isset($scope['college_id'])) {
            return [['a.college_id = ?'], [(int) $scope['college_id']]];
        }

        return [['a.user_id = ?'], [(int) ($scope['user_id'] ?? 0)]];
    }

    /** @param array<string, string> $data */
    private static function activityDate(string $type, string $section, array $data): ?string
    {
        $value = trim((string) ($data[ExtensionActivityTypes::dateFieldFor($type, $section)] ?? ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    /** @param array<string, string> $data */
    private static function title(string $type, array $data): string
    {
        $field = (string) (ExtensionActivityTypes::get($type)['title_field'] ?? '');
        $title = trim(preg_replace('/\s+/', ' ', (string) ($data[$field] ?? '')) ?? '');

        return mb_substr($title, 0, 255);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        $decoded = json_decode((string) ($row['data'] ?? ''), true);
        $row['data'] = is_array($decoded) ? $decoded : [];

        return $row;
    }

    /**
     * @param array<string, mixed> $target
     * @param list<string> $path
     */
    private static function setPath(array &$target, array $path, mixed $value): void
    {
        $ref = &$target;
        foreach ($path as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
    }

    /**
     * @param array<string, mixed> $source
     * @param list<string> $path
     * @return list<array<string, string>>
     */
    private static function getPath(array $source, array $path): array
    {
        foreach ($path as $key) {
            $source = is_array($source[$key] ?? null) ? $source[$key] : [];
        }

        return $source;
    }
}
