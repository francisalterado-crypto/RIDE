<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Role
{
    /** @return list<string> */
    public static function collegeScopedSlugs(): array
    {
        return [
            'coordinator_research',
            'coordinator_extension',
            'dean',
            'faculty',
            'extension_worker',
            'external_partner',
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT id, slug, name, description FROM roles ORDER BY name ASC');

        return $stmt->fetchAll() ?: [];
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, slug, name, description FROM roles WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, slug, name, description FROM roles WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function requiresCollege(string $slug): bool
    {
        return in_array($slug, self::collegeScopedSlugs(), true);
    }
}
