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

$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Technical Advisory | RIDE';

$pageHeading = 'Technical Advisory';

$pageSubtitle = $isEdit

    ? 'Update your quarterly technical advisory report before saving or resubmitting.'

    : 'Quarterly report of accomplishment in Extension (WPU-QSF-RIDE-ESO-07).';

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

$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];

$challenges = is_string($summaryData['challenges'] ?? null) ? $summaryData['challenges'] : '';

$bestPractices = is_string($summaryData['best_practices'] ?? null) ? $summaryData['best_practices'] : '';

$lessonsLearned = is_string($summaryData['lessons_learned'] ?? null) ? $summaryData['lessons_learned'] : '';

?>



<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper" method="post" enctype="multipart/form-data" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/technical-advisory') : base_url('proposals/technical-advisory') ?>">

    <?= csrf_field() ?>

    <?php if (proposal_nav_scope() !== null): ?>

        <input type="hidden" name="nav_scope" value="extension">

    <?php endif; ?>



    <header class="completed-researches-header">

        <p class="completed-researches-header-line">Republic of the Philippines</p>

        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>

        <p class="completed-researches-header-line">Office of Extension Services</p>

        <p class="completed-researches-header-line">Quarterly Report of Accomplishment in Extension</p>

        <h2 class="completed-researches-title">TECHNICAL ADVISORY</h2>

        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-07 Rev.00 (08.15.25)</p>

    </header>



    <section class="proposal-section">

        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'technical_advisory',
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

                        <input type="hidden" name="college_name" id="technical-advisory-college-name" value="">

                    <?php endif; ?>

                </td>

            </tr>

        </table>

    </section>



    <?php $readOnly = false; require APP_PATH . '/views/proposals/_technical-advisory-table.php'; ?>



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



    <?php require APP_PATH . '/views/proposals/_technical-advisory-notes.php'; ?>



    <?php

    $allowUpload = $allowDocumentUpload;

    require APP_PATH . '/views/proposals/_technical-advisory-supporting-documents.php';

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

        'summary' => json_encode(['form_type' => 'technical_advisory']),

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



<template id="technical-advisory-row-template">

    <tr class="technical-advisory-row">

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="extension_program"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="technical_advisory_conducted"></textarea></td>

        <td><input type="text" data-field="venue"></td>

        <td><input type="date" data-field="advisory_date"></td>

        <td><input type="text" data-field="client_type" placeholder="e.g. LGU, Farmer"></td>

        <td class="tc-col-count"><input type="number" min="0" step="1" class="tc-input-num" data-field="clients_served"></td>

        <td><input type="text" data-field="personnel_name"></td>

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

        <td class="completed-researches-actions-col">

            <button type="button" class="btn btn-sm btn-outline technical-advisory-remove-row" title="Remove row">Remove</button>

        </td>

    </tr>

</template>



<script>

(() => {

    const collegeSelect = document.querySelector('select[name="college_id"]');

    const collegeNameInput = document.getElementById('technical-advisory-college-name');

    if (collegeSelect && collegeNameInput) {

        const syncCollegeName = () => {

            const option = collegeSelect.options[collegeSelect.selectedIndex];

            collegeNameInput.value = option?.dataset?.name ?? '';

        };

        collegeSelect.addEventListener('change', syncCollegeName);

        syncCollegeName();

    }



    const table = document.querySelector('.technical-advisory-table');

    const rowTemplate = document.getElementById('technical-advisory-row-template');

    const fieldNames = [

        'extension_program', 'technical_advisory_conducted', 'venue', 'advisory_date', 'client_type',

        'clients_served', 'personnel_name', 'trainees_surveyed',

        'quality_poor', 'quality_fair', 'quality_good', 'quality_better', 'quality_best',

        'timeliness_poor', 'timeliness_fair', 'timeliness_good', 'timeliness_better', 'timeliness_best',

    ];



    const rowHasContent = (row) => {

        for (const input of row.querySelectorAll('input, textarea')) {

            if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {

                if (input.value.trim() !== '' && input.value.trim() !== '0') {

                    return true;

                }

            }

        }

        return false;

    };



    const updateTotals = () => {

        if (!table) {

            return;

        }

        let count = 0;

        table.querySelectorAll('.technical-advisory-row').forEach((row) => {

            if (rowHasContent(row)) {

                count += 1;

            }

        });

        const totalEl = table.querySelector('.tc-total-advisories');

        if (totalEl) {

            totalEl.textContent = String(count);

        }

    };



    const assignNames = (row, index) => {

        fieldNames.forEach((field) => {

            const input = row.querySelector(`[data-field="${field}"]`);

            if (!input) {

                return;

            }

            input.name = `entries[${index}][${field}]`;

            input.removeAttribute('data-field');

        });

    };



    const reindexRows = () => {

        if (!table) {

            return;

        }

        const rows = table.querySelectorAll('.technical-advisory-row');

        rows.forEach((row, index) => assignNames(row, index));

        updateTotals();

    };



    const bindRow = (row) => {

        row.querySelectorAll('input, textarea').forEach((input) => {

            input.addEventListener('input', updateTotals);

        });

        row.querySelector('.technical-advisory-remove-row')?.addEventListener('click', () => {

            const rows = table?.querySelectorAll('.technical-advisory-row') ?? [];

            if (rows.length <= 1) {

                row.querySelectorAll('input, textarea').forEach((input) => {

                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {

                        input.value = '';

                    }

                });

                updateTotals();

                return;

            }

            row.remove();

            reindexRows();

        });

    };



    table?.querySelectorAll('.technical-advisory-row').forEach((row) => {

        bindRow(row);

    });

    reindexRows();



    document.querySelector('.technical-advisory-add-row')?.addEventListener('click', () => {

        const totalRow = table?.querySelector('.technical-advisory-total-row');

        const row = rowTemplate?.content.firstElementChild?.cloneNode(true);

        if (!(row instanceof HTMLTableRowElement) || !totalRow) {

            return;

        }

        totalRow.parentNode?.insertBefore(row, totalRow);

        bindRow(row);

        reindexRows();

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

