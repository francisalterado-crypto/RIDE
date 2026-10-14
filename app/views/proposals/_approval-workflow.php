<?php
/** @var list<array> $workflowSteps */

$pendingStep = null;
foreach ($workflowSteps as $step) {
    if (($step['status'] ?? '') === 'pending' || ($step['status'] ?? '') === 'returned') {
        $pendingStep = $step;
        break;
    }
}
?>
<section class="proposal-section proposal-workflow-section">
    <h3 class="proposal-subtitle">Approval Workflow</h3>

    <?php if ($pendingStep !== null): ?>
        <p class="proposal-workflow-summary">
            Currently pending:
            <strong><?= htmlspecialchars($pendingStep['title']) ?></strong>
            (<?= htmlspecialchars($pendingStep['status_label']) ?>)
        </p>
    <?php endif; ?>

    <ol class="proposal-workflow-steps">
        <?php foreach ($workflowSteps as $step): ?>
            <?php
            $status = (string) ($step['status'] ?? 'upcoming');
            $actorName = trim((string) ($step['actor_name'] ?? ''));
            $actedAt = (string) ($step['acted_at'] ?? '');
            ?>
            <li class="proposal-workflow-step proposal-workflow-step--<?= htmlspecialchars($status) ?>">
                <div class="proposal-workflow-step-head">
                    <strong><?= htmlspecialchars($step['title']) ?></strong>
                    <span class="proposal-workflow-badge proposal-workflow-badge--<?= htmlspecialchars($status) ?>">
                        <?= htmlspecialchars($step['status_label']) ?>
                    </span>
                </div>
                <p class="proposal-workflow-description"><?= htmlspecialchars($step['description']) ?></p>
                <?php if ($actorName !== '' || $actedAt !== ''): ?>
                    <div class="proposal-workflow-meta">
                        <?php if ($actorName !== ''): ?>
                            <span><?= htmlspecialchars($actorName) ?></span>
                        <?php endif; ?>
                        <?php if ($actedAt !== ''): ?>
                            <span><?= htmlspecialchars($actedAt) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
