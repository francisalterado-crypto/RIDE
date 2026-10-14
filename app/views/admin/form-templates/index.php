<?php
/** @var list<array<string, mixed>> $templates */

use App\Models\FormTemplate;

$pageTitle = 'Form Templates | RIDE';
$pageHeading = 'Form Templates';
$pageSubtitle = 'Upload the official templates users download from Downloadable Forms (e.g. Accomplishment Report, Technical Advisory AR, Outreach Activities AR, Attendance Sheet).';

$editId = (int) ($_GET['edit'] ?? 0);
$accept = FormTemplate::acceptAttribute();
$allowedLabel = FormTemplate::allowedExtensionsLabel();

$formatSize = static function (int $bytes): string {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    }

    return max(1, (int) round($bytes / 1024)) . ' KB';
};

$grouped = [];
foreach ($templates as $template) {
    $grouped[(string) $template['audience']][] = $template;
}
?>

<div class="card" style="margin-bottom: 1.5rem;">
    <h2>Upload Template</h2>
    <form method="post" action="<?= base_url('admin/form-templates') ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label for="title">Title <span class="required">*</span></label>
        <input type="text" id="title" name="title" maxlength="200" required placeholder="e.g. WPU-QSF-RIDE-ESO-13 Accomplishment Report">

        <label for="description">Instructions</label>
        <textarea id="description" name="description" rows="2" placeholder="Optional note shown under the form name"></textarea>

        <label for="audience">Visible to <span class="required">*</span></label>
        <select id="audience" name="audience" required>
            <?php foreach (FormTemplate::AUDIENCE_LABELS as $value => $label): ?>
                <option value="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="file">File <span class="required">*</span></label>
        <input type="file" id="file" name="file" accept="<?= htmlspecialchars($accept) ?>" required>
        <p class="muted"><?= htmlspecialchars($allowedLabel) ?> · Max 10 MB</p>

        <button type="submit" class="btn btn-accent">Upload Template</button>
    </form>
</div>

<?php if ($templates === []): ?>
<div class="card">
    <p class="muted">No templates yet. Uploaded templates appear on the Downloadable Forms page for the selected audience.</p>
</div>
<?php endif; ?>

<?php foreach (FormTemplate::AUDIENCE_LABELS as $audience => $label): ?>
    <?php if (empty($grouped[$audience])) { continue; } ?>
    <?php $rows = $grouped[$audience]; ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h2><?= htmlspecialchars($label) ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Form</th>
                    <th>File</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $index => $template): ?>
                <?php
                $id = (int) $template['id'];
                $isActive = (bool) $template['is_active'];
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string) $template['title']) ?></strong>
                        <?php if (!empty($template['description'])): ?>
                            <div class="muted"><?= nl2br(htmlspecialchars((string) $template['description'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('forms/' . $id . '/download') ?>"><?= htmlspecialchars((string) $template['original_name']) ?></a>
                        <div class="muted"><?= $formatSize((int) $template['file_size']) ?></div>
                    </td>
                    <td><span class="badge <?= $isActive ? 'badge-approved' : 'badge-draft' ?>"><?= $isActive ? 'Published' : 'Hidden' ?></span></td>
                    <td style="white-space: nowrap;">
                        <form method="post" action="<?= base_url('admin/form-templates/' . $id . '/move') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="direction" value="-1">
                            <button type="submit" class="btn btn-outline btn-sm" title="Move up" aria-label="Move up" <?= $index === 0 ? 'disabled' : '' ?>>&#9650;</button>
                        </form>
                        <form method="post" action="<?= base_url('admin/form-templates/' . $id . '/move') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="direction" value="1">
                            <button type="submit" class="btn btn-outline btn-sm" title="Move down" aria-label="Move down" <?= $index === count($rows) - 1 ? 'disabled' : '' ?>>&#9660;</button>
                        </form>
                        <a href="<?= base_url('admin/form-templates?edit=' . $id) ?>" class="btn btn-outline btn-sm icon-btn" title="Edit template" aria-label="Edit template">
                            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                        </a>
                        <form method="post" action="<?= base_url('admin/form-templates/' . $id . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this form template?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger btn-sm icon-btn" title="Delete template" aria-label="Delete template">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php if ($editId === $id): ?>
                <tr>
                    <td colspan="4">
                        <form method="post" action="<?= base_url('admin/form-templates/' . $id . '/update') ?>" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <label for="edit_title_<?= $id ?>">Title</label>
                            <input type="text" id="edit_title_<?= $id ?>" name="title" maxlength="200" required value="<?= htmlspecialchars((string) $template['title']) ?>">

                            <label for="edit_description_<?= $id ?>">Instructions</label>
                            <textarea id="edit_description_<?= $id ?>" name="description" rows="2"><?= htmlspecialchars((string) ($template['description'] ?? '')) ?></textarea>

                            <label for="edit_audience_<?= $id ?>">Visible to</label>
                            <select id="edit_audience_<?= $id ?>" name="audience" required>
                                <?php foreach (FormTemplate::AUDIENCE_LABELS as $value => $optionLabel): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $value === $template['audience'] ? 'selected' : '' ?>><?= htmlspecialchars($optionLabel) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label for="edit_file_<?= $id ?>">Replace file</label>
                            <input type="file" id="edit_file_<?= $id ?>" name="file" accept="<?= htmlspecialchars($accept) ?>">
                            <p class="muted">Leave empty to keep the current file.</p>

                            <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                                <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                                Published on Downloadable Forms
                            </label>

                            <div style="display:flex;gap:0.5rem;margin-top:0.85rem;">
                                <button type="submit" class="btn btn-accent btn-sm">Save changes</button>
                                <a href="<?= base_url('admin/form-templates') ?>" class="btn btn-outline btn-sm">Cancel</a>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
