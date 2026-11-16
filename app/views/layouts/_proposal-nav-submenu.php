<?php

/** @var string $menuScope 'research'|'extension' */

$scopeQuery = proposal_nav_scope_query($menuScope);

$navActive = static fn (bool $active): bool => proposal_nav_scoped_active($active, $menuScope);

$navLabel = static fn (string $label): string => htmlspecialchars(proposal_nav_submenu_label($label));

$isExtensionMenu = $menuScope === 'extension';

?>

<?php if (!$hideCoordinatorProposalIntroNav): ?>

    <a href="<?= base_url('proposals/create' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($applicantNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-file-signature nav-subicon" aria-hidden="true"></i><span><?= $isExtensionMenu ? 'Extension Project Proposal' : 'Research Proposal Application' ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchRequestsNav): ?>
    <?php
    $incentiveForms = array_filter(\App\Support\ResearchRequestForms::all(), static fn (array $d): bool => ($d['group'] ?? '') === 'incentive');
    $incentiveNavActive = false;
    foreach ($incentiveForms as $incentiveDefinition) {
        $incentiveNavActive = $incentiveNavActive || nav_active('research-requests/new/' . $incentiveDefinition['slug']);
    }
    ?>
    <?php foreach (\App\Support\ResearchRequestForms::all() as $requestDefinition): ?>
        <?php if (($requestDefinition['group'] ?? '') === 'incentive') { continue; } ?>
        <a href="<?= base_url('research-requests/new/' . $requestDefinition['slug']) ?>" class="nav-subitem<?= nav_active('research-requests/new/' . $requestDefinition['slug']) ? ' active' : '' ?>">
            <i class="fa-solid <?= htmlspecialchars(\App\Support\ResearchRequestForms::navIcon((string) $requestDefinition['slug'])) ?> nav-subicon" aria-hidden="true"></i><span><?= htmlspecialchars((string) $requestDefinition['label']) ?></span>
        </a>
    <?php endforeach; ?>
    <?php if ($incentiveForms !== []): ?>
    <div class="nav-subgroup<?= $incentiveNavActive ? ' has-active' : '' ?>" id="research-incentives-nav" data-nav-subgroup>
        <button type="button" class="nav-subitem nav-subgroup-toggle<?= $incentiveNavActive ? ' is-current' : '' ?>" aria-expanded="true" aria-controls="research-incentives-subitems">
            <i class="fa-solid fa-hand-holding-dollar nav-subicon" aria-hidden="true"></i><span>Incentives</span>
            <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
        </button>
        <div class="nav-subgroup-items" id="research-incentives-subitems">
            <?php foreach ($incentiveForms as $incentiveDefinition): ?>
                <a href="<?= base_url('research-requests/new/' . $incentiveDefinition['slug']) ?>" class="nav-subitem<?= nav_active('research-requests/new/' . $incentiveDefinition['slug']) ? ' active' : '' ?>">
                    <i class="fa-solid <?= htmlspecialchars(\App\Support\ResearchRequestForms::navIcon((string) $incentiveDefinition['slug'])) ?> nav-subicon" aria-hidden="true"></i><span><?= htmlspecialchars((string) $incentiveDefinition['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchAccomplishmentsNav): ?>
    <a href="<?= base_url('research-accomplishments') ?>" class="nav-subitem<?= $researchAccomplishmentsNavActive ? ' active' : '' ?>">
        <i class="fa-solid fa-trophy nav-subicon" aria-hidden="true"></i><span>Research Accomplishments</span>
    </a>
<?php endif; ?>

<?php
$researchQuarterlyNavActive = $navActive($completedResearchesNavActive)
    || $navActive($ongoingResearchesNavActive)
    || $navActive($researchOutputPublishedNavActive)
    || $navActive($researchOutputPresentedNavActive)
    || $navActive($commercializedNavActive)
    || $navActive($resultedInExtensionNavActive)
    || $navActive($journalCitationNavActive)
    || $navActive($bookCitationNavActive)
    || $navActive($inventionsUmCopyrightsNavActive)
    || $navActive($linkagesNavActive);
$groupResearchQuarterlyNav = !$isExtensionMenu && \App\Support\AccessControl::canPrepareResearchQuarterlyReports()
    && ($showCompletedResearchesNav || $showOngoingResearchesNav || $showResearchOutputPublishedNav
        || $showResearchOutputPresentedNav || $showCommercializedNav || $showResultedInExtensionNav
        || $showJournalCitationNav || $showBookCitationNav || $showInventionsUmCopyrightsNav || $showLinkagesNav);
?>
<?php if ($groupResearchQuarterlyNav): ?>
<div class="nav-subgroup<?= $researchQuarterlyNavActive ? ' has-active' : '' ?>" id="research-quarterly-report-nav" data-nav-subgroup>
    <button type="button" class="nav-subitem nav-subgroup-toggle<?= $researchQuarterlyNavActive ? ' is-current' : '' ?>" aria-expanded="true" aria-controls="research-quarterly-report-subitems">
        <i class="fa-solid fa-calendar-days nav-subicon" aria-hidden="true"></i><span>Quarterly Report</span>
        <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
    </button>
    <div class="nav-subgroup-items" id="research-quarterly-report-subitems">
<?php endif; ?>

<?php if (!$isExtensionMenu && $showCompletedResearchesNav): ?>

    <a href="<?= base_url('quarterly-reports/completed-researches') ?>" class="nav-subitem<?= $navActive($completedResearchesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-circle-check nav-subicon" aria-hidden="true"></i><span>Completed Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showOngoingResearchesNav): ?>

    <a href="<?= base_url('quarterly-reports/ongoing-researches') ?>" class="nav-subitem<?= $navActive($ongoingResearchesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-hourglass-half nav-subicon" aria-hidden="true"></i><span>Ongoing Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedOngoingResearchesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-ongoing-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedOngoingResearchesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Ongoing Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchOutputPublishedNav): ?>

    <a href="<?= base_url('quarterly-reports/research-output-published') ?>" class="nav-subitem<?= $navActive($researchOutputPublishedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-book nav-subicon" aria-hidden="true"></i><span>Research Output Published</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResearchOutputPublishedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-research-output-published' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResearchOutputPublishedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span><?= $navLabel('Consolidated Research Output Published') ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchOutputPresentedNav): ?>

    <a href="<?= base_url('quarterly-reports/research-output-presented') ?>" class="nav-subitem<?= $navActive($researchOutputPresentedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-person-chalkboard nav-subicon" aria-hidden="true"></i><span>Research Output Presented</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResearchOutputPresentedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-research-output-presented' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResearchOutputPresentedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Research Output Presented</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showCommercializedNav): ?>

    <a href="<?= base_url('quarterly-reports/commercialized') ?>" class="nav-subitem<?= $navActive($commercializedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-store nav-subicon" aria-hidden="true"></i><span>Commercialized</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedCommercializedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-commercialized' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedCommercializedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Commercialized</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResultedInExtensionNav): ?>

    <a href="<?= base_url('quarterly-reports/resulted-in-extension') ?>" class="nav-subitem<?= $navActive($resultedInExtensionNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-people-arrows nav-subicon" aria-hidden="true"></i><span>Resulted in Extension</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResultedInExtensionNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-resulted-in-extension' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResultedInExtensionNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Resulted in Extension</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showJournalCitationNav): ?>

    <a href="<?= base_url('quarterly-reports/journal-citation') ?>" class="nav-subitem<?= $navActive($journalCitationNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-quote-right nav-subicon" aria-hidden="true"></i><span>Journal Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedJournalCitationNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-journal-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedJournalCitationNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Journal Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showBookCitationNav): ?>

    <a href="<?= base_url('quarterly-reports/book-citation') ?>" class="nav-subitem<?= $navActive($bookCitationNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-book-bookmark nav-subicon" aria-hidden="true"></i><span>Book Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedBookCitationNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-book-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedBookCitationNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Book Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showInventionsUmCopyrightsNav): ?>

    <a href="<?= base_url('quarterly-reports/inventions-um-copyrights') ?>" class="nav-subitem<?= $navActive($inventionsUmCopyrightsNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-lightbulb nav-subicon" aria-hidden="true"></i><span>Inventions, UM, Copyrights</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedInventionsUmCopyrightsNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-inventions-um-copyrights' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedInventionsUmCopyrightsNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Inventions, UM, Copyrights</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showLinkagesNav): ?>

    <a href="<?= base_url('quarterly-reports/linkages') ?>" class="nav-subitem<?= $navActive($linkagesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-link nav-subicon" aria-hidden="true"></i><span>Linkages</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedLinkagesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-linkages' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedLinkagesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span>Consolidated Linkages</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedCompletedResearchesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-completed-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedCompletedResearchesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-layer-group nav-subicon" aria-hidden="true"></i><span><?= $navLabel('Consolidated Completed Researches') ?></span>

    </a>

<?php endif; ?>

<?php if ($groupResearchQuarterlyNav): ?>
    </div>
</div>
<?php endif; ?>

<?php if (!$isExtensionMenu && $showProgressReportNav && !$isFacultyAccount): ?>

    <a href="<?= base_url('proposals/create/progress-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($progressReportNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-chart-line nav-subicon" aria-hidden="true"></i><span><?= $navLabel('Progress Report') ?></span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showConductedActivitiesNav): ?>
    <a href="<?= base_url('activities') ?>" class="nav-subitem<?= $conductedActivitiesNavActive ? ' active' : '' ?>">
        <i class="fa-solid fa-clipboard-list nav-subicon" aria-hidden="true"></i><span>Conducted Activities</span>
    </a>
<?php endif; ?>

<?php
$quarterlyReportNavActive = $navActive($trainingsConductedNavActive)
    || $navActive($technicalAdvisoryNavActive)
    || $navActive($extensionLinkagesNavActive)
    || $navActive($outreachActivitiesNavActive)
    || $navActive($technologyAdoptionNavActive);
$groupQuarterlyReportNav = $isExtensionMenu && \App\Support\AccessControl::canPrepareExtensionQuarterlyReports()
    && ($showTrainingsConductedNav || $showTechnicalAdvisoryNav || $showExtensionLinkagesNav || $showOutreachActivitiesNav || $showTechnologyAdoptionNav);
?>
<?php if ($groupQuarterlyReportNav): ?>
<div class="nav-subgroup<?= $quarterlyReportNavActive ? ' has-active' : '' ?>" id="quarterly-report-nav" data-nav-subgroup>
    <button type="button" class="nav-subitem nav-subgroup-toggle<?= $quarterlyReportNavActive ? ' is-current' : '' ?>" aria-expanded="true" aria-controls="quarterly-report-subitems">
        <i class="fa-solid fa-calendar-days nav-subicon" aria-hidden="true"></i><span>Quarterly Report</span>
        <i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i>
    </button>
    <div class="nav-subgroup-items" id="quarterly-report-subitems">
<?php endif; ?>

<?php if ($isExtensionMenu && $showTrainingsConductedNav): ?>

    <a href="<?= base_url('quarterly-reports/trainings-conducted') ?>" class="nav-subitem<?= $navActive($trainingsConductedNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-chalkboard-user nav-subicon" aria-hidden="true"></i><span>Trainings Conducted</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showTechnicalAdvisoryNav): ?>

    <a href="<?= base_url('quarterly-reports/technical-advisory') ?>" class="nav-subitem<?= $navActive($technicalAdvisoryNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-headset nav-subicon" aria-hidden="true"></i><span>Technical Advisory</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showExtensionLinkagesNav): ?>

    <a href="<?= base_url('quarterly-reports/extension-linkages') ?>" class="nav-subitem<?= $navActive($extensionLinkagesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-handshake nav-subicon" aria-hidden="true"></i><span>Extension Linkages</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showOutreachActivitiesNav): ?>

    <a href="<?= base_url('quarterly-reports/outreach-activities') ?>" class="nav-subitem<?= $navActive($outreachActivitiesNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-hand-holding-heart nav-subicon" aria-hidden="true"></i><span>Outreach Activities</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showTechnologyAdoptionNav): ?>

    <a href="<?= base_url('quarterly-reports/technology-adoption') ?>" class="nav-subitem<?= $navActive($technologyAdoptionNavActive) ? ' active' : '' ?>">

        <i class="fa-solid fa-microchip nav-subicon" aria-hidden="true"></i><span>Technology Adoption</span>

    </a>

<?php endif; ?>

<?php if ($groupQuarterlyReportNav): ?>
    </div>
</div>
<?php endif; ?>
