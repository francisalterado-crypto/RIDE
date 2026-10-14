<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = 'Book Citation';
$pageSubtitle = 'Research article citation report in books.';

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
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper book-citation-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Research and Development</p>
        <p class="completed-researches-header-line">Quarterly Report of Accomplishments in Research</p>
        <h2 class="completed-researches-title">RESEARCH ARTICLE AS CITED BY OTHER RESEARCHER(S) IN BOOKS</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-RDO-12 Rev.00 (09.15.25)</p>
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

    <section class="proposal-section trainings-conducted-section book-citation-section">
        <p class="trainings-conducted-scroll-hint proposal-section-note">Scroll horizontally to view all columns, including <strong>Google Drive Link</strong> for attached files.</p>
        <div class="proposal-table-wrap trainings-conducted-table-wrap book-citation-table-wrap">
            <table class="proposal-table book-citation-table trainings-conducted-table">
                <thead>
                    <tr>
                        <th>Title of Original Research Article/Paper Cited</th>
                        <th>Author(s) of Original Article/Paper</th>
                        <th>Title of Publication where the Original Research Article Being Cited was Published</th>
                        <th>Title of New Book Chapter where the Original Research Article/Paper was Cited</th>
                        <th>Author(s) of the Book Chapter who Cited the Original Research Article/Paper</th>
                        <th>Title of Book where the Chapter was Published</th>
                        <th>Vol. No./Issue no.</th>
                        <th>Number of Pages</th>
                        <th>Year of Publication</th>
                        <th>ISBN</th>
                        <th>Publisher</th>
                        <th>Google Drive Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($entries === []): ?>
                        <tr>
                            <td colspan="12" class="proposal-table-empty">No entries added yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $row): ?>
                            <tr>
                                <td><?= nl2br(htmlspecialchars((string) ($row['title_original_article_cited'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['authors_original_article'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['title_publication_original'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['title_new_book_chapter'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['authors_book_chapter'] ?? ''))) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['title_book_chapter_published'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string) ($row['volume_issue'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['pages'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['year_publication'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['isbn'] ?? '')) ?></td>
                                <td><?= nl2br(htmlspecialchars((string) ($row['publisher'] ?? ''))) ?></td>
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
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
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
