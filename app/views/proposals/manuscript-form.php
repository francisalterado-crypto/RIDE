<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string|null $lockedProjectType */

$isEdit = $proposal !== null;
$lockedProjectType = $isEdit
    ? (string) ($proposal['project_type'] ?? 'research')
    : ($lockedProjectType ?? proposal_nav_scope() ?? 'research');
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Manuscript Writing for Publication | RIDE';
$pageHeading = 'Manuscript Writing for Publication';
$pageSubtitle = $isEdit
    ? 'Update your manuscript writing proposal before saving or resubmitting.'
    : 'Complete all sections of the manuscript writing proposal form before saving or submitting.';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$field = static function (string $key, string $fallback = '') use ($summaryData): string {
    $value = $summaryData[$key] ?? null;
    return is_string($value) ? $value : $fallback;
};

$sdgOptions = [
    'sdg_1' => 'SDG 1 — No Poverty',
    'sdg_2' => 'SDG 2 — Zero Hunger',
    'sdg_3' => 'SDG 3 — Good Health and Well-Being',
    'sdg_4' => 'SDG 4 — Quality Education',
    'sdg_5' => 'SDG 5 — Gender Equality',
    'sdg_6' => 'SDG 6 — Clean Water and Sanitation',
    'sdg_7' => 'SDG 7 — Affordable and Clean Energy',
    'sdg_8' => 'SDG 8 — Decent Work and Economic Growth',
    'sdg_9' => 'SDG 9 — Industry, Innovation and Infrastructure',
    'sdg_10' => 'SDG 10 — Reduced Inequalities',
    'sdg_11' => 'SDG 11 — Sustainable Cities and Communities',
    'sdg_12' => 'SDG 12 — Responsible Consumption and Production',
    'sdg_13' => 'SDG 13 — Climate Action',
    'sdg_14' => 'SDG 14 — Life Below Water',
    'sdg_15' => 'SDG 15 — Life on Land',
    'sdg_16' => 'SDG 16 — Peace, Justice and Strong Institutions',
    'sdg_17' => 'SDG 17 — Partnerships for the Goals',
];
$chedAchieveOptions = [
    'access' => 'A — Access',
    'connectivity' => 'C — Connectivity',
    'human_capital' => 'H — Human Capital',
    'innovation' => 'I — Innovation',
    'excellence' => 'E — Excellence',
    'value' => 'V — Value for Money',
    'engagement' => 'E — Engagement',
];
$selectedSdgs = array_fill_keys(
    array_values(array_filter($summaryData['sdgs'] ?? [], static fn ($value): bool => is_string($value))),
    true
);
$selectedAchieve = array_fill_keys(
    array_values(array_filter($summaryData['ched_achieve'] ?? [], static fn ($value): bool => is_string($value))),
    true
);

$titleValue = (string) ($proposal['title'] ?? '');
$fundingSourceValue = (string) ($proposal['funding_source'] ?? '');
$studyOriginStatus = $field('study_origin_status');
$studyOriginCompletionDate = $field('study_origin_completion_date');
$studyOriginDetails = $field('study_origin_details');
$leadResearchersValue = $field('lead_researchers', trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? '')));
$coResearchersValue = $field('co_researchers');
$durationValue = $field('duration_writing');
$targetJournalValue = $field('target_journal');
$briefRationaleValue = $field('brief_rationale');
$objectivesValue = $field('objectives');
$methodologyValue = $field('general_methodology');
$highlightsValue = $field('highlights_of_results');
$showSignatureHint = true;
?>

<form class="proposal-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/manuscript') : base_url('proposals/manuscript') ?>">
    <?= csrf_field() ?>
    <?php if (!$isEdit && proposal_nav_scope() !== null): ?>
        <input type="hidden" name="nav_scope" value="<?= htmlspecialchars((string) proposal_nav_scope()) ?>">
    <?php endif; ?>
    <input type="hidden" name="project_type" value="<?= htmlspecialchars($lockedProjectType) ?>">

    <section class="proposal-section">
        <h2 class="proposal-section-title">Manuscript Writing for Publication</h2>

        <table class="proposal-table">
            <tr>
                <th>Title</th>
                <td colspan="3">
                    <input name="title" value="<?= htmlspecialchars($titleValue) ?>" placeholder="Enter manuscript title" required>
                </td>
            </tr>
            <tr>
                <th>Program/Project/Study origin</th>
                <td colspan="3">
                    <p class="proposal-section-note-inline">Specify whether ongoing or completed (specify date of completion).</p>
                    <textarea name="study_origin_details" class="proposal-textarea proposal-textarea-compact" placeholder="Identify the program, project, or study of origin."><?= htmlspecialchars($studyOriginDetails) ?></textarea>
                    <div class="manuscript-origin-options">
                        <label class="proposal-check-item">
                            <input type="radio" name="study_origin_status" value="ongoing" <?= $studyOriginStatus === 'ongoing' ? 'checked' : '' ?>>
                            <span>Ongoing</span>
                        </label>
                        <label class="proposal-check-item">
                            <input type="radio" name="study_origin_status" value="completed" <?= $studyOriginStatus === 'completed' ? 'checked' : '' ?>>
                            <span>Completed</span>
                        </label>
                        <label class="manuscript-origin-date">
                            <span class="proposal-inline-label">Date of completion</span>
                            <input type="date" name="study_origin_completion_date" value="<?= htmlspecialchars($studyOriginCompletionDate) ?>">
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>Lead Researcher/s</th>
                <td colspan="3">
                    <textarea name="lead_researchers" class="proposal-textarea proposal-textarea-compact" placeholder="List lead researcher/s."><?= htmlspecialchars($leadResearchersValue) ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Co-researchers/ collaborators</th>
                <td colspan="3">
                    <textarea name="co_researchers" class="proposal-textarea proposal-textarea-compact" placeholder="List co-researchers and collaborators."><?= htmlspecialchars($coResearchersValue) ?></textarea>
                </td>
            </tr>
            <tr>
                <th>Funding Source</th>
                <td colspan="3">
                    <input name="funding_source" value="<?= htmlspecialchars($fundingSourceValue) ?>" placeholder="Enter funding source">
                </td>
            </tr>
            <tr>
                <th>Duration</th>
                <td colspan="3">
                    <input name="duration_writing" value="<?= htmlspecialchars($durationValue) ?>" placeholder="Start of writing to target submission to journal">
                </td>
            </tr>
            <tr>
                <th>Target Journal</th>
                <td colspan="3">
                    <input name="target_journal" value="<?= htmlspecialchars($targetJournalValue) ?>" placeholder="Enter target journal">
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">SDG/s</h3>
        <div class="proposal-check-grid">
            <?php foreach ($sdgOptions as $value => $label): ?>
                <label class="proposal-check-item">
                    <input type="checkbox" name="sdgs[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedSdgs[$value]) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">CHED A.C.H.I.E.V.E.</h3>
        <div class="proposal-check-grid">
            <?php foreach ($chedAchieveOptions as $value => $label): ?>
                <label class="proposal-check-item">
                    <input type="checkbox" name="ched_achieve[]" value="<?= htmlspecialchars($value) ?>" <?= isset($selectedAchieve[$value]) ? 'checked' : '' ?>>
                    <span><?= htmlspecialchars($label) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Brief Rationale</h3>
        <textarea name="brief_rationale" class="proposal-textarea" placeholder="Describe the brief rationale for the manuscript."><?= htmlspecialchars($briefRationaleValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Objectives</h3>
        <textarea name="objectives" class="proposal-textarea" placeholder="State the objectives of the manuscript."><?= htmlspecialchars($objectivesValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">General Methodology</h3>
        <textarea name="general_methodology" class="proposal-textarea" placeholder="Describe the general methodology."><?= htmlspecialchars($methodologyValue) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Highlights of results</h3>
        <textarea name="highlights_of_results" class="proposal-textarea" placeholder="Summarize the highlights of results."><?= htmlspecialchars($highlightsValue) ?></textarea>
    </section>

    <?php
    $workflowProposal = $proposal ?? [
        'status' => 'draft',
        'project_type' => $lockedProjectType,
        'summary' => json_encode(['form_type' => 'manuscript']),
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
    document.querySelectorAll('textarea[data-autoresize], .proposal-textarea').forEach((textarea) => {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${textarea.scrollHeight}px`;
        };
        resize();
        textarea.addEventListener('input', resize);
    });
})();
</script>
