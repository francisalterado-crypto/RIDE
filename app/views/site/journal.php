<?php
/** @var array<string, string> $content */
/** @var list<array<string, mixed>> $posts */
/** @var array<string, mixed>|null $issue */
use App\Support\SiteContent;

$show = ['id' => 'about', 'index' => '01', 'kicker' => 'Journal', 'name' => 'The Palawan Scientist', 'text' => $content['journal_description'],
    'details' => SiteContent::details($content['journal_details']), 'url' => $content['journal_url'], 'page' => '', 'posts' => [], 'issue' => null];
?>
<section class="site-head">
    <div class="site-wrap">
        <p class="site-label">RIDE</p>
        <h1>Journal</h1>
    </div>
</section>

<section class="site-section">
    <div class="site-wrap">
        <?php require APP_PATH . '/views/site/_showcase.php'; ?>
    </div>
</section>

<?php if ($issue !== null): ?>
    <section class="site-section site-section--line" id="current-issue">
        <div class="site-wrap">
            <?php $articleLimit = 0; require APP_PATH . '/views/site/_journal-issue.php'; ?>
        </div>
    </section>
<?php endif; ?>

<section class="site-section site-section--tint">
    <div class="site-wrap site-narrow">
        <header class="site-section-head"><h2>Announcements</h2></header>
        <?php if ($posts === []): ?>
            <p class="site-note">No announcements yet.</p>
        <?php else: ?>
            <div class="site-rows">
                <?php foreach ($posts as $post): ?>
                    <?php require APP_PATH . '/views/site/_post-row.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
