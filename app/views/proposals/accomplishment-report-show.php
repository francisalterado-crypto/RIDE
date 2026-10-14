<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */
/** @var array<string, string> $requiredFileList */
/** @var array<string, list<array<string, mixed>>> $requiredDocuments */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($proposal['title']);
$pageSubtitle = 'Review the submitted Accomplishment Report (WPU-QSF-RIDE-ESO-13).';

$summaryData = [];
if (!empty($proposal['summary'])) {
    $decodedSummary = json_decode((string) $proposal['summary'], true);
    if (is_array($decodedSummary)) {
        $summaryData = $decodedSummary;
    }
}

$display = static function (string $value): string {
    $trimmed = trim($value);

    return $trimmed === '' ? '—' : nl2br(htmlspecialchars($trimmed));
};

$summaryField = static function (string $key) use ($summaryData): string {
    return isset($summaryData[$key]) && is_string($summaryData[$key]) ? $summaryData[$key] : '';
};

$collegeDisplay = $summaryField('college_name') !== ''
    ? $summaryField('college_name')
    : (string) ($proposal['college_name'] ?? '');
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">ACCOMPLISHMENT REPORT</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-13 Rev.00 (08.15.25)</p>
    </header>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
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
                <th>College</th>
                <td><?= htmlspecialchars($collegeDisplay !== '' ? $collegeDisplay : '—') ?></td>
                <th>Title</th>
                <td><?= htmlspecialchars((string) ($proposal['title'] ?? '—')) ?></td>
            </tr>
            <tr>
                <th>Authors</th>
                <td><?= $display($summaryField('authors')) ?></td>
                <th>Keywords</th>
                <td><?= $display($summaryField('keywords')) ?></td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Introduction</h3>
        <p><?= $display($summaryField('introduction')) ?></p>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Methodology</h3>
        <p><?= $display($summaryField('methodology')) ?></p>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Results</h3>
        <p><?= $display($summaryField('results')) ?></p>
        <?php
        $tableCaption = trim($summaryField('table_caption'));
        $tableDocuments = is_array($requiredDocuments['ar_results_table'] ?? null) ? $requiredDocuments['ar_results_table'] : [];
        $proposalId = (int) ($proposal['id'] ?? 0);
        ?>
        <?php if ($tableCaption !== '' || $tableDocuments !== []): ?>
            <div class="eso-ar-caption-upload">
                <?php if ($tableCaption !== ''): ?>
                    <p class="proposal-section-note"><?= htmlspecialchars($tableCaption) ?></p>
                <?php endif; ?>
                <?php foreach ($tableDocuments as $document): ?>
                    <?php
                    $docUrl = base_url('projects/' . $proposalId . '/documents/' . (int) $document['id']);
                    $mime = strtolower((string) ($document['mime_type'] ?? ''));
                    $isImage = str_starts_with($mime, 'image/') || preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) ($document['original_name'] ?? '')) === 1;
                    ?>
                    <div class="trainings-conducted-uploaded-file">
                        <a href="<?= htmlspecialchars($docUrl) ?>"><?= htmlspecialchars((string) $document['original_name']) ?></a>
                        <?php if ($isImage): ?>
                            <img class="eso-ar-table-preview" src="<?= htmlspecialchars($docUrl) ?>" alt="<?= htmlspecialchars((string) $document['original_name']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php
        $figureCaption = trim($summaryField('figure_caption'));
        $figureDocuments = is_array($requiredDocuments['ar_results_figure'] ?? null) ? $requiredDocuments['ar_results_figure'] : [];
        ?>
        <?php if ($figureCaption !== '' || $figureDocuments !== []): ?>
            <div class="eso-ar-caption-upload">
                <?php if ($figureCaption !== ''): ?>
                    <p class="proposal-section-note"><?= htmlspecialchars($figureCaption) ?></p>
                <?php endif; ?>
                <?php foreach ($figureDocuments as $document): ?>
                    <?php
                    $docUrl = base_url('projects/' . $proposalId . '/documents/' . (int) $document['id']);
                    $mime = strtolower((string) ($document['mime_type'] ?? ''));
                    $isImage = str_starts_with($mime, 'image/') || preg_match('/\.(jpe?g|png|gif|webp)$/i', (string) ($document['original_name'] ?? '')) === 1;
                    ?>
                    <div class="trainings-conducted-uploaded-file">
                        <a href="<?= htmlspecialchars($docUrl) ?>"><?= htmlspecialchars((string) $document['original_name']) ?></a>
                        <?php if ($isImage): ?>
                            <img class="eso-ar-table-preview" src="<?= htmlspecialchars($docUrl) ?>" alt="<?= htmlspecialchars((string) $document['original_name']) ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php
    $allowUpload = false;
    require APP_PATH . '/views/proposals/_accomplishment-report-supporting-documents.php';
    ?>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">References</h3>
        <p><?= $display($summaryField('references')) ?></p>
    </section>

    <section class="proposal-section">
        <h3 class="proposal-subtitle">Acknowledgement</h3>
        <p><?= $display($summaryField('acknowledgement')) ?></p>
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
