<?php
/** @var list<array> $proposals rows from ProposalRegistry::rows() */
/** @var int $currentUserId */
/** @var string $registryKind 'proposal', 'request' or 'report' */
use App\Support\AccessControl;
use App\Support\ProposalRegistry;
use App\Support\ResearchRequestForms;

$registryKind = in_array($registryKind ?? 'proposal', ['proposal', 'request', 'report'], true) ? ($registryKind ?? 'proposal') : 'proposal';
$isReportRegistry = $registryKind === 'report';
$isVprideRegistry = AccessControl::isVpride();
$canExport = $isVprideRegistry;
$proposalCount = count($proposals);
$registryText = [
    'proposal' => [
        'heading' => 'Proposals',
        'registry' => 'Proposal Registry',
        'noun' => 'proposal',
        'subtitle' => $isVprideRegistry
            ? 'University-wide registry of Research Proposal Applications and Extension Project Proposals.'
            : 'Your Research Proposal Applications and Extension Project Proposals, from draft to approval.',
        'empty' => 'No proposals found.',
    ],
    'request' => [
        'heading' => 'Requests',
        'registry' => 'Request Registry',
        'noun' => 'request',
        'subtitle' => $isVprideRegistry
            ? 'University-wide registry of filed RDO requests.'
            : 'Your filed RDO requests: critical activities, external funding, paper presentation, and publication.',
        'empty' => 'No requests found.',
    ],
    'report' => [
        'heading' => 'Reports',
        'registry' => 'Report Registry',
        'noun' => 'report',
        'subtitle' => $isVprideRegistry
            ? 'University-wide registry of submitted quarterly, progress, and terminal reports.'
            : 'Your quarterly, progress, and terminal reports, from draft to approval.',
        'empty' => 'No reports found.',
    ],
][$registryKind];
$pageTitle = $registryText['heading'] . ' | RIDE Hub';
$pageHeading = $registryText['heading'];
$pageSubtitle = $registryText['subtitle'];

// "New form" menu: the proposal forms this user can start, then the RDO request forms.
$newForms = [];
if (!$isVprideRegistry && $registryKind === 'proposal') {
    if (proposal_nav_has_dual_menu()) {
        $newForms[] = ['RDO-20 Research Proposal Application', base_url('proposals/create' . proposal_nav_scope_query('research'))];
        $newForms[] = ['ESO-03 Extension Project Proposal', base_url('proposals/create' . proposal_nav_scope_query('extension'))];
    } elseif (!AccessControl::isCoordinatorExtension()) {
        $newForms[] = [proposal_nav_scope() === 'extension' ? 'ESO-03 Extension Project Proposal' : 'RDO-20 Research Proposal Application', base_url('proposals/create')];
    }
}
$requestForms = [];
if ($registryKind === 'request' && AccessControl::canFileResearchRequests()) {
    foreach (ResearchRequestForms::all() as $definition) {
        $requestForms[] = [str_replace('WPU-QSF-RIDE-', '', (string) $definition['code']) . ' ' . $definition['label'], base_url('research-requests/new/' . $definition['slug'])];
    }
}

// Tabs with counts; "Needs my action" for approvers or when something waits on the user.
$isApprover = $isVprideRegistry || \App\Core\Auth::hasRole('dean') || AccessControl::isCoordinatorResearch()
    || AccessControl::isCoordinatorExtension() || AccessControl::isRideCoordinator() || AccessControl::isDirectorResearch() || AccessControl::isDirectorExtension();
$tabCounts = [];
foreach (array_keys(ProposalRegistry::TABS) as $tab) {
    $tabCounts[$tab] = count(array_filter($proposals, static fn (array $row): bool => ProposalRegistry::inTab($row, $tab)));
}
$visibleTabs = array_filter(ProposalRegistry::TABS, static function (string $label, string $tab) use ($tabCounts, $isApprover): bool {
    return $tab !== 'action' || $isApprover || $tabCounts['action'] > 0;
}, ARRAY_FILTER_USE_BOTH);
$defaultTab = ($tabCounts['action'] ?? 0) > 0 ? 'action' : 'all';

$formOptions = array_values(array_unique(array_map(static fn (array $row): string => $row['registry']['form'], $proposals)));
sort($formOptions);
$yearOptions = array_values(array_unique(array_filter(array_map(static fn (array $row): string => $row['registry']['year'], $proposals))));
rsort($yearOptions);
?>

<div class="page-actions-bar">
    <?php if ($newForms !== [] || $requestForms !== []): ?>
        <details class="new-form-menu">
            <summary class="btn btn-accent"><i class="fas fa-plus" aria-hidden="true"></i> New form</summary>
            <div class="new-form-menu-panel">
                <?php if ($newForms !== []): ?>
                    <p class="new-form-menu-heading">Proposals</p>
                    <?php foreach ($newForms as [$label, $url]): ?>
                        <a href="<?= $url ?>"><?= htmlspecialchars($label) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($requestForms !== []): ?>
                    <p class="new-form-menu-heading">Requests</p>
                    <?php foreach ($requestForms as [$label, $url]): ?>
                        <a href="<?= $url ?>"><?= htmlspecialchars($label) ?></a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </details>
    <?php endif; ?>
</div>

<div class="card list-card proposal-registry">
    <section>
        <div class="list-card-head">
            <div>
                <h2><?= htmlspecialchars($registryText['registry']) ?></h2>
                <p id="proposal-registry-count">
                    <?= $proposalCount ?> record<?= $proposalCount === 1 ? '' : 's' ?>
                </p>
            </div>
            <?php if ($proposalCount > 0): ?>
            <div class="proposal-registry-toolbar list-search">
                <label class="proposal-registry-search-label" for="proposal-registry-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Search <?= $registryText['noun'] ?>s</span>
                </label>
                <input
                    type="search"
                    id="proposal-registry-search"
                    class="proposal-registry-search no-capitalize"
                    placeholder="<?= $isVprideRegistry ? 'Search title, form, college, or name' : 'Search title, form, or name' ?>"
                    aria-label="Search the <?= $registryText['noun'] ?> registry"
                >
            </div>
            <?php endif; ?>
        </div>

        <?php if ($proposalCount > 0): ?>
            <div class="tabs proposal-registry-tabs" role="tablist" id="proposal-registry-tabs" data-default-tab="<?= htmlspecialchars($defaultTab) ?>">
                <?php foreach ($visibleTabs as $tab => $label): ?>
                    <button type="button" role="tab" class="tab proposal-registry-tab" data-tab="<?= htmlspecialchars($tab) ?>" aria-selected="false">
                        <?= htmlspecialchars($label) ?> <span class="proposal-registry-tab-count"><?= (int) $tabCounts[$tab] ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="proposal-registry-filters">
                <label>
                    <span class="sr-only">Form</span>
                    <select id="proposal-registry-form">
                        <option value="">All forms</option>
                        <?php foreach ($formOptions as $form): ?>
                            <option value="<?= htmlspecialchars($form) ?>"><?= htmlspecialchars($form) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span class="sr-only">Year</span>
                    <select id="proposal-registry-year">
                        <option value="">All years</option>
                        <?php foreach ($yearOptions as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <?php if ($canExport): ?>
                    <a class="btn btn-outline btn-sm" id="proposal-registry-export" href="<?= base_url('proposals/export?kind=' . $registryKind) ?>" data-base="<?= base_url('proposals/export?kind=' . $registryKind) ?>">
                        <i class="fas fa-file-csv" aria-hidden="true"></i> Export CSV
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($proposalCount === 0): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open empty-icon" aria-hidden="true"></i>
                <p><?= htmlspecialchars($registryText['empty']) ?><?= ($newForms !== [] || $requestForms !== []) ? ' Use New form above to start one.' : '' ?><?= $isReportRegistry ? ' Reports are prepared from their pages under RIDE in the sidebar.' : '' ?></p>
            </div>
        <?php else: ?>
            <div class="empty-state" id="proposal-registry-empty" hidden><p>No records match the selected filters.</p></div>
            <div class="data-table-wrap proposal-registry-table-wrap">
                <table class="data-table proposal-registry-table" id="proposal-registry-table">
                    <thead>
                        <tr>
                            <th class="col-code"><?= $isReportRegistry ? 'Period' : 'Code' ?></th>
                            <th class="col-type">Type</th>
                            <th class="col-title">Title</th>
                            <th class="col-form">Form</th>
                            <?php if ($isVprideRegistry): ?>
                            <th class="col-college">College</th>
                            <?php endif; ?>
                            <th class="col-researcher"><?= $isReportRegistry ? 'Prepared by' : 'Lead' ?></th>
                            <?php if (!$isVprideRegistry): ?>
                            <th class="col-role">Your Role</th>
                            <?php endif; ?>
                            <th class="col-status">Status</th>
                            <th class="col-step">Step</th>
                            <?php if (!$isReportRegistry): ?>
                            <th class="col-progress">Form filled</th>
                            <?php endif; ?>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($proposals as $p): ?>
                        <?php
                        $r = $p['registry'];
                        $leadName = trim((string) (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')));
                        $rowTabs = implode(' ', array_keys(array_filter(ProposalRegistry::TABS, static fn (string $label, string $tab): bool => ProposalRegistry::inTab($p, $tab), ARRAY_FILTER_USE_BOTH)));
                        $canDelete = (int) ($p['user_id'] ?? 0) === $currentUserId
                            && in_array((string) ($p['status'] ?? ''), ['draft', 'returned'], true);
                        $canForceManage = \App\Core\Auth::hasRole('ride_admin', 'vpride', 'dean')
                            && AccessControl::canViewProposal($p);
                        $canForceReopen = $canForceManage
                            && in_array((string) ($p['status'] ?? ''), ['submitted', 'under_review'], true);
                        $canForceDelete = $canForceManage
                            && in_array((string) ($p['status'] ?? ''), ['draft', 'returned', 'submitted', 'under_review', 'suspended'], true);
                        $currentStep = !empty($p['current_step'])
                            ? AccessControl::stepLabel((string) $p['current_step'])
                            : (in_array((string) ($p['status'] ?? ''), ['draft', 'returned'], true) ? 'Not submitted yet' : '');
                        $note = $r['return_note'];
                        $noteText = $note !== null ? (string) $note['comment'] : '';
                        ?>
                        <tr
                            data-search="<?= htmlspecialchars(ProposalRegistry::searchText($p)) ?>"
                            data-tabs="<?= htmlspecialchars($rowTabs) ?>"
                            data-form="<?= htmlspecialchars($r['form']) ?>"
                            data-year="<?= htmlspecialchars($r['year']) ?>"
                            <?= $r['needs_action'] ? 'class="is-needs-action"' : '' ?>
                        >
                            <?php if ($isReportRegistry): ?>
                            <td class="col-code"><?= $r['period'] !== '' ? htmlspecialchars($r['period']) : '<span class="data-table-muted">-</span>' ?></td>
                            <?php else: ?>
                            <td class="col-code"><?php if (!empty($p['project_code'])): ?><code class="proposal-registry-code"><?= htmlspecialchars((string) $p['project_code']) ?></code><?php else: ?><span class="data-table-muted">Not assigned</span><?php endif; ?></td>
                            <?php endif; ?>
                            <?php $isExtensionRow = (string) ($p['project_type'] ?? '') === 'extension'; ?>
                            <td class="col-type"><span class="proposal-kind proposal-kind--<?= $isExtensionRow ? 'extension' : 'research' ?>"><?= $isExtensionRow ? 'Extension' : 'Research' ?></span></td>
                            <td class="col-title">
                                <a class="proposal-registry-title" href="<?= base_url('proposals/' . $p['id']) ?>" title="<?= htmlspecialchars((string) $p['title']) ?>">
                                    <?= htmlspecialchars((string) $p['title']) ?>
                                </a>
                                <?php if ($r['linked_code'] !== ''): ?>
                                    <div class="proposal-registry-sub">
                                        For research
                                        <?php if ($r['linked_id'] > 0): ?>
                                            <a href="<?= base_url('proposals/' . $r['linked_id']) ?>"><?= htmlspecialchars($r['linked_code']) ?></a>
                                        <?php else: ?>
                                            <?= htmlspecialchars($r['linked_code']) ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($note !== null): ?>
                                    <div class="proposal-registry-return" title="<?= htmlspecialchars($noteText) ?>">
                                        <strong>Returned by <?= htmlspecialchars($note['name']) ?>:</strong>
                                        <?= htmlspecialchars(mb_strlen($noteText) > 140 ? mb_substr($noteText, 0, 137) . '...' : $noteText) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="col-form"><?= htmlspecialchars($r['form']) ?></td>
                            <?php if ($isVprideRegistry): ?>
                            <td class="col-college"><?= htmlspecialchars((string) ($p['college_name'] ?? '')) ?></td>
                            <?php endif; ?>
                            <td class="col-researcher"><?= htmlspecialchars($leadName) ?></td>
                            <?php if (!$isVprideRegistry): ?>
                            <td class="col-role">
                                <?php if (($p['membership'] ?? 'lead') === 'coauthor'): ?>
                                    <span class="badge badge-under_review">Co-author</span>
                                <?php else: ?>
                                    Lead
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="col-status">
                                <span class="badge badge-<?= htmlspecialchars((string) $p['status']) ?>">
                                    <?= htmlspecialchars(status_label((string) $p['status'])) ?>
                                </span>
                                <?php if ($r['needs_action']): ?>
                                    <div class="proposal-registry-sub proposal-registry-action-flag">Waiting on you</div>
                                <?php endif; ?>
                            </td>
                            <td class="col-step<?= empty($p['current_step']) ? ' data-table-muted' : '' ?>"><?= htmlspecialchars($currentStep) ?></td>
                            <?php if (!$isReportRegistry): ?>
                            <td class="col-progress">
                                <?php if ($r['progress'] === null): ?>
                                    <span class="data-table-muted">-</span>
                                <?php else: ?>
                                    <?php $pct = $r['progress']['total'] > 0 ? (int) round(100 * $r['progress']['filled'] / $r['progress']['total']) : 100; ?>
                                    <div class="proposal-progress<?= $pct >= 100 ? ' is-complete' : '' ?>" title="<?= (int) $r['progress']['filled'] ?> of <?= (int) $r['progress']['total'] ?> required parts filled">
                                        <span class="proposal-progress-bar"><span style="width: <?= $pct ?>%"></span></span>
                                        <span class="proposal-progress-text"><?= $pct >= 100 ? 'Complete' : (int) $r['progress']['filled'] . '/' . (int) $r['progress']['total'] ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="col-actions">
                                <div class="proposal-registry-actions" role="group" aria-label="Proposal actions">
                                    <a
                                        class="btn btn-sm btn-outline icon-btn proposal-icon-btn"
                                        href="<?= base_url('proposals/' . $p['id']) ?>"
                                        title="Open <?= $registryText['noun'] ?>"
                                        aria-label="Open <?= $registryText['noun'] ?>"
                                    >
                                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    </a>
                                    <?php if ($canForceReopen): ?>
                                        <form method="post" action="<?= base_url('proposals/' . $p['id'] . '/force-reopen') ?>" onsubmit="return confirm('Force reopen this proposal for applicant editing?');">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline icon-btn proposal-icon-btn"
                                                title="Force reopen"
                                                aria-label="Force reopen proposal"
                                            >
                                                <i class="fas fa-undo" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canDelete): ?>
                                        <form method="post" action="<?= base_url('proposals/' . $p['id'] . '/delete') ?>" onsubmit="return confirm('Delete this proposal?');">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger icon-btn proposal-icon-btn"
                                                title="Delete proposal"
                                                aria-label="Delete proposal"
                                            >
                                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($canForceDelete): ?>
                                        <form method="post" action="<?= base_url('proposals/' . $p['id'] . '/force-delete') ?>" onsubmit="return confirm('Force delete this proposal? This action cannot be undone.');">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger icon-btn proposal-icon-btn"
                                                title="Force delete"
                                                aria-label="Force delete proposal"
                                            >
                                                <i class="fas fa-trash-can" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
