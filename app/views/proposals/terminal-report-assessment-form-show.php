<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = 'Terminal Report Assessment Form';
$pageSubtitle = 'Assessment checklist for terminal report evaluation.';

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

$parameters = [
    1 => 'The abstract presents an accurate synopsis of the research.',
    2 => 'The approved methodology in the proposal was followed (i.e. sample size, sampling procedure, data collection, data analysis, etc).',
    3 => 'The approved research ethics protocol was followed.',
    4 => 'Analysis of limitations and errors of the methods were presented.',
    5 => 'Stakeholders validated the data.',
    6 => 'Scientific literature supported the findings.',
    7 => 'The conclusion(s) captures the result of hypotheses test.',
    8 => 'The conclusions validated the theory used.',
    9 => 'The study generated new knowledge in the discipline.',
    10 => 'There are leads to future studies emanating from the current results.',
    11 => 'The paper complied with the standard of technical writing.',
    12 => "There is evidence that scientific literature and stakeholder's view support the recommendations.",
    13 => 'The over-all presentation of report captures the substance of the study.',
];
$scoreValues = is_array($summaryData['scores'] ?? null) ? $summaryData['scores'] : [];
$judgmentLabels = [
    'accepted_without_revisions' => 'Accepted without revisions',
    'accepted_after_minor_revisions' => 'Accepted after minor revisions suggested in this review',
    'accepted_after_major_revisions' => 'Accepted after major revisions suggested in this review',
    'failed_to_pass' => 'Failed to pass',
];
$judgmentKey = (string) ($summaryData['judgment'] ?? '');
$judgmentText = $judgmentLabels[$judgmentKey] ?? '—';
?>

<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper terminal-report-assessment-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">TERMINAL REPORT ASSESSMENT FORM</h2>
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
            <tr><th>Date</th><td><?= htmlspecialchars($value('date')) ?></td><th>Control No.</th><td><?= htmlspecialchars($value('control_no')) ?></td></tr>
            <tr><th>College</th><td colspan="3"><?= htmlspecialchars($value('college_name')) ?></td></tr>
            <tr><th>Lead Researcher</th><td colspan="3"><?= htmlspecialchars($value('lead_researcher')) ?></td></tr>
            <tr><th>Co-Researcher(s)</th><td colspan="3"><?= htmlspecialchars($value('co_researchers')) ?></td></tr>
            <tr><th>Research Title</th><td colspan="3"><?= htmlspecialchars($value('research_title')) ?></td></tr>
            <tr><th>Duration of the Study</th><td><?= htmlspecialchars($value('duration_of_study')) ?></td><th>Proposed Budget</th><td><?= htmlspecialchars($value('proposed_budget')) ?></td></tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section">
        <h3 class="proposal-subtitle">Assessment Checklist</h3>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table terminal-assessment-table">
                <colgroup>
                    <col class="ta-col-no">
                    <col class="ta-col-parameter">
                    <col class="ta-col-score">
                    <col class="ta-col-score">
                    <col class="ta-col-score">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Parameters</th>
                        <th>Yes</th>
                        <th>No</th>
                        <th>N/A</th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach ($parameters as $number => $label): ?>
                <?php $score = strtolower((string) ($scoreValues[(string) $number] ?? '')); ?>
                <tr>
                    <td><?= (int) $number ?></td>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td class="terminal-assessment-score"><?= $score === 'yes' ? '✓' : '' ?></td>
                    <td class="terminal-assessment-score"><?= $score === 'no' ? '✓' : '' ?></td>
                    <td class="terminal-assessment-score"><?= $score === 'na' ? '✓' : '' ?></td>
                </tr>
            <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Comment(s)/Suggestion(s)/Recommendation(s):</h3>
        <p><?= nl2br(htmlspecialchars((string) ($summaryData['comments'] ?? '—'))) ?></p>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">My Judgment</h3>
        <p><?= htmlspecialchars($judgmentText) ?></p>
    </section>

    <section class="proposal-section completed-researches-signoff">
        <p><strong>Signature over printed name:</strong> <?= htmlspecialchars($value('reviewer_name')) ?></p>
        <p>TWG Chair/Member</p>
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
