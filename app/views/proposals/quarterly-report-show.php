<?php
/**
 * View page of a quarterly report in the web app. Print Preview downloads the filled office form (.xlsx for
 * ESO, .docx for RDO), which carries the letterhead.
 */
/** @var string $reportType */
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */
/** @var list<array<string, mixed>> $documents */
use App\Support\ExtensionActivityTypes;
use App\Support\QuarterlyReporting;

$definition = ExtensionActivityTypes::get($reportType) ?? [];
$office = ExtensionActivityTypes::officeOf($reportType);
$terms = ExtensionActivityTypes::officeTerms($office);
$summaryData = json_decode((string) ($proposal['summary'] ?? ''), true);
$summaryData = is_array($summaryData) ? $summaryData : [];
$reportLabel = proposal_form_label($proposal);
$period = (string) ($summaryData['reporting_period'] ?? '');
$status = (string) ($proposal['status'] ?? 'draft');
$narrative = array_filter([
    'Challenges' => trim((string) ($summaryData['challenges'] ?? '')),
    'Best practices' => trim((string) ($summaryData['best_practices'] ?? '')),
    'Lessons learned and recommendations' => trim((string) ($summaryData['lessons_learned'] ?? '')),
]);
$pageTitle = $reportLabel . ' | RIDE Hub';
$pageHeading = $reportLabel;
$pageSubtitle = (string) ($proposal['title'] ?? '');
?>
<div class="page-actions-bar">
    <?php $fileFormat = \App\Services\QuarterlyReportFile::format($reportType); ?>
    <?php if (\App\Services\QuarterlyReportFile::available($reportType)): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . (int) $proposal['id'] . '/print') ?>" title="Download the filled office form (letterhead included) to view or print"><i class="fas fa-print" aria-hidden="true"></i> Print Preview (.<?= htmlspecialchars($fileFormat) ?>)</a>
    <?php else: ?>
        <button type="button" class="btn btn-outline" disabled title="The office form for this report has not been added yet."><i class="fas fa-print" aria-hidden="true"></i> Print Preview</button>
    <?php endif; ?>
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . (int) $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<section class="card list-card">
    <div class="list-card-head">
        <div>
            <h2>Report details</h2>
            <p><?= htmlspecialchars((string) ($definition['code'] ?? '')) ?></p>
        </div>
        <span class="badge badge-<?= htmlspecialchars($status) ?>"><?= htmlspecialchars(status_label($status)) ?></span>
    </div>
    <dl class="report-facts">
        <div><dt>Reporting period</dt><dd><?= htmlspecialchars($period !== '' ? QuarterlyReporting::periodLabel($period, $reportType) : '-') ?></dd></div>
        <div><dt>As of</dt><dd><?= htmlspecialchars((string) (($summaryData['report_as_of'] ?? '') !== '' ? $summaryData['report_as_of'] : '-')) ?></dd></div>
        <div><dt>College</dt><dd><?= htmlspecialchars((string) (($summaryData['college_name'] ?? '') !== '' ? $summaryData['college_name'] : ($proposal['college_name'] ?? '-'))) ?></dd></div>
        <div><dt>Prepared by</dt><dd><?= htmlspecialchars(trim((string) ($proposal['first_name'] ?? '') . ' ' . (string) ($proposal['last_name'] ?? '')) ?: '-') ?></dd></div>
        <div><dt>Current step</dt><dd><?= htmlspecialchars($stepLabel !== '' ? $stepLabel : '-') ?></dd></div>
    </dl>
</section>

<section class="card list-card">
    <div class="list-card-head">
        <div>
            <h2><?= htmlspecialchars(ucfirst($terms['entries'])) ?></h2>
            <p>From <?= htmlspecialchars(strtolower($terms['page'])) ?> for the reporting period.</p>
        </div>
    </div>
    <?php require APP_PATH . '/views/proposals/_quarterly-report-rows.php'; ?>
</section>

<?php
$statusType = $reportType;
$statusCollegeId = (int) ($proposal['college_id'] ?? 0);
$statusPeriod = $period;
ob_start();
require APP_PATH . '/views/proposals/_activity-documents-status.php';
$statusHtml = trim((string) ob_get_clean());
?>
<?php if ($statusHtml !== ''): ?>
    <div class="app-panel report-evidence"><?= $statusHtml ?></div>
<?php endif; ?>

<?php if ($narrative !== []): ?>
    <section class="card list-card">
        <div class="list-card-head"><div><h2>Narrative</h2></div></div>
        <dl class="report-narrative">
            <?php foreach ($narrative as $label => $text): ?>
                <div><dt><?= htmlspecialchars($label) ?></dt><dd><?= nl2br(htmlspecialchars($text)) ?></dd></div>
            <?php endforeach; ?>
        </dl>
    </section>
<?php endif; ?>

<?php if (!empty($documents)): ?>
    <section class="card list-card">
        <div class="list-card-head"><div><h2>Attached files</h2><p>Uploaded with this report.</p></div></div>
        <ul class="report-files">
            <?php foreach ($documents as $doc): ?>
                <li><a href="<?= base_url('projects/' . (int) $proposal['id'] . '/documents/' . (int) $doc['id']) ?>"><?= htmlspecialchars((string) ($doc['original_name'] ?? $doc['file_name'] ?? 'File')) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<div class="app-panel proposal-workflow-panel">
    <?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
            <?php
            $submitConfirmMessage = 'Submit this report for review?';
            require APP_PATH . '/views/proposals/_submit-for-review-button.php';
            ?>
            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . (int) $proposal['id'] . '/approve') ?>" class="inline-action">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars($approveLabel) ?></button>
                </form>
                <form method="post" action="<?= base_url('proposals/' . (int) $proposal['id'] . '/return') ?>" class="proposal-review-form">
                    <?= csrf_field() ?>
                    <input name="comment" placeholder="Revision comments (required)" required>
                    <button type="submit" class="btn btn-outline">Return for Revision</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="app-panel proposal-history-paper">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Comments &amp; History</h2>
        <?php if (empty($comments)): ?>
            <p>No comments yet.</p>
        <?php else: ?>
            <ul class="proposal-history-list">
                <?php foreach ($comments as $c): ?>
                    <li>
                        <div class="proposal-history-meta">
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                            | <?= htmlspecialchars($c['action']) ?>
                            <?php if ($c['step']): ?> (<?= htmlspecialchars($c['step']) ?>)<?php endif; ?>
                            | <?= htmlspecialchars($c['created_at']) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
