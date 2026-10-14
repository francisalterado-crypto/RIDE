<?php
/** Reporting period and announcement cards shown at the top of role dashboards. */
/** @var array<string, mixed>|null $facultyReportingNotice */
/** @var string $globalMessage */
$e ??= static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
?>
<?php
$hasReporting = !empty($facultyReportingNotice);
$hasAnnouncement = trim((string) $globalMessage) !== '';
$submissionOpen = (bool) ($facultyReportingNotice['submissionOpen'] ?? true);
$periodLabel = (string) ($facultyReportingNotice['currentPeriodLabel'] ?? '');
$scheduleNotice = (string) ($facultyReportingNotice['scheduleNotice'] ?? '');
?>
<?php if ($hasReporting || $hasAnnouncement): ?>
    <div class="fd-notices<?= ($hasReporting && $hasAnnouncement) ? '' : ' fd-notices--single' ?>">
        <?php if ($hasReporting): ?>
            <section class="fd-report-card" aria-label="Quarterly reporting">
                <div class="fd-report-icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></div>
                <div class="fd-report-body">
                    <div class="fd-report-top">
                        <span class="fd-report-eyebrow">Quarterly reporting</span>
                        <?php if ($submissionOpen): ?>
                            <span class="fd-report-chip fd-report-chip--open">Open for submissions</span>
                        <?php else: ?>
                            <span class="fd-report-chip fd-report-chip--closed">Opens <?= $e(\App\Support\FacultyFormDeadlines::submissionOpenDateText()) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($periodLabel !== ''): ?>
                        <p class="fd-report-period"><?= $e($periodLabel) ?></p>
                        <p class="fd-report-caption">Current reporting period</p>
                    <?php endif; ?>
                    <?php if ($scheduleNotice !== ''): ?>
                        <p class="fd-report-note"><?= $e($scheduleNotice) ?></p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php if ($hasAnnouncement): ?>
            <section class="fd-announcement" aria-label="Announcement">
                <div class="fd-announcement-head">
                    <span class="fd-announcement-icon" aria-hidden="true"><i class="fas fa-bullhorn"></i></span>
                    <div>
                        <span class="fd-announcement-title">Announcement</span>
                        <span class="fd-announcement-from">From the RIDE office</span>
                    </div>
                </div>
                <p class="fd-announcement-text"><?= nl2br($e($globalMessage)) ?></p>
            </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
