<?php
/**
 * @var string $sectionKey
 * @var string $sectionLabel
 * @var list<array<string, string>> $rows
 * @var bool $readOnly
 */
$readOnly = $readOnly ?? false;
$rows = $rows ?? [];
if ($rows === []) {
    $rows = [[
        'research_title' => '',
        'researcher_names' => '',
        'date_started' => '',
        'date_completed' => '',
        'duration_months' => '',
        'budget_source' => '',
        'budget_amount' => '',
        'category' => '',
        'remarks' => '',
        'google_drive_link' => '',
    ]];
}

$entryValue = static function (array $row, string $key): string {
    $value = $row[$key] ?? '';
    return is_string($value) ? $value : '';
};
?>
<section class="proposal-section completed-researches-section trainings-conducted-section" data-funding-section="<?= htmlspecialchars($sectionKey) ?>">
    <h3 class="proposal-subtitle"><?= htmlspecialchars($sectionLabel) ?></h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap">
        <table class="proposal-table completed-researches-table trainings-conducted-table">
            <colgroup>
                <col class="cr-col-title">
                <col class="cr-col-researchers">
                <col class="cr-col-date">
                <col class="cr-col-date">
                <col class="cr-col-duration">
                <col class="cr-col-budget">
                <col class="cr-col-budget">
                <col class="cr-col-category">
                <col class="cr-col-remarks">
                <col class="cr-col-drive-link">
                <?php if (!$readOnly): ?>
                    <col class="cr-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">Research Title</th>
                    <th rowspan="2">Researcher(s) Name</th>
                    <th colspan="3">Inclusive Dates</th>
                    <th colspan="2">Budget</th>
                    <th rowspan="2">Category</th>
                    <th rowspan="2">Remarks</th>
                    <th rowspan="2">Google Drive Link</th>
                    <?php if (!$readOnly): ?>
                        <th rowspan="2" class="completed-researches-actions-col"></th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th>Month, Day, and Year Started</th>
                    <th>Month, Day and Year Completed</th>
                    <th>Duration in Months</th>
                    <th>Source</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody class="completed-researches-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="completed-researches-row">
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'research_title') !== '' ? $entryValue($row, 'research_title') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][research_title]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Research title"
                                ><?= htmlspecialchars($entryValue($row, 'research_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'researcher_names') !== '' ? $entryValue($row, 'researcher_names') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][researcher_names]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Researcher name(s)"
                                ><?= htmlspecialchars($entryValue($row, 'researcher_names')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_started') !== '' ? $entryValue($row, 'date_started') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="date"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_started]"
                                    value="<?= htmlspecialchars($entryValue($row, 'date_started')) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_completed') !== '' ? $entryValue($row, 'date_completed') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="date"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_completed]"
                                    value="<?= htmlspecialchars($entryValue($row, 'date_completed')) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'duration_months') !== '' ? $entryValue($row, 'duration_months') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="number"
                                    min="0"
                                    step="1"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][duration_months]"
                                    value="<?= htmlspecialchars($entryValue($row, 'duration_months')) ?>"
                                    placeholder="Months"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'budget_source') !== '' ? $entryValue($row, 'budget_source') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][budget_source]"
                                    value="<?= htmlspecialchars($entryValue($row, 'budget_source')) ?>"
                                    placeholder="Budget source"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'budget_amount') !== '' ? $entryValue($row, 'budget_amount') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][budget_amount]"
                                    value="<?= htmlspecialchars($entryValue($row, 'budget_amount')) ?>"
                                    placeholder="Amount"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'category') !== '' ? $entryValue($row, 'category') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][category]"
                                    value="<?= htmlspecialchars($entryValue($row, 'category')) ?>"
                                    placeholder="Category"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'remarks') !== '' ? $entryValue($row, 'remarks') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][remarks]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Remarks"
                                ><?= htmlspecialchars($entryValue($row, 'remarks')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td class="cr-col-drive-link-cell">
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
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][google_drive_link]"
                                    value="<?= htmlspecialchars($driveLink) ?>"
                                    placeholder="https://drive.google.com/..."
                                >
                            <?php endif; ?>
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline completed-researches-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-sm btn-outline completed-researches-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
                Add research entry
            </button>
        </div>
    <?php endif; ?>
</section>
