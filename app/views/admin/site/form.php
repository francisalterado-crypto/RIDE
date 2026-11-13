<?php
/** @var array<string, mixed>|null $post */
/** @var array<string, mixed> $values */
use App\Models\SitePost;

$e = static fn ($v): string => htmlspecialchars((string) ($v ?? ''));
$isEdit = $post !== null;
$kind = strtolower((string) ($values['kind'] ?? 'news'));
$series = strtolower((string) ($values['series'] ?? ''));
$action = $isEdit ? base_url('admin/public-site/posts/' . $post['id']) : base_url('admin/public-site/posts');
?>
<div class="page-actions-bar">
    <a class="btn btn-outline" href="<?= base_url('admin/public-site') ?>">Back to Public Site</a>
</div>

<form class="webform site-post-form" method="post" action="<?= $action ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">1</span>
            <div>
                <h2>Post</h2>
                <p>Fields marked <span class="webform-req">*</span> are required.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="post-kind">Section <span class="webform-req">*</span></label>
                <select id="post-kind" name="kind" required>
                    <?php foreach (SitePost::KINDS as $key => $label): ?>
                        <option value="<?= $key ?>"<?= $kind === $key ? ' selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="webform-field" data-show-for="conference"<?= $kind === 'conference' ? '' : ' hidden' ?>>
                <label for="post-series">Conference <span class="webform-req">*</span></label>
                <select id="post-series" name="series">
                    <option value="">Select</option>
                    <?php foreach (SitePost::SERIES as $key => $label): ?>
                        <option value="<?= $key ?>"<?= $series === $key ? ' selected' : '' ?>><?= $e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="webform-field webform-field--full">
                <label for="post-title">Title <span class="webform-req">*</span></label>
                <input id="post-title" name="title" value="<?= $e($values['title'] ?? '') ?>" maxlength="255" required>
            </div>
            <div class="webform-field webform-field--full">
                <label for="post-summary">Summary</label>
                <textarea id="post-summary" name="summary" rows="2" maxlength="600" placeholder="One or two sentences shown on the cards."><?= $e($values['summary'] ?? '') ?></textarea>
            </div>
            <div class="webform-field webform-field--full">
                <label for="post-body">Full text</label>
                <textarea id="post-body" name="body" rows="10" placeholder="Leave a blank line between paragraphs."><?= $e($values['body'] ?? '') ?></textarea>
            </div>
        </div>
    </section>

    <section class="webform-card">
        <header class="webform-card-head">
            <span class="webform-card-num">2</span>
            <div>
                <h2>Event, link and image</h2>
                <p>For conferences, issues and calls for papers. All optional.</p>
            </div>
        </header>
        <div class="webform-grid">
            <div class="webform-field">
                <label for="post-event-date">Event or deadline date</label>
                <input id="post-event-date" type="date" name="event_date" value="<?= $e($values['event_date'] ?? '') ?>">
            </div>
            <div class="webform-field">
                <label for="post-event-end">End date</label>
                <input id="post-event-end" type="date" name="event_end_date" value="<?= $e($values['event_end_date'] ?? '') ?>">
            </div>
            <div class="webform-field webform-field--full">
                <label for="post-location">Venue</label>
                <input id="post-location" name="location" value="<?= $e($values['location'] ?? '') ?>" maxlength="255" placeholder="Venue, city or online">
            </div>
            <div class="webform-field">
                <label for="post-link">Link</label>
                <input id="post-link" type="url" name="link_url" value="<?= $e($values['link_url'] ?? '') ?>" placeholder="https://">
            </div>
            <div class="webform-field">
                <label for="post-link-label">Link button text</label>
                <input id="post-link-label" name="link_label" value="<?= $e($values['link_label'] ?? '') ?>" maxlength="80" placeholder="e.g. Submit a paper">
            </div>
            <div class="webform-field webform-field--full">
                <label for="post-image">Cover image</label>
                <?php if ($isEdit && $post['image_url'] !== ''): ?>
                    <img class="site-post-form-thumb" src="<?= $e($post['image_url']) ?>" alt="Current cover image">
                    <label class="check-row"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
                <?php endif; ?>
                <input id="post-image" type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                <small class="webform-hint">JPG, PNG, WebP or GIF, up to 5 MB. A wide (16:9) picture looks best.</small>
            </div>
        </div>
    </section>

    <div class="webform-actionbar">
        <a class="btn btn-outline" href="<?= base_url('admin/public-site') ?>">Cancel</a>
        <button type="submit" class="btn btn-outline" name="status" value="draft">Save as draft</button>
        <button type="submit" class="btn" name="status" value="published"><?= $isEdit && $post['is_published'] ? 'Save' : 'Publish' ?></button>
    </div>
</form>

<script>
(() => {
    const kind = document.getElementById('post-kind');
    const apply = () => document.querySelectorAll('[data-show-for]').forEach((el) => {
        const show = el.dataset.showFor === kind.value;
        el.hidden = !show;
        el.querySelectorAll('select, input').forEach((input) => { input.disabled = !show; });
    });
    kind.addEventListener('change', apply);
    apply();
})();
</script>
