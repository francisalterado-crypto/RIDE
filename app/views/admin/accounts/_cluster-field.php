<?php
/**
 * Cluster checkboxes for the RIDE coordinators (Research Coordinator: research clusters; Field Extensionist:
 * extension clusters). Saved only for those roles.
 *
 * @var list<array<string, mixed>> $clusters
 * @var list<int> $selectedClusterIds
 * @var string $clusterFieldPrefix unique id prefix
 */
$clustersByTrack = [];
foreach ($clusters as $cluster) {
    $clustersByTrack[(string) ($cluster['track'] ?? '')][] = $cluster;
}
$trackLabels = ['research' => 'Research clusters (Research Coordinator, RDO)', 'extension' => 'Extension clusters (Field Extensionist, ESO)'];
?>
<fieldset class="accounts-form-field accounts-form-field-span-2 accounts-cluster-field">
    <legend>Clusters</legend>
    <p class="muted">Required for the Research Coordinator (RDO) and the Field Extensionist (ESO): the colleges of these clusters are under them. Ignored for other roles.</p>
    <?php foreach ($trackLabels as $track => $trackLabel): ?>
        <?php if (!empty($clustersByTrack[$track])): ?>
            <div class="accounts-cluster-group">
                <span class="accounts-cluster-track"><?= htmlspecialchars($trackLabel) ?></span>
                <?php foreach ($clustersByTrack[$track] as $cluster): ?>
                    <?php $inputId = $clusterFieldPrefix . '-' . (int) $cluster['id']; ?>
                    <label class="proposal-check-item" for="<?= htmlspecialchars($inputId) ?>">
                        <input type="checkbox" id="<?= htmlspecialchars($inputId) ?>" name="cluster_ids[]" value="<?= (int) $cluster['id'] ?>"<?= in_array((int) $cluster['id'], $selectedClusterIds, true) ? ' checked' : '' ?>>
                        <span><?= htmlspecialchars((string) $cluster['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</fieldset>
