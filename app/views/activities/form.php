<?php
/** @var string $type */
/** @var array<string, mixed> $definition */
/** @var array<string, mixed>|null $entry */
/** @var string $section */
/** @var array<string, mixed> $values */
/** @var list<string> $errors */
/** @var array<string, array<string, string>> $checklist group => [category => label] */
/** @var array<string, list<array<string, mixed>>> $documents category => files */

$isEdit = $entry !== null;
$office = \App\Support\ExtensionActivityTypes::officeOf($type);
$basePath = \App\Support\ExtensionActivityTypes::basePath($office);
$terms = \App\Support\ExtensionActivityTypes::officeTerms($office);
$pageHeading = ($isEdit ? 'Edit ' : 'Add ') . $definition['label'];
$pageSubtitle = (string) $definition['description'] . ' ' . ucfirst($terms['entries']) . ' are compiled into the ' . $definition['label'] . ' quarterly report.';
$action = $isEdit ? base_url($basePath . '/' . (int) $entry['id']) : base_url($basePath . '/new/' . $definition['slug']);
$sections = $definition['sections'];
$hasSections = count($sections) > 1;

// Consecutive fields sharing a group (e.g. "Budget": Source, Amount) render together.
$blocks = [];
foreach ($definition['fields'] as $name => $field) {
    $group = (string) ($field['group'] ?? '');
    $last = array_key_last($blocks);
    if ($group !== '' && $last !== null && $blocks[$last]['group'] === $group) {
        $blocks[$last]['fields'][$name] = $field;
        continue;
    }
    $blocks[] = ['group' => $group, 'fields' => [$name => $field]];
}

$renderField = static function (string $name, array $field, string $value, string $section): void {
    $id = 'activity-field-' . $name;
    $only = $field['only'] ?? null;
    $hidden = $only !== null && !in_array($section, $only, true);
    $required = !empty($field['required']);
    $common = 'id="' . $id . '" name="fields[' . htmlspecialchars($name) . ']"'
        . ($required ? ' required' : '')
        . ($hidden ? ' disabled' : '');
    ?>
    <div class="webform-field activity-field activity-field--<?= htmlspecialchars((string) $field['type']) ?><?= $field['type'] === 'textarea' ? ' webform-field--full' : '' ?>"
        <?= $only !== null ? 'data-only="' . htmlspecialchars(implode(' ', $only)) . '"' : '' ?>
        <?= $hidden ? 'hidden' : '' ?>>
        <label for="<?= $id ?>"><?= htmlspecialchars((string) $field['label']) ?><?= $required ? ' <span class="webform-req">*</span>' : '' ?></label>
        <?php if ($field['type'] === 'textarea'): ?>
            <textarea <?= $common ?> rows="2" data-autoresize><?= htmlspecialchars($value) ?></textarea>
        <?php elseif ($field['type'] === 'select'): ?>
            <select <?= $common ?>>
                <option value="">Select</option>
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <option value="<?= htmlspecialchars((string) $optionValue) ?>"<?= $value === (string) $optionValue ? ' selected' : '' ?>><?= htmlspecialchars((string) $optionLabel) ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($field['type'] === 'number'): ?>
            <input type="number" min="0" step="1" <?= $common ?> value="<?= htmlspecialchars($value) ?>">
        <?php else: ?>
            <input type="<?= $field['type'] === 'date' ? 'date' : 'text' ?>" <?= $common ?> value="<?= htmlspecialchars($value) ?>"
                <?= isset($field['placeholder']) ? 'placeholder="' . htmlspecialchars((string) $field['placeholder']) . '"' : '' ?>>
        <?php endif; ?>
        <?php if (isset($field['hint'])): ?>
            <small class="webform-hint activity-field-hint"><?= htmlspecialchars((string) $field['hint']) ?></small>
        <?php endif; ?>
    </div>
    <?php
};
?>

<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url($basePath . '?type=' . $definition['slug']) ?>">Back to <?= htmlspecialchars($terms['page']) ?></a>
</div>

<?php if ($errors !== []): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="webform activity-form" method="post" action="<?= $action ?>"<?= $checklist !== [] ? ' enctype="multipart/form-data"' : '' ?>>
    <?= csrf_field() ?>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>Details</h2>
                <p>Fields marked <span class="webform-req">*</span> are required.</p>
            </div>
        </header>

    <?php if ($hasSections): ?>
        <fieldset class="activity-field-group">
            <legend>Category <span class="webform-req">*</span></legend>
            <div class="activity-section-options">
                <?php foreach ($sections as $key => $sectionDef): ?>
                    <label class="activity-section-option">
                        <input type="radio" name="section" value="<?= htmlspecialchars((string) $key) ?>"<?= $key === $section ? ' checked' : '' ?>>
                        <?= htmlspecialchars((string) $sectionDef['label']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
    <?php else: ?>
        <input type="hidden" name="section" value="<?= htmlspecialchars($section) ?>">
    <?php endif; ?>

    <div class="webform-grid">
    <?php foreach ($blocks as $block): ?>
        <?php if ($block['group'] !== ''): ?>
            <fieldset class="activity-field-group webform-field--full">
                <legend><?= htmlspecialchars($block['group']) ?></legend>
                <div class="activity-field-row">
                    <?php foreach ($block['fields'] as $name => $field): ?>
                        <?php $renderField($name, $field, (string) ($values[$name] ?? ''), $section); ?>
                    <?php endforeach; ?>
                </div>
            </fieldset>
        <?php else: ?>
            <?php foreach ($block['fields'] as $name => $field): ?>
                <?php $renderField($name, $field, (string) ($values[$name] ?? ''), $section); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
    </section>

    <?php if ($checklist !== []): ?>
        <section class="webform-card">
            <header class="webform-card-head">
                <span class="webform-card-num">2</span>
                <div>
                    <h2>Documentary Evidence</h2>
                    <p>Upload the supporting files for this <?= htmlspecialchars($terms['entry']) ?>.</p>
                </div>
            </header>
            <?php require APP_PATH . '/views/activities/_document-checklist.php'; ?>
        </section>
    <?php endif; ?>

    <div class="webform-actionbar">
        <a class="btn btn-outline" href="<?= base_url($basePath . '?type=' . $definition['slug']) ?>">Cancel</a>
        <?php if (!$isEdit): ?>
            <button type="submit" class="btn btn-outline" name="save_and_add" value="1">Save and Add Another</button>
        <?php endif; ?>
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save ' . ucfirst($terms['entry']) ?></button>
    </div>
</form>

<?php if ($checklist !== [] && $entry !== null): ?>
<form method="post" id="activity-doc-remove-form" hidden><?= csrf_field() ?></form>
<script>
(() => {
    const removeForm = document.getElementById('activity-doc-remove-form');
    document.querySelectorAll('.activity-doc-remove').forEach((button) => {
        button.addEventListener('click', () => {
            if (confirm('Remove this file?')) {
                removeForm.action = button.dataset.removeUrl;
                removeForm.submit();
            }
        });
    });
})();
</script>
<?php endif; ?>

<?php if ($hasSections): ?>
<script>
(() => {
    const form = document.querySelector('.activity-form');
    const apply = () => {
        const section = form.querySelector('input[name="section"]:checked')?.value ?? '';
        form.querySelectorAll('[data-only]').forEach((wrap) => {
            const show = wrap.dataset.only.split(' ').includes(section);
            wrap.hidden = !show;
            wrap.querySelectorAll('input, select, textarea').forEach((input) => { input.disabled = !show; });
        });
    };
    form.querySelectorAll('input[name="section"]').forEach((radio) => radio.addEventListener('change', apply));
    apply();
})();
</script>
<?php endif; ?>
