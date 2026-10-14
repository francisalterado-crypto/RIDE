<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($proposal['title']);
$pageSubtitle = 'Review the submitted attendance form (WPU-QSF-RIDE-ESO-17).';

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
$attendees = is_array($summaryData['attendees'] ?? null) ? $summaryData['attendees'] : [];
$timeAm = $summaryField('time_am');
$timePm = $summaryField('time_pm');
$timeDisplay = [];
if ($timeAm !== '') {
    $timeDisplay[] = 'AM ' . $timeAm;
}
if ($timePm !== '') {
    $timeDisplay[] = 'PM ' . $timePm;
}
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper attendance-sheet-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">ATTENDANCE FORM</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-17 Rev.00 (08.15.25)</p>
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
                <th>Title of Activity</th>
                <td colspan="3"><?= $display($summaryField('activity_title')) ?></td>
            </tr>
            <tr>
                <th>Venue</th>
                <td><?= $display($summaryField('venue')) ?></td>
                <th>Date</th>
                <td><?= htmlspecialchars($summaryField('activity_date') !== '' ? $summaryField('activity_date') : '—') ?></td>
            </tr>
            <tr>
                <th>Time</th>
                <td colspan="3"><?= htmlspecialchars($timeDisplay !== [] ? implode(' / ', $timeDisplay) : '—') ?></td>
            </tr>
        </table>
    </section>

    <aside class="attendance-privacy-notice" aria-label="Privacy notice">
        <h3>Privacy Notice</h3>
        <p>
            For this activity, we collect your names, sex, office/agency affiliation, position/designation, and email address or mobile number when you register for purposes of coordination, printing of certificates, and in compliance to GAD requirements. Through this attendance sheet, we also collect your signature as proof of attendance. To the extent permitted or required by law, we may also share photos and videos of this activity/meeting/event to promote WPU through brochures, website posts, and social media.
        </p>
        <p>
            All personal information collected will be stored in a secure location and only authorized staff will have access to them.
        </p>
    </aside>

    <?php $readOnly = true; require APP_PATH . '/views/proposals/_attendance-sheet-table.php'; ?>

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
