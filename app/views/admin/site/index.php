<?php
/** @var list<array<string, mixed>> $posts */
/** @var string $kind */
/** @var array<string, string> $content */
use App\Models\SitePost;
use App\Support\SiteContent;

$e = static fn (?string $v): string => htmlspecialchars((string) $v);
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('') ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt" aria-hidden="true"></i> View public site</a>
    <a class="btn btn-accent" href="<?= base_url('admin/public-site/posts/new' . ($kind !== '' ? '?kind=' . $kind : '')) ?>"><i class="fas fa-plus" aria-hidden="true"></i> New post</a>
</div>

<section class="card list-card">
    <div class="list-card-head">
        <div>
            <h2>Posts</h2>
            <p><?= count($posts) ?> post<?= count($posts) === 1 ? '' : 's' ?></p>
        </div>
    </div>
    <nav class="tabs" aria-label="Post type">
        <a class="tab<?= $kind === '' ? ' active' : '' ?>" href="<?= base_url('admin/public-site') ?>">All</a>
        <?php foreach (SitePost::KINDS as $key => $label): ?>
            <a class="tab<?= $kind === $key ? ' active' : '' ?>" href="<?= base_url('admin/public-site?kind=' . $key) ?>"><?= $e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($posts === []): ?>
        <div class="empty-state">
            <i class="fas fa-newspaper empty-icon" aria-hidden="true"></i>
            <p>No posts yet. Use New post to add news, journal updates or conference announcements.</p>
        </div>
    <?php else: ?>
        <div class="data-table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Section</th>
                        <th>Event date</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('admin/public-site/posts/' . $post['id'] . '/edit') ?>"><?= $e($post['title']) ?></a>
                            <?php if (!empty($post['summary'])): ?><div class="data-table-muted"><?= $e(mb_strimwidth((string) $post['summary'], 0, 110, '...')) ?></div><?php endif; ?>
                        </td>
                        <td><?= $e(SitePost::label($post)) ?></td>
                        <td class="<?= SitePost::eventDates($post) === '' ? 'data-table-muted' : '' ?>"><?= $e(SitePost::eventDates($post) ?: '-') ?></td>
                        <td><span class="badge <?= $post['is_published'] ? 'badge-approved' : 'badge-draft' ?>"><?= $post['is_published'] ? 'Published' : 'Draft' ?></span></td>
                        <td class="data-table-muted nowrap"><?= $e(date('M j, Y', strtotime((string) $post['date']))) ?></td>
                        <td class="nowrap">
                            <?php if ($post['is_published']): ?>
                                <a class="btn btn-sm btn-outline icon-btn" href="<?= base_url('articles/' . $post['id']) ?>" target="_blank" rel="noopener" title="View on site" aria-label="View on site"><i class="fas fa-eye" aria-hidden="true"></i></a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline icon-btn" href="<?= base_url('admin/public-site/posts/' . $post['id'] . '/edit') ?>" title="Edit post" aria-label="Edit post"><i class="fas fa-pencil-alt" aria-hidden="true"></i></a>
                            <form method="post" action="<?= base_url('admin/public-site/posts/' . $post['id'] . '/delete') ?>" class="inline-action" onsubmit="return confirm('Delete this post?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger icon-btn" title="Delete post" aria-label="Delete post"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card list-card" id="site-texts">
    <div class="list-card-head">
        <div>
            <h2>Site texts</h2>
            <p>The tagline, the journal description and the conference descriptions and websites.</p>
        </div>
    </div>
    <form method="post" action="<?= base_url('admin/public-site/content') ?>" class="site-texts-form">
        <?= csrf_field() ?>
        <div class="site-texts-grid">
            <?php foreach (SiteContent::FIELDS as $field => [, $label, , $type]): ?>
                <div class="site-texts-field<?= $type === 'textarea' ? ' site-texts-field--wide' : '' ?>">
                    <label for="site-<?= $field ?>"><?= $e($label) ?></label>
                    <?php if ($type === 'textarea'): ?>
                        <textarea id="site-<?= $field ?>" name="<?= $field ?>" rows="3"><?= $e($content[$field]) ?></textarea>
                    <?php else: ?>
                        <input id="site-<?= $field ?>" name="<?= $field ?>" type="<?= in_array($type, ['url', 'email'], true) ? $type : 'text' ?>" value="<?= $e($content[$field]) ?>"<?= $type === 'url' ? ' placeholder="https://"' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="form-actions-row">
            <button type="submit" class="btn">Save texts</button>
        </div>
    </form>
</section>
