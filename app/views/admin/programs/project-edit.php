<?php
/** @var array $project */
/** @var list<array> $extensionPrograms */
/** @var list<array> $academicPrograms */
/** @var list<array> $planned */
/** @var list<array> $conducted */

use App\Models\ExtensionProgram;
use App\Models\ExtensionProject;

$pageTitle = 'Extension Project | RIDE Hub';
$pageHeading = (string) $project['title'];
$pageSubtitle = (string) ($project['college_code'] ?? '') . ' | ' . (string) (($project['program_acronym'] ?? '') ?: $project['program_name'])
    . ($project['source'] ? ' | Source: ' . $project['source'] : '');
$e = static fn (mixed $v): string => htmlspecialchars((string) $v);
$plannedBudget = array_sum(array_map(static fn (array $a): float => (float) ($a['budget'] ?? 0), $planned));
$plannedParticipants = array_sum(array_map(static fn (array $a): int => (int) ($a['participants'] ?? 0), $planned));
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/programs?tab=projects') ?>">Back to Projects</a>
</div>

<div class="card">
    <h2>Project Details</h2>
    <form method="post" action="<?= base_url('admin/programs/projects/' . (int) $project['id'] . '/update') ?>" class="accounts-form-grid">
        <?= csrf_field() ?>
        <div class="accounts-form-field accounts-form-field-span-2">
            <label for="pj_program">Extension Program <span class="required">*</span></label>
            <select id="pj_program" name="extension_program_id" required>
                <?php foreach ($extensionPrograms as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === (int) $project['extension_program_id'] ? ' selected' : '' ?>><?= $e($p['college_code'] . ' | ' . ExtensionProgram::label($p)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="accounts-form-field accounts-form-field-span-2">
            <label for="pj_title">Project Title <span class="required">*</span></label>
            <input id="pj_title" name="title" maxlength="500" required value="<?= $e($project['title']) ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_academic">Academic Program</label>
            <select id="pj_academic" name="academic_program_id">
                <option value="">None / all programs</option>
                <?php foreach ($academicPrograms as $a): ?>
                    <option value="<?= (int) $a['id'] ?>"<?= (int) $a['id'] === (int) ($project['academic_program_id'] ?? 0) ? ' selected' : '' ?>><?= $e(($a['college_code'] ? $a['college_code'] . ' | ' : '') . $a['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="accounts-form-field">
            <label for="pj_campus">Campus</label>
            <input id="pj_campus" name="campus" maxlength="80" value="<?= $e($project['campus'] ?? '') ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_leader">Project Leader</label>
            <input id="pj_leader" name="leader_name" maxlength="255" value="<?= $e($project['leader_name'] ?? '') ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_year">Year</label>
            <input id="pj_year" name="year" type="number" min="2000" max="2100" value="<?= $e($project['year'] ?? '') ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_budget">Total Budget</label>
            <input id="pj_budget" name="total_budget" inputmode="decimal" value="<?= $project['total_budget'] !== null ? $e(number_format((float) $project['total_budget'], 2, '.', '')) : '' ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_participants">Target Participants</label>
            <input id="pj_participants" name="target_participants" type="number" min="0" value="<?= $e($project['target_participants'] ?? '') ?>">
        </div>
        <div class="accounts-form-field">
            <label for="pj_status">Status</label>
            <select id="pj_status" name="status">
                <?php foreach (ExtensionProject::STATUSES as $key => $label): ?>
                    <option value="<?= $key ?>"<?= (string) $project['status'] === $key ? ' selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="accounts-form-actions">
            <button type="submit" class="btn btn-accent">Save Project</button>
        </div>
    </form>
    <form method="post" action="<?= base_url('admin/programs/projects/' . (int) $project['id'] . '/delete') ?>" onsubmit="return confirm('Delete this project and its planned activities?');" style="margin-top:1rem;">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-danger">Delete Project</button>
    </form>
</div>

<div class="card">
    <h2>Planned Activities (<?= count($planned) ?>)</h2>
    <p class="muted">Total planned: <?= number_format($plannedParticipants) ?> participants | PHP <?= number_format($plannedBudget, 2) ?></p>
    <table class="data-table">
        <thead><tr><th>#</th><th>Activity</th><th>Coordinators</th><th>Participants</th><th>Budget</th><th>Schedule</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($planned as $i => $a): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= $e($a['title']) ?></td>
                <td><?= $e($a['coordinators'] ?? '-') ?></td>
                <td><?= $e($a['participants'] ?? '-') ?></td>
                <td><?= $a['budget'] !== null ? number_format((float) $a['budget'], 2) : '-' ?></td>
                <td><?= $e($a['schedule'] ?? '-') ?></td>
                <td>
                    <form method="post" action="<?= base_url('admin/programs/projects/' . (int) $project['id'] . '/activities/' . (int) $a['id'] . '/delete') ?>" onsubmit="return confirm('Remove this planned activity?');">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($planned === []): ?>
            <tr><td colspan="7" class="muted">No planned activities yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <h3>Add Planned Activity</h3>
    <form method="post" action="<?= base_url('admin/programs/projects/' . (int) $project['id'] . '/activities') ?>" class="accounts-form-grid">
        <?= csrf_field() ?>
        <div class="accounts-form-field accounts-form-field-span-2">
            <label for="pa_title">Activity <span class="required">*</span></label>
            <input id="pa_title" name="activity_title" maxlength="500" required>
        </div>
        <div class="accounts-form-field accounts-form-field-span-2">
            <label for="pa_coordinators">Coordinators</label>
            <input id="pa_coordinators" name="activity_coordinators">
        </div>
        <div class="accounts-form-field">
            <label for="pa_participants">Participants</label>
            <input id="pa_participants" name="activity_participants" type="number" min="0">
        </div>
        <div class="accounts-form-field">
            <label for="pa_budget">Budget</label>
            <input id="pa_budget" name="activity_budget" inputmode="decimal">
        </div>
        <div class="accounts-form-field">
            <label for="pa_schedule">Schedule</label>
            <input id="pa_schedule" name="activity_schedule" maxlength="255" placeholder="e.g. Q1: Mar 04, 2027">
        </div>
        <div class="accounts-form-actions">
            <button type="submit" class="btn btn-accent">Add Activity</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Conducted Activities (<?= count($conducted) ?>)</h2>
    <p class="muted">Entries on the Conducted Activities page that were linked to this project.</p>
    <table class="data-table">
        <thead><tr><th>Date</th><th>Activity</th><th>Type</th><th>Entered by</th></tr></thead>
        <tbody>
        <?php foreach ($conducted as $c): ?>
            <tr>
                <td><?= $e($c['activity_date'] ?? '-') ?></td>
                <td><?= $e($c['title']) ?></td>
                <td><?= $e(\App\Support\ExtensionActivityTypes::get((string) $c['activity_type'])['label'] ?? $c['activity_type']) ?></td>
                <td><?= $e(trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($conducted === []): ?>
            <tr><td colspan="4" class="muted">None linked yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
