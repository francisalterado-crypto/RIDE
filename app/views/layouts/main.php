<?php
$isAuthStandalone = !\App\Core\Auth::check() && str_ends_with($contentView ?? '', 'auth/login.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?= htmlspecialchars((string) $pageTitle) ?></title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/ride-logo.png') ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= base_url('assets/images/ride-logo.png') ?>">
    <?php if (\App\Core\Auth::check()): ?>
    <script>document.documentElement.classList.add('ride-preload'); try { if (localStorage.getItem('ride:sidebar-collapsed') === '1') { document.documentElement.classList.add('sidebar-collapsed'); } } catch (e) {}</script>
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if (!$isAuthStandalone): ?>
    <?php
    $appCssPath = BASE_PATH . '/public/assets/css/app.css';
    $appCssVersion = is_file($appCssPath) ? (string) filemtime($appCssPath) : '1';
    ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= htmlspecialchars($appCssVersion) ?>">
    <?php endif; ?>
</head>
<body data-app-base="<?= htmlspecialchars(base_url()) ?>" class="<?= \App\Core\Auth::check() ? 'app-body' : ($isAuthStandalone ? 'auth-body auth-standalone' : 'auth-body') ?>">
<?php if (\App\Core\Auth::check()): ?>
    <?php
    $u = \App\Core\Auth::user();
    $heading = $pageHeading ?? explode(' | ', (string) $pageTitle)[0];
    $subtitle = $pageSubtitle ?? '';
    $initials = user_initials($u);
    $roleLabel = user_role_label();
    $displayName = trim($u['first_name'] . ' ' . $u['last_name']);
    $headerUnread = (int) ($unreadNotifications ?? \App\Models\Notification::unreadCount());
        $unreadAdminMessageCount = \App\Models\AdminMessage::unreadCountForUser((int) ($u['id'] ?? 0));
        $unreadDirectMessageCount = \App\Support\DirectMessaging::isEnabledForCurrentUser()
            ? \App\Models\DirectMessage::unreadCountForUser((int) ($u['id'] ?? 0))
            : 0;
        $unreadMessageNavCount = $unreadAdminMessageCount + $unreadDirectMessageCount;
    ?>
    <div class="app-wrapper">
        <aside class="sidebar" id="app-sidebar">
            <div class="sidebar-header">
                <div class="logo-area">
                    <div class="logo-icon">
                        <img src="<?= base_url('assets/images/ride-logo.png') ?>" alt="RIDE - Research, Innovation, Development and Extension" width="40" height="40">
                    </div>
                    <div class="logo-text">
                        <h2>RIDE Hub</h2>
                        <span>Information Management System for RIDE</span>
                    </div>
                </div>
                <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-controls="app-sidebar" aria-expanded="true" title="Collapse sidebar">
                    <i class="fas fa-angle-double-left" aria-hidden="true"></i><span class="sr-only">Collapse sidebar</span>
                </button>
            </div>
            <nav class="nav-menu">
                <?php $isVprideAccount = \App\Support\MonitoringRoles::isVpride(); ?>
                <a href="<?= base_url('dashboard') ?>" class="nav-item<?= nav_active('dashboard') ? ' active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
                <?php if ($isVprideAccount): ?>
                <div class="nav-section">
                    <div class="nav-section-label">Research &amp; Extension</div>
                    <a href="<?= base_url('proposals') ?>" class="nav-item<?= nav_active('proposals') && !nav_active('proposals/create') ? ' active' : '' ?>">
                        <i class="fas fa-file-alt"></i> <span>Proposals</span>
                    </a>
                    <a href="<?= base_url('projects') ?>" class="nav-item<?= nav_active('projects') ? ' active' : '' ?>">
                        <i class="fas fa-project-diagram"></i> <span>Projects</span>
                    </a>
                    <a href="<?= base_url('reports/extension-beneficiaries') ?>" class="nav-item<?= nav_active('reports') ? ' active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> <span>Reports</span>
                    </a>
                    <a href="<?= base_url('forms') ?>" class="nav-item<?= nav_active('forms') ? ' active' : '' ?>">
                        <i class="fas fa-file-download"></i> <span>Downloadable Forms</span>
                    </a>
                    <a href="<?= base_url('faculty-directory') ?>" class="nav-item<?= nav_active('faculty-directory') ? ' active' : '' ?>">
                        <i class="fas fa-address-book"></i> <span>Faculty Directory</span>
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-label">Settings</div>
                    <?php if (\App\Support\FacultyFormDeadlines::canManage()): ?>
                    <a href="<?= base_url('settings/faculty-deadlines') ?>" class="nav-item<?= nav_active('settings/faculty-deadlines') ? ' active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> <span>Form Deadlines</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php if (\App\Support\FacultyFormDeadlines::canManage()): ?>
                <a href="<?= base_url('settings/faculty-deadlines') ?>" class="nav-item<?= nav_active('settings/faculty-deadlines') ? ' active' : '' ?>">
                    <i class="fas fa-calendar-check"></i> <span>Form Deadlines</span>
                </a>
                <?php endif; ?>
                <a href="<?= base_url('proposals') ?>" class="nav-item<?= nav_active('proposals') && !nav_active('proposals/create') ? ' active' : '' ?>">
                    <i class="fas fa-file-alt"></i> <span>Proposals</span>
                </a>
                <a href="<?= base_url('projects') ?>" class="nav-item<?= nav_active('projects') ? ' active' : '' ?>">
                    <i class="fas fa-project-diagram"></i> <span>Projects</span>
                </a>
                <?php if (\App\Support\MonitoringRoles::canEnterConductedActivities() && !conducted_activities_in_extension_nav()): ?>
                <a href="<?= base_url('activities') ?>" class="nav-item<?= nav_active('activities') ? ' active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i> <span>Conducted Activities</span>
                </a>
                <?php endif; ?>
                <a href="<?= base_url('forms') ?>" class="nav-item<?= nav_active('forms') ? ' active' : '' ?>">
                    <i class="fas fa-file-download"></i> <span>Downloadable Forms</span>
                </a>
                <a href="<?= base_url('faculty-directory') ?>" class="nav-item<?= nav_active('faculty-directory') ? ' active' : '' ?>">
                    <i class="fas fa-address-book"></i> <span>Faculty Directory</span>
                </a>
                <?php
                $newProposalActive = nav_active('proposals/create');
                $completedResearchesNavActive = nav_active('quarterly-reports/completed-researches') || nav_active('proposals/create/completed-researches');
                $ongoingResearchesNavActive = nav_active('quarterly-reports/ongoing-researches') || nav_active('proposals/create/ongoing-researches');
                $researchOutputPublishedNavActive = nav_active('quarterly-reports/research-output-published') || nav_active('proposals/create/research-output-published');
                $researchOutputPresentedNavActive = nav_active('quarterly-reports/research-output-presented') || nav_active('proposals/create/research-output-presented');
                $commercializedNavActive = nav_active('quarterly-reports/commercialized') || nav_active('proposals/create/commercialized');
                $resultedInExtensionNavActive = nav_active('quarterly-reports/resulted-in-extension') || nav_active('proposals/create/resulted-in-extension');
                $journalCitationNavActive = nav_active('quarterly-reports/journal-citation') || nav_active('proposals/create/journal-citation');
                $bookCitationNavActive = nav_active('quarterly-reports/book-citation') || nav_active('proposals/create/book-citation');
                $inventionsUmCopyrightsNavActive = nav_active('quarterly-reports/inventions-um-copyrights') || nav_active('proposals/create/inventions-um-copyrights');
                $linkagesNavActive = nav_active('quarterly-reports/linkages') || nav_active('proposals/create/linkages');
                $consolidatedCompletedResearchesNavActive = nav_active('proposals/create/consolidated-completed-researches');
                $consolidatedOngoingResearchesNavActive = nav_active('proposals/create/consolidated-ongoing-researches');
                $consolidatedResearchOutputPublishedNavActive = nav_active('proposals/create/consolidated-research-output-published');
                $consolidatedResearchOutputPresentedNavActive = nav_active('proposals/create/consolidated-research-output-presented');
                $consolidatedCommercializedNavActive = nav_active('proposals/create/consolidated-commercialized');
                $consolidatedResultedInExtensionNavActive = nav_active('proposals/create/consolidated-resulted-in-extension');
                $consolidatedJournalCitationNavActive = nav_active('proposals/create/consolidated-journal-citation');
                $consolidatedBookCitationNavActive = nav_active('proposals/create/consolidated-book-citation');
                $consolidatedInventionsUmCopyrightsNavActive = nav_active('proposals/create/consolidated-inventions-um-copyrights');
                $consolidatedLinkagesNavActive = nav_active('proposals/create/consolidated-linkages');
                $registryFormType = trim((string) ($_GET['form_type'] ?? ''));
                $progressReportNavActive = nav_active('proposals/create/progress-report')
                    || (nav_active('proposals/terminal-report-registry') && $registryFormType === 'progress_report');
                $terminalReportNavActive = nav_active('proposals/create/terminal-report')
                    || (
                        nav_active('proposals/terminal-report-registry')
                        && in_array($registryFormType, ['', 'terminal_report'], true)
                    );
                $terminalReportAssessmentFormNavActive = nav_active('proposals/create/terminal-report-assessment-form')
                    || (nav_active('proposals/terminal-report-registry') && $registryFormType === 'terminal_report_assessment_form');
                $trainingsConductedNavActive = nav_active('quarterly-reports/trainings-conducted') || nav_active('proposals/create/trainings-conducted');
                $technicalAdvisoryNavActive = nav_active('quarterly-reports/technical-advisory') || nav_active('proposals/create/technical-advisory');
                $extensionLinkagesNavActive = nav_active('quarterly-reports/extension-linkages') || nav_active('proposals/create/extension-linkages');
                $outreachActivitiesNavActive = nav_active('quarterly-reports/outreach-activities') || nav_active('proposals/create/outreach-activities');
                $technologyAdoptionNavActive = nav_active('quarterly-reports/technology-adoption') || nav_active('proposals/create/technology-adoption');
                $requiredFilesNavActive = nav_active('proposals/create/required-files')
                    || preg_match('#^proposals/\d+/edit/required-files$#', request_path()) === 1;
                $applicantNavActive = nav_active('proposals/create')
                    && !$completedResearchesNavActive
                    && !$ongoingResearchesNavActive
                    && !$researchOutputPublishedNavActive
                    && !$commercializedNavActive
                    && !$resultedInExtensionNavActive
                    && !$researchOutputPresentedNavActive
                    && !$journalCitationNavActive
                    && !$bookCitationNavActive
                    && !$inventionsUmCopyrightsNavActive
                    && !$consolidatedCompletedResearchesNavActive
                    && !$consolidatedOngoingResearchesNavActive
                    && !$consolidatedResearchOutputPublishedNavActive
                    && !$linkagesNavActive
                    && !$consolidatedLinkagesNavActive
                    && !$progressReportNavActive
                    && !$terminalReportNavActive
                    && !$terminalReportAssessmentFormNavActive
                    && !$trainingsConductedNavActive
                    && !$technicalAdvisoryNavActive
                    && !$extensionLinkagesNavActive
                    && !$outreachActivitiesNavActive
                    && !$technologyAdoptionNavActive
                    && !$requiredFilesNavActive;
                $proposalNavItemsActive = $newProposalActive || $requiredFilesNavActive
                    || $completedResearchesNavActive || $ongoingResearchesNavActive
                    || $researchOutputPublishedNavActive || $researchOutputPresentedNavActive || $commercializedNavActive
                    || $resultedInExtensionNavActive || $journalCitationNavActive || $bookCitationNavActive
                    || $inventionsUmCopyrightsNavActive || $linkagesNavActive || $consolidatedCompletedResearchesNavActive
                    || $consolidatedOngoingResearchesNavActive || $consolidatedResearchOutputPublishedNavActive
                    || $consolidatedResearchOutputPresentedNavActive || $consolidatedCommercializedNavActive
                    || $consolidatedResultedInExtensionNavActive || $consolidatedJournalCitationNavActive
                    || $consolidatedBookCitationNavActive || $consolidatedInventionsUmCopyrightsNavActive
                    || $consolidatedLinkagesNavActive
                    || $progressReportNavActive
                    || $trainingsConductedNavActive || $technicalAdvisoryNavActive || $extensionLinkagesNavActive
                    || $outreachActivitiesNavActive || $technologyAdoptionNavActive;
                $isFacultyAccount = \App\Core\Auth::hasRole('faculty');
                $hasDualProposalMenu = proposal_nav_has_dual_menu();
                $showResearchRequestsNav = \App\Support\MonitoringRoles::canFileResearchRequests();
                $researchRequestsNavActive = $showResearchRequestsNav && nav_active('research-requests');
                $showCompletedResearchesNav = \App\Support\MonitoringRoles::canAccessCompletedResearches();
                $showOngoingResearchesNav = \App\Support\MonitoringRoles::canAccessOngoingResearches();
                $showResearchOutputPublishedNav = \App\Support\MonitoringRoles::canAccessResearchOutputPublished();
                $showResearchOutputPresentedNav = \App\Support\MonitoringRoles::canAccessResearchOutputPresented();
                $showCommercializedNav = \App\Support\MonitoringRoles::canAccessCommercialized();
                $showResultedInExtensionNav = \App\Support\MonitoringRoles::canAccessResultedInExtension();
                $showJournalCitationNav = \App\Support\MonitoringRoles::canAccessJournalCitation();
                $showBookCitationNav = \App\Support\MonitoringRoles::canAccessBookCitation();
                $showInventionsUmCopyrightsNav = \App\Support\MonitoringRoles::canAccessInventionsUmCopyrights();
                $showLinkagesNav = \App\Support\MonitoringRoles::canAccessLinkages();
                $showConsolidatedCompletedResearchesNav = \App\Support\MonitoringRoles::canAccessConsolidatedCompletedResearches();
                $showConsolidatedOngoingResearchesNav = \App\Support\MonitoringRoles::canAccessConsolidatedOngoingResearches();
                $showConsolidatedResearchOutputPublishedNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchOutputPublished();
                $showConsolidatedResearchOutputPresentedNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchOutputPresented();
                $showConsolidatedCommercializedNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('commercialized');
                $showConsolidatedResultedInExtensionNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('resulted_in_extension');
                $showConsolidatedJournalCitationNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('journal_citation');
                $showConsolidatedBookCitationNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('book_citation');
                $showConsolidatedInventionsUmCopyrightsNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('inventions_um_copyrights');
                $showConsolidatedLinkagesNav = \App\Support\MonitoringRoles::canAccessConsolidatedResearchForm('linkages');
                $hideDirectorResearchFormNav = \App\Support\MonitoringRoles::isDirectorResearch();
                $showProgressReportNav = \App\Support\MonitoringRoles::canAccessProgressReport() && !$hideDirectorResearchFormNav;
                $showTerminalReportNav = \App\Support\MonitoringRoles::canAccessTerminalReport() && !$hideDirectorResearchFormNav;
                $showTerminalReportAssessmentFormNav = \App\Support\MonitoringRoles::canAccessTerminalReport() && !$hideDirectorResearchFormNav;
                $showTrainingsConductedNav = \App\Support\MonitoringRoles::canAccessTrainingsConducted();
                $showTechnicalAdvisoryNav = \App\Support\MonitoringRoles::canAccessTechnicalAdvisory();
                $showExtensionLinkagesNav = \App\Support\MonitoringRoles::canAccessExtensionLinkages();
                $showOutreachActivitiesNav = \App\Support\MonitoringRoles::canAccessOutreachActivities();
                $showTechnologyAdoptionNav = \App\Support\MonitoringRoles::canAccessTechnologyAdoption();
                $showResearchProposalNav = !\App\Support\MonitoringRoles::isVpride()
                    && !$hideDirectorResearchFormNav;
                if (\App\Support\MonitoringRoles::isCoordinatorResearch()) {
                    // The R&D Coordinator prepares the quarterly reports themselves (generated from Research
                    // Accomplishments), so the Consolidated versions are no longer offered to them.
                    $showConsolidatedCompletedResearchesNav = $showConsolidatedOngoingResearchesNav = false;
                    $showConsolidatedResearchOutputPublishedNav = $showConsolidatedResearchOutputPresentedNav = false;
                    $showConsolidatedCommercializedNav = $showConsolidatedResultedInExtensionNav = false;
                    $showConsolidatedJournalCitationNav = $showConsolidatedBookCitationNav = false;
                    $showConsolidatedInventionsUmCopyrightsNav = $showConsolidatedLinkagesNav = false;
                }
                $showResearchAccomplishmentsNav = $showResearchProposalNav
                    && \App\Support\MonitoringRoles::canEnterResearchAccomplishments();
                $researchAccomplishmentsNavActive = $showResearchAccomplishmentsNav && nav_active('research-accomplishments');
                $hideCoordinatorProposalIntroNav = \App\Support\MonitoringRoles::isCoordinator()
                    && !\App\Support\MonitoringRoles::isCoordinatorExtension();
                $terminalReportNavGroupActive = $hasDualProposalMenu
                    ? proposal_nav_scoped_active(
                        $progressReportNavActive || $terminalReportNavActive || $terminalReportAssessmentFormNavActive,
                        'research'
                    )
                    : ($terminalReportNavActive || $terminalReportAssessmentFormNavActive);
                $showConductedActivitiesNav = conducted_activities_in_extension_nav();
                $conductedActivitiesNavActive = $showConductedActivitiesNav && nav_active('activities');
                $researchMenuScope = (\App\Support\MonitoringRoles::isCoordinatorExtension()
                    || \App\Support\MonitoringRoles::isDirectorExtension()
                    || \App\Support\MonitoringRoles::isExtensionWorker())
                    ? 'extension'
                    : 'research';
                // Progress, Terminal and Terminal Report Assessment are research (VPRDE) forms: they sit in the research menu.
                $projectReportsInResearchMenu = $showTerminalReportNav && $showResearchProposalNav && $researchMenuScope === 'research';
                $researchProposalNavActive = ($hasDualProposalMenu
                    ? proposal_nav_scoped_active($proposalNavItemsActive, 'research')
                    : ($proposalNavItemsActive || $conductedActivitiesNavActive))
                    || $researchAccomplishmentsNavActive || $researchRequestsNavActive
                    || ($projectReportsInResearchMenu && $terminalReportNavGroupActive);
                $researchExtensionNavActive = $hasDualProposalMenu
                    && (proposal_nav_scoped_active($proposalNavItemsActive, 'extension') || $conductedActivitiesNavActive);
                ?>
                <?php if ($showResearchProposalNav): ?>
                <div class="nav-group nav-group-collapsible" id="research-proposal-nav" data-nav-collapsible>
                    <button type="button" class="nav-item nav-item-toggle<?= $researchProposalNavActive ? ' active' : '' ?>" id="research-proposal-nav-toggle" aria-expanded="true" aria-controls="research-proposal-submenu">
                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                        <span><?= htmlspecialchars(proposal_nav_group_label()) ?></span>
                        <i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-submenu" id="research-proposal-submenu">
                    <?php
                    $menuScope = $researchMenuScope;
                    require APP_PATH . '/views/layouts/_proposal-nav-submenu.php';
                    ?>
                    <?php if ($projectReportsInResearchMenu): ?>
                    <div class="nav-subgroup<?= $terminalReportNavGroupActive ? ' has-active' : '' ?>" id="project-reports-nav" data-nav-subgroup>
                        <button type="button" class="nav-subitem nav-subgroup-toggle<?= $terminalReportNavGroupActive ? ' is-current' : '' ?>" aria-expanded="true" aria-controls="project-reports-subitems">
                            <span>Project Reports</span>
                            <i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
                        </button>
                        <div class="nav-subgroup-items" id="project-reports-subitems">
                        <?php
                        $menuScope = 'research';
                        require APP_PATH . '/views/layouts/_terminal-report-nav-submenu.php';
                        ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($hasDualProposalMenu && $showResearchProposalNav): ?>
                <div class="nav-group nav-group-collapsible" id="research-extension-nav" data-nav-collapsible>
                    <button type="button" class="nav-item nav-item-toggle<?= $researchExtensionNavActive ? ' active' : '' ?>" id="research-extension-nav-toggle" aria-expanded="true" aria-controls="research-extension-submenu">
                        <i class="fas fa-hands-helping" aria-hidden="true"></i>
                        <span>Extension Services</span>
                        <i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-submenu" id="research-extension-submenu">
                    <?php
                    $menuScope = 'extension';
                    require APP_PATH . '/views/layouts/_proposal-nav-submenu.php';
                    ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($showTerminalReportNav && !$projectReportsInResearchMenu): ?>
                <div class="nav-group nav-group-collapsible" id="terminal-report-nav" data-nav-collapsible>
                    <button type="button" class="nav-item nav-item-toggle<?= $terminalReportNavGroupActive ? ' active' : '' ?>" id="terminal-report-nav-toggle" aria-expanded="true" aria-controls="terminal-report-submenu">
                        <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                        <span>Terminal Report</span>
                        <i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-submenu" id="terminal-report-submenu">
                    <?php
                    $menuScope = 'research';
                    require APP_PATH . '/views/layouts/_terminal-report-nav-submenu.php';
                    ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ((\App\Core\Auth::hasRole('ride_reporter') || \App\Support\MonitoringRoles::isDirectorExtension()) && !$isVprideAccount): ?>
                    <a href="<?= base_url('reports/extension-beneficiaries') ?>" class="nav-item<?= nav_active('reports') ? ' active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> <span>Reports</span>
                    </a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (\App\Core\Auth::hasRole('ride_admin')): ?>
                    <a href="<?= base_url('admin/accounts') ?>" class="nav-item<?= nav_active('admin/accounts') ? ' active' : '' ?>">
                        <i class="fas fa-users"></i> <span>Accounts</span>
                    </a>
                    <a href="<?= base_url('admin/programs') ?>" class="nav-item<?= nav_active('admin/programs') ? ' active' : '' ?>">
                        <i class="fas fa-sitemap"></i> <span>Programs</span>
                    </a>
                    <a href="<?= base_url('admin/scholarly') ?>" class="nav-item<?= nav_active('admin/scholarly') ? ' active' : '' ?>">
                        <i class="fas fa-book-open"></i> <span>Faculty Papers</span>
                    </a>
                    <a href="<?= base_url('admin/public-site') ?>" class="nav-item<?= nav_active('admin/public-site') ? ' active' : '' ?>">
                        <i class="fas fa-globe"></i> <span>Public Site</span>
                    </a>
                    <a href="<?= base_url('admin/highlights') ?>" class="nav-item<?= nav_active('admin/highlights') ? ' active' : '' ?>">
                        <i class="fas fa-images"></i> <span>Login Highlights</span>
                    </a>
                    <a href="<?= base_url('admin/form-templates') ?>" class="nav-item<?= nav_active('admin/form-templates') ? ' active' : '' ?>">
                        <i class="fas fa-file-upload"></i> <span>Form Templates</span>
                    </a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::hasRole('faculty')): ?>
                    <a href="<?= base_url('scholarly') ?>" class="nav-item<?= nav_active('scholarly') ? ' active' : '' ?>">
                        <i class="fas fa-graduation-cap"></i> <span>My Scholarly Output</span>
                    </a>
                <?php endif; ?>
                <?php if (\App\Core\Auth::hasRole('ride_admin')): ?>
                    <a href="<?= base_url('admin/accounts') ?>" class="nav-item">
                        <i class="fas fa-user-shield"></i> <span>RIDE Administrator</span>
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a class="profile-mini" href="<?= base_url('profile') ?>" title="Account settings"<?= nav_active('profile') ? ' aria-current="page"' : '' ?>>
                    <?php $sidebarAvatarUrl = user_avatar_url($u); ?>
                    <?php if ($sidebarAvatarUrl !== null): ?>
                        <img
                            src="<?= htmlspecialchars($sidebarAvatarUrl) ?>"
                            alt=""
                            class="avatar avatar-image"
                        >
                    <?php else: ?>
                        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                    <?php endif; ?>
                    <div class="profile-info">
                        <p><?= htmlspecialchars($displayName) ?></p>
                        <small><?= htmlspecialchars((string) $u['email']) ?></small>
                        <span class="profile-link-hint">Account settings</span>
                    </div>
                    <i class="fas fa-chevron-right profile-chevron" aria-hidden="true"></i>
                </a>
                <a href="<?= base_url('logout') ?>" class="logout-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </aside>
        <script>
        // Apply remembered menu state and scroll position before the first paint, so the sidebar does not jump.
        (function () {
            var sidebar = document.getElementById('app-sidebar');
            try {
                sidebar.querySelectorAll('[data-nav-collapsible]').forEach(function (group) {
                    if (localStorage.getItem('ride:nav:' + group.id.replace(/-nav$/, '') + '-collapsed') === '1') {
                        group.classList.add('is-collapsed');
                        var menu = group.querySelector('.nav-submenu');
                        if (menu) { menu.hidden = true; }
                        var t = group.querySelector('.nav-item-toggle');
                        if (t) { t.setAttribute('aria-expanded', 'false'); }
                    }
                });
                sidebar.querySelectorAll('[data-nav-subgroup]').forEach(function (group) {
                    if (localStorage.getItem('ride:nav:' + group.id + '-collapsed') === '1' && !group.classList.contains('has-active')) {
                        group.classList.add('is-collapsed');
                        var items = group.querySelector('.nav-subgroup-items');
                        if (items) { items.hidden = true; }
                        var t = group.querySelector('.nav-subgroup-toggle');
                        if (t) { t.setAttribute('aria-expanded', 'false'); }
                    }
                });
                var top = sessionStorage.getItem('ride:sidebar-scroll');
                if (top !== null) { sidebar.scrollTop = parseInt(top, 10) || 0; }
            } catch (e) {}
        })();
        </script>
        <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>

        <main class="main-content">
            <div class="top-bar">
                <button type="button" class="topbar-menu-btn" id="sidebar-open" aria-controls="app-sidebar" aria-expanded="false" title="Menu">
                    <i class="fas fa-bars" aria-hidden="true"></i><span class="sr-only">Open menu</span>
                </button>
                <div class="page-title">
                    <h1><?= htmlspecialchars($heading) ?></h1>
                    <?php if ($subtitle !== ''): ?>
                        <p><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                <div class="header-actions">
                    <a href="<?= base_url('messages') ?>" class="notification-bell header-messages<?= nav_active('messages') ? ' is-active' : '' ?>" title="Messages" aria-label="Messages<?= $unreadMessageNavCount > 0 ? ', ' . $unreadMessageNavCount . ' unread' : '' ?>">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <?php if ($unreadMessageNavCount > 0): ?>
                            <span class="badge-new"><?= $unreadMessageNavCount > 99 ? '99+' : $unreadMessageNavCount ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if ($headerUnread > 0): ?>
                        <a href="<?= base_url('dashboard') ?>" class="notification-bell" title="Notifications">
                            <i class="fas fa-bell"></i>
                            <span class="badge-new"><?= $headerUnread ?></span>
                        </a>
                    <?php else: ?>
                        <div class="notification-bell" title="Notifications">
                            <i class="fas fa-bell"></i>
                        </div>
                    <?php endif; ?>
                    <a href="<?= base_url('profile') ?>" class="user-welcome" title="Account Settings">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($roleLabel) ?>
                    </a>
                </div>
            </div>

            <div class="page-body">
                <?php if ($msg = flash('success')): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if (isset($pageContent)): ?>
                    <?= $pageContent ?>
                <?php else: ?>
                    <?php /** @var string $contentView */ ?>
                    <?php require $contentView; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
<?php else: ?>
    <?php
    $authFlashSuccess = flash('success');
    $authFlashError = flash('error');
    ?>
    <?php if ($isAuthStandalone): ?>
        <?php if (isset($pageContent)): ?>
            <?= $pageContent ?>
        <?php else: ?>
            <?php /** @var string $contentView */ ?>
            <?php require $contentView; ?>
        <?php endif; ?>
    <?php else: ?>
        <main class="auth-main">
            <?php if ($authFlashSuccess): ?>
                <div class="alert alert-success"><?= htmlspecialchars($authFlashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($authFlashError): ?>
                <div class="alert alert-error"><?= htmlspecialchars($authFlashError) ?></div>
            <?php endif; ?>
            <?php if (isset($pageContent)): ?>
                <?= $pageContent ?>
            <?php else: ?>
                <?php /** @var string $contentView */ ?>
                <?php require $contentView; ?>
            <?php endif; ?>
        </main>
    <?php endif; ?>
<?php endif; ?>
<?php if (!$isAuthStandalone): ?>
    <?php
    $appJsPath = BASE_PATH . '/public/assets/js/app.js';
    $appJsVersion = is_file($appJsPath) ? (string) filemtime($appJsPath) : '1';
    ?>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= htmlspecialchars($appJsVersion) ?>"></script>
    <?php $lpJsPath = BASE_PATH . '/public/assets/js/location-picker.js'; ?>
    <script src="<?= base_url('assets/js/location-picker.js') ?>?v=<?= is_file($lpJsPath) ? filemtime($lpJsPath) : 1 ?>" defer></script>
<?php endif; ?>
</body>
</html>
