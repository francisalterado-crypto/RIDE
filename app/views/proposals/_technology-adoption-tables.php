<?php
/** @var list<array<string, string>> $demonstrationFarmEntries */
/** @var list<array<string, string>> $trainingEntries */
/** @var bool $readOnly */

$demoRows = is_array($demonstrationFarmEntries) ? $demonstrationFarmEntries : [];
$trainingRows = is_array($trainingEntries) ? $trainingEntries : [];
if ($demoRows === [] && !$readOnly) {
    $demoRows = [[], [], []];
}
if ($trainingRows === [] && !$readOnly) {
    $trainingRows = [[], [], []];
}

$countRowContent = static function (array $row, array $fields): bool {
    foreach ($fields as $field) {
        $value = trim((string) ($row[$field] ?? ''));
        if ($value !== '' && $value !== '0') {
            return true;
        }
    }
    return false;
};

$countCommercialized = static function (array $rows): int {
    $count = 0;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (trim((string) ($row['date_commercialized'] ?? '')) !== '') {
            $count++;
        }
    }
    return $count;
};

$demoFields = [
    'extension_personnel',
    'demonstration_farm',
    'year_established',
    'technology_demonstrated',
    'adopter_name',
    'adopter_location',
    'date_adopted',
    'date_commercialized',
    'net_income',
];
$trainingFields = [
    'extension_personnel',
    'extension_service_title',
    'date_conducted',
    'technology_demonstrated',
    'adopter_name',
    'adopter_location',
    'date_adopted',
    'date_commercialized',
    'net_income',
];

$totalFarms = 0;
foreach ($demoRows as $row) {
    if (is_array($row) && $countRowContent($row, $demoFields)) {
        $totalFarms++;
    }
}
$totalCommercialized = $countCommercialized($demoRows) + $countCommercialized($trainingRows);

$renderDataRow = static function (
    int $index,
    array $row,
    string $namePrefix,
    string $rowClass,
    string $siteField,
    string $periodField,
    string $periodInputType,
    bool $readOnly
): void {
    $prefix = $readOnly ? '' : $namePrefix . '[' . $index . ']';
    ?>
    <tr class="<?= htmlspecialchars($rowClass) ?>">
        <td class="ta-col-no"><span class="ta-row-num"><?= $index + 1 ?></span></td>
        <td>
            <input type="text" class="proposal-textarea-compact" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[extension_personnel]' ?>" value="<?= htmlspecialchars((string) ($row['extension_personnel'] ?? '')) ?>">
        </td>
        <td>
            <textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[' . $siteField . ']' ?>"><?= htmlspecialchars((string) ($row[$siteField] ?? '')) ?></textarea>
        </td>
        <td>
            <?php if ($periodInputType === 'date'): ?>
                <input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[' . $periodField . ']' ?>" value="<?= htmlspecialchars((string) ($row[$periodField] ?? '')) ?>">
            <?php else: ?>
                <input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[' . $periodField . ']' ?>" value="<?= htmlspecialchars((string) ($row[$periodField] ?? '')) ?>" placeholder="2024">
            <?php endif; ?>
        </td>
        <td>
            <textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[technology_demonstrated]' ?>"><?= htmlspecialchars((string) ($row['technology_demonstrated'] ?? '')) ?></textarea>
        </td>
        <td>
            <input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[adopter_name]' ?>" value="<?= htmlspecialchars((string) ($row['adopter_name'] ?? '')) ?>">
        </td>
        <td>
            <input type="text" data-location-picker="ph" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[adopter_location]' ?>" value="<?= htmlspecialchars((string) ($row['adopter_location'] ?? '')) ?>">
        </td>
        <td>
            <input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[date_adopted]' ?>" value="<?= htmlspecialchars((string) ($row['date_adopted'] ?? '')) ?>">
        </td>
        <td>
            <input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[date_commercialized]' ?>" value="<?= htmlspecialchars((string) ($row['date_commercialized'] ?? '')) ?>">
        </td>
        <td>
            <input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $prefix . '[net_income]' ?>" value="<?= htmlspecialchars((string) ($row['net_income'] ?? '')) ?>">
        </td>
        <?php if (!$readOnly): ?>
            <td class="completed-researches-actions-col">
                <button type="button" class="btn btn-sm btn-outline technology-adoption-remove-row" title="Remove row">Remove</button>
            </td>
        <?php endif; ?>
    </tr>
    <?php
};

$renderTableHead = static function (string $siteHeader, string $periodHeader, bool $readOnly): void {
    ?>
    <thead>
        <tr>
            <th rowspan="2" class="ta-col-no">No.</th>
            <th rowspan="2">Name of Extension Personnel</th>
            <th rowspan="2"><?= htmlspecialchars($siteHeader) ?></th>
            <th rowspan="2"><?= htmlspecialchars($periodHeader) ?></th>
            <th rowspan="2">Technology Demonstrated / Product / Enterprise</th>
            <th rowspan="2">Name of Adopter</th>
            <th rowspan="2">Location of Adopter</th>
            <th colspan="2">Date Adopted (mm/dd/yyyy)</th>
            <th rowspan="2">Net Income</th>
            <?php if (!$readOnly): ?>
                <th rowspan="2" class="completed-researches-actions-col">Actions</th>
            <?php endif; ?>
        </tr>
        <tr>
            <th>Adopted</th>
            <th>Commercialized</th>
        </tr>
    </thead>
    <?php
};
?>

<section class="proposal-section trainings-conducted-section">
    <h3 class="proposal-subtitle">A. Technology Adoption through the Demonstration Farm</h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table technology-adoption-table technology-adoption-demo-table" data-section="demonstration_farm">
            <?php $renderTableHead('Name and Location of Demonstration Farm', 'Year Established', $readOnly); ?>
            <tbody>
                <?php foreach ($demoRows as $index => $row): ?>
                    <?php
                    if (!is_array($row)) {
                        $row = [];
                    }
                    $renderDataRow(
                        (int) $index,
                        $row,
                        'demonstration_farm_entries',
                        'technology-adoption-demo-row',
                        'demonstration_farm',
                        'year_established',
                        'text',
                        $readOnly
                    );
                    ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="trainings-conducted-add-buttons">
            <button type="button" class="btn btn-outline btn-sm technology-adoption-add-row" data-section="demonstration_farm">Add Row (Section A)</button>
        </div>
    <?php endif; ?>
</section>

<section class="proposal-section trainings-conducted-section">
    <h3 class="proposal-subtitle">B. Technology Adoption through Trainings Extended</h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table technology-adoption-table technology-adoption-training-table" data-section="training">
            <?php $renderTableHead('Title of Extension Service Provided', 'Date Conducted', $readOnly); ?>
            <tbody>
                <?php foreach ($trainingRows as $index => $row): ?>
                    <?php
                    if (!is_array($row)) {
                        $row = [];
                    }
                    $renderDataRow(
                        (int) $index,
                        $row,
                        'training_entries',
                        'technology-adoption-training-row',
                        'extension_service_title',
                        'date_conducted',
                        'date',
                        $readOnly
                    );
                    ?>
                <?php endforeach; ?>
                <tr class="technology-adoption-summary-row">
                    <td colspan="3"><strong>Total No. of Demonstration Farms</strong></td>
                    <td colspan="6" class="ta-total-farms"><?= (int) $totalFarms ?></td>
                    <?php if (!$readOnly): ?>
                        <td></td>
                    <?php endif; ?>
                </tr>
                <tr class="technology-adoption-summary-row">
                    <td colspan="3"><strong>Total No. of Technologies Adopted and Commercialized</strong></td>
                    <td colspan="6" class="ta-total-commercialized"><?= (int) $totalCommercialized ?></td>
                    <?php if (!$readOnly): ?>
                        <td></td>
                    <?php endif; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="trainings-conducted-add-buttons">
            <button type="button" class="btn btn-outline btn-sm technology-adoption-add-row" data-section="training">Add Row (Section B)</button>
        </div>
    <?php endif; ?>
</section>
