<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** A file attached to a conducted activity (e.g. one item of a training's ETR checklist). */
final class ExtensionActivityDocument
{
    public const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'zip'];
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @return array<string, list<array<string, mixed>>> category => documents */
    public static function forActivity(int $activityId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, activity_id, category, original_name, stored_name, mime_type, file_size, created_at
             FROM extension_activity_documents WHERE activity_id = ? ORDER BY category ASC, id ASC'
        );
        $stmt->execute([$activityId]);

        $grouped = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $grouped[(string) $row['category']][] = $row;
        }

        return $grouped;
    }

    /**
     * Number of checklist categories with at least one file, per activity, counting only $categories.
     *
     * @param list<int> $activityIds
     * @param list<string> $categories
     * @return array<int, int>
     */
    public static function filledCategoryCounts(array $activityIds, array $categories): array
    {
        $activityIds = array_values(array_unique(array_map('intval', $activityIds)));
        $categories = array_values(array_unique($categories));
        if ($activityIds === [] || $categories === []) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            'SELECT activity_id, COUNT(DISTINCT category) AS filled FROM extension_activity_documents
             WHERE activity_id IN (' . implode(', ', array_fill(0, count($activityIds), '?')) . ')
               AND category IN (' . implode(', ', array_fill(0, count($categories), '?')) . ')
             GROUP BY activity_id'
        );
        $stmt->execute([...$activityIds, ...$categories]);

        $counts = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $counts[(int) $row['activity_id']] = (int) $row['filled'];
        }

        return $counts;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM extension_activity_documents WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @param array<string, mixed> $file one entry of $_FILES */
    public static function store(int $activityId, int $userId, string $category, array $file): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds 10 MB limit.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException('File type not allowed. Use ' . strtoupper(implode(', ', self::ALLOWED_EXT)) . '.');
        }

        $dir = self::directory($activityId);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create storage directory.');
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $stored)) {
            throw new \RuntimeException('Could not save file.');
        }

        $name = basename(str_replace('\\', '/', (string) $file['name']));
        $name = mb_substr(preg_replace('/[\x00-\x1F"]/', '', $name) ?: 'document.' . $ext, 0, 255);

        $stmt = Database::pdo()->prepare(
            'INSERT INTO extension_activity_documents (activity_id, category, original_name, stored_name, mime_type, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$activityId, $category, $name, $stored, (string) ($file['type'] ?? '') ?: null, (int) $file['size'], $userId]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $document */
    public static function filePath(array $document): string
    {
        return self::directory((int) $document['activity_id']) . '/' . basename((string) $document['stored_name']);
    }

    public static function delete(int $id): void
    {
        $document = self::find($id);
        if ($document === null) {
            return;
        }

        $path = self::filePath($document);
        if (is_file($path)) {
            @unlink($path);
        }
        Database::pdo()->prepare('DELETE FROM extension_activity_documents WHERE id = ?')->execute([$id]);
    }

    /** Removes every stored file of an activity; the rows go with the activity (ON DELETE CASCADE). */
    public static function deleteFilesForActivity(int $activityId): void
    {
        foreach (self::forActivity($activityId) as $documents) {
            foreach ($documents as $document) {
                $path = self::filePath($document);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }
        @rmdir(self::directory($activityId));
    }

    public static function acceptAttribute(): string
    {
        return implode(',', array_map(static fn (string $ext): string => '.' . $ext, self::ALLOWED_EXT));
    }

    private static function directory(int $activityId): string
    {
        return STORAGE_PATH . '/activities/' . $activityId;
    }
}
