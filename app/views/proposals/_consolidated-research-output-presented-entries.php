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
        'researchers' => '',
        'paper_title' => '',
        'presenter_name' => '',
        'conference_title' => '',
        'venue' => '',
        'presentation_date' => '',
        'organizer' => '',
        'conference_type' => '',
        'presentation_type' => '',
        'award_received' => '',
        'college' => '',
        'campus' => '',
        'google_drive_link' => '',
    ]];
}

$entryValue = static function (array $row, string $key): string {
    $value = $row[$key] ?? '';
    return is_string($value) ? $value : '';
};

$conferenceTypeLabel = static function (string $value): string {
    return match ($value) {
        'local' => 'Local',
        'regional' => 'Regional',
        'national' => 'National',
        'international' => 'International',
        default => $value,
    };
};

$presentationTypeLabel = static function (string $value): string {
    return match ($value) {
        'oral' => 'Oral',
        'poster' => 'Poster',
        default => $value,
    };
};
?>
<section class="proposal-section completed-researches-section consolidated-research-output-presented-section trainings-conducted-section" data-funding-section="<?= htmlspecialchars($sectionKey) ?>">
    <h3 class="proposal-subtitle"><?= htmlspecialchars($sectionLabel) ?></h3>
    <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
    <div class="proposal-table-wrap trainings-conducted-table-wrap research-output-presented-table-wrap">
        <table class="proposal-table completed-researches-table research-output-presented-table trainings-conducted-table">
            <colgroup>
                <col class="ropr-col-title">
                <col class="ropr-col-date">
                <col class="ropr-col-date">
                <col class="ropr-col-duration">
                <col class="ropr-col-researchers">
                <col class="ropr-col-paper">
                <col class="ropr-col-presenter">
                <col class="ropr-col-conference">
                <col class="ropr-col-venue">
                <col class="ropr-col-date">
                <col class="ropr-col-organizer">
                <col class="ropr-col-conf-type">
                <col class="ropr-col-pres-type">
                <col class="ropr-col-award">
                <col class="ropr-col-college">
                <col class="ropr-col-campus">
                <col class="ropr-col-drive-link">
                <?php if (!$readOnly): ?>
                    <col class="ropr-col-actions">
                <?php endif; ?>
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2">Research Title</th>
                    <th colspan="2">Period Covered</th>
                    <th rowspan="2">Duration in Months</th>
                    <th rowspan="2">Researcher(s)</th>
                    <th rowspan="2">Title of Research Paper</th>
                    <th rowspan="2">Name of Presenter</th>
                    <th rowspan="2">Title of Conference/Forum</th>
                    <th rowspan="2">Venue</th>
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Organizer</th>
                    <th rowspan="2">Type of Conference</th>
                    <th rowspan="2">Type of Presentation</th>
                    <th rowspan="2">Award Received</th>
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
                </tr>
            </thead>
            <tbody class="consolidated-research-output-presented-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">
                <?php foreach ($rows as $index => $row): ?>
                    <tr class="consolidated-research-output-presented-row">
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
                                <?= nl2br(htmlspecialchars($entryValue($row, 'researchers') !== '' ? $entryValue($row, 'researchers') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][researchers]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Researcher(s)"
                                ><?= htmlspecialchars($entryValue($row, 'researchers')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'paper_title') !== '' ? $entryValue($row, 'paper_title') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][paper_title]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Title of research paper"
                                ><?= htmlspecialchars($entryValue($row, 'paper_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'presenter_name') !== '' ? $entryValue($row, 'presenter_name') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][presenter_name]"
                                    value="<?= htmlspecialchars($entryValue($row, 'presenter_name')) ?>"
                                    placeholder="Name of presenter"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= nl2br(htmlspecialchars($entryValue($row, 'conference_title') !== '' ? $entryValue($row, 'conference_title') : '-')) ?>
                            <?php else: ?>
                                <textarea
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][conference_title]"
                                    class="proposal-textarea proposal-textarea-compact"
                                    rows="2"
                                    placeholder="Title of conference/forum"
                                ><?= htmlspecialchars($entryValue($row, 'conference_title')) ?></textarea>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'venue') !== '' ? $entryValue($row, 'venue') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    data-location-picker="world"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][venue]"
                                    value="<?= htmlspecialchars($entryValue($row, 'venue')) ?>"
                                    placeholder="Venue"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'presentation_date') !== '' ? $entryValue($row, 'presentation_date') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="date"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][presentation_date]"
                                    value="<?= htmlspecialchars($entryValue($row, 'presentation_date')) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'organizer') !== '' ? $entryValue($row, 'organizer') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][organizer]"
                                    value="<?= htmlspecialchars($entryValue($row, 'organizer')) ?>"
                                    placeholder="Organizer"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?php
                                $conferenceType = $entryValue($row, 'conference_type');
                                echo htmlspecialchars($conferenceType !== '' ? $conferenceTypeLabel($conferenceType) : '-');
                                ?>
                            <?php else: ?>
                                <select name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][conference_type]">
                                    <option value="">Select type</option>
                                    <option value="local"<?= $entryValue($row, 'conference_type') === 'local' ? ' selected' : '' ?>>Local</option>
                                    <option value="regional"<?= $entryValue($row, 'conference_type') === 'regional' ? ' selected' : '' ?>>Regional</option>
                                    <option value="national"<?= $entryValue($row, 'conference_type') === 'national' ? ' selected' : '' ?>>National</option>
                                    <option value="international"<?= $entryValue($row, 'conference_type') === 'international' ? ' selected' : '' ?>>International</option>
                                </select>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?php
                                $presentationType = $entryValue($row, 'presentation_type');
                                echo htmlspecialchars($presentationType !== '' ? $presentationTypeLabel($presentationType) : '-');
                                ?>
                            <?php else: ?>
                                <select name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][presentation_type]">
                                    <option value="">Select type</option>
                                    <option value="oral"<?= $entryValue($row, 'presentation_type') === 'oral' ? ' selected' : '' ?>>Oral</option>
                                    <option value="poster"<?= $entryValue($row, 'presentation_type') === 'poster' ? ' selected' : '' ?>>Poster</option>
                                </select>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'award_received') !== '' ? $entryValue($row, 'award_received') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][award_received]"
                                    value="<?= htmlspecialchars($entryValue($row, 'award_received')) ?>"
                                    placeholder="Award received"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'college') !== '' ? $entryValue($row, 'college') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][college]"
                                    value="<?= htmlspecialchars($entryValue($row, 'college')) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($readOnly): ?>
                                <?= htmlspecialchars($entryValue($row, 'campus') !== '' ? $entryValue($row, 'campus') : '-') ?>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= (int) $index ?>][campus]"
                                    value="<?= htmlspecialchars($entryValue($row, 'campus')) ?>"
                                >
                            <?php endif; ?>
                        </td>
                        <td class="ropr-col-drive-link-cell">
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
                                <button type="button" class="btn btn-sm btn-outline consolidated-research-output-presented-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" class="btn btn-sm btn-outline consolidated-research-output-presented-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">
                Add presentation entry
            </button>
        </div>
    <?php endif; ?>
</section>
