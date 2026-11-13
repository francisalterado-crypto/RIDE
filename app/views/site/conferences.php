<?php
/** @var array<string, array<string, mixed>> $conferences */
$e = static fn (?string $v): string => htmlspecialchars((string) $v);
$n = 0;
?>
<section class="site-head">
    <div class="site-wrap">
        <p class="site-label">RIDE</p>
        <h1>Conferences</h1>
        <nav class="site-jump" aria-label="Conferences">
            <?php foreach ($conferences as $key => $conf): ?>
                <a href="#<?= $key ?>"><?= $e($conf['short']) ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
</section>

<section class="site-section">
    <div class="site-wrap">
        <?php foreach ($conferences as $key => $conf): $n++; ?>
            <?php
            $show = ['id' => $key, 'index' => str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'kicker' => $key === 'racp' ? 'Proceedings' : 'Conference',
                'name' => $conf['name'], 'text' => $conf['text'], 'details' => $conf['details'], 'url' => $conf['url'], 'page' => '',
                'posts' => $conf['posts']];
            require APP_PATH . '/views/site/_showcase.php';
            ?>
        <?php endforeach; ?>
    </div>
</section>
