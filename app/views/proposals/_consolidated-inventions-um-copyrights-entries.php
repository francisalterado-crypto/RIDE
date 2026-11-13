<?php

/**

 * @var array<string, list<array<string, string>>> $entries

 * @var bool $readOnly

 */

$readOnly = $readOnly ?? false;

$entries = $entries ?? [];



$sections = [

    'inventions_patented' => ['group' => 'Inventions', 'label' => 'A. Patented'],

    'inventions_applied_for_patenting' => ['group' => null, 'label' => 'B. Applied for Patenting'],

    'inventions_not_patented_but_utilized' => ['group' => null, 'label' => 'C. Not Patented but Utilized by the Community'],

    'utility_models_registered' => ['group' => 'Utility Models', 'label' => 'A. Registered'],

    'utility_models_applied_for_registration' => ['group' => null, 'label' => 'B. Applied for Registration'],

    'copyrights' => ['group' => 'Copyrights', 'label' => null],

];



$entryValue = static function (array $row, string $key): string {

    $value = $row[$key] ?? '';

    return is_string($value) ? $value : '';

};



$columnCount = $readOnly ? 11 : 12;

?>

<section class="proposal-section trainings-conducted-section consolidated-inventions-um-copyrights-section">

    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>

    <div class="proposal-table-wrap trainings-conducted-table-wrap inventions-um-table-wrap">

        <table class="proposal-table inventions-um-table trainings-conducted-table">

            <thead>

                <tr>

                    <th>Research Title</th>

                    <th>Date Started</th>

                    <th>Date Developed/Completed</th>

                    <th>Inventor(s)/Researcher(s)</th>

                    <th>Patent Registration/Copyright Number</th>

                    <th>Date of Issue/Application</th>

                    <th>Adopter of Inventions/UM/Copyrights</th>

                    <th>Name of Commercial Product</th>

                    <th>College</th>

                    <th>Campus</th>

                    <th>Google Drive Link</th>

                    <?php if (!$readOnly): ?>

                        <th class="completed-researches-actions-col">Action</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <?php foreach ($sections as $sectionKey => $section): ?>

                <?php

                $rows = is_array($entries[$sectionKey] ?? null) ? $entries[$sectionKey] : [];

                if ($readOnly && $rows === []) {

                    continue;

                }

                if (!$readOnly && $rows === []) {

                    $rows = [[

                        'research_title' => '',

                        'date_started' => '',

                        'date_developed_completed' => '',

                        'inventors_researchers' => '',

                        'patent_registration_copyright_number' => '',

                        'date_of_issue_application' => '',

                        'adopter' => '',

                        'commercial_product_name' => '',

                        'college' => '',

                        'campus' => '',

                        'google_drive_link' => '',

                    ]];

                }

                ?>

                <tbody class="consolidated-inventions-um-copyrights-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">

                    <?php if (is_string($section['group']) && $section['group'] !== ''): ?>

                        <tr class="inventions-um-section-row"><td colspan="<?= $columnCount ?>"><?= htmlspecialchars($section['group']) ?></td></tr>

                    <?php endif; ?>

                    <?php if (is_string($section['label']) && $section['label'] !== ''): ?>

                        <tr class="inventions-um-subsection-row"><td colspan="<?= $columnCount ?>"><?= htmlspecialchars($section['label']) ?></td></tr>

                    <?php endif; ?>

                    <?php foreach ($rows as $index => $row): ?>

                        <tr class="consolidated-inventions-um-copyrights-row">

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= nl2br(htmlspecialchars($entryValue($row, 'research_title') !== '' ? $entryValue($row, 'research_title') : '-')) ?>

                                <?php else: ?>

                                    <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][research_title]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'research_title')) ?></textarea>

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

                                    <?= htmlspecialchars($entryValue($row, 'date_developed_completed') !== '' ? $entryValue($row, 'date_developed_completed') : '-') ?>

                                <?php else: ?>

                                    <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_developed_completed]" value="<?= htmlspecialchars($entryValue($row, 'date_developed_completed')) ?>">

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= nl2br(htmlspecialchars($entryValue($row, 'inventors_researchers') !== '' ? $entryValue($row, 'inventors_researchers') : '-')) ?>

                                <?php else: ?>

                                    <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][inventors_researchers]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'inventors_researchers')) ?></textarea>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= htmlspecialchars($entryValue($row, 'patent_registration_copyright_number') !== '' ? $entryValue($row, 'patent_registration_copyright_number') : '-') ?>

                                <?php else: ?>

                                    <input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][patent_registration_copyright_number]" value="<?= htmlspecialchars($entryValue($row, 'patent_registration_copyright_number')) ?>">

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= htmlspecialchars($entryValue($row, 'date_of_issue_application') !== '' ? $entryValue($row, 'date_of_issue_application') : '-') ?>

                                <?php else: ?>

                                    <input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][date_of_issue_application]" value="<?= htmlspecialchars($entryValue($row, 'date_of_issue_application')) ?>">

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= nl2br(htmlspecialchars($entryValue($row, 'adopter') !== '' ? $entryValue($row, 'adopter') : '-')) ?>

                                <?php else: ?>

                                    <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][adopter]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'adopter')) ?></textarea>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?php if ($readOnly): ?>

                                    <?= nl2br(htmlspecialchars($entryValue($row, 'commercial_product_name') !== '' ? $entryValue($row, 'commercial_product_name') : '-')) ?>

                                <?php else: ?>

                                    <textarea name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][commercial_product_name]" class="proposal-textarea proposal-textarea-compact" rows="2"><?= htmlspecialchars($entryValue($row, 'commercial_product_name')) ?></textarea>

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

                            <td class="ium-col-drive-link-cell">

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

                                    <button type="button" class="btn btn-sm btn-outline consolidated-inventions-um-copyrights-remove-row">Remove</button>

                                </td>

                            <?php endif; ?>

                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$readOnly): ?>

                        <tr>

                            <td colspan="<?= $columnCount ?>">

                                <button type="button" class="btn btn-outline consolidated-inventions-um-copyrights-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">Add Row</button>

                            </td>

                        </tr>

                    <?php endif; ?>

                </tbody>

            <?php endforeach; ?>

        </table>

    </div>

    <p class="proposal-section-note inventions-um-note">

        Note: *An invention/utility model may be utilized for: 1) development of technology, 2) service provision, or 3) an end-product in itself or it may also be commercialized for selling to other end-users.

    </p>

</section>

