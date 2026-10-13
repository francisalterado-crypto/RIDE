<?php
/** @var array|null $proposal */
/** @var string|null $lockedProjectType */
/** @var list<array<string, mixed>> $colleges */
/** @var list<array{id: int, label: string, name: string, college_name: string, department: string, department_label?: string}> $facultyTeamOptions */

$isEdit = $proposal !== null;
$lockedProjectType = $isEdit ? 'extension' : ($lockedProjectType ?? proposal_nav_scope());
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Extension Program/Project Proposal — RIDE IMS';
$pageHeading = 'Research Extension';
$pageSubtitle = $isEdit
    ? 'Update your Extension Program/Project Proposal before saving or resubmitting.'
    : 'Complete the Office of Extension Services program/project proposal (WPU-QSF-RIDE-ESO-03).';
$user = \App\Core\Auth::user() ?? [];
$colleges = $colleges ?? [];
$facultyTeamOptions = $facultyTeamOptions ?? [];
$collegeExtensionProgramOptions = $collegeExtensionProgramOptions ?? [];
$deanNamesByCollege = $deanNamesByCollege ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$field = static function (string $key, string $fallback = '') use ($summaryData): string {
    if (isset($summaryData[$key]) && is_string($summaryData[$key])) {
        return $summaryData[$key];
    }

    return $fallback;
};

$legacyLeaderName = trim(implode(' ', array_filter([
    $field('program_leader_first_name', $field('applicant_first_name')),
    $field('program_leader_middle_name', $field('applicant_middle_name')),
    $field('program_leader_last_name', $field('applicant_last_name')),
], static fn (string $part): bool => $part !== '')));

$userName = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
$userCollege = (string) ($user['college_name'] ?? '');
if ($userCollege === '') {
    $userCollegeId = (int) ($user['college_id'] ?? 0);
    foreach ($colleges as $college) {
        if ((int) ($college['id'] ?? 0) === $userCollegeId) {
            $userCollege = (string) ($college['name'] ?? '');
            break;
        }
    }
}

$padRows = static function (mixed $rows, array $blank, int $min = 1): array {
    $normalized = [];
    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = array_merge($blank, $row);
            }
        }
    }
    while (count($normalized) < $min) {
        $normalized[] = $blank;
    }

    return $normalized;
};

$sdgOptions = [
    'Goal 1: No Poverty',
    'Goal 2: Zero Hunger',
    'Goal 3: Good Health and Well-Being',
    'Goal 4: Quality Education',
    'Goal 5: Gender Equality',
    'Goal 6: Clean Water and Sanitation',
    'Goal 7: Affordable and Clean Energy',
    'Goal 8: Decent Work and Economic Growth',
    'Goal 9: Industry, Innovation and Infrastructure',
    'Goal 10: Reduced Inequalities',
    'Goal 11: Sustainable Cities and Communities',
    'Goal 12: Responsible Consumption and Production',
    'Goal 13: Climate Action',
    'Goal 14: Life Below Water',
    'Goal 15: Life on Land',
    'Goal 16: Peace, Justice and Strong Institutions',
    'Goal 17: Partnerships for the Goals',
];

$titleValue = (string) ($proposal['title'] ?? '');
$collegeProgramValue = $field('college_extension_program');
$teamLeaderValue = $field('project_team_leader', $legacyLeaderName !== '' ? $legacyLeaderName : ($isEdit ? '' : $userName));
$membersTrainersValue = $field('members_trainers');
$implementingCollegeValue = $field(
    'implementing_college_department',
    $field('program_leader_college', $field('applicant_college_department', $isEdit ? '' : $userCollege))
);
$collaboratingOrgsValue = $field('collaborating_organizations');
$beneficiariesValue = $field('beneficiaries');
$maleBeneficiariesValue = $field('male_beneficiaries');
$femaleBeneficiariesValue = $field('female_beneficiaries');
$durationValue = $field('duration_inclusive_dates');
$durationStartValue = $field('duration_start_date');
$durationEndValue = $field('duration_end_date');
$locationValue = $field('location');
$budgetValue = $field('budget', $field('program_summary_total_amount'));
$sourceOfFundValue = $field('source_of_fund', $field('program_summary_total_source', (string) ($proposal['funding_source'] ?? '')));
$rationaleValue = $field('rationale', $field('introduction'));
$generalObjectiveValue = $field('general_objective', $field('objectives'));
$specificObjectivesValue = $field('specific_objectives');
$communityAnalysisValue = $field('community_analysis');
$problemAnalysisValue = $field('problem_analysis');
$targetGroupValue = $field('target_group_description');
$methodologyValue = $field('methodology');
$activitiesNarrativeValue = $field('activities_narrative', $field('expected_outputs', $field('expected_outcomes')));
$referencesValue = $field('references');
$proponentNameValue = $field('proponent_name', $isEdit ? '' : $userName);
$proponentDateValue = $field('proponent_date', $isEdit ? '' : date('Y-m-d'));
$deanNameValue = $field('dean_name');
$deanDateValue = $field('dean_date');
$adminNameValue = $field('extension_admin_name');
$adminDateValue = $field('extension_admin_date');

$collegeOptions = array_values(array_filter(
    array_map(static fn (array $college): string => trim((string) ($college['name'] ?? '')), $colleges),
    static fn (string $name): bool => $name !== ''
));
$hasLegacyCollege = $implementingCollegeValue !== '' && !in_array($implementingCollegeValue, $collegeOptions, true);

$partnerships = $padRows($summaryData['partnerships'] ?? [], [
    'partner' => '',
    'task_description' => '',
    'area_of_responsibility' => '',
    'resource_sharing' => '',
], 1);

$blankTeamDuty = [
    'user_id' => '',
    'member' => '',
    'college' => '',
    'department' => '',
    'role' => '',
    'task_description' => '',
    'responsibility' => '',
];
$defaultTeamDuties = [
    array_merge($blankTeamDuty, ['role' => 'Project Leader']),
    array_merge($blankTeamDuty, ['role' => 'Member 1']),
    array_merge($blankTeamDuty, ['role' => 'Member 2']),
];
$teamDuties = is_array($summaryData['team_duties'] ?? null) && $summaryData['team_duties'] !== []
    ? $padRows($summaryData['team_duties'], $blankTeamDuty, 1)
    : $defaultTeamDuties;

$facultyGroups = [];
$facultyCollegeFilters = [];
$facultyDepartmentFilters = [];
foreach ($facultyTeamOptions as $facultyOption) {
    $collegeKey = trim((string) ($facultyOption['college_name'] ?? ''));
    if ($collegeKey === '') {
        $collegeKey = 'Unassigned college';
    }
    $departmentKey = trim((string) ($facultyOption['department_label'] ?? $facultyOption['department'] ?? ''));
    if ($departmentKey === '') {
        $departmentKey = 'No department/program assigned';
    }
    $groupKey = $collegeKey . ' — ' . $departmentKey;
    $facultyGroups[$groupKey][] = $facultyOption;
    $facultyCollegeFilters[$collegeKey] = true;
    $facultyDepartmentFilters[$departmentKey] = true;
}
ksort($facultyGroups);
$facultyCollegeFilters = array_keys($facultyCollegeFilters);
$facultyDepartmentFilters = array_keys($facultyDepartmentFilters);
sort($facultyCollegeFilters);
sort($facultyDepartmentFilters);

$logicalFramework = $padRows($summaryData['logical_framework'] ?? [], [
    'inputs' => '',
    'activities' => '',
    'outputs' => '',
    'effects' => '',
    'outcomes' => '',
    'impact' => '',
    'sdg' => '',
], 1);

$workPlan = $padRows($summaryData['work_plan'] ?? [], [
    'activities' => '',
    'objective' => '',
    'indicator' => '',
    'strategies' => '',
    'time_frame' => '',
    'responsible_persons' => '',
    'budget_needed' => '',
    'outputs' => '',
], 1);

$budgetGeneral = $padRows($summaryData['budget_general'] ?? [], [
    'item' => '',
    'particulars' => '',
    'amount' => '',
], 3);

$blankBudgetItem = ['item_no' => '', 'particulars' => '', 'qty' => '', 'unit' => '', 'cost_unit' => '', 'total_cost' => ''];
$budgetLineGroups = is_array($summaryData['budget_line_groups'] ?? null) ? $summaryData['budget_line_groups'] : [];
if ($budgetLineGroups === []) {
    $budgetLineGroups = [
        ['label' => 'Line-Item Budget 1', 'items' => [$blankBudgetItem, $blankBudgetItem]],
        ['label' => 'Line-Item Budget 2', 'items' => [$blankBudgetItem, $blankBudgetItem]],
    ];
} else {
    foreach ($budgetLineGroups as $groupIndex => $group) {
        $items = is_array($group['items'] ?? null) ? $group['items'] : [];
        $budgetLineGroups[$groupIndex] = [
            'label' => trim((string) ($group['label'] ?? '')) !== ''
                ? (string) $group['label']
                : 'Line-Item Budget ' . ($groupIndex + 1),
            'items' => $padRows($items, $blankBudgetItem, 2),
        ];
    }
}
$formSections = [
    'eso-sec-identifying' => 'Identifying Information',
    'eso-sec-rationale' => 'Rationale',
    'eso-sec-objectives' => 'Objectives',
    'eso-sec-components' => 'Project Components',
    'eso-sec-framework' => 'Logical Framework',
    'eso-sec-methodology' => 'Methodology & Activities',
    'eso-sec-workplan' => 'Work & Financial Plan',
    'eso-sec-budget' => 'Budget',
    'eso-sec-references' => 'References',
    'eso-sec-signoff' => 'Sign-off',
];
$sdgSelect = static function (string $selected = '', string $name = '') use ($sdgOptions): string {
    $html = '<select data-field="sdg"' . ($name !== '' ? ' name="' . htmlspecialchars($name) . '"' : '') . '>';
    $html .= '<option value="">Select an SDG</option>';
    foreach ($sdgOptions as $sdg) {
        $html .= '<option value="' . htmlspecialchars($sdg) . '"' . ($selected === $sdg ? ' selected' : '') . '>' . htmlspecialchars($sdg) . '</option>';
    }

    return $html . '</select>';
};
$removeItemButton = '<button type="button" class="webform-icon-btn eso-remove-row" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>';
?>

<form class="webform eso-webform" id="eso-proposal-form" novalidate
      data-draft-key="ride:eso-draft:<?= (int) ($user['id'] ?? 0) ?>:<?= $isEdit ? (int) $proposal['id'] : 'new' ?>"
      data-server-version="<?= htmlspecialchars((string) ($proposal['updated_at'] ?? '')) ?>"
      data-dean-names="<?= htmlspecialchars(json_encode($deanNamesByCollege, JSON_UNESCAPED_UNICODE) ?: '{}') ?>"
      method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/update') : base_url('proposals') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="nav_scope" value="extension">
    <input type="hidden" name="project_type" value="extension">
    <?php if ($isEdit): ?>
        <input type="hidden" name="preview_proposal_id" value="<?= (int) $proposal['id'] ?>">
    <?php endif; ?>

    <div class="webform-hero">
        <div>
            <p class="webform-eyebrow">Office of Extension Services · WPU-QSF-RIDE-ESO-03</p>
            <h2 class="webform-hero-title">Extension Program/Project Proposal</h2>
            <p class="webform-hero-text">Fill in each section below. Use <strong>Print Preview</strong> anytime to see the official paper format, print it, or save it as PDF.</p>
        </div>
        <button type="submit" class="btn btn-outline webform-preview-btn" formaction="<?= base_url('proposals/preview/extension-program') ?>" formtarget="_blank" formnovalidate>
            <i class="fa-solid fa-print"></i> Print Preview
        </button>
    </div>

    <div class="webform-banner" id="eso-draft-banner" role="status" hidden>
        <i class="fa-solid fa-clock-rotate-left"></i>
        <div class="webform-banner-text">
            <strong>Unsaved changes found.</strong>
            <span id="eso-draft-banner-detail"></span>
        </div>
        <div class="webform-banner-actions">
            <button type="button" class="btn btn-sm" id="eso-draft-restore">Restore</button>
            <button type="button" class="btn btn-outline btn-sm" id="eso-draft-discard">Discard</button>
        </div>
    </div>

    <nav class="webform-steps" aria-label="Form sections">
        <?php $stepNo = 0; foreach ($formSections as $anchor => $label): $stepNo++; ?>
            <a href="#<?= $anchor ?>"><span><?= $stepNo ?></span><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
    </nav>

    <!-- I. Identifying information -->
    <section class="webform-card" id="eso-sec-identifying">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>Identifying Information</h2>
                <p>Basic details of the extension program/project.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="eso-title">Title <span class="webform-req">*</span></label>
                <input id="eso-title" name="title" value="<?= htmlspecialchars($titleValue) ?>" placeholder="Title of the extension program/project" required>
            </div>
            <div class="webform-field webform-field--full">
                <label for="eso-college-program">College Extension Program</label>
                <input id="eso-college-program" name="college_extension_program" list="eso-college-program-options" value="<?= htmlspecialchars($collegeProgramValue) ?>" placeholder="Choose from the list or type a new program" autocomplete="off">
                <datalist id="eso-college-program-options">
                    <?php foreach ($collegeExtensionProgramOptions as $programOption): ?>
                        <option value="<?= htmlspecialchars($programOption) ?>">
                    <?php endforeach; ?>
                </datalist>
                <p class="webform-hint">Start typing to see suggested programs.</p>
            </div>
            <div class="webform-field">
                <label for="eso-team-leader">Project Team Leader</label>
                <input id="eso-team-leader" name="project_team_leader" list="eso-registered-user-options" value="<?= htmlspecialchars($teamLeaderValue) ?>" placeholder="Search faculty or type a name" autocomplete="off">
            </div>
            <div class="webform-field">
                <label for="eso-implementing-college">Implementing College / Department</label>
                <?php if ($collegeOptions !== []): ?>
                    <select id="eso-implementing-college" name="implementing_college_department">
                        <option value="">Select college / department</option>
                        <?php if ($hasLegacyCollege): ?>
                            <option value="<?= htmlspecialchars($implementingCollegeValue) ?>" selected><?= htmlspecialchars($implementingCollegeValue) ?></option>
                        <?php endif; ?>
                        <?php foreach ($collegeOptions as $collegeName): ?>
                            <option value="<?= htmlspecialchars($collegeName) ?>" <?= $implementingCollegeValue === $collegeName ? 'selected' : '' ?>><?= htmlspecialchars($collegeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="eso-implementing-college" name="implementing_college_department" value="<?= htmlspecialchars($implementingCollegeValue) ?>" placeholder="Implementing college / department">
                <?php endif; ?>
            </div>
            <div class="webform-field webform-field--full">
                <label for="eso-members-trainers-search">Members / Trainers</label>
                <div class="webform-inline">
                    <input type="text" id="eso-members-trainers-search" list="eso-registered-user-options" placeholder="Search a registered user to add…" autocomplete="off">
                    <datalist id="eso-registered-user-options">
                        <?php foreach ($facultyTeamOptions as $facultyOption): ?>
                            <option value="<?= htmlspecialchars((string) $facultyOption['name']) ?>" data-meta="<?= htmlspecialchars((string) $facultyOption['college_name']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <button type="button" class="btn btn-outline btn-sm" id="eso-members-trainers-add"><i class="fa-solid fa-plus"></i> Add</button>
                </div>
                <textarea name="members_trainers" id="eso-members-trainers" rows="3" data-autoresize placeholder="One name per line" aria-label="Members and trainers"><?= htmlspecialchars($membersTrainersValue) ?></textarea>
                <p class="webform-hint">Pick a suggested name to add it, or type names directly (one per line) for people not registered in the portal.</p>
            </div>
            <div class="webform-field webform-field--full">
                <label for="eso-collaborating">Collaborating Organizations</label>
                <textarea id="eso-collaborating" name="collaborating_organizations" rows="2" data-autoresize placeholder="e.g. LGU of Aborlan, DA-MIMAROPA"><?= htmlspecialchars($collaboratingOrgsValue) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="eso-beneficiaries">Beneficiaries</label>
                <input id="eso-beneficiaries" name="beneficiaries" value="<?= htmlspecialchars($beneficiariesValue) ?>" placeholder="e.g. Smallholder farmers of Barangay San Juan">
            </div>
            <div class="webform-field">
                <label for="eso-male">Male beneficiaries</label>
                <input id="eso-male" type="number" min="0" step="1" name="male_beneficiaries" value="<?= htmlspecialchars($maleBeneficiariesValue) ?>" placeholder="0">
            </div>
            <div class="webform-field">
                <label for="eso-female">Female beneficiaries</label>
                <input id="eso-female" type="number" min="0" step="1" name="female_beneficiaries" value="<?= htmlspecialchars($femaleBeneficiariesValue) ?>" placeholder="0">
            </div>
            <p class="webform-calc webform-field--full" id="eso-beneficiary-total" hidden></p>
            <div class="webform-field">
                <label for="eso-start">Start date</label>
                <input id="eso-start" type="date" name="duration_start_date" value="<?= htmlspecialchars($durationStartValue) ?>">
            </div>
            <div class="webform-field">
                <label for="eso-end">End date</label>
                <input id="eso-end" type="date" name="duration_end_date" value="<?= htmlspecialchars($durationEndValue) ?>">
            </div>
            <p class="webform-calc webform-field--full" id="eso-duration-calc" hidden></p>
            <?php if ($durationValue !== '' && $durationStartValue === '' && $durationEndValue === ''): ?>
                <p class="webform-hint webform-field--full">Previously entered as: <?= htmlspecialchars($durationValue) ?>. Pick dates above to replace it.</p>
            <?php endif; ?>
            <div class="webform-field webform-field--full">
                <label for="eso-location">Location</label>
                <input id="eso-location" name="location" value="<?= htmlspecialchars($locationValue) ?>" placeholder="Barangay, municipality, province">
            </div>
            <div class="webform-field">
                <label for="eso-budget">Budget</label>
                <div class="webform-affix"><span>₱</span><input id="eso-budget" name="budget" inputmode="decimal" value="<?= htmlspecialchars($budgetValue) ?>" placeholder="0.00"></div>
                <div class="webform-calc" id="eso-budget-calc" hidden></div>
            </div>
            <div class="webform-field">
                <label for="eso-source">Source of Fund</label>
                <input id="eso-source" name="source_of_fund" list="eso-fund-source-options" value="<?= htmlspecialchars($sourceOfFundValue) ?>" placeholder="e.g. WPU Fund" autocomplete="off">
                <datalist id="eso-fund-source-options">
                    <option value="WPU Fund">
                    <option value="LGU Counterpart">
                    <option value="External Partner">
                </datalist>
            </div>
        </div>
    </section>

    <!-- II. Rationale -->
    <section class="webform-card" id="eso-sec-rationale">
        <header class="webform-card-head">
            <span class="webform-card-num">2</span>
            <div>
                <h2>Rationale</h2>
                <p>The basis for the extension program/project and why it matters.</p>
            </div>
        </header>
        <div class="webform-field">
            <textarea name="rationale" rows="6" data-autoresize aria-label="Rationale" placeholder="Explain why this extension program/project is needed and why it matters."><?= htmlspecialchars($rationaleValue) ?></textarea>
        </div>
    </section>

    <!-- III. Objectives -->
    <section class="webform-card" id="eso-sec-objectives">
        <header class="webform-card-head">
            <span class="webform-card-num">3</span>
            <div>
                <h2>Objectives</h2>
                <p>The results the extension program/project seeks to achieve.</p>
            </div>
        </header>
        <div class="webform-field">
            <label for="eso-general-objective">General objective</label>
            <textarea id="eso-general-objective" name="general_objective" rows="3" data-autoresize placeholder="State the general objective."><?= htmlspecialchars($generalObjectiveValue) ?></textarea>
        </div>
        <div class="webform-field">
            <label for="eso-specific-objectives">Specific objectives</label>
            <textarea id="eso-specific-objectives" name="specific_objectives" rows="5" data-autoresize placeholder="List each specific objective on its own line."><?= htmlspecialchars($specificObjectivesValue) ?></textarea>
        </div>
    </section>

    <!-- IV. Project components -->
    <section class="webform-card" id="eso-sec-components">
        <header class="webform-card-head">
            <span class="webform-card-num">4</span>
            <div>
                <h2>Project Components / Descriptions</h2>
                <p>Community context, the problem, target group, partners and team.</p>
            </div>
        </header>
        <div class="webform-field">
            <label for="eso-community">A. Community analysis</label>
            <p class="webform-hint">Why the activities are relevant, explained through the community's current situation.</p>
            <textarea id="eso-community" name="community_analysis" rows="5" data-autoresize placeholder="Describe the current community situation."><?= htmlspecialchars($communityAnalysisValue) ?></textarea>
        </div>
        <div class="webform-field">
            <label for="eso-problem">B. Problem analysis</label>
            <p class="webform-hint">The primary issue facing the community, its underlying causes, and current consequences.</p>
            <textarea id="eso-problem" name="problem_analysis" rows="5" data-autoresize placeholder="Identify the primary issue, causes, and consequences."><?= htmlspecialchars($problemAnalysisValue) ?></textarea>
        </div>
        <div class="webform-field">
            <label for="eso-target-group">C. Description of the target group</label>
            <p class="webform-hint">Actual numbers, age, gender and quality of life of the target clients.</p>
            <textarea id="eso-target-group" name="target_group_description" rows="4" data-autoresize placeholder="Describe the target clients."><?= htmlspecialchars($targetGroupValue) ?></textarea>
        </div>

        <div class="webform-subsection">
            <h3>D. Partnership</h3>
            <p class="webform-hint">Tasks, areas of responsibility, and resource sharing among partners and implementers.</p>
            <div class="webform-repeater" id="eso-partnerships-body">
                <?php foreach ($partnerships as $index => $row): ?>
                    <div class="webform-item eso-repeater-row">
                        <div class="webform-item-head"><span class="webform-item-label">Partner</span><?= $removeItemButton ?></div>
                        <div class="webform-grid">
                            <div class="webform-field"><label>Partner</label><textarea rows="1" data-autoresize data-field="partner" name="partnerships[<?= (int) $index ?>][partner]" placeholder="e.g. Barangay San Juan"><?= htmlspecialchars((string) ($row['partner'] ?? '')) ?></textarea></div>
                            <div class="webform-field"><label>Area of responsibility</label><textarea rows="1" data-autoresize data-field="area_of_responsibility" name="partnerships[<?= (int) $index ?>][area_of_responsibility]" placeholder="e.g. Coordination"><?= htmlspecialchars((string) ($row['area_of_responsibility'] ?? '')) ?></textarea></div>
                            <div class="webform-field"><label>Task description</label><textarea rows="2" data-autoresize data-field="task_description" name="partnerships[<?= (int) $index ?>][task_description]" placeholder="e.g. Coordinate with the target participants"><?= htmlspecialchars((string) ($row['task_description'] ?? '')) ?></textarea></div>
                            <div class="webform-field"><label>Resource sharing</label><textarea rows="2" data-autoresize data-field="resource_sharing" name="partnerships[<?= (int) $index ?>][resource_sharing]" placeholder="e.g. Venue, meals, documentation"><?= htmlspecialchars((string) ($row['resource_sharing'] ?? '')) ?></textarea></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="webform-add-btn" id="eso-add-partnership"><i class="fa-solid fa-plus"></i> Add partner</button>
        </div>

        <div class="webform-subsection">
            <h3>E. Project team — duties &amp; responsibilities</h3>
            <p class="webform-hint">Add registered faculty from the directory, or add a member manually for people outside the portal.</p>
            <?php if ($facultyTeamOptions !== []): ?>
                <div class="webform-panel eso-faculty-picker">
                    <div class="webform-grid webform-grid--3">
                        <div class="webform-field">
                            <label for="eso-faculty-search">Search faculty</label>
                            <input type="search" id="eso-faculty-search" placeholder="Name, college, or department…" autocomplete="off">
                        </div>
                        <div class="webform-field">
                            <label for="eso-faculty-college-filter">College</label>
                            <select id="eso-faculty-college-filter">
                                <option value="">All colleges</option>
                                <?php foreach ($facultyCollegeFilters as $collegeFilter): ?>
                                    <option value="<?= htmlspecialchars($collegeFilter) ?>"><?= htmlspecialchars($collegeFilter) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="webform-field">
                            <label for="eso-faculty-department-filter">Department / Program</label>
                            <select id="eso-faculty-department-filter">
                                <option value="">All departments/programs</option>
                                <?php foreach ($facultyDepartmentFilters as $departmentFilter): ?>
                                    <option value="<?= htmlspecialchars($departmentFilter) ?>"><?= htmlspecialchars($departmentFilter) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <select id="eso-faculty-select" class="webform-listbox" size="6" aria-label="Faculty across colleges and departments">
                        <?php foreach ($facultyGroups as $groupLabel => $facultyRows): ?>
                            <optgroup label="<?= htmlspecialchars($groupLabel) ?>">
                                <?php foreach ($facultyRows as $facultyOption): ?>
                                    <option
                                        value="<?= (int) $facultyOption['id'] ?>"
                                        data-name="<?= htmlspecialchars((string) $facultyOption['name']) ?>"
                                        data-college="<?= htmlspecialchars((string) $facultyOption['college_name']) ?>"
                                        data-department="<?= htmlspecialchars((string) $facultyOption['department']) ?>"
                                        data-department-label="<?= htmlspecialchars((string) ($facultyOption['department_label'] ?? $facultyOption['department'])) ?>"
                                    ><?= htmlspecialchars((string) $facultyOption['label']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="eso-add-faculty-member" class="btn btn-sm"><i class="fa-solid fa-user-plus"></i> Add selected faculty to team</button>
                </div>
            <?php endif; ?>

            <div class="webform-repeater" id="eso-team-duties-body">
                <?php foreach ($teamDuties as $index => $row): ?>
                    <?php $linkedUserId = (int) ($row['user_id'] ?? 0); ?>
                    <div class="webform-item eso-repeater-row"<?= $linkedUserId > 0 ? ' data-linked-faculty="1"' : '' ?>>
                        <div class="webform-item-head">
                            <span class="webform-item-label">Team member</span>
                            <?php if ($linkedUserId > 0): ?><span class="badge badge-ongoing">Faculty account</span><?php endif; ?>
                            <?= $removeItemButton ?>
                        </div>
                        <div class="webform-grid">
                            <div class="webform-field">
                                <label>Name</label>
                                <?php if ($linkedUserId > 0): ?>
                                    <input type="hidden" data-field="user_id" name="team_duties[<?= (int) $index ?>][user_id]" value="<?= $linkedUserId ?>">
                                <?php endif; ?>
                                <input data-field="member" name="team_duties[<?= (int) $index ?>][member]" value="<?= htmlspecialchars((string) ($row['member'] ?? '')) ?>" placeholder="Full name" <?= $linkedUserId > 0 ? 'readonly' : '' ?>>
                            </div>
                            <div class="webform-field"><label>Role</label><input data-field="role" list="eso-role-options" name="team_duties[<?= (int) $index ?>][role]" value="<?= htmlspecialchars((string) ($row['role'] ?? '')) ?>" placeholder="e.g. Project Leader"></div>
                            <div class="webform-field"><label>College</label><input data-field="college" name="team_duties[<?= (int) $index ?>][college]" value="<?= htmlspecialchars((string) ($row['college'] ?? '')) ?>" placeholder="College" <?= $linkedUserId > 0 ? 'readonly' : '' ?>></div>
                            <div class="webform-field"><label>Department / Program</label><input data-field="department" name="team_duties[<?= (int) $index ?>][department]" value="<?= htmlspecialchars((string) ($row['department'] ?? '')) ?>" placeholder="Department / program" <?= $linkedUserId > 0 ? 'readonly' : '' ?>></div>
                            <div class="webform-field"><label>Task description</label><textarea rows="2" data-autoresize data-field="task_description" name="team_duties[<?= (int) $index ?>][task_description]" placeholder="What this member will do"><?= htmlspecialchars((string) ($row['task_description'] ?? '')) ?></textarea></div>
                            <div class="webform-field"><label>Responsibility</label><textarea rows="2" data-autoresize data-field="responsibility" name="team_duties[<?= (int) $index ?>][responsibility]" placeholder="What this member is accountable for"><?= htmlspecialchars((string) ($row['responsibility'] ?? '')) ?></textarea></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <datalist id="eso-role-options">
                <option value="Project Leader">
                <option value="Member">
                <option value="Trainer">
                <option value="Resource Speaker">
                <option value="Facilitator">
                <option value="Documenter">
            </datalist>
            <button type="button" class="webform-add-btn" id="eso-add-team-duty"><i class="fa-solid fa-plus"></i> Add member manually</button>
        </div>
    </section>

    <!-- V. Logical framework -->
    <section class="webform-card" id="eso-sec-framework">
        <header class="webform-card-head">
            <span class="webform-card-num">5</span>
            <div>
                <h2>Logical Framework</h2>
                <p>Trace each input through activities and outputs to the long-term impact.</p>
            </div>
        </header>
        <div class="webform-repeater" id="eso-framework-body">
            <?php foreach ($logicalFramework as $index => $row): ?>
                <div class="webform-item eso-repeater-row">
                    <div class="webform-item-head"><span class="webform-item-label">Framework entry</span><?= $removeItemButton ?></div>
                    <div class="webform-grid webform-grid--3">
                        <div class="webform-field"><label>Inputs</label><textarea rows="2" data-autoresize data-field="inputs" name="logical_framework[<?= (int) $index ?>][inputs]" placeholder="e.g. WPU Fund – ₱5,000.00"><?= htmlspecialchars((string) ($row['inputs'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Activities</label><textarea rows="2" data-autoresize data-field="activities" name="logical_framework[<?= (int) $index ?>][activities]" placeholder="e.g. Workshops on sustainable agriculture"><?= htmlspecialchars((string) ($row['activities'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Outputs</label><textarea rows="2" data-autoresize data-field="outputs" name="logical_framework[<?= (int) $index ?>][outputs]" placeholder="e.g. 50 participants trained"><?= htmlspecialchars((string) ($row['outputs'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Effects</label><textarea rows="2" data-autoresize data-field="effects" name="logical_framework[<?= (int) $index ?>][effects]" placeholder="e.g. Increased awareness and skills"><?= htmlspecialchars((string) ($row['effects'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Outcomes</label><textarea rows="2" data-autoresize data-field="outcomes" name="logical_framework[<?= (int) $index ?>][outcomes]" placeholder="e.g. Sustainable practices adopted"><?= htmlspecialchars((string) ($row['outcomes'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Impact</label><textarea rows="2" data-autoresize data-field="impact" name="logical_framework[<?= (int) $index ?>][impact]" placeholder="e.g. Long-term economic stability"><?= htmlspecialchars((string) ($row['impact'] ?? '')) ?></textarea></div>
                        <div class="webform-field webform-field--full"><label>Sustainable Development Goal</label><?= $sdgSelect((string) ($row['sdg'] ?? ''), 'logical_framework[' . (int) $index . '][sdg]') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="webform-add-btn" id="eso-add-framework"><i class="fa-solid fa-plus"></i> Add framework entry</button>
    </section>

    <!-- VI–VII. Methodology & activities -->
    <section class="webform-card" id="eso-sec-methodology">
        <header class="webform-card-head">
            <span class="webform-card-num">6</span>
            <div>
                <h2>Methodology &amp; Activities</h2>
                <p>How the project will be delivered, monitored and evaluated.</p>
            </div>
        </header>
        <div class="webform-field">
            <label for="eso-methodology">Methodology</label>
            <p class="webform-hint">Training techniques, methods, tools and materials, plus the monitoring and evaluation scheme.</p>
            <textarea id="eso-methodology" name="methodology" rows="6" data-autoresize placeholder="Describe the methods, tools, materials, and M&amp;E scheme."><?= htmlspecialchars($methodologyValue) ?></textarea>
        </div>
        <div class="webform-field">
            <label for="eso-activities">Activities</label>
            <textarea id="eso-activities" name="activities_narrative" rows="6" data-autoresize placeholder="Describe the extension activities to be conducted."><?= htmlspecialchars($activitiesNarrativeValue) ?></textarea>
        </div>
    </section>

    <!-- VIII. Work and financial plan -->
    <section class="webform-card" id="eso-sec-workplan">
        <header class="webform-card-head">
            <span class="webform-card-num">7</span>
            <div>
                <h2>Work &amp; Financial Plan</h2>
                <p>One entry per activity, with its schedule, people and budget.</p>
            </div>
        </header>
        <div class="webform-repeater" id="eso-workplan-body">
            <?php foreach ($workPlan as $index => $row): ?>
                <div class="webform-item eso-repeater-row">
                    <div class="webform-item-head"><span class="webform-item-label">Activity</span><?= $removeItemButton ?></div>
                    <div class="webform-grid">
                        <div class="webform-field webform-field--full"><label>Activity</label><textarea rows="1" data-autoresize data-field="activities" name="work_plan[<?= (int) $index ?>][activities]" placeholder="e.g. Training Workshop on Organic Farming"><?= htmlspecialchars((string) ($row['activities'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Objective</label><textarea rows="2" data-autoresize data-field="objective" name="work_plan[<?= (int) $index ?>][objective]" placeholder="What this activity aims to achieve"><?= htmlspecialchars((string) ($row['objective'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Indicator</label><textarea rows="2" data-autoresize data-field="indicator" name="work_plan[<?= (int) $index ?>][indicator]" placeholder="e.g. Number of farmers trained"><?= htmlspecialchars((string) ($row['indicator'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Strategies</label><textarea rows="2" data-autoresize data-field="strategies" name="work_plan[<?= (int) $index ?>][strategies]" placeholder="e.g. Partner with local NGOs"><?= htmlspecialchars((string) ($row['strategies'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Expected output/s</label><textarea rows="2" data-autoresize data-field="outputs" name="work_plan[<?= (int) $index ?>][outputs]" placeholder="e.g. Accomplishment Report (ETR)"><?= htmlspecialchars((string) ($row['outputs'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Responsible persons</label><textarea rows="1" data-autoresize data-field="responsible_persons" name="work_plan[<?= (int) $index ?>][responsible_persons]" placeholder="Names or roles"><?= htmlspecialchars((string) ($row['responsible_persons'] ?? '')) ?></textarea></div>
                        <div class="webform-field webform-field--split">
                            <div><label>Time frame</label><input data-field="time_frame" name="work_plan[<?= (int) $index ?>][time_frame]" value="<?= htmlspecialchars((string) ($row['time_frame'] ?? '')) ?>" placeholder="e.g. June 1–2, 2026"></div>
                            <div><label>Budget needed</label><div class="webform-affix"><span>₱</span><input data-field="budget_needed" inputmode="decimal" name="work_plan[<?= (int) $index ?>][budget_needed]" value="<?= htmlspecialchars((string) ($row['budget_needed'] ?? '')) ?>" placeholder="0.00"></div></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="webform-add-btn" id="eso-add-workplan"><i class="fa-solid fa-plus"></i> Add activity</button>
    </section>

    <!-- IX. Budgetary requirements -->
    <section class="webform-card" id="eso-sec-budget">
        <header class="webform-card-head">
            <span class="webform-card-num">8</span>
            <div>
                <h2>Budgetary Requirements</h2>
                <p>General breakdown, then line-item budgets. Totals compute automatically.</p>
            </div>
        </header>

        <div class="webform-subsection">
            <h3>A. General breakdown</h3>
            <div class="webform-table-wrap">
                <table class="webform-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Item</th>
                            <th>Particulars</th>
                            <th style="width:20%">Amount (₱)</th>
                            <th class="webform-table-action"><span class="sr-only">Remove</span></th>
                        </tr>
                    </thead>
                    <tbody id="eso-budget-general-body">
                        <?php foreach ($budgetGeneral as $index => $row): ?>
                            <tr class="eso-repeater-row">
                                <td><input data-field="item" name="budget_general[<?= (int) $index ?>][item]" value="<?= htmlspecialchars((string) ($row['item'] ?? '')) ?>" placeholder="e.g. Supplies" aria-label="Item"></td>
                                <td><textarea rows="1" data-autoresize data-field="particulars" name="budget_general[<?= (int) $index ?>][particulars]" placeholder="Particulars" aria-label="Particulars"><?= htmlspecialchars((string) ($row['particulars'] ?? '')) ?></textarea></td>
                                <td><input data-field="amount" inputmode="decimal" class="webform-num" name="budget_general[<?= (int) $index ?>][amount]" value="<?= htmlspecialchars((string) ($row['amount'] ?? '')) ?>" placeholder="0.00" aria-label="Amount"></td>
                                <td class="webform-table-action"><?= $removeItemButton ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="webform-calc webform-calc--total" id="eso-general-total" hidden></p>
            <button type="button" class="webform-add-btn" id="eso-add-budget-general"><i class="fa-solid fa-plus"></i> Add item</button>
        </div>

        <div id="eso-budget-groups">
            <?php foreach ($budgetLineGroups as $groupIndex => $group): ?>
                <?php
                $groupLabel = (string) ($group['label'] ?? ('Line-Item Budget ' . ($groupIndex + 1)));
                $groupItems = is_array($group['items'] ?? null) ? $group['items'] : [$blankBudgetItem];
                ?>
                <div class="webform-subsection eso-budget-group" data-group-index="<?= (int) $groupIndex ?>">
                    <div class="webform-budget-group-head">
                        <label class="webform-budget-group-title">
                            <span>B.<span class="eso-budget-group-number"><?= (int) $groupIndex + 1 ?></span> Line-item budget of</span>
                            <input class="eso-budget-group-label" data-field="label" name="budget_line_groups[<?= (int) $groupIndex ?>][label]" value="<?= htmlspecialchars($groupLabel) ?>" aria-label="Budget name">
                        </label>
                        <?php if ($groupIndex > 0): ?>
                            <button type="button" class="btn btn-outline btn-sm eso-remove-budget-group"><i class="fa-solid fa-trash-can"></i> Remove budget</button>
                        <?php endif; ?>
                    </div>
                    <div class="webform-table-wrap">
                        <table class="webform-table eso-line-item-table">
                            <thead>
                                <tr>
                                    <th style="width:9%">No.</th>
                                    <th>Particulars</th>
                                    <th style="width:10%">Qty</th>
                                    <th style="width:12%">Unit</th>
                                    <th style="width:15%">Cost/unit (₱)</th>
                                    <th style="width:16%">Total (₱)</th>
                                    <th class="webform-table-action"><span class="sr-only">Remove</span></th>
                                </tr>
                            </thead>
                            <tbody class="eso-budget-group-body">
                                <?php foreach ($groupItems as $itemIndex => $item): ?>
                                    <tr class="eso-repeater-row">
                                        <td><input data-field="item_no" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][item_no]" value="<?= htmlspecialchars((string) ($item['item_no'] ?? '')) ?>" placeholder="<?= (int) $itemIndex + 1 ?>" aria-label="Item number"></td>
                                        <td><textarea rows="1" data-autoresize data-field="particulars" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][particulars]" placeholder="e.g. Bond paper" aria-label="Particulars"><?= htmlspecialchars((string) ($item['particulars'] ?? '')) ?></textarea></td>
                                        <td><input data-field="qty" inputmode="decimal" class="webform-num" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][qty]" value="<?= htmlspecialchars((string) ($item['qty'] ?? '')) ?>" placeholder="0" aria-label="Quantity"></td>
                                        <td><input data-field="unit" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][unit]" value="<?= htmlspecialchars((string) ($item['unit'] ?? '')) ?>" placeholder="e.g. ream" aria-label="Unit"></td>
                                        <td><input data-field="cost_unit" inputmode="decimal" class="webform-num" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][cost_unit]" value="<?= htmlspecialchars((string) ($item['cost_unit'] ?? '')) ?>" placeholder="0.00" aria-label="Cost per unit"></td>
                                        <td><input data-field="total_cost" class="eso-line-total webform-num" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][total_cost]" value="<?= htmlspecialchars((string) ($item['total_cost'] ?? '')) ?>" placeholder="0.00" readonly tabindex="-1" aria-label="Total cost"></td>
                                        <td class="webform-table-action"><button type="button" class="webform-icon-btn eso-remove-line-item" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="eso-subtotal-row">
                                    <td colspan="5" class="webform-subtotal-label">Sub-total</td>
                                    <td><input class="eso-group-subtotal webform-num" value="" readonly tabindex="-1" aria-label="Sub-total"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="webform-add-btn eso-add-line-item"><i class="fa-solid fa-plus"></i> Add line item</button>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="webform-calc webform-calc--total" id="eso-lineitem-grand-total" hidden></p>
        <button type="button" class="btn btn-outline btn-sm" id="eso-add-budget-group"><i class="fa-solid fa-layer-group"></i> Add another line-item budget</button>
    </section>

    <!-- X. References -->
    <section class="webform-card" id="eso-sec-references">
        <header class="webform-card-head">
            <span class="webform-card-num">9</span>
            <div>
                <h2>References</h2>
                <p>Sources cited in this proposal.</p>
            </div>
        </header>
        <div class="webform-field">
            <textarea name="references" rows="5" data-autoresize class="no-capitalize" aria-label="References" placeholder="One reference per line."><?= htmlspecialchars($referencesValue) ?></textarea>
        </div>
    </section>

    <!-- Sign-off -->
    <section class="webform-card" id="eso-sec-signoff">
        <header class="webform-card-head">
            <span class="webform-card-num">10</span>
            <div>
                <h2>Sign-off</h2>
                <p>Names printed on the paper form. Official approvals are also captured through the workflow after submission.</p>
            </div>
        </header>
        <div class="webform-grid webform-grid--3">
            <div class="webform-panel">
                <p class="webform-panel-title">Submitted by — Proponent</p>
                <div class="webform-field"><label for="eso-proponent">Name</label><input id="eso-proponent" name="proponent_name" value="<?= htmlspecialchars($proponentNameValue) ?>" placeholder="Proponent"></div>
                <div class="webform-field"><label for="eso-proponent-date">Date</label><input id="eso-proponent-date" type="date" name="proponent_date" value="<?= htmlspecialchars($proponentDateValue) ?>"></div>
            </div>
            <div class="webform-panel">
                <p class="webform-panel-title">Endorsed by — Dean</p>
                <div class="webform-field"><label for="eso-dean">Name</label><input id="eso-dean" name="dean_name" list="eso-dean-options" value="<?= htmlspecialchars($deanNameValue) ?>" placeholder="Dean" autocomplete="off">
                    <datalist id="eso-dean-options">
                        <?php foreach ($deanNamesByCollege as $deanCollege => $deanName): ?>
                            <option value="<?= htmlspecialchars($deanName) ?>" data-meta="<?= htmlspecialchars($deanCollege) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <p class="webform-hint" id="eso-dean-hint" hidden>Filled in from the implementing college's dean account — edit if different.</p>
                </div>
                <div class="webform-field"><label for="eso-dean-date">Date</label><input id="eso-dean-date" type="date" name="dean_date" value="<?= htmlspecialchars($deanDateValue) ?>"></div>
            </div>
            <div class="webform-panel">
                <p class="webform-panel-title">Received by — Extension Admin Staff</p>
                <div class="webform-field"><label for="eso-admin">Name</label><input id="eso-admin" name="extension_admin_name" value="<?= htmlspecialchars($adminNameValue) ?>" placeholder="Extension admin staff"></div>
                <div class="webform-field"><label for="eso-admin-date">Date</label><input id="eso-admin-date" type="date" name="extension_admin_date" value="<?= htmlspecialchars($adminDateValue) ?>"></div>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <?php
        $workflowSteps = proposal_workflow_steps($proposal ?? [
            'status' => 'draft',
            'project_type' => 'extension',
            'summary' => json_encode(['form_type' => 'extension_program_proposal']),
            'first_name' => (string) ($user['first_name'] ?? ''),
            'last_name' => (string) ($user['last_name'] ?? ''),
        ]);
        require APP_PATH . '/views/proposals/_approval-workflow.php';
        ?>
    </section>

    <div class="webform-checklist" id="eso-checklist" aria-live="polite"></div>

    <div class="webform-actionbar">
        <span class="webform-autosave" id="eso-autosave-status" aria-live="polite"></span>
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline" formaction="<?= base_url('proposals/preview/extension-program') ?>" formtarget="_blank" formnovalidate>
            <i class="fa-solid fa-print"></i> Print Preview
        </button>
        <button type="submit" class="btn"><i class="fa-solid fa-floppy-disk"></i> <?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
    </div>
</form>

<template id="eso-partnership-row-template">
    <div class="webform-item eso-repeater-row">
        <div class="webform-item-head"><span class="webform-item-label">Partner</span><?= $removeItemButton ?></div>
        <div class="webform-grid">
            <div class="webform-field"><label>Partner</label><textarea rows="1" data-autoresize data-field="partner" placeholder="e.g. Barangay San Juan"></textarea></div>
            <div class="webform-field"><label>Area of responsibility</label><textarea rows="1" data-autoresize data-field="area_of_responsibility" placeholder="e.g. Coordination"></textarea></div>
            <div class="webform-field"><label>Task description</label><textarea rows="2" data-autoresize data-field="task_description" placeholder="e.g. Coordinate with the target participants"></textarea></div>
            <div class="webform-field"><label>Resource sharing</label><textarea rows="2" data-autoresize data-field="resource_sharing" placeholder="e.g. Venue, meals, documentation"></textarea></div>
        </div>
    </div>
</template>

<template id="eso-team-duty-row-template">
    <div class="webform-item eso-repeater-row">
        <div class="webform-item-head"><span class="webform-item-label">Team member</span><?= $removeItemButton ?></div>
        <div class="webform-grid">
            <div class="webform-field"><label>Name</label><input data-field="member" placeholder="Full name"></div>
            <div class="webform-field"><label>Role</label><input data-field="role" list="eso-role-options" placeholder="e.g. Member"></div>
            <div class="webform-field"><label>College</label><input data-field="college" placeholder="College"></div>
            <div class="webform-field"><label>Department / Program</label><input data-field="department" placeholder="Department / program"></div>
            <div class="webform-field"><label>Task description</label><textarea rows="2" data-autoresize data-field="task_description" placeholder="What this member will do"></textarea></div>
            <div class="webform-field"><label>Responsibility</label><textarea rows="2" data-autoresize data-field="responsibility" placeholder="What this member is accountable for"></textarea></div>
        </div>
    </div>
</template>

<template id="eso-framework-row-template">
    <div class="webform-item eso-repeater-row">
        <div class="webform-item-head"><span class="webform-item-label">Framework entry</span><?= $removeItemButton ?></div>
        <div class="webform-grid webform-grid--3">
            <div class="webform-field"><label>Inputs</label><textarea rows="2" data-autoresize data-field="inputs" placeholder="e.g. WPU Fund – ₱5,000.00"></textarea></div>
            <div class="webform-field"><label>Activities</label><textarea rows="2" data-autoresize data-field="activities" placeholder="e.g. Workshops on sustainable agriculture"></textarea></div>
            <div class="webform-field"><label>Outputs</label><textarea rows="2" data-autoresize data-field="outputs" placeholder="e.g. 50 participants trained"></textarea></div>
            <div class="webform-field"><label>Effects</label><textarea rows="2" data-autoresize data-field="effects" placeholder="e.g. Increased awareness and skills"></textarea></div>
            <div class="webform-field"><label>Outcomes</label><textarea rows="2" data-autoresize data-field="outcomes" placeholder="e.g. Sustainable practices adopted"></textarea></div>
            <div class="webform-field"><label>Impact</label><textarea rows="2" data-autoresize data-field="impact" placeholder="e.g. Long-term economic stability"></textarea></div>
            <div class="webform-field webform-field--full"><label>Sustainable Development Goal</label><?= $sdgSelect() ?></div>
        </div>
    </div>
</template>

<template id="eso-workplan-row-template">
    <div class="webform-item eso-repeater-row">
        <div class="webform-item-head"><span class="webform-item-label">Activity</span><?= $removeItemButton ?></div>
        <div class="webform-grid">
            <div class="webform-field webform-field--full"><label>Activity</label><textarea rows="1" data-autoresize data-field="activities" placeholder="e.g. Training Workshop on Organic Farming"></textarea></div>
            <div class="webform-field"><label>Objective</label><textarea rows="2" data-autoresize data-field="objective" placeholder="What this activity aims to achieve"></textarea></div>
            <div class="webform-field"><label>Indicator</label><textarea rows="2" data-autoresize data-field="indicator" placeholder="e.g. Number of farmers trained"></textarea></div>
            <div class="webform-field"><label>Strategies</label><textarea rows="2" data-autoresize data-field="strategies" placeholder="e.g. Partner with local NGOs"></textarea></div>
            <div class="webform-field"><label>Expected output/s</label><textarea rows="2" data-autoresize data-field="outputs" placeholder="e.g. Accomplishment Report (ETR)"></textarea></div>
            <div class="webform-field"><label>Responsible persons</label><textarea rows="1" data-autoresize data-field="responsible_persons" placeholder="Names or roles"></textarea></div>
            <div class="webform-field webform-field--split">
                <div><label>Time frame</label><input data-field="time_frame" placeholder="e.g. June 1–2, 2026"></div>
                <div><label>Budget needed</label><div class="webform-affix"><span>₱</span><input data-field="budget_needed" inputmode="decimal" placeholder="0.00"></div></div>
            </div>
        </div>
    </div>
</template>

<template id="eso-budget-general-row-template">
    <tr class="eso-repeater-row">
        <td><input data-field="item" placeholder="e.g. Supplies" aria-label="Item"></td>
        <td><textarea rows="1" data-autoresize data-field="particulars" placeholder="Particulars" aria-label="Particulars"></textarea></td>
        <td><input data-field="amount" inputmode="decimal" class="webform-num" placeholder="0.00" aria-label="Amount"></td>
        <td class="webform-table-action"><?= $removeItemButton ?></td>
    </tr>
</template>

<template id="eso-line-item-row-template">
    <tr class="eso-repeater-row">
        <td><input data-field="item_no" placeholder="#" aria-label="Item number"></td>
        <td><textarea rows="1" data-autoresize data-field="particulars" placeholder="e.g. Bond paper" aria-label="Particulars"></textarea></td>
        <td><input data-field="qty" inputmode="decimal" class="webform-num" placeholder="0" aria-label="Quantity"></td>
        <td><input data-field="unit" placeholder="e.g. ream" aria-label="Unit"></td>
        <td><input data-field="cost_unit" inputmode="decimal" class="webform-num" placeholder="0.00" aria-label="Cost per unit"></td>
        <td><input data-field="total_cost" class="eso-line-total webform-num" placeholder="0.00" readonly tabindex="-1" aria-label="Total cost"></td>
        <td class="webform-table-action"><button type="button" class="webform-icon-btn eso-remove-line-item" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button></td>
    </tr>
</template>

<template id="eso-budget-group-template">
    <div class="webform-subsection eso-budget-group" data-group-index="">
        <div class="webform-budget-group-head">
            <label class="webform-budget-group-title">
                <span>B.<span class="eso-budget-group-number">1</span> Line-item budget of</span>
                <input class="eso-budget-group-label" data-field="label" value="Line-Item Budget" aria-label="Budget name">
            </label>
            <button type="button" class="btn btn-outline btn-sm eso-remove-budget-group"><i class="fa-solid fa-trash-can"></i> Remove budget</button>
        </div>
        <div class="webform-table-wrap">
            <table class="webform-table eso-line-item-table">
                <thead>
                    <tr>
                        <th style="width:9%">No.</th>
                        <th>Particulars</th>
                        <th style="width:10%">Qty</th>
                        <th style="width:12%">Unit</th>
                        <th style="width:15%">Cost/unit (₱)</th>
                        <th style="width:16%">Total (₱)</th>
                        <th class="webform-table-action"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody class="eso-budget-group-body">
                    <tr class="eso-subtotal-row">
                        <td colspan="5" class="webform-subtotal-label">Sub-total</td>
                        <td><input class="eso-group-subtotal webform-num" value="" readonly tabindex="-1" aria-label="Sub-total"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <button type="button" class="webform-add-btn eso-add-line-item"><i class="fa-solid fa-plus"></i> Add line item</button>
    </div>
</template>

<script>
(() => {
    const parseAmount = (value) => {
        const cleaned = String(value || '').replace(/,/g, '').replace(/[^\d.-]/g, '');
        const amount = parseFloat(cleaned);
        return Number.isFinite(amount) ? amount : 0;
    };

    const bindAutoResize = (root = document) => {
        root.querySelectorAll('textarea[data-autoresize]').forEach((textarea) => {
            if (textarea.dataset.esoBound === '1') {
                return;
            }
            textarea.dataset.esoBound = '1';
            const resize = () => {
                textarea.style.height = 'auto';
                textarea.style.height = `${textarea.scrollHeight}px`;
            };
            textarea.addEventListener('input', resize);
            resize();
        });
    };

    const bindRepeater = ({ bodyId, addId, templateId, namePrefix, minRows = 1 }) => {
        const tbody = document.getElementById(bodyId);
        const addButton = document.getElementById(addId);
        const template = document.getElementById(templateId);
        if (!tbody || !addButton || !template) {
            return;
        }

        const reindex = () => {
            tbody.querySelectorAll('.eso-repeater-row').forEach((row, index) => {
                row.querySelectorAll('[data-field]').forEach((input) => {
                    input.name = `${namePrefix}[${index}][${input.dataset.field}]`;
                });
            });
        };

        const bindRow = (row) => {
            row.querySelector('.eso-remove-row')?.addEventListener('click', () => {
                const rows = tbody.querySelectorAll('.eso-repeater-row');
                if (rows.length <= minRows) {
                    row.removeAttribute('data-linked-faculty');
                    row.querySelector('[data-field="user_id"]')?.remove();
                    row.querySelector('.badge')?.remove();
                    row.querySelectorAll('input, textarea, select').forEach((el) => {
                        if (el.tagName === 'SELECT') {
                            el.selectedIndex = 0;
                        } else {
                            el.value = '';
                            el.readOnly = false;
                        }
                    });
                    bindAutoResize(row);
                    return;
                }
                row.remove();
                reindex();
            });
            bindAutoResize(row);
        };

        const addRow = () => {
            const row = template.content.firstElementChild.cloneNode(true);
            tbody.appendChild(row);
            bindRow(row);
            reindex();
            return row;
        };

        addButton.addEventListener('click', () => {
            addRow();
        });

        tbody.querySelectorAll('.eso-repeater-row').forEach(bindRow);
        reindex();

        return { tbody, reindex, bindRow, addRow };
    };

    const updateGroupSubtotal = (group) => {
        let total = 0;
        group.querySelectorAll('.eso-repeater-row [data-field="total_cost"]').forEach((input) => {
            total += parseAmount(input.value);
        });
        const subtotal = group.querySelector('.eso-group-subtotal');
        if (subtotal) {
            subtotal.value = total.toFixed(2);
        }
    };

    const bindLineItemRow = (row, group) => {
        const qty = row.querySelector('[data-field="qty"]');
        const cost = row.querySelector('[data-field="cost_unit"]');
        const total = row.querySelector('[data-field="total_cost"]');
        const recalc = () => {
            if (qty && cost && total && String(qty.value).trim() !== '' && String(cost.value).trim() !== '') {
                total.value = (parseAmount(qty.value) * parseAmount(cost.value)).toFixed(2);
            }
            updateGroupSubtotal(group);
        };
        qty?.addEventListener('input', recalc);
        cost?.addEventListener('input', recalc);
        total?.addEventListener('input', () => updateGroupSubtotal(group));
        row.querySelector('.eso-remove-line-item')?.addEventListener('click', () => {
            const rows = group.querySelectorAll('.eso-budget-group-body .eso-repeater-row');
            if (rows.length <= 1) {
                row.querySelectorAll('input, textarea').forEach((el) => {
                    el.value = '';
                });
                recalc();
                return;
            }
            row.remove();
            reindexBudgetGroups();
        });
        bindAutoResize(row);
        recalc();
    };

    const reindexBudgetGroups = () => {
        document.querySelectorAll('#eso-budget-groups .eso-budget-group').forEach((group, groupIndex) => {
            group.dataset.groupIndex = String(groupIndex);
            const number = group.querySelector('.eso-budget-group-number');
            if (number) {
                number.textContent = String(groupIndex + 1);
            }
            const heading = group.querySelector('.proposal-subtitle');
            if (heading && !group.querySelector('.eso-budget-group-number')) {
                heading.childNodes[0].textContent = `${groupIndex + 1}. Specific Breakdown of Budget of `;
            }
            const label = group.querySelector('.eso-budget-group-label');
            if (label) {
                label.name = `budget_line_groups[${groupIndex}][label]`;
            }
            group.querySelectorAll('.eso-budget-group-body .eso-repeater-row').forEach((row, itemIndex) => {
                row.querySelectorAll('[data-field]').forEach((input) => {
                    input.name = `budget_line_groups[${groupIndex}][items][${itemIndex}][${input.dataset.field}]`;
                });
            });
            updateGroupSubtotal(group);
        });
    };

    const bindBudgetGroup = (group) => {
        group.querySelector('.eso-add-line-item')?.addEventListener('click', () => {
            const template = document.getElementById('eso-line-item-row-template');
            const tbody = group.querySelector('.eso-budget-group-body');
            const subtotal = tbody?.querySelector('.eso-subtotal-row');
            if (!template || !tbody || !subtotal) {
                return;
            }
            const row = template.content.firstElementChild.cloneNode(true);
            tbody.insertBefore(row, subtotal);
            bindLineItemRow(row, group);
            reindexBudgetGroups();
        });
        group.querySelector('.eso-remove-budget-group')?.addEventListener('click', () => {
            const groups = document.querySelectorAll('#eso-budget-groups .eso-budget-group');
            if (groups.length <= 1) {
                return;
            }
            group.remove();
            reindexBudgetGroups();
        });
        group.querySelectorAll('.eso-budget-group-body .eso-repeater-row').forEach((row) => bindLineItemRow(row, group));
        updateGroupSubtotal(group);
    };

    bindRepeater({ bodyId: 'eso-partnerships-body', addId: 'eso-add-partnership', templateId: 'eso-partnership-row-template', namePrefix: 'partnerships' });
    const teamDutiesRepeater = bindRepeater({ bodyId: 'eso-team-duties-body', addId: 'eso-add-team-duty', templateId: 'eso-team-duty-row-template', namePrefix: 'team_duties', minRows: 1 });
    bindRepeater({ bodyId: 'eso-framework-body', addId: 'eso-add-framework', templateId: 'eso-framework-row-template', namePrefix: 'logical_framework' });
    bindRepeater({ bodyId: 'eso-workplan-body', addId: 'eso-add-workplan', templateId: 'eso-workplan-row-template', namePrefix: 'work_plan' });
    bindRepeater({ bodyId: 'eso-budget-general-body', addId: 'eso-add-budget-general', templateId: 'eso-budget-general-row-template', namePrefix: 'budget_general', minRows: 1 });

    const facultySelect = document.getElementById('eso-faculty-select');
    const facultySearch = document.getElementById('eso-faculty-search');
    const facultyCollegeFilter = document.getElementById('eso-faculty-college-filter');
    const facultyDepartmentFilter = document.getElementById('eso-faculty-department-filter');
    const addFacultyButton = document.getElementById('eso-add-faculty-member');

    const applyFacultyToRow = (row, option) => {
        const memberField = row.querySelector('[data-field="member"]')?.closest('.webform-field');
        if (memberField && !row.querySelector('[data-field="user_id"]')) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-ongoing';
            badge.textContent = 'Faculty account';
            const head = row.querySelector('.webform-item-head');
            head?.insertBefore(badge, head.querySelector('.eso-remove-row'));

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.dataset.field = 'user_id';
            hidden.value = option.value;
            memberField.appendChild(hidden);
        }

        const member = row.querySelector('[data-field="member"]');
        const college = row.querySelector('[data-field="college"]');
        const department = row.querySelector('[data-field="department"]');
        if (member) {
            member.value = option.dataset.name || '';
            member.readOnly = true;
        }
        if (college) {
            college.value = option.dataset.college || '';
            college.readOnly = true;
        }
        if (department) {
            department.value = option.dataset.department || '';
            department.readOnly = true;
        }
        row.dataset.linkedFaculty = '1';
        bindAutoResize(row);
    };

    const linkedFacultyIds = () => {
        const ids = new Set();
        teamDutiesRepeater?.tbody.querySelectorAll('[data-field="user_id"]').forEach((input) => {
            if (input.value) {
                ids.add(input.value);
            }
        });
        return ids;
    };

    const emptyTeamDutyRow = () => {
        const rows = Array.from(teamDutiesRepeater?.tbody.querySelectorAll('.eso-repeater-row') ?? []);
        return rows.find((row) => {
            if (row.querySelector('[data-field="user_id"]')) {
                return false;
            }
            const member = row.querySelector('[data-field="member"]');
            const college = row.querySelector('[data-field="college"]');
            const department = row.querySelector('[data-field="department"]');
            return (member?.value.trim() ?? '') === ''
                && (college?.value.trim() ?? '') === ''
                && (department?.value.trim() ?? '') === '';
        }) ?? null;
    };

    const optionDepartmentLabel = (option) => (option.dataset.departmentLabel || option.dataset.department || '').trim()
        || 'No department/program assigned';

    const filterFacultyOptions = () => {
        if (!facultySelect) {
            return;
        }
        const query = (facultySearch?.value || '').trim().toLowerCase();
        const college = (facultyCollegeFilter?.value || '').trim().toLowerCase();
        const department = (facultyDepartmentFilter?.value || '').trim().toLowerCase();
        let firstVisible = null;

        facultySelect.querySelectorAll('optgroup').forEach((group) => {
            let visibleInGroup = 0;
            group.querySelectorAll('option').forEach((option) => {
                const departmentLabel = optionDepartmentLabel(option);
                const haystack = [
                    option.textContent,
                    option.dataset.name,
                    option.dataset.college,
                    option.dataset.department,
                    departmentLabel,
                    group.label,
                ].join(' ').toLowerCase();
                const matchesQuery = query === '' || haystack.includes(query);
                const matchesCollege = college === '' || (option.dataset.college || '').toLowerCase() === college
                    || group.label.toLowerCase().startsWith(college);
                const matchesDepartment = department === '' || departmentLabel.toLowerCase() === department;
                const visible = matchesQuery && matchesCollege && matchesDepartment;
                option.hidden = !visible;
                if (visible) {
                    visibleInGroup += 1;
                    if (firstVisible === null) {
                        firstVisible = option;
                    }
                }
            });
            group.hidden = visibleInGroup === 0;
        });

        if (firstVisible) {
            firstVisible.selected = true;
        }
    };

    const refreshDepartmentFilter = () => {
        if (!facultyDepartmentFilter || !facultySelect) {
            return;
        }
        const college = (facultyCollegeFilter?.value || '').trim().toLowerCase();
        const current = facultyDepartmentFilter.value;
        const departments = new Set();
        facultySelect.querySelectorAll('option[value]').forEach((option) => {
            const optionCollege = (option.dataset.college || '').trim().toLowerCase();
            if (college !== '' && optionCollege !== college) {
                return;
            }
            departments.add(optionDepartmentLabel(option));
        });

        const sorted = Array.from(departments).sort((a, b) => a.localeCompare(b));
        facultyDepartmentFilter.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = 'All departments/programs';
        facultyDepartmentFilter.appendChild(allOption);
        sorted.forEach((label) => {
            const option = document.createElement('option');
            option.value = label;
            option.textContent = label;
            facultyDepartmentFilter.appendChild(option);
        });
        facultyDepartmentFilter.value = sorted.includes(current) ? current : '';
        filterFacultyOptions();
    };

    facultySearch?.addEventListener('input', filterFacultyOptions);
    facultyCollegeFilter?.addEventListener('change', refreshDepartmentFilter);
    facultyDepartmentFilter?.addEventListener('change', filterFacultyOptions);
    filterFacultyOptions();

    addFacultyButton?.addEventListener('click', () => {
        if (!facultySelect || !teamDutiesRepeater) {
            return;
        }
        const option = facultySelect.options[facultySelect.selectedIndex];
        if (!option || !option.value || option.hidden) {
            facultySelect.focus();
            return;
        }
        if (linkedFacultyIds().has(option.value)) {
            window.alert('This faculty member is already included in the project team.');
            return;
        }

        const row = emptyTeamDutyRow() ?? teamDutiesRepeater.addRow();
        applyFacultyToRow(row, option);
        teamDutiesRepeater.reindex();
    });

    document.querySelectorAll('#eso-budget-groups .eso-budget-group').forEach(bindBudgetGroup);
    reindexBudgetGroups();

    document.getElementById('eso-add-budget-group')?.addEventListener('click', () => {
        const template = document.getElementById('eso-budget-group-template');
        const wrap = document.getElementById('eso-budget-groups');
        const lineItemTemplate = document.getElementById('eso-line-item-row-template');
        if (!template || !wrap || !lineItemTemplate) {
            return;
        }
        const group = template.content.firstElementChild.cloneNode(true);
        const nextIndex = wrap.querySelectorAll('.eso-budget-group').length;
        group.querySelector('.eso-budget-group-label').value = `Line-Item Budget ${nextIndex + 1}`;
        const tbody = group.querySelector('.eso-budget-group-body');
        const subtotal = tbody.querySelector('.eso-subtotal-row');
        const firstRow = lineItemTemplate.content.firstElementChild.cloneNode(true);
        const secondRow = lineItemTemplate.content.firstElementChild.cloneNode(true);
        tbody.insertBefore(firstRow, subtotal);
        tbody.insertBefore(secondRow, subtotal);
        wrap.appendChild(group);
        bindBudgetGroup(group);
        reindexBudgetGroups();
    });

    const membersTrainersSearch = document.getElementById('eso-members-trainers-search');
    const membersTrainersTextarea = document.getElementById('eso-members-trainers');
    document.getElementById('eso-members-trainers-add')?.addEventListener('click', () => {
        const name = membersTrainersSearch?.value.trim();
        if (!name || !membersTrainersTextarea) {
            return;
        }
        const existing = membersTrainersTextarea.value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
        if (!existing.includes(name)) {
            existing.push(name);
        }
        membersTrainersTextarea.value = existing.join('\n');
        membersTrainersTextarea.dispatchEvent(new Event('input'));
        membersTrainersSearch.value = '';
        membersTrainersSearch.focus();
    });

    const form = document.getElementById('eso-proposal-form');
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

    /* ---------- Styled suggestion dropdowns (replace the browser's native <datalist> UI) ---------- */
    const comboPanel = document.createElement('div');
    comboPanel.className = 'webform-combo-panel';
    comboPanel.id = 'eso-combo-panel';
    comboPanel.setAttribute('role', 'listbox');
    comboPanel.hidden = true;
    document.body.appendChild(comboPanel);
    let comboInput = null;
    let comboItems = [];
    let comboActive = -1;

    const enhanceCombos = (root) => {
        root.querySelectorAll('input[list]').forEach((input) => {
            input.dataset.combobox = input.getAttribute('list');
            input.removeAttribute('list');
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('role', 'combobox');
            input.setAttribute('aria-autocomplete', 'list');
            input.setAttribute('aria-controls', comboPanel.id);
            input.setAttribute('aria-expanded', 'false');
        });
    };
    enhanceCombos(form);
    document.querySelectorAll('template').forEach((template) => enhanceCombos(template.content));

    const comboOptions = (input) => {
        const list = document.getElementById(input.dataset.combobox);
        return list ? Array.from(list.options).map((option) => ({ value: option.value, meta: option.dataset.meta || '' })) : [];
    };
    // "Program Name (CCAS)" → label + college-code chip
    const splitCode = (value) => {
        const match = value.match(/^(.*?)\s*\(([A-Z]{2,8})\)$/);
        return match ? [match[1], match[2]] : [value, ''];
    };
    const highlight = (text, query) => {
        const index = query === '' ? -1 : text.toLowerCase().indexOf(query);
        if (index < 0) {
            return escapeHtml(text);
        }
        return escapeHtml(text.slice(0, index)) + '<mark>' + escapeHtml(text.slice(index, index + query.length)) + '</mark>' + escapeHtml(text.slice(index + query.length));
    };
    const positionCombo = () => {
        if (!comboInput || comboPanel.hidden) {
            return;
        }
        const rect = comboInput.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        comboPanel.style.left = `${rect.left}px`;
        comboPanel.style.width = `${rect.width}px`;
        if (spaceBelow < 260 && rect.top > spaceBelow) {
            comboPanel.style.top = '';
            comboPanel.style.bottom = `${window.innerHeight - rect.top + 4}px`;
        } else {
            comboPanel.style.bottom = '';
            comboPanel.style.top = `${rect.bottom + 4}px`;
        }
    };
    const closeCombo = () => {
        comboPanel.hidden = true;
        comboInput?.setAttribute('aria-expanded', 'false');
        comboInput?.removeAttribute('aria-activedescendant');
        comboInput = null;
        comboActive = -1;
    };
    const setComboActive = (index) => {
        const options = comboPanel.querySelectorAll('.webform-combo-option');
        options.forEach((option, i) => option.classList.toggle('is-active', i === index));
        comboActive = index;
        if (options[index]) {
            options[index].scrollIntoView({ block: 'nearest' });
            comboInput?.setAttribute('aria-activedescendant', options[index].id);
        }
    };
    const renderCombo = () => {
        if (!comboInput) {
            return;
        }
        const all = comboOptions(comboInput);
        if (all.length === 0) {
            closeCombo();
            return;
        }
        const query = comboInput.value.trim().toLowerCase();
        const exact = all.some((option) => option.value.toLowerCase() === query);
        comboItems = all.filter((option) => exact || query === '' || `${option.value} ${option.meta}`.toLowerCase().includes(query)).slice(0, 60);
        comboPanel.innerHTML = comboItems.length === 0
            ? '<div class="webform-combo-empty">No matching suggestions — what you typed will be used.</div>'
            : comboItems.map((option, i) => {
                const [label, code] = splitCode(option.value);
                const meta = code || option.meta;
                const selected = option.value === comboInput.value;
                return `<div class="webform-combo-option${selected ? ' is-selected' : ''}" role="option" id="eso-combo-opt-${i}" data-index="${i}" aria-selected="${selected}">`
                    + `<span class="webform-combo-label">${highlight(label, exact ? '' : query)}</span>`
                    + (meta ? `<span class="webform-combo-meta">${escapeHtml(meta)}</span>` : '')
                    + (selected ? '<i class="fa-solid fa-check"></i>' : '')
                    + '</div>';
            }).join('');
        comboPanel.hidden = false;
        comboInput.setAttribute('aria-expanded', 'true');
        comboActive = -1;
        positionCombo();
    };
    const openCombo = (input) => {
        comboInput = input;
        renderCombo();
    };
    const pickCombo = (index) => {
        const option = comboItems[index];
        const input = comboInput;
        if (!option || !input) {
            return;
        }
        input.value = option.value;
        closeCombo();
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.dispatchEvent(new CustomEvent('combobox:pick', { bubbles: true }));
    };

    form.addEventListener('focusin', (event) => {
        if (event.target.dataset?.combobox) {
            openCombo(event.target);
        }
    });
    form.addEventListener('mousedown', (event) => {
        if (event.target.dataset?.combobox && event.target === document.activeElement && comboPanel.hidden) {
            openCombo(event.target);
        }
    });
    form.addEventListener('focusout', (event) => {
        if (event.target === comboInput) {
            closeCombo();
        }
    });
    form.addEventListener('input', (event) => {
        if (event.isTrusted && event.target.dataset?.combobox && event.target === document.activeElement) {
            comboInput = event.target;
            renderCombo();
        }
    });
    comboPanel.addEventListener('mousedown', (event) => event.preventDefault());
    comboPanel.addEventListener('click', (event) => {
        const option = event.target.closest('.webform-combo-option');
        if (option) {
            pickCombo(Number(option.dataset.index));
        }
    });
    window.addEventListener('scroll', positionCombo, true);
    window.addEventListener('resize', positionCombo);

    form.addEventListener('keydown', (event) => {
        const target = event.target;
        if (comboInput && target === comboInput && !comboPanel.hidden) {
            const count = comboItems.length;
            if (event.key === 'ArrowDown' && count) {
                event.preventDefault();
                setComboActive((comboActive + 1) % count);
                return;
            }
            if (event.key === 'ArrowUp' && count) {
                event.preventDefault();
                setComboActive(comboActive <= 0 ? count - 1 : comboActive - 1);
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeCombo();
                return;
            }
            if (event.key === 'Enter' && comboActive >= 0) {
                event.preventDefault();
                pickCombo(comboActive);
                return;
            }
        } else if (target.dataset?.combobox && event.key === 'ArrowDown') {
            event.preventDefault();
            openCombo(target);
            return;
        }
        // Enter in a single-line field must not submit the form (the first submit button is Print Preview).
        if (event.key === 'Enter' && target.tagName === 'INPUT') {
            event.preventDefault();
            if (target === membersTrainersSearch) {
                document.getElementById('eso-members-trainers-add')?.click();
            }
        }
    });
    membersTrainersSearch?.addEventListener('combobox:pick', () => document.getElementById('eso-members-trainers-add')?.click());

    /* ---------- Dean auto-fill from the implementing college ---------- */
    let deanNames = {};
    try {
        deanNames = JSON.parse(form.dataset.deanNames || '{}');
    } catch (error) {
        deanNames = {};
    }
    const deanInput = document.getElementById('eso-dean');
    const deanHint = document.getElementById('eso-dean-hint');
    const implementingCollege = document.getElementById('eso-implementing-college');
    const syncDean = () => {
        if (!deanInput) {
            return;
        }
        const suggested = deanNames[implementingCollege?.value || ''] || '';
        const current = deanInput.value.trim();
        if (suggested && (current === '' || current === deanInput.dataset.autofilled)) {
            deanInput.value = suggested;
            deanInput.dataset.autofilled = suggested;
        }
        if (deanHint) {
            deanHint.hidden = !deanInput.dataset.autofilled || deanInput.value !== deanInput.dataset.autofilled;
        }
    };
    implementingCollege?.addEventListener('change', syncDean);
    deanInput?.addEventListener('input', () => {
        if (deanHint) {
            deanHint.hidden = deanInput.value !== deanInput.dataset.autofilled;
        }
    });
    syncDean();

    /* ---------- Computed values: totals, duration, budget cross-check ---------- */
    const maleInput = document.getElementById('eso-male');
    const femaleInput = document.getElementById('eso-female');
    const startInput = document.getElementById('eso-start');
    const endInput = document.getElementById('eso-end');
    const budgetInput = document.getElementById('eso-budget');
    const isAmount = (value) => /^\s*(\d{1,3}(,\d{3})+|\d+)(\.\d+)?\s*$/.test(value);
    const peso = (amount) => '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const plural = (count, word) => `${count.toLocaleString()} ${word}${count === 1 ? '' : 's'}`;
    let lineItemGrandTotal = 0;

    const sumField = (selector) => {
        let total = 0;
        let any = false;
        form.querySelectorAll(selector).forEach((input) => {
            if (input.value.trim() !== '') {
                any = true;
                total += parseAmount(input.value);
            }
        });
        return [total, any];
    };
    const showCalc = (id, html, extraClass = '') => {
        const el = document.getElementById(id);
        if (!el) {
            return;
        }
        el.hidden = html === '';
        el.innerHTML = html;
        el.className = el.className.replace(/\s*webform-calc--warn/g, '') + (extraClass ? ` ${extraClass}` : '');
    };

    const updateComputed = () => {
        const male = parseInt(maleInput?.value || '', 10);
        const female = parseInt(femaleInput?.value || '', 10);
        showCalc('eso-beneficiary-total', (maleInput?.value !== '' || femaleInput?.value !== '')
            ? `<i class="fa-solid fa-users"></i> Total beneficiaries: <strong>${((male > 0 ? male : 0) + (female > 0 ? female : 0)).toLocaleString()}</strong>`
            : '');

        let durationHtml = '';
        if (startInput?.value && endInput?.value) {
            const start = new Date(`${startInput.value}T00:00:00`);
            const end = new Date(`${endInput.value}T00:00:00`);
            const days = Math.round((end - start) / 86400000) + 1;
            if (days >= 1) {
                let months = (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth());
                if (end.getDate() + 1 < start.getDate()) {
                    months -= 1;
                }
                durationHtml = `<i class="fa-regular fa-calendar"></i> Duration: <strong>${plural(days, 'day')}</strong>${months >= 1 ? ` (about ${plural(months, 'month')})` : ''}`;
            }
        }
        showCalc('eso-duration-calc', durationHtml);

        const [generalTotal, hasGeneral] = sumField('#eso-budget-general-body [data-field="amount"]');
        showCalc('eso-general-total', hasGeneral ? `General breakdown total: <strong>${peso(generalTotal)}</strong>` : '');

        const [grandTotal, hasLines] = sumField('#eso-budget-groups [data-field="total_cost"]');
        lineItemGrandTotal = grandTotal;
        showCalc('eso-lineitem-grand-total', hasLines ? `All line-item budgets: <strong>${peso(grandTotal)}</strong>` : '');

        let budgetHtml = '';
        let budgetClass = '';
        if (hasLines && grandTotal > 0) {
            const typed = budgetInput?.value.trim() || '';
            const matches = typed !== '' && isAmount(typed) && Math.abs(parseAmount(typed) - grandTotal) < 0.005;
            if (matches) {
                budgetHtml = '<i class="fa-solid fa-circle-check"></i> Matches the line-item budget total';
            } else {
                budgetHtml = (typed !== ''
                    ? `<i class="fa-solid fa-triangle-exclamation"></i> Line-item budgets add up to <strong>${peso(grandTotal)}</strong>.`
                    : `Line-item budgets add up to <strong>${peso(grandTotal)}</strong>.`)
                    + ' <button type="button" class="webform-link-btn" id="eso-use-total">Use this amount</button>';
                budgetClass = typed !== '' ? 'webform-calc--warn' : '';
            }
        }
        showCalc('eso-budget-calc', budgetHtml, budgetClass);
    };
    form.addEventListener('click', (event) => {
        if (event.target.closest('#eso-use-total') && budgetInput) {
            budgetInput.value = lineItemGrandTotal.toFixed(2);
            budgetInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });

    /* ---------- Validation (blocks saving only on clearly wrong values) ---------- */
    const clearErrors = () => {
        form.querySelectorAll('.is-invalid').forEach((el) => {
            el.classList.remove('is-invalid');
            el.removeAttribute('aria-invalid');
            if (el.dataset.errorTitle) {
                el.removeAttribute('title');
                delete el.dataset.errorTitle;
            }
        });
        form.querySelectorAll('.webform-error').forEach((el) => el.remove());
    };
    const flagError = (el, message) => {
        el.classList.add('is-invalid');
        el.setAttribute('aria-invalid', 'true');
        if (el.closest('td')) {
            el.title = message;
            el.dataset.errorTitle = '1';
        } else {
            const note = document.createElement('p');
            note.className = 'webform-error';
            note.textContent = message;
            (el.closest('.webform-affix') || el).insertAdjacentElement('afterend', note);
        }
        return el;
    };
    const validateForm = () => {
        clearErrors();
        const errors = [];
        const title = form.elements.namedItem('title');
        if (title && title.value.trim() === '') {
            errors.push(flagError(title, 'Enter a title for the proposal.'));
        }
        if (startInput?.value && endInput?.value && endInput.value < startInput.value) {
            errors.push(flagError(endInput, 'End date must be on or after the start date.'));
        }
        [maleInput, femaleInput].forEach((input) => {
            if (!input) {
                return;
            }
            const number = Number(input.value);
            if (input.validity.badInput || (input.value !== '' && (!Number.isInteger(number) || number < 0))) {
                errors.push(flagError(input, 'Enter a whole number (0 or more).'));
            }
        });
        form.querySelectorAll('[name="budget"], [data-field="budget_needed"], [data-field="amount"], [data-field="qty"], [data-field="cost_unit"]').forEach((input) => {
            const value = input.value.trim();
            if (value !== '' && !isAmount(value)) {
                errors.push(flagError(input, 'Enter a number, e.g. 1,250.00'));
            }
        });
        return errors;
    };

    /* ---------- Section completeness checklist ---------- */
    const fieldValue = (name) => (form.elements.namedItem(name)?.value || '').trim();
    const anyFilled = (selector) => Array.from(form.querySelectorAll(selector)).some((el) => el.value.trim() !== '');
    const sectionChecks = [
        ['eso-sec-identifying', 'Identifying Information', () => ['title', 'college_extension_program', 'project_team_leader', 'implementing_college_department', 'beneficiaries', 'duration_start_date', 'duration_end_date', 'location', 'budget', 'source_of_fund'].every((name) => fieldValue(name) !== '')],
        ['eso-sec-rationale', 'Rationale', () => fieldValue('rationale') !== ''],
        ['eso-sec-objectives', 'Objectives', () => fieldValue('general_objective') !== '' && fieldValue('specific_objectives') !== ''],
        ['eso-sec-components', 'Project Components', () => ['community_analysis', 'problem_analysis', 'target_group_description'].every((name) => fieldValue(name) !== '')
            && anyFilled('#eso-partnerships-body [data-field="partner"]') && anyFilled('#eso-team-duties-body [data-field="member"]')],
        ['eso-sec-framework', 'Logical Framework', () => anyFilled('#eso-framework-body [data-field="activities"]')],
        ['eso-sec-methodology', 'Methodology & Activities', () => fieldValue('methodology') !== '' && fieldValue('activities_narrative') !== ''],
        ['eso-sec-workplan', 'Work & Financial Plan', () => anyFilled('#eso-workplan-body [data-field="activities"]')],
        ['eso-sec-budget', 'Budget', () => Array.from(form.querySelectorAll('#eso-budget-groups [data-field="total_cost"]')).some((input) => parseAmount(input.value) > 0)],
        ['eso-sec-references', 'References', () => fieldValue('references') !== ''],
        ['eso-sec-signoff', 'Sign-off', () => fieldValue('proponent_name') !== '' && fieldValue('proponent_date') !== ''],
    ];
    const checklist = document.getElementById('eso-checklist');
    const renderChecklist = () => {
        const missing = sectionChecks.filter(([, , isComplete]) => !isComplete());
        const missingIds = new Set(missing.map(([id]) => `#${id}`));
        document.querySelectorAll('.webform-steps a').forEach((link) => link.classList.toggle('is-done', !missingIds.has(link.getAttribute('href'))));
        if (!checklist) {
            return;
        }
        checklist.classList.toggle('is-complete', missing.length === 0);
        checklist.innerHTML = missing.length === 0
            ? '<i class="fa-solid fa-circle-check"></i> All sections are filled in — ready to save and submit for review.'
            : `<span class="webform-checklist-title"><i class="fa-solid fa-list-check"></i> ${sectionChecks.length - missing.length} of ${sectionChecks.length} sections complete. Still to fill:</span>`
                + missing.map(([id, label]) => `<a href="#${id}">${escapeHtml(label)}</a>`).join('')
                + '<span class="webform-checklist-note">You can save a draft anytime.</span>';
    };

    /* ---------- Autosave to this browser + restore ---------- */
    const draftKey = form.dataset.draftKey;
    const skipKeys = new Set(['_csrf', 'nav_scope', 'project_type', 'preview_proposal_id']);
    const statusEl = document.getElementById('eso-autosave-status');
    const draftStore = {
        get() {
            try {
                return JSON.parse(window.localStorage.getItem(draftKey) || 'null');
            } catch (error) {
                return null;
            }
        },
        set(draft) {
            try {
                window.localStorage.setItem(draftKey, JSON.stringify(draft));
                return true;
            } catch (error) {
                return false;
            }
        },
        clear() {
            try {
                window.localStorage.removeItem(draftKey);
            } catch (error) {
                // storage unavailable — nothing to clear
            }
        },
    };
    const snapshot = () => {
        const values = [];
        new FormData(form).forEach((value, key) => {
            if (!skipKeys.has(key) && typeof value === 'string') {
                values.push([key, value]);
            }
        });
        return values;
    };
    const setStatus = (html, className = '') => {
        if (statusEl) {
            statusEl.className = `webform-autosave ${className}`.trim();
            statusEl.innerHTML = html;
        }
    };
    const baseline = JSON.stringify(snapshot());
    const saveDraft = (extra = {}) => {
        const values = snapshot();
        if (JSON.stringify(values) === baseline) {
            draftStore.clear();
            setStatus('');
            return;
        }
        const saved = draftStore.set({ savedAt: Date.now(), serverVersion: form.dataset.serverVersion || '', values, ...extra });
        setStatus(saved
            ? `<i class="fa-solid fa-circle-check"></i> Draft saved on this device ${new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`
            : '<i class="fa-solid fa-triangle-exclamation"></i> Autosave isn\'t available in this browser', saved ? '' : 'is-warn');
    };

    const fitRows = (containerSelector, addButtonId, prefix, values) => {
        const container = document.querySelector(containerSelector);
        if (!container) {
            return;
        }
        const pattern = new RegExp(`^${prefix}\\[(\\d+)\\]`);
        let needed = 1;
        values.forEach((_, key) => {
            const match = key.match(pattern);
            if (match) {
                needed = Math.max(needed, Number(match[1]) + 1);
            }
        });
        const rows = () => container.querySelectorAll(':scope > .eso-repeater-row');
        while (rows().length < needed) {
            document.getElementById(addButtonId)?.click();
        }
        while (rows().length > needed) {
            const list = rows();
            list[list.length - 1].querySelector('.eso-remove-row')?.click();
        }
    };

    const restoreDraft = (draft) => {
        const values = new Map(draft.values);
        fitRows('#eso-partnerships-body', 'eso-add-partnership', 'partnerships', values);
        fitRows('#eso-team-duties-body', 'eso-add-team-duty', 'team_duties', values);
        fitRows('#eso-framework-body', 'eso-add-framework', 'logical_framework', values);
        fitRows('#eso-workplan-body', 'eso-add-workplan', 'work_plan', values);
        fitRows('#eso-budget-general-body', 'eso-add-budget-general', 'budget_general', values);

        let groupCount = 1;
        values.forEach((_, key) => {
            const match = key.match(/^budget_line_groups\[(\d+)\]/);
            if (match) {
                groupCount = Math.max(groupCount, Number(match[1]) + 1);
            }
        });
        const groups = () => document.querySelectorAll('#eso-budget-groups .eso-budget-group');
        while (groups().length < groupCount) {
            document.getElementById('eso-add-budget-group')?.click();
        }
        while (groups().length > groupCount) {
            const list = groups();
            list[list.length - 1].remove();
        }
        groups().forEach((group, groupIndex) => {
            const pattern = new RegExp(`^budget_line_groups\\[${groupIndex}\\]\\[items\\]\\[(\\d+)\\]`);
            let needed = 1;
            values.forEach((_, key) => {
                const match = key.match(pattern);
                if (match) {
                    needed = Math.max(needed, Number(match[1]) + 1);
                }
            });
            const items = () => group.querySelectorAll('.eso-budget-group-body .eso-repeater-row');
            while (items().length < needed) {
                group.querySelector('.eso-add-line-item')?.click();
            }
            while (items().length > needed) {
                const list = items();
                list[list.length - 1].remove();
            }
        });
        reindexBudgetGroups();

        // Re-link (or unlink) faculty accounts on team rows to match the draft.
        teamDutiesRepeater?.tbody.querySelectorAll(':scope > .eso-repeater-row').forEach((row, index) => {
            const userId = values.get(`team_duties[${index}][user_id]`) || '';
            const hidden = row.querySelector('[data-field="user_id"]');
            if (userId && !hidden) {
                applyFacultyToRow(row, {
                    value: userId,
                    dataset: {
                        name: values.get(`team_duties[${index}][member]`) || '',
                        college: values.get(`team_duties[${index}][college]`) || '',
                        department: values.get(`team_duties[${index}][department]`) || '',
                    },
                });
            } else if (!userId && hidden) {
                hidden.remove();
                row.querySelector('.badge')?.remove();
                row.removeAttribute('data-linked-faculty');
                row.querySelectorAll('[data-field="member"], [data-field="college"], [data-field="department"]').forEach((el) => {
                    el.readOnly = false;
                });
            }
        });
        teamDutiesRepeater?.reindex();

        values.forEach((value, key) => {
            const el = form.elements.namedItem(key);
            if (el && !(el instanceof RadioNodeList)) {
                el.value = value;
            }
        });
        if (deanInput) {
            delete deanInput.dataset.autofilled;
            if (deanHint) {
                deanHint.hidden = true;
            }
        }
        reindexBudgetGroups();
        form.querySelectorAll('textarea[data-autoresize]').forEach((textarea) => textarea.dispatchEvent(new Event('input')));
        refreshAll();
        saveDraft();
        setStatus('<i class="fa-solid fa-clock-rotate-left"></i> Unsaved changes restored — remember to save');
    };

    const banner = document.getElementById('eso-draft-banner');
    const showDraftBanner = (draft) => {
        if (!banner) {
            return;
        }
        const when = new Date(draft.savedAt).toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
        const stale = draft.serverVersion !== (form.dataset.serverVersion || '');
        document.getElementById('eso-draft-banner-detail').textContent = draft.pendingSubmit
            ? `Your last save didn't go through (${when}). Restore what you entered?`
            : `You have changes from ${when} that weren't saved.${stale ? ' This proposal was saved again since then — restoring will replace those values in this form.' : ''}`;
        banner.hidden = false;
        document.getElementById('eso-draft-restore')?.addEventListener('click', () => {
            banner.hidden = true;
            restoreDraft(draft);
        }, { once: true });
        document.getElementById('eso-draft-discard')?.addEventListener('click', () => {
            banner.hidden = true;
            draftStore.clear();
            setStatus('');
        }, { once: true });
    };

    /* ---------- Wiring ---------- */
    let validateLive = false;
    let autosaveTimer = null;
    let refreshTimer = null;
    const refreshAll = () => {
        updateComputed();
        renderChecklist();
        if (validateLive) {
            validateForm();
        }
    };
    const scheduleAutosave = () => {
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(saveDraft, 800);
    };
    const onFormChanged = () => {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refreshAll, 120);
        scheduleAutosave();
    };
    form.addEventListener('input', onFormChanged);
    form.addEventListener('change', onFormChanged);
    form.addEventListener('click', (event) => {
        if (event.target.closest('.eso-remove-row, .eso-remove-line-item, .eso-remove-budget-group, .webform-add-btn, #eso-add-budget-group, #eso-add-faculty-member')) {
            onFormChanged();
        }
    });

    form.addEventListener('submit', (event) => {
        if (event.submitter?.hasAttribute('formaction')) {
            return; // Print Preview — never blocked
        }
        const errors = validateForm();
        if (errors.length > 0) {
            event.preventDefault();
            validateLive = true;
            setStatus(`<i class="fa-solid fa-circle-exclamation"></i> Fix ${errors.length === 1 ? 'the highlighted field' : `${errors.length} highlighted fields`} before saving.`, 'is-error');
            errors[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
            errors[0].focus({ preventScroll: true });
            return;
        }
        clearTimeout(autosaveTimer);
        saveDraft({ pendingSubmit: true });
    });

    const existingDraft = draftStore.get();
    if (existingDraft && Array.isArray(existingDraft.values)) {
        const saveFailed = document.querySelector('.alert-error') !== null;
        if (existingDraft.pendingSubmit && !saveFailed) {
            draftStore.clear(); // the save went through
        } else if (JSON.stringify(existingDraft.values) === baseline) {
            draftStore.clear();
        } else {
            showDraftBanner(existingDraft);
        }
    }
    refreshAll();

    bindAutoResize(document);
})();
</script>
