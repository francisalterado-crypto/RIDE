<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */
/** @var list<array<string, string>> $prefilledEntries */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Consolidated Linkages | RIDE';
$pageHeading = 'Consolidated Linkages';
$pageSubtitle = $isEdit
    ? 'Update the consolidated linkages report before saving or resubmitting.'
    : 'College consolidation of faculty linkages reports approved by the RDO Director (WPU-QSF-RIDE-RDO-14).';
$user = \App\Core\Auth::user() ?? [];
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
    \App\Services\ResearchFormConsolidation::entriesHaveRows('linkages', $prefilledEntries)
    && !\App\Services\ResearchFormConsolidation::entriesHaveRows('linkages', $entries)
) {
    $entries = $prefilledEntries;
}
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper linkages-paper consolidated-linkages-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/consolidated-linkages') : base_url('proposals/consolidated-linkages') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">CONSOLIDATED RESEARCH LINKAGES</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-14 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'consolidated_linkages',
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
                        <input type="hidden" name="college_name" id="consolidated-linkages-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php if (\App\Services\ResearchFormConsolidation::entriesHaveRows('linkages', $entries)): ?>
        <p class="proposal-section-note">Rows below include faculty linkages reports for your college, deposited after RDO Director approval.</p>
    <?php elseif (!$isEdit): ?>
        <p class="proposal-section-note">No faculty linkages reports approved by the RDO Director were found yet. Rows are added automatically when the RDO Director approves each report.</p>
    <?php endif; ?>

    <?php
    $rows = $entries;
    $readOnly = false;
    require APP_PATH . '/views/proposals/_consolidated-linkages-entries.php';
    ?>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => 'research',
        'summary' => json_encode(['form_type' => 'consolidated_linkages']),
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

<template id="consolidated-linkages-row-template">
    <tr class="consolidated-linkages-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="program"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="partner"></textarea></td>
        <td><input type="text" data-field="linkage_forged"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="institution_type"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="deliverables"></textarea></td>
        <td><input type="date" data-field="date_started"></td>
        <td><input type="date" data-field="date_completed"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="personnel"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="beneficiaries"></textarea></td>
        <td><input type="text" data-field="college"></td>
        <td><input type="text" data-field="campus"></td>
        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline consolidated-linkages-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('consolidated-linkages-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const fieldNames = [
        'program', 'partner', 'linkage_forged', 'institution_type', 'deliverables',
        'date_started', 'date_completed', 'personnel', 'beneficiaries', 'college', 'campus', 'google_drive_link',
    ];
    const rowsBody = document.getElementById('consolidated-linkages-rows');
    const rowTemplate = document.getElementById('consolidated-linkages-row-template');
    const addButton = document.querySelector('.consolidated-linkages-add-row');

    const reindexRows = () => {
        if (!rowsBody) {
            return;
        }
        rowsBody.querySelectorAll('.consolidated-linkages-row').forEach((row, index) => {
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
        row.querySelector('.consolidated-linkages-remove-row')?.addEventListener('click', () => {
            if (!rowsBody) {
                return;
            }
            if (rowsBody.querySelectorAll('.consolidated-linkages-row').length <= 1) {
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
        rowsBody.querySelectorAll('.consolidated-linkages-row').forEach((row) => bindRemove(row));
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
    });

    reindexRows();
})();
</script>
