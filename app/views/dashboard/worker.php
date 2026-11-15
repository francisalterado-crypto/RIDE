<?php
/**
 * Dashboard for extension workers: their Conducted Activities in the current reporting period, entries that
 * still need supporting documents, and entries per month.
 *
 * @var array<string, mixed> $user
 * @var array<string, array<string, mixed>> $types
 * @var string $period
 * @var array{0: string, 1: string}|null $periodRange
 * @var list<array<string, mixed>> $periodEntries
 * @var array<string, int> $byType
 * @var list<array<string, mixed>> $missingDocs
 * @var list<array<string, mixed>> $recentEntries
 * @var array{labels: list<string>, counts: list<int>} $entriesPerMonth
 */
use App\Support\ExtensionActivityTypes;
use App\Support\QuarterlyReporting;

$pageTitle = 'Dashboard | RIDE Hub';
$pageHeading = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$formatDate = static function (?string $date): string {
    $time = $date ? strtotime($date) : false;

    return $time ? date('M j, Y', $time) : '';
};
$periodName = $period !== '' ? (string) preg_replace('/\s*\(due [^)]*\)$/', '', QuarterlyReporting::periodLabel($period)) : '';
$typeLabel = static fn (string $type): string => (string) ($types[$type]['label'] ?? $type);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="fd">
    <?php require APP_PATH . '/views/dashboard/_notices.php'; ?>

    <div class="fd-actions">
        <?php foreach ($types as $definition): ?>
            <a class="fd-action" href="<?= base_url('activities/new/' . $definition['slug']) ?>">
                <strong>Add <?= $e($definition['label']) ?></strong><span>Conducted Activities</span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php
    $deadlineFormType = (string) array_key_first($types);
    $deadlinePeriod = $period;
    require APP_PATH . '/views/partials/quarterly-deadline-notice.php';
    ?>

    <div class="fd-stats">
        <a class="fd-stat" href="<?= base_url('activities?period=' . rawurlencode($period)) ?>">
            <span class="fd-stat-value"><?= count($periodEntries) ?></span>
            <span class="fd-stat-label">Activities this period<?= $periodName !== '' ? ' (' . $e($periodName) . ')' : '' ?></span>
        </a>
        <a class="fd-stat<?= $missingDocs !== [] ? ' fd-stat--alert' : '' ?>" href="#wd-missing">
            <span class="fd-stat-value"><?= count($missingDocs) ?></span>
            <span class="fd-stat-label">Missing supporting documents</span>
        </a>
        <?php foreach (array_slice($byType, 0, 2, true) as $type => $count): ?>
            <a class="fd-stat" href="<?= base_url('activities?type=' . rawurlencode((string) $types[$type]['slug']) . '&period=' . rawurlencode($period)) ?>">
                <span class="fd-stat-value"><?= (int) $count ?></span>
                <span class="fd-stat-label"><?= $e($typeLabel($type)) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="fd-columns">
        <section class="fd-panel" id="wd-missing">
            <div class="fd-panel-head">
                <h2>Missing supporting documents</h2>
                <a href="<?= base_url('activities?period=' . rawurlencode($period)) ?>">All activities</a>
            </div>
            <?php if ($missingDocs === []): ?>
                <p class="fd-empty">Every activity this period has its required documents attached.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($missingDocs as $entry): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-kind"><?= $e($typeLabel((string) $entry['activity_type'])) ?></span>
                                <span class="fd-tag fd-tag--overdue"><?= (int) $entry['docs_filled'] ?> of <?= (int) $entry['docs_required'] ?> attached</span>
                                <a class="fd-item-title" href="<?= base_url('activities/' . (int) $entry['id']) ?>"><?= $e($entry['title'] !== '' ? $entry['title'] : '(untitled)') ?></a>
                                <p class="fd-item-note"><?= $e($formatDate($entry['activity_date'] ?? null)) ?></p>
                            </div>
                            <a class="btn btn-sm btn-accent" href="<?= base_url('activities/' . (int) $entry['id'] . '/edit') ?>">Attach files</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="fd-panel">
            <div class="fd-panel-head">
                <h2>Recent activities</h2>
                <a href="<?= base_url('activities?period=all') ?>">All dates</a>
            </div>
            <?php if ($recentEntries === []): ?>
                <p class="fd-empty">No activities have been recorded yet. Use the buttons above to add one.</p>
            <?php else: ?>
                <ul class="fd-list">
                    <?php foreach ($recentEntries as $entry): ?>
                        <li class="fd-item">
                            <div class="fd-item-main">
                                <span class="fd-kind"><?= $e($typeLabel((string) $entry['activity_type'])) ?></span>
                                <a class="fd-item-title" href="<?= base_url('activities/' . (int) $entry['id']) ?>"><?= $e($entry['title'] !== '' ? $entry['title'] : '(untitled)') ?></a>
                                <p class="fd-item-note"><?= $e($formatDate($entry['activity_date'] ?? null)) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>

    <div class="fd-columns">
        <section class="fd-panel">
            <div class="fd-panel-head"><h2>My activities, last <?= count($entriesPerMonth['labels']) ?> months</h2></div>
            <div class="fd-chart"><canvas id="wd-month-chart" aria-label="Activities recorded per month"></canvas></div>
        </section>
        <section class="fd-panel">
            <div class="fd-panel-head"><h2>This period by type</h2></div>
            <div class="fd-chart"><canvas id="wd-type-chart" aria-label="Activities this period by type"></canvas></div>
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

    new Chart(document.getElementById('wd-month-chart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($entriesPerMonth['labels'])) ?>,
            datasets: [{ label: 'Activities', data: <?= json_encode(array_values($entriesPerMonth['counts'])) ?>, backgroundColor: '#1f7840', borderRadius: 4 }],
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid, ticks: integerTicks } },
        },
    });

    new Chart(document.getElementById('wd-type-chart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values(array_map($typeLabel, array_keys($byType)))) ?>,
            datasets: [{ label: 'Activities', data: <?= json_encode(array_values($byType)) ?>, backgroundColor: '#2dd4bf', borderRadius: 4 }],
        },
        options: {
            indexAxis: 'y',
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid, ticks: integerTicks }, y: { grid: { display: false } } },
        },
    });
})();
</script>
