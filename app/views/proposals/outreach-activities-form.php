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
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Outreach Activities | RIDE';
$pageHeading = 'Outreach Activities';
$pageSubtitle = $isEdit
    ? 'Update your quarterly outreach activities report before saving or resubmitting.'
    : 'Quarterly report of accomplishment in Extension.';
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

<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper" method="post" enctype="multipart/form-data" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/outreach-activities') : base_url('proposals/outreach-activities') ?>">
    <?= csrf_field() ?>
    <?php if (proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="extension">
    <?php endif; ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishment in Extension</p>
        <h2 class="completed-researches-title">OUTREACH ACTIVITIES</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-09 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'outreach_activities',
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
                        <input type="hidden" name="college_name" id="outreach-activities-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_outreach-activities-table.php'; ?>

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

    <?php require APP_PATH . '/views/proposals/_outreach-activities-notes.php'; ?>

    <?php
    $allowUpload = $allowDocumentUpload;
    require APP_PATH . '/views/proposals/_outreach-activities-supporting-documents.php';
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
        'summary' => json_encode(['form_type' => 'outreach_activities']),
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

<template id="outreach-activities-row-template">
    <tr class="outreach-activities-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="title"></textarea></td>
        <td><input type="text" data-field="venue"></td>
        <td><input type="date" data-field="start_date"></td>
        <td><input type="date" data-field="end_date"></td>
        <td><input type="number" min="0" step="1" data-field="beneficiaries"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline outreach-activities-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('outreach-activities-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const table = document.querySelector('.outreach-activities-table');
    const rowTemplate = document.getElementById('outreach-activities-row-template');
    const addButton = document.querySelector('.outreach-activities-add-row');
    const fieldNames = ['title', 'venue', 'start_date', 'end_date', 'beneficiaries'];

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

    const rowHasContent = (row) => {
        for (const input of row.querySelectorAll('input, textarea')) {
            if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement) {
                const value = input.value.trim();
                if (value !== '' && value !== '0') {
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
        let totalActivities = 0;
        let totalBeneficiaries = 0;
        table.querySelectorAll('.outreach-activities-row').forEach((row) => {
            if (rowHasContent(row)) {
                totalActivities += 1;
            }
            const beneficiariesInput = row.querySelector('input[name$="[beneficiaries]"]');
            if (beneficiariesInput instanceof HTMLInputElement) {
                totalBeneficiaries += parseInt(beneficiariesInput.value || '0', 10) || 0;
            }
        });
        const totalActivitiesEl = table.querySelector('.oa-total-activities');
        const totalBeneficiariesEl = table.querySelector('.oa-total-beneficiaries');
        if (totalActivitiesEl) {
            totalActivitiesEl.textContent = String(totalActivities);
        }
        if (totalBeneficiariesEl) {
            totalBeneficiariesEl.textContent = String(totalBeneficiaries);
        }
    };

    const reindexRows = () => {
        if (!table) {
            return;
        }
        const rows = table.querySelectorAll('.outreach-activities-row');
        rows.forEach((row, index) => assignNames(row, index));
        updateTotals();
    };

    const bindRow = (row) => {
        row.querySelectorAll('input, textarea').forEach((input) => {
            input.addEventListener('input', updateTotals);
        });
        row.querySelector('.outreach-activities-remove-row')?.addEventListener('click', () => {
            const rows = table?.querySelectorAll('.outreach-activities-row') ?? [];
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

    table?.querySelectorAll('.outreach-activities-row').forEach((row) => bindRow(row));
    reindexRows();

    addButton?.addEventListener('click', () => {
        const totalRow = table?.querySelector('.outreach-activities-total-row');
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
