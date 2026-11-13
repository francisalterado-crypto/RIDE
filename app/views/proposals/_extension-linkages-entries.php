<?php
/** @var list<array<string, string>> $rows */
/** @var bool $readOnly */

$readOnly = $readOnly ?? false;
$rows = $rows ?? [];
if ($rows === []) {
    $rows = [[], [], [], [], [], [], [], [], [], []];
}

$entryValue = static function (array $row, string $key): string {
    $value = $row[$key] ?? '';
    return is_string($value) ? $value : '';
};
?>
<section class="proposal-section trainings-conducted-section">
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table extension-linkages-table">
            <thead>
                <tr>
                    <th rowspan="2" scope="col">Partner LGU / Community / Industry / SMEs / Private or Public Agencies / NGOs</th>
                    <th rowspan="2" scope="col">Linkage Forged (MOA/MOU) <sup>[1]</sup></th>
                    <th rowspan="2" scope="col">Description of Linkage/Partnership</th>
                    <th colspan="2" scope="colgroup">Effectivity of MOA/MOU</th>
                    <th rowspan="2" scope="col">Extension Activities</th>
                    <th colspan="2" scope="colgroup">Date Conducted</th>
                    <?php if (!$readOnly): ?>
                        <th rowspan="2" scope="col" class="completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                    <th scope="col">From</th>
                    <th scope="col">To</th>
                </tr>
            </thead>
            <tbody class="extension-linkages-rows">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="extension-linkages-row">
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'partner') !== '' ? $entryValue($row, 'partner') : '-')) ?>
                            <?php else: ?>
                                <textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][partner]"><?= htmlspecialchars($entryValue($row, 'partner')) ?></textarea>
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
                                <?= nl2br(htmlspecialchars($entryValue($row, 'description') !== '' ? $entryValue($row, 'description') : '-')) ?>
                            <?php else: ?>
                                <textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][description]"><?= htmlspecialchars($entryValue($row, 'description')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'effectivity_from') !== '' ? $entryValue($row, 'effectivity_from') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][effectivity_from]" value="<?= htmlspecialchars($entryValue($row, 'effectivity_from')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'effectivity_to') !== '' ? $entryValue($row, 'effectivity_to') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][effectivity_to]" value="<?= htmlspecialchars($entryValue($row, 'effectivity_to')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'extension_activities') !== '' ? $entryValue($row, 'extension_activities') : '-')) ?>
                            <?php else: ?>
                                <textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][extension_activities]"><?= htmlspecialchars($entryValue($row, 'extension_activities')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'conducted_from') !== '' ? $entryValue($row, 'conducted_from') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][conducted_from]" value="<?= htmlspecialchars($entryValue($row, 'conducted_from')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'conducted_to') !== '' ? $entryValue($row, 'conducted_to') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= (int) $index ?>][conducted_to]" value="<?= htmlspecialchars($entryValue($row, 'conducted_to')) ?>">
                            <?php endif; ?>
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline extension-linkages-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <button type="button" class="btn btn-outline completed-researches-add-row" id="extension-linkages-add-row">Add Row</button>
    <?php endif; ?>
</section>
