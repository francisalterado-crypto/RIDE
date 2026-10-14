<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = 'Progress Report';
$pageSubtitle = 'Quarterly project progress report.';

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$value = static function (string $key) use ($summaryData): string {
    $current = $summaryData[$key] ?? null;
    return is_string($current) && $current !== '' ? $current : '—';
};

$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
$coauthors = is_array($summaryData['coauthors'] ?? null) ? $summaryData['coauthors'] : [];
?>

<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper progress-report-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">PROGRESS REPORT</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-17 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
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
                <th>Research Title</th>
                <td colspan="3"><?= htmlspecialchars($value('research_title')) ?></td>
            </tr>
            <tr>
                <th>Period Covered</th>
                <td><?= htmlspecialchars($value('period_covered')) ?></td>
                <th>Duration in Months</th>
                <td><?= htmlspecialchars($value('duration_months')) ?></td>
            </tr>
            <tr>
                <th>Funding/ Support</th>
                <td colspan="3"><?= htmlspecialchars($value('funding_support')) ?></td>
            </tr>
            <tr>
                <th>As of</th>
                <td colspan="3"><?= htmlspecialchars($value('report_as_of')) ?></td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">I. THE PROPONENT</h3>
        <table class="proposal-table">
            <tr><th>Full Name</th><td colspan="3"><?= htmlspecialchars(trim((string) ($summaryData['last_name'] ?? '') . ', ' . (string) ($summaryData['first_name'] ?? '') . ' ' . (string) ($summaryData['middle_name'] ?? ''))) ?></td></tr>
            <tr><th>Title/Prefix</th><td colspan="3"><?= htmlspecialchars($value('title_prefix')) ?></td></tr>
            <tr><th>College/Department</th><td colspan="3"><?= htmlspecialchars($value('college_name')) ?></td></tr>
            <tr><th>Campus</th><td colspan="3"><?= htmlspecialchars($value('campus')) ?></td></tr>
            <tr><th>Email</th><td colspan="3"><?= htmlspecialchars($value('email')) ?></td></tr>
            <tr><th>Contact Number</th><td colspan="3"><?= htmlspecialchars($value('contact_number')) ?></td></tr>
            <tr><th>Google Scholar account link</th><td colspan="3"><?= htmlspecialchars($value('google_scholar_link')) ?></td></tr>
            <tr><th>ResearchGate account link</th><td colspan="3"><?= htmlspecialchars($value('researchgate_link')) ?></td></tr>
        </table>
        <?php if ($coauthors !== []): ?>
            <div class="trainings-conducted-section">
                <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
                <div class="proposal-table-wrap trainings-conducted-table-wrap">
                    <table class="proposal-table trainings-conducted-table proposal-coauthors-table">
                        <thead>
                            <tr><th>Co-Author(s)</th><th>Last Name</th><th>First Name</th><th>Middle Name</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coauthors as $author): ?>
                                <tr>
                                    <td></td>
                                    <td><?= htmlspecialchars((string) ($author['last_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($author['first_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string) ($author['middle_name'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Project/Study Overview</h3>
        <p><?= nl2br(htmlspecialchars((string) ($summaryData['project_overview'] ?? '—'))) ?></p>
    </section>

    <section class="proposal-section trainings-conducted-section">
        <h3 class="proposal-subtitle">Work/s Completed and/or in Progress</h3>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table">
                <thead>
                    <tr>
                        <th>Activities</th>
                        <th>Target Schedule (based on proposal)</th>
                        <th>Period of Actual Implementation/Date of Completion</th>
                        <th>Problems Encountered/Action Required</th>
                    </tr>
                </thead>
                <tbody>
            <?php if ($entries === []): ?>
                <tr><td colspan="4">No entries yet.</td></tr>
            <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= nl2br(htmlspecialchars((string) ($entry['activity'] ?? ''))) ?></td>
                        <td><?= nl2br(htmlspecialchars((string) ($entry['target_schedule'] ?? ''))) ?></td>
                        <td><?= nl2br(htmlspecialchars((string) ($entry['actual_period'] ?? ''))) ?></td>
                        <td><?= nl2br(htmlspecialchars((string) ($entry['problems'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Revised targets if the project is delayed/advanced</h3>
        <p><?= nl2br(htmlspecialchars((string) ($summaryData['revised_targets'] ?? '—'))) ?></p>
        <h3 class="proposal-subtitle">How the project/study is going in general</h3>
        <p><?= nl2br(htmlspecialchars((string) ($summaryData['general_progress'] ?? '—'))) ?></p>
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
