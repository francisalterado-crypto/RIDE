<?php
/** @var list<array<string, mixed>> $templates */

use App\Models\FormTemplate;

$pageTitle = 'Downloadable Forms — RIDE IMS';
$pageHeading = 'Downloadable Forms';
$pageSubtitle = 'Download official templates (accomplishment reports, attendance sheets, and more), fill them out, and attach them where required.';

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

<?php if ($templates === []): ?>
<div class="card">
    <div class="empty-state">
        <i class="fas fa-file-download empty-icon" aria-hidden="true"></i>
        <p>No downloadable forms have been published yet. The RIDE administrator uploads templates here.</p>
    </div>
</div>
<?php endif; ?>

<?php foreach (FormTemplate::AUDIENCE_LABELS as $audience => $label): ?>
    <?php if (empty($grouped[$audience])) { continue; } ?>
    <div class="card" style="margin-bottom: 1.5rem;">
        <h2><?= htmlspecialchars($label) ?></h2>
        <table>
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
                            <i class="fas fa-download" aria-hidden="true"></i> Download
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
