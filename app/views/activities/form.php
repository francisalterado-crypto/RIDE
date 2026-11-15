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

// Extension Program comes from the catalog. Text saved before it was a selection stays as its own option.
$extensionPrograms = $extensionPrograms ?? [];
$extensionProjects = $extensionProjects ?? [];
$hasProgramField = in_array('program', array_column($definition['fields'], 'type'), true);
$programText = trim((string) ($values['extension_program'] ?? ''));
$selectedProgramId = 0;
foreach ($extensionPrograms as $programRow) {
    if ($programText !== '' && strcasecmp(\App\Models\ExtensionProgram::label($programRow), $programText) === 0) {
        $selectedProgramId = (int) $programRow['id'];
        break;
    }
}
$legacyProgramText = $selectedProgramId === 0 && $programText !== '' ? $programText : '';
$programsByCollege = [];
foreach ($extensionPrograms as $programRow) {
    $programsByCollege[trim((string) ($programRow['college_name'] ?? '')) ?: 'University-wide'][] = $programRow;
}

$renderCatalogPick = static function () use ($hasProgramField, $programsByCollege, $selectedProgramId, $legacyProgramText, $extensionProjects, $selectedProjectId): void {
    if ($hasProgramField && $programsByCollege !== []): ?>
        <div class="webform-field webform-field--full">
            <label for="activity-extension-program">Extension Program</label>
            <select id="activity-extension-program" name="extension_program_id">
                <option value="0">Not under an extension program</option>
                <?php if ($legacyProgramText !== ''): ?>
                    <option value="-1" selected>Saved value: <?= htmlspecialchars($legacyProgramText) ?></option>
                <?php endif; ?>
                <?php foreach ($programsByCollege as $collegeName => $rows): ?>
                    <optgroup label="<?= htmlspecialchars($collegeName) ?>">
                        <?php foreach ($rows as $programRow): ?>
                            <option value="<?= (int) $programRow['id'] ?>"<?= (int) $programRow['id'] === $selectedProgramId ? ' selected' : '' ?>><?= htmlspecialchars(\App\Models\ExtensionProgram::label($programRow)) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <p class="webform-hint">Programs are maintained by the RIDE administrator in the program catalog.</p>
        </div>
    <?php endif;
    if ($extensionProjects !== []): ?>
        <div class="webform-field webform-field--full">
            <label for="activity-extension-project">Extension Project</label>
            <select id="activity-extension-project" name="extension_project_id">
                <option value="0">Not part of a planned project</option>
                <?php foreach ($extensionProjects as $projectRow): ?>
                    <option value="<?= (int) $projectRow['id'] ?>" data-program="<?= (int) $projectRow['extension_program_id'] ?>"<?= (int) $projectRow['id'] === (int) ($selectedProjectId ?? 0) ? ' selected' : '' ?>><?= htmlspecialchars(\App\Models\ExtensionProject::label($projectRow)) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="webform-hint"><?= $hasProgramField
                ? 'Links this activity to a planned project for monitoring. Selecting a project also sets its Extension Program.'
                : 'Links this activity to its program and project for monitoring.' ?></p>
        </div>
    <?php endif;
};

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
                <?= in_array($name, ['venue', 'location', 'adopter_location'], true) ? 'data-location-picker="ph"' : '' ?>
                <?= ($field['suggest'] ?? '') === 'people' ? 'list="activity-people-options" autocomplete="off"' : '' ?>
                <?= isset($field['placeholder']) ? 'placeholder="' . htmlspecialchars((string) $field['placeholder']) . '"' : '' ?>>
        <?php endif; ?>
        <?php if (isset($field['hint'])): ?>
            <small class="webform-hint activity-field-hint"><?= htmlspecialchars((string) $field['hint']) ?></small>
        <?php endif; ?>
    </div>
    <?php
};

// Trainee ratings (Trainings Conducted, Technical Advisory): one block with the number surveyed and a 1-5 scale
// per aspect, instead of two separate groups of five number boxes.
$ratingLevels = ['poor' => 'Poor', 'fair' => 'Fair', 'good' => 'Good', 'better' => 'Better', 'best' => 'Best'];
$ratingScales = [];
foreach (['quality' => 'Quality', 'timeliness' => 'Timeliness'] as $prefix => $scaleLabel) {
    if (isset($definition['fields'][$prefix . '_poor'])) {
        $ratingScales[$prefix] = $scaleLabel;
    }
}
$hasRatings = $ratingScales !== [] && isset($definition['fields']['trainees_surveyed']);
$isRatingField = static fn (string $name): bool => $hasRatings
    && ($name === 'trainees_surveyed' || preg_match('/^(quality|timeliness)_(poor|fair|good|better|best)$/', $name) === 1);
$renderRatings = static function () use ($ratingScales, $ratingLevels, $values, $definition): void {
    $surveyed = (string) ($values['trainees_surveyed'] ?? '');
    $isTraining = isset($definition['fields']['training_title']);
    ?>
    <fieldset class="rating-block webform-field--full" data-rating-block>
        <legend>Trainee Ratings</legend>
        <p class="webform-hint">Enter how many of the surveyed trainees gave each rating. Each row should add up to the number surveyed.</p>
        <div class="rating-surveyed">
            <label for="activity-field-trainees_surveyed">No. of Trainees Surveyed</label>
            <input type="number" min="0" step="1" id="activity-field-trainees_surveyed" name="fields[trainees_surveyed]" value="<?= htmlspecialchars($surveyed) ?>" data-rating-surveyed>
        </div>
        <?php foreach ($ratingScales as $prefix => $scaleLabel): ?>
            <div class="rating-scale" data-rating-scale>
                <div class="rating-scale-head">
                    <span class="rating-scale-label"><?= htmlspecialchars($scaleLabel) ?> of <?= $isTraining ? 'training' : 'service' ?></span>
                    <span class="rating-scale-sum" data-rating-sum aria-live="polite"></span>
                </div>
                <div class="rating-options">
                    <?php $n = 0; foreach ($ratingLevels as $level => $levelLabel): $n++; ?>
                        <?php $name = $prefix . '_' . $level; ?>
                        <label class="rating-option" for="activity-field-<?= $name ?>">
                            <span class="rating-option-head"><span class="rating-num"><?= $n ?></span> <?= $levelLabel ?></span>
                            <input type="number" min="0" step="1" inputmode="numeric" id="activity-field-<?= $name ?>" name="fields[<?= $name ?>]" value="<?= htmlspecialchars((string) ($values[$name] ?? '')) ?>" placeholder="0" data-rating-count>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </fieldset>
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
    <?php if (!$hasProgramField) {
        $renderCatalogPick();
    } ?>
    <?php if (!empty($peopleNames) && in_array('people', array_column($definition['fields'], 'suggest'), true)): ?>
        <datalist id="activity-people-options">
            <?php foreach ($peopleNames as $personName): ?>
                <option value="<?= htmlspecialchars($personName) ?>">
            <?php endforeach; ?>
        </datalist>
    <?php endif; ?>
    <?php $ratingsRendered = false; ?>
    <?php foreach ($blocks as $block): ?>
        <?php $blockIsRatings = $hasRatings && array_filter(array_keys($block['fields']), static fn (string $name): bool => !$isRatingField($name)) === []; ?>
        <?php if ($blockIsRatings): ?>
            <?php if (!$ratingsRendered) { $renderRatings(); $ratingsRendered = true; } ?>
        <?php elseif ($block['group'] !== ''): ?>
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
                <?php if ($field['type'] === 'program'): ?>
                    <?php $renderCatalogPick(); ?>
                <?php else: ?>
                    <?php $renderField($name, $field, (string) ($values[$name] ?? ''), $section); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
    </section>

    <?php if ($hasRatings): ?>
    <script>
    (() => {
        // Each rating row should add up to the number of trainees surveyed.
        const block = document.querySelector('[data-rating-block]');
        const surveyed = block?.querySelector('[data-rating-surveyed]');
        if (!block || !surveyed) {
            return;
        }
        const update = () => {
            const target = surveyed.value === '' ? null : Number(surveyed.value);
            block.querySelectorAll('[data-rating-scale]').forEach((scale) => {
                const counts = Array.from(scale.querySelectorAll('[data-rating-count]'));
                const any = counts.some((input) => input.value !== '');
                const sum = counts.reduce((total, input) => total + (Number(input.value) || 0), 0);
                const out = scale.querySelector('[data-rating-sum]');
                scale.classList.remove('is-match', 'is-mismatch');
                if (!any) {
                    out.textContent = '';
                    return;
                }
                if (target === null) {
                    out.textContent = sum + ' rated';
                    return;
                }
                const ok = sum === target;
                scale.classList.add(ok ? 'is-match' : 'is-mismatch');
                out.textContent = sum + ' of ' + target + ' rated' + (ok ? '' : (sum > target ? ' (more than surveyed)' : ' (' + (target - sum) + ' missing)'));
            });
        };
        block.addEventListener('input', update);
        update();
    })();
    </script>
    <?php endif; ?>

    <?php if ($hasProgramField && $extensionProjects !== []): ?>
    <script>
    (() => {
        // Picking a program narrows the project list; picking a project sets its program.
        const programSelect = document.getElementById('activity-extension-program');
        const projectSelect = document.getElementById('activity-extension-project');
        if (!programSelect || !projectSelect) {
            return;
        }
        const filterProjects = () => {
            const programId = programSelect.value;
            const narrow = Number(programId) > 0;
            Array.from(projectSelect.options).forEach((option) => {
                if (option.value === '0') {
                    return;
                }
                option.hidden = narrow && option.dataset.program !== programId;
                option.disabled = option.hidden;
            });
            if (projectSelect.selectedOptions[0]?.disabled) {
                projectSelect.value = '0';
            }
        };
        const syncProgramFromProject = () => {
            const programId = projectSelect.selectedOptions[0]?.dataset.program;
            if (programId && programSelect.querySelector(`option[value="${programId}"]`)) {
                programSelect.value = programId;
            }
        };
        programSelect.addEventListener('change', filterProjects);
        projectSelect.addEventListener('change', () => {
            syncProgramFromProject();
            filterProjects();
        });
        // Entries saved with a project show that project's program.
        syncProgramFromProject();
        filterProjects();
    })();
    </script>
    <?php endif; ?>

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
