<?php
/** @var array $program */
/** @var list<array> $colleges */
$pageTitle = 'Edit Academic Program | RIDE Hub';
$pageHeading = 'Edit Academic Program';
$pageSubtitle = 'Renaming a program updates every account that has it.';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v);
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/programs?tab=academic') ?>">Back to Academic Programs</a>
</div>

<div class="card">
    <form method="post" action="<?= base_url('admin/programs/academic/' . (int) $program['id'] . '/update') ?>">
        <?= csrf_field() ?>
        <label for="ap_college">College</label>
        <select id="ap_college" name="college_id">
            <option value="">None (university-wide)</option>
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === (int) ($program['college_id'] ?? 0) ? ' selected' : '' ?>><?= $e($c['code'] . ' - ' . $c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="ap_name">Program Name <span class="required">*</span></label>
        <input id="ap_name" name="name" maxlength="150" required value="<?= $e($program['name']) ?>">

        <label class="accounts-checkbox-item">
            <input type="checkbox" name="is_active" value="1"<?= (int) $program['is_active'] === 1 ? ' checked' : '' ?>>
            Active (inactive programs are hidden from dropdowns but kept on existing records)
        </label>

        <div class="actions">
            <button type="submit" class="btn btn-accent">Save</button>
        </div>
    </form>
    <form method="post" action="<?= base_url('admin/programs/academic/' . (int) $program['id'] . '/delete') ?>" onsubmit="return confirm('Delete this academic program?');" style="margin-top:1rem;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
</div>
