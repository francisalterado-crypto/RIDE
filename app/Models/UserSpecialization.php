<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Areas of expertise a user lists on their profile (several per user). */
final class UserSpecialization
{
    public const MAX_PER_USER = 15;
    public const MAX_LENGTH = 80;

    /** @return list<string> */
    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare('SELECT name FROM user_specializations WHERE user_id = ? ORDER BY id ASC');
        $stmt->execute([$userId]);

        return array_map('strval', $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * @param list<int> $userIds
     * @return array<int, list<string>> user id => specializations
     */
    public static function forUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($userIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = Database::pdo()->prepare(
            'SELECT user_id, name FROM user_specializations WHERE user_id IN (' . $placeholders . ') ORDER BY id ASC'
        );
        $stmt->execute($userIds);

        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $map[(int) $row['user_id']][] = (string) $row['name'];
        }

        return $map;
    }

    /**
     * Every specialization in use, most common first, for suggestions and filters.
     *
     * @return list<array{name: string, count: int}>
     */
    public static function allInUse(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT name, COUNT(*) AS cnt FROM user_specializations GROUP BY name ORDER BY cnt DESC, name ASC'
        );

        return array_map(
            static fn (array $row): array => ['name' => (string) $row['name'], 'count' => (int) $row['cnt']],
            $stmt->fetchAll() ?: []
        );
    }

    /**
     * Cleans user input: trims, collapses spaces, drops blanks and case-insensitive duplicates, applies limits.
     *
     * @return list<string>
     */
    public static function normalize(mixed $input): array
    {
        $names = [];
        foreach (is_array($input) ? $input : [] as $value) {
            if (!is_string($value)) {
                continue;
            }
            $name = trim((string) preg_replace('/\s+/u', ' ', $value));
            // Capitalize each word for consistent display; existing capitals (e.g. "IoT", "GIS") are kept.
            $name = title_case_words(mb_substr($name, 0, self::MAX_LENGTH));
            $key = mb_strtolower($name);
            if ($name === '' || isset($names[$key])) {
                continue;
            }
            $names[$key] = $name;
            if (count($names) >= self::MAX_PER_USER) {
                break;
            }
        }

        return array_values($names);
    }

    /** @param list<string> $names already normalized */
    public static function replaceForUser(int $userId, array $names): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM user_specializations WHERE user_id = ?')->execute([$userId]);
            $insert = $pdo->prepare('INSERT INTO user_specializations (user_id, name) VALUES (?, ?)');
            foreach ($names as $name) {
                $insert->execute([$userId, $name]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
