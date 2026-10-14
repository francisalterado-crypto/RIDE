<?php

/** @var array|null $proposal */

/** @var list<array> $colleges */

/** @var string $collegeName */



$isEdit = $proposal !== null;

$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Consolidated Inventions, UM, Copyrights | RIDE';

$pageHeading = 'Consolidated Inventions, UM, Copyrights';

$pageSubtitle = $isEdit

    ? 'Update the consolidated inventions, utility models, and copyrights report before saving or resubmitting.'

    : 'College consolidation of faculty inventions, utility models, and copyrights reports approved by the RDO Director (WPU-QSF-RIDE-RDO-13).';

$user = \App\Core\Auth::user() ?? [];



/** @var array<string, list<array<string, string>>> $prefilledEntries */

$prefilledEntries = $prefilledEntries ?? [];



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



if (

    \App\Services\ResearchFormConsolidation::entriesHaveRows('inventions_um_copyrights', $prefilledEntries)

    && !\App\Services\ResearchFormConsolidation::entriesHaveRows('inventions_um_copyrights', $entries)

) {

    $entries = $prefilledEntries;

}



$fieldNames = [

    'research_title',

    'date_started',

    'date_developed_completed',

    'inventors_researchers',

    'patent_registration_copyright_number',

    'date_of_issue_application',

    'adopter',

    'commercial_product_name',

    'college',

    'campus',

    'google_drive_link',

];

?>



<form class="proposal-paper completed-researches-paper trainings-conducted-paper consolidated-inventions-um-copyrights-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/consolidated-inventions-um-copyrights') : base_url('proposals/consolidated-inventions-um-copyrights') ?>">

    <?= csrf_field() ?>



    <header class="completed-researches-header">

        <p class="completed-researches-header-line">Republic of the Philippines</p>

        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>

        <p class="completed-researches-header-line">Office of Research and Development</p>

        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>

        <h2 class="completed-researches-title">CONSOLIDATED INVENTIONS, UTILITY MODELS AND COPYRIGHTS</h2>

        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-13 Rev.00 (09.15.25)</p>

    </header>



    <section class="proposal-section">

        <h2 class="proposal-section-title">Report Information</h2>

        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'consolidated_inventions_um_copyrights',
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

                                <option value="<?= (int) ($college['id'] ?? 0) ?>" data-name="<?= htmlspecialchars((string) ($college['name'] ?? '')) ?>">

                                    <?= htmlspecialchars((string) ($college['name'] ?? '')) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <input type="hidden" name="college_name" id="consolidated-inventions-um-copyrights-college-name" value="">

                    <?php endif; ?>

                </td>

            </tr>

        </table>

    </section>



    <?php if (\App\Services\ResearchFormConsolidation::entriesHaveRows('inventions_um_copyrights', $entries)): ?>

        <p class="proposal-section-note">Rows below include faculty inventions, utility models, and copyrights reports for your college, deposited after RDO Director approval.</p>

    <?php elseif (!$isEdit): ?>

        <p class="proposal-section-note">No faculty inventions, utility models, and copyrights reports approved by the RDO Director were found yet. Rows are added automatically when the RDO Director approves each report.</p>

    <?php endif; ?>



    <?php

    $readOnly = false;

    require APP_PATH . '/views/proposals/_consolidated-inventions-um-copyrights-entries.php';

    ?>



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

        'summary' => json_encode(['form_type' => 'consolidated_inventions_um_copyrights']),

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



<template id="consolidated-inventions-um-copyrights-row-template">

    <tr class="consolidated-inventions-um-copyrights-row">

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="research_title"></textarea></td>

        <td><input type="date" data-field="date_started"></td>

        <td><input type="date" data-field="date_developed_completed"></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="inventors_researchers"></textarea></td>

        <td><input type="text" data-field="patent_registration_copyright_number"></td>

        <td><input type="date" data-field="date_of_issue_application"></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="adopter"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="commercial_product_name"></textarea></td>

        <td><input type="text" data-field="college"></td>

        <td><input type="text" data-field="campus"></td>

        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>

        <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline consolidated-inventions-um-copyrights-remove-row">Remove</button></td>

    </tr>

</template>



<script>

(() => {

    const fieldNames = <?= json_encode($fieldNames) ?>;

    const collegeSelect = document.querySelector('select[name="college_id"]');

    const collegeNameInput = document.getElementById('consolidated-inventions-um-copyrights-college-name');

    if (collegeSelect && collegeNameInput) {

        const syncCollegeName = () => {

            const option = collegeSelect.options[collegeSelect.selectedIndex];

            collegeNameInput.value = option?.dataset?.name ?? '';

        };

        collegeSelect.addEventListener('change', syncCollegeName);

        syncCollegeName();

    }



    const rowTemplate = document.getElementById('consolidated-inventions-um-copyrights-row-template');

    const resizeTextareas = () => {

        document.querySelectorAll('textarea.proposal-textarea').forEach((textarea) => {

            const resize = () => {

                textarea.style.height = 'auto';

                textarea.style.height = `${textarea.scrollHeight}px`;

            };

            resize();

            textarea.addEventListener('input', resize);

        });

    };



    const reindexSection = (tbody, sectionKey) => {

        tbody.querySelectorAll('.consolidated-inventions-um-copyrights-row').forEach((row, index) => {

            fieldNames.forEach((field) => {

                const input = row.querySelector(`[name*="[${field}]"], [data-field="${field}"]`);

                if (input) {

                    input.name = `entries[${sectionKey}][${index}][${field}]`;

                    input.removeAttribute('data-field');

                }

            });

        });

    };



    const bindRowRemove = (tbody, row, sectionKey) => {

        row.querySelector('.consolidated-inventions-um-copyrights-remove-row')?.addEventListener('click', () => {

            if (tbody.querySelectorAll('.consolidated-inventions-um-copyrights-row').length <= 1) {

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



    document.querySelectorAll('.consolidated-inventions-um-copyrights-rows').forEach((tbody) => {

        const sectionKey = tbody.dataset.section ?? '';

        tbody.querySelectorAll('.consolidated-inventions-um-copyrights-row').forEach((row) => bindRowRemove(tbody, row, sectionKey));

        reindexSection(tbody, sectionKey);

    });



    document.querySelectorAll('.consolidated-inventions-um-copyrights-add-row').forEach((button) => {

        button.addEventListener('click', () => {

            const sectionKey = button.getAttribute('data-section') ?? '';

            const tbody = document.querySelector(`.consolidated-inventions-um-copyrights-rows[data-section="${sectionKey}"]`);

            if (!tbody || !rowTemplate) {

                return;

            }

            const addRowButton = tbody.querySelector('.consolidated-inventions-um-copyrights-add-row')?.closest('tr');

            const row = rowTemplate.content.firstElementChild?.cloneNode(true);

            if (!(row instanceof HTMLTableRowElement)) {

                return;

            }

            if (addRowButton instanceof HTMLTableRowElement) {

                tbody.insertBefore(row, addRowButton);

            } else {

                tbody.appendChild(row);

            }

            bindRowRemove(tbody, row, sectionKey);

            reindexSection(tbody, sectionKey);

            resizeTextareas();

        });

    });



    resizeTextareas();

})();

</script>


