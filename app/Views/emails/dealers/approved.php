<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Your dealer account is approved</h1>
<p>Hi <?= e($dealer['contact_name'] ?: 'there') ?>,</p>
<p>Welcome aboard. <strong><?= e($dealer['company']) ?></strong> now has a Thunder Mounts dealer account.</p>
<?php if (!empty($dealer['tier']['name'] ?? '')): ?>
  <div style="background:#E6F4EC;border:1px solid #B9E0C7;border-radius:6px;padding:14px 16px;margin:0 0 18px;font-size:14px;color:#145A32;">
    <strong style="display:block;">Your tier: <?= e($dealer['tier']['name'] ?? '') ?></strong>
    <?php $pct = (float) ($dealer['tier']['discount_percent'] ?? 0); ?><?= $pct > 0 ? e(rtrim(rtrim(number_format($pct, 2, '.', ''), '0'), '.')) . '% off list pricing' : 'Trade pricing enabled on your account' ?>
  </div>
<?php endif; ?>
<p>Sign in with your existing account and visit the dealer portal to see trade pricing and place orders.</p>
<p style="margin:24px 0;">
  <a href="<?= e(absolute_url('/dealer/account')) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Open the dealer portal</a>
</p>
<p style="margin:0;">Call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a> or reply to this email if you need help with an order.</p>
