<?php
use App\Core\Csrf;
use App\Core\Money;
use App\Services\OrderService;
$service = new OrderService();
$ship = $order['shipping_address_data'];
$bill = $order['billing_address_data'];
$customerNotes = array_values(array_filter($order['notes'], static fn ($n) => (int) $n['is_customer_visible'] === 1));
?>
<div class="admin-head">
  <div>
    <h2><?= e($order['order_number']) ?> <span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></h2>
    <p><?= e(fmt_datetime($order['created_at'])) ?> · <?= e(Money::format((float) $order['total'])) ?> · <?= e($order['payment_method']) ?></p>
  </div>
  <div class="flex wrap" style="gap:6px">
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/orders') ?>">Back</a>
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/orders/' . urlencode($order['order_number']) . '/invoice') ?>" target="_blank"><?= icon('file-text') ?> Invoice</a>
    <a class="btn btn-outline btn-sm" href="<?= url('/admin/orders/' . urlencode($order['order_number']) . '/invoice?packing=1') ?>" target="_blank">Packing slip</a>
  </div>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.6fr 1fr;align-items:start">
  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Items</h3><span><?= count($order['items']) ?> line<?= count($order['items']) === 1 ? '' : 's' ?></span></div></div>
      <div class="table-wrap">
        <table class="data-table">
          <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($order['items'] as $item): ?>
              <tr>
                <td><strong><?= e($item['name']) ?></strong><?php if ((int) $item['is_dealer_price']): ?><div class="tiny muted">Dealer price</div><?php endif; ?></td>
                <td class="small muted"><?= e($item['sku']) ?></td>
                <td><?= (int) $item['qty'] ?></td>
                <td><?= e(Money::format((float) $item['unit_price'])) ?></td>
                <td><strong><?= e(Money::format((float) $item['line_total'])) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="admin-card-body">
        <div class="totals" style="margin-left:auto;max-width:320px">
          <div class="totals-row"><span>Subtotal</span><span><?= e(Money::format((float) $order['subtotal'])) ?></span></div>
          <?php if ((float) $order['discount_total'] > 0): ?><div class="totals-row" style="color:var(--green)"><span>Discount <?= e($order['discount_code']) ?></span><span>−<?= e(Money::format((float) $order['discount_total'])) ?></span></div><?php endif; ?>
          <div class="totals-row"><span>Shipping</span><span><?= e(Money::format((float) $order['shipping_total'])) ?></span></div>
          <div class="totals-row"><span>Tax</span><span><?= e(Money::format((float) $order['tax_total'])) ?></span></div>
          <div class="totals-row total"><span>Total</span><span><?= e(Money::format((float) $order['total'])) ?></span></div>
          <?php if ((float) $order['refunded_total'] > 0): ?><div class="totals-row red"><span>Refunded</span><span>−<?= e(Money::format((float) $order['refunded_total'])) ?></span></div><?php endif; ?>
        </div>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Customer notes</h3><span>Visible to the customer</span></div></div>
      <div class="admin-card-body">
        <?php if ($customerNotes === []): ?><p class="small muted">No customer-visible updates yet.</p><?php endif; ?>
        <?php foreach ($customerNotes as $note): ?>
          <div class="admin-note"><p><?= nl2br(e($note['note'])) ?></p><span class="tiny muted"><?= e(fmt_datetime($note['created_at'])) ?></span></div>
        <?php endforeach; ?>
        <form action="<?= url('/admin/orders/' . (int) $order['id'] . '/note') ?>" method="post" style="margin-top:12px">
          <?= csrf_field() ?>
          <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
          <div class="field"><label for="note">Add a note</label><textarea class="textarea" id="note" name="note" rows="3" required></textarea></div>
          <label class="check"><input type="checkbox" name="customer_visible" value="1"><span>Email/notify the customer (visible in their account)</span></label>
          <button class="btn btn-dark btn-sm mt-2" type="submit">Add note</button>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Status history</h3><span>Audit trail</span></div></div>
      <div class="admin-card-body">
        <div class="admin-timeline">
          <?php foreach ($history as $event): ?>
            <div>
              <strong><?= e(status_label($event['to_status'])) ?></strong>
              <span class="tiny muted"><?= e(fmt_datetime($event['created_at'])) ?></span>
              <?php if ($event['note']): ?><p class="tiny"><?= e($event['note']) ?></p><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Payment</h3></div><span class="badge <?= e(badge_class($order['payment_status'])) ?>"><?= e(status_label($order['payment_status'])) ?></span></div>
      <div class="admin-card-body small">
        <p style="margin:0 0 4px"><strong>Method:</strong> <?= e($order['payment_method']) ?></p>
        <p style="margin:0 0 4px"><strong>PayPal order:</strong> <?= e($order['paypal_order_id'] ?: '—') ?></p>
        <p style="margin:0 0 4px"><strong>Capture:</strong> <?= e($order['paypal_capture_id'] ?: '—') ?></p>
        <p style="margin:0 0 4px"><strong>Paid:</strong> <?= $order['paid_at'] ? e(fmt_datetime($order['paid_at'])) : '—' ?></p>
        <?php if ($order['payment_status'] !== 'paid' && $order['payment_status'] !== 'refunded'): ?>
          <form action="<?= url('/admin/orders/' . (int) $order['id'] . '/mark-paid') ?>" method="post" style="margin-top:10px" data-confirm="Record this order as paid? This will reduce stock and email the customer.">
            <?= csrf_field() ?>
            <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
            <input class="input" name="note" placeholder="Payment reference / note">
            <button class="btn btn-dark btn-sm btn-block mt-1" type="submit">Record payment manually</button>
          </form>
        <?php endif; ?>
        <?php if ($order['payment_status'] === 'paid'): ?>
          <?php if ($order['paypal_capture_id']): ?>
            <form action="<?= url('/admin/orders/' . (int) $order['id'] . '/refund') ?>" method="post" style="margin-top:10px" data-confirm="Refund the full order total through PayPal?">
              <?= csrf_field() ?>
              <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
              <label class="check"><input type="checkbox" name="restock" value="1" checked><span>Restock items</span></label>
              <button class="btn btn-danger btn-sm btn-block" type="submit">Refund via PayPal</button>
            </form>
          <?php else: ?>
            <p class="tiny muted" style="margin:8px 0 0">No PayPal capture on file. Refund in PayPal, then mark the order refunded below.</p>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Fulfilment</h3></div></div>
      <div class="admin-card-body">
        <form action="<?= url('/admin/orders/' . (int) $order['id'] . '/tracking') ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
          <div class="field"><label for="carrier">Carrier</label><input class="input" id="carrier" name="carrier" value="<?= e($order['tracking_carrier']) ?>" placeholder="UPS"></div>
          <div class="field"><label for="tracking_number">Tracking number</label><input class="input" id="tracking_number" name="tracking_number" value="<?= e($order['tracking_number']) ?>"></div>
          <button class="btn btn-outline btn-sm" type="submit">Save tracking</button>
          <p class="tiny muted" style="margin:8px 0 0">Saving tracking on a paid/processing order marks it shipped and emails the customer.</p>
        </form>
      </div>
    </div>

    <?php if ($allowed !== []): ?>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Change status</h3><span>Allowed transitions</span></div></div>
        <div class="admin-card-body">
          <form action="<?= url('/admin/orders/' . (int) $order['id'] . '/status') ?>" method="post" data-confirm="Change the order status?">
            <?= csrf_field() ?>
            <input type="hidden" name="order_number" value="<?= e($order['order_number']) ?>">
            <div class="field"><label for="status">New status</label><select class="select" id="status" name="status">
              <?php foreach ($allowed as $status): ?><option value="<?= e($status) ?>"><?= e(status_label($status)) ?></option><?php endforeach; ?>
            </select></div>
            <div class="field"><label for="status_note">Note <span class="muted">(optional)</span></label><input class="input" id="status_note" name="note"></div>
            <?php if (in_array('cancelled', $allowed, true) || in_array('refunded', $allowed, true)): ?>
              <label class="check"><input type="checkbox" name="restock" value="1"><span>Restock items</span></label>
            <?php endif; ?>
            <button class="btn btn-dark btn-sm mt-2" type="submit">Update status</button>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Customer</h3></div><a class="small bold" href="<?= url('/admin/customers?q=' . urlencode($order['email'])) ?>">Profile</a></div>
      <div class="admin-card-body small">
        <p style="margin:0 0 4px"><strong><?= e($ship['first_name'] ?? '') ?> <?= e($ship['last_name'] ?? '') ?></strong></p>
        <p style="margin:0 0 4px"><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></p>
        <p style="margin:0 0 10px"><?= e($order['phone']) ?></p>
        <h4 style="margin:10px 0 4px;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em" class="muted">Ship to</h4>
        <p class="muted" style="white-space:pre-line;margin:0"><?= e($service->addressText($ship)) ?></p>
        <h4 style="margin:10px 0 4px;font-size:.8rem;text-transform:uppercase;letter-spacing:.06em" class="muted">Bill to</h4>
        <p class="muted" style="white-space:pre-line;margin:0"><?= e($service->addressText($bill)) ?></p>
      </div>
    </div>

    <?php if ($order['customer_note']): ?>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Customer note</h3></div></div>
        <div class="admin-card-body"><p class="small"><?= nl2br(e($order['customer_note'])) ?></p></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($order['status'] === 'cancelled'): ?>
<div class="admin-card mt-3"><div class="admin-card-body"><h3>Order retention</h3><p class="small muted">Paid, refunded, PayPal-linked and stock-audited orders are protected. Eligible never-paid cancelled orders can be permanently removed after password confirmation in Data management.</p><a class="btn btn-outline btn-sm" href="<?= url('/admin/data?' . http_build_query(['type' => 'orders', 'record' => $order['id'], 'before' => date('Y-m-d', strtotime('tomorrow'))])) ?>">Review deletion eligibility</a></div></div>
<?php endif; ?>
