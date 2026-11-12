<?php
use App\Core\Csrf;
use App\Core\Money;
$isApproved = $dealer['status'] === 'approved';
?>
<div class="admin-head">
  <div>
    <h2><?= e($dealer['company']) ?></h2>
    <p>Application #<?= (int) $dealer['id'] ?> · <?= e($dealer['business_type']) ?> · <span class="badge <?= $isApproved ? 'badge-green' : ($dealer['status'] === 'rejected' ? 'badge-red' : 'badge-amber') ?>"><?= e(ucfirst($dealer['status'])) ?></span></p>
  </div>
  <a class="btn btn-outline btn-sm" href="<?= url('/admin/dealers') ?>">Back</a>
</div>

<div class="admin-grid-2" style="grid-template-columns:1.5fr 1fr;align-items:start">
  <div class="stack">
    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Business details</h3></div></div>
      <div class="admin-card-body small">
        <div class="grid grid-2">
          <p style="margin:0"><strong>Contact</strong><br><?= e($dealer['contact_name']) ?></p>
          <p style="margin:0"><strong>Email</strong><br><a href="mailto:<?= e($dealer['email']) ?>"><?= e($dealer['email']) ?></a></p>
          <p style="margin:0"><strong>Phone</strong><br><a href="tel:<?= e(preg_replace('/\D+/', '', $dealer['phone'])) ?>"><?= e($dealer['phone']) ?></a></p>
          <p style="margin:0"><strong>Website</strong><br><?= $dealer['website'] ? '<a href="' . e($dealer['website']) . '" target="_blank" rel="noopener">' . e($dealer['website']) . '</a>' : '—' ?></p>
          <p style="margin:0"><strong>Resale number</strong><br><?= e($dealer['resale_number'] ?: '—') ?></p>
          <p style="margin:0"><strong>Applied</strong><br><?= e(fmt_datetime($dealer['applied_at'])) ?></p>
        </div>
        <p style="margin:10px 0 0"><strong>Address</strong><br><span class="muted"><?= e(trim($dealer['address1'] . "\n" . ($dealer['address2'] ? $dealer['address2'] . "\n" : '') . $dealer['city'] . ', ' . $dealer['state'] . ' ' . $dealer['postcode'] . ' ' . $dealer['country'])) ?></span></p>
        <?php if ($dealer['document_path']): ?>
          <p style="margin:10px 0 0"><a class="btn btn-outline btn-sm" href="<?= e(upload_url($dealer['document_path'])) ?>" target="_blank" rel="noopener"><?= icon('file-text') ?> View uploaded document</a></p>
        <?php endif; ?>
        <?php if ($dealer['admin_notes']): ?><div class="admin-note" style="margin-top:12px"><strong>Admin notes</strong><p class="small"><?= nl2br(e($dealer['admin_notes'])) ?></p></div><?php endif; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-head"><div><h3>Orders</h3><span><?= count($dealer['orders'] ?? []) ?> shown</span></div></div>
      <?php if (empty($dealer['orders'])): ?>
        <div class="admin-empty"><p>No orders on this dealer account yet.</p></div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($dealer['orders'] as $order): ?>
                <tr>
                  <td><a class="bold" href="<?= url('/admin/orders/' . urlencode($order['order_number'])) ?>"><?= e($order['order_number']) ?></a></td>
                  <td class="small"><?= e(fmt_date($order['created_at'])) ?></td>
                  <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e(status_label($order['status'])) ?></span></td>
                  <td><?= e(Money::format((float) $order['total'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stack">
    <?php if (!$isApproved): ?>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Approve dealer</h3></div></div>
        <div class="admin-card-body">
          <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/approve') ?>" method="post">
            <?= csrf_field() ?>
            <div class="field"><label for="tier_id">Pricing tier</label>
              <select class="select" id="tier_id" name="tier_id" required>
                <option value="">Choose a tier</option>
                <?php foreach ($tiers as $tier): ?>
                  <option value="<?= (int) $tier['id'] ?>"><?= e($tier['name']) ?> — <?= e(rtrim(rtrim(number_format((float) $tier['discount_percent'], 2, '.', ''), '0'), '.')) ?>%</option>
                <?php endforeach; ?>
              </select>
              <?php if ($tiers === []): ?><span class="hint" style="color:var(--red)">Create a dealer tier first. <a href="<?= url('/admin/dealers/tiers') ?>">Manage tiers</a></span><?php endif; ?>
            </div>
            <div class="field"><label for="approve_note">Note <span class="muted">(optional, included in email)</span></label><textarea class="textarea" id="approve_note" name="note" rows="2"></textarea></div>
            <button class="btn btn-dark btn-block" type="submit" <?= $tiers === [] ? 'disabled' : '' ?>>Approve and assign tier</button>
          </form>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Not approved</h3></div></div>
        <div class="admin-card-body">
          <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/reject') ?>" method="post" data-confirm="Mark this application as not approved?">
            <?= csrf_field() ?>
            <div class="field"><label for="reject_note">Reason <span class="muted">(sent to applicant)</span></label><textarea class="textarea" id="reject_note" name="note" rows="3" required></textarea></div>
            <button class="btn btn-danger btn-block" type="submit">Mark not approved</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Approved</h3></div><span class="badge badge-green">Active</span></div>
        <div class="admin-card-body small">
          <p style="margin:0 0 4px"><strong>Tier:</strong> <?= !empty($dealer['tier']) ? e($dealer['tier']['name']) . ' (' . e(rtrim(rtrim(number_format((float) $dealer['tier']['discount_percent'], 2, '.', ''), '0'), '.')) . '%)' : 'Not assigned' ?></p>
          <p style="margin:0 0 10px"><strong>Approved:</strong> <?= $dealer['reviewed_at'] ? e(fmt_datetime($dealer['reviewed_at'])) : '—' ?></p>
          <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/approve') ?>" method="post">
            <?= csrf_field() ?>
            <div class="field"><label for="tier_id">Change tier</label>
              <select class="select" id="tier_id" name="tier_id">
                <?php foreach ($tiers as $tier): ?>
                  <option value="<?= (int) $tier['id'] ?>"<?= selected((string) $tier['id'], (string) $dealer['tier_id']) ?>><?= e($tier['name']) ?> — <?= e(rtrim(rtrim(number_format((float) $tier['discount_percent'], 2, '.', ''), '0'), '.')) ?>%</option>
                <?php endforeach; ?>
              </select>
            </div>
            <button class="btn btn-dark btn-sm" type="submit" <?= $tiers === [] ? 'disabled' : '' ?>>Update tier</button>
          </form>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Place on hold</h3></div></div>
        <div class="admin-card-body">
          <form action="<?= url('/admin/dealers/' . (int) $dealer['id'] . '/reject') ?>" method="post" data-confirm="Remove this dealer's approved status?">
            <?= csrf_field() ?>
            <div class="field"><label for="reject_note">Reason</label><textarea class="textarea" id="reject_note" name="note" rows="2" required></textarea></div>
            <button class="btn btn-danger btn-sm btn-block" type="submit">Revoke approval</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
    <?php if ($dealer['user_id']): ?>
      <div class="admin-card">
        <div class="admin-card-head"><div><h3>Login account</h3></div></div>
        <div class="admin-card-body small">
          <p style="margin:0 0 8px"><?= e($dealer['user']['email'] ?? '') ?><br><span class="muted"><?= e(ucfirst($dealer['user']['status'] ?? 'active')) ?></span></p>
          <a class="btn btn-outline btn-sm" href="<?= url('/admin/customers/' . (int) $dealer['user_id']) ?>">Open customer record</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
