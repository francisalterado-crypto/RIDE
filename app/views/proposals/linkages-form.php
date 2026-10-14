<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Linkages | RIDE';
$pageHeading = 'Linkages';
$pageSubtitle = $isEdit
    ? 'Update your quarterly linkages report before saving or resubmitting.'
    : 'Quarterly report of accomplishments in research linkages.';
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
$rows = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper linkages-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/linkages') : base_url('proposals/linkages') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">RESEARCH LINKAGES</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-14 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'linkages',
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
                        <input type="hidden" name="college_name" id="linkages-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_linkages-entries.php'; ?>

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
        'summary' => json_encode(['form_type' => 'linkages']),
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

<template id="linkages-row-template">
    <tr class="linkages-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="program"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="partner"></textarea></td>
        <td><input type="text" data-field="linkage_forged"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="institution_type"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="deliverables"></textarea></td>
        <td><input type="date" data-field="date_started"></td>
        <td><input type="date" data-field="date_completed"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="personnel"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="beneficiaries"></textarea></td>
        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline linkages-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('linkages-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowTemplate = document.getElementById('linkages-row-template');
    const tbody = document.querySelector('.linkages-rows');
    const addButton = document.getElementById('linkages-add-row');
    const fieldNames = [
        'program',
        'partner',
        'linkage_forged',
        'institution_type',
        'deliverables',
        'date_started',
        'date_completed',
        'personnel',
        'beneficiaries',
        'google_drive_link',
    ];

    const assignNames = (row, index) => {
        fieldNames.forEach((field) => {
            const withDataField = row.querySelector(`[data-field="${field}"]`);
            const input = withDataField || row.querySelector(`[name$="[${field}]"]`);
            if (!input) {
                return;
            }
            input.name = `entries[${index}][${field}]`;
            input.removeAttribute('data-field');
        });
    };

    const reindexRows = () => {
        if (!tbody) {
            return;
        }
        tbody.querySelectorAll('.linkages-row').forEach((row, index) => assignNames(row, index));
    };

    const bindRemove = (row) => {
        const removeButton = row.querySelector('.linkages-remove-row');
        removeButton?.addEventListener('click', () => {
            if (!tbody) {
                return;
            }
            if (tbody.querySelectorAll('.linkages-row').length <= 1) {
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

    if (tbody) {
        tbody.querySelectorAll('.linkages-row').forEach((row, index) => {
            assignNames(row, index);
            bindRemove(row);
        });
    }

    addButton?.addEventListener('click', () => {
        if (!tbody || !rowTemplate) {
            return;
        }
        const row = rowTemplate.content.firstElementChild?.cloneNode(true);
        if (!(row instanceof HTMLTableRowElement)) {
            return;
        }
        tbody.appendChild(row);
        bindRemove(row);
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
