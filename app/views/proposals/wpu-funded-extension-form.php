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
?>

<form class="proposal-paper completed-researches-paper eso-extension-paper wpu-funded-extension-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/update') : base_url('proposals') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="nav_scope" value="extension">
    <input type="hidden" name="project_type" value="extension">

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">EXTENSION PROGRAM/PROJECT PROPOSAL</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-03 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">I. Identifying Information</h2>
        <table class="proposal-table eso-identifying-table">
            <tr>
                <th>Title</th>
                <td colspan="3">
                    <input name="title" value="<?= htmlspecialchars($titleValue) ?>" placeholder="Enter the title of the extension program/project" required>
                </td>
            </tr>
            <tr>
                <th>College Extension Program</th>
                <td colspan="3">
                    <input name="college_extension_program" list="eso-college-program-options" value="<?= htmlspecialchars($collegeProgramValue) ?>" placeholder="Select or enter the college extension program" autocomplete="off">
                    <datalist id="eso-college-program-options">
                        <?php foreach ($collegeExtensionProgramOptions as $programOption): ?>
                            <option value="<?= htmlspecialchars($programOption) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </td>
            </tr>
            <tr>
                <th>Project Team Leader</th>
                <td colspan="3">
                    <input name="project_team_leader" value="<?= htmlspecialchars($teamLeaderValue) ?>" placeholder="Enter the project team leader">
                </td>
            </tr>
            <tr>
                <th>Members/Trainers</th>
                <td colspan="3">
                    <div class="eso-members-trainers-picker">
                        <input type="text" id="eso-members-trainers-search" list="eso-registered-user-options" placeholder="Type a registered user's name to add…" autocomplete="off">
                        <datalist id="eso-registered-user-options">
                            <?php foreach ($facultyTeamOptions as $facultyOption): ?>
                                <option value="<?= htmlspecialchars((string) $facultyOption['name']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <button type="button" class="btn btn-outline btn-sm" id="eso-members-trainers-add">Add</button>
                    </div>
                    <textarea name="members_trainers" id="eso-members-trainers" class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize placeholder="List the members and trainers"><?= htmlspecialchars($membersTrainersValue) ?></textarea>
                    <p class="proposal-section-note">Pick a suggested name to append it, or type directly in the box above for people not registered in the portal.</p>
                </td>
            </tr>
            <tr>
                <th>Implementing College / Department</th>
                <td colspan="3">
                    <?php if ($collegeOptions !== []): ?>
                        <select name="implementing_college_department">
                            <option value="">Select college / department</option>
                            <?php if ($hasLegacyCollege): ?>
                                <option value="<?= htmlspecialchars($implementingCollegeValue) ?>" selected><?= htmlspecialchars($implementingCollegeValue) ?></option>
                            <?php endif; ?>
                            <?php foreach ($collegeOptions as $collegeName): ?>
                                <option value="<?= htmlspecialchars($collegeName) ?>" <?= $implementingCollegeValue === $collegeName ? 'selected' : '' ?>><?= htmlspecialchars($collegeName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input name="implementing_college_department" value="<?= htmlspecialchars($implementingCollegeValue) ?>" placeholder="Enter implementing college / department">
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Collaborating Organizations</th>
                <td colspan="3">
                    <textarea name="collaborating_organizations" class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize placeholder="List collaborating organizations"><?= htmlspecialchars($collaboratingOrgsValue) ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Beneficiaries</th>
                <td colspan="3">
                    <input name="beneficiaries" value="<?= htmlspecialchars($beneficiariesValue) ?>" placeholder="Describe the target beneficiaries">
                </td>
            </tr>
            <tr>
                <th>Number of Male Beneficiaries</th>
                <td>
                    <input type="number" min="0" step="1" name="male_beneficiaries" value="<?= htmlspecialchars($maleBeneficiariesValue) ?>" placeholder="0">
                </td>
                <th>Number of Female Beneficiaries</th>
                <td>
                    <input type="number" min="0" step="1" name="female_beneficiaries" value="<?= htmlspecialchars($femaleBeneficiariesValue) ?>" placeholder="0">
                </td>
            </tr>
            <tr>
                <th>Duration / Inclusive Dates</th>
                <td colspan="3">
                    <div class="eso-duration-range">
                        <input type="date" name="duration_start_date" value="<?= htmlspecialchars($durationStartValue) ?>" aria-label="Start date">
                        <span class="eso-duration-range-sep">to</span>
                        <input type="date" name="duration_end_date" value="<?= htmlspecialchars($durationEndValue) ?>" aria-label="End date">
                    </div>
                    <?php if ($durationValue !== '' && $durationStartValue === '' && $durationEndValue === ''): ?>
                        <p class="proposal-section-note">Previously entered as: <?= htmlspecialchars($durationValue) ?>. Pick dates above to replace it.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Location</th>
                <td colspan="3">
                    <input name="location" value="<?= htmlspecialchars($locationValue) ?>" placeholder="Enter the project location">
                </td>
            </tr>
            <tr>
                <th>Budget</th>
                <td>
                    <input name="budget" value="<?= htmlspecialchars($budgetValue) ?>" placeholder="0.00">
                </td>
                <th>Source of Fund</th>
                <td>
                    <input name="source_of_fund" value="<?= htmlspecialchars($sourceOfFundValue) ?>" placeholder="e.g. WPU Fund">
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">II. Rationale</h2>
        <p class="proposal-section-note">The explanation of the basis for an extension program/project and its importance.</p>
        <textarea name="rationale" class="proposal-textarea" rows="6" placeholder="Explain why this extension program/project is needed and why it matters."><?= htmlspecialchars($rationaleValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">III. Objectives</h2>
        <p class="proposal-section-note">The result that the extension program/project sought to achieve.</p>
        <h3 class="proposal-subtitle">A. General objective</h3>
        <textarea name="general_objective" class="proposal-textarea" rows="3" placeholder="State the general objective."><?= htmlspecialchars($generalObjectiveValue) ?></textarea>
        <h3 class="proposal-subtitle">B. Specific objectives</h3>
        <textarea name="specific_objectives" class="proposal-textarea" rows="5" placeholder="List the specific objectives."><?= htmlspecialchars($specificObjectivesValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">IV. Project Components/Descriptions</h2>

        <h3 class="proposal-subtitle">A. Community Analysis</h3>
        <p class="proposal-section-note">Provide a reason why the activities are relevant and important in the community by explaining the current situation in the community.</p>
        <textarea name="community_analysis" class="proposal-textarea" rows="5" placeholder="Describe the current community situation."><?= htmlspecialchars($communityAnalysisValue) ?></textarea>

        <h3 class="proposal-subtitle">B. Problem Analysis</h3>
        <p class="proposal-section-note">Identify the primary issue facing the target community, its underlying causes, and its current consequences. This will clearly outline the predicament that the extension program or project can address effectively.</p>
        <textarea name="problem_analysis" class="proposal-textarea" rows="5" placeholder="Identify the primary issue, causes, and consequences."><?= htmlspecialchars($problemAnalysisValue) ?></textarea>

        <h3 class="proposal-subtitle">C. Description of the Target Group</h3>
        <p class="proposal-section-note">Describe the actual numbers, age, gender and the quality of life of the target clients.</p>
        <textarea name="target_group_description" class="proposal-textarea" rows="4" placeholder="Describe the target clients."><?= htmlspecialchars($targetGroupValue) ?></textarea>

        <h3 class="proposal-subtitle">D. Partnership</h3>
        <p class="proposal-section-note">Provide a concise overview of the tasks, areas of responsibility, and resource sharing among the collaborative partners and implementers.</p>
        <div class="proposal-table-wrap">
            <table class="proposal-table" id="eso-partnerships-table">
                <thead>
                    <tr>
                        <th>Partner</th>
                        <th>Task Description</th>
                        <th>Area of Responsibility</th>
                        <th>Resource Sharing</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="eso-partnerships-body">
                    <?php foreach ($partnerships as $index => $row): ?>
                        <tr class="eso-repeater-row">
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="partner" name="partnerships[<?= (int) $index ?>][partner]" placeholder="e.g. Barangay San Juan"><?= htmlspecialchars((string) ($row['partner'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="task_description" name="partnerships[<?= (int) $index ?>][task_description]" placeholder="e.g. Coordinate with the target participants"><?= htmlspecialchars((string) ($row['task_description'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="area_of_responsibility" name="partnerships[<?= (int) $index ?>][area_of_responsibility]" placeholder="e.g. Coordination"><?= htmlspecialchars((string) ($row['area_of_responsibility'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="resource_sharing" name="partnerships[<?= (int) $index ?>][resource_sharing]" placeholder="e.g. Facilities, report"><?= htmlspecialchars((string) ($row['resource_sharing'] ?? '')) ?></textarea></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-partnership">Add partner</button>
        </div>

        <h3 class="proposal-subtitle">E. Duties and Responsibilities of the Project Team Members</h3>
        <p class="proposal-section-note">Describe the role, task and responsibility of each member of the extension program/project. Choose registered faculty from any college or department, or add a member manually.</p>
        <?php if ($facultyTeamOptions !== []): ?>
            <div class="proposal-coauthor-picker eso-faculty-picker">
                <p class="proposal-section-note">Faculty are listed from all colleges and departments in the RIDE account registry.</p>
                <div class="eso-faculty-picker-filters">
                    <div>
                        <label for="eso-faculty-search" class="proposal-coauthor-picker-label">Search faculty</label>
                        <input type="search" id="eso-faculty-search" class="proposal-coauthor-search" placeholder="Type name, college, or department/program…" autocomplete="off">
                    </div>
                    <div>
                        <label for="eso-faculty-college-filter" class="proposal-coauthor-picker-label">College</label>
                        <select id="eso-faculty-college-filter" class="eso-faculty-college-filter">
                            <option value="">All colleges</option>
                            <?php foreach ($facultyCollegeFilters as $collegeFilter): ?>
                                <option value="<?= htmlspecialchars($collegeFilter) ?>"><?= htmlspecialchars($collegeFilter) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="eso-faculty-department-filter" class="proposal-coauthor-picker-label">Department/Program</label>
                        <select id="eso-faculty-department-filter" class="eso-faculty-college-filter">
                            <option value="">All departments/programs</option>
                            <?php foreach ($facultyDepartmentFilters as $departmentFilter): ?>
                                <option value="<?= htmlspecialchars($departmentFilter) ?>"><?= htmlspecialchars($departmentFilter) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <label for="eso-faculty-select" class="proposal-coauthor-picker-label">Faculty directory</label>
                <select id="eso-faculty-select" class="proposal-coauthor-select eso-faculty-select" size="8" aria-label="Faculty across colleges and departments">
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
                <button type="button" id="eso-add-faculty-member" class="btn btn-outline btn-sm">Add selected faculty</button>
            </div>
        <?php endif; ?>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns. Use &ldquo;Add member&rdquo; for people who are not in the faculty directory.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table eso-team-duties-table" id="eso-team-duties-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>College</th>
                        <th>Department/Program</th>
                        <th>Role</th>
                        <th>Task Description</th>
                        <th>Responsibility</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="eso-team-duties-body">
                    <?php foreach ($teamDuties as $index => $row): ?>
                        <?php $linkedUserId = (int) ($row['user_id'] ?? 0); ?>
                        <tr class="eso-repeater-row"<?= $linkedUserId > 0 ? ' data-linked-faculty="1"' : '' ?>>
                            <td>
                                <?php if ($linkedUserId > 0): ?>
                                    <span class="badge badge-ongoing">Faculty account</span>
                                    <input type="hidden" data-field="user_id" name="team_duties[<?= (int) $index ?>][user_id]" value="<?= $linkedUserId ?>">
                                <?php endif; ?>
                                <textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="member" name="team_duties[<?= (int) $index ?>][member]" placeholder="Name" <?= $linkedUserId > 0 ? 'readonly' : '' ?>><?= htmlspecialchars((string) ($row['member'] ?? '')) ?></textarea>
                            </td>
                            <td><input data-field="college" name="team_duties[<?= (int) $index ?>][college]" value="<?= htmlspecialchars((string) ($row['college'] ?? '')) ?>" placeholder="College" <?= $linkedUserId > 0 ? 'readonly' : '' ?>></td>
                            <td><input data-field="department" name="team_duties[<?= (int) $index ?>][department]" value="<?= htmlspecialchars((string) ($row['department'] ?? '')) ?>" placeholder="Department/Program" <?= $linkedUserId > 0 ? 'readonly' : '' ?>></td>
                            <td><input data-field="role" name="team_duties[<?= (int) $index ?>][role]" value="<?= htmlspecialchars((string) ($row['role'] ?? '')) ?>" placeholder="Role"></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="task_description" name="team_duties[<?= (int) $index ?>][task_description]" placeholder="Task description"><?= htmlspecialchars((string) ($row['task_description'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="responsibility" name="team_duties[<?= (int) $index ?>][responsibility]" placeholder="Responsibility"><?= htmlspecialchars((string) ($row['responsibility'] ?? '')) ?></textarea></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-team-duty">Add member</button>
        </div>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">V. Logical Framework</h2>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table eso-framework-table" id="eso-framework-table">
                <thead>
                    <tr>
                        <th>Inputs</th>
                        <th>Activities</th>
                        <th>Outputs</th>
                        <th>Effects</th>
                        <th>Outcomes</th>
                        <th>Impact</th>
                        <th>Sustainable Development Goals</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="eso-framework-body">
                    <?php foreach ($logicalFramework as $index => $row): ?>
                        <tr class="eso-repeater-row">
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="inputs" name="logical_framework[<?= (int) $index ?>][inputs]" placeholder="e.g. WPU Fund – P5,000.00"><?= htmlspecialchars((string) ($row['inputs'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="activities" name="logical_framework[<?= (int) $index ?>][activities]" placeholder="e.g. Conduct workshops on sustainable agriculture"><?= htmlspecialchars((string) ($row['activities'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="outputs" name="logical_framework[<?= (int) $index ?>][outputs]" placeholder="e.g. Number of participants trained"><?= htmlspecialchars((string) ($row['outputs'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="effects" name="logical_framework[<?= (int) $index ?>][effects]" placeholder="e.g. Increased awareness and skills"><?= htmlspecialchars((string) ($row['effects'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="outcomes" name="logical_framework[<?= (int) $index ?>][outcomes]" placeholder="e.g. Sustainable farming practices adopted"><?= htmlspecialchars((string) ($row['outcomes'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="impact" name="logical_framework[<?= (int) $index ?>][impact]" placeholder="e.g. Long-term economic stability in the region"><?= htmlspecialchars((string) ($row['impact'] ?? '')) ?></textarea></td>
                            <td>
                                <select data-field="sdg" name="logical_framework[<?= (int) $index ?>][sdg]">
                                    <option value="">Select SDG</option>
                                    <?php foreach ($sdgOptions as $sdg): ?>
                                        <option value="<?= htmlspecialchars($sdg) ?>" <?= (string) ($row['sdg'] ?? '') === $sdg ? 'selected' : '' ?>><?= htmlspecialchars($sdg) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-framework">Add row</button>
        </div>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VI. Methodology</h2>
        <p class="proposal-section-note">Provide a description of the training techniques, methods, tools and materials to be used of the project as well as the monitoring and evaluation scheme.</p>
        <textarea name="methodology" class="proposal-textarea proposal-textarea-lg" rows="6" placeholder="Describe the methods, tools, materials, and monitoring and evaluation scheme."><?= htmlspecialchars($methodologyValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VII. Activities</h2>
        <textarea name="activities_narrative" class="proposal-textarea" rows="6" placeholder="Describe the extension activities to be conducted."><?= htmlspecialchars($activitiesNarrativeValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">VIII. Work and Financial Plan</h2>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table eso-workplan-table" id="eso-workplan-table">
                <thead>
                    <tr>
                        <th>Activities</th>
                        <th>Objective</th>
                        <th>Indicator</th>
                        <th>Strategies</th>
                        <th>Time Frame</th>
                        <th>Responsible Persons</th>
                        <th>Budget Needed</th>
                        <th>Output/s</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="eso-workplan-body">
                    <?php foreach ($workPlan as $index => $row): ?>
                        <tr class="eso-repeater-row">
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="activities" name="work_plan[<?= (int) $index ?>][activities]" placeholder="e.g. Training Workshop on …"><?= htmlspecialchars((string) ($row['activities'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="objective" name="work_plan[<?= (int) $index ?>][objective]" placeholder="Objective"><?= htmlspecialchars((string) ($row['objective'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="indicator" name="work_plan[<?= (int) $index ?>][indicator]" placeholder="e.g. Number of farmers trained"><?= htmlspecialchars((string) ($row['indicator'] ?? '')) ?></textarea></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="strategies" name="work_plan[<?= (int) $index ?>][strategies]" placeholder="e.g. Partner with local NGOs"><?= htmlspecialchars((string) ($row['strategies'] ?? '')) ?></textarea></td>
                            <td><input data-field="time_frame" name="work_plan[<?= (int) $index ?>][time_frame]" value="<?= htmlspecialchars((string) ($row['time_frame'] ?? '')) ?>" placeholder="e.g. June 1-2, 2025"></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="responsible_persons" name="work_plan[<?= (int) $index ?>][responsible_persons]" placeholder="Responsible persons"><?= htmlspecialchars((string) ($row['responsible_persons'] ?? '')) ?></textarea></td>
                            <td><input data-field="budget_needed" name="work_plan[<?= (int) $index ?>][budget_needed]" value="<?= htmlspecialchars((string) ($row['budget_needed'] ?? '')) ?>" placeholder="0.00"></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="outputs" name="work_plan[<?= (int) $index ?>][outputs]" placeholder="e.g. Accomplishment Report (ETR)"><?= htmlspecialchars((string) ($row['outputs'] ?? '')) ?></textarea></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-workplan">Add activity</button>
        </div>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">IX. Budgetary Requirements</h2>
        <h3 class="proposal-subtitle">A. General Breakdown of the Budgetary Requirements</h3>
        <div class="proposal-table-wrap">
            <table class="proposal-table" id="eso-budget-general-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Particulars</th>
                        <th>Amount</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody id="eso-budget-general-body">
                    <?php foreach ($budgetGeneral as $index => $row): ?>
                        <tr class="eso-repeater-row">
                            <td><input data-field="item" name="budget_general[<?= (int) $index ?>][item]" value="<?= htmlspecialchars((string) ($row['item'] ?? '')) ?>" placeholder="Item"></td>
                            <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="particulars" name="budget_general[<?= (int) $index ?>][particulars]" placeholder="Particulars"><?= htmlspecialchars((string) ($row['particulars'] ?? '')) ?></textarea></td>
                            <td><input data-field="amount" name="budget_general[<?= (int) $index ?>][amount]" value="<?= htmlspecialchars((string) ($row['amount'] ?? '')) ?>" placeholder="0.00"></td>
                            <td class="completed-researches-actions-col">
                                <button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-budget-general">Add item</button>
        </div>

        <div id="eso-budget-groups">
            <?php foreach ($budgetLineGroups as $groupIndex => $group): ?>
                <?php
                $groupLabel = (string) ($group['label'] ?? ('Line-Item Budget ' . ($groupIndex + 1)));
                $groupItems = is_array($group['items'] ?? null) ? $group['items'] : [$blankBudgetItem];
                ?>
                <div class="eso-budget-group" data-group-index="<?= (int) $groupIndex ?>">
                    <h3 class="proposal-subtitle">
                        <?= (int) $groupIndex + 1 ?>. Specific Breakdown of Budget of
                        <input class="eso-budget-group-label" data-field="label" name="budget_line_groups[<?= (int) $groupIndex ?>][label]" value="<?= htmlspecialchars($groupLabel) ?>">
                    </h3>
                    <div class="proposal-table-wrap">
                        <table class="proposal-table eso-line-item-table">
                            <thead>
                                <tr>
                                    <th>Item no.</th>
                                    <th>Particulars</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Cost/unit</th>
                                    <th>Total cost</th>
                                    <th class="completed-researches-actions-col">Action</th>
                                </tr>
                            </thead>
                            <tbody class="eso-budget-group-body">
                                <?php foreach ($groupItems as $itemIndex => $item): ?>
                                    <tr class="eso-repeater-row">
                                        <td><input data-field="item_no" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][item_no]" value="<?= htmlspecialchars((string) ($item['item_no'] ?? '')) ?>" placeholder="<?= (int) $itemIndex + 1 ?>"></td>
                                        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="particulars" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][particulars]" placeholder="e.g. Bond Paper"><?= htmlspecialchars((string) ($item['particulars'] ?? '')) ?></textarea></td>
                                        <td><input data-field="qty" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][qty]" value="<?= htmlspecialchars((string) ($item['qty'] ?? '')) ?>" placeholder="Qty"></td>
                                        <td><input data-field="unit" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][unit]" value="<?= htmlspecialchars((string) ($item['unit'] ?? '')) ?>" placeholder="e.g. ream"></td>
                                        <td><input data-field="cost_unit" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][cost_unit]" value="<?= htmlspecialchars((string) ($item['cost_unit'] ?? '')) ?>" placeholder="0.00"></td>
                                        <td><input data-field="total_cost" class="eso-line-total" name="budget_line_groups[<?= (int) $groupIndex ?>][items][<?= (int) $itemIndex ?>][total_cost]" value="<?= htmlspecialchars((string) ($item['total_cost'] ?? '')) ?>" placeholder="0.00" readonly tabindex="-1"></td>
                                        <td class="completed-researches-actions-col">
                                            <button type="button" class="btn btn-outline btn-sm eso-remove-line-item">Remove</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="eso-subtotal-row">
                                    <td colspan="5"><strong>Sub-Total</strong></td>
                                    <td><input class="eso-group-subtotal" value="" readonly tabindex="-1" aria-label="Sub-total"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="completed-researches-add-row-wrap">
                        <button type="button" class="btn btn-outline btn-sm eso-add-line-item">Add line item</button>
                        <?php if ($groupIndex > 0): ?>
                            <button type="button" class="btn btn-outline btn-sm eso-remove-budget-group">Remove this budget</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm" id="eso-add-budget-group">Add line-item budget</button>
        </div>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">X. References</h2>
        <textarea name="references" class="proposal-textarea" rows="5" placeholder="List the references used in this proposal."><?= htmlspecialchars($referencesValue) ?></textarea>
    </section>

    <section class="proposal-section completed-researches-signoff">
        <div class="eso-signoff-grid">
            <div>
                <p class="completed-researches-signoff-label">Submitted by:</p>
                <input name="proponent_name" value="<?= htmlspecialchars($proponentNameValue) ?>" placeholder="Proponent">
                <input type="date" name="proponent_date" value="<?= htmlspecialchars($proponentDateValue) ?>">
                <p class="completed-researches-signoff-role">Proponent / Date</p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Endorsed by:</p>
                <input name="dean_name" value="<?= htmlspecialchars($deanNameValue) ?>" placeholder="Dean">
                <input type="date" name="dean_date" value="<?= htmlspecialchars($deanDateValue) ?>">
                <p class="completed-researches-signoff-role">Dean / Date</p>
            </div>
            <div>
                <p class="completed-researches-signoff-label">Received by:</p>
                <input name="extension_admin_name" value="<?= htmlspecialchars($adminNameValue) ?>" placeholder="Extension Admin Staff">
                <input type="date" name="extension_admin_date" value="<?= htmlspecialchars($adminDateValue) ?>">
                <p class="completed-researches-signoff-role">Extension Admin Staff / Date</p>
            </div>
        </div>
        <p class="proposal-section-note">Official approval signatures are also captured through the workflow after submission.</p>
    </section>

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

    <div class="actions proposal-form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<template id="eso-partnership-row-template">
    <tr class="eso-repeater-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="partner" placeholder="e.g. Barangay San Juan"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="task_description" placeholder="e.g. Coordinate with the target participants"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="area_of_responsibility" placeholder="e.g. Coordination"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="resource_sharing" placeholder="e.g. Facilities, report"></textarea></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button></td>
    </tr>
</template>

<template id="eso-team-duty-row-template">
    <tr class="eso-repeater-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="member" placeholder="Name"></textarea></td>
        <td><input data-field="college" placeholder="College"></td>
        <td><input data-field="department" placeholder="Department/Program"></td>
        <td><input data-field="role" placeholder="Role"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="task_description" placeholder="Task description"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="responsibility" placeholder="Responsibility"></textarea></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button></td>
    </tr>
</template>

<template id="eso-framework-row-template">
    <tr class="eso-repeater-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="inputs" placeholder="e.g. WPU Fund – P5,000.00"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="activities" placeholder="e.g. Conduct workshops on sustainable agriculture"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="outputs" placeholder="e.g. Number of participants trained"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="effects" placeholder="e.g. Increased awareness and skills"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="outcomes" placeholder="e.g. Sustainable farming practices adopted"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="3" data-autoresize data-field="impact" placeholder="e.g. Long-term economic stability in the region"></textarea></td>
        <td>
            <select data-field="sdg">
                <option value="">Select SDG</option>
                <?php foreach ($sdgOptions as $sdg): ?>
                    <option value="<?= htmlspecialchars($sdg) ?>"><?= htmlspecialchars($sdg) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button></td>
    </tr>
</template>

<template id="eso-workplan-row-template">
    <tr class="eso-repeater-row">
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="activities" placeholder="e.g. Training Workshop on …"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="objective" placeholder="Objective"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="indicator" placeholder="e.g. Number of farmers trained"></textarea></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="strategies" placeholder="e.g. Partner with local NGOs"></textarea></td>
        <td><input data-field="time_frame" placeholder="e.g. June 1-2, 2025"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="responsible_persons" placeholder="Responsible persons"></textarea></td>
        <td><input data-field="budget_needed" placeholder="0.00"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="outputs" placeholder="e.g. Accomplishment Report (ETR)"></textarea></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button></td>
    </tr>
</template>

<template id="eso-budget-general-row-template">
    <tr class="eso-repeater-row">
        <td><input data-field="item" placeholder="Item"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="particulars" placeholder="Particulars"></textarea></td>
        <td><input data-field="amount" placeholder="0.00"></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-row">Remove</button></td>
    </tr>
</template>

<template id="eso-line-item-row-template">
    <tr class="eso-repeater-row">
        <td><input data-field="item_no" placeholder="1"></td>
        <td><textarea class="proposal-textarea proposal-textarea-compact" rows="2" data-autoresize data-field="particulars" placeholder="e.g. Bond Paper"></textarea></td>
        <td><input data-field="qty" placeholder="Qty"></td>
        <td><input data-field="unit" placeholder="e.g. ream"></td>
        <td><input data-field="cost_unit" placeholder="0.00"></td>
        <td><input data-field="total_cost" class="eso-line-total" placeholder="0.00" readonly tabindex="-1"></td>
        <td class="completed-researches-actions-col"><button type="button" class="btn btn-outline btn-sm eso-remove-line-item">Remove</button></td>
    </tr>
</template>

<template id="eso-budget-group-template">
    <div class="eso-budget-group" data-group-index="">
        <h3 class="proposal-subtitle">
            <span class="eso-budget-group-number">1</span>. Specific Breakdown of Budget of
            <input class="eso-budget-group-label" data-field="label" value="Line-Item Budget">
        </h3>
        <div class="proposal-table-wrap">
            <table class="proposal-table eso-line-item-table">
                <thead>
                    <tr>
                        <th>Item no.</th>
                        <th>Particulars</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Cost/unit</th>
                        <th>Total cost</th>
                        <th class="completed-researches-actions-col">Action</th>
                    </tr>
                </thead>
                <tbody class="eso-budget-group-body">
                    <tr class="eso-subtotal-row">
                        <td colspan="5"><strong>Sub-Total</strong></td>
                        <td><input class="eso-group-subtotal" value="" readonly tabindex="-1" aria-label="Sub-total"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="completed-researches-add-row-wrap">
            <button type="button" class="btn btn-outline btn-sm eso-add-line-item">Add line item</button>
            <button type="button" class="btn btn-outline btn-sm eso-remove-budget-group">Remove this budget</button>
        </div>
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
        const memberCell = row.querySelector('[data-field="member"]')?.closest('td') ?? row.querySelector('td');
        if (memberCell && !row.querySelector('[data-field="user_id"]')) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-ongoing';
            badge.textContent = 'Faculty account';
            memberCell.insertBefore(badge, memberCell.firstChild);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.dataset.field = 'user_id';
            hidden.value = option.value;
            memberCell.insertBefore(hidden, memberCell.firstChild);
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

    bindAutoResize(document);
})();
</script>
