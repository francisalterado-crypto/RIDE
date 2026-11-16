<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE Hub';
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
    return is_string($current) && $current !== '' ? $current : '-';
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
$judgmentText = $judgmentLabels[$judgmentKey] ?? '-';
?>

<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<?php require APP_PATH . '/views/proposals/_proposal-status-strip.php'; ?>
<?php
$viewText = static fn (string $key): string => is_scalar($summaryData[$key] ?? null) ? trim((string) $summaryData[$key]) : '';
$viewField = static function (string $label, string $content, bool $full = false): void {
    ?>
    <div class="wf-view-field<?= $full ? ' webform-field--full' : '' ?>">
        <span class="wf-view-label"><?= htmlspecialchars($label) ?></span>
        <div class="wf-view-value"><?= $content !== '' ? nl2br(htmlspecialchars($content)) : '<span class="wf-view-empty">Not provided</span>' ?></div>
    </div>
    <?php
};
$scoreLabels = ['yes' => 'Yes', 'no' => 'No', 'na' => 'N/A'];
?>
<div class="webform wf-view">
    <section class="webform-card">
        <header class="webform-card-head"><span class="webform-card-num">1</span><div><h2>Report Information</h2></div></header>
        <div class="webform-grid">
            <?php $viewField('Date', $viewText('date') !== '' ? proposal_format_workflow_date($viewText('date')) : ''); ?>
            <?php $viewField('Control No.', $viewText('control_no')); ?>
            <?php $viewField('College', $viewText('college_name')); ?>
            <?php $viewField('Research Title', $viewText('research_title'), true); ?>
            <?php $viewField('Lead Researcher', $viewText('lead_researcher')); ?>
            <?php $viewField('Co-Researcher(s)', $viewText('co_researchers')); ?>
            <?php $viewField('Duration of the Study', $viewText('duration_of_study')); ?>
            <?php $viewField('Proposed Budget', $viewText('proposed_budget')); ?>
        </div>
    </section>

    <section class="webform-card">
        <header class="webform-card-head"><span class="webform-card-num">2</span><div><h2>Assessment Checklist</h2></div></header>
        <ol class="assessment-list">
            <?php foreach ($parameters as $number => $label): ?>
                <?php $score = strtolower((string) ($scoreValues[(string) $number] ?? '')); ?>
                <li class="assessment-item">
                    <span class="assessment-num"><?= (int) $number ?></span>
                    <span class="assessment-text"><?= htmlspecialchars($label) ?></span>
                    <?php if (isset($scoreLabels[$score])): ?>
                        <span class="assessment-result assessment-result--<?= $score ?>"><?= $scoreLabels[$score] ?></span>
                    <?php else: ?>
                        <span class="assessment-result wf-view-empty">Not rated</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="webform-card">
        <header class="webform-card-head"><span class="webform-card-num">3</span><div><h2>Comments and Judgment</h2></div></header>
        <div class="webform-grid">
            <?php $viewField('Comment(s)/Suggestion(s)/Recommendation(s)', $viewText('comments'), true); ?>
            <?php $viewField('My Judgment', $judgmentLabels[$judgmentKey] ?? '', true); ?>
            <?php $viewField('Reviewer (TWG Chair/Member)', $viewText('reviewer_name')); ?>
        </div>
    </section>
</div>

<div class="app-panel proposal-workflow-panel">
    <?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
<?php require APP_PATH . '/views/proposals/_submit-for-review-button.php'; ?>

            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/approve') ?>" class="inline-action">
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
