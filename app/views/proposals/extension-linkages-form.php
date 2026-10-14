<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */
/** @var array<string, string> $requiredFileList */
/** @var array<string, list<array<string, mixed>>> $requiredDocuments */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Extension Linkages | RIDE';
$pageHeading = 'Extension Linkages';
$pageSubtitle = $isEdit
    ? 'Update your quarterly extension linkages report before saving or resubmitting.'
    : 'Quarterly report of accomplishment in Extension (WPU-QSF-RIDE-ESO-08).';
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

<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper" method="post" enctype="multipart/form-data" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/extension-linkages') : base_url('proposals/extension-linkages') ?>">
    <?= csrf_field() ?>
    <?php if (proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="extension">
    <?php endif; ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishment in Extension</p>
        <h2 class="completed-researches-title">EXTENSION LINKAGES</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-08 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'extension_linkages',
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
                        <input type="hidden" name="college_name" id="extension-linkages-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_extension-linkages-entries.php'; ?>

    <?php require APP_PATH . '/views/proposals/_extension-linkages-notes.php'; ?>

    <?php
    $allowUpload = true;
    require APP_PATH . '/views/proposals/_extension-linkages-supporting-documents.php';
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
        'summary' => json_encode(['form_type' => 'extension_linkages']),
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

<template id="extension-linkages-row-template">
    <tr class="extension-linkages-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="partner"></textarea></td>
        <td><input type="text" data-field="linkage_forged"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="description"></textarea></td>
        <td><input type="date" data-field="effectivity_from"></td>
        <td><input type="date" data-field="effectivity_to"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="extension_activities"></textarea></td>
        <td><input type="date" data-field="conducted_from"></td>
        <td><input type="date" data-field="conducted_to"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline extension-linkages-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('extension-linkages-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowTemplate = document.getElementById('extension-linkages-row-template');
    const tbody = document.querySelector('.extension-linkages-rows');
    const addButton = document.getElementById('extension-linkages-add-row');
    const fieldNames = [
        'partner',
        'linkage_forged',
        'description',
        'effectivity_from',
        'effectivity_to',
        'extension_activities',
        'conducted_from',
        'conducted_to',
    ];

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
        if (!tbody) {
            return;
        }
        tbody.querySelectorAll('.extension-linkages-row').forEach((row, index) => assignNames(row, index));
    };

    const bindRemove = (row) => {
        row.querySelector('.extension-linkages-remove-row')?.addEventListener('click', () => {
            if (!tbody) {
                return;
            }
            if (tbody.querySelectorAll('.extension-linkages-row').length <= 1) {
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
        tbody.querySelectorAll('.extension-linkages-row').forEach((row, index) => {
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
