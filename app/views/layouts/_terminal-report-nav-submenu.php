<?php
/** @var string $menuScope 'research'|'extension' */
$scopeQuery = proposal_nav_scope_query($menuScope);
$navActive = static fn (bool $active): bool => proposal_nav_scoped_active($active, $menuScope);
$navLabel = static fn (string $label): string => htmlspecialchars(proposal_nav_submenu_label($label));
?>
<?php if ($showTerminalReportNav): ?>
    <a href="<?= base_url(\App\Support\AccessControl::isCoordinatorResearch() ? 'proposals/terminal-report-registry' : 'proposals/create/terminal-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($terminalReportNavActive) ? ' active' : '' ?>">
        <i class="fa-solid fa-flag-checkered nav-subicon" aria-hidden="true"></i><span><?= $navLabel('Terminal Report') ?></span>
    </a>
<?php endif; ?>
<?php if ($showProgressReportNav): ?>
    <a href="<?= base_url('proposals/create/progress-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($progressReportNavActive) ? ' active' : '' ?>">
        <i class="fa-solid fa-chart-line nav-subicon" aria-hidden="true"></i><span><?= $navLabel('Progress Report') ?></span>
    </a>
<?php endif; ?>
