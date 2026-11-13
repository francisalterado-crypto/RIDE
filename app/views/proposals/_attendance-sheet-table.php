<?php
/** @var list<array<string, string>> $attendees */
/** @var bool $readOnly */

$sexOptions = attendance_sheet_sex_options();
$rows = is_array($attendees) ? $attendees : [];
if ($rows === []) {
    $rows = $readOnly ? [] : [[], [], [], [], [], [], [], [], [], []];
}

$cell = static function (string $value) use ($readOnly): string {
    $trimmed = trim($value);
    if ($readOnly) {
        return $trimmed === '' ? '-' : htmlspecialchars($trimmed);
    }

    return htmlspecialchars($trimmed);
};
?>
<section class="proposal-section attendance-sheet-section">
    <div class="proposal-table-wrap">
        <table class="proposal-table completed-researches-table attendance-sheet-table">
            <thead>
                <tr>
                    <th class="attendance-no-col">No</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th class="attendance-sex-col">Sex</th>
                    <th>Contact #</th>
                    <th>Office/Unit/Agency</th>
                    <th>E-mail</th>
                    <th>Signature</th>
                    <?php if (!$readOnly): ?>
                        <th class="completed-researches-actions-col">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($readOnly && $rows === []): ?>
                    <tr>
                        <td colspan="8">No attendees recorded yet.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($rows as $index => $row): ?>
                    <?php
                    $name = (string) ($row['name'] ?? '');
                    $position = (string) ($row['position'] ?? '');
                    $sex = (string) ($row['sex'] ?? '');
                    $contact = (string) ($row['contact_number'] ?? '');
                    $office = (string) ($row['office'] ?? '');
                    $email = (string) ($row['email'] ?? '');
                    $signature = (string) ($row['signature'] ?? '');
                    ?>
                    <tr class="attendance-sheet-row">
                        <td class="attendance-no-col attendance-row-number"><?= (int) $index + 1 ?></td>
                        <?php if ($readOnly): ?>
                            <td><?= $cell($name) ?></td>
                            <td><?= $cell($position) ?></td>
                            <td><?= htmlspecialchars($sexOptions[$sex] ?? ($sex !== '' ? $sex : '-')) ?></td>
                            <td><?= $cell($contact) ?></td>
                            <td><?= $cell($office) ?></td>
                            <td><?= $cell($email) ?></td>
                            <td><?= $cell($signature) ?></td>
                        <?php else: ?>
                            <td><input type="text" name="attendees[<?= (int) $index ?>][name]" value="<?= htmlspecialchars($name) ?>" placeholder="Full name"></td>
                            <td><input type="text" name="attendees[<?= (int) $index ?>][position]" value="<?= htmlspecialchars($position) ?>" placeholder="Position / designation"></td>
                            <td>
                                <select name="attendees[<?= (int) $index ?>][sex]" aria-label="Sex">
                                    <option value="">Select</option>
                                    <?php foreach ($sexOptions as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $sex === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="attendees[<?= (int) $index ?>][contact_number]" value="<?= htmlspecialchars($contact) ?>" placeholder="Mobile number"></td>
                            <td><input type="text" name="attendees[<?= (int) $index ?>][office]" value="<?= htmlspecialchars($office) ?>" placeholder="Office / unit / agency"></td>
                            <td><input type="email" name="attendees[<?= (int) $index ?>][email]" value="<?= htmlspecialchars($email) ?>" placeholder="Email address"></td>
                            <td><input type="text" name="attendees[<?= (int) $index ?>][signature]" value="<?= htmlspecialchars($signature) ?>" placeholder="Typed name"></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline attendance-sheet-remove-row" title="Remove row">Remove</button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if (!$readOnly): ?>
        <div class="proposal-coauthors-actions">
            <button type="button" class="btn btn-outline btn-sm attendance-sheet-add-row">Add attendee</button>
        </div>
        <p class="proposal-section-note">Type the attendee&apos;s name in Signature as acknowledgment of attendance. A printed copy may still be used to collect handwritten signatures.</p>
    <?php endif; ?>
</section>
