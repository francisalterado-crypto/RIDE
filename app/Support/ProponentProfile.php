<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * The proponent's details as the RDO forms ask for them (Part I of the Research Proposal Application, the
 * contact person of the request forms). They are kept on the user profile and copied into each form when it
 * is saved, so a submitted form keeps the details it was signed with.
 */
final class ProponentProfile
{
    public const TITLE_PREFIXES = ['Dr.', 'Prof.', 'Asso. Prof.', 'Asst. Prof.', 'Instructor', 'Staff'];
    public const SEXES = ['Male', 'Female'];

    /**
     * Profile fields from the profile form. POST text is title-cased globally, so option values are matched
     * case-insensitively.
     *
     * @param array<string, mixed> $post
     * @return array{middle_name: string, title_prefix: string, sex: string, position: string, contact_number: string}
     */
    public static function fromPost(array $post): array
    {
        return [
            'middle_name' => mb_substr(trim((string) ($post['middle_name'] ?? '')), 0, 80),
            'title_prefix' => self::option((string) ($post['title_prefix'] ?? ''), self::TITLE_PREFIXES),
            'sex' => self::option((string) ($post['sex'] ?? ''), self::SEXES),
            'position' => mb_substr(trim((string) ($post['position'] ?? '')), 0, 120),
            'contact_number' => mb_substr(trim((string) ($post['contact_number'] ?? '')), 0, 30),
        ];
    }

    /**
     * The applicant_* values the Research Proposal Application stores, taken from a user's profile.
     *
     * @return array<string, string>
     */
    public static function applicantFields(int $userId): array
    {
        $user = User::findById($userId) ?? [];

        return [
            'applicant_last_name' => (string) ($user['last_name'] ?? ''),
            'applicant_first_name' => (string) ($user['first_name'] ?? ''),
            'applicant_middle_name' => (string) ($user['middle_name'] ?? ''),
            'applicant_title_prefix' => (string) ($user['title_prefix'] ?? ''),
            'applicant_sex' => (string) ($user['sex'] ?? ''),
            'applicant_position' => (string) ($user['position'] ?? ''),
            'applicant_email' => (string) ($user['email'] ?? ''),
            'applicant_contact_number' => (string) ($user['contact_number'] ?? ''),
            'applicant_college_department' => (string) ($user['college_name'] ?? ''),
            'applicant_program' => (string) ($user['program'] ?? ''),
            'applicant_campus' => (string) ($user['campus_name'] ?? ''),
            'applicant_google_scholar_link' => (string) ($user['google_scholar_url'] ?? ''),
            'applicant_researchgate_link' => (string) ($user['researchgate_url'] ?? ''),
        ];
    }

    /** Full name as printed on the forms, e.g. "Dr. Juan A. Dela Cruz". */
    public static function displayName(array $user): string
    {
        $middle = trim((string) ($user['middle_name'] ?? ''));
        $parts = array_filter([
            trim((string) ($user['title_prefix'] ?? '')),
            trim((string) ($user['first_name'] ?? '')),
            $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '',
            trim((string) ($user['last_name'] ?? '')),
        ], static fn (string $part): bool => $part !== '');

        return implode(' ', $parts);
    }

    /** @param list<string> $allowed */
    private static function option(string $value, array $allowed): string
    {
        foreach ($allowed as $option) {
            if (strcasecmp(trim($value), $option) === 0) {
                return $option;
            }
        }

        return '';
    }
}
