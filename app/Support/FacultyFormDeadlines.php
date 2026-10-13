<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Auth;
use App\Models\AppSetting;

final class FacultyFormDeadlines
{
    public const SETTING_KEY = 'faculty_quarterly_deadline_months';

    public const GROUP_RESEARCH = 'research';

    public const GROUP_EXTENSION = 'extension';

    public const GROUP_PROJECT_MONITORING = 'project_monitoring';

    public const GROUP_CONSOLIDATED = 'consolidated';

    /** @var list<int> */
    private const DEFAULT_MONTHS = [3, 6, 9];

    /** @var array<string, string> */
    public const FORM_GROUP_LABELS = [
        self::GROUP_RESEARCH => 'Research monitoring forms',
        self::GROUP_EXTENSION => 'Extension monitoring forms',
        self::GROUP_PROJECT_MONITORING => 'Project monitoring forms',
        self::GROUP_CONSOLIDATED => 'College consolidated quarterly reports',
    ];

    /** @var array<string, string> */
    private const FORM_TYPE_GROUPS = [
        'completed_researches' => self::GROUP_RESEARCH,
        'ongoing_researches' => self::GROUP_RESEARCH,
        'research_output_published' => self::GROUP_RESEARCH,
        'research_output_presented' => self::GROUP_RESEARCH,
        'commercialized' => self::GROUP_RESEARCH,
        'resulted_in_extension' => self::GROUP_RESEARCH,
        'journal_citation' => self::GROUP_RESEARCH,
        'book_citation' => self::GROUP_RESEARCH,
        'inventions_um_copyrights' => self::GROUP_RESEARCH,
        'linkages' => self::GROUP_RESEARCH,
        'trainings_conducted' => self::GROUP_EXTENSION,
        'technical_advisory' => self::GROUP_EXTENSION,
        'extension_linkages' => self::GROUP_EXTENSION,
        'outreach_activities' => self::GROUP_EXTENSION,
        'technology_adoption' => self::GROUP_EXTENSION,
        'progress_report' => self::GROUP_PROJECT_MONITORING,
        'terminal_report' => self::GROUP_PROJECT_MONITORING,
        'terminal_report_assessment_form' => self::GROUP_PROJECT_MONITORING,
        'obr_matrix' => self::GROUP_PROJECT_MONITORING,
        'consolidated_completed_researches' => self::GROUP_CONSOLIDATED,
        'consolidated_ongoing_researches' => self::GROUP_CONSOLIDATED,
        'consolidated_research_output_published' => self::GROUP_CONSOLIDATED,
        'consolidated_research_output_presented' => self::GROUP_CONSOLIDATED,
        'consolidated_commercialized' => self::GROUP_CONSOLIDATED,
        'consolidated_resulted_in_extension' => self::GROUP_CONSOLIDATED,
        'consolidated_journal_citation' => self::GROUP_CONSOLIDATED,
        'consolidated_book_citation' => self::GROUP_CONSOLIDATED,
        'consolidated_inventions_um_copyrights' => self::GROUP_CONSOLIDATED,
        'consolidated_linkages' => self::GROUP_CONSOLIDATED,
    ];

    public static function canManage(): bool
    {
        return MonitoringRoles::isVpride()
            || MonitoringRoles::isDirectorResearch()
            || MonitoringRoles::isDirectorExtension();
    }

    /** Submitters bound by declared deadlines: faculty and extension workers (who file the ESO quarterly forms). */
    public static function appliesToCurrentUser(): bool
    {
        return Auth::hasRole('faculty', MonitoringRoles::EXTENSION_WORKER);
    }

    public static function isDeclared(): bool
    {
        return AppSetting::has(self::SETTING_KEY);
    }

    public static function hasDeadline(): bool
    {
        return self::config()['deadline_enabled'];
    }

    public static function hasDeadlineForFormType(string $formType): bool
    {
        if (!self::hasDeadline()) {
            return false;
        }

        $group = self::formGroupForFormType($formType);
        if ($group === null) {
            return false;
        }

        return in_array($group, self::activatedFormGroups(), true);
    }

    public static function formGroupForFormType(string $formType): ?string
    {
        $formType = trim($formType);

        return self::FORM_TYPE_GROUPS[$formType] ?? null;
    }

    /** @return list<string> */
    public static function activatedFormGroups(): array
    {
        return self::config()['activated_groups'];
    }

    /** Faculty may not change reporting period once admins saved deadlines for this form type. */
    public static function locksFacultyReportingPeriod(?string $formType = null): bool
    {
        if (!self::isDeclared() || !self::appliesToCurrentUser()) {
            return false;
        }

        if ($formType !== null && $formType !== '') {
            return self::hasDeadlineForFormType($formType);
        }

        return self::hasDeadline();
    }

    /**
     * @return array{deadline_enabled: bool, months: list<int>, open_date: string, activated_groups: list<string>}
     */
    public static function config(): array
    {
        $raw = AppSetting::get(self::SETTING_KEY, '');
        if ($raw === '') {
            return self::defaultConfig();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return self::defaultConfig();
        }

        if (array_is_list($decoded)) {
            return self::normalizeConfig([
                'deadline_enabled' => true,
                'months' => $decoded,
                'open_date' => '',
                'activated_groups' => array_keys(self::FORM_GROUP_LABELS),
            ]);
        }

        return self::normalizeConfig($decoded);
    }

    /**
     * @return array{deadline_enabled: bool, months: list<int>, open_date: string, activated_groups: list<string>}
     */
    private static function defaultConfig(): array
    {
        return [
            'deadline_enabled' => false,
            'months' => self::DEFAULT_MONTHS,
            'open_date' => '',
            'activated_groups' => [],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{deadline_enabled: bool, months: list<int>, open_date: string, activated_groups: list<string>}
     */
    private static function normalizeConfig(array $input): array
    {
        $months = self::parseMonthList($input['months'] ?? $input['deadline_months'] ?? []);
        if ($months === [] && array_key_exists('months', $input) && is_array($input['months']) && $input['months'] === []) {
            $months = [];
        } elseif ($months === [] && !array_key_exists('deadline_enabled', $input)) {
            $months = self::DEFAULT_MONTHS;
        }

        $activatedGroups = self::parseActivatedGroups($input);
        $deadlineEnabled = array_key_exists('deadline_enabled', $input)
            ? (bool) $input['deadline_enabled']
            : ($activatedGroups !== [] && $months !== []);

        if ($months === [] && $deadlineEnabled) {
            $deadlineEnabled = false;
            $months = self::DEFAULT_MONTHS;
        }

        if ($activatedGroups === []) {
            $deadlineEnabled = false;
        }

        $openDate = trim((string) ($input['open_date'] ?? $input['submission_open_date'] ?? ''));
        if ($openDate !== '' && self::parseDate($openDate) === null) {
            $openDate = '';
        }

        return [
            'deadline_enabled' => $deadlineEnabled,
            'months' => $months !== [] ? $months : self::DEFAULT_MONTHS,
            'open_date' => $openDate,
            'activated_groups' => $activatedGroups,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return list<string>
     */
    private static function parseActivatedGroups(array $input): array
    {
        $rawGroups = $input['activated_groups'] ?? $input['activated_form_groups'] ?? null;
        if (!is_array($rawGroups)) {
            if (!array_key_exists('activated_groups', $input) && !array_key_exists('activated_form_groups', $input)) {
                return array_keys(self::FORM_GROUP_LABELS);
            }

            return [];
        }

        $activatedGroups = [];
        foreach ($rawGroups as $group) {
            $key = (string) $group;
            if (array_key_exists($key, self::FORM_GROUP_LABELS)) {
                $activatedGroups[] = $key;
            }
        }

        $activatedGroups = array_values(array_unique($activatedGroups));
        sort($activatedGroups);

        return $activatedGroups;
    }

    /**
     * @param mixed $rawMonths
     * @return list<int>
     */
    private static function parseMonthList(mixed $rawMonths): array
    {
        if (!is_array($rawMonths)) {
            return [];
        }

        $months = [];
        foreach ($rawMonths as $month) {
            $m = (int) $month;
            if ($m >= 1 && $m <= 12) {
                $months[] = $m;
            }
        }

        $months = array_values(array_unique($months));
        sort($months);

        return $months;
    }

    /** @return list<int> */
    public static function deadlineMonths(): array
    {
        $months = self::config()['months'];

        return $months !== [] ? $months : self::DEFAULT_MONTHS;
    }

    public static function submissionOpenDate(): ?\DateTimeImmutable
    {
        $openDate = self::config()['open_date'];
        if ($openDate === '') {
            return null;
        }

        return self::parseDate($openDate);
    }

    public static function isSubmissionOpen(?\DateTimeImmutable $now = null): bool
    {
        $open = self::submissionOpenDate();
        if ($open === null) {
            return true;
        }

        $now ??= new \DateTimeImmutable('now', QuarterlyReporting::timezone());

        return $now >= $open->setTime(0, 0, 0);
    }

    public static function submissionOpenDateText(): string
    {
        $open = self::submissionOpenDate();

        return $open !== null ? $open->format('M j, Y') : '';
    }

    /**
     * @param array{deadline_enabled?: bool, months?: list<int>, open_date?: string, activated_groups?: list<string>, activated_form_groups?: list<string>} $config
     */
    public static function saveConfig(array $config, ?int $updatedBy = null): void
    {
        $normalized = self::normalizeConfig($config);
        AppSetting::put(
            self::SETTING_KEY,
            json_encode($normalized, JSON_THROW_ON_ERROR),
            $updatedBy
        );
    }

    public static function deadlineSummaryText(): string
    {
        $config = self::config();
        $groupLabels = [];
        foreach ($config['activated_groups'] as $group) {
            $groupLabels[] = self::FORM_GROUP_LABELS[$group] ?? $group;
        }

        if (!self::hasDeadline()) {
            $summary = 'No deadline (open submission)';
            if ($groupLabels !== []) {
                $summary .= '; activated for ' . implode(', ', $groupLabels) . ' (no closing dates selected)';
            }
            $openText = self::submissionOpenDateText();
            if ($openText !== '') {
                $summary .= '; opens ' . $openText;
            }

            return $summary;
        }

        $parts = [];
        foreach (self::deadlineMonths() as $month) {
            $parts[] = 'end of ' . self::monthLabel($month);
        }

        $summary = implode(', ', $parts);
        if ($groupLabels !== []) {
            $summary .= ' for ' . implode(', ', $groupLabels);
        }
        $openText = self::submissionOpenDateText();
        if ($openText !== '') {
            $summary .= '; opens ' . $openText;
        }

        return $summary;
    }

    public static function scheduleNoticeText(?string $formType = null): string
    {
        if ($formType !== null && $formType !== '' && !self::hasDeadlineForFormType($formType)) {
            $openText = self::submissionOpenDateText();
            if ($openText !== '' && !self::isSubmissionOpen()) {
                return 'No Deadline. Submissions open on ' . $openText . '.';
            }

            return 'No Deadline.';
        }

        if (!self::isDeclared()) {
            return '';
        }

        if (!self::hasDeadline()) {
            $openText = self::submissionOpenDateText();

            return $openText !== ''
                ? 'No Deadline. Submissions open on ' . $openText . '.'
                : 'No Deadline.';
        }

        $parts = [];
        foreach (self::deadlineMonths() as $month) {
            $parts[] = 'end of ' . self::monthLabel($month);
        }
        $summary = 'Due at the ' . implode(', ', $parts);
        if (!self::isSubmissionOpen()) {
            return 'Submissions open on ' . self::submissionOpenDateText() . '. ' . $summary;
        }

        return $summary;
    }

    public static function monthLabel(int $month): string
    {
        $labels = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        return $labels[$month] ?? 'Month ' . $month;
    }

    /** @return array<string, string> */
    public static function monthOptions(): array
    {
        $options = [];
        for ($m = 1; $m <= 12; $m++) {
            $options[(string) $m] = self::monthLabel($m);
        }

        return $options;
    }

    public static function requireManager(): void
    {
        if (!self::canManage()) {
            http_response_code(403);
            view('errors.403');
            exit;
        }
    }

    public static function currentManagerId(): int
    {
        return (int) (Auth::user()['id'] ?? 0);
    }

    private static function parseDate(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date, QuarterlyReporting::timezone());

        return $parsed instanceof \DateTimeImmutable ? $parsed : null;
    }
}
