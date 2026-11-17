<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE Hub';
$pageHeading = 'Terminal Report';
$pageSubtitle = 'Project terminal report (WPU-QSF-RDE-VPRDE-21).';
// The RDO Research Coordinator completes the Terminal Report Assessment with their approval.
$reviewFields = $canApprove
    ? \App\Controllers\ProposalController::reviewFieldsFor($proposal, (string) ($proposal['current_step'] ?? ''))
    : [];
?>

<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<?php require APP_PATH . '/views/proposals/_proposal-status-strip.php'; ?>
<?php require APP_PATH . '/views/proposals/_project-report-details.php'; ?>
<?php require APP_PATH . '/views/proposals/_terminal-report-assessment.php'; ?>

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
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/approve') ?>" class="<?= $reviewFields !== [] ? 'request-review-form' : 'inline-action' ?>">
                    <?= csrf_field() ?>
                    <?php if ($reviewFields !== []): ?>
                        <h4 class="proposal-subtitle">Terminal Report Assessment (WPU-QSF-RDE-VPRDE-22)</h4>
                        <p class="webform-hint">Rate the terminal report on each parameter and give your judgment. The assessment is saved with your action: accepted without revisions or after minor revisions approves the report; major revisions or failed to pass returns it to the researcher with your comments.</p>
                        <?php require APP_PATH . '/views/requests/_review-fields.php'; ?>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars($reviewFields !== [] ? 'Save Assessment' : $approveLabel) ?></button>
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
