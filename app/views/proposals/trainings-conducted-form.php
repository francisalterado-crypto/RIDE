<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */
/** @var array<string, string> $requiredFileList */
/** @var array<string, list<array<string, mixed>>> $requiredDocuments */

$isEdit = $proposal !== null;
$requiredFileList = $requiredFileList ?? [];
$requiredDocuments = $requiredDocuments ?? [];
$allowDocumentUpload = true;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Trainings Conducted — RIDE IMS';
$pageHeading = 'Trainings Conducted';
$pageSubtitle = $isEdit
    ? 'Update your quarterly trainings conducted report before saving or resubmitting.'
    : 'Quarterly report of accomplishment in Extension (WPU-QSF-RIDE-ESO-06).';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$reportAsOfValue = is_string($summaryData['report_as_of'] ?? null) ? $summaryData['report_as_of'] : '';
$collegeDisplay = is_string($summaryData['college_name'] ?? null) && $summaryData['college_name'] !== ''
    ? $summaryData['college_name']
    : $collegeName;
$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : ['need_based' => [], 'other' => []];
if (!isset($entries['need_based']) || !is_array($entries['need_based'])) {
    $entries['need_based'] = [];
}
if (!isset($entries['other']) || !is_array($entries['other'])) {
    $entries['other'] = [];
}
$challenges = is_string($summaryData['challenges'] ?? null) ? $summaryData['challenges'] : '';
$bestPractices = is_string($summaryData['best_practices'] ?? null) ? $summaryData['best_practices'] : '';
$lessonsLearned = is_string($summaryData['lessons_learned'] ?? null) ? $summaryData['lessons_learned'] : '';
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper eso-trainings-paper" method="post" enctype="multipart/form-data" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/trainings-conducted') : base_url('proposals/trainings-conducted') ?>">
    <?= csrf_field() ?>
    <?php if (proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="extension">
    <?php endif; ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishment in Extension</p>
        <h2 class="completed-researches-title">TRAININGS CONDUCTED</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-06 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'trainings_conducted',
                    'report_as_of' => $reportAsOfValue ?? '',
                    'reporting_period' => quarterly_reporting_period_from_summary(['report_as_of' => $reportAsOfValue ?? '']),
                ];
                require APP_PATH . '/views/partials/quarterly-reporting-period.php';
                ?>
            <tr>
                <th>College</th>
                <td>
                    <?php if ($collegeDisplay !== ''): ?>
                        <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                        <span><?= htmlspecialchars($collegeDisplay) ?></span>
                    <?php else: ?>
                        <select name="college_id" required>
                            <option value="">Select college</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>">
                                    <?= htmlspecialchars((string) $college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="college_name" id="trainings-conducted-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_trainings-conducted-table.php'; ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Challenges</h3>
        <p class="proposal-section-note">Problems encountered that contributed to low accomplishment.</p>
        <textarea class="proposal-textarea" name="challenges" rows="4"><?= htmlspecialchars($challenges) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Best Practices</h3>
        <p class="proposal-section-note">Factors that contributed to outstanding accomplishment.</p>
        <textarea class="proposal-textarea" name="best_practices" rows="4"><?= htmlspecialchars($bestPractices) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Lessons Learned and/or Recommendations</h3>
        <textarea class="proposal-textarea" name="lessons_learned" rows="4"><?= htmlspecialchars($lessonsLearned) ?></textarea>
    </section>

    <?php
    $allowUpload = $allowDocumentUpload;
    require APP_PATH . '/views/proposals/_trainings-conducted-supporting-documents.php';
    ?>

    <section class="proposal-section completed-researches-signoff">
        <div class="completed-researches-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Prepared by:</p>
                <p class="completed-researches-signoff-role">Field Extensionist</p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Attested True and Correct:</p>
                <p class="completed-researches-signoff-role">College Dean</p>
            </div>
        </div>
        <p class="proposal-section-note">Signatures are captured through the approval workflow after submission.</p>
    </section>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => 'extension',
        'summary' => json_encode(['form_type' => 'trainings_conducted']),
        'first_name' => (string) ($user['first_name'] ?? ''),
        'last_name' => (string) ($user['last_name'] ?? ''),
    ];
    $workflowSteps = proposal_workflow_steps($workflowProposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <div class="actions proposal-form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<template id="trainings-conducted-row-template">
    <tr class="trainings-conducted-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="extension_program"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="training_title"></textarea></td>
        <td><input type="text" data-field="venue"></td>
        <td><input type="date" data-field="training_date"></td>
        <td><input type="text" data-field="participant_type" placeholder="e.g. Farmers"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-persons-trained tc-input-num" data-field="persons_trained"></td>
        <td class="tc-col-weighted">
            <div class="tc-cell-stack">
                <select class="tc-training-length" data-field="training_length" aria-label="Training length for weighted total">
                    <option value="">Select length</option>
                    <option value="lt_8h">&lt; 8 hours (0.5)</option>
                    <option value="8h_1d">8 hours or 1 day (1)</option>
                    <option value="2d">2 days (1.25)</option>
                    <option value="3_4d">3–4 days (1.5)</option>
                    <option value="5d_plus">5 days or more (2)</option>
                </select>
                <input type="hidden" class="tc-weighted-value" data-field="persons_trained_weighted" value="">
                <div class="tc-weighted-display" aria-live="polite"></div>
            </div>
        </td>
        <td><input type="text" data-field="personnel_name"></td>
        <td><input type="text" data-field="personnel_role" placeholder="e.g. Facilitator"></td>
        <td><input type="text" data-field="budget_source"></td>
        <td><input type="text" data-field="budget_amount"></td>
        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-input-num" data-field="trainees_surveyed"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="quality_poor"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="quality_fair"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="quality_good"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="quality_better"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="quality_best"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="timeliness_poor"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="timeliness_fair"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="timeliness_good"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="timeliness_better"></td>
        <td class="tc-col-rating"><input type="number" min="0" step="1" class="tc-input-num" data-field="timeliness_best"></td>
        <td class="tc-col-actions completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline trainings-conducted-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const weights = { lt_8h: 0.5, '8h_1d': 1, '2d': 1.25, '3_4d': 1.5, '5d_plus': 2 };

    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('trainings-conducted-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const table = document.querySelector('.trainings-conducted-table');
    const rowTemplate = document.getElementById('trainings-conducted-row-template');
    const fieldNames = [
        'extension_program', 'training_title', 'venue', 'training_date', 'participant_type',
        'persons_trained', 'training_length', 'persons_trained_weighted',
        'personnel_name', 'personnel_role', 'budget_source', 'budget_amount', 'trainees_surveyed',
        'quality_poor', 'quality_fair', 'quality_good', 'quality_better', 'quality_best',
        'timeliness_poor', 'timeliness_fair', 'timeliness_good', 'timeliness_better', 'timeliness_best',
    ];

    const computeWeighted = (row) => {
        const persons = parseInt(row.querySelector('.tc-persons-trained')?.value ?? '0', 10) || 0;
        const length = row.querySelector('.tc-training-length')?.value ?? '';
        const weight = weights[length] ?? 0;
        const weighted = persons > 0 && weight > 0 ? Math.round(persons * weight * 100) / 100 : 0;
        const hidden = row.querySelector('.tc-weighted-value');
        const display = row.querySelector('.tc-weighted-display');
        if (hidden) {
            hidden.value = weighted > 0 ? String(weighted) : '';
        }
        if (display) {
            display.textContent = weighted > 0 ? `Weighted: ${weighted}` : '';
        }
        return weighted;
    };

    const sectionTotals = () => {
        let grandPersons = 0;
        let grandWeighted = 0;
        table?.querySelectorAll('.trainings-conducted-total-row').forEach((totalRow) => {
            const section = totalRow.dataset.section ?? '';
            let persons = 0;
            let weighted = 0;
            table.querySelectorAll(`.trainings-conducted-row[data-section="${section}"]`).forEach((row) => {
                const p = parseInt(row.querySelector('.tc-persons-trained')?.value ?? '0', 10) || 0;
                persons += p;
                weighted += computeWeighted(row);
            });
            totalRow.querySelector('.tc-total-persons').textContent = String(persons);
            totalRow.querySelector('.tc-total-weighted').textContent = String(Math.round(weighted * 100) / 100);
            grandPersons += persons;
            grandWeighted += weighted;
        });
        const grandRow = table?.querySelector('.trainings-conducted-grand-total-row');
        if (grandRow) {
            grandRow.querySelector('.tc-grand-total-persons').textContent = String(grandPersons);
            grandRow.querySelector('.tc-grand-total-weighted').textContent = String(Math.round(grandWeighted * 100) / 100);
        }
    };

    const assignNames = (row, section, index) => {
        fieldNames.forEach((field) => {
            const input = row.querySelector(`[data-field="${field}"]`) || row.querySelector(`[name$="[${field}]"]`);
            if (!input) {
                return;
            }
            input.name = `entries[${section}][${index}][${field}]`;
            input.removeAttribute('data-field');
        });
    };

    const reindexSection = (section) => {
        if (!table) {
            return;
        }
        const rows = table.querySelectorAll(`.trainings-conducted-row[data-section="${section}"]`);
        rows.forEach((row, index) => assignNames(row, section, index));
        sectionTotals();
    };

    const bindRow = (row) => {
        row.querySelector('.tc-persons-trained')?.addEventListener('input', () => {
            computeWeighted(row);
            sectionTotals();
        });
        row.querySelector('.tc-training-length')?.addEventListener('change', () => {
            computeWeighted(row);
            sectionTotals();
        });
        row.querySelector('.trainings-conducted-remove-row')?.addEventListener('click', () => {
            const section = row.dataset.section ?? '';
            const sectionRows = table?.querySelectorAll(`.trainings-conducted-row[data-section="${section}"]`) ?? [];
            if (sectionRows.length <= 1) {
                row.querySelectorAll('input, textarea, select').forEach((input) => {
                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                        input.value = '';
                    } else if (input instanceof HTMLSelectElement) {
                        input.selectedIndex = 0;
                    }
                });
                computeWeighted(row);
                sectionTotals();
                return;
            }
            row.remove();
            reindexSection(section);
        });
        computeWeighted(row);
    };

    table?.querySelectorAll('.trainings-conducted-row').forEach((row) => {
        const section = row.dataset.section ?? '';
        bindRow(row);
        const rows = table.querySelectorAll(`.trainings-conducted-row[data-section="${section}"]`);
        rows.forEach((r, index) => assignNames(r, section, index));
    });
    sectionTotals();

    document.querySelectorAll('.trainings-conducted-add-row').forEach((button) => {
        button.addEventListener('click', () => {
            const section = button.dataset.section ?? '';
            if (!table || !rowTemplate || section === '') {
                return;
            }
            const totalRow = table.querySelector(`.trainings-conducted-total-row[data-section="${section}"]`);
            const row = rowTemplate.content.firstElementChild?.cloneNode(true);
            if (!(row instanceof HTMLTableRowElement) || !totalRow) {
                return;
            }
            row.dataset.section = section;
            totalRow.parentNode?.insertBefore(row, totalRow);
            bindRow(row);
            reindexSection(section);
        });
    });

    document.querySelectorAll('textarea.proposal-textarea').forEach((textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
        };
        resize();
        textarea.addEventListener('input', resize);
    });
})();
</script>
