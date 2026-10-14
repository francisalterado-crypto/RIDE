<?php
/** @var array|null $proposal */
/** @var list<array> $colleges */
/** @var string $collegeName */

$isEdit = $proposal !== null;
$pageTitle = ($isEdit ? 'Edit' : 'New') . ' Terminal Report Assessment Form | RIDE';
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
?>

<form class="proposal-paper completed-researches-paper trainings-conducted-paper terminal-report-assessment-paper" method="post" action="<?= $isEdit ? base_url('proposals/' . $proposal['id'] . '/terminal-report-assessment-form') : base_url('proposals/terminal-report-assessment-form') ?>">
    <?= csrf_field() ?>

    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <h2 class="completed-researches-title">TERMINAL REPORT ASSESSMENT FORM</h2>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
            <tr>
                <th>Date</th>
                <td><input type="date" name="date" value="<?= htmlspecialchars($value('date')) ?>"></td>
                <th>Control No.</th>
                <td><input name="control_no" value="<?= htmlspecialchars($value('control_no')) ?>"></td>
            </tr>
            <tr>
                <th>College</th>
                <td colspan="3">
                    <?php if ($collegeDisplay !== ''): ?>
                        <input type="hidden" name="college_name" value="<?= htmlspecialchars($collegeDisplay) ?>">
                        <span><?= htmlspecialchars($collegeDisplay) ?></span>
                    <?php else: ?>
                        <select name="college_id" id="terminal-assessment-college-id" required>
                            <option value="">Select college</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= (int) $college['id'] ?>" data-name="<?= htmlspecialchars((string) $college['name']) ?>">
                                    <?= htmlspecialchars((string) $college['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="college_name" id="terminal-assessment-college-name" value="">
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Lead Researcher</th>
                <td colspan="3"><input name="lead_researcher" value="<?= htmlspecialchars($value('lead_researcher')) ?>"></td>
            </tr>
            <tr>
                <th>Co-Researcher(s)</th>
                <td colspan="3"><input name="co_researchers" value="<?= htmlspecialchars($value('co_researchers')) ?>"></td>
            </tr>
            <tr>
                <th>Research Title</th>
                <td colspan="3"><input name="research_title" value="<?= htmlspecialchars($value('research_title', (string) ($proposal['title'] ?? ''))) ?>"></td>
            </tr>
            <tr>
                <th>Duration of the Study</th>
                <td><input name="duration_of_study" value="<?= htmlspecialchars($value('duration_of_study')) ?>"></td>
                <th>Proposed Budget</th>
                <td><input name="proposed_budget" value="<?= htmlspecialchars($value('proposed_budget')) ?>"></td>
            </tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section">
        <h3 class="proposal-subtitle">Assessment Checklist</h3>
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap">
            <table class="proposal-table trainings-conducted-table terminal-assessment-table">
                <colgroup>
                    <col class="ta-col-no">
                    <col class="ta-col-parameter">
                    <col class="ta-col-score">
                    <col class="ta-col-score">
                    <col class="ta-col-score">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Parameters</th>
                        <th>Yes</th>
                        <th>No</th>
                        <th>N/A</th>
                    </tr>
                </thead>
                <tbody>
            <?php foreach ($parameters as $number => $label): ?>
                <?php $current = strtolower((string) ($scoreValues[(string) $number] ?? '')); ?>
                <tr>
                    <td><?= (int) $number ?></td>
                    <td><?= htmlspecialchars($label) ?></td>
                    <td class="terminal-assessment-score"><input type="radio" name="scores[<?= (int) $number ?>]" value="yes" <?= $current === 'yes' ? 'checked' : '' ?>></td>
                    <td class="terminal-assessment-score"><input type="radio" name="scores[<?= (int) $number ?>]" value="no" <?= $current === 'no' ? 'checked' : '' ?>></td>
                    <td class="terminal-assessment-score"><input type="radio" name="scores[<?= (int) $number ?>]" value="na" <?= $current === 'na' ? 'checked' : '' ?>></td>
                </tr>
            <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Comment(s)/Suggestion(s)/Recommendation(s):</h3>
        <textarea class="proposal-textarea" rows="6" name="comments"><?= htmlspecialchars($value('comments')) ?></textarea>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">My Judgment (Please check):</h3>
        <?php $judgment = $value('judgment'); ?>
        <div class="terminal-assessment-judgment">
            <label class="proposal-check-item">
                <input type="radio" name="judgment" value="accepted_without_revisions" <?= $judgment === 'accepted_without_revisions' ? 'checked' : '' ?>>
                <span>Accepted without revisions</span>
            </label>
            <label class="proposal-check-item">
                <input type="radio" name="judgment" value="accepted_after_minor_revisions" <?= $judgment === 'accepted_after_minor_revisions' ? 'checked' : '' ?>>
                <span>Accepted after minor revisions suggested in this review</span>
            </label>
            <label class="proposal-check-item">
                <input type="radio" name="judgment" value="accepted_after_major_revisions" <?= $judgment === 'accepted_after_major_revisions' ? 'checked' : '' ?>>
                <span>Accepted after major revisions suggested in this review</span>
            </label>
            <label class="proposal-check-item">
                <input type="radio" name="judgment" value="failed_to_pass" <?= $judgment === 'failed_to_pass' ? 'checked' : '' ?>>
                <span>Failed to pass</span>
            </label>
        </div>
    </section>

    <section class="proposal-section completed-researches-signoff">
        <div style="max-width: 420px;">
            <label for="reviewer_name">Signature over printed name</label>
            <input id="reviewer_name" name="reviewer_name" value="<?= htmlspecialchars($value('reviewer_name')) ?>">
            <div style="font-size: 13px; margin-top: 4px;">TWG Chair/Member</div>
        </div>
    </section>

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

    <div class="actions proposal-form-actions">
        <button type="submit" class="btn"><?= $isEdit ? 'Save Changes' : 'Save Draft' ?></button>
        <?php if ($isEdit): ?>
            <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id']) ?>">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
(() => {
    const collegeSelect = document.getElementById('terminal-assessment-college-id');
    const collegeNameInput = document.getElementById('terminal-assessment-college-name');
    if (!collegeSelect || !collegeNameInput) {
        return;
    }

    const syncCollegeName = () => {
        const option = collegeSelect.options[collegeSelect.selectedIndex];
        collegeNameInput.value = option?.dataset?.name ?? '';
    };

    collegeSelect.addEventListener('change', syncCollegeName);
    syncCollegeName();
})();
</script>
