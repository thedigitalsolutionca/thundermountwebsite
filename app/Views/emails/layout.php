<?php
/**
 * Shared transactional email shell.
 * Inline styles only — email clients are not kind to stylesheets.
 */
$storeName = $name ?? 'Thunder Mounts';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($storeName) ?></title>
</head>
<body style="margin:0;padding:0;background:#F3F4F6;color:#181818;font-family:Inter,-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;line-height:1.6;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#F3F4F6;padding:28px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;background:#FFFFFF;border-radius:10px;overflow:hidden;border:1px solid #E5E7EB;">
        <tr>
          <td style="background:#181818;padding:20px 28px;border-bottom:4px solid #C82026;">
            <a href="<?= e($url ?? '/') ?>" style="color:#FFFFFF;text-decoration:none;font-weight:800;font-size:20px;letter-spacing:-.02em;">THUNDER MOUNTS</a>
            <div style="color:#A7ADB4;font-size:10px;letter-spacing:.22em;text-transform:uppercase;margin-top:3px;">Garage Systems</div>
          </td>
        </tr>
        <tr>
          <td style="padding:30px 28px;font-size:15px;color:#181818;">
            <?= $content ?>
          </td>
        </tr>
        <tr>
          <td style="padding:20px 28px;border-top:1px solid #E5E7EB;background:#F8F9FA;color:#5B6168;font-size:12px;">
            <p style="margin:0 0 6px;"><strong style="color:#181818;"><?= e($storeName) ?></strong></p>
            <p style="margin:0 0 6px;"><?= e($address ?? '') ?></p>
            <p style="margin:0 0 6px;">Phone <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', (string) ($phone ?? ''))) ?>" style="color:#C82026;text-decoration:none;"><?= e($phone ?? '') ?></a> &middot; <a href="mailto:<?= e($email ?? '') ?>" style="color:#C82026;text-decoration:none;"><?= e($email ?? '') ?></a></p>
            <p style="margin:0;color:#9AA1A9;">This message was sent about your Thunder Mounts account or order.</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
