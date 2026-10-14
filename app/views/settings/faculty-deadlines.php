<?php

/** @var array{deadline_enabled: bool, months: list<int>, open_date: string, activated_groups: list<string>} $config */
/** @var list<int> $deadlineMonths */
/** @var list<string> $activatedGroups */
/** @var bool $deadlineEnabled */
/** @var string $submissionOpenDate */
/** @var array<string, string> $monthOptions */
/** @var array<string, string> $formGroupOptions */
/** @var string $deadlineSummary */

$selectedMonths = array_flip($deadlineMonths);
$selectedGroups = array_flip($activatedGroups);

?>

<div class="card">
    <p class="muted">
        VPRIDE and the RDO Director choose which form groups use closing deadlines and which quarter-end months apply.
        Forms without an activated deadline show <strong>No Deadline</strong> and stay open for submission.
    </p>
    <p class="muted">Current schedule: <strong><?= htmlspecialchars($deadlineSummary) ?></strong></p>
</div>

<div class="card">
    <form method="post" action="<?= base_url('settings/faculty-deadlines') ?>">
        <?= csrf_field() ?>

        <h2>Submission open date</h2>
        <p class="muted">Optional. Faculty cannot submit until this date. Leave blank to allow submission anytime.</p>
        <div style="margin: 0.75rem 0 1.25rem;">
            <input
                type="date"
                name="submission_open_date"
                id="submission-open-date"
                value="<?= htmlspecialchars($submissionOpenDate) ?>"
                style="max-width: 14rem;"
            >
        </div>

        <h2>Activate deadlines for</h2>
        <p class="muted">Select which form groups use closing due dates. Unselected groups always show <strong>No Deadline</strong> on their forms.</p>
        <div class="settings-option-list" style="margin: 0.75rem 0 1.25rem;">
            <?php foreach ($formGroupOptions as $groupKey => $groupLabel): ?>
                <label class="proposal-check-item settings-option-item">
                    <input
                        type="checkbox"
                        name="activated_form_groups[]"
                        value="<?= htmlspecialchars($groupKey) ?>"
                        <?= isset($selectedGroups[$groupKey]) ? 'checked' : '' ?>
                    >
                    <span><?= htmlspecialchars($groupLabel) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <h2>Quarter-end closing dates</h2>
        <p class="muted">Select one or more months when submissions are due (last day of each month). Leave all unchecked for open submission on activated groups.</p>
        <div class="deadline-month-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); gap: 0.5rem 1rem; margin: 1rem 0;">
            <?php foreach ($monthOptions as $value => $label): ?>
                <label class="proposal-check-item">
                    <input
                        type="checkbox"
                        name="deadline_months[]"
                        value="<?= (int) $value ?>"
                        <?= isset($selectedMonths[(int) $value]) ? 'checked' : '' ?>
                    >
                    <?= htmlspecialchars($label) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="muted">When a form group is checked but no months are selected, faculty forms in that group display <strong>No Deadline</strong>.</p>

        <div class="form-actions" style="margin-top: 1.25rem;">
            <button type="submit" class="btn btn-accent">Save settings</button>
        </div>
    </form>
</div>

<div class="card">
    <h2>Forms using these deadlines</h2>
    <ul class="muted" style="margin: 0.5rem 0 0 1.25rem; line-height: 1.6;">
        <li><strong>Research</strong> — completed / ongoing researches, outputs published &amp; presented, commercialized, resulted in extension, journal &amp; book citations, inventions, linkages</li>
        <li><strong>Extension</strong> — trainings conducted, technical advisory, extension linkages, outreach activities, technology adoption</li>
        <li><strong>Project monitoring</strong> — progress report, terminal report, terminal report assessment, OBR matrix</li>
        <li><strong>College consolidated</strong> — coordinator quarterly reports</li>
    </ul>
</div>
