<?php
/** @var array<string, mixed> $entry */
/** @var array<string, mixed> $definition */
/** @var array<string, array<string, string>> $checklist */
/** @var array<string, list<array<string, mixed>>> $documents */
/** @var bool $isOwner */

$section = (string) $entry['section'];
$office = \App\Support\ExtensionActivityTypes::officeOf((string) $entry['activity_type']);
$basePath = \App\Support\ExtensionActivityTypes::basePath($office);
$terms = \App\Support\ExtensionActivityTypes::officeTerms($office);
$pageHeading = (string) $definition['label'];
$pageSubtitle = $terms['verb'] . ' ' . trim((string) $entry['first_name'] . ' ' . (string) $entry['last_name']) . '.';
$sectionLabel = (string) ($definition['sections'][$section]['label'] ?? '');
$fields = \App\Support\ExtensionActivityTypes::fieldsFor((string) $entry['activity_type'], $section);
?>

<div class="page-actions-bar">
    <?php if ($isOwner): ?>
        <a class="btn" href="<?= base_url($basePath . '/' . (int) $entry['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
    <a class="btn btn-outline" href="<?= base_url($basePath . '?type=' . $definition['slug']) ?>">Back to <?= htmlspecialchars($terms['page']) ?></a>
</div>

<div class="card activity-form">
    <div class="data-table-wrap">
        <table class="data-table">
            <tbody>
                <?php if ($sectionLabel !== ''): ?>
                    <tr><th>Category</th><td><?= htmlspecialchars($sectionLabel) ?></td></tr>
                <?php endif; ?>
                <?php foreach ($fields as $name => $field): ?>
                    <?php
                    $value = (string) ($entry['data'][$name] ?? '');
                    if ($field['type'] === 'select') {
                        $value = (string) ($field['options'][$value] ?? $value);
                    }
                    $label = isset($field['group']) ? $field['group'] . ' - ' . $field['label'] : $field['label'];
                    ?>
                    <tr>
                        <th style="width: 40%;"><?= htmlspecialchars((string) $label) ?></th>
                        <td><?= $value !== '' ? nl2br(htmlspecialchars($value)) : '<span class="muted">-</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($checklist !== []): ?>
        <section class="activity-documents">
            <h3><?= htmlspecialchars(\App\Support\ExtensionActivityTypes::documentsName((string) $entry['activity_type'])[1]) ?></h3>
            <?php foreach ($checklist as $group => $items): ?>
                <?php $optionalGroup = $group === \App\Support\ExtensionActivityTypes::OPTIONAL_GROUP; ?>
                <div class="data-table-wrap">
                    <table class="data-table activity-documents-table">
                        <thead><tr><th><?= htmlspecialchars($group) ?></th><th>Files</th></tr></thead>
                        <tbody>
                        <?php foreach ($items as $category => $label): ?>
                            <?php $files = $documents[$category] ?? []; ?>
                            <tr>
                                <td>
                                    <i class="<?= $files !== [] ? 'fas fa-check-circle activity-doc-done' : ($optionalGroup ? 'far fa-circle activity-doc-missing' : 'fas fa-circle activity-doc-missing') ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($label) ?>
                                </td>
                                <td>
                                    <?php if ($files === []): ?><span class="muted"><?= $optionalGroup ? 'Not attached' : 'Missing' ?></span><?php endif; ?>
                                    <?php foreach ($files as $file): ?>
                                        <div class="activity-doc-file">
                                            <a href="<?= base_url($basePath . '/' . (int) $entry['id'] . '/documents/' . (int) $file['id']) ?>" target="_blank" rel="noopener">
                                                <?= htmlspecialchars((string) $file['original_name']) ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</div>
