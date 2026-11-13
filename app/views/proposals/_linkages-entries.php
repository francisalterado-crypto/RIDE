<?php
/** @var list<array<string, string>> $rows */
/** @var bool $readOnly */
?>
<section class="proposal-section completed-researches-section linkages-section trainings-conducted-section">
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap linkages-table-wrap">
        <table class="proposal-table trainings-conducted-table linkages-table">
            <colgroup>
                <col class="link-col-program">
                <col class="link-col-partner">
                <col class="link-col-forged">
                <col class="link-col-institution">
                <col class="link-col-deliverables">
                <col class="link-col-date">
                <col class="link-col-date">
                <col class="link-col-personnel">
                <col class="link-col-beneficiaries">
                <col class="link-col-drive-link">
                <?php if (!$readOnly): ?>
                    <col class="link-col-actions">
                <?php endif; ?>
            </colgroup>
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
                    <th>Google Drive Link</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="linkages-rows">
                <?php if ($rows === []): ?>
                    <?php $rows = [[], [], [], [], []]; ?>
                <?php endif; ?>
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="linkages-row">
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][program]' ?>"><?= htmlspecialchars((string) ($row['program'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][partner]' ?>"><?= htmlspecialchars((string) ($row['partner'] ?? '')) ?></textarea></td>
                        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][linkage_forged]' ?>" value="<?= htmlspecialchars((string) ($row['linkage_forged'] ?? '')) ?>"></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][institution_type]' ?>"><?= htmlspecialchars((string) ($row['institution_type'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][deliverables]' ?>"><?= htmlspecialchars((string) ($row['deliverables'] ?? '')) ?></textarea></td>
                        <td><input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][date_started]' ?>" value="<?= htmlspecialchars((string) ($row['date_started'] ?? '')) ?>"></td>
                        <td><input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][date_completed]' ?>" value="<?= htmlspecialchars((string) ($row['date_completed'] ?? '')) ?>"></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][personnel]' ?>"><?= htmlspecialchars((string) ($row['personnel'] ?? '')) ?></textarea></td>
                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : 'entries[' . $index . '][beneficiaries]' ?>"><?= htmlspecialchars((string) ($row['beneficiaries'] ?? '')) ?></textarea></td>
                        <td class="link-col-drive-link-cell">
                            <?php
                            $driveLink = trim((string) ($row['google_drive_link'] ?? ''));
                            if ($readOnly): ?>
                                <?php if ($driveLink !== ''): ?>
                                    <a href="<?= htmlspecialchars($driveLink) ?>" target="_blank" rel="noopener noreferrer">View file</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            <?php else: ?>
                                <input type="url" name="entries[<?= $index ?>][google_drive_link]" value="<?= htmlspecialchars($driveLink) ?>" placeholder="https://drive.google.com/...">
                            <?php endif; ?>
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline linkages-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline completed-researches-add-row" id="linkages-add-row">Add Row</button>
        </div>
    <?php endif; ?>
</section>
