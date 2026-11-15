<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */
$pageTitle = (string) $proposal['title'] . ' | RIDE Hub';
$pageHeading = 'Research Proposal Application';
$pageSubtitle = 'Review the submitted Research Proposal Application (WPU-QSF-RIDE-RDO-20).';
?>
<div class="page-actions-bar">
    <?php if (in_array($proposal['status'], ['ongoing','approved','completed'], true)): ?>
        <a class="btn btn-accent" href="<?= base_url('projects/' . $proposal['id']) ?>">Monitor Project</a>
    <?php endif; ?>
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<?php require APP_PATH . '/views/proposals/_proposal-status-strip.php'; ?>
<?php if (\App\Services\ResearchFormDocument::formKey($proposal) !== null): ?>
    <?php
    $editUrl = base_url('proposals/' . $proposal['id'] . '/edit');
    require APP_PATH . '/views/proposals/_form-docx-actions.php';
    if (\App\Services\ResearchFormDocument::formKey($proposal) === 'research') {
        require APP_PATH . '/views/proposals/_research-application-details.php';
    }
    ?>
<?php else: ?>
    <div class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper rdo-research-paper">
        <?php require APP_PATH . '/views/proposals/_research-application-paper.php'; ?>
    </div>
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
            $submitConfirmMessage = 'Submit this proposal for review?';
            require APP_PATH . '/views/proposals/_submit-for-review-button.php';
            ?>

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
