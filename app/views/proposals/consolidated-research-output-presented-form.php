<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Consolidated Research Output Presented — RIDE IMS';
$pageHeading = 'Consolidated Research Output Presented';
$pageSubtitle = $isEdit
    ? 'Update the consolidated research output presented report before saving or resubmitting.'
    : 'College consolidation of faculty research output presented approved by the RDO Director (WPU-QSF-RIDE-RDO-08).';
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
    \App\Services\ResearchOutputPresentedConsolidation::entriesHaveRows($prefilledEntries)
    && !\App\Services\ResearchOutputPresentedConsolidation::entriesHaveRows($entries)
) {
    $entries = $prefilledEntries;
}

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

<form class="proposal-paper completed-researches-paper trainings-conducted-paper consolidated-research-output-presented-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/consolidated-research-output-presented') : base_url('proposals/consolidated-research-output-presented') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">CONSOLIDATED RESEARCH OUTPUT PRESENTED</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-08 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
                <?php
                $summaryData = [
                    'form_type' => 'consolidated_research_output_presented',
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
                        <input type="hidden" name="college_name" id="consolidated-research-output-presented-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <?php if (\App\Services\ResearchOutputPresentedConsolidation::entriesHaveRows($entries)): ?>
        <p class="proposal-section-note">Rows below include faculty research output presented for your college, deposited after RDO Director approval.</p>
    <?php elseif (!$isEdit): ?>
        <p class="proposal-section-note">No faculty research output presented approved by the RDO Director was found yet. Rows are added automatically when the RDO Director approves each report.</p>
    <?php endif; ?>

    <?php foreach ($fundingSections as $sectionKey => $sectionLabel): ?>
        <?php
        $rows = $sectionRows($sectionKey);
        $readOnly = false;
        require APP_PATH . '/views/proposals/_consolidated-research-output-presented-entries.php';
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
        'summary' => json_encode(['form_type' => 'consolidated_research_output_presented']),
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

<template id="consolidated-research-output-presented-row-template">
    <tr class="consolidated-research-output-presented-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Research title" data-field="research_title"></textarea></td>
        <td><input type="date" data-field="date_started"></td>
        <td><input type="date" data-field="date_completed"></td>
        <td><input type="number" min="0" step="1" placeholder="Months" data-field="duration_months"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Researcher(s)" data-field="researchers"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Title of research paper" data-field="paper_title"></textarea></td>
        <td><input type="text" placeholder="Name of presenter" data-field="presenter_name"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" placeholder="Title of conference/forum" data-field="conference_title"></textarea></td>
        <td><input type="text" placeholder="Venue" data-field="venue"></td>
        <td><input type="date" data-field="presentation_date"></td>
        <td><input type="text" placeholder="Organizer" data-field="organizer"></td>
        <td>
            <select data-field="conference_type">
                <option value="">Select type</option>
                <option value="local">Local</option>
                <option value="national">National</option>
                <option value="international">International</option>
            </select>
        </td>
        <td>
            <select data-field="presentation_type">
                <option value="">Select type</option>
                <option value="oral">Oral</option>
                <option value="poster">Poster</option>
            </select>
        </td>
        <td><input type="text" placeholder="Award received" data-field="award_received"></td>
        <td><input type="text" data-field="college"></td>
        <td><input type="text" data-field="campus"></td>
        <td><input type="url" placeholder="https://drive.google.com/..." data-field="google_drive_link"></td>
        <td class="completed-researches-actions-col">
            <button type="button" class="btn btn-sm btn-outline consolidated-research-output-presented-remove-row" title="Remove row">Remove</button>
        </td>
    </tr>
</template>

<script>
(() => {
    const collegeSelect = document.querySelector('select[name="college_id"]');
    const collegeNameInput = document.getElementById('consolidated-research-output-presented-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowTemplate = document.getElementById('consolidated-research-output-presented-row-template');
    const fieldNames = [
        'research_title',
        'date_started',
        'date_completed',
        'duration_months',
        'researchers',
        'paper_title',
        'presenter_name',
        'conference_title',
        'venue',
        'presentation_date',
        'organizer',
        'conference_type',
        'presentation_type',
        'award_received',
        'college',
        'campus',
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

        const removeButton = row.querySelector('.consolidated-research-output-presented-remove-row');
        removeButton?.addEventListener('click', () => {
            const tbody = row.closest('.consolidated-research-output-presented-rows');
            if (!tbody || tbody.querySelectorAll('.consolidated-research-output-presented-row').length <= 1) {
                row.querySelectorAll('input, textarea, select').forEach((input) => {
                    if (input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement || input instanceof HTMLSelectElement) {
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
        tbody.querySelectorAll('.consolidated-research-output-presented-row').forEach((row, index) => {
            fieldNames.forEach((field) => {
                const input = row.querySelector(`[name*="[${field}]"]`);
                if (input) {
                    input.name = `entries[${sectionKey}][${index}][${field}]`;
                }
            });
        });
    };

    document.querySelectorAll('.consolidated-research-output-presented-rows').forEach((tbody) => {
        const sectionKey = tbody.dataset.section ?? '';
        tbody.querySelectorAll('.consolidated-research-output-presented-row').forEach((row, index) => {
            bindRow(row, sectionKey, index);
        });
    });

    document.querySelectorAll('.consolidated-research-output-presented-add-row').forEach((button) => {
        button.addEventListener('click', () => {
            const sectionKey = button.dataset.section ?? '';
            const tbody = document.querySelector(`.consolidated-research-output-presented-rows[data-section="${sectionKey}"]`);
            if (!tbody || !rowTemplate) {
                return;
            }
            const index = tbody.querySelectorAll('.consolidated-research-output-presented-row').length;
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
