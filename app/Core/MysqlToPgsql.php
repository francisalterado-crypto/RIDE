<?php

declare(strict_types=1);

namespace App\Core;

final class MysqlToPgsql
{
    public static function convert(string $sql): string
    {
        $sql = trim($sql);
        if ($sql === '') {
            return '';
        }

        if (preg_match('/^SET\s+(NAMES|FOREIGN_KEY_CHECKS)\b/i', $sql) === 1) {
            return '';
        }

        if (preg_match('/^ALTER\s+TABLE\s+\S+\s+MODIFY\b/i', $sql) === 1) {
            return '';
        }

        $sql = str_replace('`', '', $sql);
        $sql = preg_replace('/\s+AFTER\s+[A-Za-z_][A-Za-z0-9_]*/i', '', $sql) ?? $sql;

        if (preg_match('/^DELETE\s+(\w+)\s+FROM\s+(\w+)\s+\1\s+INNER\s+JOIN\s+(\w+)\s+(\w+)\s+ON\s+(.+?)\s+WHERE\s+(.+)$/is', $sql, $m) === 1) {
            return "DELETE FROM {$m[2]} {$m[1]} USING {$m[3]} {$m[4]} WHERE {$m[5]} AND {$m[6]}";
        }

        if (preg_match('/^CREATE\s+TABLE\b/i', $sql) === 1) {
            return self::convertCreateTable($sql);
        }

        if (preg_match('/^INSERT\s+IGNORE\s+INTO\s+(\w+)\s*\(([^)]+)\)/i', $sql, $insertMatch) === 1) {
            $columns = array_map(static fn (string $col): string => trim($col), explode(',', $insertMatch[2]));
            $sql = preg_replace('/^INSERT\s+IGNORE\s+INTO\b/i', 'INSERT INTO', $sql) ?? $sql;
            if (!str_contains(strtoupper($sql), 'ON CONFLICT')) {
                $conflictTarget = self::insertConflictTarget($insertMatch[1], $columns);
                if ($conflictTarget !== null) {
                    $sql .= ' ON CONFLICT (' . $conflictTarget . ') DO NOTHING';
                }
            }
        }

        if (preg_match('/^ALTER\s+TABLE\b/i', $sql) === 1) {
            $sql = self::convertTypes($sql);
        }

        return trim($sql);
    }

    private static function convertCreateTable(string $sql): string
    {
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([A-Za-z_][A-Za-z0-9_]*)/i', $sql, $nameMatch) !== 1) {
            return self::convertTypes($sql);
        }

        $table = $nameMatch[1];
        $paren = strpos($sql, '(', (int) strpos($sql, $table));
        if ($paren === false) {
            return self::convertTypes($sql);
        }

        $depth = 0;
        $end = null;
        $length = strlen($sql);
        for ($i = $paren; $i < $length; $i++) {
            $ch = $sql[$i];
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        if ($end === null) {
            return self::convertTypes($sql);
        }

        $prefix = trim(substr($sql, 0, $paren));
        $body = substr($sql, $paren + 1, $end - $paren - 1);
        $parts = self::splitTopLevel($body);
        $columns = [];
        $indexes = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $upper = strtoupper($part);
            if (str_starts_with($upper, 'CONSTRAINT') || str_starts_with($upper, 'PRIMARY KEY') || str_starts_with($upper, 'FOREIGN KEY')) {
                $columns[] = $part;
                continue;
            }

            if (preg_match('/^UNIQUE\s+(?:KEY|INDEX)\s+(?:([A-Za-z_][A-Za-z0-9_]*)\s+)?\((.+)\)$/is', $part, $m) === 1) {
                $constraint = trim((string) ($m[1] ?? ''));
                $cols = trim($m[2]);
                $columns[] = $constraint !== ''
                    ? "CONSTRAINT {$constraint} UNIQUE ({$cols})"
                    : "UNIQUE ({$cols})";
                continue;
            }

            if (str_starts_with($upper, 'UNIQUE')) {
                $columns[] = $part;
                continue;
            }

            if (preg_match('/^(?:INDEX|KEY)\s+(?:([A-Za-z_][A-Za-z0-9_]*)\s+)?\((.+)\)$/is', $part, $m) === 1) {
                $indexName = trim((string) ($m[1] ?? ''));
                if ($indexName === '') {
                    $indexName = 'idx_' . $table . '_' . (count($indexes) + 1);
                }
                $indexes[] = "CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$m[2]})";
                continue;
            }

            $columns[] = self::convertTypes($part);
        }

        $out = $prefix . " (\n    " . implode(",\n    ", $columns) . "\n)";
        if ($indexes !== []) {
            $out .= ";\n" . implode(";\n", $indexes);
        }

        return $out;
    }

    /** @return list<string> */
    private static function splitTopLevel(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($body);
        for ($i = 0; $i < $length; $i++) {
            $ch = $body[$i];
            if ($ch === '(') {
                $depth++;
                $current .= $ch;
            } elseif ($ch === ')') {
                $depth--;
                $current .= $ch;
            } elseif ($ch === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
            } else {
                $current .= $ch;
            }
        }
        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    /** @param list<string> $columns */
    private static function insertConflictTarget(string $table, array $columns): ?string
    {
        if ($table === 'roles' && in_array('slug', $columns, true)) {
            return 'slug';
        }
        if ($table === 'permissions' && in_array('slug', $columns, true)) {
            return 'slug';
        }
        if (in_array('id', $columns, true)) {
            return 'id';
        }
        if ($table === 'user_roles' && in_array('user_id', $columns, true) && in_array('role_id', $columns, true)) {
            return 'user_id, role_id';
        }
        if ($table === 'role_permissions' && in_array('role_id', $columns, true) && in_array('permission_id', $columns, true)) {
            return 'role_id, permission_id';
        }
        if (in_array('setting_key', $columns, true)) {
            return 'setting_key';
        }
        if (in_array('slug', $columns, true)) {
            return 'slug';
        }
        if (in_array('email', $columns, true)) {
            return 'email';
        }
        if (count($columns) === 1) {
            return $columns[0];
        }

        return null;
    }

    private static function convertTypes(string $sql): string
    {
        $sql = preg_replace('/\bBIGINT\s+UNSIGNED\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'BIGINT GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY', $sql) ?? $sql;
        $sql = preg_replace('/\bINT\s+UNSIGNED\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i', 'INTEGER GENERATED BY DEFAULT AS IDENTITY PRIMARY KEY', $sql) ?? $sql;
        $sql = preg_replace('/\bBIGINT\s+UNSIGNED\s+AUTO_INCREMENT\b/i', 'BIGINT GENERATED BY DEFAULT AS IDENTITY', $sql) ?? $sql;
        $sql = preg_replace('/\bINT\s+UNSIGNED\s+AUTO_INCREMENT\b/i', 'INTEGER GENERATED BY DEFAULT AS IDENTITY', $sql) ?? $sql;
        $sql = preg_replace('/\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\bTINYINT\s*\(\s*1\s*\)/i', 'SMALLINT', $sql) ?? $sql;
        $sql = preg_replace('/\bTINYINT\b/i', 'SMALLINT', $sql) ?? $sql;
        $sql = preg_replace('/\bYEAR(?:\(\s*\d+\s*\))?\b/i', 'SMALLINT', $sql) ?? $sql;
        $sql = preg_replace('/\b(?:LONG|MEDIUM|TINY)?BLOB\b/i', 'BYTEA', $sql) ?? $sql;
        $sql = preg_replace('/\bTINYTEXT\b/i', 'TEXT', $sql) ?? $sql;
        $sql = preg_replace('/\bENUM\s*\([^)]*\)/is', 'VARCHAR(80)', $sql) ?? $sql;
        $sql = preg_replace('/\bINT\s+UNSIGNED\b/i', 'INTEGER', $sql) ?? $sql;
        $sql = preg_replace('/\bBIGINT\s+UNSIGNED\b/i', 'BIGINT', $sql) ?? $sql;
        $sql = preg_replace('/\bSMALLINT\s+UNSIGNED\b/i', 'SMALLINT', $sql) ?? $sql;
        $sql = preg_replace('/\s+CHARACTER\s+SET\s+\w+/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\s+COLLATE\s+\w+/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\s+ENGINE\s*=\s*\w+/i', '', $sql) ?? $sql;
        $sql = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql) ?? $sql;

        return trim($sql);
    }
}
