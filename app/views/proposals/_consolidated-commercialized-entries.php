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
        'researchers' => '',
        'date_started' => '',
        'date_completed' => '',
        'product_name' => '',
        'adopter' => '',
        'date_adopted' => '',
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
<section class="proposal-section completed-researches-section commercialized-section trainings-conducted-section" data-funding-section="<?= htmlspecialchars($sectionKey) ?>">
    <h3 class="proposal-subtitle"><?= htmlspecialchars($sectionLabel) ?></h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap commercialized-table-wrap">
        <table class="proposal-table commercialized-table trainings-conducted-table">
            <colgroup>
                <col class="comm-col-title">
                <col class="comm-col-researchers">
                <col class="comm-col-date">
                <col class="comm-col-date">
                <col class="comm-col-product">
                <col class="comm-col-adopter">
                <col class="comm-col-date">
                <col class="comm-col-college">
                <col class="comm-col-campus">
                <col class="comm-col-drive-link">
                <?php if (!$readOnly): ?>
                    <col class="comm-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th>Research Title</th>
                    <th>Researcher(s)</th>
                    <th>Date Started<span class="tc-footnote">mm/dd/yyyy</span></th>
                    <th>Date Completed<span class="tc-footnote">mm/dd/yyyy</span></th>
                    <th>Product Name<span class="tc-footnote">Methods / Process / Technology</span></th>
                    <th>Adopter<span class="tc-footnote">Industry Beneficiary</span></th>
                    <th>Date Adopted<span class="tc-footnote">mm/dd/yyyy</span></th>
                    <th>College</th>
                    <th>Campus</th>
                    <th>Google Drive Link</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col"></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="consolidated-commercialized-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="consolidated-commercialized-row">
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'research_title') !== '' ? $entryValue($row, 'research_title') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][research_title]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Research title"><?= htmlspecialchars($entryValue($row, 'research_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'researchers') !== '' ? $entryValue($row, 'researchers') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][researchers]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Researcher(s)"><?= htmlspecialchars($entryValue($row, 'researchers')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_started') !== '' ? $entryValue($row, 'date_started') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_started]" value="<?= htmlspecialchars($entryValue($row, 'date_started')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_completed') !== '' ? $entryValue($row, 'date_completed') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_completed]" value="<?= htmlspecialchars($entryValue($row, 'date_completed')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'product_name') !== '' ? $entryValue($row, 'product_name') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][product_name]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Product name"><?= htmlspecialchars($entryValue($row, 'product_name')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'adopter') !== '' ? $entryValue($row, 'adopter') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][adopter]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Adopter / beneficiary"><?= htmlspecialchars($entryValue($row, 'adopter')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'date_adopted') !== '' ? $entryValue($row, 'date_adopted') : '-') ?>
                            <?php else: ?>
                                <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_adopted]" value="<?= htmlspecialchars($entryValue($row, 'date_adopted')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'college') !== '' ? $entryValue($row, 'college') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][college]" value="<?= htmlspecialchars($entryValue($row, 'college')) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'campus') !== '' ? $entryValue($row, 'campus') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][campus]" value="<?= htmlspecialchars($entryValue($row, 'campus')) ?>">
                            <?php endif; ?>
                        </td>
                        <td class="comm-col-drive-link-cell">
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
                                <button type="button" class="btn btn-sm btn-outline consolidated-commercialized-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-sm btn-outline consolidated-commercialized-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
                Add commercialized entry
            </button>
        </div>
    <?php endif; ?>
</section>
