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
<body class="<?= \App\Core\Auth::check() ? 'app-body' : ($isAuthStandalone ? 'auth-body auth-standalone' : 'auth-body') ?>">
<?php if (\App\Core\Auth::check()): ?>
    <?php
    $u = \App\Core\Auth::user();
    $heading = $pageHeading ?? explode(' — ', (string) $pageTitle)[0];
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
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-area">
                    <div class="logo-icon">
                        <img src="<?= base_url('assets/images/ride-logo.png') ?>" alt="RIDE — Research, Innovation, Development and Extension" width="40" height="40">
                    </div>
                    <div class="logo-text">
                        <h2>RIDE</h2>
                        <span>Research &amp; Extension</span>
                    </div>
                </div>
            </div>
            <nav class="nav-menu">
                <?php $isVprideAccount = \App\Support\MonitoringRoles::isVpride(); ?>
                <a href="<?= base_url('dashboard') ?>" class="nav-item<?= nav_active('dashboard') ? ' active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                </a>
                <a href="<?= base_url('messages') ?>" class="nav-item<?= nav_active('messages') ? ' active' : '' ?>">
                    <i class="fas fa-envelope-open-text"></i>
                    <span>Message</span>
                    <?php if ($unreadMessageNavCount > 0): ?>
                        <span class="nav-mark-count"><?= $unreadMessageNavCount > 99 ? '99+' : $unreadMessageNavCount ?></span>
                    <?php endif; ?>
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
                </div>
                <div class="nav-section">
                    <div class="nav-section-label">Monitoring</div>
                    <a href="<?= base_url('monitoring?scope=research') ?>" class="nav-item<?= monitoring_nav_active('research') ? ' active' : '' ?>">
                        <i class="fas fa-flask"></i> <span>Research Monitoring</span>
                    </a>
                    <a href="<?= base_url('monitoring?scope=extension') ?>" class="nav-item<?= monitoring_nav_active('extension') ? ' active' : '' ?>">
                        <i class="fas fa-hands-helping"></i> <span>Extension Monitoring</span>
                    </a>
                    <?php if (\App\Support\FacultyFormDeadlines::canManage()): ?>
                    <a href="<?= base_url('settings/faculty-deadlines') ?>" class="nav-item<?= nav_active('settings/faculty-deadlines') ? ' active' : '' ?>">
                        <i class="fas fa-calendar-check"></i> <span>Form Deadlines</span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <?php if (\App\Support\MonitoringRoles::isStaff()): ?>
                    <a href="<?= base_url('monitoring') ?>" class="nav-item<?= nav_active('monitoring') ? ' active' : '' ?>">
                        <i class="fas fa-chart-line"></i> <span>Monitoring</span>
                    </a>
                <?php endif; ?>
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
                <?php
                $newProposalActive = nav_active('proposals/create');
                $manuscriptNavActive = nav_active('proposals/create/manuscript');
                $completedResearchesNavActive = nav_active('proposals/create/completed-researches');
                $ongoingResearchesNavActive = nav_active('proposals/create/ongoing-researches');
                $researchOutputPublishedNavActive = nav_active('proposals/create/research-output-published');
                $researchOutputPresentedNavActive = nav_active('proposals/create/research-output-presented');
                $commercializedNavActive = nav_active('proposals/create/commercialized');
                $resultedInExtensionNavActive = nav_active('proposals/create/resulted-in-extension');
                $journalCitationNavActive = nav_active('proposals/create/journal-citation');
                $bookCitationNavActive = nav_active('proposals/create/book-citation');
                $inventionsUmCopyrightsNavActive = nav_active('proposals/create/inventions-um-copyrights');
                $linkagesNavActive = nav_active('proposals/create/linkages');
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
                $obrMatrixNavActive = nav_active('proposals/create/obr-matrix');
                $trainingsConductedNavActive = nav_active('proposals/create/trainings-conducted');
                $technicalAdvisoryNavActive = nav_active('proposals/create/technical-advisory');
                $extensionLinkagesNavActive = nav_active('proposals/create/extension-linkages');
                $outreachActivitiesNavActive = nav_active('proposals/create/outreach-activities');
                $technologyAdoptionNavActive = nav_active('proposals/create/technology-adoption');
                $accomplishmentReportNavActive = nav_active('proposals/create/accomplishment-report');
                $technicalAdvisoryArNavActive = nav_active('proposals/create/technical-advisory-ar');
                $outreachActivitiesArNavActive = nav_active('proposals/create/outreach-activities-ar');
                $ebalwasyonNgGawainNavActive = nav_active('proposals/create/ebalwasyon-ng-gawain');
                $attendanceSheetNavActive = nav_active('proposals/create/attendance-sheet');
                $requiredFilesNavActive = nav_active('proposals/create/required-files')
                    || preg_match('#^proposals/\d+/edit/required-files$#', request_path()) === 1;
                $applicantNavActive = nav_active('proposals/create')
                    && !$manuscriptNavActive
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
                    && !$obrMatrixNavActive
                    && !$trainingsConductedNavActive
                    && !$technicalAdvisoryNavActive
                    && !$extensionLinkagesNavActive
                    && !$outreachActivitiesNavActive
                    && !$technologyAdoptionNavActive
                    && !$accomplishmentReportNavActive
                    && !$technicalAdvisoryArNavActive
                    && !$outreachActivitiesArNavActive
                    && !$ebalwasyonNgGawainNavActive
                    && !$attendanceSheetNavActive
                    && !$requiredFilesNavActive;
                $proposalNavItemsActive = $newProposalActive || $requiredFilesNavActive
                    || $manuscriptNavActive || $completedResearchesNavActive || $ongoingResearchesNavActive
                    || $researchOutputPublishedNavActive || $researchOutputPresentedNavActive || $commercializedNavActive
                    || $resultedInExtensionNavActive || $journalCitationNavActive || $bookCitationNavActive
                    || $inventionsUmCopyrightsNavActive || $linkagesNavActive || $consolidatedCompletedResearchesNavActive
                    || $consolidatedOngoingResearchesNavActive || $consolidatedResearchOutputPublishedNavActive
                    || $consolidatedResearchOutputPresentedNavActive || $consolidatedCommercializedNavActive
                    || $consolidatedResultedInExtensionNavActive || $consolidatedJournalCitationNavActive
                    || $consolidatedBookCitationNavActive || $consolidatedInventionsUmCopyrightsNavActive
                    || $consolidatedLinkagesNavActive
                    || $progressReportNavActive
                    || $obrMatrixNavActive
                    || $trainingsConductedNavActive || $technicalAdvisoryNavActive || $extensionLinkagesNavActive
                    || $outreachActivitiesNavActive || $technologyAdoptionNavActive || $accomplishmentReportNavActive
                    || $technicalAdvisoryArNavActive || $outreachActivitiesArNavActive || $ebalwasyonNgGawainNavActive
                    || $attendanceSheetNavActive;
                $isFacultyAccount = \App\Core\Auth::hasRole('faculty');
                $showManuscriptNav = \App\Support\MonitoringRoles::canAccessManuscript();
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
                $showObrMatrixNav = \App\Support\MonitoringRoles::canAccessObrMatrix();
                $showTrainingsConductedNav = \App\Support\MonitoringRoles::canAccessTrainingsConducted();
                $showTechnicalAdvisoryNav = \App\Support\MonitoringRoles::canAccessTechnicalAdvisory();
                $showExtensionLinkagesNav = \App\Support\MonitoringRoles::canAccessExtensionLinkages();
                $showOutreachActivitiesNav = \App\Support\MonitoringRoles::canAccessOutreachActivities();
                $showTechnologyAdoptionNav = \App\Support\MonitoringRoles::canAccessTechnologyAdoption();
                $showAccomplishmentReportNav = \App\Support\MonitoringRoles::canAccessAccomplishmentReport();
                $showTechnicalAdvisoryArNav = \App\Support\MonitoringRoles::canAccessTechnicalAdvisoryAr();
                $showOutreachActivitiesArNav = \App\Support\MonitoringRoles::canAccessOutreachActivitiesAr();
                $showEbalwasyonNgGawainNav = \App\Support\MonitoringRoles::canAccessEbalwasyonNgGawain();
                $showAttendanceSheetNav = \App\Support\MonitoringRoles::canAccessAttendanceSheet();
                $showConsolidatedReportsNav = \App\Support\MonitoringRoles::isCoordinatorResearch();
                $showResearchProposalNav = !\App\Support\MonitoringRoles::isVpride()
                    && !$showConsolidatedReportsNav
                    && !$hideDirectorResearchFormNav;
                $disableManuscriptHighlight = \App\Support\MonitoringRoles::isCoordinator();
                $hideCoordinatorProposalIntroNav = \App\Support\MonitoringRoles::isCoordinator();
                $consolidatedReportsNavActive = $consolidatedOngoingResearchesNavActive
                    || $consolidatedResearchOutputPublishedNavActive
                    || $consolidatedResearchOutputPresentedNavActive
                    || $consolidatedCommercializedNavActive
                    || $consolidatedResultedInExtensionNavActive
                    || $consolidatedJournalCitationNavActive
                    || $consolidatedBookCitationNavActive
                    || $consolidatedInventionsUmCopyrightsNavActive
                    || $consolidatedLinkagesNavActive
                    || $consolidatedCompletedResearchesNavActive
                    || $obrMatrixNavActive;
                $terminalReportNavGroupActive = $isFacultyAccount
                    ? proposal_nav_scoped_active(
                        $progressReportNavActive || $terminalReportNavActive || $terminalReportAssessmentFormNavActive,
                        'research'
                    )
                    : ($terminalReportNavActive || $terminalReportAssessmentFormNavActive);
                $researchProposalNavActive = $isFacultyAccount
                    ? proposal_nav_scoped_active($proposalNavItemsActive, 'research')
                    : $proposalNavItemsActive;
                $researchExtensionNavActive = $isFacultyAccount
                    && proposal_nav_scoped_active($proposalNavItemsActive, 'extension');
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
                    $menuScope = (\App\Support\MonitoringRoles::isCoordinatorExtension()
                        || \App\Support\MonitoringRoles::isDirectorExtension())
                        ? 'extension'
                        : 'research';
                    require APP_PATH . '/views/layouts/_proposal-nav-submenu.php';
                    ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($showConsolidatedReportsNav): ?>
                <div class="nav-group nav-group-collapsible" id="consolidated-reports-nav" data-nav-collapsible>
                    <button type="button" class="nav-item nav-item-toggle<?= $consolidatedReportsNavActive ? ' active' : '' ?>" id="consolidated-reports-nav-toggle" aria-expanded="true" aria-controls="consolidated-reports-submenu">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        <span>Consolidated Reports</span>
                        <i class="fas fa-chevron-down nav-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="nav-submenu" id="consolidated-reports-submenu">
                    <?php
                    $menuScope = 'research';
                    require APP_PATH . '/views/layouts/_consolidated-reports-nav-submenu.php';
                    ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($showTerminalReportNav): ?>
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
                <?php if ($isFacultyAccount && $showResearchProposalNav): ?>
                <div class="nav-group nav-group-collapsible" id="research-extension-nav" data-nav-collapsible>
                    <button type="button" class="nav-item nav-item-toggle<?= $researchExtensionNavActive ? ' active' : '' ?>" id="research-extension-nav-toggle" aria-expanded="true" aria-controls="research-extension-submenu">
                        <i class="fas fa-hands-helping" aria-hidden="true"></i>
                        <span>Research Extension</span>
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
                <?php if (\App\Core\Auth::hasRole('ride_reporter') && !$isVprideAccount): ?>
                    <a href="<?= base_url('reports/extension-beneficiaries') ?>" class="nav-item<?= nav_active('reports') ? ' active' : '' ?>">
                        <i class="fas fa-chart-pie"></i> <span>Reports</span>
                    </a>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (\App\Core\Auth::hasRole('ride_admin')): ?>
                    <a href="<?= base_url('admin/accounts') ?>" class="nav-item<?= nav_active('admin/accounts') ? ' active' : '' ?>">
                        <i class="fas fa-users"></i> <span>Accounts</span>
                    </a>
                    <a href="<?= base_url('admin/scholarly') ?>" class="nav-item<?= nav_active('admin/scholarly') ? ' active' : '' ?>">
                        <i class="fas fa-book-open"></i> <span>Faculty Papers</span>
                    </a>
                    <a href="<?= base_url('admin/highlights') ?>" class="nav-item<?= nav_active('admin/highlights') ? ' active' : '' ?>">
                        <i class="fas fa-images"></i> <span>Login Highlights</span>
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
                <div class="profile-mini">
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
                    </div>
                </div>
                <a href="<?= base_url('logout') ?>" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="page-title">
                    <h1><?= htmlspecialchars($heading) ?></h1>
                    <?php if ($subtitle !== ''): ?>
                        <p><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                <div class="header-actions">
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
                    <a href="<?= base_url('profile') ?>" class="user-welcome">
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
<?php endif; ?>
</body>
</html>
