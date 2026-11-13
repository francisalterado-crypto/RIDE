<?php
/**
 * One publication or conference, shown with its full description and key details.
 *
 * @var array{id: string, index: string, kicker: string, name: string, text: string, details: list<array{0: string, 1: string}>,
 *            url: string, page: string, posts: list<array<string, mixed>>, issue?: array<string, mixed>|null} $show
 */
use App\Support\SiteContent;

$e = static fn (?string $v): string => htmlspecialchars((string) $v);
$paragraphs = array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', $show['text']) ?: [])));
$showIssue = $show['issue'] ?? null;
?>
<article class="site-show" id="<?= $e($show['id']) ?>">
    <div class="site-show-main">
        <p class="site-label"><span><?= $e($show['index']) ?></span><?= $e($show['kicker']) ?></p>
        <h3 class="site-show-name"><?= $e($show['name']) ?></h3>
        <?php foreach ($paragraphs as $paragraph): ?>
            <p class="site-show-text"><?= $e($paragraph) ?></p>
        <?php endforeach; ?>
        <div class="site-show-links">
            <?php if ($show['url'] !== ''): ?>
                <a class="site-button site-ext" href="<?= $e($show['url']) ?>" target="_blank" rel="noopener"><?= $e(SiteContent::host($show['url'])) ?></a>
            <?php endif; ?>
            <?php if ($show['page'] !== ''): ?>
                <a class="site-link" href="<?= $e($show['page']) ?>">Updates</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="site-show-side">
        <?php if ($show['details'] !== []): ?>
            <dl class="site-details">
                <?php foreach ($show['details'] as [$label, $value]): ?>
                    <div><dt><?= $e($label) ?></dt><dd><?= $e($value) ?></dd></div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>

        <?php if ($showIssue !== null): ?>
            <div class="site-issue-strip">
                <?php if ($showIssue['cover'] !== ''): ?>
                    <a class="site-issue-cover" href="<?= $e($showIssue['url']) ?>" target="_blank" rel="noopener">
                        <img src="<?= $e($showIssue['cover']) ?>" alt="Cover of <?= $e($showIssue['title']) ?>" loading="lazy" width="96" height="136">
                    </a>
                <?php endif; ?>
                <div class="site-issue-body">
                    <p class="site-label">Current issue</p>
                    <a class="site-issue-title site-ext" href="<?= $e($showIssue['url']) ?>" target="_blank" rel="noopener"><?= $e($showIssue['title']) ?></a>
                    <p class="site-issue-meta"><?= $showIssue['published'] !== '' ? 'Published ' . $e($showIssue['published']) . ' | ' : '' ?><?= count($showIssue['articles']) ?> articles</p>
                    <ul class="site-issue-list">
                        <?php foreach (array_slice($showIssue['articles'], 0, 3) as $article): ?>
                            <li><a href="<?= $e($article['url']) ?>" target="_blank" rel="noopener"><?= $e($article['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show['posts'] !== []): ?>
            <div class="site-rows">
                <?php foreach (array_slice($show['posts'], 0, 3) as $post): ?>
                    <?php require APP_PATH . '/views/site/_post-row.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</article>
