<?php
/** @var array $program */
/** @var list<array> $colleges */
/** @var list<array> $projects */
$pageTitle = 'Edit Extension Program | RIDE Hub';
$pageHeading = (string) (($program['acronym'] ?? '') ?: $program['name']);
$pageSubtitle = 'Extension program of ' . (string) ($program['college_name'] ?? 'no college');
$e = static fn (mixed $v): string => htmlspecialchars((string) $v);
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/programs?tab=extension') ?>">Back to Extension Programs</a>
</div>

<div class="card">
    <form method="post" action="<?= base_url('admin/programs/extension/' . (int) $program['id'] . '/update') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="is_active_present" value="1">
        <label for="ep_college">College <span class="required">*</span></label>
        <select id="ep_college" name="college_id" required>
            <?php foreach ($colleges as $c): ?>
                <option value="<?= (int) $c['id'] ?>"<?= (int) $c['id'] === (int) ($program['college_id'] ?? 0) ? ' selected' : '' ?>><?= $e($c['code'] . ' - ' . $c['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="ep_acronym">Acronym</label>
        <input id="ep_acronym" name="acronym" maxlength="40" value="<?= $e($program['acronym'] ?? '') ?>">

        <label for="ep_name">Program Name <span class="required">*</span></label>
        <input id="ep_name" name="name" maxlength="255" required value="<?= $e($program['name']) ?>">

        <label for="ep_description">Description</label>
        <textarea id="ep_description" name="description" rows="3"><?= $e($program['description'] ?? '') ?></textarea>

        <label for="ep_period">Agenda Period</label>
        <input id="ep_period" name="agenda_period" maxlength="20" value="<?= $e($program['agenda_period'] ?? '') ?>">

        <label class="accounts-checkbox-item">
            <input type="checkbox" name="is_active" value="1"<?= (int) $program['is_active'] === 1 ? ' checked' : '' ?>>
            Active (inactive programs are hidden from proposal dropdowns)
        </label>

        <div class="actions">
            <button type="submit" class="btn btn-accent">Save</button>
        </div>
    </form>
    <form method="post" action="<?= base_url('admin/programs/extension/' . (int) $program['id'] . '/delete') ?>" onsubmit="return confirm('Delete this extension program?');" style="margin-top:1rem;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Delete</button>
    </form>
</div>

<div class="card">
    <h2>Projects under this program (<?= count($projects) ?>)</h2>
    <table class="data-table">
        <thead><tr><th>Project</th><th>Campus</th><th>Leader</th><th>Year</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($projects as $p): ?>
            <tr>
                <td><?= $e($p['title']) ?></td>
                <td><?= $e($p['campus'] ?? '-') ?></td>
                <td><?= $e($p['leader_name'] ?? '-') ?></td>
                <td><?= $e($p['year'] ?? '-') ?></td>
                <td><a class="btn btn-sm btn-outline" href="<?= base_url('admin/programs/projects/' . (int) $p['id']) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($projects === []): ?>
            <tr><td colspan="5" class="muted">No projects yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
