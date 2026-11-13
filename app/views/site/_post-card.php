<?php
/** @var array<string, mixed> $post */
use App\Models\SitePost;

$cardDates = SitePost::eventDates($post);
?>
<a class="site-card" href="<?= base_url('articles/' . (int) $post['id']) ?>">
    <span class="site-card-media site-card-media--<?= htmlspecialchars((string) $post['kind']) ?>">
        <?php if ($post['image_url'] !== ''): ?>
            <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="" loading="lazy">
        <?php endif; ?>
    </span>
    <span class="site-card-meta"><?= htmlspecialchars(SitePost::label($post)) ?> | <?= date('M j, Y', strtotime((string) $post['date'])) ?></span>
    <span class="site-card-title"><?= htmlspecialchars((string) $post['title']) ?></span>
    <?php if ($cardDates !== '' || !empty($post['location'])): ?>
        <span class="site-card-event"><?= htmlspecialchars(trim($cardDates . ($cardDates !== '' && !empty($post['location']) ? ', ' : '') . (string) ($post['location'] ?? ''))) ?></span>
    <?php endif; ?>
    <?php if (!empty($post['summary'])): ?><span class="site-card-summary"><?= htmlspecialchars((string) $post['summary']) ?></span><?php endif; ?>
</a>
