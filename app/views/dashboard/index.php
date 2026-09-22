<?php
$user = $user ?? [];
$dashboardSections = $dashboardSections ?? [];
$splitDashboard = (bool) ($splitDashboard ?? false);
$globalMessage = (string) ($globalMessage ?? 'Please check this page regularly for the latest dashboard announcements and updates.');

$pageTitle = 'Dashboard — RIDE IMS';
$pageHeading = 'Dashboard';
$pageSubtitle = 'Welcome back, ' . trim($user['first_name'] . ' ' . $user['last_name'])
    . ($user['college_name'] ? ' · ' . $user['college_name'] : '')
    . ' · Research & Extension insights';

$statusLabels = [
    'draft' => 'Draft',
    'submitted' => 'Submitted',
    'under_review' => 'Under Review',
    'returned' => 'Returned',
    'approved' => 'Approved',
    'ongoing' => 'Ongoing',
    'completed' => 'Completed',
    'suspended' => 'Suspended',
];
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
  .graph-dashboard * { margin: 0; padding: 0; box-sizing: border-box; }
  .graph-dashboard {
    background: #f0f2f8;
    font-family: 'Inter', sans-serif;
    padding: 22px;
    color: #1a2634;
    border-radius: 20px;
  }
  .graph-dashboard .dashboard-analytics-section + .dashboard-analytics-section {
    margin-top: 28px;
    padding-top: 28px;
    border-top: 2px solid #dde5f0;
  }
  .graph-dashboard .header { margin-bottom: 24px; }
  .graph-dashboard .header h2 {
    font-size: 1.35rem;
    font-weight: 600;
    background: linear-gradient(135deg, #1E3A6F, var(--section-accent, #2B5A8C));
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    letter-spacing: -0.3px;
    display: inline-flex;
    align-items: center;
    gap: 12px;
  }
  .graph-dashboard .header h2 i { color: var(--section-accent, #2B5A8C); font-size: 1.25rem; }
  .graph-dashboard .sub {
    color: #5b6e8c;
    margin-top: 8px;
    font-weight: 400;
    border-left: 3px solid var(--section-accent, #2B5A8C);
    padding-left: 14px;
    font-size: 0.9rem;
  }
  .graph-dashboard .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
  .graph-dashboard .card {
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.02), 0 2px 6px rgba(0,0,0,0.05);
    padding: 1.1rem 1.1rem 1.3rem;
    transition: all 0.2s ease;
    border: 1px solid #eef2f8;
  }
  .graph-dashboard .card:hover { transform: translateY(-3px); box-shadow: 0 20px 28px -12px rgba(0,32,64,0.12); border-color: #cddfe7; }
  .graph-dashboard .card-header {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 1rem; border-bottom: 2px solid #f0f2f7; padding-bottom: 0.6rem;
  }
  .graph-dashboard .card-header h3 { font-size: 0.95rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #4f6f8f; }
  .graph-dashboard .card-header i { font-size: 1.2rem; color: #6c8db0; }
  .graph-dashboard .stat-row { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 12px; }
  .graph-dashboard .stat-badge { font-size: 2rem; font-weight: 700; color: #1E3A6F; }
  .graph-dashboard .label-sm { font-size: .75rem; color: #6f8eae; font-weight: 500; }
  .graph-dashboard .workflow-stages { display: flex; flex-wrap: wrap; justify-content: space-between; margin-top: 8px; gap: 8px; }
  .graph-dashboard .stage-item { text-align: center; flex: 1; min-width: 62px; }
  .graph-dashboard .stage-count { font-size: 1.3rem; font-weight: 700; color: #2c3e66; }
  .graph-dashboard .stage-label { font-size: .7rem; font-weight: 500; color: #7d8eaa; text-transform: capitalize; }
  .graph-dashboard .bottleneck-pending {
    background: #fff0e0; border-radius: 60px; padding: 8px 12px; display: flex;
    align-items: center; justify-content: space-between; margin-top: 14px; gap: 8px;
  }
  .graph-dashboard .pending-text { font-weight: 600; font-size: .85rem; color: #b85c00; }
  .graph-dashboard .chart-container {
    background: #fff; border-radius: 24px; box-shadow: 0 8px 18px rgba(0,0,0,.02);
    border: 1px solid #eef2f8; padding: 1rem; margin-bottom: 24px;
  }
  .graph-dashboard .chart-title {
    font-weight: 600; margin-bottom: 1rem; padding-left: 6px; font-size: 1rem;
    color: #2c4b6e; display: flex; align-items: center; gap: 8px;
  }
  .graph-dashboard .recent-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
  .graph-dashboard .recent-table th {
    text-align: left; padding: 10px 4px 6px 0; font-weight: 600;
    color: #56759b; border-bottom: 1px solid #e9edf2;
  }
  .graph-dashboard .recent-table td {
    padding: 10px 4px 10px 0; border-bottom: 1px solid #f0f3f8; color: #1f2f40; font-weight: 500;
  }
  .graph-dashboard .status-badge {
    font-size: .7rem; font-weight: 600; padding: 4px 8px; border-radius: 40px;
    display: inline-block; background: #eef2fa; color: #2c6280;
  }
  .graph-dashboard .status-returned { background: #ffe8e0; color: #c45c2c; }
  .graph-dashboard .status-ongoing { background: #e0f0ea; color: #1f7840; }
  .graph-dashboard .status-under-review { background: #e3eef9; color: #2a6d9c; }
  .graph-dashboard .status-approved { background: #dff0e6; color: #257545; }
  .graph-dashboard .view-link { text-align: right; margin-top: 14px; font-size: .75rem; }
  .graph-dashboard .view-link a { color: #2b6a9f; text-decoration: none; font-weight: 500; }
  .graph-dashboard .view-link a:hover { text-decoration: underline; }
  .graph-dashboard .flex-between { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
  .graph-dashboard .double-chart-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
  .graph-dashboard canvas { max-width: 100%; height: auto; }
  .graph-dashboard .status-badge i { margin-right: 4px; font-size: .7rem; }
  @media (max-width: 1180px) { .graph-dashboard .grid { grid-template-columns: repeat(2,1fr); } .graph-dashboard .double-chart-grid { grid-template-columns: 1fr; } }
  @media (max-width: 700px) { .graph-dashboard { padding: 14px; } .graph-dashboard .grid { grid-template-columns: 1fr; } }
  .dashboard-tab-bar { display: flex; gap: 8px; margin-bottom: 16px; }
  .dashboard-tab-btn {
    border: none; cursor: pointer; padding: 10px 18px; border-radius: 40px; font-weight: 600;
    font-size: 0.85rem; background: #fff; color: #4f6f8f; box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    display: inline-flex; align-items: center; gap: 8px;
  }
  .dashboard-tab-btn.active { background: var(--tab-accent, #2B5A8C); color: #fff; }
  .dashboard-panel[hidden] { display: none; }
</style>

<div class="graph-dashboard">
  <?php require APP_PATH . '/views/dashboard/_coauthor-invitations.php'; ?>

  <?php if (!empty($facultyReportingNotice)): ?>
  <div class="chart-container" style="padding: 0.9rem 1rem; margin-bottom: 16px; border-left: 4px solid #1f7840;">
    <div style="display:flex; gap:10px; align-items:flex-start; color:#1f3854; font-size:0.9rem;">
      <i class="fas fa-calendar-check" style="margin-top:2px;"></i>
      <div>
        <strong>Quarterly reporting schedule:</strong>
        <?php if (!($facultyReportingNotice['submissionOpen'] ?? true)): ?>
          Submissions open on <?= htmlspecialchars(\App\Support\FacultyFormDeadlines::submissionOpenDateText()) ?>.
        <?php elseif (($facultyReportingNotice['scheduleNotice'] ?? '') !== ''): ?>
          <?= htmlspecialchars($facultyReportingNotice['scheduleNotice']) ?>
        <?php else: ?>
          See Form Deadlines in settings for the current schedule.
        <?php endif; ?>
        <?php if ($facultyReportingNotice['currentPeriodLabel'] !== ''): ?>
          Current reporting period: <?= htmlspecialchars($facultyReportingNotice['currentPeriodLabel']) ?>.
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div id="global-account-message" class="chart-container" style="padding: 0.9rem 1rem; margin-bottom: 16px; border-left: 4px solid #2B5A8C;">
    <div style="display:flex; gap:10px; align-items:flex-start; color:#1f3854; font-size:0.9rem;">
      <i class="fas fa-bullhorn" style="margin-top:2px;"></i>
      <div>
        <strong>Message for all accounts:</strong>
        <?= nl2br(htmlspecialchars($globalMessage)) ?>
      </div>
    </div>
  </div>

  <?php if ($splitDashboard && count($dashboardSections) > 1): ?>
    <div class="dashboard-tab-bar" role="tablist">
      <?php foreach (array_values($dashboardSections) as $tabIndex => $section): ?>
        <button
          type="button"
          class="dashboard-tab-btn<?= $tabIndex === 0 ? ' active' : '' ?>"
          style="--tab-accent: <?= htmlspecialchars((string) ($section['accent'] ?? '#2B5A8C')) ?>;"
          data-dashboard-tab-btn="<?= htmlspecialchars((string) ($section['key'] ?? '')) ?>"
        >
          <i class="fas <?= htmlspecialchars((string) ($section['icon'] ?? 'fa-chart-line')) ?>"></i>
          <?= htmlspecialchars((string) ($section['title'] ?? '')) ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php foreach ($dashboardSections as $panelIndex => $section): ?>
    <div class="dashboard-panel" data-dashboard-panel="<?= htmlspecialchars((string) ($section['key'] ?? '')) ?>"<?= ($splitDashboard && count($dashboardSections) > 1 && $panelIndex !== array_key_first($dashboardSections)) ? ' hidden' : '' ?>>
      <?php require APP_PATH . '/views/dashboard/_analytics-section.php'; ?>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($splitDashboard && count($dashboardSections) > 1): ?>
<script>
  (function () {
    var buttons = document.querySelectorAll('[data-dashboard-tab-btn]');
    var panels = document.querySelectorAll('[data-dashboard-panel]');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-dashboard-tab-btn');
        buttons.forEach(function (b) { b.classList.toggle('active', b === btn); });
        panels.forEach(function (p) { p.hidden = p.getAttribute('data-dashboard-panel') !== target; });
        window.dispatchEvent(new Event('resize'));
      });
    });
  })();
</script>
<?php endif; ?>
