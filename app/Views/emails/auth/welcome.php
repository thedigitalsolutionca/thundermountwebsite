<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Welcome to Thunder Mounts</h1>
<p>Hi <?= e(trim((string) ($user['first_name'] ?? '')) ?: 'there') ?>,</p>
<p>Your account is ready. You can use it to check out faster, review past orders and keep your shipping details up to date.</p>
<p style="margin:24px 0;">
  <a href="<?= e(absolute_url('/account')) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Go to your account</a>
</p>
<p>If you have a question about a mount, an opener or an installation, reply to this email or call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a>.</p>
<p style="color:#5B6168;font-size:13px;margin-bottom:0;">— The Thunder Mounts team</p>
