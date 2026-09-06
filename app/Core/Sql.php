<?php

declare(strict_types=1);

namespace App\Core;

final class Sql
{
    public static function currentDate(): string
    {
        return Database::isPgsql() ? 'CURRENT_DATE' : 'CURDATE()';
    }

    public static function currentYear(): string
    {
        return Database::isPgsql()
            ? 'EXTRACT(YEAR FROM CURRENT_DATE)::integer'
            : 'YEAR(CURDATE())';
    }

    public static function year(string $expr): string
    {
        return Database::isPgsql()
            ? 'EXTRACT(YEAR FROM ' . $expr . ')'
            : 'YEAR(' . $expr . ')';
    }

    public static function dateAddDays(string $dateExpr, string $daysExpr): string
    {
        if (Database::isPgsql()) {
            return '(' . $dateExpr . ' + (' . $daysExpr . ") * INTERVAL '1 day')";
        }

        return 'DATE_ADD(' . $dateExpr . ', INTERVAL ' . $daysExpr . ' DAY)';
    }

    public static function dateAddDaysFromToday(string $daysExpr): string
    {
        return self::dateAddDays(self::currentDate(), $daysExpr);
    }

    public static function nowPlusDays(int $days): string
    {
        if (Database::isPgsql()) {
            return "(NOW() + INTERVAL '" . $days . " days')";
        }

        return 'DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY)';
    }

    public static function nowMinusDays(int $days): string
    {
        if (Database::isPgsql()) {
            return "(NOW() - INTERVAL '" . $days . " days')";
        }

        return 'DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)';
    }

    public static function yearMonth(string $expr): string
    {
        return Database::isPgsql()
            ? "to_char({$expr}, 'YYYY-MM')"
            : "DATE_FORMAT({$expr}, '%Y-%m')";
    }

    public static function monthStartAgo(int $months): string
    {
        if (Database::isPgsql()) {
            return "(date_trunc('month', CURRENT_DATE - INTERVAL '{$months} months'))::date";
        }

        return "DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL {$months} MONTH), '%Y-%m-01')";
    }

    public static function groupConcat(string $expr, string $separator = ', ', ?string $orderBy = null, bool $distinct = false): string
    {
        $orderBy ??= $expr;
        $distinctSql = $distinct ? 'DISTINCT ' : '';
        if (Database::isPgsql()) {
            $sep = str_replace("'", "''", $separator);

            return "STRING_AGG({$distinctSql}{$expr}, '{$sep}' ORDER BY {$orderBy})";
        }

        $sep = str_replace('"', '\\"', $separator);

        return "GROUP_CONCAT({$distinctSql}{$expr} ORDER BY {$orderBy} SEPARATOR \"{$sep}\")";
    }

    public static function substringIndex(string $expr, string $delimiter, int $count): string
    {
        if (Database::isPgsql()) {
            $del = str_replace("'", "''", $delimiter);

            return "split_part({$expr}, E'{$del}', {$count})";
        }

        $del = str_replace('"', '\\"', $delimiter);

        return "SUBSTRING_INDEX({$expr}, \"{$del}\", {$count})";
    }

    public static function regexp(string $columnExpr, string $paramPlaceholder): string
    {
        return Database::isPgsql()
            ? "{$columnExpr} ~ {$paramPlaceholder}"
            : "{$columnExpr} REGEXP {$paramPlaceholder}";
    }

    public static function insertIgnore(string $insertIntoSql, ?string $conflictTarget = null): string
    {
        if (Database::isPgsql()) {
            if (!str_contains(strtoupper($insertIntoSql), 'ON CONFLICT')) {
                if ($conflictTarget !== null && $conflictTarget !== '') {
                    return $insertIntoSql . ' ON CONFLICT (' . $conflictTarget . ') DO NOTHING';
                }

                return $insertIntoSql . ' ON CONFLICT DO NOTHING';
            }

            return $insertIntoSql;
        }

        return preg_replace('/^INSERT\s+INTO\b/i', 'INSERT IGNORE INTO', $insertIntoSql) ?? $insertIntoSql;
    }

    public static function sessionUpsertSql(): string
    {
        if (Database::isPgsql()) {
            return 'INSERT INTO php_sessions (id, data, last_activity) VALUES (?, ?, ?)
                    ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = EXCLUDED.last_activity';
        }

        return 'INSERT INTO php_sessions (id, data, last_activity) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)';
    }

    public static function appSettingUpsertSql(): string
    {
        if (Database::isPgsql()) {
            return 'INSERT INTO app_settings (setting_key, setting_value, updated_by)
                    VALUES (?, ?, ?)
                    ON CONFLICT (setting_key) DO UPDATE SET
                        setting_value = EXCLUDED.setting_value,
                        updated_by = EXCLUDED.updated_by';
        }

        return 'INSERT INTO app_settings (setting_key, setting_value, updated_by)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    setting_value = VALUES(setting_value),
                    updated_by = VALUES(updated_by)';
    }

    public static function globalMessageReadUpsertSql(): string
    {
        if (Database::isPgsql()) {
            return 'INSERT INTO global_message_reads (user_id, last_read_at)
                    VALUES (?, NOW())
                    ON CONFLICT (user_id) DO UPDATE SET last_read_at = NOW()';
        }

        return 'INSERT INTO global_message_reads (user_id, last_read_at)
                VALUES (?, NOW())
                ON DUPLICATE KEY UPDATE last_read_at = NOW()';
    }
}
