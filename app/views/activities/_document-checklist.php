<?php
/**
 * Upload table for an activity's required documents (a training's ETR, a linkage's MOA, ...).
 * Rendered inside the activity form; remove buttons post through a separate form (see activities/form.php).
 */
/** @var array<string, array<string, string>> $checklist */
/** @var array<string, list<array<string, mixed>>> $documents */
/** @var array<string, mixed>|null $entry */
/** @var string $type */

use App\Models\ExtensionActivityDocument;

$entryId = $entry !== null ? (int) $entry['id'] : 0;
$basePath = \App\Support\ExtensionActivityTypes::basePath(\App\Support\ExtensionActivityTypes::officeOf($type));
$entryWord = \App\Support\ExtensionActivityTypes::officeTerms(\App\Support\ExtensionActivityTypes::officeOf($type))['entry'];
$total = 0;
$filled = 0;
foreach ($checklist as $group => $items) {
    if ($group === \App\Support\ExtensionActivityTypes::OPTIONAL_GROUP) {
        continue;
    }
    foreach (array_keys($items) as $category) {
        $total++;
        if (!empty($documents[$category])) {
            $filled++;
        }
    }
}
$accept = ExtensionActivityDocument::acceptAttribute();
[, $documentsLong] = \App\Support\ExtensionActivityTypes::documentsName($type);
$multiItem = $total > 1;
?>
<section class="activity-documents">
    <div class="activity-documents-head">
        <h3><?= htmlspecialchars($documentsLong) ?><?= $multiItem && $entryWord === 'activity' ? ' documents' : '' ?></h3>
        <span class="badge<?= $filled === $total ? ' badge-approved' : ' badge-draft' ?>"><?= $multiItem ? $filled . ' of ' . $total . ' items' : ($filled > 0 ? 'Attached' : 'Missing') ?></span>
    </div>
    <p class="muted">
        <?php if ($multiItem): ?>
            Attach the documents in the <?= htmlspecialchars($documentsLong) ?> checklist. You can add several files per item
            (e.g. the <?= $entryWord ?> pictures) and come back later to add the rest.
        <?php else: ?>
            Attach the <?= htmlspecialchars($documentsLong) ?> for this <?= $entryWord ?>. You can add it later.
        <?php endif; ?>
        PDF, Word, Excel, PowerPoint, JPG, PNG or ZIP, up to 10 MB each.
        <?php if (in_array($type, ['trainings_conducted', 'outreach_activities', 'technical_advisory'], true)): ?>
            The Accomplishment Report<?= $type === 'outreach_activities' ? ' and Attendance Sheet templates are' : ' template is' ?> under Downloadable Forms.
        <?php endif; ?>
        <?php if (isset($checklist[\App\Support\ExtensionActivityTypes::OPTIONAL_GROUP])): ?>
            Optional items are attached only when they apply.
        <?php endif; ?>
    </p>

    <?php foreach ($checklist as $group => $items): ?>
        <?php $optionalGroup = $group === \App\Support\ExtensionActivityTypes::OPTIONAL_GROUP; ?>
        <div class="data-table-wrap">
            <table class="data-table activity-documents-table">
                <thead>
                    <tr>
                        <th><?= htmlspecialchars($group) ?></th>
                        <th>Uploaded</th>
                        <th>Add file(s)</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $category => $label): ?>
                    <?php $files = $documents[$category] ?? []; ?>
                    <tr>
                        <td>
                            <i class="<?= $files !== [] ? 'fas fa-check-circle activity-doc-done' : ($optionalGroup ? 'far fa-circle activity-doc-missing' : 'fas fa-circle activity-doc-missing') ?>" aria-hidden="true"></i>
                            <?= htmlspecialchars($label) ?>
                        </td>
                        <td>
                            <?php if ($files === []): ?>
                                <span class="muted">None yet</span>
                            <?php endif; ?>
                            <?php foreach ($files as $file): ?>
                                <div class="activity-doc-file">
                                    <a href="<?= base_url($basePath . '/' . $entryId . '/documents/' . (int) $file['id']) ?>" target="_blank" rel="noopener">
                                        <?= htmlspecialchars((string) $file['original_name']) ?>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline activity-doc-remove"
                                        data-remove-url="<?= base_url($basePath . '/' . $entryId . '/documents/' . (int) $file['id'] . '/delete') ?>">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <input type="file" name="documents[<?= htmlspecialchars($category) ?>][]" multiple accept="<?= htmlspecialchars($accept) ?>"
                                aria-label="Add file for <?= htmlspecialchars($label) ?>">
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</section>
