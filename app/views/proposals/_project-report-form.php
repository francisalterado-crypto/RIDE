<?php
/**
 * Web form of a project Progress Report (WPU-QSF-RDE-VPRDE-18) or Terminal Report (WPU-QSF-RDE-VPRDE-21).
 * Both forms have the same fields; only the form type, title and route differ.
 *
 * @var array|null $proposal
 * @var list<array> $colleges
 * @var string $collegeName
 * @var string $reportFormType 'progress_report' or 'terminal_report'
 * @var string $reportLabel    e.g. 'Progress Report'
 * @var string $reportRoute    e.g. 'progress-report'
 */
use App\Support\OfficeForms;
use App\Support\ProponentProfile;

$isEdit = $proposal !== null;
$user = \App\Core\Auth::user() ?? [];
$office = OfficeForms::get($reportFormType);
$formCode = (string) ($office['code'] ?? '');

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}
$value = static function (string $key, string $fallback = '') use ($summaryData, $isEdit): string {
    $current = $summaryData[$key] ?? null;
    return is_string($current) && ($isEdit || $current !== '') ? $current : $fallback;
};

$collegeDisplay = $value('college_name', $collegeName);
$entries = is_array($summaryData['entries'] ?? null) ? array_values($summaryData['entries']) : [];
if ($entries === []) {
    $entries[] = [];
}
$coauthors = is_array($summaryData['coauthors'] ?? null) ? array_values($summaryData['coauthors']) : [];
if ($coauthors === []) {
    $coauthors[] = [];
}

$campusValue = $value('campus', (string) ($user['campus_name'] ?? ''));
$campusNames = array_map(static fn (array $row): string => (string) $row['name'], \App\Models\College::campuses((int) ($user['college_id'] ?? 0)));
if ($campusValue !== '' && !in_array($campusValue, $campusNames, true)) {
    $campusNames[] = $campusValue;
}
$prefixValue = $value('title_prefix', (string) ($user['title_prefix'] ?? ''));
$prefixOptions = ProponentProfile::TITLE_PREFIXES;
if ($prefixValue !== '' && !in_array($prefixValue, $prefixOptions, true)) {
    $prefixOptions[] = $prefixValue;
}

$entryColumns = [
    'activity' => 'Activity',
    'target_schedule' => 'Target Schedule (based on proposal)',
    'actual_period' => 'Period of Actual Implementation / Date of Completion',
    'problems' => 'Problems Encountered / Action Required',
];
$cards = [
    'report-sec-info' => 'Report Information',
    'report-sec-proponent' => 'The Proponent',
    'report-sec-overview' => 'Project/Study Overview',
    'report-sec-work' => 'Work Completed and/or in Progress',
    'report-sec-assessment' => 'Revised Targets and General Progress',
];
$cardNumbers = array_flip(array_keys($cards));
$cardNo = static fn (string $anchor): int => ($cardNumbers[$anchor] ?? 0) + 1;
$action = $isEdit ? base_url('proposals/' . $proposal['id'] . '/' . $reportRoute) : base_url('proposals/' . $reportRoute);
?>

<form class="webform rdo-webform" id="project-report-form" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <div class="webform-hero">
        <div>
            <p class="webform-eyebrow">Office of Research and Development<?= $formCode !== '' ? ', Form ' . htmlspecialchars($formCode) : '' ?></p>
            <h2 class="webform-hero-title"><?= htmlspecialchars($reportLabel) ?></h2>
            <p class="webform-hero-text">Fill out each section below, save the draft, then submit it for review from the report page.</p>
            <?php $blankFormType = $reportFormType; require APP_PATH . '/views/partials/blank-form-link.php'; ?>
        </div>
    </div>

    <nav class="webform-steps" aria-label="Form sections">
        <?php foreach ($cards as $anchor => $label): ?>
            <a href="#<?= $anchor ?>"><span><?= $cardNo($anchor) ?></span><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <section class="webform-card" id="report-sec-info">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('report-sec-info') ?></span>
            <div>
                <h2>Report Information</h2>
                <p>The research this report covers and the reporting period.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="report-research-title">Research Title</label>
                <input id="report-research-title" name="research_title" value="<?= htmlspecialchars($value('research_title', (string) ($proposal['title'] ?? ''))) ?>" placeholder="Enter the title of the research">
            </div>
            <div class="webform-field">
                <label for="report-period-covered">Period Covered</label>
                <input id="report-period-covered" name="period_covered" value="<?= htmlspecialchars($value('period_covered')) ?>" placeholder="Enter the period covered by the research">
            </div>
            <div class="webform-field">
                <label for="report-duration">Duration in Months</label>
                <input id="report-duration" inputmode="numeric" name="duration_months" value="<?= htmlspecialchars($value('duration_months')) ?>" placeholder="Enter the number of months">
            </div>
            <div class="webform-field">
                <label for="report-funding">Funding/Support</label>
                <input id="report-funding" name="funding_support" value="<?= htmlspecialchars($value('funding_support')) ?>" placeholder="Enter the funding source or support">
            </div>
            <?php
            $periodSummary = ['form_type' => $reportFormType, 'report_as_of' => $value('report_as_of'), 'reporting_period' => $value('reporting_period')];
            (static function () use ($periodSummary, $reportFormType): void {
                $summaryData = $periodSummary;
                $quarterlyFormType = $reportFormType;
                require APP_PATH . '/views/partials/quarterly-period-field.php';
            })();
            ?>
        </div>
    </section>

    <section class="webform-card" id="report-sec-proponent">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('report-sec-proponent') ?></span>
            <div>
                <h2>I. The Proponent</h2>
                <p>Filled in from your profile. Change any detail that differs for this report.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="report-last-name">Last Name</label>
                <input id="report-last-name" name="last_name" value="<?= htmlspecialchars($value('last_name', (string) ($user['last_name'] ?? ''))) ?>">
            </div>
            <div class="webform-field">
                <label for="report-first-name">First Name</label>
                <input id="report-first-name" name="first_name" value="<?= htmlspecialchars($value('first_name', (string) ($user['first_name'] ?? ''))) ?>">
            </div>
            <div class="webform-field">
                <label for="report-middle-name">Middle Name</label>
                <input id="report-middle-name" name="middle_name" value="<?= htmlspecialchars($value('middle_name', (string) ($user['middle_name'] ?? ''))) ?>">
            </div>
            <div class="webform-field">
                <label for="report-title-prefix">Title/Prefix</label>
                <select id="report-title-prefix" name="title_prefix">
                    <option value="">Select the title/prefix</option>
                    <?php foreach ($prefixOptions as $prefix): ?>
                        <option value="<?= htmlspecialchars($prefix) ?>"<?= $prefix === $prefixValue ? ' selected' : '' ?>><?= htmlspecialchars($prefix) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="webform-field">
                <span class="webform-label">College/Department</span>
                <?php if ($collegeDisplay !== ''): ?>
                    <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                    <p class="webform-readonly"><?= htmlspecialchars($collegeDisplay) ?></p>
                <?php else: ?>
                    <select name="college_id" id="report-college-id" required aria-label="College/Department">
                        <option value="">Select the college</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>"><?= htmlspecialchars((string) $college['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="college_name" id="report-college-name" value="">
                <?php endif; ?>
            </div>
            <div class="webform-field">
                <label for="report-campus">Campus</label>
                <?php if ($campusNames !== []): ?>
                    <select id="report-campus" name="campus">
                        <option value="">Select the campus</option>
                        <?php foreach ($campusNames as $campusName): ?>
                            <option value="<?= htmlspecialchars($campusName) ?>"<?= $campusName === $campusValue ? ' selected' : '' ?>><?= htmlspecialchars($campusName) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="report-campus" name="campus" value="<?= htmlspecialchars($campusValue) ?>" placeholder="Enter the campus">
                <?php endif; ?>
            </div>
            <div class="webform-field">
                <label for="report-email">Email</label>
                <input id="report-email" type="email" name="email" value="<?= htmlspecialchars($value('email', (string) ($user['email'] ?? ''))) ?>">
            </div>
            <div class="webform-field">
                <label for="report-contact">Contact Number</label>
                <input id="report-contact" name="contact_number" value="<?= htmlspecialchars($value('contact_number', (string) ($user['contact_number'] ?? ''))) ?>">
            </div>
            <div class="webform-field">
                <label for="report-scholar">Google Scholar Account Link</label>
                <input id="report-scholar" type="url" name="google_scholar_link" value="<?= htmlspecialchars($value('google_scholar_link', (string) ($user['google_scholar_link'] ?? ''))) ?>" placeholder="Enter the profile link">
            </div>
            <div class="webform-field">
                <label for="report-researchgate">ResearchGate Account Link</label>
                <input id="report-researchgate" type="url" name="researchgate_link" value="<?= htmlspecialchars($value('researchgate_link', (string) ($user['researchgate_link'] ?? ''))) ?>" placeholder="Enter the profile link">
            </div>
        </div>

        <h3 class="wf-view-subhead">Co-Author(s)</h3>
        <div class="webform-repeater" data-report-rows="coauthors">
            <?php foreach ($coauthors as $index => $row): ?>
                <div class="webform-item">
                    <div class="webform-item-head">
                        <span class="webform-item-label">Co-Author</span>
                        <button type="button" class="webform-icon-btn" data-remove-row title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                    <div class="webform-grid">
                        <?php foreach (['last_name' => 'Last Name', 'first_name' => 'First Name', 'middle_name' => 'Middle Name'] as $column => $columnLabel): ?>
                            <div class="webform-field">
                                <label><?= $columnLabel ?></label>
                                <input name="coauthors[<?= (int) $index ?>][<?= $column ?>]" value="<?= htmlspecialchars((string) ($row[$column] ?? '')) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="webform-add-btn" data-add-row="coauthors">Add co-author</button>
    </section>

    <section class="webform-card" id="report-sec-overview">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('report-sec-overview') ?></span>
            <div><h2>Project/Study Overview</h2></div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="report-overview">Overview</label>
                <textarea id="report-overview" name="project_overview" rows="4" data-autoresize placeholder="Enter a brief overview of the project/study"><?= htmlspecialchars($value('project_overview')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card" id="report-sec-work">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('report-sec-work') ?></span>
            <div>
                <h2>Work Completed and/or in Progress</h2>
                <p>Add one entry for each activity.</p>
            </div>
        </header>
        <div class="webform-repeater" data-report-rows="entries">
            <?php foreach ($entries as $index => $row): ?>
                <div class="webform-item">
                    <div class="webform-item-head">
                        <span class="webform-item-label">Activity</span>
                        <button type="button" class="webform-icon-btn" data-remove-row title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                    <div class="webform-grid">
                        <?php foreach ($entryColumns as $column => $columnLabel): ?>
                            <div class="webform-field<?= $column === 'activity' || $column === 'problems' ? ' webform-field--full' : '' ?>">
                                <label><?= htmlspecialchars($columnLabel) ?></label>
                                <textarea rows="<?= $column === 'problems' ? 2 : 1 ?>" data-autoresize name="entries[<?= (int) $index ?>][<?= $column ?>]"><?= htmlspecialchars((string) ($row[$column] ?? '')) ?></textarea>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="webform-add-btn" data-add-row="entries">Add activity</button>
    </section>

    <section class="webform-card" id="report-sec-assessment">
        <header class="webform-card-head">
            <span class="webform-card-num"><?= $cardNo('report-sec-assessment') ?></span>
            <div><h2>Revised Targets and General Progress</h2></div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="report-revised">Revised targets if the project is delayed/advanced</label>
                <textarea id="report-revised" name="revised_targets" rows="3" data-autoresize><?= htmlspecialchars($value('revised_targets')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="report-general">How the project/study is going in general</label>
                <textarea id="report-general" name="general_progress" rows="4" data-autoresize><?= htmlspecialchars($value('general_progress')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <?php
        $workflowProposal = $proposal ?? [
            'status' => 'draft',
            'project_type' => 'research',
            'summary' => json_encode(['form_type' => $reportFormType]),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
        ];
        $workflowSteps = proposal_workflow_steps($workflowProposal);
        require APP_PATH . '/views/proposals/_approval-workflow.php';
        ?>
    </section>

    <div class="webform-actionbar">
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . (int) $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
    </div>
</form>

<script>
(() => {
    const form = document.getElementById('project-report-form');
    const resize = (textarea) => {
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    };
    const bindResize = (root) => root.querySelectorAll('textarea[data-autoresize]').forEach((textarea) => {
        resize(textarea);
        textarea.addEventListener('input', () => resize(textarea));
    });
    bindResize(form);

    const collegeSelect = document.getElementById('report-college-id');
    const collegeName = document.getElementById('report-college-name');
    if (collegeSelect && collegeName) {
        const sync = () => { collegeName.value = collegeSelect.options[collegeSelect.selectedIndex]?.dataset?.name ?? ''; };
        collegeSelect.addEventListener('change', sync);
        sync();
    }

    // Repeaters: renumber names after adding or removing an entry.
    const renumber = (repeater) => {
        repeater.querySelectorAll('.webform-item').forEach((item, index) => {
            item.querySelectorAll('[name]').forEach((control) => {
                control.name = control.name.replace(/\[\d+\]/, `[${index}]`);
            });
        });
    };
    form.querySelectorAll('[data-add-row]').forEach((button) => {
        button.addEventListener('click', () => {
            const repeater = form.querySelector(`[data-report-rows="${button.dataset.addRow}"]`);
            const last = repeater?.lastElementChild;
            if (!last) {
                return;
            }
            const item = last.cloneNode(true);
            item.querySelectorAll('input, textarea').forEach((control) => { control.value = ''; });
            repeater.appendChild(item);
            renumber(repeater);
            bindResize(item);
        });
    });
    form.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-row]');
        if (!remove) {
            return;
        }
        const repeater = remove.closest('[data-report-rows]');
        const item = remove.closest('.webform-item');
        if (repeater.querySelectorAll('.webform-item').length > 1) {
            item.remove();
            renumber(repeater);
        } else {
            item.querySelectorAll('input, textarea').forEach((control) => { control.value = ''; });
            item.querySelectorAll('textarea').forEach(resize);
        }
    });
})();
</script>
