<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Progress Report | RIDE';
$pageHeading = 'Progress Report';
$pageSubtitle = $isEdit
    ? 'Update your progress report before saving or resubmitting.'
    : 'Quarterly project progress report (WPU-QSF-RIDE-RDO-17).';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$value = static function (string $key, string $fallback = '') use ($summaryData): string {
    $current = $summaryData[$key] ?? null;
    return is_string($current) ? $current : $fallback;
};

$collegeDisplay = $value('college_name', $collegeName);
$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
if ($entries === []) {
    $entries[] = ['activity' => '', 'target_schedule' => '', 'actual_period' => '', 'problems' => ''];
}

$coauthors = is_array($summaryData['coauthors'] ?? null) ? $summaryData['coauthors'] : [];
if ($coauthors === []) {
    $coauthors[] = ['last_name' => '', 'first_name' => '', 'middle_name' => ''];
}
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper progress-report-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/progress-report') : base_url('proposals/progress-report') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">PROGRESS REPORT</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-17 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
            <tr>
                <th>Research Title</th>
                <td colspan="3"><input name="research_title" value="<?= htmlspecialchars($value('research_title', (string) ($proposal['title'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Period Covered</th>
                <td><input name="period_covered" value="<?= htmlspecialchars($value('period_covered')) ?>"></td>
                <th>Duration in Months</th>
                <td><input name="duration_months" value="<?= htmlspecialchars($value('duration_months')) ?>"></td>
            </tr>
            <tr>
                <th>Funding/ Support</th>
                <td colspan="3"><input name="funding_support" value="<?= htmlspecialchars($value('funding_support')) ?>"></td>
            </tr>
                        <?php
            $summaryData = [
                'form_type' => 'progress_report',
                'report_as_of' => $value('report_as_of'),
                'reporting_period' => quarterly_reporting_period_from_summary(['report_as_of' => $value('report_as_of')]),
            ];
            $colspanRow = true;
            require APP_PATH . '/views/partials/quarterly-reporting-period.php';
            ?>
        </table>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">I. THE PROPONENT</h3>
        <table class="proposal-table">
            <tr>
                <th>Full Name</th>
                <td><input name="last_name" placeholder="Last Name" value="<?= htmlspecialchars($value('last_name', (string) ($user['last_name'] ?? ''))) ?>"></td>
                <td><input name="first_name" placeholder="First Name" value="<?= htmlspecialchars($value('first_name', (string) ($user['first_name'] ?? ''))) ?>"></td>
                <td><input name="middle_name" placeholder="Middle Name" value="<?= htmlspecialchars($value('middle_name', (string) ($user['middle_name'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Title/Prefix</th>
                <td colspan="3"><input name="title_prefix" value="<?= htmlspecialchars($value('title_prefix')) ?>"></td>
            </tr>
            <tr>
                <th>College/Department</th>
                <td colspan="3">
                    <?php if ($collegeDisplay !== ''): ?>
                        <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                        <span><?= htmlspecialchars($collegeDisplay) ?></span>
                    <?php else: ?>
                        <select name="college_id" id="progress-report-college-id" required>
                            <option value="">Select college</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>">
                                    <?= htmlspecialchars((string) $college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="college_name" id="progress-report-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Campus</th>
                <td colspan="3"><input name="campus" value="<?= htmlspecialchars($value('campus', (string) ($user['campus_name'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Email</th>
                <td colspan="3"><input type="email" name="email" value="<?= htmlspecialchars($value('email', (string) ($user['email'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Contact Number</th>
                <td colspan="3"><input name="contact_number" value="<?= htmlspecialchars($value('contact_number', (string) ($user['contact_number'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Google Scholar account link</th>
                <td colspan="3"><input name="google_scholar_link" value="<?= htmlspecialchars($value('google_scholar_link', (string) ($user['google_scholar_link'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>ResearchGate account link</th>
                <td colspan="3"><input name="researchgate_link" value="<?= htmlspecialchars($value('researchgate_link', (string) ($user['researchgate_link'] ?? ''))) ?>"></td>
            </tr>
        </table>

        <div class="trainings-conducted-section">
            <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
            <div class="proposal-table-wrap trainings-conducted-table-wrap">
                <table class="proposal-table trainings-conducted-table proposal-coauthors-table">
                    <thead>
                        <tr>
                            <th>Co-Author(s)</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Middle Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coauthors as $index => $row): ?>
                            <tr>
                                <td></td>
                                <td><input name="coauthors[<?= (int) $index ?>][last_name]" value="<?= htmlspecialchars((string) ($row['last_name'] ?? '')) ?>"></td>
                                <td><input name="coauthors[<?= (int) $index ?>][first_name]" value="<?= htmlspecialchars((string) ($row['first_name'] ?? '')) ?>"></td>
                                <td><input name="coauthors[<?= (int) $index ?>][middle_name]" value="<?= htmlspecialchars((string) ($row['middle_name'] ?? '')) ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Project/Study Overview</h3>
        <textarea class="proposal-textarea" rows="3" name="project_overview"><?= htmlspecialchars($value('project_overview')) ?></textarea>

    </section>

    <section class="proposal-section trainings-conducted-section">
        <h3 class="proposal-subtitle">Work/s Completed and/or in Progress</h3>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table">
                <thead>
                    <tr>
                        <th>Activities</th>
                        <th>Target Schedule (based on proposal)</th>
                        <th>Period of Actual Implementation/Date of Completion</th>
                        <th>Problems Encountered/Action Required</th>
                        <th class="completed-researches-actions-col"></th>
                    </tr>
                </thead>
                <tbody id="progress-report-rows">
                    <?php foreach ($entries as $index => $row): ?>
                        <tr class="progress-report-row">
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][activity]"><?= htmlspecialchars((string) ($row['activity'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][target_schedule]"><?= htmlspecialchars((string) ($row['target_schedule'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][actual_period]"><?= htmlspecialchars((string) ($row['actual_period'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" name="entries[<?= (int) $index ?>][problems]"><?= htmlspecialchars((string) ($row['problems'] ?? '')) ?></textarea></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-sm btn-outline progress-report-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="proposal-coauthors-actions completed-researches-add-row-wrap">
            <button type="button" id="progress-report-add-row" class="btn btn-outline btn-sm">+ Add Row</button>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Revised targets if the project is delayed/advanced</h3>
        <textarea class="proposal-textarea" rows="3" name="revised_targets"><?= htmlspecialchars($value('revised_targets')) ?></textarea>

        <h3 class="proposal-subtitle">How the project/study is going in general</h3>
        <textarea class="proposal-textarea" rows="5" name="general_progress"><?= htmlspecialchars($value('general_progress')) ?></textarea>
    </section>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => 'research',
        'summary' => json_encode(['form_type' => 'progress_report']),
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

<script>
(() => {
    const collegeSelect = document.getElementById('progress-report-college-id');
    const collegeNameInput = document.getElementById('progress-report-college-name');
    if (collegeSelect && collegeNameInput) {
        const syncCollegeName = () => {
            const option = collegeSelect.options[collegeSelect.selectedIndex];
            collegeNameInput.value = option?.dataset?.name ?? '';
        };
        collegeSelect.addEventListener('change', syncCollegeName);
        syncCollegeName();
    }

    const rowsBody = document.getElementById('progress-report-rows');
    const addRowButton = document.getElementById('progress-report-add-row');
    const fieldNames = ['activity', 'target_schedule', 'actual_period', 'problems'];

    const createRow = () => {
        const row = document.createElement('tr');
        row.className = 'progress-report-row';
        row.innerHTML = `
            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2"></textarea></td>
            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2"></textarea></td>
            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2"></textarea></td>
            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2"></textarea></td>
            <td class="completed-researches-actions-col">
                <button type="button" class="btn btn-sm btn-outline progress-report-remove-row">Remove</button>
            </td>
        `;
        return row;
    };

    const reindexRows = () => {
        if (!rowsBody) {
            return;
        }
        rowsBody.querySelectorAll('.progress-report-row').forEach((row, index) => {
            const controls = row.querySelectorAll('textarea');
            controls.forEach((control, fieldIndex) => {
                const field = fieldNames[fieldIndex];
                control.name = `entries[${index}][${field}]`;
            });
        });
    };

    if (rowsBody && addRowButton) {
        addRowButton.addEventListener('click', () => {
            rowsBody.appendChild(createRow());
            reindexRows();
        });

        rowsBody.addEventListener('click', (event) => {
            const button = event.target.closest('.progress-report-remove-row');
            if (!button) {
                return;
            }
            const row = button.closest('.progress-report-row');
            if (!row) {
                return;
            }

            const existingRows = rowsBody.querySelectorAll('.progress-report-row');
            if (existingRows.length <= 1) {
                row.querySelectorAll('textarea').forEach((textarea) => {
                    textarea.value = '';
                });
                return;
            }

            row.remove();
            reindexRows();
        });

        reindexRows();
    }
})();
</script>
