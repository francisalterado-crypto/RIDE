<?php
/** @var array<string, mixed> $cluster */
/** @var list<array> $colleges */
/** @var list<int> $memberCollegeIds */
$pageTitle = 'Edit Cluster | RIDE Hub';
$pageHeading = 'Edit Cluster';
$pageSubtitle = 'Rename this cluster, set its track, or change which colleges and centers belong to it.';
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
            placeholder="Extension Cluster 1"
        >

        <?php $clusterTrack = (string) ($cluster['track'] ?? 'extension'); ?>
        <label for="edit_cluster_track">Track</label>
        <select id="edit_cluster_track" name="cluster_track">
            <?php foreach (\App\Models\Cluster::TRACKS as $trackKey => $trackLabel): ?>
                <option value="<?= $trackKey ?>"<?= $clusterTrack === $trackKey ? ' selected' : '' ?>><?= $trackLabel ?></option>
            <?php endforeach; ?>
        </select>
        <p class="muted">Changing the track clears this cluster's members, since research and extension group units differently.</p>

        <label for="edit_cluster_leader">Head / Coordinator</label>
        <input id="edit_cluster_leader" name="leader_name" maxlength="150" value="<?= htmlspecialchars((string) ($cluster['leader_name'] ?? '')) ?>" placeholder="Arlene L. Avillanosa (University Researcher V)">

        <label for="edit_cluster_notes">Notes (staff, extensionists)</label>
        <textarea id="edit_cluster_notes" name="cluster_notes" rows="3"><?= htmlspecialchars((string) ($cluster['notes'] ?? '')) ?></textarea>

        <label>Colleges and centers in this cluster</label>
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
                        <?php $currentName = (string) ($college[$clusterTrack . '_cluster_name'] ?? ''); ?>
                        <?php if ($currentName !== '' && (int) ($college[\App\Models\Cluster::memberColumn($clusterTrack)] ?? 0) !== (int) $cluster['id']): ?>
                            <span class="muted">(currently in <?= htmlspecialchars($currentName) ?>)</span>
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
