<?php

/** @var string $menuScope 'research'|'extension' */

$scopeQuery = proposal_nav_scope_query($menuScope);
$terminalAssessmentHref = \App\Support\AccessControl::isCoordinatorResearch()
    ? base_url('proposals/terminal-report-registry?form_type=terminal_report_assessment_form')
    : base_url('proposals/create/terminal-report-assessment-form' . $scopeQuery);

$navActive = static fn (bool $active): bool => proposal_nav_scoped_active($active, $menuScope);

$navLabel = static fn (string $label): string => htmlspecialchars(proposal_nav_submenu_label($label));

?>

<?php if ($showTerminalReportNav): ?>

    <a href="<?= base_url(\App\Support\AccessControl::isCoordinatorResearch() ? 'proposals/terminal-report-registry' : 'proposals/create/terminal-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($terminalReportNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Terminal Report') ?></span>

    </a>

<?php endif; ?>

<?php if ($showProgressReportNav): ?>

    <a href="<?= base_url('proposals/create/progress-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($progressReportNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Progress Report') ?></span>

    </a>

<?php endif; ?>

<?php if ($showTerminalReportAssessmentFormNav): ?>

    <a href="<?= $terminalAssessmentHref ?>" class="nav-subitem<?= $navActive($terminalReportAssessmentFormNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Terminal Report Assessment Form') ?></span>

    </a>

<?php endif; ?>
