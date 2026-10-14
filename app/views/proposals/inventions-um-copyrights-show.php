<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = 'Inventions, Utility Models and Copyrights';
$pageSubtitle = 'Quarterly report of accomplishments in research (WPU-QSF-RIDE-RDO-13).';

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$reportAsOf = is_string($summaryData['report_as_of'] ?? null) ? $summaryData['report_as_of'] : '';
$collegeDisplay = is_string($summaryData['college_name'] ?? null) && $summaryData['college_name'] !== ''
    ? $summaryData['college_name']
    : (string) ($proposal['college_name'] ?? '');
$entries = is_array($summaryData['entries'] ?? null) ? $summaryData['entries'] : [];
$sections = [
    'inventions_patented' => ['group' => 'Inventions', 'label' => 'A. Patented'],
    'inventions_applied_for_patenting' => ['group' => null, 'label' => 'B. Applied for Patenting'],
    'inventions_not_patented_but_utilized' => ['group' => null, 'label' => 'C. Not Patented but Utilized by the Community'],
    'utility_models_registered' => ['group' => 'Utility Models', 'label' => 'A. Registered'],
    'utility_models_applied_for_registration' => ['group' => null, 'label' => 'B. Applied for Registration'],
    'copyrights' => ['group' => 'Copyrights', 'label' => null],
];
?>

<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper inventions-um-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">INVENTIONS, UTILITY MODELS AND COPYRIGHTS</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-13 Rev.00 (09.15.25)</p>
    </header>

    <section class="proposal-section">
        <h2 class="proposal-section-title">Report Information</h2>
        <table class="proposal-table">
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
                <th>As of</th>
                <td><?= htmlspecialchars($reportAsOf !== '' ? $reportAsOf : '—') ?></td>
                <th>College</th>
                <td><?= htmlspecialchars($collegeDisplay !== '' ? $collegeDisplay : '—') ?></td>
            </tr>
        </table>
    </section>

    <section class="proposal-section trainings-conducted-section inventions-um-section">
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap inventions-um-table-wrap">
            <table class="proposal-table inventions-um-table trainings-conducted-table">
                <colgroup>
                    <col class="ium-col-title">
                    <col class="ium-col-date">
                    <col class="ium-col-date">
                    <col class="ium-col-inventors">
                    <col class="ium-col-patent">
                    <col class="ium-col-date">
                    <col class="ium-col-adopter">
                    <col class="ium-col-product">
                    <col class="ium-col-drive">
                </colgroup>
                <thead>
                    <tr>
                        <th>Research Title</th>
                        <th>Date Started</th>
                        <th>Date Developed/Completed</th>
                        <th>Inventor(s)/Researcher(s)</th>
                        <th>Patent Registration/Copyright Number</th>
                        <th>Date of Issue/Application</th>
                        <th>Adopter of Inventions/UM/Copyrights</th>
                        <th>Name of Commercial Product</th>
                        <th>Google Drive Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sections as $sectionKey => $section): ?>
                        <?php if (is_string($section['group']) && $section['group'] !== ''): ?>
                            <tr class="inventions-um-section-row"><td colspan="9"><?= htmlspecialchars($section['group']) ?></td></tr>
                        <?php endif; ?>
                        <?php if (is_string($section['label']) && $section['label'] !== ''): ?>
                            <tr class="inventions-um-subsection-row"><td colspan="9"><?= htmlspecialchars($section['label']) ?></td></tr>
                        <?php endif; ?>
                        <?php
                        $rows = is_array($entries[$sectionKey] ?? null) ? $entries[$sectionKey] : [];
                        if ($rows === []) {
                            $rows[] = [];
                        }
                        ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= nl2br(htmlspecialchars((string) ($row['research_title'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string) ($row['date_started'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['date_developed_completed'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['inventors_researchers'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string) ($row['patent_registration_copyright_number'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['date_of_issue_application'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['adopter'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['commercial_product_name'] ?? ''))) ?></td>
                                <td>
                                    <?php
                                    $driveLink = trim((string) ($row['google_drive_link'] ?? ''));
                                    if ($driveLink !== ''): ?>
                                        <a href="<?= htmlspecialchars($driveLink) ?>" target="_blank" rel="noopener noreferrer">View file</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="proposal-section-note inventions-um-note">
            Note: *An invention/utility model may be utilized for: 1) development of technology, 2) service provision, or 3) an end-product in itself or it may also be commercialized for selling to other end-users.
        </p>
    </section>

<?php
    $workflowSteps = proposal_workflow_steps($proposal);
    require APP_PATH . '/views/proposals/_approval-workflow.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Workflow Actions</h3>
        <div class="actions proposal-form-actions">
<?php require APP_PATH . '/views/proposals/_submit-for-review-button.php'; ?>

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
