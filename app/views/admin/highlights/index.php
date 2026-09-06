<?php
/** @var list<array<string, mixed>> $slides */
/** @var string $vision */
/** @var string $mission */

$pageTitle = 'Login Highlights — RIDE IMS';
$pageHeading = 'Login Highlights';
$pageSubtitle = 'Manage vision, mission, and picture activities shown on the public login page.';
?>

<style>
  .highlight-upload-hint { margin: 0.35rem 0 0; font-size: 0.82rem; color: #64748b; }
  .highlight-slide-list { display: flex; flex-direction: column; gap: 0.75rem; }
  .highlight-slide-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
  }
  .highlight-slide-thumb {
    width: 112px;
    height: 64px;
    border-radius: 6px;
    overflow: hidden;
    flex-shrink: 0;
    background: #e2e8f0;
  }
  .highlight-slide-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
  .highlight-slide-meta {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
  }
  .highlight-slide-meta strong {
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .highlight-slide-meta small {
    color: #64748b;
    font-size: 0.8rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .highlight-slide-actions {
    display: flex;
    gap: 0.35rem;
    flex-shrink: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .highlight-edit-panel {
    margin-top: 0.85rem;
    padding-top: 0.85rem;
    border-top: 1px solid #e2e8f0;
  }
  .badge-active { background: #d1fae5; color: #065f46; }
  .badge-hidden { background: #f1f5f9; color: #475569; }
  @media (max-width: 720px) {
    .highlight-slide-item { flex-wrap: wrap; }
    .highlight-slide-actions { width: 100%; }
  }
</style>

<div class="card" style="margin-bottom: 1.5rem;">
  <h2>Vision &amp; Mission</h2>
  <p class="muted">Shown in the left panel of the public login landing page.</p>
  <form method="post" action="<?= base_url('admin/highlights/settings') ?>">
    <?= csrf_field() ?>
    <label for="vision">Vision Statement</label>
    <textarea id="vision" name="vision" rows="4" required><?= htmlspecialchars($vision) ?></textarea>

    <label for="mission">Mission Statement</label>
    <textarea id="mission" name="mission" rows="4" required><?= htmlspecialchars($mission) ?></textarea>

    <button type="submit" class="btn btn-accent">Save Vision &amp; Mission</button>
  </form>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
  <h2>Upload Highlight Photos</h2>
  <p class="muted">Programs, activities, and announcements from the WPU RIDE Office appear in the login carousel. You can select multiple images at once.</p>
  <form method="post" action="<?= base_url('admin/highlights') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label for="images">Images <span class="required">*</span></label>
    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
    <p class="highlight-upload-hint">Select one or more images · JPEG, PNG, WebP, or GIF · Max 5 MB each · Recommended landscape (16:7)</p>
    <p id="highlight-file-count" class="highlight-upload-hint" hidden></p>

    <label for="title">Title</label>
    <input type="text" id="title" name="title" maxlength="200" placeholder="e.g. Research Week 2026">

    <label for="caption">Caption</label>
    <textarea id="caption" name="caption" rows="2" placeholder="Brief description of the activity or announcement"></textarea>
    <p class="highlight-upload-hint">Title and caption apply to all selected images. Edit each slide afterward if needed.</p>

    <button type="submit" class="btn btn-accent">Upload Slides</button>
  </form>
  <script>
    (function () {
      var input = document.getElementById('images');
      var countEl = document.getElementById('highlight-file-count');
      if (!input || !countEl) return;
      input.addEventListener('change', function () {
        var n = input.files ? input.files.length : 0;
        if (n === 0) {
          countEl.hidden = true;
          countEl.textContent = '';
          return;
        }
        countEl.hidden = false;
        countEl.textContent = n === 1 ? '1 image selected' : n + ' images selected';
      });
    })();
  </script>
</div>

<div class="card">
  <h2>Carousel Slides</h2>
  <?php if (empty($slides)): ?>
    <p class="muted">No carousel images yet. Upload your first slide above.</p>
  <?php else: ?>
    <p class="muted" style="margin-bottom: 0.85rem;">Use the arrows to reorder. Only active slides appear on the login page.</p>
    <div class="highlight-slide-list">
      <?php foreach ($slides as $index => $slide): ?>
        <?php
        $slideId = (int) $slide['id'];
        $isActive = (bool) $slide['is_active'];
        $editOpen = (int) ($_GET['edit'] ?? 0) === $slideId;
        ?>
        <div class="highlight-slide-item">
          <div class="highlight-slide-thumb">
            <img src="<?= htmlspecialchars((string) $slide['image_url']) ?>" alt="<?= htmlspecialchars((string) ($slide['title'] ?: 'Highlight slide')) ?>">
          </div>
          <div class="highlight-slide-meta">
            <strong><?= htmlspecialchars((string) ($slide['title'] ?: 'Untitled slide')) ?></strong>
            <small><?= htmlspecialchars((string) ($slide['caption'] ?: 'No caption')) ?></small>
            <span class="badge <?= $isActive ? 'badge-active' : 'badge-hidden' ?>">
              <?= $isActive ? 'Active' : 'Hidden' ?>
            </span>
          </div>
          <div class="highlight-slide-actions">
            <form method="post" action="<?= base_url('admin/highlights/' . $slideId . '/move') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="direction" value="-1">
              <button type="submit" class="btn btn-outline btn-sm" title="Move up" <?= $index === 0 ? 'disabled' : '' ?>>&#9650;</button>
            </form>
            <form method="post" action="<?= base_url('admin/highlights/' . $slideId . '/move') ?>" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="direction" value="1">
              <button type="submit" class="btn btn-outline btn-sm" title="Move down" <?= $index === count($slides) - 1 ? 'disabled' : '' ?>>&#9660;</button>
            </form>
            <a href="<?= base_url('admin/highlights?edit=' . $slideId) ?>" class="btn btn-outline btn-sm icon-btn" title="Edit slide" aria-label="Edit slide">
              <i class="fas fa-pencil-alt" aria-hidden="true"></i>
            </a>
            <form method="post" action="<?= base_url('admin/highlights/' . $slideId . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this highlight slide?');">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm icon-btn" title="Delete slide" aria-label="Delete slide">
                <i class="fas fa-trash-alt" aria-hidden="true"></i>
              </button>
            </form>
          </div>

          <?php if ($editOpen): ?>
            <div class="highlight-edit-panel" style="flex: 1 1 100%;">
              <form method="post" action="<?= base_url('admin/highlights/' . $slideId . '/update') ?>">
                <?= csrf_field() ?>
                <label for="edit_title_<?= $slideId ?>">Title</label>
                <input type="text" id="edit_title_<?= $slideId ?>" name="title" maxlength="200" value="<?= htmlspecialchars((string) ($slide['title'] ?? '')) ?>">

                <label for="edit_caption_<?= $slideId ?>">Caption</label>
                <textarea id="edit_caption_<?= $slideId ?>" name="caption" rows="2"><?= htmlspecialchars((string) ($slide['caption'] ?? '')) ?></textarea>

                <label style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;">
                  <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                  Show on login page
                </label>

                <div style="display:flex;gap:0.5rem;margin-top:0.85rem;">
                  <button type="submit" class="btn btn-accent btn-sm">Save changes</button>
                  <a href="<?= base_url('admin/highlights') ?>" class="btn btn-outline btn-sm">Cancel</a>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
