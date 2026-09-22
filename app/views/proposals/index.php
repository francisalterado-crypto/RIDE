<?php
/** @var list<array> $proposals */
/** @var int $currentUserId */
use App\Support\MonitoringRoles;

$isVprideRegistry = MonitoringRoles::isVpride();
$proposalCount = count($proposals);
$pageTitle = 'Proposals — RIDE IMS';
$pageHeading = 'Proposals';
$pageSubtitle = $isVprideRegistry
    ? 'University-wide proposal registry — search, review, and manage submissions.'
    : 'Review draft, submitted, and approved research applications in the same paper-style format.';
?>

<div class="page-actions-bar">
    <?php if (!$isVprideRegistry): ?>
        <?php if (\App\Core\Auth::hasRole('faculty')): ?>
        <a class="btn btn-accent" href="<?= base_url('proposals/create' . proposal_nav_scope_query('research')) ?>">Research and Development Proposal</a>
        <a class="btn btn-accent" href="<?= base_url('proposals/create' . proposal_nav_scope_query('extension')) ?>">Extension Services Proposal</a>
        <?php elseif (!MonitoringRoles::isCoordinatorExtension()): ?>
        <a class="btn btn-accent" href="<?= base_url('proposals/create') ?>"><?= htmlspecialchars(proposal_nav_group_label()) ?></a>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="proposal-paper proposal-paper-list proposal-registry">
    <section class="proposal-section">
        <div class="proposal-registry-header">
            <div>
                <h2 class="proposal-section-title">Proposal Registry</h2>
                <p class="proposal-section-note" id="proposal-registry-count">
                    <?= $proposalCount ?> record<?= $proposalCount === 1 ? '' : 's' ?>
                </p>
            </div>
            <?php if ($proposalCount > 0): ?>
            <div class="proposal-registry-toolbar">
                <label class="proposal-registry-search-label" for="proposal-registry-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span class="sr-only">Search proposals</span>
                </label>
                <input
                    type="search"
                    id="proposal-registry-search"
                    class="proposal-registry-search no-capitalize"
                    placeholder="<?= $isVprideRegistry ? 'Search code, title, college, researcher…' : 'Search code, title, researcher…' ?>"
                    aria-label="Search proposals in registry"
                >
            </div>
            <?php endif; ?>
        </div>

        <?php if ($proposalCount === 0): ?>
            <div class="proposal-empty-state">
                <p>No proposals found.<?= (!$isVprideRegistry && !MonitoringRoles::isCoordinatorExtension()) ? ' Use the button above to create one.' : '' ?></p>
            </div>
        <?php else: ?>
            <div class="proposal-table-wrap proposal-registry-table-wrap">
                <table class="proposal-table proposal-registry-table" id="proposal-registry-table">
                    <thead>
                        <tr>
                            <th class="col-code">Code</th>
                            <th class="col-title">Title</th>
                            <?php if ($isVprideRegistry): ?>
                            <th class="col-college">College</th>
                            <?php endif; ?>
                            <th class="col-type">Type</th>
                            <th class="col-researcher">Lead Researcher</th>
                            <?php if (!$isVprideRegistry): ?>
                            <th class="col-role">Your Role</th>
                            <?php endif; ?>
                            <th class="col-status">Status</th>
                            <th class="col-step">Step</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($proposals as $p): ?>
                        <?php
                        $leadName = trim((string) (($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')));
                        $searchText = strtolower(implode(' ', array_filter([
                            (string) ($p['project_code'] ?? ''),
                            (string) ($p['title'] ?? ''),
                            (string) ($p['project_type'] ?? ''),
                            $leadName,
                            (string) ($p['college_name'] ?? ''),
                            (string) ($p['status'] ?? ''),
                            (string) ($p['current_step'] ?? ''),
                            (string) ($p['membership'] ?? ''),
                        ])));
                        $canDelete = \App\Core\Auth::hasRole('faculty')
                            && (int) ($p['user_id'] ?? 0) === $currentUserId
                            && in_array((string) ($p['status'] ?? ''), ['draft', 'returned'], true);
                        $canForceManage = \App\Core\Auth::hasRole('ride_admin', 'vpride', 'dean')
                            && MonitoringRoles::canViewProposal($p);
                        $canForceReopen = $canForceManage
                            && in_array((string) ($p['status'] ?? ''), ['submitted', 'under_review'], true);
                        $canForceDelete = $canForceManage
                            && in_array((string) ($p['status'] ?? ''), ['draft', 'returned', 'submitted', 'under_review', 'suspended'], true);
                        $currentStep = MonitoringRoles::stepLabel((string) ($p['current_step'] ?? '—'));
                        ?>
                        <tr data-search="<?= htmlspecialchars($searchText) ?>">
                            <td class="col-code"><code class="proposal-registry-code"><?= htmlspecialchars($p['project_code'] ?? '—') ?></code></td>
                            <td class="col-title">
                                <span class="proposal-registry-title" title="<?= htmlspecialchars((string) $p['title']) ?>">
                                    <?= htmlspecialchars((string) $p['title']) ?>
                                </span>
                            </td>
                            <?php if ($isVprideRegistry): ?>
                            <td class="col-college"><?= htmlspecialchars((string) ($p['college_name'] ?? '—')) ?></td>
                            <?php endif; ?>
                            <td class="col-type"><?= htmlspecialchars(ucfirst((string) $p['project_type'])) ?></td>
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
                            </td>
                            <td class="col-step"><?= htmlspecialchars($currentStep) ?></td>
                            <td class="col-actions">
                                <div class="proposal-registry-actions" role="group" aria-label="Proposal actions">
                                    <a
                                        class="btn btn-sm proposal-icon-btn"
                                        href="<?= base_url('proposals/' . $p['id']) ?>"
                                        title="Open proposal"
                                        aria-label="Open proposal"
                                    >
                                        <i class="fas fa-folder-open" aria-hidden="true"></i>
                                    </a>
                                    <?php if ($canForceReopen): ?>
                                        <form method="post" action="<?= base_url('proposals/' . $p['id'] . '/force-reopen') ?>" onsubmit="return confirm('Force reopen this proposal for applicant editing?');">
                                            <?= csrf_field() ?>
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline proposal-icon-btn"
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
                                                class="btn btn-sm btn-danger proposal-icon-btn"
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
                                                class="btn btn-sm btn-danger proposal-icon-btn"
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
