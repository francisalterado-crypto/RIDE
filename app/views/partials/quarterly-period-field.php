<?php
/**
 * Reporting period field of a quarterly report in the web form (same rules as quarterly-reporting-period.php,
 * which renders the paper layout).
 *
 * @var array<string, mixed> $summaryData
 * @var string $quarterlyFormType
 */
use App\Support\FacultyFormDeadlines;
use App\Support\QuarterlyReporting;

$formTypeArg = $quarterlyFormType !== '' ? $quarterlyFormType : null;
$lockPeriod = FacultyFormDeadlines::locksFacultyReportingPeriod($formTypeArg);
$submissionOpen = FacultyFormDeadlines::isSubmissionOpen();
$selectedPeriod = QuarterlyReporting::periodFromSummary($summaryData);
if ($selectedPeriod === '') {
    $selectedPeriod = QuarterlyReporting::currentPeriodKey() ?? '';
}
$reportAsOf = trim((string) ($summaryData['report_as_of'] ?? ''));
if ($lockPeriod) {
    $locked = QuarterlyReporting::facultyLockedPeriod($summaryData, $formTypeArg);
    if ($locked !== null) {
        $selectedPeriod = $locked['reporting_period'];
        $reportAsOf = $locked['report_as_of'];
    }
}
$periodOptions = QuarterlyReporting::periodOptions(1, 0, $formTypeArg);
if ($selectedPeriod !== '' && !isset($periodOptions[$selectedPeriod])) {
    $periodOptions = [$selectedPeriod => QuarterlyReporting::periodLabel($selectedPeriod, $formTypeArg)] + $periodOptions;
}
$scheduleNote = FacultyFormDeadlines::scheduleNoticeText($formTypeArg);
$periodText = $selectedPeriod !== '' ? QuarterlyReporting::periodLabel($selectedPeriod, $formTypeArg) : '';
?>
<div class="webform-field">
    <label for="report-period">Reporting period <span class="webform-req">*</span></label>
    <?php if ($lockPeriod && $selectedPeriod !== ''): ?>
        <input type="hidden" name="reporting_period" value="<?= htmlspecialchars($selectedPeriod) ?>">
        <input type="hidden" name="report_as_of" value="<?= htmlspecialchars($reportAsOf) ?>">
        <input id="report-period" value="<?= htmlspecialchars($periodText) ?>" readonly>
        <small class="webform-hint">Set by the RIDE administration.</small>
    <?php elseif (!$submissionOpen): ?>
        <input type="hidden" name="reporting_period" value="<?= htmlspecialchars($selectedPeriod) ?>">
        <input type="hidden" name="report_as_of" value="<?= htmlspecialchars($reportAsOf) ?>">
        <input id="report-period" value="<?= htmlspecialchars($periodText !== '' ? $periodText : '-') ?>" readonly>
        <small class="webform-hint">Submissions open on <?= htmlspecialchars(FacultyFormDeadlines::submissionOpenDateText()) ?>.</small>
    <?php else: ?>
        <select id="report-period" name="reporting_period" required data-report-period>
            <option value="">Select period</option>
            <?php foreach ($periodOptions as $key => $label): ?>
                <option value="<?= htmlspecialchars((string) $key) ?>"<?= $key === $selectedPeriod ? ' selected' : '' ?>><?= htmlspecialchars((string) $label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($scheduleNote !== ''): ?><small class="webform-hint"><?= htmlspecialchars($scheduleNote) ?></small><?php endif; ?>
    <?php endif; ?>
</div>
