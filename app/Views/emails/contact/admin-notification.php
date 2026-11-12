<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">New website enquiry</h1>
<p><strong><?= e($message_data['name'] ?? '') ?></strong> sent a message through the contact form.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;border:1px solid #E5E7EB;border-radius:8px;margin:16px 0;">
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;width:120px;">Email</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><a href="mailto:<?= e($message_data['email'] ?? '') ?>" style="color:#C82026;"><?= e($message_data['email'] ?? '') ?></a></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Phone</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($message_data['phone'] ?? '—') ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;">Subject</td><td style="padding:8px 14px;"><?= e($message_data['subject'] ?? '—') ?></td></tr>
</table>
<div style="background:#F8F9FA;border:1px solid #E5E7EB;border-radius:6px;padding:14px 16px;font-size:14px;">
  <?= nl2br(e($message_data['message'] ?? '')) ?>
</div>
<p style="margin:18px 0 0;">
  <a href="<?= e(absolute_url('/admin/messages')) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Open the inbox</a>
</p>
