<?php
/** @var array<string, string> $content */
/** @var array{vision: string, mission: string} $aims */
/** @var list<array<string, mixed>> $highlights */
/** @var list<array<string, mixed>> $news */
/** @var list<array<string, mixed>> $journalPosts */
/** @var array<string, mixed>|null $issue current issue from the journal site */
/** @var array<string, array<string, mixed>> $conferences */
use App\Support\SiteContent;

$e = static fn (?string $v): string => htmlspecialchars((string) $v);
$firstParagraph = static fn (string $text): string => trim((string) (preg_split('/\R\s*\R/', trim($text))[0] ?? ''));

// Carousel: activity photos (Login Highlights) first, then the journal and the two conferences.
$slides = [];
foreach (array_slice($highlights, 0, 5) as $photo) {
    $slides[] = ['type' => 'photo', 'image' => $photo['image_url'], 'kicker' => 'RIDE', 'title' => (string) ($photo['title'] ?? ''), 'text' => (string) ($photo['caption'] ?? '')];
}
$slides[] = ['type' => 'blue', 'kicker' => 'Journal', 'title' => 'The Palawan Scientist', 'text' => $firstParagraph($content['journal_description']),
    'link' => base_url('journal'), 'label' => 'About the journal', 'ext' => $content['journal_url']];
$slides[] = ['type' => 'green', 'kicker' => 'Conference', 'title' => 'ICWPS', 'text' => $firstParagraph($content['icwps_description']),
    'link' => base_url('conferences#icwps'), 'label' => 'About the conference', 'ext' => $content['icwps_url']];
$slides[] = ['type' => 'gold', 'kicker' => 'Proceedings', 'title' => 'RIDE Conference Proceedings', 'text' => $firstParagraph($content['racp_description']),
    'link' => base_url('conferences#racp'), 'label' => 'About the proceedings', 'ext' => $content['racp_url']];

$showcases = [
    ['id' => 'journal', 'index' => '01', 'kicker' => 'Journal', 'name' => 'The Palawan Scientist', 'text' => $content['journal_description'],
        'details' => SiteContent::details($content['journal_details']), 'url' => $content['journal_url'], 'page' => base_url('journal'),
        'posts' => $journalPosts, 'issue' => $issue],
    ['id' => 'icwps', 'index' => '02', 'kicker' => 'Conference', 'name' => $conferences['icwps']['name'], 'text' => $conferences['icwps']['text'],
        'details' => $conferences['icwps']['details'], 'url' => $conferences['icwps']['url'], 'page' => base_url('conferences#icwps'),
        'posts' => $conferences['icwps']['posts']],
    ['id' => 'racp', 'index' => '03', 'kicker' => 'Proceedings', 'name' => 'RIDE Conference Proceedings', 'text' => $conferences['racp']['text'],
        'details' => $conferences['racp']['details'], 'url' => $conferences['racp']['url'], 'page' => base_url('conferences#racp'),
        'posts' => $conferences['racp']['posts']],
];
$areas = [
    'Research' => 'Studies by WPU faculty, staff and students.',
    'Innovation' => 'Technologies, inventions and intellectual property.',
    'Development' => 'Programs that put findings to use.',
    'Extension' => 'Training and services for communities.',
];
$slideCount = count($slides);
?>
<section class="site-carousel" aria-roledescription="carousel" aria-label="Highlights" data-carousel>
    <?php foreach ($slides as $i => $slide): ?>
        <div class="site-slide site-slide--<?= $e($slide['type']) ?><?= $i === 0 ? ' is-active' : '' ?>"
             role="group" aria-roledescription="slide" aria-label="<?= $i + 1 ?> of <?= $slideCount ?>"<?= $i === 0 ? '' : ' aria-hidden="true"' ?>>
            <?php if ($slide['type'] === 'photo'): ?>
                <img class="site-slide-bg" src="<?= $e($slide['image']) ?>" alt="<?= $e($slide['title'] ?: 'RIDE activity') ?>"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
            <?php endif; ?>
            <div class="site-wrap site-slide-copy">
                <p class="site-slide-kicker"><?= $e($slide['kicker']) ?></p>
                <?php if ($slide['title'] !== ''): ?><h2><?= $e($slide['title']) ?></h2><?php endif; ?>
                <?php if ($slide['text'] !== ''): ?><p class="site-slide-text"><?= $e($slide['text']) ?></p><?php endif; ?>
                <?php if (isset($slide['link'])): ?>
                    <div class="site-slide-links">
                        <a class="site-button site-button--light" href="<?= $slide['link'] ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?= $e($slide['label']) ?></a>
                        <?php if ($slide['ext'] !== ''): ?>
                            <a class="site-link site-link--light site-ext" href="<?= $e($slide['ext']) ?>" target="_blank" rel="noopener"<?= $i === 0 ? '' : ' tabindex="-1"' ?>><?= $e(SiteContent::host($slide['ext'])) ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php if ($slideCount > 1): ?>
        <div class="site-wrap site-carousel-bar">
            <span class="site-carousel-count"><span data-current>01</span> / <?= str_pad((string) $slideCount, 2, '0', STR_PAD_LEFT) ?></span>
            <span class="site-carousel-progress" aria-hidden="true"><span data-progress></span></span>
            <button class="site-carousel-btn site-carousel-btn--prev" type="button" aria-label="Previous slide" data-prev></button>
            <button class="site-carousel-btn" type="button" aria-label="Next slide" data-next></button>
        </div>
    <?php endif; ?>
</section>

<section class="site-areas" aria-label="Areas">
    <div class="site-wrap site-areas-grid">
        <?php foreach ($areas as $name => $line): ?>
            <div class="site-area">
                <span class="site-area-letter"><?= $name[0] ?></span>
                <h2><?= $e($name) ?></h2>
                <p><?= $e($line) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section" id="news">
    <div class="site-wrap">
        <header class="site-section-head">
            <h2>Latest news</h2>
            <a class="site-link" href="<?= base_url('news') ?>">All news</a>
        </header>
        <?php if ($news === []): ?>
            <p class="site-note">No news yet.</p>
        <?php else: ?>
            <div class="site-cards">
                <?php foreach (array_slice($news, 0, 3) as $post): ?>
                    <?php require APP_PATH . '/views/site/_post-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="site-section site-section--line" aria-label="Publications and conferences">
    <div class="site-wrap">
        <header class="site-section-head">
            <h2>Publications and conferences</h2>
        </header>
        <?php foreach ($showcases as $show): ?>
            <?php require APP_PATH . '/views/site/_showcase.php'; ?>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-section site-section--tint" aria-label="Vision and mission">
    <div class="site-wrap site-aims">
        <div>
            <p class="site-label">Vision</p>
            <p class="site-aim"><?= $e($aims['vision']) ?></p>
        </div>
        <div>
            <p class="site-label">Mission</p>
            <p class="site-aim"><?= $e($aims['mission']) ?></p>
        </div>
    </div>
</section>

<script>
(() => {
    const root = document.querySelector('[data-carousel]');
    if (!root) return;
    const slides = [...root.querySelectorAll('.site-slide')];
    if (slides.length < 2) return;
    const current = root.querySelector('[data-current]');
    const progress = root.querySelector('[data-progress]');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const delay = 6500;
    let index = 0;
    let timer = null;
    const restartBar = () => {
        progress.style.transition = 'none';
        progress.style.width = '0%';
        if (timer === null) return;
        requestAnimationFrame(() => requestAnimationFrame(() => {
            progress.style.transition = 'width ' + delay + 'ms linear';
            progress.style.width = '100%';
        }));
    };
    const show = (next) => {
        index = (next + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            const active = i === index;
            slide.classList.toggle('is-active', active);
            slide.toggleAttribute('aria-hidden', !active);
            slide.querySelectorAll('a').forEach((a) => (active ? a.removeAttribute('tabindex') : a.setAttribute('tabindex', '-1')));
        });
        current.textContent = String(index + 1).padStart(2, '0');
        restartBar();
    };
    const stop = () => { clearInterval(timer); timer = null; restartBar(); };
    const start = () => {
        if (reduce) return;
        clearInterval(timer);
        timer = setInterval(() => show(index + 1), delay);
        restartBar();
    };
    root.querySelector('[data-prev]').addEventListener('click', () => { show(index - 1); start(); });
    root.querySelector('[data-next]').addEventListener('click', () => { show(index + 1); start(); });
    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    let touchX = null;
    root.addEventListener('touchstart', (ev) => { touchX = ev.touches[0].clientX; }, { passive: true });
    root.addEventListener('touchend', (ev) => {
        if (touchX === null) return;
        const dx = ev.changedTouches[0].clientX - touchX;
        if (Math.abs(dx) > 40) { show(index + (dx < 0 ? 1 : -1)); start(); }
        touchX = null;
    });
    start();
})();
</script>
