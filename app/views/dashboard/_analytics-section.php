<?php
/** @var array<string, mixed> $section */
/** @var array<string, string> $statusLabels */

$sectionKey = (string) ($section['key'] ?? 'all');
$sectionTitle = (string) ($section['title'] ?? 'Monitoring System');
$sectionIcon = (string) ($section['icon'] ?? 'fa-chart-line');
$sectionAccent = (string) ($section['accent'] ?? '#2B5A8C');
$stats = is_array($section['stats'] ?? null) ? $section['stats'] : ['by_status' => []];
$proposals = is_array($section['proposals'] ?? null) ? $section['proposals'] : [];
$ongoingCount = (int) ($section['ongoingCount'] ?? 0);
$monthlyActivity = is_array($section['monthlyActivity'] ?? null) ? $section['monthlyActivity'] : ['labels' => [], 'submitted' => [], 'approved' => []];
$trendLabels = is_array($monthlyActivity['labels'] ?? null) ? $monthlyActivity['labels'] : [];
$trendSubmitted = is_array($monthlyActivity['submitted'] ?? null) ? $monthlyActivity['submitted'] : [];
$trendApproved = is_array($monthlyActivity['approved'] ?? null) ? $monthlyActivity['approved'] : [];

$statusCounts = [];
foreach ($statusLabels as $key => $label) {
    $statusCounts[$key] = (int) ($stats['by_status'][$key] ?? 0);
}

$totalStatusCount = array_sum($statusCounts);
$funnelPercentages = [];
foreach ($statusCounts as $key => $count) {
    $funnelPercentages[$key] = $totalStatusCount > 0 ? round(($count / $totalStatusCount) * 100, 2) : 0;
}

$overdueMilestoneCount = (int) ($stats['overdue_milestones'] ?? 0);
$overdueReportCount = (int) ($stats['overdue_reports'] ?? 0);
$bottleneckCount = 0;
$bottleneckLabel = $sectionKey === 'extension' ? 'Director Extension' : 'Director Research';
if (!empty($stats['bottleneck'])) {
    $bottleneckCount = (int) ($stats['bottleneck'][0]['cnt'] ?? 0);
    $bottleneckLabel = ucwords(str_replace('_', ' ', (string) ($stats['bottleneck'][0]['current_step'] ?? $bottleneckLabel)));
}

$chartPrefix = 'dash-' . preg_replace('/[^a-z0-9_-]+/i', '-', $sectionKey);
?>

<section class="dashboard-analytics-section" style="--section-accent: <?= htmlspecialchars($sectionAccent) ?>;">
  <div class="header">
    <h2><i class="fas <?= htmlspecialchars($sectionIcon) ?>"></i> <?= htmlspecialchars($sectionTitle) ?></h2>
    <div class="sub">Graph-driven insights | Workflow analytics | Real-time tracking</div>
  </div>

  <div class="grid">
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-diagram-project"></i> Workflow Status</h3>
        <i class="fas fa-chart-simple"></i>
      </div>
      <div class="workflow-stages">
        <?php foreach ($statusLabels as $statusKey => $statusLabel): ?>
          <div class="stage-item">
            <div class="stage-count"><?= $statusCounts[$statusKey] ?></div>
            <div class="stage-label"><?= htmlspecialchars($statusLabel) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="margin-top: 16px;">
        <div style="font-size:.7rem; color:#7089a8; margin-bottom: 6px;">Proposal funnel efficiency</div>
        <div style="height: 6px; background: #e2e9f0; border-radius: 8px; overflow: hidden; display: flex;">
          <div style="width: <?= $funnelPercentages['draft'] ?>%; background:#bdc4d0;"></div>
          <div style="width: <?= $funnelPercentages['submitted'] ?>%; background:#8aa9c9;"></div>
          <div style="width: <?= $funnelPercentages['under_review'] ?>%; background:#ffb347;"></div>
          <div style="width: <?= $funnelPercentages['returned'] ?>%; background:#e67e5a;"></div>
          <div style="width: <?= $funnelPercentages['approved'] ?>%; background:#2ecc71;"></div>
          <div style="width: <?= $funnelPercentages['ongoing'] ?>%; background:#3498db;"></div>
          <div style="width: <?= $funnelPercentages['completed'] ?>%; background:#27ae60;"></div>
          <div style="width: <?= $funnelPercentages['suspended'] ?>%; background:#95a5a6;"></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-hourglass-half"></i> Bottlenecks</h3>
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <div class="stat-row">
        <div><span class="stat-badge"><?= $bottleneckCount ?></span> <span class="label-sm">pending approval</span></div>
      </div>
      <div class="bottleneck-pending">
        <span><i class="fas fa-user-check"></i> <?= htmlspecialchars($bottleneckLabel) ?></span>
        <span class="pending-text"><?= $bottleneckCount ?> pending review</span>
      </div>
      <div style="margin-top: 12px;">
        <canvas id="<?= htmlspecialchars($chartPrefix) ?>-bottleneckDonut" width="100" height="70" style="max-height: 70px; width: 100%;"></canvas>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-play-circle"></i> Active Tracking</h3>
        <i class="fas fa-chart-line"></i>
      </div>
      <div class="flex-between" style="margin-bottom: 12px;">
        <div><span class="stat-badge" style="font-size:2rem;"><?= $ongoingCount ?></span> <span class="label-sm">Ongoing Projects</span></div>
        <div class="view-link" style="margin-top: 0;"><a href="<?= base_url('projects') ?>">View all</a></div>
      </div>
      <div style="background:#f8fafd; border-radius: 20px; padding: 12px; margin: 12px 0;">
        <div class="flex-between"><span><i class="fas fa-calendar-exclamation"></i> Overdue Milestones</span> <strong><?= $overdueMilestoneCount ?></strong></div>
        <div class="flex-between" style="margin-top: 6px;"><span><i class="fas fa-file-alt"></i> Overdue Report Drafts</span> <strong><?= $overdueReportCount ?></strong></div>
      </div>
      <div class="view-link" style="margin-top: 0;"><a href="<?= base_url('projects') ?>">Review deadlines</a></div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-chart-pie"></i> Project Status Mix</h3>
        <i class="fas fa-chart-column"></i>
      </div>
      <canvas id="<?= htmlspecialchars($chartPrefix) ?>-statusPieChart" width="180" height="130" style="max-height: 130px; width: 100%;"></canvas>
      <div class="flex-between" style="margin-top: 12px; font-size: .75rem; flex-wrap: wrap;">
        <span>Ongoing:<?= $statusCounts['ongoing'] ?></span>
        <span>Suspended:<?= $statusCounts['suspended'] ?></span>
        <span>Under Review:<?= $statusCounts['under_review'] ?></span>
        <span>Returned:<?= $statusCounts['returned'] ?></span>
      </div>
    </div>
  </div>

  <div class="double-chart-grid">
    <div class="chart-container">
      <div class="chart-title"><i class="fas fa-chart-line"></i> Monthly Workflow Activity</div>
      <canvas id="<?= htmlspecialchars($chartPrefix) ?>-trendChart" height="160" style="width:100%; max-height:200px;"></canvas>
    </div>
    <div class="chart-container">
      <div class="chart-title"><i class="fas fa-chart-bar"></i> Proposals by Status (Graph)</div>
      <canvas id="<?= htmlspecialchars($chartPrefix) ?>-proposalsBarChart" height="160" style="width:100%; max-height:200px;"></canvas>
    </div>
  </div>

  <div class="chart-container" style="padding: 1.2rem;">
    <div class="flex-between" style="margin-bottom: 12px;">
      <div class="chart-title" style="margin-bottom:0;"><i class="fas fa-table-list"></i> Recent Proposals</div>
      <div class="view-link" style="margin-top: 0;"><a href="<?= base_url('proposals') ?>">View all proposals</a></div>
    </div>
    <table class="recent-table">
      <thead>
        <tr><th>Title</th><th>Status</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php if (empty($proposals)): ?>
          <tr><td colspan="3">No proposals yet.</td></tr>
        <?php else: ?>
          <?php foreach ($proposals as $proposal): ?>
            <?php
            $status = (string) ($proposal['status'] ?? 'draft');
            $dateRaw = (string) ($proposal['updated_at'] ?? $proposal['created_at'] ?? '');
            $dateLabel = $dateRaw !== '' ? date('M j, Y', strtotime($dateRaw)) : '-';
            $statusClass = match ($status) {
                'returned' => 'status-returned',
                'under_review' => 'status-under-review',
                'approved', 'ongoing', 'completed' => 'status-approved',
                default => 'status-ongoing',
            };
            $statusIcon = match ($status) {
                'returned' => 'fa-undo-alt',
                'under_review' => 'fa-eye',
                'approved' => 'fa-circle-check',
                'ongoing' => 'fa-spinner',
                'completed' => 'fa-flag-checkered',
                default => 'fa-circle',
            };
            ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars((string) ($proposal['title'] ?? 'Untitled')) ?></strong>
                <?php if (($proposal['membership'] ?? 'lead') === 'coauthor'): ?>
                  <span class="status-badge status-under-review" style="margin-left:0.35rem; font-size:0.65rem;">Co-author</span>
                <?php endif; ?>
                <br>
                <span style="font-size:0.7rem; color:#6d7f95;">Proposal #<?= (int) ($proposal['id'] ?? 0) ?></span>
              </td>
              <td>
                <span class="status-badge <?= $statusClass ?>">
                  <i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars(status_label($status)) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($dateLabel) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <div class="view-link" style="margin-top: 12px; text-align: center;"><a href="<?= base_url('proposals') ?>"><i class="fas fa-chevron-circle-right"></i> Browse complete list</a></div>
  </div>
</section>

<script>
  (function () {
    const statusData = <?= json_encode([
      'draft' => $statusCounts['draft'],
      'submitted' => $statusCounts['submitted'],
      'under_review' => $statusCounts['under_review'],
      'returned' => $statusCounts['returned'],
      'approved' => $statusCounts['approved'],
      'ongoing' => $statusCounts['ongoing'],
      'completed' => $statusCounts['completed'],
      'suspended' => $statusCounts['suspended'],
    ], JSON_UNESCAPED_SLASHES) ?>;
    const trendLabels = <?= json_encode($trendLabels, JSON_UNESCAPED_SLASHES) ?>;
    const trendSubmitted = <?= json_encode($trendSubmitted, JSON_UNESCAPED_SLASHES) ?>;
    const trendApproved = <?= json_encode($trendApproved, JSON_UNESCAPED_SLASHES) ?>;
    const sectionAccent = <?= json_encode($sectionAccent, JSON_UNESCAPED_SLASHES) ?>;
    const totalItems = Object.values(statusData).reduce((sum, value) => sum + value, 0);
    const pendingApprovals = statusData.under_review + statusData.returned + statusData.submitted;
    const nonPending = Math.max(totalItems - pendingApprovals, 0);
    const prefix = <?= json_encode($chartPrefix, JSON_UNESCAPED_SLASHES) ?>;

    const donutCtx = document.getElementById(prefix + '-bottleneckDonut');
    if (donutCtx) {
      new Chart(donutCtx.getContext('2d'), {
        type: 'doughnut',
        data: {
          labels: ['Pending Approval', 'Approved / Others'],
          datasets: [{ data: [pendingApprovals, nonPending], backgroundColor: ['#E67E5A', '#C4D6E6'], borderWidth: 0, borderRadius: 6 }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          cutout: '60%',
          plugins: { legend: { display: false } },
        },
      });
    }

    const pieCtx = document.getElementById(prefix + '-statusPieChart');
    if (pieCtx) {
      new Chart(pieCtx.getContext('2d'), {
        type: 'pie',
        data: {
          labels: [
            `Ongoing (${statusData.ongoing})`,
            `Suspended (${statusData.suspended})`,
            `Under Review (${statusData.under_review})`,
            `Returned (${statusData.returned})`,
          ],
          datasets: [{
            data: [statusData.ongoing, statusData.suspended, statusData.under_review, statusData.returned],
            backgroundColor: ['#3498db', '#95a5a6', '#f39c12', '#e67e5a'],
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } },
        },
      });
    }

    const trendCtx = document.getElementById(prefix + '-trendChart');
    if (trendCtx) {
      new Chart(trendCtx.getContext('2d'), {
        type: 'line',
        data: {
          labels: trendLabels,
          datasets: [
            { label: 'Proposals Submitted', data: trendSubmitted, borderColor: sectionAccent, backgroundColor: sectionAccent + '0d', tension: 0.3, fill: true, pointBackgroundColor: sectionAccent, pointRadius: 3 },
            { label: 'Approvals Completed', data: trendApproved, borderColor: '#27AE60', borderDash: [5, 5], tension: 0.3, fill: false, pointRadius: 2 },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { position: 'top', labels: { font: { size: 10 } } } },
          scales: { y: { beginAtZero: true, grid: { color: '#eef2f8' } } },
        },
      });
    }

    const barCtx = document.getElementById(prefix + '-proposalsBarChart');
    if (barCtx) {
      new Chart(barCtx.getContext('2d'), {
        type: 'bar',
        data: {
          labels: ['Draft', 'Submitted', 'Under Review', 'Returned', 'Approved', 'Ongoing', 'Completed', 'Suspended'],
          datasets: [{
            label: 'Number of Proposals',
            data: [statusData.draft, statusData.submitted, statusData.under_review, statusData.returned, statusData.approved, statusData.ongoing, statusData.completed, statusData.suspended],
            backgroundColor: sectionAccent,
            borderRadius: 8,
            barPercentage: 0.65,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: { legend: { display: false } },
          scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { ticks: { font: { size: 9 } } } },
        },
      });
    }
  })();
</script>
