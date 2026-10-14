<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($proposal['title']);
$pageSubtitle = 'Manuscript writing proposal for publication.';
$user = \App\Core\Auth::user() ?? [];

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$summaryField = static function (string $key) use ($summaryData): string {
    $value = $summaryData[$key] ?? '';
    return is_string($value) ? $value : '';
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

$originStatus = $summaryField('study_origin_status');
$originStatusLabel = match ($originStatus) {
    'ongoing' => 'Ongoing',
    'completed' => 'Completed',
    default => '',
};
$showSignatureHint = false;
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Manuscript Writing for Publication</h2>
        <table class="proposal-table">
            <tr>
                <th>Title</th>
                <td colspan="3"><?= htmlspecialchars($proposal['title']) ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge badge-<?= htmlspecialchars($proposal['status']) ?>"><?= htmlspecialchars($proposal['status']) ?></span>
                    <?php if ($proposal['current_step']): ?>
                        <div class="proposal-table-meta">Step: <?= htmlspecialchars($stepLabel) ?></div>
                    <?php endif; ?>
                </td>
                <th>Project Code</th>
                <td><?= htmlspecialchars($proposal['project_code'] ?: 'Draft') ?></td>
            </tr>
            <tr>
                <th>Program/Project/Study origin</th>
                <td colspan="3">
                    <?php if ($summaryField('study_origin_details') !== ''): ?>
                        <p><?= nl2br(htmlspecialchars($summaryField('study_origin_details'))) ?></p>
                    <?php endif; ?>
                    <?php if ($originStatusLabel !== ''): ?>
                        <p><strong>Status:</strong> <?= htmlspecialchars($originStatusLabel) ?></p>
                    <?php endif; ?>
                    <?php if ($summaryField('study_origin_completion_date') !== ''): ?>
                        <p><strong>Date of completion:</strong> <?= htmlspecialchars($summaryField('study_origin_completion_date')) ?></p>
                    <?php endif; ?>
                    <?php if ($summaryField('study_origin_details') === '' && $originStatusLabel === '' && $summaryField('study_origin_completion_date') === ''): ?>
                        —
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Lead Researcher/s</th>
                <td colspan="3"><?= nl2br(htmlspecialchars($summaryField('lead_researchers') !== '' ? $summaryField('lead_researchers') : '—')) ?></td>
            </tr>
            <tr>
                <th>Co-researchers/ collaborators</th>
                <td colspan="3"><?= nl2br(htmlspecialchars($summaryField('co_researchers') !== '' ? $summaryField('co_researchers') : '—')) ?></td>
            </tr>
            <tr>
                <th>Funding Source</th>
                <td colspan="3"><?= htmlspecialchars($proposal['funding_source'] ?? '—') ?></td>
            </tr>
            <tr>
                <th>Duration</th>
                <td colspan="3"><?= htmlspecialchars($summaryField('duration_writing') !== '' ? $summaryField('duration_writing') : '—') ?></td>
            </tr>
            <tr>
                <th>Target Journal</th>
                <td colspan="3"><?= htmlspecialchars($summaryField('target_journal') !== '' ? $summaryField('target_journal') : '—') ?></td>
            </tr>
        </table>
    </section>

    <?php if (!empty($summaryData['sdgs']) && is_array($summaryData['sdgs'])): ?>
        <section class="proposal-section">
            <h3 class="proposal-subtitle">SDG/s</h3>
            <div class="proposal-chip-row">
                <?php foreach ($summaryData['sdgs'] as $item): ?>
                    <?php if (!is_string($item) || !isset($sdgOptions[$item])) {
                        continue;
                    } ?>
                    <span class="proposal-chip"><?= htmlspecialchars($sdgOptions[$item]) ?></span>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($summaryData['ched_achieve']) && is_array($summaryData['ched_achieve'])): ?>
        <section class="proposal-section">
            <h3 class="proposal-subtitle">CHED A.C.H.I.E.V.E.</h3>
            <div class="proposal-chip-row">
                <?php foreach ($summaryData['ched_achieve'] as $item): ?>
                    <?php if (!is_string($item) || !isset($chedAchieveOptions[$item])) {
                        continue;
                    } ?>
                    <span class="proposal-chip"><?= htmlspecialchars($chedAchieveOptions[$item]) ?></span>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php foreach ([
        'brief_rationale' => 'Brief Rationale',
        'objectives' => 'Objectives',
        'general_methodology' => 'General Methodology',
        'highlights_of_results' => 'Highlights of results',
    ] as $key => $label): ?>
        <?php if ($summaryField($key) !== ''): ?>
            <section class="proposal-section">
                <h3 class="proposal-subtitle"><?= htmlspecialchars($label) ?></h3>
                <p><?= nl2br(htmlspecialchars($summaryField($key))) ?></p>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
<?php
            $submitConfirmMessage = 'Submit this proposal for review?';
            require APP_PATH . '/views/proposals/_submit-for-review-button.php';
            ?>

            <?php if ($canApprove): ?>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/approve') ?>" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-accent"><?= htmlspecialchars($approveLabel) ?></button>
                </form>
                <form method="post" action="<?= base_url('proposals/' . $proposal['id'] . '/return') ?>" class="proposal-review-form">
                    <?= csrf_field() ?>
                    <input name="comment" placeholder="Revision comments (required)" required>
                    <button type="submit" class="btn btn-outline">Return for Revision</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="proposal-paper proposal-history-paper">
    <section class="proposal-section">
        <h2 class="proposal-section-title">Comments &amp; History</h2>
        <?php if (empty($comments)): ?>
            <p>No comments yet.</p>
        <?php else: ?>
            <ul class="proposal-history-list">
                <?php foreach ($comments as $c): ?>
                    <li>
                        <div class="proposal-history-meta">
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                            • <?= htmlspecialchars($c['action']) ?>
                            <?php if ($c['step']): ?> (<?= htmlspecialchars($c['step']) ?>)<?php endif; ?>
                            • <?= htmlspecialchars($c['created_at']) ?>
                        </div>
                        <div><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
