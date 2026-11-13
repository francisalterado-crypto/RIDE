<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Posts on the public site: RIDE news, The Palawan Scientist journal posts (issues, calls for papers) and
 * conference posts (ICWPS, RIDE Annual Conference). Body text is plain text; paragraphs are blank-line separated.
 */
final class SitePost
{
    public const KINDS = ['news' => 'News', 'journal' => 'Journal', 'conference' => 'Conference'];
    public const SERIES = ['icwps' => 'ICWPS', 'racp' => 'RIDE Conference Proceedings'];

    private const COLUMNS = 'id, kind, series, title, summary, body, image_path, link_url, link_label, event_date, event_end_date,
        location, is_published, published_at, created_by, created_at, updated_at';
    private const ALLOWED_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const RELATIVE_DIR = 'assets/uploads/site';

    /** @return list<array<string, mixed>> Every post, newest first (admin). */
    public static function all(): array
    {
        $rows = Database::pdo()->query('SELECT ' . self::COLUMNS . ' FROM site_posts ORDER BY COALESCE(published_at, created_at) DESC, id DESC')->fetchAll() ?: [];

        return array_map([self::class, 'hydrate'], $rows);
    }

    /**
     * Published posts of a kind, newest first. $series narrows conference posts.
     *
     * @return list<array<string, mixed>>
     */
    public static function published(string $kind, int $limit = 50, ?string $series = null): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM site_posts WHERE kind = ? AND is_published = 1';
        $params = [$kind];
        if ($series !== null) {
            $sql .= ' AND series = ?';
            $params[] = $series;
        }
        $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC LIMIT ' . max(1, $limit);
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return array_map([self::class, 'hydrate'], $stmt->fetchAll() ?: []);
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT ' . self::COLUMNS . ' FROM site_posts WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? self::hydrate($row) : null;
    }

    /** @param array<string, mixed> $data validated fields (see SiteAdminController::input) */
    public static function create(array $data, ?array $image, ?int $createdBy): int
    {
        $imagePath = $image !== null ? self::storeImage($image) : null;
        $stmt = Database::pdo()->prepare(
            'INSERT INTO site_posts (kind, series, title, summary, body, image_path, link_url, link_label, event_date,
                event_end_date, location, is_published, published_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['kind'], $data['series'], $data['title'], $data['summary'], $data['body'], $imagePath, $data['link_url'],
            $data['link_label'], $data['event_date'], $data['event_end_date'], $data['location'], $data['is_published'] ? 1 : 0,
            $data['is_published'] ? date('Y-m-d H:i:s') : null, $createdBy,
        ]);

        return (int) Database::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(int $id, array $data, ?array $image, bool $removeImage): void
    {
        $post = self::find($id);
        if ($post === null) {
            return;
        }
        $imagePath = $post['image_path'];
        if ($image !== null) {
            $imagePath = self::storeImage($image);
            self::deleteImageFile((string) $post['image_path']);
        } elseif ($removeImage) {
            self::deleteImageFile((string) $post['image_path']);
            $imagePath = null;
        }
        // First publish stamps the date; later edits keep it.
        $publishedAt = $data['is_published'] ? ($post['published_at'] ?: date('Y-m-d H:i:s')) : $post['published_at'];

        $stmt = Database::pdo()->prepare(
            'UPDATE site_posts SET kind = ?, series = ?, title = ?, summary = ?, body = ?, image_path = ?, link_url = ?, link_label = ?,
                event_date = ?, event_end_date = ?, location = ?, is_published = ?, published_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['kind'], $data['series'], $data['title'], $data['summary'], $data['body'], $imagePath, $data['link_url'],
            $data['link_label'], $data['event_date'], $data['event_end_date'], $data['location'], $data['is_published'] ? 1 : 0,
            $publishedAt, $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $post = self::find($id);
        if ($post === null) {
            return;
        }
        Database::pdo()->prepare('DELETE FROM site_posts WHERE id = ?')->execute([$id]);
        self::deleteImageFile((string) $post['image_path']);
    }

    /** Label shown on cards: "News", "The Palawan Scientist", "ICWPS", ... */
    public static function label(array $post): string
    {
        if ($post['kind'] === 'conference' && isset(self::SERIES[(string) $post['series']])) {
            return self::SERIES[(string) $post['series']];
        }

        return self::KINDS[(string) $post['kind']] ?? 'News';
    }

    /** "March 4, 2027" or "March 4 to 6, 2027" for events; '' when no date. */
    public static function eventDates(array $post): string
    {
        $start = (string) ($post['event_date'] ?? '');
        if ($start === '') {
            return '';
        }
        $from = strtotime($start);
        $end = (string) ($post['event_end_date'] ?? '');
        $to = $end !== '' ? strtotime($end) : false;
        if ($to === false || $to <= $from) {
            return date('F j, Y', $from);
        }
        if (date('Y-m', $from) === date('Y-m', $to)) {
            return date('F j', $from) . ' to ' . date('j, Y', $to);
        }

        return date('F j, Y', $from) . ' to ' . date('F j, Y', $to);
    }

    /** @return list<string> paragraphs of the body */
    public static function paragraphs(string $text): array
    {
        $parts = preg_split('/\R\s*\R/', trim($text)) ?: [];

        return array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    }

    private static function storeImage(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE ? 'Image is too large.' : 'Image upload failed.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \RuntimeException('Image upload failed.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('Image must be 5 MB or smaller.');
        }
        $info = @getimagesize($tmp);
        $mime = is_array($info) ? strtolower((string) ($info['mime'] ?? '')) : '';
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new \RuntimeException('Image must be a JPG, PNG, WebP or GIF file.');
        }
        $dir = BASE_PATH . '/public/' . self::RELATIVE_DIR;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload folder.');
        }
        $filename = 'post_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . self::ALLOWED_MIME[$mime];
        if (!move_uploaded_file($tmp, $dir . '/' . $filename)) {
            throw new \RuntimeException('Could not save the image.');
        }

        return self::RELATIVE_DIR . '/' . $filename;
    }

    private static function deleteImageFile(string $relativePath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '' || !str_starts_with($relativePath, self::RELATIVE_DIR . '/')) {
            return;
        }
        $real = realpath(BASE_PATH . '/public/' . $relativePath);
        $root = realpath(BASE_PATH . '/public/' . self::RELATIVE_DIR);
        if ($real !== false && $root !== false && str_starts_with($real, $root) && is_file($real)) {
            unlink($real);
        }
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['is_published'] = (bool) $row['is_published'];
        $path = (string) ($row['image_path'] ?? '');
        $row['image_url'] = $path !== '' ? HighlightSlide::imageUrl($path) : '';
        $row['date'] = (string) ($row['published_at'] ?: $row['created_at']);

        return $row;
    }
}
