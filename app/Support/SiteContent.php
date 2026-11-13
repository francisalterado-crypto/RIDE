<?php

declare(strict_types=1);

namespace App\Support;

use App\Controllers\HighlightController;
use App\Models\AppSetting;

/**
 * Editable text blocks of the public site (stored in app_settings), with plain defaults.
 */
final class SiteContent
{
    /** field => [setting key, label, default, type] */
    public const FIELDS = [
        'tagline_text' => ['site_tagline', 'Hero tagline', 'News, publications and conferences of Western Philippines University research and extension.', 'text'],
        'wpu_url' => ['site_wpu_url', 'Western Philippines University website', 'https://wpu.edu.ph', 'url'],
        'about_text' => ['site_about', 'Footer: about RIDE',
            'Research, Innovation, Development and Extension (RIDE) carries out the research and extension agenda of Western Philippines University. It publishes The Palawan Scientist and the RIDE Conference Proceedings and holds the International Conference on the West Philippine Sea.',
            'textarea'],
        'contact_address' => ['site_contact_address', 'Contact: address', 'Western Philippines University Main Campus, San Juan, Aborlan, Palawan 5302', 'text'],
        'contact_email' => ['site_contact_email', 'Contact: email', '', 'email'],
        'contact_phone' => ['site_contact_phone', 'Contact: phone', '', 'text'],
        'contact_facebook_url' => ['site_contact_facebook', 'Contact: Facebook page', '', 'url'],
        'journal_description' => ['site_journal_description', 'The Palawan Scientist: description',
            "The Palawan Scientist is the open-access, externally peer-reviewed multidisciplinary journal of Western Philippines University, indexed in Scopus and in Clarivate Analytics (Web of Science). It charges no processing or publication fees.\n\n"
            . 'Each issue carries original articles, case studies, review papers and research notes. Accepted papers are posted online ahead of the publication date, and all articles are free to download, share and reuse under a Creative Commons Attribution Non-commercial 4.0 license. Its articles are also indexed in the ASEAN Citation Index, Crossref, EBSCO, Scilit, Philippine E-Journals and Google Scholar.',
            'textarea'],
        'journal_details' => ['site_journal_details', 'The Palawan Scientist: key details (one "Label: value" per line)',
            "Publisher: Western Philippines University\nFrequency: Two issues a year, June and December\nAccess: Open access, no fees\nReview: External peer review\nIndexed in: Scopus, Clarivate Analytics (Web of Science)\nAlso indexed in: ASEAN Citation Index, Crossref, EBSCO, Scilit, Philippine E-Journals, Google Scholar\nPrint ISSN: 1656-4707\nOnline ISSN: 2467-5903",
            'textarea'],
        'journal_url' => ['site_journal_url', 'The Palawan Scientist: website', 'https://palawanscientist.org', 'url'],
        'icwps_name' => ['site_icwps_name', 'ICWPS: full name', 'International Conference on the West Philippine Sea', 'text'],
        'icwps_description' => ['site_icwps_description', 'ICWPS: description',
            "The International Conference on the West Philippine Sea brings together researchers, extension practitioners, community workers, government, civil society and the private sector to share research, projects and experiences that promote the sustainable management and inclusive growth of coastal and marine areas in the West Philippine Sea.\n\n"
            . 'The conference is organized by Western Philippines University, which has carried out research and development work in the Kalayaan Island Group since 2004.',
            'textarea'],
        'icwps_details' => ['site_icwps_details', 'ICWPS: key details (one "Label: value" per line)',
            "Organizer: Western Philippines University\nNext edition: 6th ICWPS, October 2027\nVenue: Palawan, Philippines\nPresentations: Oral and poster",
            'textarea'],
        'icwps_url' => ['site_icwps_url', 'ICWPS: website', 'https://icwps.org', 'url'],
        'racp_description' => ['site_racp_description', 'RIDE Conference Proceedings: description',
            "The RIDE Conference Proceedings is published by Western Philippines University as part of its Research, Innovation, Development and Extension agenda. It gathers papers that have passed the University's internal review, the step that readies research for presentation at regional, national and international scientific conferences.\n\n"
            . 'Each volume reflects the work of WPU researchers, faculty and students across agriculture, fisheries, forestry, engineering, environmental science, education, arts and sciences, and rural development.',
            'textarea'],
        'racp_details' => ['site_racp_details', 'RIDE Conference Proceedings: key details (one "Label: value" per line)',
            "Publisher: Western Philippines University\nFrequency: Annual, every June\nContent: Papers from RIDE conferences",
            'textarea'],
        'racp_url' => ['site_racp_url', 'RIDE Conference Proceedings: website', 'https://racp.wpu.edu.ph', 'url'],
    ];

    /** @return list<array{0: string, 1: string}> "Label: value" lines as pairs */
    public static function details(string $text): array
    {
        $pairs = [];
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                $pairs[] = [trim($parts[0]), trim($parts[1])];
            }
        }

        return $pairs;
    }

    /** @return array<string, string> field => current value */
    public static function all(): array
    {
        $values = [];
        foreach (self::FIELDS as $field => [$key, , $default]) {
            $values[$field] = AppSetting::get($key, $default);
        }

        return $values;
    }

    /** @return array{vision: string, mission: string} the same texts as the portal login page */
    public static function visionMission(): array
    {
        return [
            'vision' => AppSetting::get('portal_vision', HighlightController::DEFAULT_VISION),
            'mission' => AppSetting::get('portal_mission', HighlightController::DEFAULT_MISSION),
        ];
    }

    public static function put(string $field, string $value, ?int $userId): void
    {
        if (isset(self::FIELDS[$field])) {
            AppSetting::put(self::FIELDS[$field][0], $value, $userId);
        }
    }

    /** Host part for display, e.g. "palawanscientist.org". */
    public static function host(string $url): string
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return preg_replace('/^www\./', '', $host) ?? $host;
    }

    /** A safe http(s) URL or ''. */
    public static function cleanUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
    }
}
