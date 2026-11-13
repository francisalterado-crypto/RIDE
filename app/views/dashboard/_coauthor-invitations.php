<?php
/** @var list<array<string, mixed>> $pendingCoAuthorInvitations */
$pendingCoAuthorInvitations = $pendingCoAuthorInvitations ?? [];
if ($pendingCoAuthorInvitations === []) {
    return;
}
?>

<section class="coauthor-invitations-panel" style="margin-bottom: 20px;">
    <div class="chart-container" style="padding: 1rem 1.1rem; border-left: 4px solid #b85c00;">
        <h3 style="margin: 0 0 8px; font-size: 1rem; color: #1E3A6F;">
            <i class="fas fa-user-plus"></i> Co-author invitations
        </h3>
        <p class="muted" style="margin: 0 0 14px; font-size: 0.88rem;">
            A researcher listed you as co-author. Accept or decline each request below.
        </p>
        <ul class="coauthor-invitations-list" style="list-style: none; margin: 0; padding: 0;">
            <?php foreach ($pendingCoAuthorInvitations as $inv): ?>
                <?php
                $invId = (int) ($inv['id'] ?? 0);
                $leadName = trim((string) ($inv['lead_first_name'] ?? '') . ' ' . (string) ($inv['lead_last_name'] ?? ''));
                $proposalTitle = (string) ($inv['proposal_title'] ?? 'Untitled proposal');
                ?>
                <li class="coauthor-invitation-item" style="padding: 12px 0; border-top: 1px solid #eef2f8;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;">
                        <div>
                            <strong><?= htmlspecialchars($leadName !== '' ? $leadName : 'Lead author') ?></strong>
                            <span class="muted"> | </span>
                            <?= htmlspecialchars($proposalTitle) ?>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <form method="post" action="<?= base_url('coauthor-invitations/' . $invId . '/accept') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                            </form>
                            <form method="post" action="<?= base_url('coauthor-invitations/' . $invId . '/reject') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm">Decline</button>
                            </form>
                            <a href="<?= base_url('coauthor-invitations/' . $invId) ?>" class="btn btn-outline btn-sm">Details</a>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
