<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Current issue of The Palawan Scientist, read from the journal's public OJS 3.3 "issue/current" page
 * (the OJS REST API needs an API token, the page does not). Cached for a few hours in storage; when the
 * journal site cannot be reached the last good copy is used, and when there is none the caller gets null.
 */
final class JournalIssueFeed
{
    private const CACHE_SECONDS = 6 * 3600;
    private const RETRY_SECONDS = 15 * 60;
    private const TIMEOUT_SECONDS = 6;

    /**
     * @return array{title: string, url: string, cover: string, published: string, articles: list<array{title: string, url: string, authors: string}>}|null
     */
    public static function current(string $journalUrl): ?array
    {
        $journalUrl = rtrim(trim($journalUrl), '/');
        if ($journalUrl === '') {
            return null;
        }
        $cacheFile = STORAGE_PATH . '/cache/journal-issue-' . md5($journalUrl) . '.json';
        $cached = is_file($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : null;
        $age = is_file($cacheFile) ? time() - (int) filemtime($cacheFile) : PHP_INT_MAX;
        $hasIssue = is_array($cached) && isset($cached['issue']['title']);
        if (is_array($cached) && $age < ($hasIssue ? self::CACHE_SECONDS : self::RETRY_SECONDS)) {
            return $hasIssue ? $cached['issue'] : null;
        }

        $issue = self::fetch($journalUrl);
        if ($issue === null && $hasIssue) {
            $issue = $cached['issue']; // journal site down: keep showing the last issue we read
        }
        if (!is_dir(dirname($cacheFile))) {
            @mkdir(dirname($cacheFile), 0755, true);
        }
        @file_put_contents($cacheFile, (string) json_encode(['issue' => $issue], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $issue;
    }

    private static function fetch(string $journalUrl): ?array
    {
        // The site root redirects to the journal context (e.g. /tps); issue/current hangs off that.
        [$home, $homeUrl] = self::get($journalUrl);
        if ($home === null) {
            return null;
        }
        $base = rtrim((string) preg_replace('#/(index|issue|article)(/.*)?$#', '', $homeUrl), '/');
        [$html, $issueUrl] = self::get($base . '/issue/current');

        return $html !== null ? self::parse($html, $issueUrl) : null;
    }

    /** @return array{0: ?string, 1: string} body (null on failure) and final URL */
    private static function get(string $url): array
    {
        if (!function_exists('curl_init')) {
            return [null, $url];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_USERAGENT => 'RIDE public site (journal issue preview)',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $final = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return [is_string($body) && $code >= 200 && $code < 300 ? $body : null, $final !== '' ? $final : $url];
    }

    private static function parse(string $html, string $pageUrl): ?array
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadHTML('<?xml encoding="UTF-8">' . $html)) {
            return null;
        }
        $xp = new \DOMXPath($doc);
        $text = static fn (?\DOMNode $n): string => $n ? trim((string) preg_replace('/\s+/u', ' ', $n->textContent)) : '';
        $attr = static function (string $query, string $name) use ($xp): string {
            $node = $xp->query($query)->item(0);
            return $node instanceof \DOMElement ? trim($node->getAttribute($name)) : '';
        };

        // Prefer the schema.org PublicationIssue block, then OJS meta tags and headings.
        $ld = [];
        foreach ($xp->query('//script[@type="application/ld+json"]') as $script) {
            $data = json_decode($script->textContent, true);
            if (is_array($data) && ($data['@type'] ?? '') === 'PublicationIssue') {
                $ld = $data;
                break;
            }
        }
        $title = (string) ($ld['name'] ?? '');
        if ($title === '') {
            $title = $text($xp->query('//h1')->item(0)) ?: $attr('//meta[@property="og:image:alt"]', 'content');
        }
        // "Vol. 18 No. 1 (2026): The Palawan Scientist" -> "Vol. 18 No. 1 (2026)"
        $title = trim((string) preg_replace('/:\s*The Palawan Scientist$/i', '', $title));
        if ($title === '') {
            return null;
        }
        $cover = (string) ($ld['image'] ?? '') ?: $attr('//meta[@property="og:image"]', 'content')
            ?: $attr('//*[contains(@class,"cover")]//img', 'src');
        $published = (string) ($ld['datePublished'] ?? '');
        if ($published === '' && preg_match('/Published:?\s*([A-Za-z0-9 ,]+\d{4})/', $text($xp->query('//*[contains(@class,"published")]')->item(0)), $m)) {
            $published = $m[1];
        }

        $articles = [];
        $seen = [];
        foreach ($xp->query('//h3//a[contains(@href,"/article/view/")] | //*[contains(@class,"title")]/a[contains(@href,"/article/view/")]') as $link) {
            /** @var \DOMElement $link */
            $href = $link->getAttribute('href');
            if (!preg_match('#/article/view/\d+/?$#', $href) || isset($seen[$href])) {
                continue; // skip galley (PDF) links
            }
            $seen[$href] = true;
            $summary = $link->parentNode?->parentNode;
            $authors = $summary ? $text($xp->query('.//*[contains(@class,"authors")]', $summary)->item(0)) : '';
            $articles[] = ['title' => $text($link), 'url' => $href, 'authors' => $authors];
        }

        return [
            'title' => $title,
            'url' => (string) ($ld['url'] ?? '') ?: $pageUrl,
            'cover' => self::safeUrl($cover),
            'published' => $published !== '' && strtotime($published) !== false ? date('F j, Y', (int) strtotime($published)) : '',
            'articles' => array_values(array_filter($articles, static fn (array $a): bool => $a['title'] !== '' && self::safeUrl($a['url']) !== '')),
        ];
    }

    private static function safeUrl(string $url): string
    {
        return preg_match('#^https?://#i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }
}
