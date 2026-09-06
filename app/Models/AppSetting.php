<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AppSetting
{
    public static function has(string $key): bool
    {
        $stmt = Database::pdo()->prepare(
            'SELECT 1 FROM app_settings WHERE setting_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);

        return (bool) $stmt->fetchColumn();
    }

    public static function get(string $key, string $default = ''): string
    {
        $stmt = Database::pdo()->prepare(
            'SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1'
        );
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return is_string($value) && $value !== '' ? $value : $default;
    }

    public static function put(string $key, string $value, ?int $updatedBy = null): void
    {
        $stmt = Database::pdo()->prepare(\App\Core\Sql::appSettingUpsertSql());
        $stmt->execute([$key, $value, $updatedBy]);
    }

    public static function hasUnreadForUser(string $key, int $userId): bool
    {
        if ($userId <= 0 || !self::has($key)) {
            return false;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT 1
             FROM app_settings s
             LEFT JOIN global_message_reads gmr ON gmr.user_id = ?
             WHERE s.setting_key = ?
               AND (gmr.last_read_at IS NULL OR gmr.last_read_at < s.updated_at)
             LIMIT 1'
        );
        $stmt->execute([$userId, $key]);

        return (bool) $stmt->fetchColumn();
    }

    public static function markReadForUser(string $key, int $userId): void
    {
        if ($userId <= 0 || !self::has($key)) {
            return;
        }

        $stmt = Database::pdo()->prepare(\App\Core\Sql::globalMessageReadUpsertSql());
        $stmt->execute([$userId]);
    }
}
