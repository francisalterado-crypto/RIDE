<?php
/** @var list<array<string, string>> $rows */
/** @var bool $readOnly */
$readOnly = $readOnly ?? false;
$rows = $rows ?? [];
if ($rows === []) {
    $rows = [[
        'program' => '',
        'partner' => '',
        'linkage_forged' => '',
        'institution_type' => '',
        'deliverables' => '',
        'date_started' => '',
        'date_completed' => '',
        'personnel' => '',
        'beneficiaries' => '',
        'college' => '',
        'campus' => '',
        'google_drive_link' => '',
    ]];
}

$entryValue = static function (array $row, string $key): string {
    $value = $row[$key] ?? '';
    return is_string($value) ? $value : '';
};
?>
<section class="proposal-section completed-researches-section linkages-section trainings-conducted-section consolidated-linkages-section">
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap linkages-table-wrap">
        <table class="proposal-table trainings-conducted-table linkages-table">
            <thead>
                <tr>
                    <th>Linkage/Partnership/ Collaboration Program</th>
                    <th>Partner/ Collaborating Institution</th>
                    <th>Linkage Forged (MOA/MOU)</th>
                    <th>Institution Type of Partner Institutions</th>
                    <th>Deliverables of Desired Output/s</th>
                    <th>Date Started</th>
                    <th>Date of Completion</th>
                    <th>Personnel Involved</th>
                    <th>Target Beneficiaries</th>
                    <th>College</th>
                    <th>Campus</th>
                    <th>Google Drive Link</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="consolidated-linkages-rows" class="consolidated-linkages-rows">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="consolidated-linkages-row">
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'program') !== '' ? $entryValue($row, 'program') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][program]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'program')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'partner') !== '' ? $entryValue($row, 'partner') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][partner]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'partner')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'linkage_forged') !== '' ? $entryValue($row, 'linkage_forged') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= (int) $index ?>][linkage_forged]" value="<?= htmlspecialchars($entryValue($row, 'linkage_forged')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'institution_type') !== '' ? $entryValue($row, 'institution_type') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][institution_type]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'institution_type')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'deliverables') !== '' ? $entryValue($row, 'deliverables') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][deliverables]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'deliverables')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_started') !== '' ? $entryValue($row, 'date_started') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][date_started]" value="<?= htmlspecialchars($entryValue($row, 'date_started')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_completed') !== '' ? $entryValue($row, 'date_completed') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][date_completed]" value="<?= htmlspecialchars($entryValue($row, 'date_completed')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'personnel') !== '' ? $entryValue($row, 'personnel') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][personnel]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'personnel')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'beneficiaries') !== '' ? $entryValue($row, 'beneficiaries') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= (int) $index ?>][beneficiaries]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'beneficiaries')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'college') !== '' ? $entryValue($row, 'college') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= (int) $index ?>][college]" value="<?= htmlspecialchars($entryValue($row, 'college')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'campus') !== '' ? $entryValue($row, 'campus') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= (int) $index ?>][campus]" value="<?= htmlspecialchars($entryValue($row, 'campus')) ?>">
                            <?php endif; ?>
                        </td>
                        <td class="link-col-drive-link-cell">
                            <?php
                            $driveLink = $entryValue($row, 'google_drive_link');
                            if ($readOnly): ?>
                                <?php if ($driveLink !== ''): ?>
                                    <a href="<?= htmlspecialchars($driveLink) ?>" target="_blank" rel="noopener noreferrer">View file</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            <?php else: ?>
                                <input
                                    type="url"
                                    name="entries[<?= (int) $index ?>][google_drive_link]"
                                    value="<?= htmlspecialchars($driveLink) ?>"
                                    placeholder="https://drive.google.com/..."
                                >
                            <?php endif; ?>
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline consolidated-linkages-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline consolidated-linkages-add-row">Add Row</button>
        </div>
    <?php endif; ?>
</section>
