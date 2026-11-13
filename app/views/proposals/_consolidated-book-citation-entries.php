<?php

/**

 * @var list<array<string, string>> $rows

 * @var bool $readOnly

 */

$readOnly = $readOnly ?? false;

$rows = $rows ?? [];

if ($rows === []) {

    $rows = [[

        'title_original_article_cited' => '',

        'authors_original_article' => '',

        'title_publication_original' => '',

        'title_new_book_chapter' => '',

        'authors_book_chapter' => '',

        'title_book_chapter_published' => '',

        'volume_issue' => '',

        'pages' => '',

        'year_publication' => '',

        'isbn' => '',

        'publisher' => '',

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

<section class="proposal-section trainings-conducted-section consolidated-book-citation-section">

    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>

    <div class="proposal-table-wrap trainings-conducted-table-wrap book-citation-table-wrap">

        <table class="proposal-table book-citation-table trainings-conducted-table">

            <thead>

                <tr>

                    <th>Title of Original Research Article/Paper Cited</th>

                    <th>Author(s) of Original Article/Paper</th>

                    <th>Title of Publication where the Original Research Article Being Cited was Published</th>

                    <th>Title of New Book Chapter where the Original Research Article/Paper was Cited</th>

                    <th>Author(s) of the Book Chapter who Cited the Original Research Article/Paper</th>

                    <th>Title of Book where the Chapter was Published</th>

                    <th>Vol. No./Issue no.</th>

                    <th>Number of Pages</th>

                    <th>Year of Publication</th>

                    <th>ISBN</th>

                    <th>Publisher</th>

                    <th>College</th>

                    <th>Campus</th>

                    <th>Google Drive Link</th>

                    <?php if (!$readOnly): ?>

                        <th class="completed-researches-actions-col">Action</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody id="consolidated-book-citation-rows" class="consolidated-book-citation-rows">

                <?php foreach ($rows as $index => $row): ?>

                    <tr class="consolidated-book-citation-row">

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'title_original_article_cited') !== '' ? $entryValue($row, 'title_original_article_cited') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][title_original_article_cited]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'title_original_article_cited')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'authors_original_article') !== '' ? $entryValue($row, 'authors_original_article') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][authors_original_article]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'authors_original_article')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'title_publication_original') !== '' ? $entryValue($row, 'title_publication_original') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][title_publication_original]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'title_publication_original')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'title_new_book_chapter') !== '' ? $entryValue($row, 'title_new_book_chapter') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][title_new_book_chapter]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'title_new_book_chapter')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'authors_book_chapter') !== '' ? $entryValue($row, 'authors_book_chapter') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][authors_book_chapter]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'authors_book_chapter')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'title_book_chapter_published') !== '' ? $entryValue($row, 'title_book_chapter_published') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][title_book_chapter_published]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'title_book_chapter_published')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'volume_issue') !== '' ? $entryValue($row, 'volume_issue') : '-') ?>

                            <?php else: ?>

                                <input type="text" name="entries[<?= (int) $index ?>][volume_issue]" value="<?= htmlspecialchars($entryValue($row, 'volume_issue')) ?>">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'pages') !== '' ? $entryValue($row, 'pages') : '-') ?>

                            <?php else: ?>

                                <input type="text" name="entries[<?= (int) $index ?>][pages]" value="<?= htmlspecialchars($entryValue($row, 'pages')) ?>">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'year_publication') !== '' ? $entryValue($row, 'year_publication') : '-') ?>

                            <?php else: ?>

                                <input type="number" min="1900" max="2100" step="1" name="entries[<?= (int) $index ?>][year_publication]" value="<?= htmlspecialchars($entryValue($row, 'year_publication')) ?>">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'isbn') !== '' ? $entryValue($row, 'isbn') : '-') ?>

                            <?php else: ?>

                                <input type="text" name="entries[<?= (int) $index ?>][isbn]" value="<?= htmlspecialchars($entryValue($row, 'isbn')) ?>">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'publisher') !== '' ? $entryValue($row, 'publisher') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= (int) $index ?>][publisher]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'publisher')) ?></textarea>

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

                        <td class="bc-col-drive-link-cell">

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

                                <button type="button" class="btn btn-sm btn-outline consolidated-book-citation-remove-row">Remove</button>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php if (!$readOnly): ?>

        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">

            <button type="button" class="btn btn-outline consolidated-book-citation-add-row">Add Row</button>

        </div>

    <?php endif; ?>

</section>

