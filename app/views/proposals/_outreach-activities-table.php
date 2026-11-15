<?php
/** @var list<array<string, string>> $entries */
/** @var bool $readOnly */

$rows = is_array($entries) ? $entries : [];
if ($rows === []) {
    $rows = $readOnly ? [] : [[], [], [], [], []];
}

$totalActivities = 0;
$totalBeneficiaries = 0;
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $hasContent = false;
    foreach (['title', 'venue', 'start_date', 'end_date', 'beneficiaries'] as $key) {
        $value = trim((string) ($row[$key] ?? ''));
        if ($value !== '' && $value !== '0') {
            $hasContent = true;
            break;
        }
    }
    if ($hasContent) {
        $totalActivities++;
    }
    $totalBeneficiaries += max(0, (int) ($row['beneficiaries'] ?? 0));
}
?>
<section class="proposal-section outreach-activities-section">
    <div class="proposal-table-wrap">
        <table class="proposal-table completed-researches-table outreach-activities-table">
            <thead>
                <tr>
                    <th>Title of Outreach Activity</th>
                    <th>Venue</th>
                    <th colspan="2">Inclusive Dates</th>
                    <th>No. of Beneficiaries</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th>Start (mm/dd/yyyy)</th>
                    <th>End (mm/dd/yyyy)</th>
                    <th></th>
                    <?php if (!$readOnly): ?>
                        <th></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $index => $row): ?>
                    <?php $namePrefix = 'entries[' . (int) $index . ']'; ?>
                    <tr class="outreach-activities-row">
                        <td>
                            <textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[title]' ?>"><?= htmlspecialchars((string) ($row['title'] ?? '')) ?></textarea>
                        </td>
                        <td>
                            <input type="text" data-location-picker="ph" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[venue]' ?>" value="<?= htmlspecialchars((string) ($row['venue'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[start_date]' ?>" value="<?= htmlspecialchars((string) ($row['start_date'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[end_date]' ?>" value="<?= htmlspecialchars((string) ($row['end_date'] ?? '')) ?>">
                        </td>
                        <td>
                            <input type="number" min="0" step="1" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[beneficiaries]' ?>" value="<?= htmlspecialchars((string) ($row['beneficiaries'] ?? '')) ?>">
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline outreach-activities-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <tr class="outreach-activities-total-row">
                    <td colspan="2"><strong>Total No. of Outreach Activities</strong></td>
                    <td colspan="2" class="oa-total-activities"><?= (int) $totalActivities ?></td>
                    <td><strong>Total: <span class="oa-total-beneficiaries"><?= (int) $totalBeneficiaries ?></span></strong></td>
                    <?php if (!$readOnly): ?>
                        <td></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions">
            <button type="button" class="btn btn-outline btn-sm outreach-activities-add-row">Add Row</button>
        </div>
    <?php endif; ?>
</section>
