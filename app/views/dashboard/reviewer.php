<?php
/** Dashboard for reviewers (coordinator, dean, director, VPRIDE): their review queue and the state of their scope. */
/** @var array<string, mixed> $user */
/** @var array{role: string, title: string, step: string, types: list<string>, collegeId: ?int} $context */
/** @var list<array<string, mixed>> $queue */
/** @var list<array{label: string, count: int, isMine: bool}> $pipeline */
/** @var array<string, array{in_review: int, ongoing: int, completed: int}> $breakdown */
/** @var list<array<string, mixed>> $followUps */
/** @var list<array<string, mixed>> $myDrafts */

$role = $context['role'];
$types = $context['types'];
$singleType = count($types) === 1 ? $types[0] : null;
$actionWord = $role === 'coordinator' ? 'endorsement' : 'approval';

$pageTitle = 'Dashboard | RIDE';
$pageHeading = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''))
    . '. ' . $context['title'] . ', ' . $scopeLabel . '.';

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$daysWaiting = static function (?string $since): string {
    $time = $since ? strtotime($since) : false;
    if (!$time) {
        return '';
    }
    $days = (int) floor((time() - $time) / 86400);

    return $days < 1 ? 'arrived today' : 'waiting ' . $days . ' day' . ($days === 1 ? '' : 's');
};
$monitoringUrl = base_url('monitoring' . ($singleType !== null ? '?scope=' . $singleType : ''));
$activity = is_array($monthlyActivity ?? null) ? $monthlyActivity : ['labels' => [], 'submitted' => [], 'approved' => []];
$breakdownTitle = $context['collegeId'] === null ? 'Proposals by college' : 'Research and extension in your college';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="fd">
    <?php require APP_PATH . '/views/dashboard/_notices.php'; ?>

    <div class="fd-actions">
        <a class="fd-action" href="#rd-queue">
            <strong>Review queue</strong><span><?= count($queue) ?> waiting for your <?= $actionWord ?></span>
        </a>
        <a class="fd-action" href="<?= base_url('proposals') ?>">
            <strong>All proposals</strong><span><?= $e($scopeLabel) ?></span>
        </a>
        <a class="fd-action" href="<?= $monitoringUrl ?>">
            <strong>Monitoring</strong><span>Projects, milestones and reports</span>
        </a>
        <?php if ($role === 'coordinator' && $singleType === 'extension'): ?>
            <a class="fd-action" href="<?= base_url('proposals/create') ?>">
                <strong>New extension proposal</strong><span>Extension Program/Project Proposal</span>
            </a>
        <?php elseif (in_array('extension', $types, true) && in_array($role, ['director', 'vpride'], true)): ?>
            <a class="fd-action" href="<?= base_url('reports/extension-beneficiaries') ?>">
                <strong>Beneficiaries report</strong><span>Extension accreditation data</span>
            </a>
        <?php else: ?>
            <a class="fd-action" href="<?= base_url('forms') ?>">
                <strong>Downloadable forms</strong><span>Templates to fill out</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="fd-stats">
        <a class="fd-stat<?= count($queue) > 0 ? ' fd-stat--alert' : '' ?>" href="#rd-queue">
            <span class="fd-stat-value"><?= count($queue) ?></span>
            <span class="fd-stat-label">Waiting for your <?= $actionWord ?></span>
        </a>
        <a class="fd-stat" href="#rd-pipeline">
            <span class="fd-stat-value"><?= (int) $inReviewCount ?></span>
            <span class="fd-stat-label">In review across all steps</span>
        </a>
        <a class="fd-stat" href="<?= base_url('projects') ?>">
            <span class="fd-stat-value"><?= (int) $ongoingCount ?></span>
            <span class="fd-stat-label">Ongoing projects</span>
        </a>
        <a class="fd-stat<?= (int) $overdueCount > 0 ? ' fd-stat--alert' : '' ?>" href="#rd-followups">
            <span class="fd-stat-value"><?= (int) $overdueCount ?></span>
            <span class="fd-stat-label">Overdue milestones and reports</span>
        </a>
    </div>

    <div class="fd-columns">
        <section class="fd-panel" id="rd-queue">
            <div class="fd-panel-head">
                <h2>Waiting for your <?= $actionWord ?></h2>
                <a href="<?= base_url('proposals') ?>">All proposals</a>
            </div>
            <?php if ($queue === []): ?>
                <p class="fd-empty">Nothing is waiting for your review right now.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($queue as $p): ?>
                        <?php $lead = trim((string) ($p['first_name'] ?? '') . ' ' . (string) ($p['last_name'] ?? '')); ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                                <?php if ($context['collegeId'] === null && !empty($p['college_name'])): ?><span class="fd-tag"><?= $e($p['college_name']) ?></span><?php endif; ?>
                                <a class="fd-item-title" href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                                <p class="fd-item-note">From <?= $e($lead) ?>, <?= $e($daysWaiting($p['updated_at'] ?? null)) ?></p>
                            </div>
                            <a class="btn btn-sm btn-accent" href="<?= base_url('proposals/' . (int) $p['id']) ?>">Review</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="fd-panel" id="rd-pipeline">
            <div class="fd-panel-head">
                <h2>Where proposals are waiting</h2>
                <a href="<?= $monitoringUrl ?>">Monitoring</a>
            </div>
            <div class="fd-chart fd-chart--short"><canvas id="rd-pipeline-chart" aria-label="Proposals waiting at each review step"></canvas></div>
            <p class="fd-footnote">Your step is highlighted. <?= (int) $returnedCount ?> returned to the proponent for revision.</p>
        </section>
    </div>

    <div class="fd-columns">
        <section class="fd-panel" id="rd-followups">
            <div class="fd-panel-head">
                <h2>Projects that need follow-up</h2>
                <a href="<?= base_url('projects') ?>">All projects</a>
            </div>
            <?php if ($followUps === []): ?>
                <p class="fd-empty">No overdue milestones or progress reports. Every project is on track.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach (array_slice($followUps, 0, 8) as $f): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <?php if (!empty($f['code'])): ?><span class="fd-kind"><?= $e($f['code']) ?></span><?php endif; ?>
                                <?php if ($context['collegeId'] === null && $f['college'] !== ''): ?><span class="fd-tag"><?= $e($f['college']) ?></span><?php endif; ?>
                                <a class="fd-item-title" href="<?= base_url('projects/' . (int) $f['id']) ?>"><?= $e($f['title']) ?></a>
                                <p class="fd-item-note">
                                    <?php if ($f['milestones'] > 0): ?><span class="fd-tag fd-tag--overdue"><?= (int) $f['milestones'] ?> overdue milestone<?= $f['milestones'] === 1 ? '' : 's' ?></span><?php endif; ?>
                                    <?php if ($f['reports'] > 0): ?><span class="fd-tag fd-tag--overdue"><?= (int) $f['reports'] ?> overdue report<?= $f['reports'] === 1 ? '' : 's' ?></span><?php endif; ?>
                                </p>
                            </div>
                            <a class="btn btn-sm btn-outline" href="<?= base_url('projects/' . (int) $f['id']) ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($followUps) > 8): ?>
                    <p class="fd-footnote"><?= count($followUps) - 8 ?> more in <a href="<?= $monitoringUrl ?>">Monitoring</a>.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($role === 'coordinator'): ?>
            <section class="fd-panel">
                <div class="fd-panel-head">
                    <h2>My reports and drafts</h2>
                    <a href="<?= base_url('proposals') ?>">All</a>
                </div>
                <?php if ($myDrafts === []): ?>
                    <p class="fd-empty">You have no unsubmitted reports or drafts.</p>
                <?php else: ?>
                    <ul class="fd-list">
                        <?php foreach ($myDrafts as $p): ?>
                            <li class="fd-item">
                                <div class="fd-item-main">
                                    <span class="fd-kind"><?= $e(proposal_form_label($p)) ?></span>
                                    <?php if ($p['status'] === 'returned'): ?>
                                        <span class="fd-tag fd-tag--returned">Returned for revision</span>
                                    <?php else: ?>
                                        <span class="fd-tag fd-tag--draft">Draft</span>
                                    <?php endif; ?>
                                    <a class="fd-item-title" href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a>
                                </div>
                                <a class="btn btn-sm btn-outline" href="<?= base_url('proposals/' . (int) $p['id']) ?>">Continue</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="fd-panel">
                <div class="fd-panel-head"><h2><?= $e($breakdownTitle) ?></h2></div>
                <?php if ($breakdown === []): ?>
                    <p class="fd-empty">No proposals yet.</p>
                <?php else: ?>
                    <div class="fd-chart"><canvas id="rd-breakdown-chart" aria-label="<?= $e($breakdownTitle) ?>"></canvas></div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <section class="fd-panel">
        <div class="fd-panel-head"><h2>Submitted and approved, last <?= count($activity['labels'] ?? []) ?> months</h2></div>
        <div class="fd-chart"><canvas id="rd-activity-chart" aria-label="Proposals submitted and approved per month"></canvas></div>
    </section>
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
    const legend = { position: 'bottom', labels: { boxWidth: 12 } };

    const pipeline = <?= json_encode($pipeline) ?>;
    new Chart(document.getElementById('rd-pipeline-chart'), {
        type: 'bar',
        data: {
            labels: pipeline.map((step) => step.label),
            datasets: [{
                label: 'Proposals waiting',
                data: pipeline.map((step) => step.count),
                backgroundColor: pipeline.map((step) => (step.isMine ? '#14b8a6' : '#c7d6e2')),
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            // Labels are measured before the web font loads; leave room so they are not clipped.
            scales: { x: { beginAtZero: true, grid, ticks: integerTicks }, y: { grid: { display: false }, afterFit: (axis) => { axis.width += 12; } } },
        },
    });

    const breakdownCanvas = document.getElementById('rd-breakdown-chart');
    if (breakdownCanvas) {
        const breakdown = <?= json_encode($breakdown) ?>;
        const groups = Object.keys(breakdown);
        new Chart(breakdownCanvas, {
            type: 'bar',
            data: {
                labels: groups,
                datasets: [
                    { label: 'In review', data: groups.map((g) => breakdown[g].in_review), backgroundColor: '#2B5A8C', borderRadius: 4 },
                    { label: 'Ongoing', data: groups.map((g) => breakdown[g].ongoing), backgroundColor: '#14b8a6', borderRadius: 4 },
                    { label: 'Completed', data: groups.map((g) => breakdown[g].completed), backgroundColor: '#94a3b8', borderRadius: 4 },
                ],
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend },
                scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, grid, ticks: integerTicks } },
            },
        });
    }

    new Chart(document.getElementById('rd-activity-chart'), {
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
            plugins: { legend },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: integerTicks } },
        },
    });
})();
</script>
