<?php
/** @var list<array<string, mixed>> $entries */
/** @var bool $readOnly */

$ratingHeaders = [
    ['Poor', '1'],
    ['Fair', '2'],
    ['Good', '3'],
    ['Better', '4'],
    ['Best', '5'],
];

$entryCount = 0;

if (!is_array($entries)) {
    $entries = [];
}

foreach ($entries as $row) {
    if (!is_array($row)) {
        continue;
    }
    $hasContent = false;
    foreach ($row as $key => $value) {
        if ((string) $value !== '' && (string) $value !== '0') {
            $hasContent = true;
            break;
        }
    }
    if ($hasContent) {
        $entryCount++;
    }
}

$renderRow = static function (int $index, array $row, bool $readOnly): void {
    $namePrefix = $readOnly ? '' : 'entries[' . $index . ']';
    ?>
    <tr class="technical-advisory-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[extension_program]' ?>"><?= htmlspecialchars((string) ($row['extension_program'] ?? '')) ?></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[technical_advisory_conducted]' ?>"><?= htmlspecialchars((string) ($row['technical_advisory_conducted'] ?? '')) ?></textarea></td>
        <td><input type="text" data-location-picker="ph" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[venue]' ?>" value="<?= htmlspecialchars((string) ($row['venue'] ?? '')) ?>"></td>
        <td><input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[advisory_date]' ?>" value="<?= htmlspecialchars((string) ($row['advisory_date'] ?? '')) ?>"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[client_type]' ?>" value="<?= htmlspecialchars((string) ($row['client_type'] ?? '')) ?>" placeholder="LGU, Farmer"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-clients-served tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[clients_served]' ?>" value="<?= htmlspecialchars((string) ($row['clients_served'] ?? '')) ?>"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[personnel_name]' ?>" value="<?= htmlspecialchars((string) ($row['personnel_name'] ?? '')) ?>"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[trainees_surveyed]' ?>" value="<?= htmlspecialchars((string) ($row['trainees_surveyed'] ?? '')) ?>"></td>
        <?php foreach (['quality_poor', 'quality_fair', 'quality_good', 'quality_better', 'quality_best'] as $field): ?>
            <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[' . $field . ']' ?>" value="<?= htmlspecialchars((string) ($row[$field] ?? '')) ?>"></td>
        <?php endforeach; ?>
        <?php foreach (['timeliness_poor', 'timeliness_fair', 'timeliness_good', 'timeliness_better', 'timeliness_best'] as $field): ?>
            <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[' . $field . ']' ?>" value="<?= htmlspecialchars((string) ($row[$field] ?? '')) ?>"></td>
        <?php endforeach; ?>
        <?php if (!$readOnly): ?>
            <td class="completed-researches-actions-col">
                <button type="button" class="btn btn-sm btn-outline technical-advisory-remove-row" title="Remove row">Remove</button>
            </td>
        <?php endif; ?>
    </tr>
    <?php
};
?>
<section class="proposal-section trainings-conducted-section">
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table technical-advisory-table">
            <colgroup>
                <col class="tc-col-program">
                <col class="tc-col-title">
                <col class="tc-col-venue">
                <col class="tc-col-date">
                <col class="tc-col-participants">
                <col class="tc-col-count">
                <col class="tc-col-personnel">
                <col class="tc-col-count">
                <?php for ($i = 0; $i < 10; $i++): ?>
                    <col class="tc-col-rating">
                <?php endfor; ?>
                <?php if (!$readOnly): ?>
                    <col class="tc-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" scope="col" class="tc-col-program">Extension Program</th>
                    <th rowspan="2" scope="col" class="tc-col-title">Technical Advisory Conducted</th>
                    <th rowspan="2" scope="col" class="tc-col-venue">Venue</th>
                    <th rowspan="2" scope="col" class="tc-col-date">Date</th>
                    <th rowspan="2" scope="col" class="tc-col-participants">Type of Clients</th>
                    <th rowspan="2" scope="col" class="tc-col-count">No. of Client served</th>
                    <th rowspan="2" scope="col" class="tc-col-personnel">Resource Person&apos;s/Name of Personnel</th>
                    <th rowspan="2" scope="col" class="tc-col-count">No. of Trainees Surveyed</th>
                    <th colspan="5" scope="colgroup">No. of Trainees who Rate the Quality of Training as</th>
                    <th colspan="5" scope="colgroup">No. of Trainees who Rate the Timeliness of Training as</th>
                    <?php if (!$readOnly): ?>
                        <th rowspan="2" scope="col" class="tc-col-actions completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <?php foreach ($ratingHeaders as [$label, $num]): ?>
                        <th scope="col" class="tc-col-rating"><span class="tc-rating-label"><?= htmlspecialchars($label) ?></span><span class="tc-rating-num"><?= htmlspecialchars($num) ?></span></th>
                    <?php endforeach; ?>
                    <?php foreach ($ratingHeaders as [$label, $num]): ?>
                        <th scope="col" class="tc-col-rating"><span class="tc-rating-label"><?= htmlspecialchars($label) ?></span><span class="tc-rating-num"><?= htmlspecialchars($num) ?></span></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = $entries;
                if ($rows === []) {
                    $rows = $readOnly ? [] : [[], [], []];
                }
                ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php $renderRow((int) $index, is_array($row) ? $row : [], $readOnly); ?>
                <?php endforeach; ?>
                <tr class="technical-advisory-total-row">
                    <td colspan="8" class="tc-total-label">
                        <strong>Total No. of Technical Advisories Conducted: <span class="tc-total-advisories"><?= (int) $entryCount ?></span></strong>
                    </td>
                    <td colspan="10" class="tc-total-spacer"></td>
                    <?php if (!$readOnly): ?>
                        <td class="tc-total-spacer"></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="trainings-conducted-add-buttons">
            <button type="button" class="btn btn-outline technical-advisory-add-row">Add Row</button>
        </div>
    <?php endif; ?>
</section>
