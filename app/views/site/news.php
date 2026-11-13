<?php
/** @var list<array<string, mixed>> $posts */
?>
<section class="site-head">
    <div class="site-wrap">
        <p class="site-label">RIDE</p>
        <h1>News</h1>
    </div>
</section>

<section class="site-section">
    <div class="site-wrap">
        <?php if ($posts === []): ?>
            <p class="site-note">No news yet.</p>
        <?php else: ?>
            <div class="site-cards">
                <?php foreach ($posts as $post): ?>
                    <?php require APP_PATH . '/views/site/_post-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
