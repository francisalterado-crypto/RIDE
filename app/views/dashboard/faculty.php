<?php
/** Dashboard for faculty: their own proposals, projects, deadlines and publications. */
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $drafts */
/** @var list<array<string, mixed>> $returnedProposals */
/** @var list<array<string, mixed>> $inReview */
/** @var list<array<string, mixed>> $ongoingProjects */
/** @var list<array<string, mixed>> $overdueMilestones */
/** @var list<array<string, mixed>> $overdueReports */
/** @var list<array<string, mixed>> $recentPapers */

use App\Support\MonitoringRoles;

$firstName = trim((string) ($user['first_name'] ?? ''));
$pageTitle = 'Dashboard — RIDE IMS';
$pageHeading = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . trim($firstName . ' ' . (string) ($user['last_name'] ?? ''))
    . (!empty($user['college_name']) ? ', ' . $user['college_name'] : '');

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$formatDate = static function (?string $date): string {
    $time = $date ? strtotime($date) : false;

    return $time ? date('M j, Y', $time) : '';
};

$attentionCount = count($returnedProposals) + count($overdueMilestones) + count($overdueReports) + count($drafts);
$publicationCount = (int) $paperCount + (int) $presentationCount;
$activity = is_array($monthlyActivity ?? null) ? $monthlyActivity : ['labels' => [], 'submitted' => [], 'approved' => []];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="fd">
    <?php require APP_PATH . '/views/dashboard/_notices.php'; ?>

    <?php require APP_PATH . '/views/dashboard/_coauthor-invitations.php'; ?>

    <div class="fd-actions">
        <a class="fd-action" href="<?= base_url('proposals/create' . proposal_nav_scope_query('research')) ?>">
            <strong>New research proposal</strong><span>Research and Development</span>
        </a>
        <a class="fd-action" href="<?= base_url('proposals/create' . proposal_nav_scope_query('extension')) ?>">
            <strong>New extension proposal</strong><span>Extension Services</span>
        </a>
        <a class="fd-action" href="<?= base_url('scholarly') ?>">
            <strong>Record a publication</strong><span>Papers and presentations</span>
        </a>
        <a class="fd-action" href="<?= base_url('forms') ?>">
            <strong>Downloadable forms</strong><span>Templates to fill out</span>
        </a>
    </div>

    <div class="fd-stats">
        <a class="fd-stat<?= $attentionCount > 0 ? ' fd-stat--alert' : '' ?>" href="#fd-attention">
            <span class="fd-stat-value"><?= $attentionCount ?></span>
            <span class="fd-stat-label">Need your attention</span>
        </a>
        <a class="fd-stat" href="#fd-in-review">
            <span class="fd-stat-value"><?= count($inReview) ?></span>
            <span class="fd-stat-label">Proposals in review</span>
        </a>
        <a class="fd-stat" href="<?= base_url('projects') ?>">
            <span class="fd-stat-value"><?= count($ongoingProjects) ?></span>
            <span class="fd-stat-label">Ongoing projects</span>
        </a>
        <a class="fd-stat" href="<?= base_url('scholarly') ?>">
            <span class="fd-stat-value"><?= $publicationCount ?></span>
            <span class="fd-stat-label">Publications recorded</span>
        </a>
    </div>

    <div class="fd-columns">
        <section class="fd-panel" id="fd-attention">
            <div class="fd-panel-head">
                <h2>Needs your attention</h2>
            </div>
            <?php if ($attentionCount === 0): ?>
                <p class="fd-empty">You're all caught up. Nothing is waiting on you right now.</p>
            <?php endif; ?>
            <ul class="fd-list">
                <?php foreach ($returnedProposals as $p): ?>
                    <li class="fd-item fd-item--returned">
                        <div class="fd-item-main">
                            <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                            <span class="fd-tag fd-tag--returned">Returned for revision</span>
                            <a class="fd-item-title" href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                            <?php if (!empty($p['return_comment'])): ?>
                                <p class="fd-item-note">"<?= $e($p['return_comment']) ?>"<?= !empty($p['return_by']) ? ' from ' . $e($p['return_by']) : '' ?></p>
                            <?php endif; ?>
                        </div>
                        <a class="btn btn-sm btn-accent" href="<?= base_url('proposals/' . (int) $p['id'] . '/edit') ?>">Revise</a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($overdueMilestones as $m): ?>
                    <li class="fd-item">
                        <div class="fd-item-main">
                            <span class="fd-kind">Project milestone</span>
                            <span class="fd-tag fd-tag--overdue">Overdue</span>
                            <span class="fd-item-title"><?= $e($m['title']) ?></span>
                            <p class="fd-item-note"><?= $e($m['project_title']) ?>, due <?= $e($formatDate($m['due_date'] ?? null)) ?></p>
                        </div>
                        <a class="btn btn-sm btn-outline" href="<?= base_url('projects/' . (int) $m['proposal_id']) ?>">Open project</a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($overdueReports as $r): ?>
                    <li class="fd-item">
                        <div class="fd-item-main">
                            <span class="fd-kind">Progress Report</span>
                            <span class="fd-tag fd-tag--overdue">Overdue</span>
                            <span class="fd-item-title"><?= $e($r['project_title']) ?></span>
                            <p class="fd-item-note">Was due <?= $e($formatDate($r['due_date'] ?? null)) ?></p>
                        </div>
                        <a class="btn btn-sm btn-outline" href="<?= base_url('projects/' . (int) $r['proposal_id'] . '/reports/' . (int) $r['id']) ?>">Finish report</a>
                    </li>
                <?php endforeach; ?>
                <?php foreach ($drafts as $p): ?>
                    <li class="fd-item">
                        <div class="fd-item-main">
                            <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                            <span class="fd-tag fd-tag--draft">Draft</span>
                            <a class="fd-item-title" href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                            <p class="fd-item-note">Last edited <?= $e($formatDate($p['updated_at'] ?? null)) ?>. Not submitted yet.</p>
                        </div>
                        <a class="btn btn-sm btn-outline" href="<?= base_url('proposals/' . (int) $p['id'] . '/edit') ?>">Continue</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="fd-panel" id="fd-in-review">
            <div class="fd-panel-head">
                <h2>Proposals in review</h2>
                <a href="<?= base_url('proposals') ?>">All proposals</a>
            </div>
            <?php if ($inReview === []): ?>
                <p class="fd-empty">None of your proposals are under review right now.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($inReview as $p): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                                <?php if (($p['membership'] ?? 'lead') === 'coauthor'): ?><span class="fd-tag">Co-author</span><?php endif; ?>
                                <a class="fd-item-title" href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                                <p class="fd-item-note">
                                    Waiting on: <strong><?= $e(MonitoringRoles::stepLabel((string) ($p['current_step'] ?? ''))) ?></strong>
                                    <?php if (!empty($p['submitted_at'])): ?>, submitted <?= $e($formatDate($p['submitted_at'])) ?><?php endif; ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="fd-columns">
        <section class="fd-panel">
            <div class="fd-panel-head">
                <h2>My ongoing projects</h2>
                <a href="<?= base_url('projects') ?>">All projects</a>
            </div>
            <?php if ($ongoingProjects === []): ?>
                <p class="fd-empty">Approved proposals appear here as projects you can monitor.<?= $completedCount > 0 ? ' You have ' . (int) $completedCount . ' completed.' : '' ?></p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($ongoingProjects as $p): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                                <?php if (!empty($p['project_code'])): ?><span class="fd-tag"><?= $e($p['project_code']) ?></span><?php endif; ?>
                                <a class="fd-item-title" href="<?= base_url('projects/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                            </div>
                            <a class="btn btn-sm btn-outline" href="<?= base_url('projects/' . (int) $p['id']) ?>">Monitor</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="fd-panel">
            <div class="fd-panel-head">
                <h2>Recent publications</h2>
                <a href="<?= base_url('scholarly') ?>">Manage</a>
            </div>
            <?php if ($recentPapers === []): ?>
                <p class="fd-empty">Keep a record of your published papers and presentations. <a href="<?= base_url('scholarly') ?>">Add your first one</a>.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($recentPapers as $paper): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-item-title"><?= $e($paper['title']) ?></span>
                                <p class="fd-item-note"><em><?= $e($paper['journal_name']) ?></em><?= !empty($paper['publication_year']) ? ', ' . $e($paper['publication_year']) : '' ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="fd-footnote"><?= (int) $paperCount ?> paper<?= (int) $paperCount === 1 ? '' : 's' ?> and <?= (int) $presentationCount ?> presentation<?= (int) $presentationCount === 1 ? '' : 's' ?> recorded.</p>
            <?php endif; ?>
        </section>
    </div>

    <div class="fd-columns">
        <section class="fd-panel">
            <div class="fd-panel-head"><h2>My proposal activity, last <?= count($activity['labels'] ?? []) ?> months</h2></div>
            <div class="fd-chart"><canvas id="fd-activity-chart" aria-label="Proposals submitted and approved per month"></canvas></div>
        </section>
        <section class="fd-panel">
            <div class="fd-panel-head"><h2>My publications per year</h2></div>
            <div class="fd-chart"><canvas id="fd-output-chart" aria-label="Papers and presentations per year"></canvas></div>
        </section>
    </div>
</div>

<script>
(() => {
    if (typeof Chart === 'undefined') {
        return;
    }
    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
    Chart.defaults.color = '#5b6e8c';
    const grid = { color: '#eef2f6' };
    const integerTicks = { precision: 0 };

    new Chart(document.getElementById('fd-activity-chart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($activity['labels'] ?? [])) ?>,
            datasets: [
                { label: 'Submitted', data: <?= json_encode(array_values($activity['submitted'] ?? [])) ?>, backgroundColor: '#2B5A8C', borderRadius: 4 },
                { label: 'Approved', data: <?= json_encode(array_values($activity['approved'] ?? [])) ?>, backgroundColor: '#2dd4bf', borderRadius: 4 },
            ],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: integerTicks } },
        },
    });

    new Chart(document.getElementById('fd-output-chart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map('strval', $outputYears)) ?>,
            datasets: [
                { label: 'Papers', data: <?= json_encode($papersPerYear) ?>, backgroundColor: '#0f2b3d', borderRadius: 4 },
                { label: 'Presentations', data: <?= json_encode($presentationsPerYear) ?>, backgroundColor: '#14b8a6', borderRadius: 4 },
            ],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } },
            scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, grid, ticks: integerTicks } },
        },
    });
})();
</script>
