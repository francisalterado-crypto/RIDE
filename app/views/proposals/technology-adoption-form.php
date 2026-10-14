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
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Technology Adoption | RIDE';
$pageHeading = 'Technology Adoption';
$pageSubtitle = $isEdit
    ? 'Update your quarterly technology adoption report before saving or resubmitting.'
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
$demonstrationFarmEntries = is_array($summaryData['demonstration_farm_entries'] ?? null) ? $summaryData['demonstration_farm_entries'] : [];
$trainingEntries = is_array($summaryData['training_entries'] ?? null) ? $summaryData['training_entries'] : [];
$challenges = is_string($summaryData['challenges'] ?? null) ? $summaryData['challenges'] : '';
$bestPractices = is_string($summaryData['best_practices'] ?? null) ? $summaryData['best_practices'] : '';
$lessonsLearned = is_string($summaryData['lessons_learned'] ?? null) ? $summaryData['lessons_learned'] : '';
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper" method="post" enctype="multipart/form-data" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/technology-adoption') : base_url('proposals/technology-adoption') ?>">
    <?= csrf_field() ?>
    <?php if (proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="extension">
    <?php endif; ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishment in Extension</p>
        <h2 class="completed-researches-title">TECHNOLOGY ADOPTION</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-10 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
                <?php
                $summaryData = [
                    'form_type' => 'technology_adoption',
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
                        <input type="hidden" name="college_name" id="technology-adoption-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php $readOnly = false; require APP_PATH . '/views/proposals/_technology-adoption-tables.php'; ?>

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

    <?php require APP_PATH . '/views/proposals/_technology-adoption-notes.php'; ?>

    <?php
    $allowUpload = $allowDocumentUpload;
    require APP_PATH . '/views/proposals/_technology-adoption-supporting-documents.php';
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
        'summary' => json_encode(['form_type' => 'technology_adoption']),
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

<template id="technology-adoption-demo-row-template">
    <tr class="technology-adoption-demo-row">
        <td class="ta-col-no"><span class="ta-row-num"></span></td>
        <td><input type="text" data-field="extension_personnel"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="demonstration_farm"></textarea></td>
        <td><input type="text" data-field="year_established" placeholder="e.g. 2024"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="technology_demonstrated"></textarea></td>
        <td><input type="text" data-field="adopter_name"></td>
        <td><input type="text" data-field="adopter_location"></td>
        <td><input type="date" data-field="date_adopted"></td>
        <td><input type="date" data-field="date_commercialized"></td>
        <td><input type="text" data-field="net_income"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline technology-adoption-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<template id="technology-adoption-training-row-template">
    <tr class="technology-adoption-training-row">
        <td class="ta-col-no"><span class="ta-row-num"></span></td>
        <td><input type="text" data-field="extension_personnel"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="extension_service_title"></textarea></td>
        <td><input type="date" data-field="date_conducted"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="technology_demonstrated"></textarea></td>
        <td><input type="text" data-field="adopter_name"></td>
        <td><input type="text" data-field="adopter_location"></td>
        <td><input type="date" data-field="date_adopted"></td>
        <td><input type="date" data-field="date_commercialized"></td>
        <td><input type="text" data-field="net_income"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline technology-adoption-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('technology-adoption-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const sectionConfig = {
        demonstration_farm: {
            tableSelector: '.technology-adoption-demo-table',
            rowClass: 'technology-adoption-demo-row',
            templateId: 'technology-adoption-demo-row-template',
            namePrefix: 'demonstration_farm_entries',
            contentFields: [
                'extension_personnel', 'demonstration_farm', 'year_established',
                'technology_demonstrated', 'adopter_name', 'adopter_location',
                'date_adopted', 'date_commercialized', 'net_income',
            ],
        },
        training: {
            tableSelector: '.technology-adoption-training-table',
            rowClass: 'technology-adoption-training-row',
            templateId: 'technology-adoption-training-row-template',
            namePrefix: 'training_entries',
            contentFields: [
                'extension_personnel', 'extension_service_title', 'date_conducted',
                'technology_demonstrated', 'adopter_name', 'adopter_location',
                'date_adopted', 'date_commercialized', 'net_income',
            ],
        },
    };

    const rowHasContent = (row, fields) => {
        for (const field of fields) {
            const input = row.querySelector(`[name$="[${field}]"], [data-field="${field}"]`);
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
        const demoTable = document.querySelector(sectionConfig.demonstration_farm.tableSelector);
        const trainingTable = document.querySelector(sectionConfig.training.tableSelector);
        let totalFarms = 0;
        let totalCommercialized = 0;

        demoTable?.querySelectorAll(sectionConfig.demonstration_farm.rowClass).forEach((row) => {
            if (rowHasContent(row, sectionConfig.demonstration_farm.contentFields)) {
                totalFarms += 1;
            }
            const commercialized = row.querySelector('[name$="[date_commercialized]"]');
            if (commercialized instanceof HTMLInputElement && commercialized.value.trim() !== '') {
                totalCommercialized += 1;
            }
        });

        trainingTable?.querySelectorAll(sectionConfig.training.rowClass).forEach((row) => {
            const commercialized = row.querySelector('[name$="[date_commercialized]"]');
            if (commercialized instanceof HTMLInputElement && commercialized.value.trim() !== '') {
                totalCommercialized += 1;
            }
        });

        const farmsEl = document.querySelector('.ta-total-farms');
        const commercializedEl = document.querySelector('.ta-total-commercialized');
        if (farmsEl) {
            farmsEl.textContent = String(totalFarms);
        }
        if (commercializedEl) {
            commercializedEl.textContent = String(totalCommercialized);
        }
    };

    const renumberRows = (table, rowClass) => {
        table?.querySelectorAll(`.${rowClass}`).forEach((row, index) => {
            const num = row.querySelector('.ta-row-num');
            if (num) {
                num.textContent = String(index + 1);
            }
        });
    };

    const assignNames = (row, index, namePrefix, fields) => {
        fields.forEach((field) => {
            const input = row.querySelector(`[data-field="${field}"]`);
            if (!input) {
                return;
            }
            input.name = `${namePrefix}[${index}][${field}]`;
            input.removeAttribute('data-field');
        });
    };

    const reindexSection = (sectionKey) => {
        const config = sectionConfig[sectionKey];
        const table = document.querySelector(config.tableSelector);
        if (!table) {
            return;
        }
        const rows = table.querySelectorAll(`.${config.rowClass}`);
        rows.forEach((row, index) => assignNames(row, index, config.namePrefix, config.contentFields));
        renumberRows(table, config.rowClass);
        updateTotals();
    };

    const bindRow = (row, sectionKey) => {
        const config = sectionConfig[sectionKey];
        row.querySelectorAll('input, textarea').forEach((input) => {
            input.addEventListener('input', updateTotals);
        });
        row.querySelector('.technology-adoption-remove-row')?.addEventListener('click', () => {
            const table = document.querySelector(config.tableSelector);
            const rows = table?.querySelectorAll(`.${config.rowClass}`) ?? [];
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
            reindexSection(sectionKey);
        });
    };

    Object.keys(sectionConfig).forEach((sectionKey) => {
        const config = sectionConfig[sectionKey];
        const table = document.querySelector(config.tableSelector);
        table?.querySelectorAll(`.${config.rowClass}`).forEach((row) => bindRow(row, sectionKey));
        reindexSection(sectionKey);
    });

    document.querySelectorAll('.technology-adoption-add-row').forEach((button) => {
        button.addEventListener('click', () => {
            const sectionKey = button.getAttribute('data-section');
            const config = sectionConfig[sectionKey];
            if (!config) {
                return;
            }
            const table = document.querySelector(config.tableSelector);
            const template = document.getElementById(config.templateId);
            const summaryRow = table?.querySelector('.technology-adoption-summary-row');
            const row = template?.content.firstElementChild?.cloneNode(true);
            if (!(row instanceof HTMLTableRowElement) || !table) {
                return;
            }
            if (summaryRow) {
                table.querySelector('tbody')?.insertBefore(row, summaryRow);
            } else {
                table.querySelector('tbody')?.appendChild(row);
            }
            bindRow(row, sectionKey);
            reindexSection(sectionKey);
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
