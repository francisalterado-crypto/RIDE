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
        'date_started' => '',
        'date_completed' => '',
        'duration_months' => '',
        'authors_researchers' => '',
        'article_title' => '',
        'journal_book_title' => '',
        'volume_issue' => '',
        'pages' => '',
        'publication_year' => '',
        'type_of_publication' => '',
        'college' => '',
        'campus' => '',
    ]];
}

$entryValue = static function (array $row, string $key): string {
    $value = $row[$key] ?? '';
    return is_string($value) ? $value : '';
};
?>
<section class="proposal-section completed-researches-section research-output-published-section trainings-conducted-section" data-funding-section="<?= htmlspecialchars($sectionKey) ?>">
    <h3 class="proposal-subtitle"><?= htmlspecialchars($sectionLabel) ?></h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap research-output-published-table-wrap">
        <table class="proposal-table research-output-published-table trainings-conducted-table">
            <colgroup>
                <col class="rop-col-title">
                <col class="rop-col-date">
                <col class="rop-col-date">
                <col class="rop-col-duration">
                <col class="rop-col-authors">
                <col class="rop-col-article">
                <col class="rop-col-journal">
                <col class="rop-col-volume">
                <col class="rop-col-pages">
                <col class="rop-col-year">
                <col class="rop-col-indexing">
                <col class="cr-col-category">
                <col class="cr-col-remarks">
                <?php if (!$readOnly): ?>
                    <col class="rop-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th colspan="4">Program/Project/Study</th>
                    <th colspan="7">Research Output Published</th>
                    <th rowspan="2">College</th>
                    <th rowspan="2">Campus</th>
                    <?php if (!$readOnly): ?>
                        <th rowspan="2" class="completed-researches-actions-col"></th>
                    <?php endif; ?>
                </tr>
                <tr>
                    <th>Research Title</th>
                    <th>Date Started</th>
                    <th>Date Completed</th>
                    <th>Duration in Months</th>
                    <th>Author(s)/ Researcher(s)</th>
                    <th>Title of Article</th>
                    <th>Title of Book/ Journal</th>
                    <th>Vol. No./ Issue No.</th>
                    <th>Pages</th>
                    <th>Year Published</th>
                    <th>Type of Publication</th>
                </tr>
            </thead>
            <tbody class="consolidated-research-output-published-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="consolidated-research-output-published-row">
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'research_title') !== '' ? $entryValue($row, 'research_title') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][research_title]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Research title"><?= htmlspecialchars($entryValue($row, 'research_title')) ?></textarea>
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
                                <?= htmlspecialchars($entryValue($row, 'duration_months') !== '' ? $entryValue($row, 'duration_months') : '-') ?>
                            <?php else: ?>
                                <input type="number" min="0" step="1" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][duration_months]" value="<?= htmlspecialchars($entryValue($row, 'duration_months')) ?>" placeholder="Months">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'authors_researchers') !== '' ? $entryValue($row, 'authors_researchers') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][authors_researchers]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Author(s)/ researcher(s)"><?= htmlspecialchars($entryValue($row, 'authors_researchers')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'article_title') !== '' ? $entryValue($row, 'article_title') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][article_title]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Title of article"><?= htmlspecialchars($entryValue($row, 'article_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'journal_book_title') !== '' ? $entryValue($row, 'journal_book_title') : '-')) ?>
                            <?php else: ?>
                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][journal_book_title]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Title of journal/book"><?= htmlspecialchars($entryValue($row, 'journal_book_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'volume_issue') !== '' ? $entryValue($row, 'volume_issue') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][volume_issue]" value="<?= htmlspecialchars($entryValue($row, 'volume_issue')) ?>" placeholder="Vol./Issue">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'pages') !== '' ? $entryValue($row, 'pages') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][pages]" value="<?= htmlspecialchars($entryValue($row, 'pages')) ?>" placeholder="Pages">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'publication_year') !== '' ? $entryValue($row, 'publication_year') : '-') ?>
                            <?php else: ?>
                                <input type="number" min="1900" max="2100" step="1" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][publication_year]" value="<?= htmlspecialchars($entryValue($row, 'publication_year')) ?>" placeholder="Year">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'type_of_publication') !== '' ? $entryValue($row, 'type_of_publication') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][type_of_publication]" value="<?= htmlspecialchars($entryValue($row, 'type_of_publication')) ?>" placeholder="Type of publication">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'college') !== '' ? $entryValue($row, 'college') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][college]" value="<?= htmlspecialchars($entryValue($row, 'college')) ?>" placeholder="College">
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'campus') !== '' ? $entryValue($row, 'campus') : '-') ?>
                            <?php else: ?>
                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][campus]" value="<?= htmlspecialchars($entryValue($row, 'campus')) ?>" placeholder="Campus">
                            <?php endif; ?>
                        </td>
                        <?php if (!$readOnly): ?>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline consolidated-research-output-published-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-sm btn-outline consolidated-research-output-published-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
                Add publication entry
            </button>
        </div>
    <?php endif; ?>
</section>
