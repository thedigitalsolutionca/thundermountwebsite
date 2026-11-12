<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">We have your dealer application</h1>
<p>Hi <?= e($dealer['contact_name'] ?: 'there') ?>,</p>
<p>Thanks for applying for a Thunder Mounts dealer account for <strong><?= e($dealer['company']) ?></strong>. Our team reviews applications during business hours, usually within one business day.</p>
<p>We will email you as soon as your account is approved. If we need anything else — a resale certificate, for example — we will ask before making a decision.</p>
<p style="margin:24px 0;">
  <a href="<?= e(absolute_url('/dealer/account')) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">View your application</a>
</p>
<p style="margin:0;">Questions? Call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a> or reply to this email.</p>
