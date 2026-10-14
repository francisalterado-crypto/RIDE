<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalizes and validates researcher identifiers entered on the profile page.
 * Each method returns [normalizedValue|null, errorMessage|null]; an empty input is valid (null, null).
 */
final class ResearcherProfiles
{
    /** @return array{0: ?string, 1: ?string} */
    public static function orcid(string $input): array
    {
        $value = trim($input);
        if ($value === '') {
            return [null, null];
        }

        // Accept a bare iD or a full https://orcid.org/... link.
        $value = preg_replace('#^(https?://)?(www\.)?orcid\.org/#i', '', $value) ?? $value;
        $digits = strtoupper(str_replace(['-', ' '], '', $value));
        if (!preg_match('/^\d{15}[\dX]$/', $digits) || !self::orcidChecksumValid($digits)) {
            return [null, 'Please enter a valid ORCID iD, for example 0000-0002-1825-0097.'];
        }

        return [implode('-', str_split($digits, 4)), null];
    }

    /** @return array{0: ?string, 1: ?string} */
    public static function googleScholar(string $input): array
    {
        $value = trim($input);
        if ($value === '') {
            return [null, null];
        }

        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . $value;
        }
        $parts = parse_url($value);
        $host = strtolower((string) ($parts['host'] ?? ''));
        parse_str((string) ($parts['query'] ?? ''), $query);
        $scholarId = is_string($query['user'] ?? null) ? $query['user'] : '';

        if (!preg_match('/^scholar\.google\.[a-z.]+$/', $host) || !preg_match('/^[A-Za-z0-9_-]{6,20}$/', $scholarId)) {
            return [null, 'Please paste the link to your Google Scholar profile, for example https://scholar.google.com/citations?user=AbCdEfGhIjK.'];
        }

        return ['https://scholar.google.com/citations?user=' . $scholarId, null];
    }

    /** @return array{0: ?string, 1: ?string} */
    public static function researchGate(string $input): array
    {
        $value = trim($input);
        if ($value === '') {
            return [null, null];
        }

        // Accept a researchgate.net/profile/... link; extra path segments and query strings are trimmed off.
        if (!preg_match('#^(https?://)?([a-z]+\.)?researchgate\.net/profile/([A-Za-z0-9_.%-]+)#i', $value, $match)) {
            return [null, 'Please paste the link to your ResearchGate profile, for example https://www.researchgate.net/profile/Juan-Dela-Cruz.'];
        }

        return ['https://www.researchgate.net/profile/' . $match[3], null];
    }

    /** @return array{0: ?string, 1: ?string} */
    public static function github(string $input): array
    {
        $value = trim($input);
        if ($value === '') {
            return [null, null];
        }

        // Accept a username, @username, or a github.com link (repository paths are trimmed off).
        $value = preg_replace('#^(https?://)?(www\.)?github\.com/#i', '', $value) ?? $value;
        $username = ltrim(explode('/', $value)[0], '@');
        if (!preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9]|-(?=[A-Za-z0-9])){0,38}$/', $username)) {
            return [null, 'Please enter a valid GitHub username or profile link, for example https://github.com/username.'];
        }

        return ['https://github.com/' . $username, null];
    }

    /** ISO 7064 MOD 11-2 check digit used by ORCID. */
    private static function orcidChecksumValid(string $digits): bool
    {
        $total = 0;
        for ($i = 0; $i < 15; $i++) {
            $total = ($total + (int) $digits[$i]) * 2;
        }
        $result = (12 - ($total % 11)) % 11;
        $check = $result === 10 ? 'X' : (string) $result;

        return $check === $digits[15];
    }
}
