<?php
use App\Services\OrderService;
$address = (new OrderService())->addressText($order['shipping_address_data']);
$billing = (new OrderService())->addressText($order['billing_address_data']);
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $packing ? 'Packing slip' : 'Invoice' ?> <?= e($order['order_number']) ?></title>
  <style>
    body{font-family:Inter,system-ui,-apple-system,sans-serif;color:#181818;margin:0;padding:28px;font-size:14px;line-height:1.45}
    .sheet{max-width:800px;margin:0 auto}
    .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #C82026;padding-bottom:16px;margin-bottom:20px}
    .brand{font-weight:900;font-size:22px;letter-spacing:.02em}
    .brand span{display:block;font-size:10px;letter-spacing:.18em;color:#777;font-weight:700;margin-top:2px}
    .doc{text-align:right;font-size:12px;color:#555}
    .doc strong{display:block;font-size:18px;color:#181818;letter-spacing:.06em}
    .cols{display:flex;gap:40px;margin-bottom:22px}
    .cols > div{flex:1}
    h4{font-size:10px;text-transform:uppercase;letter-spacing:.12em;color:#777;margin:0 0 4px}
    table{width:100%;border-collapse:collapse;margin-bottom:18px}
    th{text-align:left;background:#f4f4f5;font-size:10px;text-transform:uppercase;letter-spacing:.1em;padding:8px 10px;color:#555}
    td{padding:9px 10px;border-bottom:1px solid #e7e7e9}
    th:last-child,td:last-child{text-align:right}
    .totals{margin-left:auto;width:300px}
    .totals div{display:flex;justify-content:space-between;padding:5px 0}
    .totals .total{border-top:2px solid #181818;font-weight:800;font-size:16px;margin-top:4px;padding-top:8px}
    .note{background:#f7f7f8;border-left:3px solid #C82026;padding:10px 12px;font-size:12px;margin-top:12px}
    .foot{margin-top:26px;border-top:1px solid #e7e7e9;padding-top:12px;font-size:11px;color:#777;display:flex;justify-content:space-between;gap:20px}
    @media print{body{padding:0} .sheet{max-width:none} .no-print{display:none}}
  </style>
</head>
<body>
<div class="sheet">
  <div class="head">
    <div>
      <div class="brand">THUNDER MOUNTS<span>GARAGE SYSTEMS</span></div>
      <div style="font-size:11px;color:#666;margin-top:8px"><?= e($storeLegal) ?><br><?= e($storeAddress) ?><br><?= e($storePhone) ?> · <?= e($storeEmail) ?></div>
    </div>
    <div class="doc">
      <strong><?= $packing ? 'PACKING SLIP' : 'INVOICE' ?></strong>
      <?= e($order['order_number']) ?><br>
      <?= e(date('F j, Y', strtotime($order['created_at']))) ?><br>
      Status: <?= e(ucfirst($order['status'])) ?>
    </div>
  </div>

  <div class="cols">
    <div><h4>Ship to</h4><div style="white-space:pre-line"><?= e($address) ?></div></div>
    <div><h4>Bill to</h4><div style="white-space:pre-line"><?= e($billing) ?></div></div>
    <div><h4>Contact</h4><?= e($order['email']) ?><br><?= e($order['phone']) ?></div>
  </div>

  <table>
    <thead><tr><th>Item</th><th>SKU</th><th>Qty</th><?php if (!$packing): ?><th>Unit</th><th>Amount</th><?php endif; ?></tr></thead>
    <tbody>
      <?php foreach ($order['items'] as $item): ?>
        <tr>
          <td><?= e($item['name']) ?></td>
          <td><?= e($item['sku']) ?></td>
          <td><?= (int) $item['qty'] ?></td>
          <?php if (!$packing): ?><td>$<?= e(number_format((float) $item['unit_price'], 2)) ?></td><td>$<?= e(number_format((float) $item['line_total'], 2)) ?></td><?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if (!$packing): ?>
    <div class="totals">
      <div><span>Subtotal</span><span>$<?= e(number_format((float) $order['subtotal'], 2)) ?></span></div>
      <?php if ((float) $order['discount_total'] > 0): ?><div><span>Discount <?= e($order['discount_code']) ?></span><span>−$<?= e(number_format((float) $order['discount_total'], 2)) ?></span></div><?php endif; ?>
      <div><span>Shipping</span><span>$<?= e(number_format((float) $order['shipping_total'], 2)) ?></span></div>
      <div><span>Tax</span><span>$<?= e(number_format((float) $order['tax_total'], 2)) ?></span></div>
      <div class="total"><span>Total</span><span>$<?= e(number_format((float) $order['total'], 2)) ?></span></div>
    </div>
  <?php endif; ?>

  <?php if ($order['customer_note']): ?><div class="note"><strong>Customer note:</strong> <?= e($order['customer_note']) ?></div><?php endif; ?>
  <?php if ($order['tracking_number']): ?><div class="note"><strong>Tracking:</strong> <?= e($order['tracking_carrier']) ?> <?= e($order['tracking_number']) ?></div><?php endif; ?>

  <div class="foot">
    <span>Thank you for choosing <?= e($storeName) ?>.</span>
    <span>Questions? <?= e($storePhone) ?> · <?= e($storeEmail) ?></span>
  </div>
</div>
<p class="no-print" style="text-align:center;margin-top:24px"><button onclick="window.print()" style="padding:10px 22px;font:inherit;background:#181818;color:#fff;border:0;border-radius:6px;cursor:pointer">Print</button></p>
</body>
</html>
