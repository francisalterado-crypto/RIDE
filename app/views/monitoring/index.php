<?php
/** @var array $user */
/** @var string $roleTitle */
/** @var array $stats */
/** @var list<array> $submissions */
/** @var list<array> $pendingAction */
/** @var list<array> $submitters */
/** @var bool $isVpride */
/** @var bool $isCoordinatorResearch */
/** @var bool $isCoordinatorExtension */
/** @var bool $isDean */
/** @var bool $isDirectorResearch */
/** @var bool $isDirectorExtension */
/** @var string|null $scopeProjectType */
use App\Support\MonitoringRoles;

$scopeProjectType = $scopeProjectType ?? MonitoringRoles::proposalScopeType();
$universityWide = MonitoringRoles::isUniversityWide();
$showResearch = $scopeProjectType === null || $scopeProjectType === 'research';
$showExtension = $scopeProjectType === null || $scopeProjectType === 'extension';
$canSwitchTrack = $isVpride || ($isDean && MonitoringRoles::proposalScopeType() === null);

$myStep = match (true) {
    $isCoordinatorResearch => MonitoringRoles::COORDINATOR_RESEARCH,
    $isCoordinatorExtension => MonitoringRoles::COORDINATOR_EXTENSION,
    $isDean => MonitoringRoles::DEAN,
    $isDirectorResearch => MonitoringRoles::DIRECTOR_RESEARCH,
    $isDirectorExtension => MonitoringRoles::DIRECTOR_EXTENSION,
    $isVpride => MonitoringRoles::VPRIDE,
    default => '',
};
$actionWord = in_array($myStep, [MonitoringRoles::COORDINATOR_RESEARCH, MonitoringRoles::COORDINATOR_EXTENSION], true) ? 'endorsement' : 'approval';

// Forms each track files, so the filter only lists forms that can appear on this page.
$researchForms = [
    'manuscript', 'completed_researches', 'ongoing_researches', 'research_output_published', 'research_output_presented',
    'commercialized', 'resulted_in_extension', 'journal_citation', 'book_citation', 'inventions_um_copyrights', 'linkages',
    'consolidated_completed_researches', 'consolidated_ongoing_researches', 'consolidated_research_output_published',
    'consolidated_research_output_presented', 'consolidated_commercialized', 'consolidated_resulted_in_extension',
    'consolidated_journal_citation', 'consolidated_book_citation', 'consolidated_inventions_um_copyrights', 'consolidated_linkages',
    'progress_report', 'terminal_report', 'terminal_report_assessment_form', 'obr_matrix',
];
$extensionForms = [
    'extension_program_proposal', 'trainings_conducted', 'technical_advisory', 'extension_linkages',
    'outreach_activities', 'technology_adoption', 'ebalwasyon_ng_gawain',
];
$filterForms = array_merge($showResearch ? $researchForms : [], $showExtension ? $extensionForms : []);
$formFilterLabel = static fn (string $formType): string => proposal_form_label(['summary' => json_encode(['form_type' => $formType])]);

$selectedFormType = strtolower(trim((string) ($_GET['form_type'] ?? '')));
if (!in_array($selectedFormType, $filterForms, true)) {
    $selectedFormType = '';
}
$selectedStatus = strtolower(trim((string) ($_GET['status'] ?? '')));
$statusOptions = ['submitted', 'under_review', 'returned', 'approved', 'ongoing', 'completed', 'suspended', 'draft'];
if (!in_array($selectedStatus, $statusOptions, true)) {
    $selectedStatus = '';
}
$matchesForm = static function (array $proposal) use ($selectedFormType): bool {
    if ($selectedFormType === '') {
        return true;
    }
    $formType = proposal_form_type($proposal);
    if ($selectedFormType === 'extension_program_proposal') {
        return proposal_is_wpu_funded_extension($proposal);
    }

    return $formType === $selectedFormType;
};
$filteredPendingAction = array_values(array_filter($pendingAction, $matchesForm));
$filteredSubmissions = array_values(array_filter($submissions, static fn (array $p): bool => $matchesForm($p)
    && ($selectedStatus === '' || (string) $p['status'] === $selectedStatus)));

$byStatus = $stats['by_status'] ?? [];
$pendingByStep = $stats['pending_by_step'] ?? [];
$countStatus = static fn (array $statuses): int => array_sum(array_map(static fn (string $s): int => (int) ($byStatus[$s] ?? 0), $statuses));

$scopeLabel = match ($scopeProjectType) {
    'research' => 'Research',
    'extension' => 'Extension',
    default => 'Research and Extension',
};
$pageTitle = 'Monitoring — RIDE IMS';
$pageHeading = 'Monitoring';
$pageSubtitle = $roleTitle . ', '
    . ($universityWide ? 'all colleges' : (string) ($user['college_name'] ?? 'your college'))
    . ', ' . $scopeLabel . '.';

$e = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''));
$daysWaiting = static function (?string $since): string {
    $time = $since ? strtotime($since) : false;
    if (!$time) {
        return '';
    }
    $days = (int) floor((time() - $time) / 86400);

    return $days < 1 ? 'Today' : $days . ' day' . ($days === 1 ? '' : 's');
};
$formatDate = static function (?string $date): string {
    $time = $date ? strtotime($date) : false;

    return $time ? date('M j, Y', $time) : '';
};
$baseQuery = static function (array $overrides = []) use ($scopeProjectType, $selectedFormType, $selectedStatus): string {
    $params = array_filter(array_merge([
        'scope' => $scopeProjectType,
        'form_type' => $selectedFormType,
        'status' => $selectedStatus,
    ], $overrides), static fn ($value): bool => $value !== null && $value !== '');

    return base_url('monitoring' . ($params !== [] ? '?' . http_build_query($params) : ''));
};

$tracks = [];
if ($showResearch) {
    $tracks['Research'] = [MonitoringRoles::COORDINATOR_RESEARCH, MonitoringRoles::DEAN, MonitoringRoles::DIRECTOR_RESEARCH, MonitoringRoles::VPRIDE];
}
if ($showExtension) {
    $tracks['Extension'] = [MonitoringRoles::COORDINATOR_EXTENSION, MonitoringRoles::DEAN, MonitoringRoles::DIRECTOR_EXTENSION, MonitoringRoles::VPRIDE];
}
$stepNames = [
    MonitoringRoles::COORDINATOR_RESEARCH => 'Research Coordinator',
    MonitoringRoles::COORDINATOR_EXTENSION => 'Field Extensionist',
    MonitoringRoles::DEAN => 'College Dean',
    MonitoringRoles::DIRECTOR_RESEARCH => 'RDO Director',
    MonitoringRoles::DIRECTOR_EXTENSION => 'ESO Director',
    MonitoringRoles::VPRIDE => 'VPRIDE',
];
?>

<div class="mon">
    <?php if ($canSwitchTrack): ?>
        <nav class="mon-tabs" aria-label="Track">
            <?php if (!$isVpride): ?>
                <a class="<?= $scopeProjectType === null ? 'is-active' : '' ?>" href="<?= base_url('monitoring') ?>">All</a>
            <?php endif; ?>
            <a class="<?= $scopeProjectType === 'research' ? 'is-active' : '' ?>" href="<?= base_url('monitoring?scope=research') ?>">Research and Development</a>
            <a class="<?= $scopeProjectType === 'extension' ? 'is-active' : '' ?>" href="<?= base_url('monitoring?scope=extension') ?>">Extension Services</a>
        </nav>
    <?php endif; ?>

    <div class="mon-flows">
        <?php foreach ($tracks as $trackName => $steps): ?>
            <section class="mon-flow">
                <h2><?= $e($trackName) ?> approval flow</h2>
                <ol class="mon-steps">
                    <li class="mon-step mon-step--start">
                        <span class="mon-step-count"><?= $countStatus(['draft']) ?></span>
                        <span class="mon-step-name">Drafts</span>
                    </li>
                    <?php foreach ($steps as $step): ?>
                        <li class="mon-step<?= $step === $myStep ? ' is-mine' : '' ?>">
                            <span class="mon-step-count"><?= (int) ($pendingByStep[$step] ?? 0) ?></span>
                            <span class="mon-step-name"><?= $e($stepNames[$step]) ?></span>
                            <?php if ($step === $myStep): ?><span class="mon-step-you">You</span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="fd-stats mon-stats">
        <a class="fd-stat<?= count($pendingAction) > 0 ? ' fd-stat--alert' : '' ?>" href="#mon-queue">
            <span class="fd-stat-value"><?= count($pendingAction) ?></span>
            <span class="fd-stat-label">Waiting for your <?= $actionWord ?></span>
        </a>
        <a class="fd-stat" href="<?= $e($baseQuery(['status' => 'submitted'])) ?>#mon-all">
            <span class="fd-stat-value"><?= $countStatus(['submitted', 'under_review']) ?></span>
            <span class="fd-stat-label">In review</span>
        </a>
        <a class="fd-stat" href="<?= $e($baseQuery(['status' => 'returned'])) ?>#mon-all">
            <span class="fd-stat-value"><?= $countStatus(['returned']) ?></span>
            <span class="fd-stat-label">Returned for revision</span>
        </a>
        <a class="fd-stat" href="<?= $e($baseQuery(['status' => 'ongoing'])) ?>#mon-all">
            <span class="fd-stat-value"><?= $countStatus(['ongoing', 'approved']) ?></span>
            <span class="fd-stat-label">Approved and ongoing</span>
        </a>
        <a class="fd-stat" href="<?= $e($baseQuery(['status' => 'completed'])) ?>#mon-all">
            <span class="fd-stat-value"><?= $countStatus(['completed']) ?></span>
            <span class="fd-stat-label">Completed</span>
        </a>
    </div>

    <section class="card list-card" id="mon-queue">
        <div class="list-card-head">
            <div>
                <h2>Waiting for your <?= $actionWord ?></h2>
                <p>Oldest first. Open a proposal to review, <?= $actionWord === 'endorsement' ? 'endorse' : 'approve' ?>, or return it.</p>
            </div>
        </div>
        <?php if ($filteredPendingAction === []): ?>
            <p class="fd-empty">Nothing is waiting for you<?= $selectedFormType !== '' ? ' in this form' : '' ?>.</p>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Form</th>
                            <th>Submitted by</th>
                            <?php if ($universityWide): ?><th>College</th><?php endif; ?>
                            <th>Waiting</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filteredPendingAction as $p): ?>
                        <tr>
                            <td><a href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a></td>
                            <td><?= $e(proposal_form_label($p)) ?></td>
                            <td><?= $e(trim($p['first_name'] . ' ' . $p['last_name'])) ?></td>
                            <?php if ($universityWide): ?><td><?= $e($p['college_name']) ?></td><?php endif; ?>
                            <td><span class="mon-waiting"><?= $e($daysWaiting($p['updated_at'] ?? null)) ?></span></td>
                            <td class="mon-action"><a class="btn btn-sm btn-accent" href="<?= base_url('proposals/' . (int) $p['id']) ?>">Review</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="card list-card" id="mon-all">
        <div class="list-card-head">
            <div>
                <h2>All submissions</h2>
                <p id="mon-count"><?= count($filteredSubmissions) ?> record<?= count($filteredSubmissions) === 1 ? '' : 's' ?></p>
            </div>
            <form method="get" class="mon-filters" action="<?= base_url('monitoring') ?>">
                <?php if ($scopeProjectType !== null): ?><input type="hidden" name="scope" value="<?= $e($scopeProjectType) ?>"><?php endif; ?>
                <input type="search" id="mon-search" class="no-capitalize" placeholder="Search title, code, name" aria-label="Search submissions">
                <select name="form_type" aria-label="Filter by form" onchange="this.form.submit()">
                    <option value="">All forms</option>
                    <?php foreach ($filterForms as $formKey): ?>
                        <option value="<?= $e($formKey) ?>"<?= $selectedFormType === $formKey ? ' selected' : '' ?>><?= $e($formFilterLabel($formKey)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status" aria-label="Filter by status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <?php foreach ($statusOptions as $status): ?>
                        <option value="<?= $e($status) ?>"<?= $selectedStatus === $status ? ' selected' : '' ?>><?= $e(status_label($status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selectedFormType !== '' || $selectedStatus !== ''): ?>
                    <a class="btn btn-sm btn-outline" href="<?= $e($baseQuery(['form_type' => '', 'status' => ''])) ?>">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($filteredSubmissions === []): ?>
            <p class="fd-empty">No submissions match<?= $universityWide ? '' : ' in your college' ?>.</p>
        <?php else: ?>
            <div class="data-table-wrap">
                <table class="data-table" id="mon-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Title</th>
                            <th>Form</th>
                            <th>Submitted by</th>
                            <?php if ($universityWide): ?><th>College</th><?php endif; ?>
                            <th>Status</th>
                            <th>Waiting on</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filteredSubmissions as $p): ?>
                        <?php
                        $summary = json_decode((string) ($p['summary'] ?? ''), true);
                        $reportAsOf = is_array($summary) ? trim((string) ($summary['report_as_of'] ?? '')) : '';
                        $lead = trim($p['first_name'] . ' ' . $p['last_name']);
                        $step = (string) ($p['current_step'] ?? '');
                        ?>
                        <tr data-search="<?= $e(strtolower(implode(' ', [(string) ($p['project_code'] ?? ''), (string) $p['title'], $lead, (string) ($p['college_name'] ?? '')]))) ?>">
                            <td><?php if (!empty($p['project_code'])): ?><code class="proposal-registry-code"><?= $e($p['project_code']) ?></code><?php endif; ?></td>
                            <td><a href="<?= base_url('proposals/' . (int) $p['id']) ?>"><?= $e($p['title']) ?></a></td>
                            <td>
                                <?= $e(proposal_form_label($p)) ?>
                                <?php if ($reportAsOf !== ''): ?><div class="data-table-muted">As of <?= $e($reportAsOf) ?></div><?php endif; ?>
                            </td>
                            <td><?= $e($lead) ?></td>
                            <?php if ($universityWide): ?><td><?= $e($p['college_name']) ?></td><?php endif; ?>
                            <td><span class="badge badge-<?= $e($p['status']) ?>"><?= $e(status_label((string) $p['status'])) ?></span></td>
                            <td class="<?= $step === '' ? 'data-table-muted' : '' ?>">
                                <?php if ($step !== ''): ?>
                                    <?= $e($stepNames[$step] ?? MonitoringRoles::stepLabel($step)) ?><?= $step === $myStep ? ' (you)' : '' ?>
                                <?php else: ?>
                                    <?= $e(match ((string) $p['status']) { 'draft' => 'Not submitted', 'returned' => 'Proponent', default => 'No one' }) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <?php if ($isVpride && !empty($submitters)): ?>
        <section class="card list-card">
            <div class="list-card-head">
                <div>
                    <h2>Submitters</h2>
                    <p>Faculty and extension workers with <?= $e(strtolower($scopeLabel)) ?> submissions, university-wide.</p>
                </div>
            </div>
            <div class="data-table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>College</th>
                            <th>Total</th>
                            <th>Active</th>
                            <th>Approved</th>
                            <th>Last activity</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($submitters as $s): ?>
                        <tr>
                            <td>
                                <?= $e(trim($s['first_name'] . ' ' . $s['last_name'])) ?>
                                <div class="data-table-muted"><?= $e($s['email']) ?></div>
                            </td>
                            <td><?= $e($s['college_name'] ?? '') ?></td>
                            <td><?= (int) $s['total_submissions'] ?></td>
                            <td><?= (int) $s['active_submissions'] ?></td>
                            <td><?= (int) $s['approved_submissions'] ?></td>
                            <td><?= $e($formatDate($s['last_activity'] ?? null)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
(() => {
    const search = document.getElementById('mon-search');
    const table = document.getElementById('mon-table');
    const count = document.getElementById('mon-count');
    if (!search || !table) {
        return;
    }
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    search.addEventListener('input', () => {
        const query = search.value.trim().toLowerCase();
        let shown = 0;
        rows.forEach((row) => {
            const match = query === '' || (row.dataset.search || '').includes(query);
            row.hidden = !match;
            shown += match ? 1 : 0;
        });
        if (count) {
            count.textContent = `${shown} record${shown === 1 ? '' : 's'}`;
        }
    });
    search.form?.addEventListener('submit', (event) => {
        if (document.activeElement === search) {
            event.preventDefault();
        }
    });
})();
</script>
