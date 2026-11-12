<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Reset your password</h1>
<p>Hi <?= e(trim((string) ($user['first_name'] ?? '')) ?: 'there') ?>,</p>
<p>We received a request to reset the password for your Thunder Mounts account. This link expires in <?= e($expires ?? '1 hour') ?> and can only be used once.</p>
<p style="margin:24px 0;">
  <a href="<?= e($link ?? '#') ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Choose a new password</a>
</p>
<p style="font-size:13px;color:#5B6168;">If the button does not work, paste this address into your browser:</p>
<p style="font-size:12px;color:#5B6168;word-break:break-all;background:#F8F9FA;border:1px solid #E5E7EB;border-radius:6px;padding:10px;"><?= e($link ?? '') ?></p>
<p style="font-size:13px;color:#5B6168;margin-bottom:0;">If you did not request this, you can ignore this email — your current password stays active.</p>
