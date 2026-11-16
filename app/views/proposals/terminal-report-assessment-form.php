<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Terminal Report Assessment Form | RIDE Hub';
$pageHeading = 'Terminal Report Assessment Form';
$pageSubtitle = 'Assessment checklist for terminal report evaluation.';

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
$parameters = [
    1 => 'The abstract presents an accurate synopsis of the research.',
    2 => 'The approved methodology in the proposal was followed (i.e. sample size, sampling procedure, data collection, data analysis, etc).',
    3 => 'The approved research ethics protocol was followed.',
    4 => 'Analysis of limitations and errors of the methods were presented.',
    5 => 'Stakeholders validated the data.',
    6 => 'Scientific literature supported the findings.',
    7 => 'The conclusion(s) captures the result of hypotheses test.',
    8 => 'The conclusions validated the theory used.',
    9 => 'The study generated new knowledge in the discipline.',
    10 => 'There are leads to future studies emanating from the current results.',
    11 => 'The paper complied with the standard of technical writing.',
    12 => "There is evidence that scientific literature and stakeholder's view support the recommendations.",
    13 => 'The over-all presentation of report captures the substance of the study.',
];
$scoreValues = is_array($summaryData['scores'] ?? null) ? $summaryData['scores'] : [];
$judgmentLabels = [
    'accepted_without_revisions' => 'Accepted without revisions',
    'accepted_after_minor_revisions' => 'Accepted after minor revisions suggested in this review',
    'accepted_after_major_revisions' => 'Accepted after major revisions suggested in this review',
    'failed_to_pass' => 'Failed to pass',
];
$scoreLabels = ['yes' => 'Yes', 'no' => 'No', 'na' => 'N/A'];
$judgment = $value('judgment');
$action = $isEdit ? base_url('proposals/' . $proposal['id'] . '/terminal-report-assessment-form') : base_url('proposals/terminal-report-assessment-form');
?>

<form class="webform rdo-webform" id="assessment-form" method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <div class="webform-hero">
        <div>
            <p class="webform-eyebrow">Office of Research and Development, Form WPU-QSF-RDE-VPRDE-22</p>
            <h2 class="webform-hero-title">Terminal Report Assessment Form</h2>
            <p class="webform-hero-text">Rate the terminal report on each parameter, give your comments and judgment, then save.</p>
            <?php $blankFormType = 'terminal_report_assessment_form'; require APP_PATH . '/views/partials/blank-form-link.php'; ?>
        </div>
    </div>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>Report Information</h2>
                <p>The research whose terminal report is assessed.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="assessment-date">Date</label>
                <input id="assessment-date" type="date" name="date" value="<?= htmlspecialchars($value('date')) ?>">
            </div>
            <div class="webform-field">
                <label for="assessment-control-no">Control No.</label>
                <input id="assessment-control-no" name="control_no" value="<?= htmlspecialchars($value('control_no')) ?>" placeholder="Enter the RIDE Control No.">
            </div>
            <div class="webform-field">
                <span class="webform-label">College</span>
                <?php if ($collegeDisplay !== ''): ?>
                    <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                    <p class="webform-readonly"><?= htmlspecialchars($collegeDisplay) ?></p>
                <?php else: ?>
                    <select name="college_id" id="terminal-assessment-college-id" required aria-label="College">
                        <option value="">Select the college</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>"><?= htmlspecialchars((string) $college['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="college_name" id="terminal-assessment-college-name" value="">
                <?php endif; ?>
            </div>
            <div class="webform-field webform-field--full">
                <label for="assessment-title">Research Title</label>
                <input id="assessment-title" name="research_title" value="<?= htmlspecialchars($value('research_title', (string) ($proposal['title'] ?? ''))) ?>" placeholder="Enter the title of the research">
            </div>
            <div class="webform-field">
                <label for="assessment-lead">Lead Researcher</label>
                <input id="assessment-lead" name="lead_researcher" value="<?= htmlspecialchars($value('lead_researcher')) ?>">
            </div>
            <div class="webform-field">
                <label for="assessment-co">Co-Researcher(s)</label>
                <input id="assessment-co" name="co_researchers" value="<?= htmlspecialchars($value('co_researchers')) ?>">
            </div>
            <div class="webform-field">
                <label for="assessment-duration">Duration of the Study</label>
                <input id="assessment-duration" name="duration_of_study" value="<?= htmlspecialchars($value('duration_of_study')) ?>">
            </div>
            <div class="webform-field">
                <label for="assessment-budget">Proposed Budget</label>
                <input id="assessment-budget" inputmode="decimal" name="proposed_budget" value="<?= htmlspecialchars($value('proposed_budget')) ?>">
            </div>
        </div>
    </section>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">2</span>
            <div>
                <h2>Assessment Checklist</h2>
                <p>Select Yes, No or N/A for each parameter.</p>
            </div>
        </header>
        <ol class="assessment-list">
            <?php foreach ($parameters as $number => $label): ?>
                <?php $current = strtolower((string) ($scoreValues[(string) $number] ?? '')); ?>
                <li class="assessment-item">
                    <span class="assessment-num"><?= (int) $number ?></span>
                    <span class="assessment-text" id="assessment-param-<?= (int) $number ?>"><?= htmlspecialchars($label) ?></span>
                    <span class="assessment-choice" role="radiogroup" aria-labelledby="assessment-param-<?= (int) $number ?>">
                        <?php foreach ($scoreLabels as $score => $scoreLabel): ?>
                            <label class="assessment-choice-option assessment-choice-option--<?= $score ?>">
                                <input type="radio" name="scores[<?= (int) $number ?>]" value="<?= $score ?>"<?= $current === $score ? ' checked' : '' ?>>
                                <span><?= $scoreLabel ?></span>
                            </label>
                        <?php endforeach; ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">3</span>
            <div><h2>Comments and Judgment</h2></div>
        </header>
        <div class="webform-grid">
            <div class="webform-field webform-field--full">
                <label for="assessment-comments">Comment(s)/Suggestion(s)/Recommendation(s)</label>
                <textarea id="assessment-comments" name="comments" rows="5" data-autoresize><?= htmlspecialchars($value('comments')) ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <span class="webform-label">My Judgment</span>
                <div class="proposal-check-grid">
                    <?php foreach ($judgmentLabels as $key => $label): ?>
                        <label class="proposal-check-item">
                            <input type="radio" name="judgment" value="<?= $key ?>"<?= $judgment === $key ? ' checked' : '' ?>>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="webform-field">
                <label for="reviewer_name">Reviewer (TWG Chair/Member)</label>
                <input id="reviewer_name" name="reviewer_name" value="<?= htmlspecialchars($value('reviewer_name')) ?>" placeholder="Enter the printed name of the reviewer">
            </div>
        </div>
    </section>

    <section class="webform-card">
        <?php
        $workflowProposal = $proposal ?? [
            'status' => 'draft',
            'project_type' => 'research',
            'summary' => json_encode(['form_type' => 'terminal_report_assessment_form']),
            'first_name' => '',
            'last_name' => '',
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
    document.querySelectorAll('#assessment-form textarea[data-autoresize]').forEach((textarea) => {
        const resize = () => { textarea.style.height = 'auto'; textarea.style.height = `${textarea.scrollHeight}px`; };
        resize();
        textarea.addEventListener('input', resize);
    });
    const collegeSelect = document.getElementById('terminal-assessment-college-id');
    const collegeNameInput = document.getElementById('terminal-assessment-college-name');
    if (collegeSelect && collegeNameInput) {
        const sync = () => { collegeNameInput.value = collegeSelect.options[collegeSelect.selectedIndex]?.dataset?.name ?? ''; };
        collegeSelect.addEventListener('change', sync);
        sync();
    }
})();
</script>
