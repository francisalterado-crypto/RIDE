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

        'extension_program_activity' => '',

        'faculty_staff_involved' => '',

        'budget_source' => '',

        'budget_amount' => '',

        'venue' => '',

        'date' => '',

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

<section class="proposal-section completed-researches-section consolidated-resulted-in-extension-section trainings-conducted-section" data-funding-section="<?= htmlspecialchars($sectionKey) ?>">

    <h3 class="proposal-subtitle"><?= htmlspecialchars($sectionLabel) ?></h3>

    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>

    <div class="proposal-table-wrap trainings-conducted-table-wrap resulted-in-extension-table-wrap">

        <table class="proposal-table trainings-conducted-table resulted-in-extension-table">

            <colgroup>

                <col class="riext-col-title">

                <col class="riext-col-researchers">

                <col class="riext-col-date">

                <col class="riext-col-date">

                <col class="riext-col-extension">

                <col class="riext-col-faculty">

                <col class="riext-col-budget">

                <col class="riext-col-budget">

                <col class="riext-col-venue">

                <col class="riext-col-date">

                <col class="riext-col-college">

                <col class="riext-col-campus">

                <col class="riext-col-drive-link">

                <?php if (!$readOnly): ?>

                    <col class="riext-col-actions">

                <?php endif; ?>

            </colgroup>

            <thead>

                <tr>

                    <th rowspan="2">Research Title</th>

                    <th rowspan="2">Researcher(s)</th>

                    <th colspan="2">Period Covered</th>

                    <th rowspan="2">Title of Extension Program/Activity</th>

                    <th rowspan="2">Faculty/Staff Involved</th>

                    <th colspan="2">Budget</th>

                    <th rowspan="2">Venue</th>

                    <th rowspan="2">Date</th>

                    <th rowspan="2">College</th>

                    <th rowspan="2">Campus</th>

                    <th rowspan="2">Google Drive Link</th>

                    <?php if (!$readOnly): ?>

                        <th rowspan="2" class="completed-researches-actions-col"></th>

                    <?php endif; ?>

                </tr>

                <tr>

                    <th>Date Started</th>

                    <th>Date Completed</th>

                    <th>Source</th>

                    <th>Amount</th>

                </tr>

            </thead>

            <tbody class="consolidated-resulted-in-extension-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">

                <?php foreach ($rows as $index => $row): ?>

                    <tr class="consolidated-resulted-in-extension-row">

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

                                <?= nl2br(htmlspecialchars($entryValue($row, 'extension_program_activity') !== '' ? $entryValue($row, 'extension_program_activity') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][extension_program_activity]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Extension program/activity"><?= htmlspecialchars($entryValue($row, 'extension_program_activity')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= nl2br(htmlspecialchars($entryValue($row, 'faculty_staff_involved') !== '' ? $entryValue($row, 'faculty_staff_involved') : '-')) ?>

                            <?php else: ?>

                                <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][faculty_staff_involved]" class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Faculty/staff involved"><?= htmlspecialchars($entryValue($row, 'faculty_staff_involved')) ?></textarea>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'budget_source') !== '' ? $entryValue($row, 'budget_source') : '-') ?>

                            <?php else: ?>

                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][budget_source]" value="<?= htmlspecialchars($entryValue($row, 'budget_source')) ?>" placeholder="Budget source">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'budget_amount') !== '' ? $entryValue($row, 'budget_amount') : '-') ?>

                            <?php else: ?>

                                <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][budget_amount]" value="<?= htmlspecialchars($entryValue($row, 'budget_amount')) ?>" placeholder="Amount">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'venue') !== '' ? $entryValue($row, 'venue') : '-') ?>

                            <?php else: ?>

                                <input type="text" data-location-picker="ph" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][venue]" value="<?= htmlspecialchars($entryValue($row, 'venue')) ?>" placeholder="Venue">

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if ($readOnly): ?>

                                <?= htmlspecialchars($entryValue($row, 'date') !== '' ? $entryValue($row, 'date') : '-') ?>

                            <?php else: ?>

                                <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date]" value="<?= htmlspecialchars($entryValue($row, 'date')) ?>">

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

                        <td class="riext-col-drive-link-cell">

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

                                <button type="button" class="btn btn-sm btn-outline consolidated-resulted-in-extension-remove-row" title="Remove row">Remove</button>

                            </td>

                        <?php endif; ?>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php if (!$readOnly): ?>

        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">

            <button type="button" class="btn btn-sm btn-outline consolidated-resulted-in-extension-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">

                Add research entry

            </button>

        </div>

    <?php endif; ?>

</section>

