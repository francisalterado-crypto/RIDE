<?php
/** @var array<string, mixed> $college */
/** @var list<array> $clusters */
$pageTitle = 'Edit College | RIDE Hub';
$pageHeading = 'Edit College';
$pageSubtitle = 'Update the college code or name used for account assignment and proposal prefill.';
?>

<div class="accounts-page">
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/accounts') ?>">Back to Accounts</a>
</div>

<div class="card accounts-form-card">
    <h2>College Details</h2>
    <p class="muted">
        Editing <strong><?= htmlspecialchars((string) $college['name']) ?></strong>
        (<code><?= htmlspecialchars((string) $college['code']) ?></code>)
    </p>

    <form method="post" action="<?= base_url('admin/accounts/colleges/' . $college['id'] . '/update') ?>">
        <?= csrf_field() ?>

        <label for="edit_college_code">College Code</label>
        <input
            id="edit_college_code"
            name="college_code"
            value="<?= old('edit_college_code', (string) ($college['code'] ?? '')) ?>"
            required
            maxlength="20"
            placeholder="e.g. CIT"
        >

        <label for="edit_college_name">College Name</label>
        <input
            id="edit_college_name"
            name="college_name"
            value="<?= old('edit_college_name', (string) ($college['name'] ?? '')) ?>"
            required
            maxlength="150"
            placeholder="e.g. College of Information Technology"
        >

        <?php $unitType = (string) ($college['unit_type'] ?? 'college'); ?>
        <label for="edit_college_unit_type">Type</label>
        <select id="edit_college_unit_type" name="unit_type">
            <?php foreach (\App\Models\College::UNIT_TYPES as $typeKey => $typeLabel): ?>
                <option value="<?= $typeKey ?>"<?= $unitType === $typeKey ? ' selected' : '' ?>><?= $typeLabel ?></option>
            <?php endforeach; ?>
        </select>

        <label for="edit_college_office">Office (centers only)</label>
        <select id="edit_college_office" name="office">
            <option value="">None</option>
            <?php foreach (\App\Models\College::OFFICES as $officeKey => $officeLabel): ?>
                <option value="<?= $officeKey ?>"<?= (string) ($college['office'] ?? '') === $officeKey ? ' selected' : '' ?>><?= $officeLabel ?></option>
            <?php endforeach; ?>
        </select>

        <?php foreach (['extension' => 'ESO Group', 'research' => 'Research Cluster'] as $track => $trackLabel): ?>
            <?php $column = \App\Models\Cluster::memberColumn($track); ?>
            <label for="edit_college_<?= $column ?>"><?= $trackLabel ?></label>
            <select id="edit_college_<?= $column ?>" name="<?= $column ?>">
                <option value="">No <?= strtolower($trackLabel) ?></option>
                <?php foreach ($clusters as $cluster): ?>
                    <?php if (($cluster['track'] ?? 'extension') !== $track) { continue; } ?>
                    <option value="<?= (int) $cluster['id'] ?>"<?= (int) ($college[$column] ?? 0) === (int) $cluster['id'] ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $cluster['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>

        <div class="actions">
            <button type="submit" class="btn btn-accent">Save College</button>
        </div>
    </form>
</div>
</div>
