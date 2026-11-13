<?php
$user = $user ?? [];
$partner = $partner ?? [];
$partnerId = (int) ($partnerId ?? 0);
$partnerName = (string) ($partnerName ?? '');
$partnerRoleLabel = (string) ($partnerRoleLabel ?? '');
$messages = isset($messages) && is_array($messages) ? $messages : [];
$userId = (int) ($user['id'] ?? 0);
?>

<div class="dm-thread-wrap">
  <a href="<?= base_url('messages') ?>" class="dm-thread-back">
    <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to messages
  </a>

  <div class="dm-thread-panel">
    <div class="dm-thread-head">
      <h2><?= htmlspecialchars($partnerName !== '' ? $partnerName : 'Conversation') ?></h2>
      <div class="role"><?= htmlspecialchars($partnerRoleLabel) ?></div>
    </div>

    <div class="dm-thread-messages">
      <?php if ($messages === []): ?>
        <p class="muted dm-thread-empty">No messages yet. Send the first message below.</p>
      <?php else: ?>
        <?php foreach ($messages as $msg): ?>
          <?php
          $isMine = (int) ($msg['sender_id'] ?? 0) === $userId;
          $createdAt = (string) ($msg['created_at'] ?? '');
          $dateLabel = $createdAt !== '' ? date('M j, Y g:i A', strtotime($createdAt)) : '';
          ?>
          <div class="dm-bubble <?= $isMine ? 'mine' : 'theirs' ?>">
            <?= nl2br(htmlspecialchars((string) ($msg['body'] ?? ''))) ?>
            <?php if ($dateLabel !== ''): ?>
              <div class="meta"><?= htmlspecialchars($dateLabel) ?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="dm-compose">
      <form method="post" action="<?= base_url('messages/send') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="recipient_id" value="<?= (int) $partnerId ?>">
        <label for="dm_body" class="sr-only">Your message</label>
        <textarea id="dm_body" name="body" maxlength="2000" required placeholder="Type your message..."></textarea>
        <div class="dm-compose-actions">
          <button type="submit" class="btn btn-accent">Send</button>
        </div>
      </form>
    </div>
  </div>
</div>
