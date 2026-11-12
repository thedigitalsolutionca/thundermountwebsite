<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">New dealer application</h1>
<p><strong><?= e($dealer['company']) ?></strong> has applied for a dealer account.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;border:1px solid #E5E7EB;border-radius:8px;margin:16px 0;">
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Contact</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($dealer['contact_name']) ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Email</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($dealer['email']) ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Phone</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($dealer['phone']) ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Business type</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($dealer['business_type']) ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;border-bottom:1px solid #F3F4F6;">Resale no.</td><td style="padding:8px 14px;border-bottom:1px solid #F3F4F6;"><?= e($dealer['resale_number'] ?: '—') ?></td></tr>
  <tr><td style="padding:8px 14px;color:#5B6168;">Address</td><td style="padding:8px 14px;"><?= e(trim(($dealer['address1'] ?? '') . ', ' . ($dealer['city'] ?? '') . ', ' . ($dealer['state'] ?? '') . ' ' . ($dealer['postcode'] ?? ''))) ?></td></tr>
</table>
<p style="margin:0;">
  <a href="<?= e(absolute_url('/admin/dealers/' . $dealer['id'])) ?>" style="display:inline-block;background:#C82026;color:#FFFFFF;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:6px;">Review application</a>
</p>
