<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ScholarlyAttachment
{
    public const TYPE_PAPER = 'published_paper';
    public const TYPE_PRESENTATION = 'paper_presentation';

    private const ALLOWED = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'];
    private const MAX_BYTES = 10 * 1024 * 1024;

    /** @return list<array<string, mixed>> */
    public static function forRecord(string $recordType, int $recordId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM scholarly_attachments
             WHERE record_type = ? AND record_id = ?
             ORDER BY created_at ASC'
        );
        $stmt->execute([$recordType, $recordId]);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * @param list<int> $recordIds
     * @return array<int, list<array<string, mixed>>>
     */
    public static function groupedForRecords(string $recordType, array $recordIds): array
    {
        $recordIds = array_values(array_filter(array_map('intval', $recordIds)));
        if ($recordIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($recordIds), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT * FROM scholarly_attachments
             WHERE record_type = ? AND record_id IN ({$placeholders})
             ORDER BY created_at ASC"
        );
        $stmt->execute([$recordType, ...$recordIds]);

        $grouped = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $grouped[(int) $row['record_id']][] = $row;
        }

        return $grouped;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM scholarly_attachments WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $files $_FILES entry for supporting_documents[]
     * @return array{stored: int, errors: list<string>}
     */
    public static function storeMany(string $recordType, int $recordId, int $userId, array $files): array
    {
        $stored = 0;
        $errors = [];

        foreach (self::normalizeUploadedFiles($files) as $file) {
            try {
                self::store($recordType, $recordId, $userId, $file);
                $stored++;
            } catch (\Throwable $e) {
                $name = (string) ($file['name'] ?? 'file');
                $errors[] = $name . ': ' . $e->getMessage();
            }
        }

        return ['stored' => $stored, 'errors' => $errors];
    }

    /** @param array<string, mixed> $file */
    public static function store(string $recordType, int $recordId, int $userId, array $file): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds 10 MB limit.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true)) {
            throw new \RuntimeException('File type not allowed.');
        }

        $dir = self::storageDir($recordType, $recordId);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $storedName;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save file.');
        }

        $stmt = Database::pdo()->prepare(
            'INSERT INTO scholarly_attachments
             (record_type, record_id, user_id, original_name, stored_name, mime_type, file_size)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $recordType,
            $recordId,
            $userId,
            $file['name'],
            $storedName,
            $file['type'] ?? null,
            (int) $file['size'],
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    public static function delete(int $id): bool
    {
        $attachment = self::find($id);
        if ($attachment === null) {
            return false;
        }

        self::deleteFile($attachment);

        $stmt = Database::pdo()->prepare('DELETE FROM scholarly_attachments WHERE id = ?');

        return $stmt->execute([$id]);
    }

    public static function deleteForRecord(string $recordType, int $recordId): void
    {
        foreach (self::forRecord($recordType, $recordId) as $attachment) {
            self::deleteFile($attachment);
        }

        $stmt = Database::pdo()->prepare(
            'DELETE FROM scholarly_attachments WHERE record_type = ? AND record_id = ?'
        );
        $stmt->execute([$recordType, $recordId]);
    }

    /** @param array<string, mixed> $attachment */
    public static function filePath(array $attachment): string
    {
        return self::storageDir((string) $attachment['record_type'], (int) $attachment['record_id'])
            . '/' . $attachment['stored_name'];
    }

    /**
     * @param array<string, mixed> $files
     * @return list<array<string, mixed>>
     */
    public static function normalizeUploadedFiles(array $files): array
    {
        if (!is_array($files['name'] ?? null)) {
            if (($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }

            return [$files];
        }

        $normalized = [];
        foreach ($files['name'] as $index => $name) {
            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $normalized[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? null,
                'tmp_name' => $files['tmp_name'][$index],
                'error' => $files['error'][$index],
                'size' => $files['size'][$index],
            ];
        }

        return $normalized;
    }

    private static function storageDir(string $recordType, int $recordId): string
    {
        return STORAGE_PATH . '/scholarly/' . $recordType . '/' . $recordId;
    }

    /** @param array<string, mixed> $attachment */
    private static function deleteFile(array $attachment): void
    {
        $path = self::filePath($attachment);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
