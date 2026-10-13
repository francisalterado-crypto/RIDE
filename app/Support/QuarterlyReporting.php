<?php

declare(strict_types=1);

namespace App\Support;

final class QuarterlyReporting
{
    public static function timezone(): \DateTimeZone
    {
        return new \DateTimeZone((string) \config('app.timezone', 'UTC'));
    }

    /** @return list<int> */
    public static function deadlineMonths(): array
    {
        return FacultyFormDeadlines::deadlineMonths();
    }

    public static function deadlineForPeriod(string $periodKey, ?string $formType = null): ?\DateTimeImmutable
    {
        if ($formType !== null && $formType !== '') {
            if (!FacultyFormDeadlines::hasDeadlineForFormType($formType)) {
                return null;
            }
        } elseif (!FacultyFormDeadlines::hasDeadline()) {
            return null;
        }

        if (!preg_match('/^(\d{4})-Q(\d+)$/', $periodKey, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $quarterIndex = (int) $matches[2] - 1;
        $months = self::deadlineMonths();
        if (!isset($months[$quarterIndex])) {
            return null;
        }

        $month = $months[$quarterIndex];

        return (new \DateTimeImmutable(
            sprintf('%d-%02d-01', $year, $month),
            self::timezone()
        ))->modify('last day of this month');
    }

    public static function reportAsOfForPeriod(string $periodKey): ?string
    {
        $deadline = self::deadlineForPeriod($periodKey);
        if ($deadline !== null) {
            return $deadline->format('Y-m-d');
        }

        if (!preg_match('/^(\d{4})-Q(\d+)$/', $periodKey, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $quarterIndex = (int) $matches[2] - 1;
        $months = self::deadlineMonths();
        if (!isset($months[$quarterIndex])) {
            return null;
        }

        $month = $months[$quarterIndex];

        return (new \DateTimeImmutable(
            sprintf('%d-%02d-01', $year, $month),
            self::timezone()
        ))->modify('last day of this month')->format('Y-m-d');
    }

    public static function periodLabel(string $periodKey, ?string $formType = null): string
    {
        if (!preg_match('/^(\d{4})-Q(\d+)$/', $periodKey, $matches)) {
            return $periodKey;
        }

        $year = (int) $matches[1];
        $quarterIndex = (int) $matches[2] - 1;
        $months = self::deadlineMonths();
        if (!isset($months[$quarterIndex])) {
            return $periodKey;
        }

        $month = $months[$quarterIndex];
        $label = self::monthLabel($month) . ' ' . $year;
        $deadline = self::deadlineForPeriod($periodKey, $formType);
        if ($deadline === null) {
            return $label;
        }

        return $label . ' (due ' . $deadline->format('M j, Y') . ')';
    }

    private static function monthLabel(int $month): string
    {
        return FacultyFormDeadlines::monthLabel($month);
    }

    public static function isPeriodKeyValid(string $periodKey): bool
    {
        if (!preg_match('/^(\d{4})-Q(\d+)$/', $periodKey, $matches)) {
            return false;
        }

        $quarterIndex = (int) $matches[2] - 1;

        return isset(self::deadlineMonths()[$quarterIndex]);
    }

    public static function isOverdue(string $periodKey, ?\DateTimeImmutable $now = null, ?string $formType = null): bool
    {
        if ($formType !== null && $formType !== '') {
            if (!FacultyFormDeadlines::hasDeadlineForFormType($formType)) {
                return false;
            }
        } elseif (!FacultyFormDeadlines::hasDeadline()) {
            return false;
        }

        $deadline = self::deadlineForPeriod($periodKey, $formType);
        if ($deadline === null) {
            return false;
        }

        $now ??= new \DateTimeImmutable('now', self::timezone());

        return $now > $deadline->setTime(23, 59, 59);
    }

    /** @return array<string, string> */
    public static function periodOptions(int $yearsBack = 1, int $yearsForward = 0, ?string $formType = null): array
    {
        $now = new \DateTimeImmutable('now', self::timezone());
        $startYear = (int) $now->format('Y') - $yearsBack;
        $endYear = (int) $now->format('Y') + $yearsForward;
        $options = [];

        for ($year = $endYear; $year >= $startYear; $year--) {
            foreach (self::deadlineMonths() as $index => $month) {
                $key = sprintf('%d-Q%d', $year, $index + 1);
                $options[$key] = self::periodLabel($key, $formType);
            }
        }

        return $options;
    }

    public static function currentPeriodKey(?\DateTimeImmutable $now = null): ?string
    {
        $now ??= new \DateTimeImmutable('now', self::timezone());
        $year = (int) $now->format('Y');
        $month = (int) $now->format('n');
        $day = (int) $now->format('j');
        $months = self::deadlineMonths();

        foreach ($months as $index => $deadlineMonth) {
            if ($month < $deadlineMonth || ($month === $deadlineMonth && $day <= (int) $now->format('t'))) {
                return sprintf('%d-Q%d', $year, $index + 1);
            }
        }

        if ($months === []) {
            return null;
        }

        return sprintf('%d-Q1', $year + 1);
    }

    /**
     * @param array<string, mixed> $post
     * @return array{reporting_period: string, report_as_of: string}|null
     */
    public static function parseRequest(array $post): ?array
    {
        $periodKey = trim((string) ($post['reporting_period'] ?? ''));
        if ($periodKey !== '' && self::isPeriodKeyValid($periodKey)) {
            $reportAsOf = self::reportAsOfForPeriod($periodKey);
            if ($reportAsOf !== null) {
                return [
                    'reporting_period' => $periodKey,
                    'report_as_of' => $reportAsOf,
                ];
            }
        }

        $legacyAsOf = trim((string) ($post['report_as_of'] ?? ''));
        if ($legacyAsOf !== '') {
            $inferred = self::periodKeyForDate($legacyAsOf);
            if ($inferred !== null) {
                $reportAsOf = self::reportAsOfForPeriod($inferred);

                return [
                    'reporting_period' => $inferred,
                    'report_as_of' => $reportAsOf ?? $legacyAsOf,
                ];
            }
        }

        return null;
    }

    public static function assertSubmissionOpen(): void
    {
        if (!FacultyFormDeadlines::appliesToCurrentUser() || FacultyFormDeadlines::isSubmissionOpen()) {
            return;
        }

        $openText = FacultyFormDeadlines::submissionOpenDateText();
        set_flash('error', 'Submissions are not open yet. The submission window opens on ' . $openText . '.');
        redirect(request_path() !== '' ? request_path() : 'dashboard');
    }

    /**
     * Reporting period for faculty when deadlines are declared (ignores posted changes).
     *
     * @param array<string, mixed> $existingSummary
     * @return array{reporting_period: string, report_as_of: string}|null
     */
    public static function facultyLockedPeriod(array $existingSummary = [], ?string $formType = null): ?array
    {
        if ($formType === null || $formType === '') {
            $formType = trim((string) ($existingSummary['form_type'] ?? ''));
        }

        if ($formType !== '' && !FacultyFormDeadlines::hasDeadlineForFormType($formType)) {
            return null;
        }

        if (!FacultyFormDeadlines::isDeclared()) {
            return null;
        }

        if ($formType === '' && !FacultyFormDeadlines::hasDeadline()) {
            return null;
        }

        $periodKey = self::periodFromSummary($existingSummary);
        if ($periodKey === '') {
            $periodKey = self::currentPeriodKey() ?? '';
        }

        if ($periodKey === '') {
            return null;
        }

        $reportAsOf = self::reportAsOfForPeriod($periodKey);
        if ($reportAsOf === null) {
            return null;
        }

        return [
            'reporting_period' => $periodKey,
            'report_as_of' => $reportAsOf,
        ];
    }

    public static function periodKeyForDate(string $date): ?string
    {
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $date, self::timezone());
        if (!$parsed instanceof \DateTimeImmutable) {
            return null;
        }

        $year = (int) $parsed->format('Y');
        $month = (int) $parsed->format('n');
        $months = self::deadlineMonths();

        foreach ($months as $index => $deadlineMonth) {
            if ($month !== $deadlineMonth) {
                continue;
            }

            $lastDay = (int) $parsed->format('t');
            if ((int) $parsed->format('j') === $lastDay) {
                return sprintf('%d-Q%d', $year, $index + 1);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $summary
     */
    public static function periodFromSummary(array $summary): string
    {
        $period = trim((string) ($summary['reporting_period'] ?? ''));
        if ($period !== '' && self::isPeriodKeyValid($period)) {
            return $period;
        }

        $asOf = trim((string) ($summary['report_as_of'] ?? ''));
        if ($asOf !== '') {
            return self::periodKeyForDate($asOf) ?? '';
        }

        return '';
    }

    public static function periodLabelFromSummary(array $summary, ?string $formType = null): string
    {
        $period = self::periodFromSummary($summary);
        if ($period === '') {
            $asOf = trim((string) ($summary['report_as_of'] ?? ''));

            return $asOf !== '' ? 'As of ' . $asOf : '';
        }

        if ($formType === null || $formType === '') {
            $formType = trim((string) ($summary['form_type'] ?? ''));
        }

        return self::periodLabel($period, $formType !== '' ? $formType : null);
    }
}
