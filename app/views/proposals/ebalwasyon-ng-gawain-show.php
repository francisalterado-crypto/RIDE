<?php
/** @var array $proposal */
/** @var list<array> $comments */
/** @var bool $canEdit */
/** @var bool $canApprove */
/** @var string $approveLabel */
/** @var string $stepLabel */

$pageTitle = (string) $proposal['title'] . ' | RIDE';
$pageHeading = htmlspecialchars($proposal['title']);
$pageSubtitle = 'Review the submitted activity evaluation (WPU-QSF-RIDE-ESO-16).';

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
$kasarianValue = $summaryField('kasarian');
$kasarianLabel = match ($kasarianValue) {
    'babae' => 'Babae',
    'lalaki' => 'Lalaki',
    default => '',
};
$ratings = is_array($summaryData['ratings'] ?? null) ? $summaryData['ratings'] : [];
$average = ebalwasyon_ng_gawain_average($ratings);
$markaDisplay = $average !== null ? number_format($average, 2, '.', '') : $summaryField('marka');
$legendDisplay = ebalwasyon_ng_gawain_legend($average);
$scale = ebalwasyon_ng_gawain_scale();
?>
<div class="page-actions-bar">
    <?php if ($canEdit): ?>
        <a class="btn btn-outline" href="<?= base_url('proposals/' . $proposal['id'] . '/edit') ?>">Edit</a>
    <?php endif; ?>
</div>

<div class="proposal-paper completed-researches-paper trainings-conducted-paper eso-extension-paper ebalwasyon-ng-gawain-paper">
    <header class="completed-researches-header">
        <p class="completed-researches-header-line">Republic of the Philippines</p>
        <p class="completed-researches-header-line completed-researches-header-line--strong">WESTERN PHILIPPINES UNIVERSITY</p>
        <p class="completed-researches-header-line">Office of Extension Services</p>
        <h2 class="completed-researches-title">EBALWASYON NG GAWAIN</h2>
        <p class="completed-researches-form-id">WPU-QSF-RIDE-ESO-16 Rev.00 (08.15.25)</p>
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
                <td colspan="3"><?= htmlspecialchars($collegeDisplay !== '' ? $collegeDisplay : '—') ?></td>
            </tr>
            <tr>
                <th>Paksang Gawain</th>
                <td colspan="3"><?= $display($summaryField('paksang_gawain')) ?></td>
            </tr>
            <tr>
                <th>Pangalan ng Dumalo</th>
                <td><?= $display($summaryField('pangalan_ng_dumalo')) ?></td>
                <th>Petsa</th>
                <td><?= htmlspecialchars($summaryField('petsa') !== '' ? $summaryField('petsa') : '—') ?></td>
            </tr>
            <tr>
                <th>Kasarian</th>
                <td><?= $display($kasarianLabel) ?></td>
                <th>Marka</th>
                <td>
                    <?= htmlspecialchars($markaDisplay !== '' ? $markaDisplay : '—') ?>
                    <?php if ($legendDisplay !== ''): ?>
                        <div class="proposal-table-meta"><?= htmlspecialchars($legendDisplay) ?></div>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </section>

    <section class="proposal-section">
        <p class="ebalwasyon-scale-line">
            <?php foreach ($scale as $score => $label): ?>
                <span><strong><?= (int) $score ?></strong> <?= htmlspecialchars($label) ?></span>
            <?php endforeach; ?>
        </p>
        <div class="proposal-table-wrap">
            <table class="proposal-table ebalwasyon-eval-table">
                <thead>
                    <tr>
                        <th>EBALWASYON</th>
                        <?php foreach (array_keys($scale) as $score): ?>
                            <th class="ebalwasyon-score-col"><?= (int) $score ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (ebalwasyon_ng_gawain_sections() as $section): ?>
                        <tr class="ebalwasyon-section-row">
                            <th colspan="6"><?= htmlspecialchars($section['heading']) ?></th>
                        </tr>
                        <?php foreach ($section['items'] as $itemKey => $itemLabel): ?>
                            <?php $selectedScore = (string) ($ratings[$itemKey] ?? ''); ?>
                            <tr>
                                <td><?= htmlspecialchars($itemLabel) ?></td>
                                <?php foreach (array_keys($scale) as $score): ?>
                                    <td class="ebalwasyon-score-col">
                                        <?= $selectedScore === (string) $score ? '√' : '' ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="proposal-section">
        <table class="proposal-table completed-researches-meta-table">
            <tr>
                <th>Karagdagang komento o mungkahi</th>
                <td><?= $display($summaryField('karagdagang_komento')) ?></td>
            </tr>
            <tr>
                <th>Lagda</th>
                <td><?= $display($summaryField('lagda')) ?></td>
            </tr>
        </table>
        <p class="ebalwasyon-legend">
            Legend (Resulta ng Ebalwasyon):
            4.50–5.00 – Best / Lubhang Katangi-tangi;
            3.50–4.49 – Better / Katangi-tangi;
            2.50–3.49 – Good / Kasiya-siya;
            1.50–2.49 – Fair / Kainaman;
            1.00–1.49 – Poor / Mahina
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
