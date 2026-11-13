<?php

/** @var array<string, mixed> $summaryData */
/** @var bool|null $colspanRow */
/** @var string|null $quarterlyFormType */

$summaryData = $summaryData ?? [];
$quarterlyFormType = trim((string) ($quarterlyFormType ?? $summaryData['form_type'] ?? ''));

$lockPeriod = \App\Support\FacultyFormDeadlines::locksFacultyReportingPeriod(
    $quarterlyFormType !== '' ? $quarterlyFormType : null
);
$submissionOpen = \App\Support\FacultyFormDeadlines::isSubmissionOpen();
$hasFormDeadline = $quarterlyFormType !== ''
    ? \App\Support\FacultyFormDeadlines::hasDeadlineForFormType($quarterlyFormType)
    : \App\Support\FacultyFormDeadlines::hasDeadline();

$selectedPeriod = \App\Support\QuarterlyReporting::periodFromSummary($summaryData);
if ($selectedPeriod === '') {
    $selectedPeriod = \App\Support\QuarterlyReporting::currentPeriodKey() ?? '';
}
if ($lockPeriod) {
    $locked = \App\Support\QuarterlyReporting::facultyLockedPeriod($summaryData, $quarterlyFormType !== '' ? $quarterlyFormType : null);
    if ($locked !== null) {
        $selectedPeriod = $locked['reporting_period'];
        $summaryData['report_as_of'] = $locked['report_as_of'];
        $summaryData['reporting_period'] = $locked['reporting_period'];
    }
}

$periodOptions = \App\Support\QuarterlyReporting::periodOptions(
    1,
    0,
    $quarterlyFormType !== '' ? $quarterlyFormType : null
);
// After the last deadline month the current period belongs to next year, which periodOptions() leaves out.
if ($selectedPeriod !== '' && !isset($periodOptions[$selectedPeriod])) {
    $periodOptions = [
        $selectedPeriod => \App\Support\QuarterlyReporting::periodLabel(
            $selectedPeriod,
            $quarterlyFormType !== '' ? $quarterlyFormType : null
        ),
    ] + $periodOptions;
}
$scheduleNote = \App\Support\FacultyFormDeadlines::scheduleNoticeText(
    $quarterlyFormType !== '' ? $quarterlyFormType : null
);
$periodLabel = $selectedPeriod !== ''
    ? \App\Support\QuarterlyReporting::periodLabel(
        $selectedPeriod,
        $quarterlyFormType !== '' ? $quarterlyFormType : null
    )
    : '';
$reportAsOf = trim((string) ($summaryData['report_as_of'] ?? ''));
$colspan = !empty($colspanRow) ? ' colspan="3"' : '';

?>

<tr>
    <th>Reporting period</th>
    <td<?= $colspan ?>>
        <?php if (!$hasFormDeadline): ?>
            <p class="muted" style="margin: 0 0 0.5rem;"><strong>No Deadline</strong></p>
        <?php endif; ?>

        <?php if (!$submissionOpen): ?>
            <p class="muted" style="margin: 0 0 0.5rem; color: #8a4b00;">
                <strong>Submissions are not open yet.</strong>
                Opens on <?= htmlspecialchars(\App\Support\FacultyFormDeadlines::submissionOpenDateText()) ?>.
            </p>
        <?php endif; ?>

        <?php if ($lockPeriod && $selectedPeriod !== ''): ?>
            <strong><?= htmlspecialchars($periodLabel) ?></strong>
            <input type="hidden" name="reporting_period" value="<?= htmlspecialchars($selectedPeriod) ?>">
            <input type="hidden" name="report_as_of" value="<?= htmlspecialchars($reportAsOf) ?>">
            <p class="muted" style="margin: 0.35rem 0 0;">
                Reporting period is set by administration and cannot be changed.
                <?= $scheduleNote !== '' ? htmlspecialchars($scheduleNote) : '' ?>
            </p>
        <?php elseif (!$submissionOpen): ?>
            <input type="hidden" name="reporting_period" value="<?= htmlspecialchars($selectedPeriod) ?>">
            <input type="hidden" name="report_as_of" value="<?= htmlspecialchars($reportAsOf) ?>">
            <span class="muted"><?= $periodLabel !== '' ? htmlspecialchars($periodLabel) : '-' ?></span>
        <?php else: ?>
            <select name="reporting_period" required>
                <option value="">Select period</option>
                <?php foreach ($periodOptions as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"<?= $key === $selectedPeriod ? ' selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($scheduleNote !== ''): ?>
                <p class="muted" style="margin: 0.35rem 0 0;"><?= htmlspecialchars($scheduleNote) ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </td>
</tr>
