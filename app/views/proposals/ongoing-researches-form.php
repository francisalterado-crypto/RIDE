<?php

/** @var array|null $proposal */

/** @var list<array> $colleges */

/** @var string $collegeName */



$isEdit = $proposal !== null;

$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Ongoing Researches | RIDE';

$pageHeading = 'Ongoing Researches';

$pageSubtitle = $isEdit

    ? 'Update your quarterly ongoing researches report before saving or resubmitting.'

    : 'Quarterly report of accomplishments in research (WPU-QSF-RIDE-RDO-06).';

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



$fundingSections = [

    'external' => 'A. Externally Funded',

    'inhouse' => 'B. In-house Funded',

    'personal' => 'C. University Supported through Official time',

];



$sectionRows = static function (string $key) use ($entries): array {

    $rows = $entries[$key] ?? [];

    return is_array($rows) ? $rows : [];

};

?>



<form class="proposal-paper completed-researches-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/ongoing-researches') : base_url('proposals/ongoing-researches') ?>">

    <?= csrf_field() ?>



    <header class="completed-researches-header">

        <p class="completed-researches-header-line">Republic of the Philippines</p>

        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>

        <p class="completed-researches-header-line">Office of Research and Development</p>

        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>

        <h2 class="completed-researches-title">ONGOING RESEARCHES</h2>

        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-06 Rev.00 (09.15.25)</p>

    </header>



    <section class="proposal-section">

        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'ongoing_researches',
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

                        <input type="hidden" name="college_name" id="ongoing-researches-college-name" value="">

                    <?php endif; ?>

                </td>

            </tr>

        </table>

    </section>



    <?php foreach ($fundingSections as $sectionKey => $sectionLabel): ?>

        <?php

        $rows = $sectionRows($sectionKey);

        $readOnly = false;

        $includeGoogleDriveLink = true;

        require APP_PATH . '/views/proposals/_completed-researches-entries.php';

        ?>

    <?php endforeach; ?>



    <section class="proposal-section completed-researches-signoff">

        <div class="completed-researches-signoff-grid">

            <div>

                <p class="completed-researches-signoff-label">Prepared by:</p>

                <p class="completed-researches-signoff-role">College R&amp;D Coordinator</p>

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

        'project_type' => 'research',

        'summary' => json_encode(['form_type' => 'ongoing_researches']),

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



<template id="ongoing-researches-row-template">

    <tr class="completed-researches-row">

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Research title" data-field="research_title"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Researcher name(s)" data-field="researcher_names"></textarea></td>

        <td><input type="date" data-field="date_started"></td>

        <td><input type="date" data-field="date_completed"></td>

        <td><input type="number" min="0" step="1" placeholder="Months" data-field="duration_months"></td>

        <td><input type="text" placeholder="Budget source" data-field="budget_source"></td>

        <td><input type="text" placeholder="Amount" data-field="budget_amount"></td>

        <td><input type="text" placeholder="Category" data-field="category"></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Remarks" data-field="remarks"></textarea></td>

        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>

        <td class="completed-researches-actions-col">

            <button type="button" class="btn btn-sm btn-outline completed-researches-remove-row" title="Remove row">Remove</button>

        </td>

    </tr>

</template>



<script>

(() => {

    const collegeSelect = document.querySelector('select[name="college_id"]');

    const collegeNameInput = document.getElementById('ongoing-researches-college-name');

    if (collegeSelect && collegeNameInput) {

        const syncCollegeName = () => {

            const option = collegeSelect.options[collegeSelect.selectedIndex];

            collegeNameInput.value = option?.dataset?.name ?? '';

        };

        collegeSelect.addEventListener('change', syncCollegeName);

        syncCollegeName();

    }



    const rowTemplate = document.getElementById('ongoing-researches-row-template');

    const fieldNames = [

        'research_title',

        'researcher_names',

        'date_started',

        'date_completed',

        'duration_months',

        'budget_source',

        'budget_amount',

        'category',

        'remarks',

        'google_drive_link',

    ];



    const bindRow = (row, sectionKey, index) => {

        fieldNames.forEach((field) => {

            const control = row.querySelector(`[data-field="${field}"]`);

            if (!control) {

                return;

            }

            control.name = `entries[${sectionKey}][${index}][${field}]`;

            control.removeAttribute('data-field');

        });



        const removeButton = row.querySelector('.completed-researches-remove-row');

        removeButton?.addEventListener('click', () => {

            const tbody = row.closest('.completed-researches-rows');

            if (!tbody || tbody.querySelectorAll('.completed-researches-row').length <= 1) {

                row.querySelectorAll('input, textarea').forEach((input) => {

                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {

                        input.value = '';

                    }

                });

                return;

            }

            row.remove();

            reindexSection(tbody, sectionKey);

        });

    };



    const reindexSection = (tbody, sectionKey) => {

        tbody.querySelectorAll('.completed-researches-row').forEach((row, index) => {

            fieldNames.forEach((field) => {

                const input = row.querySelector(`[name*="[${field}]"]`);

                if (input) {

                    input.name = `entries[${sectionKey}][${index}][${field}]`;

                }

            });

        });

    };



    document.querySelectorAll('.completed-researches-rows').forEach((tbody) => {

        const sectionKey = tbody.dataset.section ?? '';

        tbody.querySelectorAll('.completed-researches-row').forEach((row, index) => {

            bindRow(row, sectionKey, index);

        });

    });



    document.querySelectorAll('.completed-researches-add-row').forEach((button) => {

        button.addEventListener('click', () => {

            const sectionKey = button.dataset.section ?? '';

            const tbody = document.querySelector(`.completed-researches-rows[data-section="${sectionKey}"]`);

            if (!tbody || !rowTemplate) {

                return;

            }

            const index = tbody.querySelectorAll('.completed-researches-row').length;

            const row = rowTemplate.content.firstElementChild?.cloneNode(true);

            if (!(row instanceof HTMLTableRowElement)) {

                return;

            }

            tbody.appendChild(row);

            bindRow(row, sectionKey, index);

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

