<?php
/** @var string $pageTitle */
/** @var string $pageContent */
// Public pages never link to the internal R&E portal (login, dashboard, registration).
$siteContent = \App\Support\SiteContent::all();
$wpuUrl = $siteContent['wpu_url'];
$path = trim(request_path(), '/');
$navItems = [
    '' => 'Home',
    'news' => 'News',
    'journal' => 'Journal',
    'conferences' => 'Conferences',
];
$isActive = static fn (string $key): bool => $key === '' ? $path === '' : ($path === $key || str_starts_with($path, $key . '/'));
$externalSites = array_filter([
    'The Palawan Scientist' => $siteContent['journal_url'],
    'ICWPS' => $siteContent['icwps_url'],
    'RIDE Conference Proceedings' => $siteContent['racp_url'],
    'Western Philippines University' => $wpuUrl,
]);
$siteCss = BASE_PATH . '/public/assets/css/site.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $pageTitle) ?></title>
    <meta name="description" content="RIDE news, The Palawan Scientist, ICWPS and RIDE Conference Proceedings. Western Philippines University.">
    <link rel="icon" type="image/png" href="<?= base_url('assets/images/ride-logo.png') ?>" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/site.css') ?>?v=<?= is_file($siteCss) ? filemtime($siteCss) : 1 ?>">
</head>
<body class="site">
<a class="site-skip" href="#main">Skip to content</a>
<header class="site-header">
    <div class="site-wrap site-header-inner">
        <a class="site-brand" href="<?= base_url('') ?>" aria-label="RIDE home">
            <img src="<?= base_url('assets/images/ride-logo.png') ?>" alt="" width="40" height="40">
            <span class="site-brand-text">
                <span class="site-brand-name">RIDE</span>
                <span class="site-brand-sub">Western Philippines University</span>
            </span>
        </a>
        <button class="site-nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span></span><span></span><span class="site-sr">Menu</span>
        </button>
        <nav class="site-nav" id="site-nav" aria-label="Main">
            <?php foreach ($navItems as $key => $label): ?>
                <a href="<?= base_url($key) ?>"<?= $isActive($key) ? ' aria-current="page"' : '' ?>><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
            <?php if ($wpuUrl !== ''): ?>
                <a class="site-nav-ext site-ext" href="<?= htmlspecialchars($wpuUrl) ?>" target="_blank" rel="noopener">WPU</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main">
    <?= $pageContent ?>
</main>

<footer class="site-footer">
    <picture class="site-footer-jeep" aria-hidden="true">
        <source srcset="<?= base_url('assets/images/ride-jeep.webp') ?>" type="image/webp">
        <img src="<?= base_url('assets/images/ride-jeep.png') ?>" alt="" width="1200" height="534" loading="lazy">
    </picture>
    <div class="site-wrap site-footer-inner">
        <div class="site-footer-about">
            <div class="site-footer-brand">
                <img src="<?= base_url('assets/images/ride-logo.png') ?>" alt="" width="36" height="36">
                <span>
                    <strong>RIDE</strong>
                    <small>Research, Innovation, Development and Extension</small>
                </span>
            </div>
            <?php if ($siteContent['about_text'] !== ''): ?>
                <p class="site-footer-text"><?= htmlspecialchars($siteContent['about_text']) ?></p>
            <?php endif; ?>
        </div>
        <nav class="site-footer-col" aria-label="Pages">
            <h2>Pages</h2>
            <?php foreach ($navItems as $key => $label): ?>
                <a href="<?= base_url($key) ?>"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <nav class="site-footer-col" aria-label="Websites">
            <h2>Websites</h2>
            <?php foreach ($externalSites as $label => $url): ?>
                <a class="site-ext" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="site-footer-col site-footer-contact">
            <h2>Contact</h2>
            <?php if ($siteContent['contact_address'] !== ''): ?>
                <address><?= nl2br(htmlspecialchars($siteContent['contact_address'])) ?></address>
            <?php endif; ?>
            <?php if ($siteContent['contact_email'] !== ''): ?>
                <a href="mailto:<?= htmlspecialchars($siteContent['contact_email']) ?>"><?= htmlspecialchars($siteContent['contact_email']) ?></a>
            <?php endif; ?>
            <?php if ($siteContent['contact_phone'] !== ''): ?>
                <a href="tel:<?= htmlspecialchars((string) preg_replace('/[^0-9+]/', '', $siteContent['contact_phone'])) ?>"><?= htmlspecialchars($siteContent['contact_phone']) ?></a>
            <?php endif; ?>
            <?php if ($siteContent['contact_facebook_url'] !== ''): ?>
                <a class="site-ext" href="<?= htmlspecialchars($siteContent['contact_facebook_url']) ?>" target="_blank" rel="noopener">Facebook</a>
            <?php endif; ?>
        </div>
        <p class="site-footer-base">&copy; <?= date('Y') ?> RIDE, Western Philippines University</p>
    </div>
</footer>

<script>
(() => {
    const toggle = document.querySelector('.site-nav-toggle');
    const nav = document.getElementById('site-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const open = toggle.getAttribute('aria-expanded') !== 'true';
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            nav.classList.toggle('is-open', open);
        });
    }
})();
</script>
</body>
</html>
