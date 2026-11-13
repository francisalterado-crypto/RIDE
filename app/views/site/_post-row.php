<?php
/** @var array<string, mixed> $post */
use App\Models\SitePost;

$rowDates = SitePost::eventDates($post);
$rowTime = strtotime((string) $post['date']);
?>
<a class="site-row" href="<?= base_url('articles/' . (int) $post['id']) ?>">
    <time class="site-row-date" datetime="<?= date('Y-m-d', $rowTime) ?>">
        <span><?= date('d', $rowTime) ?></span>
        <small><?= date('M', $rowTime) ?></small>
    </time>
    <span class="site-row-main">
        <span class="site-row-title"><?= htmlspecialchars((string) $post['title']) ?></span>
        <?php if ($rowDates !== '' || !empty($post['location'])): ?>
            <span class="site-row-meta"><?= htmlspecialchars(trim($rowDates . ($rowDates !== '' && !empty($post['location']) ? ', ' : '') . (string) ($post['location'] ?? ''))) ?></span>
        <?php endif; ?>
    </span>
</a>
