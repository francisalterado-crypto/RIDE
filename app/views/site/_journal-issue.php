<?php
/**
 * Table of contents of the current issue of The Palawan Scientist (read from the journal's OJS site).
 *
 * @var array{title: string, url: string, cover: string, published: string, articles: list<array{title: string, url: string, authors: string}>} $issue
 * @var int $articleLimit 0 = all
 */
$articleLimit = $articleLimit ?? 0;
$issueArticles = $articleLimit > 0 ? array_slice($issue['articles'], 0, $articleLimit) : $issue['articles'];
$e = static fn (?string $v): string => htmlspecialchars((string) $v);
?>
<div class="site-toc">
    <div class="site-toc-head">
        <?php if ($issue['cover'] !== ''): ?>
            <a class="site-issue-cover site-issue-cover--large" href="<?= $e($issue['url']) ?>" target="_blank" rel="noopener">
                <img src="<?= $e($issue['cover']) ?>" alt="Cover of <?= $e($issue['title']) ?>" loading="lazy" width="150" height="212">
            </a>
        <?php endif; ?>
        <div>
            <p class="site-label">Current issue</p>
            <h2><?= $e($issue['title']) ?></h2>
            <p class="site-issue-meta"><?= $issue['published'] !== '' ? 'Published ' . $e($issue['published']) . ' | ' : '' ?><?= count($issue['articles']) ?> articles</p>
            <a class="site-button site-ext" href="<?= $e($issue['url']) ?>" target="_blank" rel="noopener">Read this issue</a>
        </div>
    </div>
    <ol class="site-toc-list">
        <?php foreach ($issueArticles as $article): ?>
            <li>
                <a href="<?= $e($article['url']) ?>" target="_blank" rel="noopener"><?= $e($article['title']) ?></a>
                <?php if ($article['authors'] !== ''): ?><span><?= $e($article['authors']) ?></span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</div>
