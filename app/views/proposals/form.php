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
        $entry['invitation_status'] = trim((string) ($coAuthor['invitation_status'] ?? ''));
    } elseif ($entry['last_name'] === '' && $entry['first_name'] === '' && $entry['middle_name'] === '') {
        continue;
    }
    $normalizedCoAuthors[] = $entry;
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
        <div class="webform-subsection coauthors" id="coauthors" data-coauthors>
            <h3>Co-Author(s)</h3>
            <p class="webform-hint">Search the RIDE faculty registry. A tagged faculty member is notified and must accept before the proposal appears on their dashboard. A co-author without a RIDE account can be added by name.</p>

            <div class="coauthor-picker">
                <label for="coauthor-search" class="sr-only">Search faculty by name or college</label>
                <div class="coauthor-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="coauthor-search" autocomplete="off" spellcheck="false"
                        placeholder="Search faculty by name or college"
                        role="combobox" aria-expanded="false" aria-controls="coauthor-results" aria-autocomplete="list">
                </div>
                <ul class="coauthor-results" id="coauthor-results" role="listbox" hidden></ul>
                <p class="coauthor-message" data-coauthor-message aria-live="polite" hidden></p>
            </div>

            <script type="application/json" id="coauthor-faculty-data"><?= json_encode(array_map(static fn (array $option): array => [
                'id' => (int) $option['id'],
                'last' => (string) $option['last_name'],
                'first' => (string) $option['first_name'],
                'middle' => (string) $option['middle_name'],
                'college' => (string) $option['college_name'],
            ], $facultyCoAuthorOptions), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

            <?php $facultyCollegeById = array_column($facultyCoAuthorOptions, 'college_name', 'id'); ?>
            <ol class="coauthor-list" data-coauthor-list>
                <?php foreach ($coAuthors as $index => $coAuthor): ?>
                    <?php
                    $linkedUserId = (int) ($coAuthor['user_id'] ?? 0);
                    $invitationStatus = trim((string) ($coAuthor['invitation_status'] ?? ''));
                    $invitationLabel = $linkedUserId > 0 ? \App\Support\ProposalCoAuthors::invitationStatusLabel($invitationStatus) : '';
                    $fullName = trim(implode(' ', array_filter([(string) ($coAuthor['first_name'] ?? ''), (string) ($coAuthor['middle_name'] ?? ''), (string) ($coAuthor['last_name'] ?? '')])));
                    ?>
                    <li class="coauthor-card<?= $linkedUserId > 0 ? ' is-linked' : '' ?>" data-coauthor-card>
                        <span class="coauthor-avatar" aria-hidden="true"><?= htmlspecialchars(strtoupper(substr((string) ($coAuthor['first_name'] ?? ''), 0, 1) . substr((string) ($coAuthor['last_name'] ?? ''), 0, 1)) ?: '?') ?></span>
                        <div class="coauthor-body">
                            <?php if ($linkedUserId > 0): ?>
                                <input type="hidden" data-field="user_id" name="coauthors[<?= $index ?>][user_id]" value="<?= $linkedUserId ?>">
                                <input type="hidden" data-field="last_name" name="coauthors[<?= $index ?>][last_name]" value="<?= htmlspecialchars((string) ($coAuthor['last_name'] ?? '')) ?>">
                                <input type="hidden" data-field="first_name" name="coauthors[<?= $index ?>][first_name]" value="<?= htmlspecialchars((string) ($coAuthor['first_name'] ?? '')) ?>">
                                <input type="hidden" data-field="middle_name" name="coauthors[<?= $index ?>][middle_name]" value="<?= htmlspecialchars((string) ($coAuthor['middle_name'] ?? '')) ?>">
                                <span class="coauthor-name"><?= htmlspecialchars($fullName) ?></span>
                                <span class="coauthor-meta">
                                    <span class="coauthor-tag coauthor-tag--account"><i class="fa-solid fa-user-check" aria-hidden="true"></i> RIDE account</span>
                                    <?php if (($facultyCollegeById[$linkedUserId] ?? '') !== ''): ?>
                                        <span class="coauthor-college"><?= htmlspecialchars((string) $facultyCollegeById[$linkedUserId]) ?></span>
                                    <?php endif; ?>
                                    <?php if ($invitationLabel !== ''): ?>
                                        <span class="coauthor-tag coauthor-tag--<?= htmlspecialchars($invitationStatus !== '' ? $invitationStatus : 'pending') ?>"><?= htmlspecialchars($invitationLabel) ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="coauthor-meta"><span class="coauthor-tag"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Not registered in RIDE</span></span>
                                <div class="coauthor-names">
                                    <?php foreach (['last_name' => 'Last name', 'first_name' => 'First name', 'middle_name' => 'Middle name'] as $nameField => $nameLabel): ?>
                                        <label>
                                            <span><?= $nameLabel ?><?= $nameField !== 'middle_name' ? ' <span class="webform-req">*</span>' : '' ?></span>
                                            <input type="text" data-field="<?= $nameField ?>" name="coauthors[<?= $index ?>][<?= $nameField ?>]" value="<?= htmlspecialchars((string) ($coAuthor[$nameField] ?? '')) ?>" placeholder="Enter <?= strtolower($nameLabel) ?>">
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="webform-icon-btn coauthor-remove" data-coauthor-remove title="Remove co-author" aria-label="Remove <?= htmlspecialchars($fullName !== '' ? $fullName : 'co-author') ?>"><i class="fa-solid fa-xmark"></i></button>
                    </li>
                <?php endforeach; ?>
            </ol>
            <p class="coauthor-empty" data-coauthor-empty<?= $coAuthors !== [] ? ' hidden' : '' ?>>No co-authors added. This section can be left empty.</p>
            <button type="button" class="webform-add-btn" data-coauthor-add-manual>Add a co-author not registered in RIDE</button>
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
    document.querySelectorAll('textarea[data-autoresize]').forEach((textarea) => {
        const resize = () => { textarea.style.height = 'auto'; textarea.style.height = `${textarea.scrollHeight}px`; };
        resize();
        textarea.addEventListener('input', resize);
    });

    // Co-authors: search the faculty registry, or add a co-author not registered in RIDE.
    const root = document.querySelector('[data-coauthors]');
    if (!root) {
        return;
    }
    const list = root.querySelector('[data-coauthor-list]');
    const empty = root.querySelector('[data-coauthor-empty]');
    const input = root.querySelector('#coauthor-search');
    const results = root.querySelector('#coauthor-results');
    const message = root.querySelector('[data-coauthor-message]');
    let faculty = [];
    try {
        faculty = JSON.parse(root.querySelector('#coauthor-faculty-data')?.textContent || '[]');
    } catch (error) { /* no registry */ }
    faculty.forEach((person) => {
        person.name = [person.first, person.middle, person.last].filter(Boolean).join(' ');
        person.haystack = `${person.name} ${person.last}, ${person.first} ${person.college}`.toLowerCase();
    });

    const escape = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const initials = (first, last) => `${(first || '').charAt(0)}${(last || '').charAt(0)}`.toUpperCase() || '?';
    const linkedIds = () => new Set(Array.from(list.querySelectorAll('input[data-field="user_id"]')).map((el) => parseInt(el.value, 10)));
    const say = (text) => {
        message.textContent = text;
        message.hidden = text === '';
    };

    const renumber = () => {
        list.querySelectorAll('[data-coauthor-card]').forEach((card, index) => {
            card.querySelectorAll('[data-field]').forEach((field) => {
                field.name = `coauthors[${index}][${field.dataset.field}]`;
            });
        });
        empty.hidden = list.children.length > 0;
    };

    const removeButton = (label) => `<button type="button" class="webform-icon-btn coauthor-remove" data-coauthor-remove title="Remove co-author" aria-label="Remove ${escape(label)}"><i class="fa-solid fa-xmark"></i></button>`;

    const addLinked = (person) => {
        if (linkedIds().has(person.id)) {
            say(`${person.name} is already listed as a co-author.`);
            return;
        }
        const card = document.createElement('li');
        card.className = 'coauthor-card is-linked is-new';
        card.dataset.coauthorCard = '';
        card.innerHTML = `
            <span class="coauthor-avatar" aria-hidden="true">${escape(initials(person.first, person.last))}</span>
            <div class="coauthor-body">
                <input type="hidden" data-field="user_id" value="${person.id}">
                <input type="hidden" data-field="last_name" value="${escape(person.last)}">
                <input type="hidden" data-field="first_name" value="${escape(person.first)}">
                <input type="hidden" data-field="middle_name" value="${escape(person.middle)}">
                <span class="coauthor-name">${escape(person.name)}</span>
                <span class="coauthor-meta">
                    <span class="coauthor-tag coauthor-tag--account"><i class="fa-solid fa-user-check" aria-hidden="true"></i> RIDE account</span>
                    ${person.college ? `<span class="coauthor-college">${escape(person.college)}</span>` : ''}
                    <span class="coauthor-tag coauthor-tag--pending">Invitation sent on save</span>
                </span>
            </div>
            ${removeButton(person.name)}`;
        list.appendChild(card);
        renumber();
        say('');
    };

    const addManual = (typed = '') => {
        // A typed full name is split as "First Middle Last"; surname particles (Dela Cruz, De los Santos,
        // San Juan) stay with the last name. The fields can be corrected after adding.
        const parts = typed.trim().split(/\s+/).filter(Boolean);
        const particles = ['de', 'del', 'dela', 'la', 'las', 'los', 'delos', 'delas', 'di', 'da', 'san', 'sta', 'sta.', 'santa', 'sto', 'sto.', 'santo', 'van', 'von', 'mac', 'mc'];
        const first = parts.length > 0 ? parts.shift() : '';
        let lastParts = parts.length > 0 ? [parts.pop()] : [];
        while (parts.length > 0 && particles.includes(parts[parts.length - 1].toLowerCase())) {
            lastParts.unshift(parts.pop());
        }
        const last = lastParts.join(' ');
        const middle = parts.join(' ');
        const field = (name, label, value, required) => `
            <label>
                <span>${label}${required ? ' <span class="webform-req">*</span>' : ''}</span>
                <input type="text" data-field="${name}" value="${escape(value)}" placeholder="Enter ${label.toLowerCase()}">
            </label>`;
        const card = document.createElement('li');
        card.className = 'coauthor-card is-new';
        card.dataset.coauthorCard = '';
        card.innerHTML = `
            <span class="coauthor-avatar" aria-hidden="true">${escape(initials(first, last))}</span>
            <div class="coauthor-body">
                <span class="coauthor-meta"><span class="coauthor-tag"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> Not registered in RIDE</span></span>
                <div class="coauthor-names">
                    ${field('last_name', 'Last name', last, true)}
                    ${field('first_name', 'First name', first, true)}
                    ${field('middle_name', 'Middle name', middle, false)}
                </div>
            </div>
            ${removeButton('co-author')}`;
        list.appendChild(card);
        renumber();
        say('');
        card.querySelector(last === '' ? '[data-field="last_name"]' : '[data-field="middle_name"]')?.focus();
    };

    // Search results: matching faculty, then "add as not registered" for the typed name.
    let options = [];
    let active = -1;
    const close = () => {
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        active = -1;
    };
    const highlight = (index) => {
        active = index;
        results.querySelectorAll('[role="option"]').forEach((el, i) => {
            el.classList.toggle('is-active', i === active);
            el.setAttribute('aria-selected', i === active ? 'true' : 'false');
            if (i === active) {
                input.setAttribute('aria-activedescendant', el.id);
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    };
    const render = () => {
        const query = input.value.trim().toLowerCase();
        if (query === '') {
            close();
            return;
        }
        const taken = linkedIds();
        const terms = query.split(/\s+/);
        const matches = faculty.filter((person) => terms.every((term) => person.haystack.includes(term))).slice(0, 8);
        options = matches.map((person) => ({ type: 'faculty', person, taken: taken.has(person.id) }));
        options.push({ type: 'manual', name: input.value.trim() });
        results.innerHTML = options.map((option, i) => option.type === 'faculty'
            ? `<li role="option" id="coauthor-option-${i}" class="coauthor-option${option.taken ? ' is-taken' : ''}" aria-selected="false" aria-disabled="${option.taken}">
                    <span class="coauthor-avatar" aria-hidden="true">${escape(initials(option.person.first, option.person.last))}</span>
                    <span class="coauthor-option-text"><strong>${escape(option.person.name)}</strong><span>${escape(option.person.college || 'No college on file')}${option.taken ? ', already added' : ''}</span></span>
               </li>`
            : `<li role="option" id="coauthor-option-${i}" class="coauthor-option coauthor-option--manual" aria-selected="false">
                    <span class="coauthor-avatar" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
                    <span class="coauthor-option-text"><strong>Add "${escape(option.name)}"</strong><span>Co-author not registered in RIDE</span></span>
               </li>`).join('');
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        highlight(options.findIndex((option) => option.type === 'manual' || !option.taken));
    };
    const choose = (index) => {
        const option = options[index];
        if (!option || option.taken) {
            return;
        }
        if (option.type === 'faculty') {
            addLinked(option.person);
        } else {
            addManual(option.name);
        }
        input.value = '';
        close();
        if (option.type === 'faculty') {
            input.focus();
        }
    };

    input.addEventListener('input', render);
    input.addEventListener('focus', () => { if (input.value.trim() !== '') { render(); } });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && !results.hidden) {
            event.preventDefault();
            highlight((active + 1) % options.length);
        } else if (event.key === 'ArrowUp' && !results.hidden) {
            event.preventDefault();
            highlight((active - 1 + options.length) % options.length);
        } else if (event.key === 'Enter') {
            // Never submit the proposal form from the search box.
            event.preventDefault();
            if (!results.hidden && active >= 0) {
                choose(active);
            }
        } else if (event.key === 'Escape') {
            input.value = '';
            close();
        }
    });
    results.addEventListener('mousedown', (event) => {
        const option = event.target.closest('[role="option"]');
        if (option) {
            event.preventDefault();
            choose(Array.from(results.children).indexOf(option));
        }
    });
    input.addEventListener('blur', () => setTimeout(close, 120));

    root.querySelector('[data-coauthor-add-manual]')?.addEventListener('click', () => addManual(''));
    list.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-coauthor-remove]');
        if (!remove) {
            return;
        }
        remove.closest('[data-coauthor-card]')?.remove();
        renumber();
        say('');
        input.focus();
    });
    list.addEventListener('input', (event) => {
        const card = event.target.closest('[data-coauthor-card]');
        if (!card || card.classList.contains('is-linked')) {
            return;
        }
        const first = card.querySelector('[data-field="first_name"]')?.value || '';
        const last = card.querySelector('[data-field="last_name"]')?.value || '';
        card.querySelector('.coauthor-avatar').textContent = initials(first, last);
    });

    renumber();
})();
</script>

<script src="<?= base_url('assets/js/form-preview-gate.js') ?>?v=<?= htmlspecialchars((string) @filemtime(BASE_PATH . '/public/assets/js/form-preview-gate.js')) ?>"></script>
