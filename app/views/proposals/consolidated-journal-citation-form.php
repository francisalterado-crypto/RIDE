<?php

/** @var array|null $proposal */

/** @var list<array> $colleges */

/** @var string $collegeName */



$isEdit = $proposal !== null;

$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Consolidated Journal Citation — RIDE IMS';

$pageHeading = 'Consolidated Journal Citation';

$pageSubtitle = $isEdit

    ? 'Update the consolidated journal citation report before saving or resubmitting.'

    : 'College consolidation of faculty journal citation reports approved by the RDO Director (WPU-QSF-RIDE-RDO-11).';

$user = \App\Core\Auth::user() ?? [];



/** @var list<array<string, string>> $prefilledEntries */

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

    \App\Services\ResearchFormConsolidation::entriesHaveRows('journal_citation', $prefilledEntries)

    && !\App\Services\ResearchFormConsolidation::entriesHaveRows('journal_citation', $entries)

) {

    $entries = $prefilledEntries;

}

?>



<form class="proposal-paper completed-researches-paper trainings-conducted-paper consolidated-journal-citation-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/consolidated-journal-citation') : base_url('proposals/consolidated-journal-citation') ?>">

    <?= csrf_field() ?>



    <header class="completed-researches-header">

        <p class="completed-researches-header-line">Republic of the Philippines</p>

        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>

        <p class="completed-researches-header-line">Office of Research and Development</p>

        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>

        <h2 class="completed-researches-title">CONSOLIDATED RESEARCH ARTICLE AS CITED BY OTHER RESEARCHER(S) IN REFEREED JOURNAL ARTICLES</h2>

        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-11 Rev.00 (09.15.25)</p>

    </header>



    <section class="proposal-section">

        <h2 class="proposal-section-title">Report Information</h2>

        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'consolidated_journal_citation',
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

                        <input type="hidden" name="college_name" id="consolidated-journal-citation-college-name" value="">

                    <?php endif; ?>

                </td>

            </tr>

        </table>

    </section>



    <?php if (\App\Services\ResearchFormConsolidation::entriesHaveRows('journal_citation', $entries)): ?>

        <p class="proposal-section-note">Rows below include faculty journal citation reports for your college, deposited after RDO Director approval.</p>

    <?php elseif (!$isEdit): ?>

        <p class="proposal-section-note">No faculty journal citation reports approved by the RDO Director were found yet. Rows are added automatically when the RDO Director approves each report.</p>

    <?php endif; ?>



    <?php

    $rows = $entries;

    $readOnly = false;

    require APP_PATH . '/views/proposals/_consolidated-journal-citation-entries.php';

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

        'summary' => json_encode(['form_type' => 'consolidated_journal_citation']),

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



<template id="consolidated-journal-citation-row-template">

    <tr class="consolidated-journal-citation-row">

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="authors_original_article"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_original_article_cited"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_refereed_journal_original"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_new_research_article"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="authors_new_article"></textarea></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title_refereed_journal_new"></textarea></td>

        <td><input type="text" data-field="volume_issue"></td>

        <td><input type="text" data-field="pages"></td>

        <td><input type="number" min="1900" max="2100" step="1" data-field="year_publication"></td>

        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="publisher"></textarea></td>

        <td><input type="text" data-field="college"></td>

        <td><input type="text" data-field="campus"></td>

        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>

        <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline consolidated-journal-citation-remove-row">Remove</button></td>

    </tr>

</template>



<script>

(() => {

    const collegeSelect = document.querySelector('select[name="college_id"]');

    const collegeNameInput = document.getElementById('consolidated-journal-citation-college-name');

    if (collegeSelect && collegeNameInput) {

        const syncCollegeName = () => {

            const option = collegeSelect.options[collegeSelect.selectedIndex];

            collegeNameInput.value = option?.dataset?.name ?? '';

        };

        collegeSelect.addEventListener('change', syncCollegeName);

        syncCollegeName();

    }



    const fieldNames = [

        'authors_original_article',

        'title_original_article_cited',

        'title_refereed_journal_original',

        'title_new_research_article',

        'authors_new_article',

        'title_refereed_journal_new',

        'volume_issue',

        'pages',

        'year_publication',

        'publisher',

        'college',

        'campus',

        'google_drive_link',

    ];



    const rowsBody = document.getElementById('consolidated-journal-citation-rows');

    const rowTemplate = document.getElementById('consolidated-journal-citation-row-template');

    const addButton = document.querySelector('.consolidated-journal-citation-add-row');



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



    const reindexRows = () => {

        if (!rowsBody) {

            return;

        }



        rowsBody.querySelectorAll('.consolidated-journal-citation-row').forEach((row, index) => {

            fieldNames.forEach((field) => {

                const input = row.querySelector(`[name*="[${field}]"], [data-field="${field}"]`);

                if (!input) {

                    return;

                }

                input.name = `entries[${index}][${field}]`;

                input.removeAttribute('data-field');

            });

        });

    };



    const bindRemove = (row) => {

        const removeButton = row.querySelector('.consolidated-journal-citation-remove-row');

        removeButton?.addEventListener('click', () => {

            if (!rowsBody) {

                return;

            }

            if (rowsBody.querySelectorAll('.consolidated-journal-citation-row').length <= 1) {

                row.querySelectorAll('input, textarea').forEach((input) => {

                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {

                        input.value = '';

                    }

                });

                return;

            }

            row.remove();

            reindexRows();

        });

    };



    if (rowsBody) {

        rowsBody.querySelectorAll('.consolidated-journal-citation-row').forEach((row) => bindRemove(row));

    }



    addButton?.addEventListener('click', () => {

        if (!rowsBody || !rowTemplate) {

            return;

        }

        const row = rowTemplate.content.firstElementChild?.cloneNode(true);

        if (!(row instanceof HTMLTableRowElement)) {

            return;

        }

        rowsBody.appendChild(row);

        bindRemove(row);

        reindexRows();

        resizeTextareas();

    });



    reindexRows();

    resizeTextareas();

})();

</script>


