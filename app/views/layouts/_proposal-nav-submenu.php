<?php

/** @var string $menuScope 'research'|'extension' */

$scopeQuery = proposal_nav_scope_query($menuScope);

$navActive = static fn (bool $active): bool => proposal_nav_scoped_active($active, $menuScope);

$navLabel = static fn (string $label): string => htmlspecialchars(proposal_nav_submenu_label($label));

$isExtensionMenu = $menuScope === 'extension';

?>

<?php if (!$hideCoordinatorProposalIntroNav): ?>

    <a href="<?= base_url('proposals/create' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($applicantNavActive) ? ' active' : '' ?>">

        <span><?= $isExtensionMenu ? 'Extension Program/Project Proposal' : 'Applicant&apos;s Information' ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showManuscriptNav && !$hideCoordinatorProposalIntroNav): ?>

    <a href="<?= base_url('proposals/create/manuscript' . $scopeQuery) ?>" class="nav-subitem<?= ($navActive($manuscriptNavActive) && !$disableManuscriptHighlight) ? ' active' : '' ?>">

        <span>Manuscript Writing for Publication</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showCompletedResearchesNav): ?>

    <a href="<?= base_url('proposals/create/completed-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($completedResearchesNavActive) ? ' active' : '' ?>">

        <span>Completed Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showOngoingResearchesNav): ?>

    <a href="<?= base_url('proposals/create/ongoing-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($ongoingResearchesNavActive) ? ' active' : '' ?>">

        <span>Ongoing Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedOngoingResearchesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-ongoing-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedOngoingResearchesNavActive) ? ' active' : '' ?>">

        <span>Consolidated Ongoing Researches</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchOutputPublishedNav): ?>

    <a href="<?= base_url('proposals/create/research-output-published' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($researchOutputPublishedNavActive) ? ' active' : '' ?>">

        <span>Research Output Published</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResearchOutputPublishedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-research-output-published' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResearchOutputPublishedNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Consolidated Research Output Published') ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResearchOutputPresentedNav): ?>

    <a href="<?= base_url('proposals/create/research-output-presented' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($researchOutputPresentedNavActive) ? ' active' : '' ?>">

        <span>Research Output Presented</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResearchOutputPresentedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-research-output-presented' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResearchOutputPresentedNavActive) ? ' active' : '' ?>">

        <span>Consolidated Research Output Presented</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showCommercializedNav): ?>

    <a href="<?= base_url('proposals/create/commercialized' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($commercializedNavActive) ? ' active' : '' ?>">

        <span>Commercialized</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedCommercializedNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-commercialized' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedCommercializedNavActive) ? ' active' : '' ?>">

        <span>Consolidated Commercialized</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showResultedInExtensionNav): ?>

    <a href="<?= base_url('proposals/create/resulted-in-extension' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($resultedInExtensionNavActive) ? ' active' : '' ?>">

        <span>Resulted in Extension</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedResultedInExtensionNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-resulted-in-extension' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedResultedInExtensionNavActive) ? ' active' : '' ?>">

        <span>Consolidated Resulted in Extension</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showJournalCitationNav): ?>

    <a href="<?= base_url('proposals/create/journal-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($journalCitationNavActive) ? ' active' : '' ?>">

        <span>Journal Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedJournalCitationNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-journal-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedJournalCitationNavActive) ? ' active' : '' ?>">

        <span>Consolidated Journal Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showBookCitationNav): ?>

    <a href="<?= base_url('proposals/create/book-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($bookCitationNavActive) ? ' active' : '' ?>">

        <span>Book Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedBookCitationNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-book-citation' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedBookCitationNavActive) ? ' active' : '' ?>">

        <span>Consolidated Book Citation</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showInventionsUmCopyrightsNav): ?>

    <a href="<?= base_url('proposals/create/inventions-um-copyrights' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($inventionsUmCopyrightsNavActive) ? ' active' : '' ?>">

        <span>Inventions, UM, Copyrights</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedInventionsUmCopyrightsNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-inventions-um-copyrights' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedInventionsUmCopyrightsNavActive) ? ' active' : '' ?>">

        <span>Consolidated Inventions, UM, Copyrights</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showLinkagesNav): ?>

    <a href="<?= base_url('proposals/create/linkages' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($linkagesNavActive) ? ' active' : '' ?>">

        <span>Linkages</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedLinkagesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-linkages' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedLinkagesNavActive) ? ' active' : '' ?>">

        <span>Consolidated Linkages</span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showConsolidatedCompletedResearchesNav): ?>

    <a href="<?= base_url('proposals/create/consolidated-completed-researches' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($consolidatedCompletedResearchesNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Consolidated Completed Researches') ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showProgressReportNav && !$isFacultyAccount): ?>

    <a href="<?= base_url('proposals/create/progress-report' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($progressReportNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('Progress Report') ?></span>

    </a>

<?php endif; ?>

<?php if (!$isExtensionMenu && $showObrMatrixNav): ?>

    <a href="<?= base_url('proposals/create/obr-matrix' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($obrMatrixNavActive) ? ' active' : '' ?>">

        <span><?= $navLabel('OBR Matrix') ?></span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showTrainingsConductedNav): ?>

    <a href="<?= base_url('proposals/create/trainings-conducted' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($trainingsConductedNavActive) ? ' active' : '' ?>">

        <span>Trainings Conducted</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showTechnicalAdvisoryNav): ?>

    <a href="<?= base_url('proposals/create/technical-advisory' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($technicalAdvisoryNavActive) ? ' active' : '' ?>">

        <span>Technical Advisory</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showExtensionLinkagesNav): ?>

    <a href="<?= base_url('proposals/create/extension-linkages' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($extensionLinkagesNavActive) ? ' active' : '' ?>">

        <span>Extension Linkages</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showOutreachActivitiesNav): ?>

    <a href="<?= base_url('proposals/create/outreach-activities' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($outreachActivitiesNavActive) ? ' active' : '' ?>">

        <span>Outreach Activities</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showTechnologyAdoptionNav): ?>

    <a href="<?= base_url('proposals/create/technology-adoption' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($technologyAdoptionNavActive) ? ' active' : '' ?>">

        <span>Technology Adoption</span>

    </a>

<?php endif; ?>

<?php if ($isExtensionMenu && $showEbalwasyonNgGawainNav): ?>

    <a href="<?= base_url('proposals/create/ebalwasyon-ng-gawain' . $scopeQuery) ?>" class="nav-subitem<?= $navActive($ebalwasyonNgGawainNavActive) ? ' active' : '' ?>">

        <span>Ebalwasyon ng Gawain</span>

    </a>

<?php endif; ?>

