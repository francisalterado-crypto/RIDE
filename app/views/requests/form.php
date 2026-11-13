<?php
/** @var string $formType */
/** @var array<string, mixed> $definition */
/** @var array<string, mixed>|null $proposal */
/** @var array<string, mixed> $values */
/** @var list<string> $errors */
/** @var list<array<string, mixed>> $researchOptions */
/** @var array<string, mixed> $proponent */

use App\Support\ProponentProfile;

$isEdit = $proposal !== null;
$pageHeading = ($isEdit ? 'Edit ' : '') . $definition['label'];
$pageSubtitle = $definition['description'] . ' (' . $definition['code'] . ').';
$action = $isEdit ? base_url('research-requests/' . (int) $proposal['id']) : base_url('research-requests/new/' . $definition['slug']);
$previewAction = base_url('research-requests/preview/' . $definition['slug']);
$proponentName = ProponentProfile::displayName($proponent);
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$value = static function (string $name) use ($values): string {
    $v = $values[$name] ?? '';
    return is_string($v) ? $v : '';
};
$linkedId = (int) ($values['linked_proposal_id'] ?? 0);

// Long narrative answers get their own card; everything else is a detail of the request.
$narrativeNames = ['gender_development', 'brief_rationale', 'objectives', 'general_methodology', 'expected_outcomes', 'highlights_of_results'];
$detailFields = $narrativeFields = $checkFields = $rowFields = $timelineFields = [];
foreach ($definition['fields'] as $name => $field) {
    match (true) {
        $field['type'] === 'checks' => $checkFields[$name] = $field,
        $field['type'] === 'rows' => $rowFields[$name] = $field,
        $field['type'] === 'timeline' => $timelineFields[$name] = $field,
        in_array($name, $narrativeNames, true) => $narrativeFields[$name] = $field,
        default => $detailFields[$name] = $field,
    };
}

$cards = [];
if (!empty($definition['contact'])) {
    $cards['req-sec-contact'] = 'Contact Person';
}
if ($definition['research_link'] !== null) {
    $cards['req-sec-research'] = 'Research';
}
$cards['req-sec-details'] = 'Details';
foreach ($rowFields as $name => $field) {
    $cards['req-sec-' . $name] = 'Activities';
}
if ($checkFields !== []) {
    $cards['req-sec-alignment'] = 'SDGs & CHED';
}
if ($narrativeFields !== []) {
    $cards['req-sec-narrative'] = 'Narrative';
}
foreach ($timelineFields as $name => $field) {
    $cards['req-sec-' . $name] = 'Implementation Plan';
}
$cardNo = static function (string $anchor) use ($cards): int {
    return (int) array_search($anchor, array_keys($cards), true) + 1;
};

$requiredNames = \App\Support\FormPreviewRequirements::requiredRequestFields($formType);
$reqMark = static fn (string $name): string => in_array($name, $requiredNames, true) ? ' <span class="webform-req">*</span>' : '';
$renderField = static function (string $name, array $field, string $current, bool $locked = false) use ($reqMark): void {
    $id = 'req-' . $name;
    $required = !empty($field['required']);
    $mark = $reqMark($name);
    $full = in_array($field['type'], ['textarea', 'radio'], true) || $name === 'title';
    $attrs = 'id="' . $id . '" name="' . htmlspecialchars($name) . '"' . ($required ? ' required' : '')
        . (isset($field['from_research']) ? ' data-from-research="' . htmlspecialchars((string) $field['from_research']) . '"' : '');
    ?>
    <div class="webform-field<?= $full ? ' webform-field--full' : '' ?>">
        <?php if ($field['type'] === 'radio'): ?>
            <span class="webform-label"><?= htmlspecialchars((string) $field['label']) ?><?= $mark ?></span>
            <div class="proposal-check-grid">
                <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                    <label class="proposal-check-item">
                        <input type="radio" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string) $optionValue) ?>" <?= $current === (string) $optionValue ? 'checked' : '' ?><?= $required ? ' required' : '' ?>>
                        <span><?= htmlspecialchars((string) $optionLabel) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <label for="<?= $id ?>"><?= htmlspecialchars((string) $field['label']) ?><?= $mark ?></label>
            <?php if ($field['type'] === 'textarea'): ?>
                <textarea <?= $attrs ?> rows="<?= in_array($name, ['lead_researchers', 'co_researchers', 'study_origin_details'], true) ? 2 : 4 ?>" data-autoresize><?= htmlspecialchars($current) ?></textarea>
            <?php else: ?>
                <input type="<?= $field['type'] === 'date' ? 'date' : 'text' ?>" <?= $attrs ?> value="<?= htmlspecialchars($current) ?>"<?= $locked ? ' readonly' : '' ?>>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($field['hint'])): ?>
            <p class="webform-hint"><?= htmlspecialchars((string) $field['hint']) ?></p>
        <?php endif; ?>
        <?php if ($locked): ?>
            <p class="webform-hint">Taken from the approved research picked above.</p>
        <?php endif; ?>
    </div>
    <?php
};
?>

<?php if ($errors !== []): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $error): ?>
            <div><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form class="webform rdo-webform" id="request-form" data-preview-rules="<?= htmlspecialchars(\App\Support\FormPreviewRequirements::forScript(\App\Support\FormPreviewRequirements::forRequest($formType))) ?>" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="preview_proposal_id" value="<?= (int) $proposal['id'] ?>">
    <?php endif; ?>

    <div class="webform-hero">
        <div>
            <p class="webform-eyebrow">Office of Research and Development, Form <?= htmlspecialchars((string) $definition['code']) ?></p>
            <h2 class="webform-hero-title"><?= htmlspecialchars((string) $definition['label']) ?></h2>
            <p class="webform-hero-text">Fill out each section below. When every required part is filled, <strong>Print Preview</strong> downloads the filled office form (.docx) on the letterhead, ready to print.</p>
            <p class="webform-hint webform-req-legend"><span class="webform-req">*</span> Required before Print Preview.</p>
            <p class="webform-hint js-preview-hint" aria-live="polite"></p>
        </div>
        <button type="submit" class="btn btn-outline webform-preview-btn js-preview-btn" formaction="<?= $previewAction ?>" formnovalidate disabled>Print Preview</button>
    </div>

    <?php if (!empty($definition['intro'])): ?>
        <p class="webform-hint rdo-webform-intro"><?= htmlspecialchars((string) $definition['intro']) ?></p>
    <?php endif; ?>

    <nav class="webform-steps" aria-label="Form sections">
        <?php foreach ($cards as $anchor => $label): ?>
            <a href="#<?= $anchor ?>"><span><?= $cardNo($anchor) ?></span><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <?php if (!empty($definition['contact'])): ?>
        <section class="webform-card" id="req-sec-contact">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-contact') ?></span>
                <div>
                    <h2>Contact Person</h2>
                    <p>From your profile. <a href="<?= base_url('profile') ?>" target="_blank" rel="noopener">Update your profile</a> to change these, then reload this page.</p>
                </div>
            </header>
            <div class="webform-grid">
                <div class="webform-field">
                    <span class="webform-label">Program/Project Leader</span>
                    <p class="webform-readonly"><?= $proponentName !== '' ? htmlspecialchars($proponentName) : '<span class="muted">Not set in profile</span>' ?></p>
                </div>
                <div class="webform-field">
                    <span class="webform-label">Mobile Number</span>
                    <p class="webform-readonly"><?= trim((string) ($proponent['contact_number'] ?? '')) !== '' ? htmlspecialchars((string) $proponent['contact_number']) : '<span class="muted">Not set in profile</span>' ?></p>
                </div>
                <div class="webform-field">
                    <span class="webform-label">Email Address</span>
                    <p class="webform-readonly"><?= htmlspecialchars((string) ($proponent['email'] ?? '')) ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($definition['research_link'] !== null): ?>
        <section class="webform-card" id="req-sec-research">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-research') ?></span>
                <div>
                    <h2>Research</h2>
                    <p><?= $definition['research_link'] === 'required' ? 'The approved research this request is for.' : 'If this comes from one of your approved researches, pick it to fill in its details.' ?></p>
                </div>
            </header>
            <div class="webform-grid">
                <div class="webform-field webform-field--full">
                    <label for="request-linked-research">Approved Research Proposal Application<?= $definition['research_link'] === 'required' ? ' <span class="webform-req">*</span>' : '' ?></label>
                    <?php if ($researchOptions === []): ?>
                        <p class="webform-readonly muted">You have no approved Research Proposal Application yet.<?= $definition['research_link'] === 'required' ? ' This request needs one.' : ' You can fill in the details yourself.' ?></p>
                        <input type="hidden" name="linked_proposal_id" value="0">
                    <?php else: ?>
                        <select name="linked_proposal_id" id="request-linked-research"<?= $definition['research_link'] === 'required' ? ' required' : '' ?>>
                            <option value="0"><?= $definition['research_link'] === 'required' ? 'Select approved research' : 'None / not from an approved research' ?></option>
                            <?php foreach ($researchOptions as $option): ?>
                                <option value="<?= (int) $option['id'] ?>"
                                    data-control-no="<?= htmlspecialchars((string) $option['control_no']) ?>"
                                    data-title="<?= htmlspecialchars((string) $option['title']) ?>"
                                    data-lead="<?= htmlspecialchars((string) $option['lead']) ?>"
                                    data-coauthors="<?= htmlspecialchars((string) $option['coauthors']) ?>"
                                    data-funding-source="<?= htmlspecialchars((string) $option['funding_source']) ?>"
                                    data-status="<?= htmlspecialchars((string) $option['status']) ?>"
                                    <?= $linkedId === (int) $option['id'] ? 'selected' : '' ?>><?= htmlspecialchars($option['control_no'] . ' - ' . $option['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="webform-hint">Your approved Research Proposal Applications, as lead or co-author.</p>
                    <?php endif; ?>
                </div>
                <div class="webform-field">
                    <span class="webform-label">RIDE Control No.</span>
                    <p class="webform-readonly" id="request-control-no"><?= htmlspecialchars($value('ride_control_no') !== '' ? $value('ride_control_no') : '-') ?></p>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section class="webform-card" id="req-sec-details">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('req-sec-details') ?></span>
            <div>
                <h2>Details</h2>
                <p>Lead researcher defaults to you, from your profile.</p>
            </div>
        </header>
        <div class="webform-grid">
            <?php foreach ($detailFields as $name => $field): ?>
                <?php
                $current = $value($name);
                if ($current === '' && ($field['default'] ?? '') === 'proponent' && !$isEdit) {
                    $current = $proponentName;
                }
                $renderField($name, $field, $current, ($field['from_research'] ?? null) === 'title');
                ?>
            <?php endforeach; ?>
            <?php if ($value('duration_writing') !== ''): ?>
                <p class="webform-hint webform-field--full">Duration entered before the Rev.01 form: <?= htmlspecialchars($value('duration_writing')) ?>.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($rowFields as $name => $field): ?>
        <?php
        $rows = is_array($values[$name] ?? null) ? $values[$name] : [];
        if ($rows === []) {
            $rows[] = [];
        }
        ?>
        <section class="webform-card" id="req-sec-<?= htmlspecialchars($name) ?>">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-' . $name) ?></span>
                <div>
                    <h2><?= htmlspecialchars((string) $field['label']) ?><?= $reqMark($name) ?></h2>
                    <p>Add one entry for each activity.</p>
                </div>
            </header>
            <div class="webform-repeater" data-request-rows="<?= htmlspecialchars($name) ?>">
                <?php foreach ($rows as $index => $row): ?>
                    <div class="webform-item">
                        <div class="webform-item-head">
                            <span class="webform-item-label">Activity</span>
                            <button type="button" class="webform-icon-btn" data-remove-row title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                        <div class="webform-grid">
                            <?php foreach ($field['columns'] as $column => $columnLabel): ?>
                                <div class="webform-field<?= $column === 'justification' ? ' webform-field--full' : '' ?>">
                                    <label><?= htmlspecialchars((string) $columnLabel) ?></label>
                                    <textarea rows="<?= $column === 'justification' ? 3 : 1 ?>" data-autoresize data-column="<?= htmlspecialchars($column) ?>" name="<?= htmlspecialchars($name) ?>[<?= (int) $index ?>][<?= htmlspecialchars($column) ?>]"><?= htmlspecialchars((string) ($row[$column] ?? '')) ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="webform-add-btn" data-add-row="<?= htmlspecialchars($name) ?>">Add activity</button>
        </section>
    <?php endforeach; ?>

    <?php if ($checkFields !== []): ?>
        <section class="webform-card" id="req-sec-alignment">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-alignment') ?></span>
                <div>
                    <h2>SDGs &amp; CHED A.C.H.I.E.V.E.</h2>
                    <p>Tick all that apply.</p>
                </div>
            </header>
            <?php foreach ($checkFields as $name => $field): ?>
                <?php $picked = array_fill_keys(array_filter(is_array($values[$name] ?? null) ? $values[$name] : [], 'is_string'), true); ?>
                <div class="webform-subsection">
                    <h3><?= htmlspecialchars((string) $field['label']) ?><?= $reqMark($name) ?></h3>
                    <div class="proposal-check-grid">
                        <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                            <label class="proposal-check-item">
                                <input type="checkbox" name="<?= htmlspecialchars($name) ?>[]" value="<?= htmlspecialchars((string) $optionValue) ?>" <?= isset($picked[$optionValue]) ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars((string) $optionLabel) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if ($narrativeFields !== []): ?>
        <section class="webform-card" id="req-sec-narrative">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-narrative') ?></span>
                <div>
                    <h2>Narrative</h2>
                </div>
            </header>
            <div class="webform-grid">
                <?php foreach ($narrativeFields as $name => $field): ?>
                    <?php $renderField($name, $field, $value($name)); ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php foreach ($timelineFields as $name => $field): ?>
        <?php
        $plan = is_array($values[$name] ?? null) ? $values[$name] : [];
        for ($i = count($plan); $i < 6; $i++) {
            $plan[] = ['activity' => '', 'months' => []];
        }
        $yearLabel = (string) ($field['year_label'] ?? 'CY');
        ?>
        <section class="webform-card" id="req-sec-<?= htmlspecialchars($name) ?>">
            <header class="webform-card-head">
                <span class="webform-card-num"><?= $cardNo('req-sec-' . $name) ?></span>
                <div>
                    <h2><?= htmlspecialchars((string) $field['label']) ?><?= $reqMark($name) ?></h2>
                    <p>Tick the months of each activity. If the project runs past one year, start the activity with the year it falls in.</p>
                </div>
            </header>
            <div class="webform-grid">
                <div class="webform-field">
                    <label for="req-plan-from">From <?= $yearLabel ?><?= $reqMark($name) ?></label>
                    <input type="number" id="req-plan-from" name="plan_year_from" min="2000" max="2100" value="<?= htmlspecialchars($value('plan_year_from')) ?>" placeholder="e.g. 2027">
                </div>
                <div class="webform-field">
                    <label for="req-plan-to">To <?= $yearLabel ?></label>
                    <input type="number" id="req-plan-to" name="plan_year_to" min="2000" max="2100" value="<?= htmlspecialchars($value('plan_year_to')) ?>" placeholder="e.g. 2028">
                </div>
            </div>
            <div class="webform-table-wrap">
                <table class="webform-table rdo-plan-table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <?php foreach ($months as $month): ?><th><?= $month ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plan as $index => $row): ?>
                            <?php $picked = array_fill_keys(is_array($row['months'] ?? null) ? $row['months'] : [], true); ?>
                            <tr>
                                <td><textarea rows="1" data-autoresize name="<?= htmlspecialchars($name) ?>[<?= (int) $index ?>][activity]" placeholder="Activity <?= $index + 1 ?>" aria-label="Activity <?= $index + 1 ?>"><?= htmlspecialchars((string) ($row['activity'] ?? '')) ?></textarea></td>
                                <?php foreach ($months as $month): ?>
                                    <td class="rdo-plan-check"><input type="checkbox" name="<?= htmlspecialchars($name) ?>[<?= (int) $index ?>][months][]" value="<?= $month ?>" <?= isset($picked[$month]) ? 'checked' : '' ?> aria-label="Activity <?= $index + 1 ?>, <?= $month ?>"></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="webform-card">
        <?php
        $workflowProposal = $proposal ?? [
            'status' => 'draft',
            'project_type' => 'research',
            'summary' => json_encode(['form_type' => $formType]),
            'first_name' => (string) ($proponent['first_name'] ?? ''),
            'last_name' => (string) ($proponent['last_name'] ?? ''),
        ];
        $workflowSteps = proposal_workflow_steps($workflowProposal);
        require APP_PATH . '/views/proposals/_approval-workflow.php';
        ?>
    </section>

    <div class="webform-actionbar">
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . (int) $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline js-preview-btn" formaction="<?= $previewAction ?>" formnovalidate disabled>Print Preview</button>
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
    </div>
</form>

<script>
(() => {
    const form = document.getElementById('request-form');
    const resize = (textarea) => {
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    };
    const bindResize = (root) => root.querySelectorAll('textarea[data-autoresize]').forEach((textarea) => {
        resize(textarea);
        textarea.addEventListener('input', () => resize(textarea));
    });
    bindResize(form);

    const picker = document.getElementById('request-linked-research');
    const controlNo = document.getElementById('request-control-no');
    picker?.addEventListener('change', () => {
        const data = picker.options[picker.selectedIndex]?.dataset ?? {};
        controlNo.textContent = data.controlNo || '-';
        if (!data.controlNo) {
            return;
        }
        form.querySelectorAll('[data-from-research]').forEach((input) => {
            const source = input.dataset.fromResearch;
            if (source === 'title') {
                input.value = data.title;
            } else if (source === 'origin') {
                input.value = `${data.title} (RIDE Control No. ${data.controlNo})`;
            } else if (source === 'funding_source' && input.value.trim() === '') {
                input.value = data.fundingSource;
            }
            input.dispatchEvent(new Event('input'));
        });
        const lead = form.querySelector('[name="lead_researchers"]');
        if (lead) {
            lead.value = data.lead;
            lead.dispatchEvent(new Event('input'));
        }
        const co = form.querySelector('[name="co_researchers"]');
        if (co && co.value.trim() === '') {
            co.value = data.coauthors;
            co.dispatchEvent(new Event('input'));
        }
        const status = form.querySelector(`input[name="study_origin_status"][value="${data.status}"]`);
        if (status) {
            status.checked = true;
        }
    });

    // Repeaters: renumber names after adding or removing an entry.
    const renumber = (repeater) => {
        repeater.querySelectorAll('.webform-item').forEach((item, index) => {
            item.querySelectorAll('textarea[name]').forEach((textarea) => {
                textarea.name = textarea.name.replace(/\[\d+\]/, `[${index}]`);
            });
        });
    };
    form.querySelectorAll('[data-add-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const repeater = form.querySelector(`[data-request-rows="${button.dataset.addRow}"]`);
            const last = repeater?.lastElementChild;
            if (!last) {
                return;
            }
            const item = last.cloneNode(true);
            item.querySelectorAll('textarea').forEach((textarea) => { textarea.value = ''; });
            repeater.appendChild(item);
            renumber(repeater);
            bindResize(item);
        });
    });
    form.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-row]');
        if (!remove) {
            return;
        }
        const repeater = remove.closest('[data-request-rows]');
        const item = remove.closest('.webform-item');
        if (repeater.querySelectorAll('.webform-item').length > 1) {
            item.remove();
            renumber(repeater);
        } else {
            item.querySelectorAll('textarea').forEach((textarea) => { textarea.value = ''; resize(textarea); });
        }
    });
})();
</script>

<script src="<?= base_url('assets/js/form-preview-gate.js') ?>?v=<?= htmlspecialchars((string) @filemtime(BASE_PATH . '/public/assets/js/form-preview-gate.js')) ?>"></script>
