<?php
/** @var array{need_based: list<array<string, mixed>>, other: list<array<string, mixed>>} $entries */
/** @var bool $readOnly */

$trainingLengthOptions = [
    'lt_8h' => '< 8 hours (0.5)',
    '8h_1d' => '8 hours or 1 day (1)',
    '2d' => '2 days (1.25)',
    '3_4d' => '3-4 days (1.5)',
    '5d_plus' => '5 days or more (2)',
];

$sections = [
    'need_based' => 'A. Need-based Trainings Conducted',
    'other' => 'B. Other Trainings Conducted',
];

$ratingHeaders = [
    ['Poor', '1'],
    ['Fair', '2'],
    ['Good', '3'],
    ['Better', '4'],
    ['Best', '5'],
];

$colCount = $readOnly ? 22 : 23;

$sectionTotals = ['need_based' => ['persons' => 0, 'weighted' => 0.0], 'other' => ['persons' => 0, 'weighted' => 0.0]];
foreach ($sections as $sectionKey => $sectionLabel) {
    $sectionRows = $entries[$sectionKey] ?? [];
    if (!is_array($sectionRows)) {
        continue;
    }
    foreach ($sectionRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $persons = (int) ($row['persons_trained'] ?? 0);
        $length = (string) ($row['training_length'] ?? '');
        $weighted = (float) ($row['persons_trained_weighted'] ?? 0);
        if ($weighted <= 0 && $persons > 0 && $length !== '') {
            $weighted = trainings_conducted_weighted_persons($persons, $length);
        }
        $sectionTotals[$sectionKey]['persons'] += $persons;
        $sectionTotals[$sectionKey]['weighted'] += $weighted;
    }
}
$grandPersons = $sectionTotals['need_based']['persons'] + $sectionTotals['other']['persons'];
$grandWeighted = round($sectionTotals['need_based']['weighted'] + $sectionTotals['other']['weighted'], 2);

$renderRow = static function (string $sectionKey, int $index, array $row, bool $readOnly) use ($trainingLengthOptions): void {
    $namePrefix = $readOnly ? '' : 'entries[' . $sectionKey . '][' . $index . ']';
    $personsTrained = (int) ($row['persons_trained'] ?? 0);
    $trainingLength = (string) ($row['training_length'] ?? '');
    $weighted = (string) ($row['persons_trained_weighted'] ?? '');
    if ($weighted === '' && $personsTrained > 0 && $trainingLength !== '') {
        $weighted = (string) trainings_conducted_weighted_persons($personsTrained, $trainingLength);
    }
    ?>
    <tr class="trainings-conducted-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[extension_program]' ?>"><?= htmlspecialchars((string) ($row['extension_program'] ?? '')) ?></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[training_title]' ?>"><?= htmlspecialchars((string) ($row['training_title'] ?? '')) ?></textarea></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[venue]' ?>" value="<?= htmlspecialchars((string) ($row['venue'] ?? '')) ?>"></td>
        <td><input type="date" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[training_date]' ?>" value="<?= htmlspecialchars((string) ($row['training_date'] ?? '')) ?>"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[participant_type]' ?>" value="<?= htmlspecialchars((string) ($row['participant_type'] ?? '')) ?>" placeholder="e.g. Farmers"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-persons-trained tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[persons_trained]' ?>" value="<?= htmlspecialchars((string) ($row['persons_trained'] ?? '')) ?>"></td>
        <td class="tc-col-weighted">
            <?php if ($readOnly): ?>
                <span class="tc-readonly-value"><?= htmlspecialchars($weighted !== '' ? $weighted : '-') ?></span>
            <?php else: ?>
                <div class="tc-cell-stack">
                    <select class="tc-training-length" name="<?= $namePrefix ?>[training_length]" aria-label="Training length for weighted total">
                        <option value="">Select length</option>
                        <?php foreach ($trainingLengthOptions as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>"<?= $trainingLength === $value ? ' selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" class="tc-weighted-value" name="<?= $namePrefix ?>[persons_trained_weighted]" value="<?= htmlspecialchars($weighted) ?>">
                    <div class="tc-weighted-display" aria-live="polite"><?= $weighted !== '' ? 'Weighted: ' . htmlspecialchars($weighted) : '' ?></div>
                </div>
            <?php endif; ?>
        </td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[personnel_name]' ?>" value="<?= htmlspecialchars((string) ($row['personnel_name'] ?? '')) ?>"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[personnel_role]' ?>" value="<?= htmlspecialchars((string) ($row['personnel_role'] ?? '')) ?>" placeholder="e.g. Facilitator"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[budget_source]' ?>" value="<?= htmlspecialchars((string) ($row['budget_source'] ?? '')) ?>"></td>
        <td><input type="text" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[budget_amount]' ?>" value="<?= htmlspecialchars((string) ($row['budget_amount'] ?? '')) ?>"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[trainees_surveyed]' ?>" value="<?= htmlspecialchars((string) ($row['trainees_surveyed'] ?? '')) ?>"></td>
        <?php foreach (['quality_poor', 'quality_fair', 'quality_good', 'quality_better', 'quality_best'] as $field): ?>
            <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[' . $field . ']' ?>" value="<?= htmlspecialchars((string) ($row[$field] ?? '')) ?>"></td>
        <?php endforeach; ?>
        <?php foreach (['timeliness_poor', 'timeliness_fair', 'timeliness_good', 'timeliness_better', 'timeliness_best'] as $field): ?>
            <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" <?= $readOnly ? 'readonly' : '' ?> name="<?= $readOnly ? '' : $namePrefix . '[' . $field . ']' ?>" value="<?= htmlspecialchars((string) ($row[$field] ?? '')) ?>"></td>
        <?php endforeach; ?>
        <?php if (!$readOnly): ?>
            <td class="completed-researches-actions-col">
                <button type="button" class="btn btn-sm btn-outline trainings-conducted-remove-row" title="Remove row">Remove</button>
            </td>
        <?php endif; ?>
    </tr>
    <?php
};
?>
<section class="proposal-section trainings-conducted-section eso-trainings-section">
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table">
            <colgroup>
                <col class="tc-col-program">
                <col class="tc-col-title">
                <col class="tc-col-venue">
                <col class="tc-col-date">
                <col class="tc-col-participants">
                <col class="tc-col-count">
                <col class="tc-col-weighted">
                <col class="tc-col-personnel">
                <col class="tc-col-personnel">
                <col class="tc-col-budget">
                <col class="tc-col-budget">
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
                    <th rowspan="2" scope="col" class="tc-col-title">Title of Training <span class="tc-footnote">(1)</span></th>
                    <th rowspan="2" scope="col" class="tc-col-venue">Venue</th>
                    <th rowspan="2" scope="col" class="tc-col-date">Date</th>
                    <th rowspan="2" scope="col" class="tc-col-participants">Type of Participants <span class="tc-footnote">(2)</span></th>
                    <th rowspan="2" scope="col" class="tc-col-count">No. of Persons Trained</th>
                    <th rowspan="2" scope="col" class="tc-col-weighted">Weighted by Length of Training <span class="tc-footnote">(3)</span></th>
                    <th colspan="2" scope="colgroup">Personnel Involved</th>
                    <th colspan="2" scope="colgroup">Budget</th>
                    <th rowspan="2" scope="col" class="tc-col-count">No. of Trainees Surveyed</th>
                    <th colspan="5" scope="colgroup">Trainees Rating Quality</th>
                    <th colspan="5" scope="colgroup">Trainees Rating Timeliness</th>
                    <?php if (!$readOnly): ?>
                        <th rowspan="2" scope="col" class="tc-col-actions completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th scope="col" class="tc-col-personnel">Name</th>
                    <th scope="col" class="tc-col-personnel">Role <span class="tc-footnote">(4)</span></th>
                    <th scope="col" class="tc-col-budget">Source <span class="tc-footnote">(5)</span></th>
                    <th scope="col" class="tc-col-budget">Amount</th>
                    <?php foreach ($ratingHeaders as [$label, $num]): ?>
                        <th scope="col" class="tc-col-rating"><span class="tc-rating-label"><?= htmlspecialchars($label) ?></span><span class="tc-rating-num"><?= htmlspecialchars($num) ?></span></th>
                    <?php endforeach; ?>
                    <?php foreach ($ratingHeaders as [$label, $num]): ?>
                        <th scope="col" class="tc-col-rating"><span class="tc-rating-label"><?= htmlspecialchars($label) ?></span><span class="tc-rating-num"><?= htmlspecialchars($num) ?></span></th>
                    <?php endforeach; ?>
                </tr>
            </thead>            <tbody>
                <?php foreach ($sections as $sectionKey => $sectionLabel): ?>
                    <?php
                    $rows = $entries[$sectionKey] ?? [];
                    if (!is_array($rows) || $rows === []) {
                        $rows = $readOnly ? [] : [[], [], []];
                    }
                    ?>
                    <tr class="trainings-conducted-section-row">
                        <td colspan="<?= $colCount ?>"><strong><?= htmlspecialchars($sectionLabel) ?></strong></td>
                    </tr>
                    <?php foreach ($rows as $index => $row): ?>
                        <?php $renderRow($sectionKey, (int) $index, is_array($row) ? $row : [], $readOnly); ?>
                    <?php endforeach; ?>
                    <tr class="trainings-conducted-total-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
                        <td colspan="5" class="tc-total-label"><strong>Total No. of Persons Trained</strong></td>
                        <td class="tc-total-persons tc-col-count"><?= (int) $sectionTotals[$sectionKey]['persons'] ?></td>
                        <td class="tc-total-weighted tc-col-weighted"><?= htmlspecialchars((string) round($sectionTotals[$sectionKey]['weighted'], 2)) ?></td>
                        <td colspan="<?= $readOnly ? 15 : 16 ?>" class="tc-total-spacer"></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="trainings-conducted-grand-total-row">
                    <td colspan="5" class="tc-total-label"><strong>Grand Total no. Persons Trained</strong></td>
                    <td class="tc-grand-total-persons tc-col-count"><?= (int) $grandPersons ?></td>
                    <td class="tc-grand-total-weighted tc-col-weighted"><?= htmlspecialchars((string) $grandWeighted) ?></td>
                    <td colspan="<?= $readOnly ? 15 : 16 ?>" class="tc-total-spacer"></td>
                </tr>            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="trainings-conducted-add-buttons">
            <button type="button" class="btn btn-outline trainings-conducted-add-row" data-section="need_based">Add Row (Need-based)</button>
            <button type="button" class="btn btn-outline trainings-conducted-add-row" data-section="other">Add Row (Other)</button>
        </div>
        <p class="proposal-section-note">
            <span class="tc-footnote">(3)</span> Weighted value = No. of Persons Trained × weight by training length
            (&lt; 8 h = 0.5; 8 h or 1 day = 1; 2 days = 1.25; 3-4 days = 1.5; 5+ days = 2).
        </p>
    <?php endif; ?>
</section>
