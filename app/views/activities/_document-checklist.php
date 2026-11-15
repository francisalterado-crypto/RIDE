<?php
/**
 * Supporting documents of an activity (a training's ETR, a linkage's MOA, ...), as a list of checklist items.
 * In the form ($docEditable) each item has an Attach button; remove buttons post through a separate form
 * (see activities/form.php). On the view page the same list is read-only.
 */
/** @var array<string, array<string, string>> $checklist */
/** @var array<string, list<array<string, mixed>>> $documents */
/** @var array<string, mixed>|null $entry */
/** @var string $type */
/** @var bool|null $docEditable */

use App\Models\ExtensionActivityDocument;
use App\Support\ExtensionActivityTypes;

$docEditable = $docEditable ?? true;
$entryId = $entry !== null ? (int) $entry['id'] : 0;
$office = ExtensionActivityTypes::officeOf($type);
$basePath = ExtensionActivityTypes::basePath($office);
$entryWord = ExtensionActivityTypes::officeTerms($office)['entry'];
$total = 0;
$filled = 0;
foreach ($checklist as $group => $items) {
    if ($group === ExtensionActivityTypes::OPTIONAL_GROUP) {
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
$showGroupHeadings = count($checklist) > 1;
// Blank office forms are uploaded in the portal (Admin > Form Templates) and found by their code.
$templateAudience = $office === 'rdo' ? \App\Models\FormTemplate::AUDIENCE_RESEARCH : \App\Models\FormTemplate::AUDIENCE_EXTENSION;
?>
<section class="activity-documents">
    <div class="activity-documents-head">
        <span class="doc-progress<?= $filled === $total ? ' is-complete' : '' ?>"><?= $total > 1 ? $filled . ' of ' . $total . ' required items attached' : ($filled > 0 ? 'Attached' : 'Not attached yet') ?></span>
    </div>
    <?php if ($docEditable): ?>
        <p class="muted activity-documents-intro">
            Attach the files for each item. Several files can be added to one item (for example, activity photos), and the rest can be added later.
            PDF, Word, Excel, PowerPoint, JPG, PNG or ZIP, up to 10 MB each.
        </p>
        <p class="activity-documents-links">
            <i class="fas fa-file-download" aria-hidden="true"></i>
            <span>Items that use an office form have a <strong>Download template</strong> link. All blank forms are under <a href="<?= base_url('forms') ?>">Downloadable Forms</a>.</span>
        </p>
    <?php endif; ?>

    <?php foreach ($checklist as $group => $items): ?>
        <?php $optionalGroup = $group === ExtensionActivityTypes::OPTIONAL_GROUP; ?>
        <?php if ($showGroupHeadings): ?>
            <h4 class="doc-group-title"><?= htmlspecialchars($optionalGroup ? 'Optional (attach when applicable)' : $group) ?></h4>
        <?php endif; ?>
        <ul class="doc-list">
            <?php foreach ($items as $category => $label): ?>
                <?php
                $files = $documents[$category] ?? [];
                $guide = ExtensionActivityTypes::documentGuide($category);
                $template = $guide['code'] !== '' ? \App\Models\FormTemplate::forCode($guide['code'], $templateAudience) : null;
                $inputId = 'doc-' . preg_replace('/[^a-z0-9_]/', '', $category);
                $state = $files !== [] ? 'done' : ($optionalGroup ? 'optional' : 'missing');
                ?>
                <li class="doc-item doc-item--<?= $state ?>">
                    <span class="doc-status" title="<?= $state === 'done' ? 'Attached' : ($state === 'optional' ? 'Optional' : 'Missing') ?>">
                        <i class="<?= $state === 'done' ? 'fas fa-check' : ($state === 'optional' ? 'far fa-circle' : 'fas fa-minus') ?>" aria-hidden="true"></i>
                    </span>
                    <div class="doc-body">
                        <div class="doc-title"><?= htmlspecialchars($label) ?></div>
                        <?php if ($guide['note'] !== '' || $guide['code'] !== ''): ?>
                            <div class="doc-meta">
                                <?php if ($guide['note'] !== ''): ?><span><?= htmlspecialchars($guide['note']) ?></span><?php endif; ?>
                                <?php if ($guide['code'] !== ''): ?>
                                    <span class="doc-code"><?= htmlspecialchars($guide['code']) ?></span>
                                <?php endif; ?>
                                <?php if ($template !== null): ?>
                                    <a class="doc-template" href="<?= base_url('forms/' . (int) $template['id'] . '/download') ?>"><i class="fas fa-download" aria-hidden="true"></i> Download template</a>
                                <?php elseif ($guide['code'] !== '' && $docEditable): ?>
                                    <span class="doc-template-missing">Template not uploaded yet</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($files !== []): ?>
                            <div class="doc-files">
                                <?php foreach ($files as $file): ?>
                                    <span class="doc-file">
                                        <i class="far fa-file" aria-hidden="true"></i>
                                        <a href="<?= base_url($basePath . '/' . $entryId . '/documents/' . (int) $file['id']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars((string) $file['original_name']) ?></a>
                                        <?php if ($docEditable): ?>
                                            <button type="button" class="doc-file-remove activity-doc-remove" aria-label="Remove <?= htmlspecialchars((string) $file['original_name']) ?>"
                                                data-remove-url="<?= base_url($basePath . '/' . $entryId . '/documents/' . (int) $file['id'] . '/delete') ?>"><i class="fas fa-times" aria-hidden="true"></i></button>
                                        <?php endif; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($docEditable): ?>
                            <div class="doc-picked" data-picked-for="<?= $inputId ?>" hidden></div>
                        <?php endif; ?>
                    </div>
                    <?php if ($docEditable): ?>
                        <label class="btn btn-sm btn-outline doc-attach" for="<?= $inputId ?>">
                            <i class="fas fa-paperclip" aria-hidden="true"></i> <?= $files !== [] ? 'Add more' : 'Attach' ?>
                        </label>
                        <input class="doc-input" id="<?= $inputId ?>" type="file" name="documents[<?= htmlspecialchars($category) ?>][]" multiple accept="<?= htmlspecialchars($accept) ?>"
                            aria-label="Attach files for <?= htmlspecialchars($label) ?>">
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</section>
<?php if ($docEditable): ?>
<script>
(() => {
    // Show the names of the files picked for each item until the form is saved.
    document.querySelectorAll('.doc-input').forEach((input) => {
        input.addEventListener('change', () => {
            const box = document.querySelector(`[data-picked-for="${input.id}"]`);
            if (!box) {
                return;
            }
            const names = Array.from(input.files || []).map((file) => file.name);
            box.textContent = names.length ? 'To be uploaded on save: ' + names.join(', ') : '';
            box.hidden = names.length === 0;
        });
    });
})();
</script>
<?php endif; ?>
