<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class UserAvatar
{
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    private const MAX_BYTES = 2 * 1024 * 1024;

    public static function store(int $userId, array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage($error));
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Profile picture upload failed.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('Profile picture must be 2 MB or smaller.');
        }

        $ext = self::detectExtension($file, $tmp);
        if ($ext === null) {
            throw new \RuntimeException('Profile picture must be a JPG, PNG, or WebP image.');
        }

        $dir = STORAGE_PATH . '/avatars/' . $userId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create profile picture folder.');
        }

        self::deleteExistingFiles($dir);

        $stored = 'avatar.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!move_uploaded_file($tmp, $dest)) {
            if (!@copy($tmp, $dest)) {
                throw new \RuntimeException('Could not save profile picture.');
            }
            @unlink($tmp);
        }

        $relative = 'avatars/' . $userId . '/' . $stored;
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

        $dir = STORAGE_PATH . '/avatars/' . $userId;
        if (is_dir($dir)) {
            self::deleteExistingFiles($dir);
        }

        self::updatePath($userId, null);
    }

    public static function relativePath(int $userId): ?string
    {
        $stmt = Database::pdo()->prepare('SELECT avatar_path FROM users WHERE id = ? LIMIT 1');
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
            'webp' => 'image/webp',
            default => null,
        };
    }

    public static function url(int $userId): ?string
    {
        $path = self::filePath($userId);
        if ($path === null) {
            return null;
        }

        return base_url('avatars/' . $userId) . '?v=' . filemtime($path);
    }

    private static function detectExtension(array $file, string $tmp): ?string
    {
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                if (is_string($detected)) {
                    $mime = strtolower($detected);
                }
            }
        }

        if ($mime !== null && isset(self::ALLOWED_MIME[$mime])) {
            return self::ALLOWED_MIME[$mime];
        }

        if (in_array($ext, self::ALLOWED_EXT, true)) {
            $info = @getimagesize($tmp);
            if ($info !== false) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }

        return null;
    }

    private static function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Profile picture is too large.',
            UPLOAD_ERR_PARTIAL => 'Profile picture upload was interrupted. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose a profile picture to upload.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Server could not store the profile picture.',
            default => 'Profile picture upload failed.',
        };
    }

    private static function updatePath(int $userId, ?string $path): void
    {
        $stmt = Database::pdo()->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
        $stmt->execute([$path, $userId]);
    }

    private static function deleteExistingFiles(string $dir): void
    {
        foreach (glob($dir . '/avatar.*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
