<?php
/** @var string $tab */
/** @var list<array> $colleges */
/** @var ?int $collegeId */
/** @var ?int $programId */
/** @var ?int $year */
/** @var list<array> $academicPrograms */
/** @var list<array> $extensionPrograms */
/** @var list<array> $allExtensionPrograms */
/** @var list<array> $allAcademicPrograms */
/** @var list<array> $projects */

use App\Controllers\ProgramCatalogController;
use App\Models\ExtensionProgram;
use App\Models\ExtensionProject;

$pageTitle = 'Programs | RIDE Hub';
$pageHeading = 'Programs and Projects';
$pageSubtitle = 'Academic programs, the extension programs of each college, and the extension projects under them. Forms pick from these lists instead of free text.';
$e = static fn (mixed $v): string => htmlspecialchars((string) $v);
$tabUrl = static fn (string $t, array $extra = []): string => base_url('admin/programs?' . http_build_query(array_filter(['tab' => $t] + $extra)));
$collegeOptions = static function (?int $selected, string $emptyLabel) use ($colleges, $e): string {
    $html = '<option value="">' . $e($emptyLabel) . '</option>';
    foreach ($colleges as $c) {
        $html .= '<option value="' . (int) $c['id'] . '"' . ((int) $c['id'] === (int) $selected ? ' selected' : '') . '>'
            . $e($c['code'] . ' - ' . $c['name']) . '</option>';
    }

    return $html;
};
?>

<div class="page-actions-bar">
    <?php foreach (ProgramCatalogController::TABS as $key => $label): ?>
        <a class="btn <?= $tab === $key ? 'btn-accent' : 'btn-outline' ?>" href="<?= $tabUrl($key, ['college' => $collegeId]) ?>"><?= $e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
    <form method="get" action="<?= base_url('admin/programs') ?>" class="accounts-form-grid">
        <input type="hidden" name="tab" value="<?= $e($tab) ?>">
        <div class="accounts-form-field">
            <label for="filter_college">College</label>
            <select id="filter_college" name="college" onchange="this.form.submit()"><?= $collegeOptions($collegeId, 'All colleges') ?></select>
        </div>
        <?php if ($tab === 'projects'): ?>
            <div class="accounts-form-field">
                <label for="filter_program">Extension Program</label>
                <select id="filter_program" name="program" onchange="this.form.submit()">
                    <option value="">All programs</option>
                    <?php foreach ($extensionPrograms as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === (int) $programId ? ' selected' : '' ?>><?= $e($p['college_code'] . ' | ' . ExtensionProgram::label($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="accounts-form-field">
                <label for="filter_year">Year</label>
                <input id="filter_year" name="year" type="number" min="2000" max="2100" value="<?= $year ? (int) $year : '' ?>" placeholder="Any" onchange="this.form.submit()">
            </div>
        <?php endif; ?>
    </form>
</div>

<?php if ($tab === 'extension'): ?>
    <div class="card">
        <h2>Add Extension Program</h2>
        <form method="post" action="<?= base_url('admin/programs/extension') ?>" class="accounts-form-grid">
            <?= csrf_field() ?>
            <div class="accounts-form-field">
                <label for="ep_college">College <span class="required">*</span></label>
                <select id="ep_college" name="college_id" required><?= $collegeOptions($collegeId, 'Select college') ?></select>
            </div>
            <div class="accounts-form-field">
                <label for="ep_acronym">Acronym</label>
                <input id="ep_acronym" name="acronym" maxlength="40" placeholder="e.g. THERMACAD">
            </div>
            <div class="accounts-form-field accounts-form-field-span-2">
                <label for="ep_name">Program Name <span class="required">*</span></label>
                <input id="ep_name" name="name" maxlength="255" required placeholder="e.g. Mechanization Assistance to Small Fishermen, Farmers and Processors">
            </div>
            <div class="accounts-form-field">
                <label for="ep_period">Agenda Period</label>
                <input id="ep_period" name="agenda_period" maxlength="20" placeholder="e.g. 2025-2030">
            </div>
            <div class="accounts-form-actions">
                <button type="submit" class="btn btn-accent">Add Program</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Extension Programs (<?= count($extensionPrograms) ?>)</h2>
        <div class="accounts-table-wrap">
            <table class="data-table">
                <thead><tr><th>College</th><th>Acronym</th><th>Program</th><th>Agenda</th><th>Projects</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($extensionPrograms as $p): ?>
                    <tr>
                        <td><code><?= $e($p['college_code'] ?? '-') ?></code></td>
                        <td><?= $e($p['acronym'] ?? '-') ?></td>
                        <td><?= $e($p['name']) ?></td>
                        <td><?= $e($p['agenda_period'] ?? '-') ?></td>
                        <td><a href="<?= $tabUrl('projects', ['program' => (int) $p['id']]) ?>"><?= (int) $p['project_count'] ?></a></td>
                        <td><?= (int) $p['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= base_url('admin/programs/extension/' . (int) $p['id'] . '/edit') ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($extensionPrograms === []): ?>
                    <tr><td colspan="7" class="muted">No extension programs yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php elseif ($tab === 'projects'): ?>
    <div class="card">
        <h2>Add Extension Project</h2>
        <form method="post" action="<?= base_url('admin/programs/projects') ?>" class="accounts-form-grid">
            <?= csrf_field() ?>
            <div class="accounts-form-field accounts-form-field-span-2">
                <label for="pj_program">Extension Program <span class="required">*</span></label>
                <select id="pj_program" name="extension_program_id" required>
                    <option value="">Select program</option>
                    <?php foreach ($allExtensionPrograms as $p): ?>
                        <?php if ((int) $p['is_active'] !== 1) { continue; } ?>
                        <option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === (int) $programId ? ' selected' : '' ?>><?= $e($p['college_code'] . ' | ' . ExtensionProgram::label($p)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="accounts-form-field accounts-form-field-span-2">
                <label for="pj_title">Project Title <span class="required">*</span></label>
                <input id="pj_title" name="title" maxlength="500" required>
            </div>
            <div class="accounts-form-field">
                <label for="pj_academic">Academic Program</label>
                <select id="pj_academic" name="academic_program_id">
                    <option value="">None / all programs</option>
                    <?php foreach ($allAcademicPrograms as $a): ?>
                        <option value="<?= (int) $a['id'] ?>"><?= $e(($a['college_code'] ? $a['college_code'] . ' | ' : '') . $a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="accounts-form-field">
                <label for="pj_campus">Campus</label>
                <input id="pj_campus" name="campus" maxlength="80" placeholder="e.g. Main, PPC, Quezon">
            </div>
            <div class="accounts-form-field">
                <label for="pj_leader">Project Leader</label>
                <input id="pj_leader" name="leader_name" maxlength="255">
            </div>
            <div class="accounts-form-field">
                <label for="pj_year">Year</label>
                <input id="pj_year" name="year" type="number" min="2000" max="2100" value="<?= (int) date('Y') ?>">
            </div>
            <div class="accounts-form-actions">
                <button type="submit" class="btn btn-accent">Add Project</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Extension Projects (<?= count($projects) ?>)</h2>
        <div class="accounts-table-wrap">
            <table class="data-table">
                <thead><tr><th>College</th><th>Program</th><th>Project</th><th>Campus</th><th>Leader</th><th>Year</th><th>Budget</th><th>Activities</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td><code><?= $e($p['college_code'] ?? '-') ?></code></td>
                        <td><?= $e($p['program_acronym'] ?: $p['program_name']) ?></td>
                        <td><?= $e($p['title']) ?></td>
                        <td><?= $e($p['campus'] ?? '-') ?></td>
                        <td><?= $e($p['leader_name'] ?? '-') ?></td>
                        <td><?= $e($p['year'] ?? '-') ?></td>
                        <td><?= $p['total_budget'] !== null ? number_format((float) $p['total_budget'], 2) : '-' ?></td>
                        <td><?= (int) $p['activity_count'] ?> planned | <?= (int) $p['conducted_count'] ?> conducted</td>
                        <td><?= $e(ExtensionProject::STATUSES[(string) $p['status']] ?? $p['status']) ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= base_url('admin/programs/projects/' . (int) $p['id']) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($projects === []): ?>
                    <tr><td colspan="10" class="muted">No projects match these filters.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <h2>Add Academic Program</h2>
        <form method="post" action="<?= base_url('admin/programs/academic') ?>" class="accounts-form-grid">
            <?= csrf_field() ?>
            <div class="accounts-form-field">
                <label for="ap_college">College</label>
                <select id="ap_college" name="college_id"><?= $collegeOptions($collegeId, 'None (university-wide)') ?></select>
            </div>
            <div class="accounts-form-field">
                <label for="ap_name">Program Name <span class="required">*</span></label>
                <input id="ap_name" name="name" maxlength="150" required placeholder="e.g. BS in Computer Engineering">
            </div>
            <div class="accounts-form-actions">
                <button type="submit" class="btn btn-accent">Add Program</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h2>Academic Programs (<?= count($academicPrograms) ?>)</h2>
        <div class="accounts-table-wrap">
            <table class="data-table">
                <thead><tr><th>College</th><th>Program</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($academicPrograms as $a): ?>
                    <tr>
                        <td><code><?= $e($a['college_code'] ?? '-') ?></code></td>
                        <td><?= $e($a['name']) ?></td>
                        <td><?= (int) $a['is_active'] === 1 ? 'Active' : 'Inactive' ?></td>
                        <td><a class="btn btn-sm btn-outline" href="<?= base_url('admin/programs/academic/' . (int) $a['id'] . '/edit') ?>">Edit</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($academicPrograms === []): ?>
                    <tr><td colspan="4" class="muted">No academic programs yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
