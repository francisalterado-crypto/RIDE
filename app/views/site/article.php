<?php
/** @var array<string, mixed> $post */
/** @var list<array<string, mixed>> $related */
use App\Models\SitePost;

$e = static fn (?string $v): string => htmlspecialchars((string) $v);
$back = match ($post['kind']) {
    'journal' => ['journal', 'Journal'],
    'conference' => ['conferences#' . $post['series'], 'Conferences'],
    default => ['news', 'News'],
};
$eventDates = SitePost::eventDates($post);
$article = $post;
?>
<article>
    <header class="site-head">
        <div class="site-wrap site-narrow">
            <a class="site-back" href="<?= base_url($back[0]) ?>"><?= $e($back[1]) ?></a>
            <p class="site-label"><?= $e(SitePost::label($article)) ?> | <?= $e(date('F j, Y', strtotime((string) $article['date']))) ?></p>
            <h1 class="site-head-title--article"><?= $e($article['title']) ?></h1>
            <?php if (!empty($article['summary'])): ?><p class="site-lead"><?= $e($article['summary']) ?></p><?php endif; ?>
        </div>
    </header>

    <div class="site-wrap site-narrow site-article">
        <?php if ($article['image_url'] !== ''): ?>
            <img class="site-article-image" src="<?= $e($article['image_url']) ?>" alt="">
        <?php endif; ?>

        <?php if ($eventDates !== '' || !empty($article['location'])): ?>
            <dl class="site-details">
                <?php if ($eventDates !== ''): ?><div><dt>Date</dt><dd><?= $e($eventDates) ?></dd></div><?php endif; ?>
                <?php if (!empty($article['location'])): ?><div><dt>Venue</dt><dd><?= $e($article['location']) ?></dd></div><?php endif; ?>
            </dl>
        <?php endif; ?>

        <div class="site-prose">
            <?php foreach (SitePost::paragraphs((string) ($article['body'] ?? '')) as $paragraph): ?>
                <p><?= nl2br($e($paragraph)) ?></p>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($article['link_url'])): ?>
            <p class="site-show-links"><a class="site-button site-ext" href="<?= $e($article['link_url']) ?>" target="_blank" rel="noopener"><?= $e($article['link_label'] ?: 'Open link') ?></a></p>
        <?php endif; ?>
    </div>
</article>

<?php if ($related !== []): ?>
    <section class="site-section site-section--tint">
        <div class="site-wrap">
            <header class="site-section-head"><h2>More <?= $e(strtolower($back[1])) ?></h2></header>
            <div class="site-cards">
                <?php foreach (array_slice($related, 0, 3) as $post): ?>
                    <?php require APP_PATH . '/views/site/_post-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
