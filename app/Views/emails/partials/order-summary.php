<?php
$addr = $order['shipping_address_data'] ?? [];
$line = static function (string $label, $value, bool $strong = false): string {
    return '<div style="display:flex;justify-content:space-between;gap:16px;padding:5px 0;'
        . ($strong ? 'border-top:1px solid #E5E7EB;margin-top:6px;padding-top:10px;font-weight:800;font-size:16px;' : 'font-size:14px;')
        . '"><span>' . e($label) . '</span><span>' . e((string) $value) . '</span></div>';
};
?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #E5E7EB;border-radius:8px;margin:18px 0;">
  <tr>
    <td style="padding:14px 16px;border-bottom:1px solid #E5E7EB;background:#F8F9FA;">
      <strong style="font-size:14px;">Order <?= e($order['order_number']) ?></strong>
      <div style="font-size:12px;color:#5B6168;">Placed <?= e(fmt_datetime((string) $order['created_at'])) ?></div>
    </td>
  </tr>
  <tr>
    <td style="padding:8px 16px;">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <?php foreach (($order['items'] ?? []) as $item): ?>
          <tr>
            <td style="padding:8px 0;border-bottom:1px solid #F3F4F6;font-size:14px;">
              <strong><?= e($item['name']) ?></strong>
              <div style="font-size:12px;color:#5B6168;">SKU <?= e($item['sku']) ?> &middot; Qty <?= (int) $item['qty'] ?></div>
            </td>
            <td style="padding:8px 0;border-bottom:1px solid #F3F4F6;text-align:right;font-size:14px;white-space:nowrap;"><?= e(money($item['line_total'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <div style="padding-top:8px;">
        <?= $line('Subtotal', money($order['subtotal'])) ?>
        <?php if ((float) $order['discount_total'] > 0): ?><?= $line('Discount' . (!empty($order['discount_code']) ? ' (' . $order['discount_code'] . ')' : ''), '−' . money($order['discount_total'])) ?><?php endif; ?>
        <?= $line('Shipping' . (!empty($order['shipping_method_name']) ? ' — ' . $order['shipping_method_name'] : ''), (float) $order['shipping_total'] > 0 ? money($order['shipping_total']) : 'Free') ?>
        <?= $line('Tax', money($order['tax_total'])) ?>
        <?= $line('Total', money($order['total']), true) ?>
      </div>
    </td>
  </tr>
</table>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px;">
  <tr>
    <td width="50%" valign="top" style="font-size:13px;color:#3F444A;padding-right:10px;">
      <strong style="display:block;color:#181818;margin-bottom:4px;">Shipping address</strong>
      <?= e(trim(($addr['first_name'] ?? '') . ' ' . ($addr['last_name'] ?? ''))) ?><br>
      <?= e($addr['address1'] ?? '') ?><?= !empty($addr['address2']) ? '<br>' . e($addr['address2']) : '' ?><br>
      <?= e(trim(($addr['city'] ?? '') . ', ' . ($addr['state'] ?? '') . ' ' . ($addr['postcode'] ?? ''))) ?><br>
      <?= e($addr['country'] ?? '') ?>
    </td>
    <td width="50%" valign="top" style="font-size:13px;color:#3F444A;">
      <strong style="display:block;color:#181818;margin-bottom:4px;">Contact</strong>
      <?= e($order['email']) ?><br>
      <?= e($order['phone'] ?? '') ?>
      <?php if (!empty($order['tracking_number'])): ?>
        <div style="margin-top:8px;"><strong style="color:#181818;">Tracking:</strong> <?= e($order['tracking_carrier'] ?: 'Carrier') ?> <?= e($order['tracking_number']) ?></div>
      <?php endif; ?>
    </td>
  </tr>
</table>
