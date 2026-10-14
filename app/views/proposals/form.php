<?php
/** @var array|null $proposal */
/** @var string|null $lockedProjectType */
/** @var list<array<string, mixed>> $colleges */
/** @var list<array{id: int, label: string, last_name: string, first_name: string, middle_name: string, college_name: string}> $facultyCoAuthorOptions */
$isEdit = $proposal !== null;
$lockedProjectType = $isEdit ? null : ($lockedProjectType ?? proposal_nav_scope());
$isExtensionNav = $lockedProjectType === 'extension';
$proposalLabel = $isExtensionNav ? 'Extension' : 'Research';
$pageTitle = ($isEdit ? 'Edit' : ($isExtensionNav ? $proposalLabel . ' Proposal' : 'Applicant\'s Information')) . ' | RIDE';
$pageHeading = $isEdit
    ? 'Edit ' . $proposalLabel . ' Proposal'
    : ($isExtensionNav ? $proposalLabel . ' Proposal' : 'Applicant\'s Information');
$pageSubtitle = $isEdit
    ? 'Update your proposal fields and save or resubmit for review.'
    : ($isExtensionNav
        ? 'Complete all sections of the extension proposal form before saving or submitting.'
        : 'Complete all sections of the applicant\'s information and research proposal form before saving or submitting.');
$types = ['research', 'innovation', 'development', 'extension'];
$user = \App\Core\Auth::user() ?? [];
$colleges = $colleges ?? [];
$facultyCoAuthorOptions = $facultyCoAuthorOptions ?? [];
$sampleProposal = null;

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    } else {
        $summaryData['abstract'] = (string) $proposal['summary'];
    }
}

$sampleSummaryData = [];

$field = static function (string $key, string $fallback = '') use ($summaryData, $sampleSummaryData, $isEdit): string {
    if ($isEdit && isset($summaryData[$key]) && is_string($summaryData[$key])) {
        return $summaryData[$key];
    }

    if (isset($summaryData[$key]) && is_string($summaryData[$key]) && trim($summaryData[$key]) !== '') {
        return $summaryData[$key];
    }

    $sampleValue = $sampleSummaryData[$key] ?? null;
    if (is_string($sampleValue) && trim($sampleValue) !== '') {
        return $sampleValue;
    }

    return $fallback;
};

$budgetItems = $summaryData['budget_items'] ?? $sampleSummaryData['budget_items'] ?? [];
if (!is_array($budgetItems)) {
    $budgetItems = [];
}
for ($i = count($budgetItems); $i < 3; $i++) {
    $budgetItems[] = ['item' => '', 'amount' => '', 'justification' => ''];
}
$budgetItems = array_slice($budgetItems, 0, 3);

$implementationPlan = $summaryData['implementation_plan'] ?? $sampleSummaryData['implementation_plan'] ?? [];
if (!is_array($implementationPlan)) {
    $implementationPlan = [];
}
for ($i = count($implementationPlan); $i < 8; $i++) {
    $implementationPlan[] = ['activity' => '', 'months' => []];
}
$implementationPlan = array_slice($implementationPlan, 0, 8);

$sixPsOptions = [
    'patent_granted' => 'Patent granted',
    'publication' => 'Publication',
    'people_trained' => 'People trained',
    'partnership_developed' => 'Partnership developed',
    'products_processes_developed' => 'Products/Processes developed',
    'policies_formulated' => 'Policies formulated',
];
$applicantPrefixOptions = [
    'Doctor',
    'Professor',
    'Associate Professor',
    'Assistant Professor',
    'Instructor',
    'Staff',
];
$applicantPrefixAliases = [
    'Dr.' => 'Doctor',
    'Prof.' => 'Professor',
    'Asso. Prof.' => 'Associate Professor',
    'Asst. Prof.' => 'Assistant Professor',
];
$applicantSexOptions = ['Male', 'Female'];
$applicantCampusOptions = [
    'Main Campus',
    'Puerto Princesa City Campus',
    'Quezon Campus',
    'Nido Campus',
    'Culion Campus',
    'Busuanga Campus',
    'Rio Tuba Extension School',
    'Canique Extension School',
    'Binduyan Marine Research Station',
];
$selectedSixPs = array_fill_keys(
    array_values(array_filter($summaryData['six_ps'] ?? $sampleSummaryData['six_ps'] ?? [], static fn ($value): bool => is_string($value))),
    true
);
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$applicantLastNameValue = $field('applicant_last_name');
$applicantFirstNameValue = $field('applicant_first_name');
$applicantMiddleNameValue = $field('applicant_middle_name');
$applicantTitlePrefixValue = $field('applicant_title_prefix');
if (isset($applicantPrefixAliases[$applicantTitlePrefixValue])) {
    $applicantTitlePrefixValue = $applicantPrefixAliases[$applicantTitlePrefixValue];
}
$applicantSexValue = $field('applicant_sex');
$applicantPositionValue = $field('applicant_position');
$applicantEmailValue = $field('applicant_email');
$applicantContactNumberValue = $field('applicant_contact_number');
$applicantCollegeDepartmentValue = $field('applicant_college_department');
if ($applicantCollegeDepartmentValue === '' && !$isEdit) {
    $userCollegeId = (int) ($user['college_id'] ?? 0);
    if ($userCollegeId > 0) {
        foreach ($colleges as $college) {
            if ((int) ($college['id'] ?? 0) === $userCollegeId) {
                $applicantCollegeDepartmentValue = (string) ($college['name'] ?? '');
                break;
            }
        }
    }
    if ($applicantCollegeDepartmentValue === '' && !empty($user['college_name'])) {
        $applicantCollegeDepartmentValue = (string) $user['college_name'];
    }
}
$collegeDepartmentOptions = array_values(array_filter(
    array_map(static fn (array $college): string => trim((string) ($college['name'] ?? '')), $colleges),
    static fn (string $name): bool => $name !== ''
));
$hasLegacyCollegeDepartment = $applicantCollegeDepartmentValue !== ''
    && !in_array($applicantCollegeDepartmentValue, $collegeDepartmentOptions, true);
$applicantProgramValue = $field('applicant_program');
$applicantCampusValue = $field('applicant_campus');
if ($applicantCampusValue === '' && !$isEdit && !empty($user['campus_name'])) {
    $applicantCampusValue = (string) $user['campus_name'];
}
$hasLegacyCampus = $applicantCampusValue !== ''
    && !in_array($applicantCampusValue, $applicantCampusOptions, true);
$applicantGoogleScholarValue = $field('applicant_google_scholar_link');
$applicantResearchGateValue = $field('applicant_researchgate_link');
$coAuthors = $summaryData['coauthors'] ?? $sampleSummaryData['coauthors'] ?? [];
if (!is_array($coAuthors)) {
    $coAuthors = [];
}

if ($coAuthors === []) {
    $legacyCoAuthor = [
        'last_name' => $field('coauthor_last_name'),
        'first_name' => $field('coauthor_first_name'),
        'middle_name' => $field('coauthor_middle_name'),
    ];

    if (
        $legacyCoAuthor['last_name'] !== ''
        || $legacyCoAuthor['first_name'] !== ''
        || $legacyCoAuthor['middle_name'] !== ''
    ) {
        $coAuthors[] = $legacyCoAuthor;
    }
}

$normalizedCoAuthors = [];
foreach ($coAuthors as $coAuthor) {
    if (!is_array($coAuthor)) {
        continue;
    }

    $entry = [
        'last_name' => trim((string) ($coAuthor['last_name'] ?? '')),
        'first_name' => trim((string) ($coAuthor['first_name'] ?? '')),
        'middle_name' => trim((string) ($coAuthor['middle_name'] ?? '')),
    ];
    $linkedUserId = (int) ($coAuthor['user_id'] ?? 0);
    if ($linkedUserId > 0) {
        $entry['user_id'] = $linkedUserId;
    }
    $normalizedCoAuthors[] = $entry;
}

if ($normalizedCoAuthors === []) {
    $normalizedCoAuthors[] = ['last_name' => '', 'first_name' => '', 'middle_name' => ''];
}
$coAuthors = $normalizedCoAuthors;
$titleValue = (string) ($proposal['title'] ?? '');
$fundingSourceValue = (string) ($proposal['funding_source'] ?? '');
$projectTypeValue = (string) ($proposal['project_type'] ?? ($lockedProjectType ?? 'research'));
$riskLevelValue = (string) ($proposal['risk_level'] ?? 'low');
$ethicsRequired = $proposal !== null
    ? !empty($proposal['ethics_required'])
    : false;
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/update') : base_url('proposals') ?>">
    <?= csrf_field() ?>
    <?php if ($lockedProjectType !== null): ?>
        <input type="hidden" name="nav_scope" value="<?= htmlspecialchars($lockedProjectType) ?>">
        <input type="hidden" name="project_type" value="<?= htmlspecialchars($lockedProjectType) ?>">
    <?php endif; ?>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Applicant&apos;s Information</h2>

        <table class="proposal-table">
            <tr>
                <th>Name of Applicant</th>
                <td>
                    <span class="proposal-inline-label">Last Name</span>
                    <input name="applicant_last_name" value="<?= htmlspecialchars($applicantLastNameValue) ?>" placeholder="Enter last name">
                </td>
                <td>
                    <span class="proposal-inline-label">First Name</span>
                    <input name="applicant_first_name" value="<?= htmlspecialchars($applicantFirstNameValue) ?>" placeholder="Enter first name">
                </td>
                <td>
                    <span class="proposal-inline-label">Middle Name</span>
                    <input name="applicant_middle_name" value="<?= htmlspecialchars($applicantMiddleNameValue) ?>" placeholder="Enter middle name">
                </td>
            </tr>
            <tr>
                <th>Title/Prefix</th>
                <td colspan="2">
                    <select name="applicant_title_prefix">
                        <option value="">Select title/prefix</option>
                        <?php foreach ($applicantPrefixOptions as $prefix): ?>
                            <option value="<?= htmlspecialchars($prefix) ?>" <?= $applicantTitlePrefixValue === $prefix ? 'selected' : '' ?>><?= htmlspecialchars($prefix) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <label class="proposal-inline-label" for="applicant_sex">Sex</label>
                    <select id="applicant_sex" name="applicant_sex">
                        <option value="">Select sex</option>
                        <?php foreach ($applicantSexOptions as $sex): ?>
                            <option value="<?= htmlspecialchars($sex) ?>" <?= $applicantSexValue === $sex ? 'selected' : '' ?>><?= htmlspecialchars($sex) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Position held</th>
                <td colspan="3">
                    <input name="applicant_position" value="<?= htmlspecialchars($applicantPositionValue) ?>" placeholder="Enter position held">
                </td>
            </tr>
            <tr>
                <th>E-mail</th>
                <td><input type="email" name="applicant_email" value="<?= htmlspecialchars($applicantEmailValue) ?>" placeholder="Enter e-mail"></td>
                <td>Contact Number</td>
                <td><input name="applicant_contact_number" value="<?= htmlspecialchars($applicantContactNumberValue) ?>" placeholder="Enter contact number"></td>
            </tr>
            <tr>
                <th>College/Department</th>
                <td colspan="3">
                    <select id="applicant_college_department" name="applicant_college_department">
                        <option value="">Select college/department</option>
                        <?php if ($hasLegacyCollegeDepartment): ?>
                            <option value="<?= htmlspecialchars($applicantCollegeDepartmentValue) ?>" selected><?= htmlspecialchars($applicantCollegeDepartmentValue) ?></option>
                        <?php endif; ?>
                        <?php foreach ($colleges as $college): ?>
                            <?php $collegeName = trim((string) ($college['name'] ?? '')); ?>
                            <?php if ($collegeName === ''): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <option
                                value="<?= htmlspecialchars($collegeName) ?>"
                                <?= $applicantCollegeDepartmentValue === $collegeName ? 'selected' : '' ?>
                            ><?= htmlspecialchars($collegeName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Program</th>
                <td colspan="3"><input name="applicant_program" value="<?= htmlspecialchars($applicantProgramValue) ?>" placeholder="Enter program"></td>
            </tr>
            <tr>
                <th>Campus</th>
                <td colspan="3">
                    <select id="applicant_campus" name="applicant_campus">
                        <option value="">Select campus</option>
                        <?php if ($hasLegacyCampus): ?>
                            <option value="<?= htmlspecialchars($applicantCampusValue) ?>" selected><?= htmlspecialchars($applicantCampusValue) ?></option>
                        <?php endif; ?>
                        <?php foreach ($applicantCampusOptions as $campus): ?>
                            <option
                                value="<?= htmlspecialchars($campus) ?>"
                                <?= $applicantCampusValue === $campus ? 'selected' : '' ?>
                            ><?= htmlspecialchars($campus) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Google Scholar account link</th>
                <td colspan="3"><input type="url" name="applicant_google_scholar_link" value="<?= htmlspecialchars($applicantGoogleScholarValue) ?>" placeholder="Enter Google Scholar account link"></td>
            </tr>
            <tr>
                <th>ResearchGate account link</th>
                <td colspan="3"><input type="url" name="applicant_researchgate_link" value="<?= htmlspecialchars($applicantResearchGateValue) ?>" placeholder="Enter ResearchGate account link"></td>
            </tr>
            <tr>
                <th>Co-Author(s)</th>
                <td colspan="3" class="proposal-coauthors-cell">
                    <div class="proposal-coauthors-wrap">
                        <?php if ($facultyCoAuthorOptions !== []): ?>
                        <div class="proposal-coauthor-picker">
                            <p class="proposal-section-note">Tag registered faculty from the RIDE account registry. They receive a notification and must accept before they can view this proposal on their dashboard.</p>
                            <label for="faculty-coauthor-search" class="proposal-coauthor-picker-label">Search faculty</label>
                            <input type="search" id="faculty-coauthor-search" class="proposal-coauthor-search" placeholder="Type name or college…" autocomplete="off">
                            <select id="faculty-coauthor-select" class="proposal-coauthor-select" size="6" aria-label="Registered faculty co-authors">
                                <?php foreach ($facultyCoAuthorOptions as $option): ?>
                                    <option
                                        value="<?= (int) $option['id'] ?>"
                                        data-last-name="<?= htmlspecialchars($option['last_name']) ?>"
                                        data-first-name="<?= htmlspecialchars($option['first_name']) ?>"
                                        data-middle-name="<?= htmlspecialchars($option['middle_name']) ?>"
                                        data-college="<?= htmlspecialchars($option['college_name']) ?>"
                                    ><?= htmlspecialchars($option['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="add-faculty-coauthor" class="btn btn-outline btn-sm">Add Selected Faculty</button>
                        </div>
                        <?php endif; ?>
                        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns. Use &ldquo;Add Row&rdquo; for co-authors not registered in RIDE.</p>
                        <div class="proposal-table-wrap trainings-conducted-table-wrap">
                        <table id="proposal-coauthors-table" class="proposal-table proposal-coauthors-table trainings-conducted-table">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th class="proposal-coauthors-action-col">Action</th>
                                </tr>
                            </thead>
                            <tbody id="proposal-coauthors-body">
                                <?php foreach ($coAuthors as $index => $coAuthor): ?>
                                    <?php
                                    $linkedUserId = (int) ($coAuthor['user_id'] ?? 0);
                                    $invitationStatus = trim((string) ($coAuthor['invitation_status'] ?? ''));
                                    $invitationLabel = $linkedUserId > 0
                                        ? \App\Support\ProposalCoAuthors::invitationStatusLabel($invitationStatus)
                                        : '';
                                    ?>
                                    <tr<?= $linkedUserId > 0 ? ' data-linked-faculty="1"' : '' ?>>
                                        <td class="proposal-coauthor-source-cell">
                                            <?php if ($linkedUserId > 0): ?>
                                                <span class="badge badge-ongoing">Faculty account</span>
                                                <?php if ($invitationLabel !== ''): ?>
                                                    <span class="badge <?= $invitationStatus === 'accepted' ? 'badge-ongoing' : ($invitationStatus === 'rejected' ? 'badge-returned' : 'badge-under_review') ?>"><?= htmlspecialchars($invitationLabel) ?></span>
                                                <?php endif; ?>
                                                <input type="hidden" data-field="user_id" name="coauthors[<?= $index ?>][user_id]" value="<?= $linkedUserId ?>">
                                            <?php else: ?>
                                                <span class="muted">Manual</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <textarea
                                                data-field="last_name"
                                                name="coauthors[<?= $index ?>][last_name]"
                                                class="proposal-coauthor-field"
                                                rows="2"
                                                data-autoresize
                                                placeholder="Enter last name"
                                                <?= $linkedUserId > 0 ? 'readonly' : '' ?>
                                            ><?= htmlspecialchars((string) ($coAuthor['last_name'] ?? '')) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea
                                                data-field="first_name"
                                                name="coauthors[<?= $index ?>][first_name]"
                                                class="proposal-coauthor-field"
                                                rows="2"
                                                data-autoresize
                                                placeholder="Enter first name"
                                                <?= $linkedUserId > 0 ? 'readonly' : '' ?>
                                            ><?= htmlspecialchars((string) ($coAuthor['first_name'] ?? '')) ?></textarea>
                                        </td>
                                        <td>
                                            <textarea
                                                data-field="middle_name"
                                                name="coauthors[<?= $index ?>][middle_name]"
                                                class="proposal-coauthor-field"
                                                rows="2"
                                                data-autoresize
                                                placeholder="Enter middle name"
                                            ><?= htmlspecialchars((string) ($coAuthor['middle_name'] ?? '')) ?></textarea>
                                        </td>
                                        <td class="proposal-coauthors-action-cell">
                                            <button type="button" class="btn btn-outline btn-sm" data-action="delete-coauthor">Delete Row</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <div class="proposal-coauthors-actions">
                            <button type="button" id="add-coauthor-row" class="btn btn-outline btn-sm">Add Row</button>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <p class="proposal-section-note">Provide the primary applicant&apos;s information in the fields above.</p>
    </section>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Project Information</h2>

        <table class="proposal-table">
            <tr>
                <th>Title of Proposed Research</th>
                <td colspan="3">
                    <input name="title" value="<?= htmlspecialchars($titleValue) ?>" placeholder="Indicate the title of research" required>
                </td>
            </tr>
            <tr>
                <th>Period Covered</th>
                <td>
                    <input name="period_covered" value="<?= htmlspecialchars($field('period_covered')) ?>" placeholder="e.g. June 2026 to May 2027">
                </td>
                <th>Duration in Months</th>
                <td>
                    <input name="duration_months" value="<?= htmlspecialchars($field('duration_months')) ?>" placeholder="e.g. 12">
                </td>
            </tr>
            <tr>
                <th>Funding/Support</th>
                <td colspan="3">
                    <input name="funding_source" value="<?= htmlspecialchars($fundingSourceValue) ?>" placeholder="WPU funded, University Supported through Official time, external, etc.">
                </td>
            </tr>
        </table>
        <p class="proposal-section-note">For Funding/Support, indicate if the research is WPU Funded or University Supported through Official time.</p>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Project Classification</h3>

        <table class="proposal-table">
            <tr>
                <th>Project Type</th>
                <td>
                    <?php if ($lockedProjectType !== null): ?>
                        <input type="text" value="<?= htmlspecialchars(ucfirst($lockedProjectType)) ?>" readonly>
                    <?php else: ?>
                    <select name="project_type" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $projectTypeValue === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </td>
                <th>Risk Level</th>
                <td>
                    <select name="risk_level">
                        <?php foreach (['low', 'medium', 'high'] as $r): ?>
                            <option value="<?= $r ?>" <?= $riskLevelValue === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th>Ethics Review</th>
                <td colspan="3">
                    <label class="proposal-checkbox-row">
                        <input type="checkbox" name="ethics_required" value="1" <?= $ethicsRequired ? 'checked' : '' ?>>
                        Requires ethics review
                    </label>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Abstract <span>(not more than 300 words)</span></h3>
        <textarea name="abstract" class="proposal-textarea proposal-textarea-lg" placeholder="Provide a concise abstract of the proposed research."><?= htmlspecialchars($field('abstract')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Introduction <span>(rationale, background, and proposed approach)</span></h3>
        <textarea name="introduction" class="proposal-textarea" placeholder="Describe the rationale and background of the project."><?= htmlspecialchars($field('introduction')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Research Gaps <span>(about 100 words or more, with in-text citations)</span></h3>
        <textarea name="research_gaps" class="proposal-textarea" placeholder="Describe the research gaps the proposal intends to address."><?= htmlspecialchars($field('research_gaps')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Significance of the Study <span>(about 100 words or more)</span></h3>
        <textarea name="significance" class="proposal-textarea" placeholder="Explain why the study matters and its expected contribution."><?= htmlspecialchars($field('significance')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Objectives</h3>
        <textarea name="objectives" class="proposal-textarea" placeholder="State the general and specific objectives of the study."><?= htmlspecialchars($field('objectives')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Materials and Methods/Description of Project Activities</h3>
        <p class="proposal-section-note">Describe what will be done to produce the expected results and accomplish the project objectives.</p>
        <textarea name="methods" class="proposal-textarea proposal-textarea-lg" placeholder="Outline the methodology, process, or project activities."><?= htmlspecialchars($field('methods')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Gender and Development <span>(explain the participation of men and women in the proposed research)</span></h3>
        <textarea name="gender_development" class="proposal-textarea" placeholder="Explain gender and development considerations for this proposal."><?= htmlspecialchars($field('gender_development')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Ethical Considerations</h3>
        <textarea name="ethical_considerations" class="proposal-textarea" placeholder="List any ethical considerations, safeguards, or compliance requirements."><?= htmlspecialchars($field('ethical_considerations')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Expected Outcomes <span>(if applicable)</span></h3>
        <textarea name="expected_outcomes" class="proposal-textarea" placeholder="Describe the measurable changes or results expected at the end of the project."><?= htmlspecialchars($field('expected_outcomes')) ?></textarea>

        <div class="proposal-checkboxes">
            <div>
                <strong>Expected 6Ps</strong> <span class="proposal-section-note-inline">(tick whichever is possible)</span>
            </div>
            <div class="proposal-check-grid">
                <?php foreach ($sixPsOptions as $value => $label): ?>
                    <label class="proposal-check-item">
                        <input type="checkbox" name="six_ps[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedSixPs[$value]) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Literature Cited</h3>
        <textarea name="literature_cited" class="proposal-textarea" placeholder="List the major references and literature cited."><?= htmlspecialchars($field('literature_cited')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Budget</h3>
        <ul class="proposal-guidelines">
            <li>Include only costs which directly relate to efficiently carrying out the activities and producing the objectives set forth in the proposal.</li>
            <li>The budget should be realistic and based on planned activities and actual project needs.</li>
            <li>The budget should include all costs associated with managing and administering the project.</li>
            <li>Indirect or administrative overhead costs such as staff salaries and office rent are not funded and should not be part of the funding request.</li>
        </ul>

        <div class="proposal-budget-total">
            <label for="budget_total">Total Amount Requested</label>
            <input id="budget_total" name="budget_total" value="<?= htmlspecialchars($field('budget_total')) ?>" placeholder="0.00">
        </div>

        <table class="proposal-table proposal-budget-table">
            <tr>
                <th>Item including unit cost and quantity</th>
                <th>Amount</th>
                <th>Justification</th>
            </tr>
            <?php foreach ($budgetItems as $index => $item): ?>
                <tr>
                    <td>
                        <textarea
                            name="budget_items[<?= $index ?>][item]"
                            class="proposal-budget-input"
                            rows="1"
                            data-autoresize
                        ><?= htmlspecialchars((string) ($item['item'] ?? '')) ?></textarea>
                    </td>
                    <td><input name="budget_items[<?= $index ?>][amount]" value="<?= htmlspecialchars((string) ($item['amount'] ?? '')) ?>"></td>
                    <td>
                        <textarea
                            name="budget_items[<?= $index ?>][justification]"
                            class="proposal-budget-input"
                            rows="1"
                            data-autoresize
                        ><?= htmlspecialchars((string) ($item['justification'] ?? '')) ?></textarea>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th>Resources Available</th>
                <td colspan="2">
                    <textarea
                        name="resources_available"
                        class="proposal-budget-input"
                        rows="2"
                        data-autoresize
                        placeholder="List internal resources or counterpart support available for the project."
                    ><?= htmlspecialchars($field('resources_available')) ?></textarea>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section">
        <h3 class="proposal-subtitle">Implementation Plan and Time Frame</h3>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table proposal-plan-table trainings-conducted-table">
                <tr>
                    <th rowspan="2" class="proposal-plan-activity">Activity</th>
                    <th colspan="12">Time Frame</th>
                </tr>
                <tr>
                    <?php foreach ($months as $month): ?>
                        <th class="proposal-plan-month"><?= htmlspecialchars($month) ?></th>
                    <?php endforeach; ?>
                </tr>
                <?php foreach ($implementationPlan as $index => $row): ?>
                    <?php $selectedMonths = array_fill_keys(array_values(array_filter($row['months'] ?? [], static fn ($value): bool => is_string($value))), true); ?>
                    <tr>
                        <td class="proposal-plan-activity-cell">
                            <textarea
                                name="implementation_plan[<?= $index ?>][activity]"
                                class="proposal-plan-activity-input"
                                rows="2"
                                data-autoresize
                                placeholder="Activity <?= $index + 1 ?>"
                            ><?= htmlspecialchars((string) ($row['activity'] ?? '')) ?></textarea>
                        </td>
                        <?php foreach ($months as $month): ?>
                            <td class="proposal-plan-check">
                                <input type="checkbox" name="implementation_plan[<?= $index ?>][months][]" value="<?= htmlspecialchars($month) ?>" <?= isset($selectedMonths[$month]) ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </section>

    <?php
    $workflowSteps = proposal_workflow_steps($proposal ?? ['status' => 'draft', 'project_type' => $projectTypeValue]);
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
    const tableBody = document.getElementById('proposal-coauthors-body');
    const addButton = document.getElementById('add-coauthor-row');
    const facultySelect = document.getElementById('faculty-coauthor-select');
    const facultySearch = document.getElementById('faculty-coauthor-search');
    const addFacultyButton = document.getElementById('add-faculty-coauthor');
    const autoResizeTextareas = Array.from(document.querySelectorAll('textarea[data-autoresize]'));

    const resizeTextarea = (textarea) => {
        textarea.style.height = 'auto';
        textarea.style.height = `${textarea.scrollHeight}px`;
    };

    autoResizeTextareas.forEach((textarea) => {
        resizeTextarea(textarea);
        textarea.addEventListener('input', () => resizeTextarea(textarea));
    });

    if (!tableBody || !addButton) {
        return;
    }

    const linkedUserIdsInTable = () => {
        const ids = new Set();
        tableBody.querySelectorAll('input[data-field="user_id"]').forEach((input) => {
            const value = parseInt(input.value, 10);
            if (!Number.isNaN(value) && value > 0) {
                ids.add(value);
            }
        });
        return ids;
    };

    const updateRowNames = () => {
        Array.from(tableBody.querySelectorAll('tr')).forEach((row, index) => {
            row.querySelectorAll('input[data-field], textarea[data-field]').forEach((input) => {
                input.name = `coauthors[${index}][${input.dataset.field}]`;
            });
        });
    };

    const createSourceCell = (linked = false, userId = '') => {
        const cell = document.createElement('td');
        cell.className = 'proposal-coauthor-source-cell';
        if (linked) {
            const badge = document.createElement('span');
            badge.className = 'badge badge-ongoing';
            badge.textContent = 'Faculty account';
            cell.appendChild(badge);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.dataset.field = 'user_id';
            hidden.value = String(userId);
            cell.appendChild(hidden);
        } else {
            const label = document.createElement('span');
            label.className = 'muted';
            label.textContent = 'Manual';
            cell.appendChild(label);
        }
        return cell;
    };

    const createCoauthorFieldCell = (field, placeholder, value = '', readOnly = false) => {
        const cell = document.createElement('td');
        const textarea = document.createElement('textarea');
        textarea.dataset.field = field;
        textarea.className = 'proposal-coauthor-field';
        textarea.rows = 2;
        textarea.dataset.autoresize = '';
        textarea.placeholder = placeholder;
        textarea.value = value;
        if (readOnly) {
            textarea.readOnly = true;
        }
        cell.appendChild(textarea);
        resizeTextarea(textarea);
        textarea.addEventListener('input', () => resizeTextarea(textarea));
        return cell;
    };

    const createDeleteCell = () => {
        const cell = document.createElement('td');
        cell.className = 'proposal-coauthors-action-cell';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline btn-sm';
        button.dataset.action = 'delete-coauthor';
        button.textContent = 'Delete Row';
        cell.appendChild(button);

        return cell;
    };

    const createManualRow = () => {
        const row = document.createElement('tr');
        row.appendChild(createSourceCell(false));
        row.appendChild(createCoauthorFieldCell('last_name', 'Enter last name'));
        row.appendChild(createCoauthorFieldCell('first_name', 'Enter first name'));
        row.appendChild(createCoauthorFieldCell('middle_name', 'Enter middle name'));
        row.appendChild(createDeleteCell());
        return row;
    };

    const createFacultyRow = (option) => {
        const row = document.createElement('tr');
        row.dataset.linkedFaculty = '1';
        row.appendChild(createSourceCell(true, option.value));
        row.appendChild(createCoauthorFieldCell('last_name', 'Enter last name', option.dataset.lastName || '', true));
        row.appendChild(createCoauthorFieldCell('first_name', 'Enter first name', option.dataset.firstName || '', true));
        row.appendChild(createCoauthorFieldCell('middle_name', 'Enter middle name', option.dataset.middleName || ''));
        row.appendChild(createDeleteCell());
        return row;
    };

    const removeEmptyPlaceholderRow = () => {
        const rows = Array.from(tableBody.querySelectorAll('tr'));
        if (rows.length !== 1) {
            return;
        }
        const row = rows[0];
        const hasLinked = row.querySelector('input[data-field="user_id"]');
        const lastName = row.querySelector('[data-field="last_name"]');
        const firstName = row.querySelector('[data-field="first_name"]');
        const middleName = row.querySelector('[data-field="middle_name"]');
        const isEmpty = !hasLinked
            && (lastName?.value.trim() ?? '') === ''
            && (firstName?.value.trim() ?? '') === ''
            && (middleName?.value.trim() ?? '') === '';
        if (isEmpty) {
            row.remove();
        }
    };

    addButton.addEventListener('click', () => {
        tableBody.appendChild(createManualRow());
        updateRowNames();
    });

    if (facultySelect && addFacultyButton) {
        addFacultyButton.addEventListener('click', () => {
            const option = facultySelect.options[facultySelect.selectedIndex];
            if (!option || !option.value) {
                facultySelect.focus();
                return;
            }

            const userId = parseInt(option.value, 10);
            if (linkedUserIdsInTable().has(userId)) {
                window.alert('This faculty member is already listed as a co-author.');
                return;
            }

            removeEmptyPlaceholderRow();
            tableBody.appendChild(createFacultyRow(option));
            updateRowNames();
        });
    }

    if (facultySelect && facultySearch) {
        const allOptions = Array.from(facultySelect.options).map((option) => ({
            element: option,
            text: option.textContent.toLowerCase(),
            college: (option.dataset.college || '').toLowerCase(),
        }));

        const filterFacultyOptions = () => {
            const query = facultySearch.value.trim().toLowerCase();
            let firstVisible = null;
            allOptions.forEach(({ element, text, college }) => {
                const visible = query === '' || text.includes(query) || college.includes(query);
                element.hidden = !visible;
                if (visible && firstVisible === null) {
                    firstVisible = element;
                }
            });
            if (firstVisible) {
                firstVisible.selected = true;
            }
        };

        facultySearch.addEventListener('input', filterFacultyOptions);
        filterFacultyOptions();
    }

    tableBody.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action="delete-coauthor"]');
        if (!button) {
            return;
        }

        const row = button.closest('tr');
        if (!row) {
            return;
        }

        row.remove();
        if (tableBody.querySelectorAll('tr').length === 0) {
            tableBody.appendChild(createManualRow());
        }
        updateRowNames();
    });

    updateRowNames();
})();
</script>
