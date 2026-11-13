<?php
/** @var array $proposal */
/** @var bool $canEdit */
/** @var string $submitConfirmMessage */
$submitConfirmMessage = $submitConfirmMessage ?? 'Submit this report for review?';
$canSubmitForReview = ($canEdit ?? false) && proposal_can_submit_for_review($proposal);
$submitBlockedMessage = proposal_submit_blocked_coauthor_message($proposal);
?>
<?php if ($canEdit ?? false): ?>
    <?php if ($canSubmitForReview): ?>
        <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/submit') ?>" class="inline-action">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-accent" onclick="return confirm('<?= htmlspecialchars($submitConfirmMessage, ENT_QUOTES) ?>')">Submit for Review</button>
        </form>
    <?php else: ?>
        <span class="proposal-submit-blocked-wrap inline-action">
            <button type="button" class="btn btn-accent" disabled aria-disabled="true" title="Waiting for co-author acceptance">Submit for Review</button>
        </span>
        <?php if ($submitBlockedMessage !== null): ?>
            <p class="proposal-section-note proposal-submit-blocked-note"><?= htmlspecialchars($submitBlockedMessage) ?></p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
