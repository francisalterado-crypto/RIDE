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

<section class="card list-card message-window">
  <div class="list-card-head">
    <div>
      <h2><i class="fas fa-bullhorn" aria-hidden="true"></i> Announcement</h2>
      <p>Message for all accounts.</p>
    </div>
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
                <button type="button" class="btn btn-sm btn-outline icon-btn announcement-edit-btn" data-target="edit-announcement-<?= $announcementId ?>" title="Edit announcement" aria-label="Edit announcement">
                  <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                </button>
                <form method="post" action="<?= base_url('messages/announcements/' . $announcementId . '/delete') ?>" class="inline-action" onsubmit="return confirm('Delete this announcement?');">
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
                  <button type="button" class="btn btn-sm btn-outline announcement-cancel-btn" data-target="edit-announcement-<?= $announcementId ?>">Cancel</button>
                  <button type="submit" class="btn btn-sm btn-accent">Save</button>
                </div>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

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
  <section class="card list-card dm-section">
    <div class="list-card-head">
      <div>
        <h2>
          <i class="fas fa-comments" aria-hidden="true"></i>
          Direct messages
          <?php if ($directUnreadCount > 0): ?>
            <span class="dm-unread-badge"><?= $directUnreadCount > 99 ? '99+' : $directUnreadCount ?> unread</span>
          <?php endif; ?>
        </h2>
      </div>
    </div>

    <?php if ($isFaculty): ?>
      <p class="muted dm-intro">Message or reply to your assigned college and university officials below.</p>
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
      <p class="muted dm-intro">Start a conversation with a faculty member in your scope.</p>
      <form class="dm-faculty-pick" method="get" action="#" id="dm-faculty-start-form">
        <label for="dm_faculty_pick"><strong>Select faculty</strong></label>
        <select id="dm_faculty_pick" required>
          <option value="">Choose faculty</option>
          <?php foreach ($facultyRecipients as $facultyRow): ?>
            <?php
            $fid = (int) ($facultyRow['id'] ?? 0);
            $fname = trim((string) ($facultyRow['first_name'] ?? '') . ' ' . (string) ($facultyRow['last_name'] ?? ''));
            $college = (string) ($facultyRow['college_name'] ?? '');
            ?>
            <option value="<?= $fid ?>"><?= htmlspecialchars($fname) ?><?= $college !== '' ? ' | ' . htmlspecialchars($college) : '' ?></option>
          <?php endforeach; ?>
        </select>
        <div class="dm-faculty-pick-actions">
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

    <h3 class="dm-subtitle">Your conversations</h3>
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
              $preview = substr($preview, 0, 117) . '...';
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
  </section>
<?php endif; ?>
