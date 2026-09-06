<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserSignature
{
    private const ALLOWED = ['jpg', 'jpeg', 'png'];
    private const MAX_BYTES = 2 * 1024 * 1024;

    public static function store(int $userId, array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Signature upload failed.');
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new \RuntimeException('Signature image must be 2 MB or smaller.');
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED, true)) {
            throw new \RuntimeException('Signature must be a JPG or PNG image.');
        }

        $dir = STORAGE_PATH . '/signatures/' . $userId;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        self::deleteExistingFiles($dir);

        $stored = 'signature.' . $ext;
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save signature image.');
        }

        $relative = 'signatures/' . $userId . '/' . $stored;
        self::updatePath($userId, $relative);

        return $relative;
    }

    public static function remove(int $userId): void
    {
        $path = self::relativePath($userId);
        if ($path !== null) {
            $full = STORAGE_PATH . '/' . $path;
            if (is_file($full)) {
                unlink($full);
            }
        }

        self::updatePath($userId, null);
    }

    public static function relativePath(int $userId): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT signature_path FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $path = $stmt->fetchColumn();

        return is_string($path) && $path !== '' ? $path : null;
    }

    public static function filePath(int $userId): ?string
    {
        $relative = self::relativePath($userId);
        if ($relative === null) {
            return null;
        }

        $full = STORAGE_PATH . '/' . $relative;
        return is_file($full) ? $full : null;
    }

    public static function mimeType(int $userId): ?string
    {
        $path = self::filePath($userId);
        if ($path === null) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };
    }

    private static function updatePath(int $userId, ?string $path): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET signature_path = ? WHERE id = ?');
        $stmt->execute([$path, $userId]);
    }

    private static function deleteExistingFiles(string $dir): void
    {
        foreach (glob($dir . '/signature.*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
