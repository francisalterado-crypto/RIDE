<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' OBR Matrix | RIDE';
$pageHeading = 'OBR Matrix';
$pageSubtitle = $isEdit
    ? 'Update your Outcome Based Research Matrix before saving or resubmitting.'
    : 'Outcome Based Research Matrix for college research planning and reporting.';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$collegeDisplay = is_string($summaryData['college_name'] ?? null) && $summaryData['college_name'] !== ''
    ? $summaryData['college_name']
    : $collegeName;
$rows = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper obr-matrix-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/obr-matrix') : base_url('proposals/obr-matrix') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">OUTCOME BASED RESEARCH MATRIX</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-22 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
            <tr>
                <th>College of</th>
                <td colspan="3">
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
                        <input type="hidden" name="college_name" id="obr-matrix-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_obr-matrix-entries.php'; ?>

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
        'summary' => json_encode(['form_type' => 'obr_matrix']),
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

<template id="obr-matrix-row-template">
    <tr class="obr-matrix-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="university_thrusts"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="college_thrusts"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="research_areas"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="study_title"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="research_results"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="extension_utilization"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="outcomes"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="six_ps"></textarea></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline obr-matrix-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('obr-matrix-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowTemplate = document.getElementById('obr-matrix-row-template');
    const tbody = document.querySelector('.obr-matrix-rows');
    const addButton = document.getElementById('obr-matrix-add-row');
    const fieldNames = [
        'university_thrusts',
        'college_thrusts',
        'research_areas',
        'study_title',
        'research_results',
        'extension_utilization',
        'outcomes',
        'six_ps',
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
        tbody.querySelectorAll('.obr-matrix-row').forEach((row, index) => assignNames(row, index));
    };

    const bindRemove = (row) => {
        const removeButton = row.querySelector('.obr-matrix-remove-row');
        removeButton?.addEventListener('click', () => {
            if (!tbody) {
                return;
            }
            if (tbody.querySelectorAll('.obr-matrix-row').length <= 1) {
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
        tbody.querySelectorAll('.obr-matrix-row').forEach((row, index) => {
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
