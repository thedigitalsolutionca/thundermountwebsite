<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Update on your dealer application</h1>
<p>Hi <?= e($dealer['contact_name'] ?: 'there') ?>,</p>
<p>Thank you for applying for a Thunder Mounts dealer account. We are not able to approve the application for <strong><?= e($dealer['company']) ?></strong> at this time.</p>
<?php if (trim((string) ($note ?? '')) !== ''): ?>
  <div style="background:#F8F9FA;border-left:3px solid #D1D5DB;padding:12px 14px;margin:0 0 18px;font-size:14px;color:#3F444A;">
    <?= nl2br(e($note)) ?>
  </div>
<?php endif; ?>
<p>If something has changed, or you would like us to reconsider, call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a> or reply to this email. We are glad to talk it through.</p>
<p style="margin:0;color:#5B6168;font-size:13px;">— The Thunder Mounts team</p>
