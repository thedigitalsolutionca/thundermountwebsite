<?php
use App\Core\Settings;
$invoiceEnquiry = $readiness['manual'] && (!$readiness['shipping'] || !$readiness['tax']);
$errs = errors();
$v = static fn (string $key, string $default = '') => old($key, $data[$key] ?? $default);
?>
<section class="section">
  <div class="container" style="max-width:780px">
    <?php if ($invoiceEnquiry): ?>
      <div class="card card-body">
        <span class="eyebrow">Pay by invoice</span>
        <h1 style="font-size:2rem;margin:8px 0 10px">Request an invoice for your cart.</h1>
        <p>We can take your order by invoice, but the online shipping and tax rules are not yet available. Send your cart and delivery details to our team. <strong>This is an enquiry, not an order or an invoice:</strong> no payment is collected, stock is not reserved, and we will confirm the full price before you decide to proceed.</p>
        <div class="notice notice-info" style="margin:18px 0"><?= icon('truck') ?><div>Product estimate: <strong><?= e(money($summary['subtotal_cents'] - $summary['discount_amount_cents'])) ?></strong>. Shipping and tax are <strong>not included</strong> and will be confirmed by our team.</div></div>
        <?php if ($summary['discount_error']): ?><div class="notice notice-error" style="margin:0 0 16px"><?= icon('bell') ?><div><?= e($summary['discount_error']) ?> Remove the code from your cart before requesting an invoice.</div></div><?php endif; ?>
        <?php foreach ($summary['items'] as $item): ?>
          <div class="order-line"><span><?= e($item['product']['name']) ?> <span class="muted">× <?= (int) $item['qty'] ?></span></span><strong><?= e(money($item['line_cents'])) ?></strong></div>
        <?php endforeach; ?>
        <?php if (isset($errs['cart']) || isset($errs['discount']) || isset($errs['general'])): ?>
          <div class="notice notice-error" style="margin:16px 0"><?= icon('bell') ?><div><?= e($errs['cart'] ?? $errs['discount'] ?? $errs['general']) ?></div></div>
        <?php endif; ?>
        <form action="<?= url('/checkout/invoice-request') ?>" method="post" class="mt-3">
          <?= csrf_field() ?>
          <h2 style="font-size:1.15rem">Contact details</h2>
          <div class="form-row">
            <div class="field"><label for="invoice-first">First name</label><input class="input <?= isset($errs['first_name']) ? 'invalid' : '' ?>" id="invoice-first" name="first_name" value="<?= e($v('first_name')) ?>" required autocomplete="given-name"><?php if (isset($errs['first_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['first_name']) ?></span><?php endif; ?></div>
            <div class="field"><label for="invoice-last">Last name</label><input class="input <?= isset($errs['last_name']) ? 'invalid' : '' ?>" id="invoice-last" name="last_name" value="<?= e($v('last_name')) ?>" required autocomplete="family-name"><?php if (isset($errs['last_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['last_name']) ?></span><?php endif; ?></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="invoice-email">Email address</label><input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="invoice-email" name="email" type="email" value="<?= e($v('email')) ?>" required autocomplete="email"><?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?></div>
            <div class="field"><label for="invoice-phone">Phone number</label><input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="invoice-phone" name="phone" type="tel" value="<?= e($v('phone')) ?>" required autocomplete="tel"><?php if (isset($errs['phone'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['phone']) ?></span><?php endif; ?></div>
          </div>
          <h2 style="font-size:1.15rem">Delivery details</h2>
          <div class="field"><label for="invoice-address">Street address</label><input class="input <?= isset($errs['address1']) ? 'invalid' : '' ?>" id="invoice-address" name="address1" value="<?= e($v('address1')) ?>" required autocomplete="street-address"><?php if (isset($errs['address1'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['address1']) ?></span><?php endif; ?></div>
          <div class="form-row">
            <div class="field"><label for="invoice-city">City</label><input class="input <?= isset($errs['city']) ? 'invalid' : '' ?>" id="invoice-city" name="city" value="<?= e($v('city')) ?>" required autocomplete="address-level2"><?php if (isset($errs['city'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['city']) ?></span><?php endif; ?></div>
            <div class="field"><label for="invoice-state">State / province</label><select class="select <?= isset($errs['state']) ? 'invalid' : '' ?>" id="invoice-state" name="state" required autocomplete="address-level1"><?= \App\Core\View::partial('storefront/partials/state-options', ['selected' => $v('state')]) ?></select><?php if (isset($errs['state'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['state']) ?></span><?php endif; ?></div>
          </div>
          <div class="form-row">
            <div class="field"><label for="invoice-postcode">ZIP / postal code</label><input class="input <?= isset($errs['postcode']) ? 'invalid' : '' ?>" id="invoice-postcode" name="postcode" value="<?= e($v('postcode')) ?>" required autocomplete="postal-code"><?php if (isset($errs['postcode'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['postcode']) ?></span><?php endif; ?></div>
            <div class="field"><label for="invoice-country">Country</label><select class="select <?= isset($errs['country']) ? 'invalid' : '' ?>" id="invoice-country" name="country" required autocomplete="country"><option value="US"<?= selected('US', $v('country', 'US')) ?>>United States</option><option value="CA"<?= selected('CA', $v('country', 'US')) ?>>Canada</option></select><?php if (isset($errs['country'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['country']) ?></span><?php endif; ?></div>
          </div>
          <div class="field"><label for="invoice-note">Project notes <span class="muted">(optional)</span></label><textarea class="textarea" id="invoice-note" name="customer_note" rows="3" maxlength="1000" placeholder="Anything we should know about your installation?"><?= e($v('customer_note')) ?></textarea><?php if (isset($errs['customer_note'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['customer_note']) ?></span><?php endif; ?></div>
          <button class="btn btn-primary btn-lg" type="submit" <?= $summary['has_unavailable'] || $summary['discount_error'] ? 'disabled' : '' ?>>Send invoice request <?= icon('arrow-right') ?></button>
          <p class="small muted" style="margin-bottom:0">We will review availability, shipping and tax, then contact you with the final amount and next steps. Your cart stays saved. No card or payment details are needed here. <a class="bold" href="<?= url('/page/privacy') ?>">Privacy policy</a>.</p>
        </form>
      </div>
    <?php else: ?>
      <div class="card card-body" style="padding:32px;text-align:center">
        <div class="empty" style="padding:0">
          <?= icon('phone') ?>
          <h1 style="font-size:1.9rem;margin-bottom:6px">Let's finish your order by phone</h1>
          <p class="muted" style="max-width:520px;margin:0 auto 16px">Online checkout is not available right now. Your cart is saved. Call us during business hours and we will take your order and payment over the phone.</p>
          <div class="flex" style="justify-content:center;flex-wrap:wrap">
            <a class="btn btn-primary btn-lg" href="tel:8053386680">Call <?= e(Settings::get('store.phone', '805-338-6680')) ?></a>
            <a class="btn btn-outline" href="mailto:<?= e(Settings::get('store.email', 'thunder@thundermounts.com')) ?>">Email us</a>
          </div>
        </div>
      </div>
    <?php endif; ?>
    <p class="small muted mt-3" style="text-align:center"><?= e(Settings::get('store.hours', 'Monday–Friday 9:00–4:00, Saturday 10:00–2:00')) ?> · <a href="<?= url('/cart') ?>" class="bold">Review cart</a> · <a href="<?= url('/shop') ?>" class="bold">Keep shopping</a> · <a href="tel:8053386680" class="bold">Call 805-338-6680</a></p>
  </div>
</section>
