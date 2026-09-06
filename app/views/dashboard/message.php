<?php
$user = $user ?? [];
$message = (string) ($message ?? '');
$messageHistory = isset($messageHistory) && is_array($messageHistory) ? $messageHistory : [];
$isAdmin = (bool) ($isAdmin ?? false);
$directMessagingEnabled = (bool) ($directMessagingEnabled ?? false);
$conversations = isset($conversations) && is_array($conversations) ? $conversations : [];
$facultyContacts = isset($facultyContacts) && is_array($facultyContacts) ? $facultyContacts : [];
$facultyRecipients = isset($facultyRecipients) && is_array($facultyRecipients) ? $facultyRecipients : [];
$directUnreadCount = (int) ($directUnreadCount ?? 0);
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$isFaculty = \App\Core\Auth::hasRole('faculty');
?>

<style>
  .message-window {
    background: #ffffff;
    border: 1px solid #e6edf5;
    border-left: 5px solid #2b5a8c;
    border-radius: 16px;
    box-shadow: 0 10px 24px rgba(0, 32, 64, 0.08);
    max-width: 900px;
    padding: 1.1rem 1.1rem 1.2rem;
  }
  .message-window .message-head {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    color: #1f3854;
    margin-bottom: 8px;
  }
  .message-window .message-body {
    color: #334e68;
    line-height: 1.55;
    font-size: 0.95rem;
  }
  .message-window .message-edit {
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid #e8eef5;
  }
  .message-window textarea {
    width: 100%;
    min-height: 110px;
    border: 1px solid #d3deea;
    border-radius: 10px;
    padding: 10px;
    font-family: inherit;
    resize: vertical;
  }
  .message-window .message-actions {
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;
  }
  .message-history {
    margin-top: 18px;
    border-top: 1px solid #e8eef5;
    padding-top: 12px;
  }
  .message-history h3 {
    color: #1f3854;
    font-size: 0.95rem;
    margin-bottom: 8px;
  }
  .message-history-item {
    background: #f8fbff;
    border: 1px solid #e4edf7;
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 8px;
  }
  .message-history-item .meta {
    font-size: 0.76rem;
    color: #5a7088;
    margin-bottom: 5px;
  }
  .message-history-item .text {
    color: #334e68;
    line-height: 1.5;
    font-size: 0.9rem;
  }
  .message-history-item .item-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 5px;
  }
  .message-history-item .item-head .meta {
    margin-bottom: 0;
  }
  .message-history-item .item-actions {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
  }
  .message-history-item .edit-panel {
    display: none;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #d3deea;
  }
  .message-history-item .edit-panel.is-open {
    display: block;
  }
  .message-history-item .edit-panel textarea {
    width: 100%;
    min-height: 90px;
    border: 1px solid #d3deea;
    border-radius: 10px;
    padding: 10px;
    font-family: inherit;
    resize: vertical;
  }
  .message-history-item .edit-panel .message-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
  }
  .dm-section {
    margin-top: 24px;
    max-width: 900px;
  }
  .dm-section h2 {
    font-size: 1.05rem;
    color: #1f3854;
    margin: 0 0 12px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .dm-contacts {
    display: grid;
    gap: 10px;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    margin-bottom: 18px;
  }
  .dm-contact-card {
    background: #fff;
    border: 1px solid #e6edf5;
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .dm-contact-card .label {
    font-weight: 600;
    color: #1f3854;
    font-size: 0.92rem;
  }
  .dm-contact-card .person {
    font-size: 0.84rem;
    color: #5a7088;
  }
  .dm-contact-card.unassigned {
    opacity: 0.75;
    background: #f8fafc;
  }
  .dm-conv-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }
  .dm-conv-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 14px;
    background: #fff;
    border: 1px solid #e6edf5;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: border-color 0.15s, box-shadow 0.15s;
  }
  .dm-conv-item:hover {
    border-color: #2b5a8c;
    box-shadow: 0 4px 12px rgba(43, 90, 140, 0.1);
  }
  .dm-conv-item .name {
    font-weight: 600;
    color: #1f3854;
  }
  .dm-conv-item .role {
    font-size: 0.78rem;
    color: #5a7088;
  }
  .dm-conv-item .preview {
    font-size: 0.84rem;
    color: #5a7088;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 420px;
  }
  .dm-conv-item .meta-right {
    text-align: right;
    flex-shrink: 0;
  }
  .dm-conv-item .time {
    font-size: 0.75rem;
    color: #5a7088;
  }
  .dm-unread-badge {
    display: inline-block;
    min-width: 20px;
    padding: 2px 7px;
    border-radius: 999px;
    background: #c53030;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    margin-top: 4px;
  }
  .dm-faculty-pick {
    margin-bottom: 14px;
    max-width: 420px;
  }
  .dm-faculty-pick select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d3deea;
    border-radius: 10px;
  }
</style>

<div class="message-window">
  <div class="message-head">
    <i class="fas fa-bullhorn" aria-hidden="true"></i>
    <span>Message for all accounts</span>
  </div>
  <div class="message-body">
    Hello <?= htmlspecialchars($displayName !== '' ? $displayName : 'User') ?>, <?= nl2br(htmlspecialchars($message)) ?>
  </div>

  <?php if ($isAdmin): ?>
    <div class="message-edit">
      <form method="post" action="<?= base_url('messages/update') ?>">
        <?= csrf_field() ?>
        <label for="global_message"><strong>Post new announcement (Admin only)</strong></label>
        <textarea id="global_message" name="global_message" maxlength="1000" required></textarea>
        <div class="message-actions">
          <button type="submit" class="btn btn-accent">Post Announcement</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="message-history">
    <h3>Recent announcements</h3>
    <?php if (empty($messageHistory)): ?>
      <p class="muted">No announcements yet.</p>
    <?php else: ?>
      <?php foreach ($messageHistory as $entry): ?>
        <?php
        $announcementId = (int) ($entry['id'] ?? 0);
        $announcementText = (string) ($entry['message'] ?? '');
        $createdAt = (string) ($entry['created_at'] ?? '');
        $dateLabel = $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : 'Unknown date';
        ?>
        <div class="message-history-item" data-announcement-id="<?= $announcementId ?>">
          <div class="item-head">
            <div class="meta"><?= htmlspecialchars($dateLabel) ?></div>
            <?php if ($isAdmin && $announcementId > 0): ?>
              <div class="item-actions">
                <button type="button" class="btn btn-sm icon-btn announcement-edit-btn" data-target="edit-announcement-<?= $announcementId ?>" title="Edit announcement" aria-label="Edit announcement">
                  <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                </button>
                <form method="post" action="<?= base_url('messages/announcements/' . $announcementId . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-sm btn-danger icon-btn" title="Delete announcement" aria-label="Delete announcement">
                    <i class="fas fa-trash-alt" aria-hidden="true"></i>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
          <div class="text"><?= nl2br(htmlspecialchars($announcementText)) ?></div>
          <?php if ($isAdmin && $announcementId > 0): ?>
            <div class="edit-panel" id="edit-announcement-<?= $announcementId ?>">
              <form method="post" action="<?= base_url('messages/announcements/' . $announcementId . '/update') ?>">
                <?= csrf_field() ?>
                <label for="announcement_message_<?= $announcementId ?>"><strong>Edit announcement</strong></label>
                <textarea id="announcement_message_<?= $announcementId ?>" name="announcement_message" maxlength="1000" required><?= htmlspecialchars($announcementText) ?></textarea>
                <div class="message-actions">
                  <button type="button" class="btn btn-sm announcement-cancel-btn" data-target="edit-announcement-<?= $announcementId ?>">Cancel</button>
                  <button type="submit" class="btn btn-sm btn-accent">Save</button>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php if ($isAdmin): ?>
<script>
  (function () {
    function togglePanel(id, open) {
      var panel = document.getElementById(id);
      if (!panel) return;
      panel.classList.toggle('is-open', open);
    }

    document.querySelectorAll('.announcement-edit-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (!target) return;
        document.querySelectorAll('.edit-panel.is-open').forEach(function (openPanel) {
          if (openPanel.id !== target) {
            openPanel.classList.remove('is-open');
          }
        });
        var panel = document.getElementById(target);
        if (!panel) return;
        var willOpen = !panel.classList.contains('is-open');
        togglePanel(target, willOpen);
        if (willOpen) {
          var textarea = panel.querySelector('textarea');
          if (textarea) textarea.focus();
        }
      });
    });

    document.querySelectorAll('.announcement-cancel-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-target');
        if (target) togglePanel(target, false);
      });
    });
  })();
</script>
<?php endif; ?>

<?php if ($directMessagingEnabled): ?>
  <div class="dm-section">
    <h2>
      <i class="fas fa-comments" aria-hidden="true"></i>
      Direct messages
      <?php if ($directUnreadCount > 0): ?>
        <span class="dm-unread-badge"><?= $directUnreadCount > 99 ? '99+' : $directUnreadCount ?> unread</span>
      <?php endif; ?>
    </h2>

    <?php if ($isFaculty): ?>
      <p class="muted" style="margin: 0 0 12px;">Message or reply to your assigned college and university officials below.</p>
      <?php if ($facultyContacts === []): ?>
        <p class="muted">No officials are assigned for your college yet. Please contact your RIDE administrator.</p>
      <?php else: ?>
        <div class="dm-contacts">
          <?php foreach ($facultyContacts as $contact): ?>
            <?php
            $label = (string) ($contact['label'] ?? '');
            $official = is_array($contact['user'] ?? null) ? $contact['user'] : null;
            $officialId = (int) ($official['id'] ?? 0);
            $officialName = trim((string) ($official['first_name'] ?? '') . ' ' . (string) ($official['last_name'] ?? ''));
            ?>
            <div class="dm-contact-card">
              <div class="label"><?= htmlspecialchars($label) ?></div>
              <div class="person"><?= htmlspecialchars($officialName) ?></div>
              <a href="<?= base_url('messages/conversation/' . $officialId) ?>" class="btn btn-sm btn-accent">Message</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php elseif ($facultyRecipients !== []): ?>
      <p class="muted" style="margin: 0 0 12px;">Start a conversation with a faculty member in your scope.</p>
      <form class="dm-faculty-pick" method="get" action="#" id="dm-faculty-start-form">
        <label for="dm_faculty_pick"><strong>Select faculty</strong></label>
        <select id="dm_faculty_pick" required>
          <option value="">— Choose faculty —</option>
          <?php foreach ($facultyRecipients as $facultyRow): ?>
            <?php
            $fid = (int) ($facultyRow['id'] ?? 0);
            $fname = trim((string) ($facultyRow['first_name'] ?? '') . ' ' . (string) ($facultyRow['last_name'] ?? ''));
            $college = (string) ($facultyRow['college_name'] ?? '');
            ?>
            <option value="<?= $fid ?>"><?= htmlspecialchars($fname) ?><?= $college !== '' ? ' · ' . htmlspecialchars($college) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <div style="margin-top: 10px;">
          <button type="button" class="btn btn-accent btn-sm" id="dm-faculty-start-btn">Open conversation</button>
        </div>
      </form>
      <script>
        (function () {
          var base = <?= json_encode(rtrim(base_url('messages/conversation/'), '/')) ?>;
          var btn = document.getElementById('dm-faculty-start-btn');
          var sel = document.getElementById('dm_faculty_pick');
          if (!btn || !sel) return;
          btn.addEventListener('click', function () {
            var id = sel.value;
            if (!id) {
              sel.focus();
              return;
            }
            window.location.href = base + '/' + id;
          });
        })();
      </script>
    <?php endif; ?>

    <h3 style="font-size: 0.95rem; color: #1f3854; margin: 0 0 10px;">Your conversations</h3>
    <?php if ($conversations === []): ?>
      <p class="muted">No conversations yet.</p>
    <?php else: ?>
      <div class="dm-conv-list">
        <?php foreach ($conversations as $conv): ?>
          <?php
          $partnerId = (int) ($conv['partner_id'] ?? 0);
          $lastAt = (string) ($conv['last_at'] ?? '');
          $timeLabel = $lastAt !== '' ? date('M j, g:i A', strtotime($lastAt)) : '';
          $unread = (int) ($conv['unread_count'] ?? 0);
          $preview = (string) ($conv['last_body'] ?? '');
          if (strlen($preview) > 120) {
              $preview = substr($preview, 0, 117) . '…';
          }
          ?>
          <a href="<?= base_url('messages/conversation/' . $partnerId) ?>" class="dm-conv-item">
            <div>
              <div class="name"><?= htmlspecialchars((string) ($conv['partner_name'] ?? '')) ?></div>
              <div class="role"><?= htmlspecialchars((string) ($conv['partner_role_label'] ?? '')) ?></div>
              <?php if ($preview !== ''): ?>
                <div class="preview"><?= htmlspecialchars($preview) ?></div>
              <?php endif; ?>
            </div>
            <div class="meta-right">
              <?php if ($timeLabel !== ''): ?>
                <div class="time"><?= htmlspecialchars($timeLabel) ?></div>
              <?php endif; ?>
              <?php if ($unread > 0): ?>
                <span class="dm-unread-badge"><?= $unread > 99 ? '99+' : $unread ?></span>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
