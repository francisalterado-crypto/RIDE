<?php
/**
 * Submission deadline of a quarterly report for one reporting period, as set under Settings > Faculty Deadlines.
 *
 * @var string $deadlineFormType report form type (e.g. trainings_conducted)
 * @var string $deadlinePeriod period key (e.g. 2027-Q1), or '' when no period is selected
 */
use App\Support\FacultyFormDeadlines;
use App\Support\QuarterlyReporting;

$deadlineFormType = (string) ($deadlineFormType ?? '');
$deadlinePeriod = (string) ($deadlinePeriod ?? '');
$deadlineDate = $deadlinePeriod !== '' ? QuarterlyReporting::deadlineForPeriod($deadlinePeriod, $deadlineFormType !== '' ? $deadlineFormType : null) : null;
$scheduleText = FacultyFormDeadlines::scheduleNoticeText($deadlineFormType !== '' ? $deadlineFormType : null);

$deadlineState = 'none';
$deadlineDetail = '';
if ($deadlineDate !== null) {
    $today = new DateTimeImmutable('today', QuarterlyReporting::timezone());
    $days = (int) $today->diff($deadlineDate->setTime(0, 0))->format('%r%a');
    if ($days < 0) {
        $deadlineState = 'overdue';
        $deadlineDetail = abs($days) . ' ' . (abs($days) === 1 ? 'day' : 'days') . ' overdue';
    } elseif ($days === 0) {
        $deadlineState = 'soon';
        $deadlineDetail = 'Due today';
    } else {
        $deadlineState = $days <= 14 ? 'soon' : 'open';
        $deadlineDetail = $days . ' ' . ($days === 1 ? 'day' : 'days') . ' remaining';
    }
}
?>
<?php if ($deadlineDate !== null || $scheduleText !== ''): ?>
    <div class="deadline-notice deadline-notice--<?= $deadlineState ?>" role="note">
        <i class="fas fa-calendar-day" aria-hidden="true"></i>
        <div>
            <?php if ($deadlineDate !== null): ?>
                <strong>Deadline for the <?= htmlspecialchars(preg_replace('/\s*\(due [^)]*\)$/', '', QuarterlyReporting::periodLabel($deadlinePeriod))) ?> report: <?= htmlspecialchars($deadlineDate->format('F j, Y')) ?></strong>
                <span class="deadline-notice-detail"><?= htmlspecialchars($deadlineDetail) ?></span>
                <?php if ($scheduleText !== ''): ?><small><?= htmlspecialchars($scheduleText) ?>.</small><?php endif; ?>
            <?php else: ?>
                <strong>Submission deadline</strong>
                <small><?= htmlspecialchars(rtrim($scheduleText, '.')) ?>.<?= $deadlinePeriod === '' ? ' Select a reporting period to see its deadline.' : '' ?></small>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
