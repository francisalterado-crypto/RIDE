<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Inventions, UM, Copyrights | RIDE';
$pageHeading = 'Inventions, Utility Models and Copyrights';
$pageSubtitle = $isEdit
    ? 'Update your inventions, utility models, and copyrights report before saving or resubmitting.'
    : 'Quarterly report of accomplishments in research (WPU-QSF-RIDE-RDO-13).';
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

$sections = [
    'inventions_patented' => ['group' => 'Inventions', 'label' => 'A. Patented'],
    'inventions_applied_for_patenting' => ['group' => null, 'label' => 'B. Applied for Patenting'],
    'inventions_not_patented_but_utilized' => ['group' => null, 'label' => 'C. Not Patented but Utilized by the Community'],
    'utility_models_registered' => ['group' => 'Utility Models', 'label' => 'A. Registered'],
    'utility_models_applied_for_registration' => ['group' => null, 'label' => 'B. Applied for Registration'],
    'copyrights' => ['group' => 'Copyrights', 'label' => null],
];
$fieldNames = [
    'research_title',
    'date_started',
    'date_developed_completed',
    'inventors_researchers',
    'patent_registration_copyright_number',
    'date_of_issue_application',
    'adopter',
    'commercial_product_name',
    'google_drive_link',
];
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper inventions-um-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/inventions-um-copyrights') : base_url('proposals/inventions-um-copyrights') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">INVENTIONS, UTILITY MODELS AND COPYRIGHTS</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-13 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'inventions_um_copyrights',
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
                        <input type="hidden" name="college_name" id="inventions-um-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section inventions-um-section">
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap inventions-um-table-wrap">
            <table class="proposal-table inventions-um-table trainings-conducted-table">
                <colgroup>
                    <col class="ium-col-title">
                    <col class="ium-col-date">
                    <col class="ium-col-date">
                    <col class="ium-col-inventors">
                    <col class="ium-col-patent">
                    <col class="ium-col-date">
                    <col class="ium-col-adopter">
                    <col class="ium-col-product">
                    <col class="ium-col-drive">
                    <col class="ium-col-actions">
                </colgroup>
                <thead>
                    <tr>
                        <th>Research Title</th>
                        <th>Date Started</th>
                        <th>Date Developed/Completed</th>
                        <th>Inventor(s)/Researcher(s)</th>
                        <th>Patent Registration/Copyright Number</th>
                        <th>Date of Issue/Application</th>
                        <th>Adopter of Inventions/UM/Copyrights</th>
                        <th>Name of Commercial Product</th>
                        <th>Google Drive Link</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <?php foreach ($sections as $sectionKey => $section): ?>
                    <tbody class="inventions-um-rows" data-section="<?= htmlspecialchars($sectionKey) ?>">
                        <?php if (is_string($section['group']) && $section['group'] !== ''): ?>
                            <tr class="inventions-um-section-row"><td colspan="10"><?= htmlspecialchars($section['group']) ?></td></tr>
                        <?php endif; ?>
                        <?php if (is_string($section['label']) && $section['label'] !== ''): ?>
                            <tr class="inventions-um-subsection-row"><td colspan="10"><?= htmlspecialchars($section['label']) ?></td></tr>
                        <?php endif; ?>
                        <?php
                        $rows = is_array($entries[$sectionKey] ?? null) ? $entries[$sectionKey] : [];
                        if ($rows === []) {
                            $rows[] = [];
                        }
                        ?>
                        <?php foreach ($rows as $index => $row): ?>
                            <tr class="inventions-um-row">
                                <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][research_title]"><?= htmlspecialchars((string) ($row['research_title'] ?? '')) ?></textarea></td>
                                <td><input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][date_started]" value="<?= htmlspecialchars((string) ($row['date_started'] ?? '')) ?>"></td>
                                <td><input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][date_developed_completed]" value="<?= htmlspecialchars((string) ($row['date_developed_completed'] ?? '')) ?>"></td>
                                <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][inventors_researchers]"><?= htmlspecialchars((string) ($row['inventors_researchers'] ?? '')) ?></textarea></td>
                                <td><input type="text" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][patent_registration_copyright_number]" value="<?= htmlspecialchars((string) ($row['patent_registration_copyright_number'] ?? '')) ?>"></td>
                                <td><input type="date" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][date_of_issue_application]" value="<?= htmlspecialchars((string) ($row['date_of_issue_application'] ?? '')) ?>"></td>
                                <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][adopter]"><?= htmlspecialchars((string) ($row['adopter'] ?? '')) ?></textarea></td>
                                <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][commercial_product_name]"><?= htmlspecialchars((string) ($row['commercial_product_name'] ?? '')) ?></textarea></td>
                                <td class="ium-col-drive-link-cell"><input type="url" name="entries[<?= htmlspecialchars($sectionKey) ?>][<?= $index ?>][google_drive_link]" value="<?= htmlspecialchars((string) ($row['google_drive_link'] ?? '')) ?>" placeholder="https://drive.google.com/..."></td>
                                <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline inventions-um-remove-row">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="10">
                                <button type="button" class="btn btn-outline inventions-um-add-row" data-section="<?= htmlspecialchars($sectionKey) ?>">Add Row</button>
                            </td>
                        </tr>
                    </tbody>
                <?php endforeach; ?>
            </table>
        </div>
        <p class="proposal-section-note inventions-um-note">
            Note: *An invention/utility model may be utilized for: 1) development of technology, 2) service provision, or 3) an end-product in itself or it may also be commercialized for selling to other end-users.
        </p>
    </section>

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
        'summary' => json_encode(['form_type' => 'inventions_um_copyrights']),
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

<template id="inventions-um-row-template">
    <tr class="inventions-um-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="research_title"></textarea></td>
        <td><input type="date" data-field="date_started"></td>
        <td><input type="date" data-field="date_developed_completed"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="inventors_researchers"></textarea></td>
        <td><input type="text" data-field="patent_registration_copyright_number"></td>
        <td><input type="date" data-field="date_of_issue_application"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="adopter"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-field="commercial_product_name"></textarea></td>
        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-sm btn-outline inventions-um-remove-row">Remove</button></td>
    </tr>
</template>

<script>
(() => {
    const fieldNames = <?= json_encode($fieldNames) ?>;
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('inventions-um-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowTemplate = document.getElementById('inventions-um-row-template');
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
        tbody.querySelectorAll('.inventions-um-row').forEach((row, index) => {
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
        row.querySelector('.inventions-um-remove-row')?.addEventListener('click', () => {
            if (tbody.querySelectorAll('.inventions-um-row').length <= 1) {
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

    document.querySelectorAll('.inventions-um-rows').forEach((tbody) => {
        const sectionKey = tbody.dataset.section ?? '';
        tbody.querySelectorAll('.inventions-um-row').forEach((row) => bindRowRemove(tbody, row, sectionKey));
        reindexSection(tbody, sectionKey);
    });

    document.querySelectorAll('.inventions-um-add-row').forEach((button) => {
        button.addEventListener('click', () => {
            const sectionKey = button.getAttribute('data-section') ?? '';
            const tbody = document.querySelector(`.inventions-um-rows[data-section="${sectionKey}"]`);
            if (!tbody || !rowTemplate) {
                return;
            }
            const row = rowTemplate.content.firstElementChild?.cloneNode(true);
            if (!(row instanceof HTMLTableRowElement)) {
                return;
            }
            tbody.appendChild(row);
            bindRowRemove(tbody, row, sectionKey);
            reindexSection(tbody, sectionKey);
            resizeTextareas();
        });
    });

    resizeTextareas();
})();
</script>
