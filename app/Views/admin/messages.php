<?php use App\Core\Csrf; ?>
<div class="admin-head">
  <div><h2>Messages</h2><p>Contact form enquiries from the storefront.</p></div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/data?type=messages') ?>">Manage / delete messages</a>
</div>

<div class="admin-grid-2" style="grid-template-columns:1fr 1.3fr;align-items:start">
  <div class="admin-card">
    <div class="admin-card-head"><div><h3>Inbox</h3><span><?= count($messages) ?> recent</span></div></div>
    <?php if ($messages === []): ?>
      <div class="admin-empty"><?= icon('mail') ?><p>No messages yet.</p></div>
    <?php else: ?>
      <div class="admin-message-list">
        <?php foreach ($messages as $message): ?>
          <a class="admin-message <?= $message['status'] === 'new' ? 'unread' : '' ?> <?= $selected && (int) $selected['id'] === (int) $message['id'] ? 'active' : '' ?>" href="<?= url('/admin/messages?id=' . (int) $message['id']) ?>">
            <div class="flex between"><strong><?= e($message['name']) ?></strong><span class="tiny muted"><?= e(fmt_date($message['created_at'])) ?></span></div>
            <div class="small"><?= e($message['subject']) ?></div>
            <div class="tiny muted"><?= e($message['email']) ?> · <?= e(ucfirst($message['status'])) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="admin-card">
    <?php if (!$selected): ?>
      <div class="admin-empty"><?= icon('mail') ?><h3>Select a message</h3><p>Choose an enquiry on the left to read and reply.</p></div>
    <?php else: ?>
      <div class="admin-card-head">
        <div><h3><?= e($selected['subject']) ?></h3><span><?= e($selected['name']) ?> · <?= e(fmt_datetime($selected['created_at'])) ?></span></div>
        <span class="badge <?= $selected['status'] === 'new' ? 'badge-amber' : 'badge-green' ?>"><?= e(ucfirst($selected['status'])) ?></span>
      </div>
      <div class="admin-card-body">
        <p class="small" style="margin-bottom:12px">
          <strong>From:</strong> <?= e($selected['name']) ?> &lt;<?= e($selected['email']) ?>&gt;<br>
          <?php if ($selected['phone']): ?><strong>Phone:</strong> <?= e($selected['phone']) ?><br><?php endif; ?>
        </p>
        <div class="admin-note"><p style="white-space:pre-line"><?= e($selected['message']) ?></p></div>
        <div class="flex wrap mt-2">
          <a class="btn btn-ghost btn-sm" href="<?= url('/admin/data?' . http_build_query(['type' => 'messages', 'record' => $selected['id'], 'before' => date('Y-m-d', strtotime('tomorrow'))])) ?>">Delete message…</a>
          <a class="btn btn-primary btn-sm" href="mailto:<?= e($selected['email']) ?>?subject=<?= rawurlencode('Re: ' . $selected['subject']) ?>"><?= icon('mail') ?> Reply by email</a>
          <?php if ($selected['phone']): ?><a class="btn btn-outline btn-sm" href="tel:<?= e(preg_replace('/\D+/', '', $selected['phone'])) ?>">Call</a><?php endif; ?>
          <form action="<?= url('/admin/messages/' . (int) $selected['id'] . '/status') ?>" method="post">
            <?= csrf_field() ?><input type="hidden" name="status" value="replied">
            <button class="btn btn-outline btn-sm" type="submit">Mark replied</button>
          </form>
          <form action="<?= url('/admin/messages/' . (int) $selected['id'] . '/status') ?>" method="post">
            <?= csrf_field() ?><input type="hidden" name="status" value="closed">
            <button class="btn btn-ghost btn-sm" type="submit">Close</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
