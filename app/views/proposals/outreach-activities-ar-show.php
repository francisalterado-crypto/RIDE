<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */
/** @var array<string, string> $requiredFileList */
/** @var array<string, list<array<string, mixed>>> $requiredDocuments */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($proposal['title']);
$pageSubtitle = 'Review the submitted Outreach Activities accomplishment report (WPU-QSF-RIDE-ESO-15).';

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$display = static function (string $value): string {
    $trimmed = trim($value);

    return $trimmed === '' ? '—' : nl2br(htmlspecialchars($trimmed));
};

$summaryField = static function (string $key) use ($summaryData): string {
    return isset($summaryData[$key]) && is_string($summaryData[$key]) ? $summaryData[$key] : '';
};

$collegeDisplay = $summaryField('college_name') !== ''
    ? $summaryField('college_name')
    : (string) ($proposal['college_name'] ?? '');
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">OUTREACH ACTIVITIES REPORT</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-15 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge badge-<?= htmlspecialchars($proposal['status']) ?>"><?= htmlspecialchars($proposal['status']) ?></span>
                    <?php if ($proposal['current_step']): ?>
                        <div class="proposal-table-meta">Step: <?= htmlspecialchars($stepLabel) ?></div>
                    <?php endif; ?>
                </td>
                <th>Project Code</th>
                <td><?= htmlspecialchars($proposal['project_code'] ?: 'Draft') ?></td>
            </tr>
            <tr>
                <th>College</th>
                <td colspan="3"><?= htmlspecialchars($collegeDisplay !== '' ? $collegeDisplay : '—') ?></td>
            </tr>
            <tr>
                <th>Title of Outreach Activity</th>
                <td colspan="3"><?= $display($summaryField('outreach_activity_title')) ?></td>
            </tr>
            <tr>
                <th>No. of Beneficiaries</th>
                <td><?= $display($summaryField('beneficiaries_count')) ?></td>
                <th>Date</th>
                <td><?= htmlspecialchars($summaryField('activity_date') !== '' ? $summaryField('activity_date') : '—') ?></td>
            </tr>
            <tr>
                <th>Venue</th>
                <td colspan="3"><?= $display($summaryField('venue')) ?></td>
            </tr>
            <tr>
                <th>Narrative Report</th>
                <td colspan="3"><?= $display($summaryField('narrative_report')) ?></td>
            </tr>
            <tr>
                <th>Documentation</th>
                <td colspan="3">
                    <?php
                    $allowUpload = false;
                    $fileKey = 'oa_ar_documentation';
                    $emptyLabel = 'No documentation uploaded yet.';
                    require APP_PATH . '/views/proposals/_outreach-activities-ar-file.php';
                    ?>
                </td>
            </tr>
            <tr>
                <th>Name &amp; Signature of Resource Person</th>
                <td colspan="3"><?= $display($summaryField('resource_person_name')) ?></td>
            </tr>
            <tr>
                <th>Attached MOA (for active linkages)</th>
                <td colspan="3">
                    <?php
                    $allowUpload = false;
                    $fileKey = 'oa_ar_moa';
                    $emptyLabel = 'None uploaded.';
                    require APP_PATH . '/views/proposals/_outreach-activities-ar-file.php';
                    ?>
                </td>
            </tr>
            <tr>
                <th>Attached attendance sheet</th>
                <td colspan="3">
                    <?php
                    $allowUpload = false;
                    $fileKey = 'oa_ar_attendance';
                    $emptyLabel = 'No attendance sheet uploaded yet.';
                    require APP_PATH . '/views/proposals/_outreach-activities-ar-file.php';
                    ?>
                </td>
            </tr>
        </table>
    </section>

<?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
<?php require APP_PATH . '/views/proposals/_submit-for-review-button.php'; ?>

            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/approve') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars($approveLabel) ?></button>
                </form>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/return') ?>" class="proposal-review-form">
                    <?= csrf_field() ?>
                    <input name="comment" placeholder="Revision comments (required)" required>
                    <button type="submit" class="btn btn-outline">Return for Revision</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="proposal-paper proposal-history-paper">
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
                            • <?= htmlspecialchars($c['action']) ?>
                            <?php if ($c['step']): ?> (<?= htmlspecialchars($c['step']) ?>)<?php endif; ?>
                            • <?= htmlspecialchars($c['created_at']) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
