<?php
/** @var list<array<string, mixed>> $templates */

use App\Models\FormTemplate;

/** $pageTitle is set by the controller. */
$pageHeading ??= 'Downloadable Forms';
$pageSubtitle ??= 'Download official templates (accomplishment reports, attendance sheets, and more), fill them out, and attach them where required.';
$canManageForms = \App\Core\Auth::hasRole('ride_admin');

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

<?php if ($canManageForms): ?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/form-templates') ?>">Manage forms</a>
</div>
<?php endif; ?>

<?php if ($templates === []): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-file-download empty-icon" aria-hidden="true"></i>
        <p>No forms have been posted yet. Once the RIDE administrator uploads them, they will appear here.</p>
    </div>
</div>
<?php endif; ?>

<?php foreach (FormTemplate::AUDIENCE_LABELS as $audience => $label): ?>
    <?php if (empty($grouped[$audience])) { continue; } ?>
    <div class="card list-card" style="margin-bottom: 1.5rem;">
        <div class="list-card-head"><h2><?= htmlspecialchars($label) ?></h2></div>
        <div class="data-table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Form</th>
                    <th>File</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grouped[$audience] as $template): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars((string) $template['title']) ?></strong>
                        <?php if (!empty($template['description'])): ?>
                            <div class="muted"><?= nl2br(htmlspecialchars((string) $template['description'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= htmlspecialchars((string) $template['original_name']) ?>
                        <div class="muted"><?= $formatSize((int) $template['file_size']) ?></div>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-accent" href="<?= base_url('forms/' . (int) $template['id'] . '/download') ?>">
                            Download
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php endforeach; ?>
