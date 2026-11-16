<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$definition = \App\Support\ResearchRequestForms::get((string) \App\Support\ResearchRequestForms::typeOf($proposal));
$pageTitle = (string) $proposal['title'] . ' | RIDE Hub';
$pageHeading = (string) $proposal['title'];
$pageSubtitle = $definition['label'] . ' (' . $definition['code'] . ').';
$formType = (string) \App\Support\ResearchRequestForms::typeOf($proposal);
$requestSummary = json_decode((string) ($proposal['summary'] ?? ''), true);
$requestSummary = is_array($requestSummary) ? $requestSummary : [];
$reviewFields = $canApprove ? \App\Support\ResearchRequestForms::reviewFields($formType, (string) ($proposal['current_step'] ?? '')) : [];
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('research-requests/' . (int) $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<?php
$editUrl = base_url('research-requests/' . (int) $proposal['id'] . '/edit');
require APP_PATH . '/views/proposals/_form-docx-actions.php';
?>
<?php if (is_array($requestSummary['contributions'] ?? null) && $requestSummary['contributions'] !== []): ?>
    <div class="card request-contribution-card">
        <div>
            <h2>Percentage Contribution for Incentives Claim</h2>
            <p class="muted">The office workbook with the authors and their percentages, for each author to sign the conforme column.</p>
        </div>
        <a class="btn btn-outline" href="<?= base_url('research-requests/' . (int) $proposal['id'] . '/contribution') ?>">Download (.xlsx)</a>
    </div>
<?php endif; ?>

<?php if (!empty($definition['reviews']) && is_array($requestSummary['reviews'] ?? null) && $requestSummary['reviews'] !== []): ?>
<div class="webform wf-view">
    <section class="webform-card">
        <header class="webform-card-head"><span class="webform-card-num"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></span><div><h2>Evaluation</h2><p>Entered by the reviewers with their approval.</p></div></header>
        <?php foreach ($definition['reviews'] as $reviewStep => $stepFields): ?>
            <?php $saved = $requestSummary['reviews'][$reviewStep] ?? null; ?>
            <?php if (!is_array($saved)) { continue; } ?>
            <h3 class="wf-view-subhead"><?= htmlspecialchars(\App\Support\AccessControl::stepLabel($reviewStep)) ?></h3>
            <div class="webform-grid">
                <?php foreach ($stepFields as $name => $field): ?>
                    <?php
                    $raw = $saved[$name] ?? '';
                    $shown = match ($field['type']) {
                        'radio' => (string) ($field['options'][(string) $raw] ?? ''),
                        'checks' => implode(', ', \App\Support\ResearchFormOptions::labels(is_array($raw) ? $raw : [], $field['options'])),
                        'amount' => $raw !== '' ? 'PHP ' . number_format((float) $raw, 2) : '',
                        default => (string) $raw,
                    };
                    ?>
                    <div class="wf-view-field<?= $field['type'] === 'textarea' ? ' webform-field--full' : '' ?>">
                        <span class="wf-view-label"><?= htmlspecialchars((string) $field['label']) ?></span>
                        <div class="wf-view-value"><?= $shown !== '' ? nl2br(htmlspecialchars($shown)) : '<span class="wf-view-empty">Not provided</span>' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>
</div>
<?php endif; ?>

<div class="app-panel proposal-history-paper">
    <?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
            <?php
            $submitConfirmMessage = 'Submit this request for review?';
            require APP_PATH . '/views/proposals/_submit-for-review-button.php';
            ?>
            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . (int) $proposal['id'] . '/approve') ?>" class="<?= $reviewFields !== [] ? 'request-review-form' : 'inline-action' ?>">
                    <?= csrf_field() ?>
                    <?php if ($reviewFields !== []): ?>
                        <p class="webform-hint">Complete your evaluation. It is saved with your approval and printed on the form.</p>
                        <?php require APP_PATH . '/views/requests/_review-fields.php'; ?>
                    <?php endif; ?>
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
