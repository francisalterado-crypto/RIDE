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
$showResearch = $scopeProjectType === null || $scopeProjectType === 'research';
$showExtension = $scopeProjectType === null || $scopeProjectType === 'extension';
$formTypeLabels = [
    'manuscript' => 'Proposal Form',
    'completed_researches' => 'Completed Researches',
    'ongoing_researches' => 'Ongoing Researches',
    'research_output_published' => 'Research Output Published',
    'research_output_presented' => 'Research Output Presented',
    'commercialized' => 'Commercialized',
    'resulted_in_extension' => 'Resulted in Extension',
    'journal_citation' => 'Journal Citation',
    'book_citation' => 'Book Citation',
    'inventions_um_copyrights' => 'Inventions / UM / Copyrights',
    'linkages' => 'Linkages',
    'consolidated_linkages' => 'Consolidated Linkages',
    'consolidated_completed_researches' => 'Consolidated Completed Researches',
    'consolidated_ongoing_researches' => 'Consolidated Ongoing Researches',
    'consolidated_research_output_published' => 'Consolidated Research Output Published',
    'consolidated_research_output_presented' => 'Consolidated Research Output Presented',
    'consolidated_commercialized' => 'Consolidated Commercialized',
    'consolidated_resulted_in_extension' => 'Consolidated Resulted in Extension',
    'consolidated_journal_citation' => 'Consolidated Journal Citation',
    'consolidated_book_citation' => 'Consolidated Book Citation',
    'consolidated_inventions_um_copyrights' => 'Consolidated Inventions, UM, Copyrights',
    'progress_report' => 'Progress Report',
    'terminal_report' => 'Terminal Report',
    'terminal_report_assessment_form' => 'Terminal Report Assessment Form',
    'obr_matrix' => 'OBR Matrix',
    'trainings_conducted' => 'Trainings Conducted',
    'technical_advisory' => 'Technical Advisory',
    'extension_linkages' => 'Extension Linkages',
    'outreach_activities' => 'Outreach Activities',
    'technology_adoption' => 'Technology Adoption',
    'accomplishment_report' => 'Accomplishment Report',
    'technical_advisory_ar' => 'Technical Advisory AR',
    'outreach_activities_ar' => 'Outreach Activities AR',
    'ebalwasyon_ng_gawain' => 'Ebalwasyon ng Gawain',
    'attendance_sheet' => 'Attendance Sheet',
];
$selectedFormType = trim((string) ($_GET['form_type'] ?? ''));
if ($selectedFormType === '' || !array_key_exists($selectedFormType, $formTypeLabels)) {
    $selectedFormType = '';
}
$filteredPendingAction = array_values(array_filter($pendingAction, static function (array $proposal) use ($selectedFormType): bool {
    if ($selectedFormType === '') {
        return true;
    }
    $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
    $formType = is_array($summary) ? (string) ($summary['form_type'] ?? '') : '';

    return $formType === $selectedFormType;
}));
$filteredSubmissions = array_values(array_filter($submissions, static function (array $proposal) use ($selectedFormType): bool {
    if ($selectedFormType === '') {
        return true;
    }
    $summary = json_decode((string) ($proposal['summary'] ?? ''), true);
    $formType = is_array($summary) ? (string) ($summary['form_type'] ?? '') : '';

    return $formType === $selectedFormType;
}));
$scopeLabel = match ($scopeProjectType) {
    'research' => 'Research',
    'extension' => 'Extension',
    default => 'All Proposal',
};

$pageTitle = $scopeLabel . ' Monitoring — RIDE IMS';
$pageHeading = $scopeLabel . ' Monitoring';
$pageSubtitle = 'Signed in as ' . $roleTitle
    . ($user['college_name'] && $scopeProjectType === null && !MonitoringRoles::isVpride() ? ' · ' . $user['college_name'] : '');
?>

<div class="monitoring-page">
<div class="monitoring-workflows">
    <?php if ($showResearch): ?>
    <div class="monitoring-workflow card">
        <h2>Research Approval Workflow</h2>
        <ol class="monitoring-flow">
            <li class="<?= ($isCoordinatorResearch ?? false) ? 'active' : '' ?>">
                <strong>Faculty / Researcher</strong>
                <span>Submits research proposal</span>
            </li>
            <li class="<?= ($isCoordinatorResearch ?? false) ? 'active' : '' ?>">
                <strong>Coordinator of Research</strong>
                <span>Endorses and forwards to College Dean</span>
            </li>
            <li class="<?= $isDean ? 'active' : '' ?>">
                <strong>College Dean</strong>
                <span>Approves and forwards to Director of Research</span>
            </li>
            <li class="<?= $isDirectorResearch ? 'active' : '' ?>">
                <strong>Director of Research</strong>
                <span>Approves and forwards to VPRIDE</span>
            </li>
            <li class="<?= $isVpride ? 'active' : '' ?>">
                <strong>Admin / VPRIDE</strong>
                <span>Grants final approval</span>
            </li>
        </ol>
    </div>
    <?php endif; ?>

    <?php if ($showExtension): ?>
    <div class="monitoring-workflow card">
        <h2>Extension Approval Workflow</h2>
        <ol class="monitoring-flow">
            <li class="<?= ($isCoordinatorExtension ?? false) ? 'active' : '' ?>">
                <strong>Faculty / Extension Worker</strong>
                <span>Submits extension proposal</span>
            </li>
            <li class="<?= ($isCoordinatorExtension ?? false) ? 'active' : '' ?>">
                <strong>Coordinator of Extension</strong>
                <span>Endorses and forwards to College Dean</span>
            </li>
            <li class="<?= $isDean ? 'active' : '' ?>">
                <strong>College Dean</strong>
                <span>Approves and forwards to Director of Extension</span>
            </li>
            <li class="<?= $isDirectorExtension ? 'active' : '' ?>">
                <strong>Director of Extension</strong>
                <span>Approves and forwards to VPRIDE</span>
            </li>
            <li class="<?= $isVpride ? 'active' : '' ?>">
                <strong>Admin / VPRIDE</strong>
                <span>Grants final approval</span>
            </li>
        </ol>
    </div>
    <?php endif; ?>
</div>

<div class="grid grid-4" style="margin: 1.5rem 0;">
    <div class="card stat">
        <div class="value"><?= (int) array_sum($stats['by_status']) ?></div>
        <div class="label">Total <?= htmlspecialchars($scopeLabel) ?> Submissions</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_coordinator'] ?? 0) ?></div>
        <div class="label">Awaiting Endorsement</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_dean'] ?? 0) ?></div>
        <div class="label">Awaiting Dean Approval</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) (($stats['pending_director_research'] ?? 0) + ($stats['pending_director_extension'] ?? 0)) ?></div>
        <div class="label">Awaiting Director Approval</div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <form method="get" class="proposal-inline-form" style="display: flex; gap: .75rem; align-items: end; flex-wrap: wrap;">
        <?php if ($scopeProjectType !== null): ?>
            <input type="hidden" name="scope" value="<?= htmlspecialchars($scopeProjectType) ?>">
        <?php endif; ?>
        <div>
            <label for="monitoring-form-type" class="proposal-section-note" style="display: block; margin-bottom: .35rem;">Filter by form</label>
            <select id="monitoring-form-type" name="form_type">
                <option value="">All Forms</option>
                <?php foreach ($formTypeLabels as $formKey => $formLabel): ?>
                    <option value="<?= htmlspecialchars($formKey) ?>" <?= $selectedFormType === $formKey ? 'selected' : '' ?>>
                        <?= htmlspecialchars($formLabel) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-sm">Apply Filter</button>
        <?php if ($selectedFormType !== ''): ?>
            <a class="btn btn-sm btn-outline" href="<?= base_url('monitoring' . ($scopeProjectType !== null ? '?scope=' . rawurlencode($scopeProjectType) : '')) ?>">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($scopeProjectType === null): ?>
<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_director_research'] ?? 0) ?></div>
        <div class="label">Research — Director of Research</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_director_extension'] ?? 0) ?></div>
        <div class="label">Extension — Director of Extension</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_vpride'] ?? 0) ?></div>
        <div class="label">Awaiting VPRIDE Final Approval</div>
    </div>
</div>
<?php elseif ($isDirectorResearch || $isDirectorExtension): ?>
<div class="grid grid-4" style="margin-bottom: 1.5rem;">
    <div class="card stat">
        <div class="value"><?= (int) ($isDirectorResearch ? ($stats['pending_director_research'] ?? 0) : ($stats['pending_director_extension'] ?? 0)) ?></div>
        <div class="label">Awaiting Your Approval</div>
    </div>
    <div class="card stat">
        <div class="value"><?= (int) ($stats['pending_vpride'] ?? 0) ?></div>
        <div class="label">Awaiting VPRIDE Final Approval</div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($filteredPendingAction)): ?>
<div class="card monitoring-priority">
    <h2>
        <?php if ($isCoordinatorResearch ?? false): ?>
            Pending Research Endorsement
        <?php elseif ($isCoordinatorExtension ?? false): ?>
            Pending Extension Endorsement
        <?php elseif ($isCoordinator ?? false): ?>
            Pending Endorsement
        <?php elseif ($isDean): ?>
            Pending Dean Approval
        <?php elseif ($isDirectorResearch): ?>
            Pending Director of Research Approval
        <?php elseif ($isDirectorExtension): ?>
            Pending Director of Extension Approval
        <?php else: ?>
            Pending Final Approval
        <?php endif; ?>
    </h2>
    <div class="proposal-table-wrap">
        <table class="proposal-table monitoring-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <?php if ($scopeProjectType === null): ?><th>Type</th><?php endif; ?>
                    <th>Submitter</th>
                    <?php if (MonitoringRoles::isUniversityWide()): ?><th>College</th><?php endif; ?>
                    <th>Form</th>
                    <th>Submitted</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($filteredPendingAction as $p): ?>
                <?php
                $pendingSummary = json_decode((string) ($p['summary'] ?? ''), true);
                $pendingFormType = is_array($pendingSummary) ? (string) ($pendingSummary['form_type'] ?? '') : '';
                $pendingFormLabel = $formTypeLabels[$pendingFormType] ?? ($pendingFormType !== '' ? ucwords(str_replace('_', ' ', $pendingFormType)) : 'Standard Proposal');
                ?>
                <tr>
                    <td><?= htmlspecialchars($p['title']) ?></td>
                    <?php if ($scopeProjectType === null): ?>
                        <td><?= htmlspecialchars(ucfirst((string) $p['project_type'])) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars(trim($p['first_name'] . ' ' . $p['last_name'])) ?></td>
                    <?php if (MonitoringRoles::isUniversityWide()): ?>
                        <td><?= htmlspecialchars((string) $p['college_name']) ?></td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($pendingFormLabel) ?></td>
                    <td><?= htmlspecialchars((string) ($p['submitted_at'] ?? '—')) ?></td>
                    <td><a class="btn btn-sm btn-accent" href="<?= base_url('proposals/' . $p['id']) ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($isVpride && !empty($submitters)): ?>
<div class="card">
    <h2>Faculty &amp; Extension Worker Submitters</h2>
    <p class="muted">Monitor all faculty and extension workers with extension submissions university-wide.</p>
    <div class="proposal-table-wrap">
        <table class="proposal-table monitoring-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>College</th>
                    <th>Total</th>
                    <th>Active</th>
                    <th>Approved</th>
                    <th>Last Activity</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($submitters as $s): ?>
                <tr>
                    <td><?= htmlspecialchars(trim($s['first_name'] . ' ' . $s['last_name'])) ?></td>
                    <td><?= htmlspecialchars((string) $s['email']) ?></td>
                    <td><?= htmlspecialchars((string) ($s['college_name'] ?? '—')) ?></td>
                    <td><?= (int) $s['total_submissions'] ?></td>
                    <td><?= (int) $s['active_submissions'] ?></td>
                    <td><?= (int) $s['approved_submissions'] ?></td>
                    <td><?= htmlspecialchars((string) ($s['last_activity'] ?? '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2>
        <?php if ($scopeProjectType === 'extension'): ?>
            All Extension Submissions
        <?php elseif ($scopeProjectType === 'research'): ?>
            All Research Submissions
        <?php elseif (MonitoringRoles::isUniversityWide()): ?>
            All Proposal Submissions
        <?php else: ?>
            College Proposal Submissions
        <?php endif; ?>
    </h2>
    <?php if (empty($filteredSubmissions)): ?>
        <p>No <?= htmlspecialchars(strtolower($scopeLabel)) ?> submissions found<?= MonitoringRoles::isUniversityWide() ? '' : ' for your college' ?>.</p>
    <?php else: ?>
        <div class="proposal-table-wrap">
            <table class="proposal-table monitoring-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <?php if ($scopeProjectType === null): ?><th>Type</th><?php endif; ?>
                        <th>Submitter</th>
                        <?php if (MonitoringRoles::isUniversityWide()): ?><th>College</th><?php endif; ?>
                        <th>Form</th>
                        <th>Status</th>
                        <th>Current Step</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($filteredSubmissions as $p): ?>
                    <?php
                    $summary = json_decode((string) ($p['summary'] ?? ''), true);
                    $formType = is_array($summary) ? (string) ($summary['form_type'] ?? '') : '';
                    $formLabel = $formTypeLabels[$formType] ?? ($formType !== '' ? ucwords(str_replace('_', ' ', $formType)) : 'Standard Proposal');
                    $reportAsOf = is_array($summary) ? trim((string) ($summary['report_as_of'] ?? '')) : '';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($p['project_code'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($p['title']) ?></td>
                        <?php if ($scopeProjectType === null): ?>
                            <td><?= htmlspecialchars(ucfirst((string) $p['project_type'])) ?></td>
                        <?php endif; ?>
                        <td><?= htmlspecialchars(trim($p['first_name'] . ' ' . $p['last_name'])) ?></td>
                        <?php if (MonitoringRoles::isUniversityWide()): ?>
                            <td><?= htmlspecialchars((string) $p['college_name']) ?></td>
                        <?php endif; ?>
                        <td>
                            <?= htmlspecialchars($formLabel) ?>
                            <?php if ($reportAsOf !== ''): ?>
                                <br><span class="muted">As of <?= htmlspecialchars($reportAsOf) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= htmlspecialchars($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
                        <td><?= htmlspecialchars(MonitoringRoles::stepLabel((string) ($p['current_step'] ?? '—'))) ?></td>
                        <td><a class="btn btn-sm" href="<?= base_url('proposals/' . $p['id']) ?>">Open</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>
