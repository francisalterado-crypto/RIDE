<?php
/** @var array<string, mixed> $cluster */
/** @var list<array> $colleges */
/** @var list<int> $memberCollegeIds */
$pageTitle = 'Edit Cluster — RIDE IMS';
$pageHeading = 'Edit Cluster';
$pageSubtitle = 'Rename this cluster or change which colleges belong to it.';
?>

<div class="accounts-page">
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/accounts') ?>">Back to Accounts</a>
</div>

<div class="card accounts-form-card">
    <h2>Cluster Details</h2>
    <p class="muted">
        Editing <strong><?= htmlspecialchars((string) $cluster['name']) ?></strong>
    </p>

    <form method="post" action="<?= base_url('admin/accounts/clusters/' . $cluster['id'] . '/update') ?>">
        <?= csrf_field() ?>

        <label for="edit_cluster_name">Cluster Name</label>
        <input
            id="edit_cluster_name"
            name="cluster_name"
            value="<?= old('edit_cluster_name', (string) ($cluster['name'] ?? '')) ?>"
            required
            maxlength="100"
            placeholder="e.g. Cluster 1"
        >

        <label>Colleges in this cluster</label>
        <p class="muted">Check every college that belongs to <?= htmlspecialchars((string) $cluster['name']) ?>. Unchecking a college removes it from the cluster (it becomes unassigned, not deleted).</p>
        <?php if ($colleges === []): ?>
            <p class="muted accounts-empty-note">No colleges added yet.</p>
        <?php else: ?>
            <div class="accounts-checkbox-list">
                <?php foreach ($colleges as $college): ?>
                    <label class="accounts-checkbox-item">
                        <input
                            type="checkbox"
                            name="college_ids[]"
                            value="<?= (int) $college['id'] ?>"
                            <?= in_array((int) $college['id'], $memberCollegeIds, true) ? 'checked' : '' ?>
                        >
                        <?= htmlspecialchars((string) $college['name']) ?> (<code><?= htmlspecialchars((string) $college['code']) ?></code>)
                        <?php if (!empty($college['cluster_name']) && (int) ($college['cluster_id'] ?? 0) !== (int) $cluster['id']): ?>
                            <span class="muted">— currently in <?= htmlspecialchars((string) $college['cluster_name']) ?></span>
                        <?php endif; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="actions">
            <button type="submit" class="btn btn-accent">Save Cluster</button>
        </div>
    </form>
</div>
</div>
