<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Admin-managed downloadable form templates (Word/Excel/PDF files users download and fill offline). */
final class FormTemplate
{
    public const AUDIENCE_EXTENSION = 'extension';
    public const AUDIENCE_RESEARCH = 'research';
    public const AUDIENCE_ALL = 'all';

    /** @var array<string, string> */
    public const AUDIENCE_LABELS = [
        self::AUDIENCE_EXTENSION => 'Extension Services',
        self::AUDIENCE_RESEARCH => 'Research and Development',
        self::AUDIENCE_ALL => 'All users',
    ];

    private const ALLOWED_EXT = ['pdf', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'txt', 'jpg', 'jpeg', 'png', 'zip'];
    private const MAX_BYTES = 10 * 1024 * 1024;
    private const COLUMNS = 'id, title, description, audience, original_name, stored_name, mime_type, file_size,
                             sort_order, is_active, created_by, created_at, updated_at';

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT ' . self::COLUMNS . ' FROM form_templates ORDER BY audience ASC, sort_order ASC, id ASC'
        );

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Active templates for the given audiences.
     *
     * @param list<string> $audiences
     * @return list<array<string, mixed>>
     */
    public static function activeFor(array $audiences): array
    {
        $audiences = array_values(array_intersect($audiences, array_keys(self::AUDIENCE_LABELS)));
        if ($audiences === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($audiences), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM form_templates
             WHERE is_active = 1 AND audience IN (' . $placeholders . ')
             ORDER BY audience ASC, sort_order ASC, id ASC'
        );
        $stmt->execute($audiences);

        return $stmt->fetchAll() ?: [];
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM form_templates WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function create(array $file, string $title, string $description, string $audience, ?int $createdBy): int
    {
        $stored = self::storeFile($file);

        $pdo = Database::pdo();
        $max = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM form_templates WHERE audience = ?');
        $max->execute([$audience]);

        $stmt = $pdo->prepare(
            'INSERT INTO form_templates
                (title, description, audience, original_name, stored_name, mime_type, file_size, sort_order, is_active, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([
            $title,
            $description !== '' ? $description : null,
            $audience,
            self::cleanOriginalName((string) $file['name']),
            $stored,
            $file['type'] ?? null,
            (int) $file['size'],
            (int) $max->fetchColumn() + 1,
            $createdBy,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $title, string $description, string $audience, bool $isActive): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE form_templates SET title = ?, description = ?, audience = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $title,
            $description !== '' ? $description : null,
            $audience,
            $isActive ? 1 : 0,
            $id,
        ]);
    }

    /** Swap the stored file for a new upload, keeping the template's metadata. */
    public static function replaceFile(int $id, array $file): void
    {
        $template = self::find($id);
        if ($template === null) {
            throw new \RuntimeException('Template not found.');
        }

        $stored = self::storeFile($file);
        $stmt = Database::pdo()->prepare(
            'UPDATE form_templates SET original_name = ?, stored_name = ?, mime_type = ?, file_size = ? WHERE id = ?'
        );
        $stmt->execute([
            self::cleanOriginalName((string) $file['name']),
            $stored,
            $file['type'] ?? null,
            (int) $file['size'],
            $id,
        ]);
        self::deleteStoredFile((string) $template['stored_name']);
    }

    public static function delete(int $id): void
    {
        $template = self::find($id);
        if ($template === null) {
            return;
        }

        $stmt = Database::pdo()->prepare('DELETE FROM form_templates WHERE id = ?');
        $stmt->execute([$id]);
        self::deleteStoredFile((string) $template['stored_name']);
    }

    public static function move(int $id, int $direction): void
    {
        $template = self::find($id);
        if ($template === null) {
            return;
        }

        $siblings = array_values(array_filter(
            self::all(),
            static fn(array $row): bool => $row['audience'] === $template['audience']
        ));
        $index = null;
        foreach ($siblings as $i => $row) {
            if ((int) $row['id'] === $id) {
                $index = $i;
                break;
            }
        }
        $newIndex = $index === null ? -1 : $index + $direction;
        if ($newIndex < 0 || $newIndex >= count($siblings)) {
            return;
        }

        [$siblings[$index], $siblings[$newIndex]] = [$siblings[$newIndex], $siblings[$index]];
        $stmt = Database::pdo()->prepare('UPDATE form_templates SET sort_order = ? WHERE id = ?');
        foreach ($siblings as $order => $row) {
            $stmt->execute([$order + 1, (int) $row['id']]);
        }
    }

    public static function filePath(array $template): string
    {
        return self::directory() . '/' . basename((string) $template['stored_name']);
    }

    public static function allowedExtensionsLabel(): string
    {
        return strtoupper(implode(', ', self::ALLOWED_EXT));
    }

    public static function acceptAttribute(): string
    {
        return implode(',', array_map(static fn(string $ext): string => '.' . $ext, self::ALLOWED_EXT));
    }

    private static function storeFile(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('File exceeds 10 MB limit.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException('File type not allowed. Use ' . self::allowedExtensionsLabel() . '.');
        }

        $dir = self::directory();
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create template storage directory.');
        }

        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $stored)) {
            throw new \RuntimeException('Could not save file.');
        }

        return $stored;
    }

    private static function deleteStoredFile(string $storedName): void
    {
        if ($storedName === '') {
            return;
        }

        $path = self::directory() . '/' . basename($storedName);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private static function cleanOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F"]/', '', $name) ?? '';

        return mb_substr($name !== '' ? $name : 'form-template', 0, 255);
    }

    private static function directory(): string
    {
        return STORAGE_PATH . '/form-templates';
    }
}
