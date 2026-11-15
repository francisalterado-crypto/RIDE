<?php
/** @var array|null $proposal */
/** @var string|null $lockedProjectType */
/** @var list<array<string, mixed>> $colleges */
/** @var list<array{id: int, label: string, last_name: string, first_name: string, middle_name: string, college_name: string}> $facultyCoAuthorOptions */
$isEdit = $proposal !== null;
$lockedProjectType = $isEdit ? null : ($lockedProjectType ?? proposal_nav_scope());
$isExtensionNav = $lockedProjectType === 'extension';
$proposalLabel = $isExtensionNav ? 'Extension' : 'Research';
$pageTitle = ($isEdit ? 'Edit' : ($isExtensionNav ? $proposalLabel . ' Proposal' : 'Research Proposal Application')) . ' | RIDE Hub';
$pageHeading = $isEdit
    ? 'Edit ' . ($isExtensionNav ? $proposalLabel . ' Proposal' : 'Research Proposal Application')
    : ($isExtensionNav ? $proposalLabel . ' Proposal' : 'Research Proposal Application');
$pageSubtitle = $isEdit
    ? 'Update your proposal fields and save or resubmit for review.'
    : ($isExtensionNav
        ? 'Complete all sections of the extension proposal form before saving or submitting.'
        : 'Research Proposal Application Form (WPU-QSF-RIDE-RDO-20). Your details as proponent come from your profile.');
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
$selectedSixPs = array_fill_keys(
    array_values(array_filter($summaryData['six_ps'] ?? $sampleSummaryData['six_ps'] ?? [], static fn ($value): bool => is_string($value))),
    true
);
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
// Part I (The Proponent) comes from the proponent's profile; the controller copies it into the form on save.
$proponentUserId = $isEdit ? (int) ($proposal['user_id'] ?? 0) : (int) ($user['id'] ?? 0);
$proponent = \App\Support\ProponentProfile::applicantFields($proponentUserId);
if ($isEdit && trim(implode('', $proponent)) === '') {
    foreach (array_keys($proponent) as $key) {
        $proponent[$key] = $field($key);
    }
}
$isOwnProfile = $proponentUserId === (int) ($user['id'] ?? 0);
$fundingSupport = array_values(array_filter($summaryData['funding_support'] ?? [], 'is_string'));
$selectedSdgs = array_fill_keys(array_values(array_filter($summaryData['sdgs'] ?? [], 'is_string')), true);
$selectedAchieve = array_fill_keys(array_values(array_filter($summaryData['ched_achieve'] ?? [], 'is_string')), true);
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

<form class="webform rdo-webform" id="rpa-form" data-preview-rules="<?= htmlspecialchars(\App\Support\FormPreviewRequirements::forScript(\App\Support\FormPreviewRequirements::forResearchApplication())) ?>" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/update') : base_url('proposals') ?>">
    <?= csrf_field() ?>
    <?php if ($lockedProjectType !== null): ?>
        <input type="hidden" name="nav_scope" value="<?= htmlspecialchars($lockedProjectType) ?>">
        <input type="hidden" name="project_type" value="<?= htmlspecialchars($lockedProjectType) ?>">
    <?php endif; ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="preview_proposal_id" value="<?= (int) $proposal['id'] ?>">
    <?php endif; ?>

    <div class="webform-hero">
        <div>
            <p class="webform-eyebrow">Office of Research and Development, Form WPU-QSF-RIDE-RDO-20</p>
            <h2 class="webform-hero-title">Research Proposal Application</h2>
            <p class="webform-hero-text">Fill out each section below. When every required part is filled, <strong>Print Preview</strong> downloads the filled office form (.docx) on the letterhead, ready to print.</p>
            <p class="webform-hint webform-req-legend"><span class="webform-req">*</span> Required before Print Preview.</p>
            <?php $blankFormType = 'research'; require APP_PATH . '/views/partials/blank-form-link.php'; ?>
            <p class="webform-hint js-preview-hint" aria-live="polite"></p>
        </div>
        <button type="submit" class="btn btn-outline webform-preview-btn js-preview-btn" formaction="<?= base_url('proposals/preview/research-application') ?>" formnovalidate disabled>Print Preview</button>
    </div>

    <nav class="webform-steps" aria-label="Form sections">
            <a href="#rpa-sec-proponent"><span>1</span>The Proponent</a>
            <a href="#rpa-sec-project"><span>2</span>Project Information</a>
            <a href="#rpa-sec-abstract"><span>3</span>Abstract</a>
            <a href="#rpa-sec-background"><span>4</span>Background</a>
            <a href="#rpa-sec-objectives"><span>5</span>Objectives &amp; Methods</a>
            <a href="#rpa-sec-considerations"><span>6</span>GAD, Ethics &amp; Alignment</a>
            <a href="#rpa-sec-outcomes"><span>7</span>Expected Outcomes</a>
            <a href="#rpa-sec-budget"><span>8</span>Budget</a>
            <a href="#rpa-sec-plan"><span>9</span>Implementation Plan</a>
            <a href="#rpa-sec-literature"><span>10</span>Literature Cited</a>
    </nav>

    <section class="webform-card" id="rpa-sec-proponent">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>The Proponent</h2>
                <p>Filled in from <?= $isOwnProfile ? 'your' : 'the proponent\'s' ?> profile.<?php if ($isOwnProfile): ?> To change these details, <a href="<?= base_url('profile') ?>" target="_blank" rel="noopener">update your profile</a>, then reload this page.<?php endif; ?></p>
            </div>
        </header>
        <?php $show = static fn (string $key): string => $proponent[$key] !== '' ? htmlspecialchars($proponent[$key]) : '<span class="muted">Not set in profile</span>'; ?>
        <div class="webform-grid webform-grid--3">
            <?php foreach ([
                'applicant_last_name' => 'Last Name', 'applicant_first_name' => 'First Name', 'applicant_middle_name' => 'Middle Name',
                'applicant_title_prefix' => 'Title/Prefix', 'applicant_sex' => 'Sex', 'applicant_contact_number' => 'Contact Number',
                'applicant_email' => 'E-mail', 'applicant_college_department' => 'College/Unit/Center', 'applicant_campus' => 'Campus',
            ] as $key => $label): ?>
                <div class="webform-field">
                    <span class="webform-label"><?= htmlspecialchars($label) ?></span>
                    <p class="webform-readonly"><?= $show($key) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="webform-grid">
            <div class="webform-field">
                <span class="webform-label">Google Scholar account link</span>
                <p class="webform-readonly"><?= $show('applicant_google_scholar_link') ?></p>
            </div>
            <div class="webform-field">
                <span class="webform-label">ResearchGate account link</span>
                <p class="webform-readonly"><?= $show('applicant_researchgate_link') ?></p>
            </div>
        </div>
        <div class="webform-subsection">
            <h3>Co-Author(s)</h3>
                    <div class="proposal-coauthors-wrap">
                        <?php if ($facultyCoAuthorOptions !== []): ?>
                        <div class="proposal-coauthor-picker">
                            <p class="proposal-section-note">Tag registered faculty from the RIDE account registry. They receive a notification and must accept before they can view this proposal on their dashboard.</p>
                            <label for="faculty-coauthor-search" class="proposal-coauthor-picker-label">Search faculty</label>
                            <input type="search" id="faculty-coauthor-search" class="proposal-coauthor-search" placeholder="Type name or college..." autocomplete="off">
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
                        <div class="webform-table-wrap">
                        <table id="proposal-coauthors-table" class="webform-table proposal-coauthors-table">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th class="webform-table-action proposal-coauthors-action-col"><span class="sr-only">Remove</span></th>
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
                                        <td class="webform-table-action proposal-coauthors-action-cell">
                                            <button type="button" class="webform-icon-btn" data-action="delete-coauthor" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>
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
        </div>
        <p class="webform-hint">All information (excluding co-author/s) is the project/study leader&apos;s only.</p>
    </section>

    <section class="webform-card" id="rpa-sec-project">
        <header class="webform-card-head">
            <span class="webform-card-num">2</span>
            <div>
                <h2>Project Information</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-title">Title of Proposed Research <span class="webform-req">*</span></label>
                <input id="rpa-title" name="title" value="<?= htmlspecialchars($titleValue) ?>" placeholder="Indicate the title of research" required>
            </div>
            <div class="webform-field">
                <label for="rpa-period">Period Covered <span class="webform-req">*</span></label>
                <input id="rpa-period" name="period_covered" value="<?= htmlspecialchars($field('period_covered')) ?>" placeholder="June 2026 to May 2027">
            </div>
            <div class="webform-field">
                <label for="rpa-duration">Duration in Months <span class="webform-req">*</span></label>
                <input id="rpa-duration" name="duration_months" type="number" min="0" value="<?= htmlspecialchars($field('duration_months')) ?>" placeholder="12">
            </div>
            <div class="webform-field webform-field--full">
                <span class="webform-label">Funding/Support <span class="webform-req">*</span></span>
                <div class="proposal-check-grid">
                    <?php foreach (\App\Support\ResearchFormOptions::FUNDING_SUPPORT as $value => $label): ?>
                        <label class="proposal-check-item">
                            <input type="checkbox" name="funding_support[]" value="<?= htmlspecialchars($value) ?>" <?= in_array($value, $fundingSupport, true) ? 'checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-funding-source">Funding source</label>
                <input id="rpa-funding-source" name="funding_source" value="<?= htmlspecialchars($fundingSourceValue) ?>" placeholder="DOST-PCAARRD">
                <p class="webform-hint">For external funding/support, indicate the funding source.</p>
            </div>
            <div class="webform-field">
                <label for="rpa-project-type">Project Type</label>
                <?php if ($lockedProjectType !== null): ?>
                    <input id="rpa-project-type" type="text" value="<?= htmlspecialchars(ucfirst($lockedProjectType)) ?>" readonly>
                <?php else: ?>
                    <select id="rpa-project-type" name="project_type" required>
                        <?php foreach ($types as $t): ?>
                            <option value="<?= $t ?>" <?= $projectTypeValue === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <div class="webform-field">
                <label for="rpa-risk">Risk Level</label>
                <select id="rpa-risk" name="risk_level">
                    <?php foreach (['low', 'medium', 'high'] as $level): ?>
                        <option value="<?= $level ?>" <?= $riskLevelValue === $level ? 'selected' : '' ?>><?= ucfirst($level) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="webform-field webform-field--full">
                <label class="proposal-check-item">
                    <input type="checkbox" name="ethics_required" value="1" <?= $ethicsRequired ? 'checked' : '' ?>>
                    <span>Requires ethics review</span>
                </label>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-abstract">
        <header class="webform-card-head">
            <span class="webform-card-num">3</span>
            <div>
                <h2>Abstract</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-abstract">Abstract (not more than 300 words) <span class="webform-req">*</span></label>
                <textarea id="rpa-abstract" name="abstract" rows="6" data-autoresize placeholder="Provide a concise abstract of the proposed research."><?= htmlspecialchars($field('abstract')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-graphical-abstract">Graphical Abstract (if applicable)</label>
                <input id="rpa-graphical-abstract" type="url" name="graphical_abstract_link" value="<?= htmlspecialchars($field('graphical_abstract_link')) ?>" placeholder="https://">
                <p class="webform-hint">Link to the graphical abstract image, such as a shared Google Drive file.</p>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-background">
        <header class="webform-card-head">
            <span class="webform-card-num">4</span>
            <div>
                <h2>Background</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-introduction">Introduction <span class="webform-req">*</span></label>
                <textarea id="rpa-introduction" name="introduction" rows="5" data-autoresize placeholder="Rationale and background of the project, and the proposed approach."><?= htmlspecialchars($field('introduction')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-research_gaps">Research Gaps <span class="webform-req">*</span></label>
                <textarea id="rpa-research_gaps" name="research_gaps" rows="4" data-autoresize placeholder="About 100 words or more, with in-text citations."><?= htmlspecialchars($field('research_gaps')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-significance">Significance of the Study <span class="webform-req">*</span></label>
                <textarea id="rpa-significance" name="significance" rows="4" data-autoresize placeholder="About 100 words or more, with in-text citations if necessary."><?= htmlspecialchars($field('significance')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-objectives">
        <header class="webform-card-head">
            <span class="webform-card-num">5</span>
            <div>
                <h2>Objectives &amp; Methods</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-objectives">Objectives <span class="webform-req">*</span></label>
                <textarea id="rpa-objectives" name="objectives" rows="4" data-autoresize placeholder="State the general and specific objectives of the study."><?= htmlspecialchars($field('objectives')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-methods">Materials and Methods/Description of Project Activities <span class="webform-req">*</span></label>
                <textarea id="rpa-methods" name="methods" rows="6" data-autoresize placeholder="Outline the methodology, process, or project activities."><?= htmlspecialchars($field('methods')) ?></textarea>
                <p class="webform-hint">Describe what will be done to produce the expected results. The activities must be a means to achieving the results.</p>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-considerations">
        <header class="webform-card-head">
            <span class="webform-card-num">6</span>
            <div>
                <h2>GAD, Ethics &amp; Alignment</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-gender_development">Gender and Development <span class="webform-req">*</span></label>
                <textarea id="rpa-gender_development" name="gender_development" rows="3" data-autoresize placeholder="Explain the involvement/participation of men and women in the proposed research."><?= htmlspecialchars($field('gender_development')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="rpa-ethical_considerations">Ethical Considerations <span class="webform-req">*</span></label>
                <textarea id="rpa-ethical_considerations" name="ethical_considerations" rows="3" data-autoresize placeholder="Ethical considerations, safeguards, or compliance requirements."><?= htmlspecialchars($field('ethical_considerations')) ?></textarea>
            </div>
        </div>
        <div class="webform-subsection">
            <h3>CHED A.C.H.I.E.V.E. <span class="webform-req">*</span></h3>
            <div class="proposal-check-grid">
                <?php foreach (\App\Support\ResearchFormOptions::CHED_ACHIEVE as $value => $label): ?>
                    <label class="proposal-check-item">
                        <input type="checkbox" name="ched_achieve[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedAchieve[$value]) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="webform-subsection">
            <h3>Sustainable Development Goal/s (SDG/s) <span class="webform-req">*</span></h3>
            <div class="proposal-check-grid">
                <?php foreach (\App\Support\ResearchFormOptions::SDGS as $value => $label): ?>
                    <label class="proposal-check-item">
                        <input type="checkbox" name="sdgs[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedSdgs[$value]) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-outcomes">
        <header class="webform-card-head">
            <span class="webform-card-num">7</span>
            <div>
                <h2>Expected Outcomes</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-expected_outcomes">Expected Outcomes <span class="webform-req">*</span></label>
                <p class="webform-hint">Measurable changes expected by the end of the project, such as products or processes developed, adoption in extension programs, or commercialization.</p>
                <textarea id="rpa-expected_outcomes" name="expected_outcomes" rows="4" data-autoresize><?= htmlspecialchars($field('expected_outcomes')) ?></textarea>
            </div>
        </div>
        <div class="webform-subsection">
            <h3>Expected 6Ps <span class="webform-hint">(tick whichever is possible)</span></h3>
            <div class="proposal-check-grid">
                <?php foreach ($sixPsOptions as $value => $label): ?>
                    <label class="proposal-check-item">
                        <input type="checkbox" name="six_ps[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedSixPs[$value]) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-sustainability_plan">Sustainability Plan <span class="webform-req">*</span></label>
                <textarea id="rpa-sustainability_plan" name="sustainability_plan" rows="3" data-autoresize placeholder="How the results and activities will be sustained after the project."><?= htmlspecialchars($field('sustainability_plan')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-budget">
        <header class="webform-card-head">
            <span class="webform-card-num">8</span>
            <div>
                <h2>Budget</h2>
                <p>For in-house/internally funded research only. Include only direct costs; indirect costs such as staff salaries and office rent are not funded.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="rpa-budget-total">Total Amount Requested</label>
                <div class="webform-affix"><span>₱</span><input id="rpa-budget-total" name="budget_total" inputmode="decimal" value="<?= htmlspecialchars($field('budget_total')) ?>" placeholder="0.00"></div>
            </div>
        </div>
        <div class="webform-repeater">
            <?php foreach ($budgetItems as $index => $item): ?>
                <div class="webform-item">
                    <div class="webform-item-head"><span class="webform-item-label">Budget item <?= $index + 1 ?></span></div>
                    <div class="webform-grid">
                        <div class="webform-field webform-field--full"><label>Item including unit cost and quantity</label><textarea rows="1" data-autoresize name="budget_items[<?= $index ?>][item]"><?= htmlspecialchars((string) ($item['item'] ?? '')) ?></textarea></div>
                        <div class="webform-field"><label>Amount</label><div class="webform-affix"><span>₱</span><input inputmode="decimal" name="budget_items[<?= $index ?>][amount]" value="<?= htmlspecialchars((string) ($item['amount'] ?? '')) ?>" placeholder="0.00"></div></div>
                        <div class="webform-field"><label>Justification</label><textarea rows="1" data-autoresize name="budget_items[<?= $index ?>][justification]"><?= htmlspecialchars((string) ($item['justification'] ?? '')) ?></textarea></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-resources_available">Resources Available</label>
                <textarea id="rpa-resources_available" name="resources_available" rows="2" data-autoresize placeholder="Internal resources or counterpart support available for the project."><?= htmlspecialchars($field('resources_available')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-plan">
        <header class="webform-card-head">
            <span class="webform-card-num">9</span>
            <div>
                <h2>Implementation Plan and Time Frame</h2>
                <p>Tick the months of each activity.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="rpa-plan-from">From CY <span class="webform-req">*</span></label>
                <input id="rpa-plan-from" type="number" name="plan_year_from" min="2000" max="2100" value="<?= htmlspecialchars($field('plan_year_from')) ?>" placeholder="2026">
            </div>
            <div class="webform-field">
                <label for="rpa-plan-to">To CY</label>
                <input id="rpa-plan-to" type="number" name="plan_year_to" min="2000" max="2100" value="<?= htmlspecialchars($field('plan_year_to')) ?>" placeholder="2027">
            </div>
        </div>
        <div class="webform-table-wrap">
            <table class="webform-table rdo-plan-table">
                <thead>
                    <tr>
                        <th>Activity <span class="webform-req">*</span></th>
                        <?php foreach ($months as $month): ?><th><?= htmlspecialchars($month) ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($implementationPlan as $index => $row): ?>
                        <?php $selectedMonths = array_fill_keys(array_values(array_filter($row['months'] ?? [], static fn ($value): bool => is_string($value))), true); ?>
                        <tr>
                            <td><textarea rows="1" data-autoresize name="implementation_plan[<?= $index ?>][activity]" placeholder="Activity <?= $index + 1 ?>" aria-label="Activity <?= $index + 1 ?>"><?= htmlspecialchars((string) ($row['activity'] ?? '')) ?></textarea></td>
                            <?php foreach ($months as $month): ?>
                                <td class="rdo-plan-check"><input type="checkbox" name="implementation_plan[<?= $index ?>][months][]" value="<?= htmlspecialchars($month) ?>" <?= isset($selectedMonths[$month]) ? 'checked' : '' ?> aria-label="Activity <?= $index + 1 ?>, <?= htmlspecialchars($month) ?>"></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="webform-card" id="rpa-sec-literature">
        <header class="webform-card-head">
            <span class="webform-card-num">10</span>
            <div>
                <h2>Literature Cited</h2>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="rpa-literature_cited">Literature Cited <span class="webform-req">*</span></label>
                <textarea id="rpa-literature_cited" name="literature_cited" rows="5" data-autoresize placeholder="List the major references and literature cited."><?= htmlspecialchars($field('literature_cited')) ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <?php
        $workflowSteps = proposal_workflow_steps($proposal ?? ['status' => 'draft', 'project_type' => $projectTypeValue]);
        require APP_PATH . '/views/proposals/_approval-workflow.php';
        ?>
    </section>

    <div class="webform-actionbar">
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline js-preview-btn" formaction="<?= base_url('proposals/preview/research-application') ?>" formnovalidate disabled>Print Preview</button>
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
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
        cell.className = 'webform-table-action proposal-coauthors-action-cell';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'webform-icon-btn';
        button.dataset.action = 'delete-coauthor';
        button.title = 'Remove';
        button.setAttribute('aria-label', 'Remove');
        button.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
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

<script src="<?= base_url('assets/js/form-preview-gate.js') ?>?v=<?= htmlspecialchars((string) @filemtime(BASE_PATH . '/public/assets/js/form-preview-gate.js')) ?>"></script>
