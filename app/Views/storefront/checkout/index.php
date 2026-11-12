<?php
use App\Core\Csrf;
use App\Core\Settings;
$errs = errors();
$v = static fn (string $key, string $default = '') => old($key, $data[$key] ?? $default);
$selectedMethod = (int) ($summary['shipping']['id'] ?? 0);
?>
<div class="page-head">
  <div class="container">
    <?= \App\Core\View::partial('storefront/partials/breadcrumbs', ['crumbs' => [['label' => 'Cart', 'url' => '/cart'], ['label' => 'Checkout']]]) ?>
    <div class="flex between wrap" style="align-items:flex-end;gap:16px">
      <div>
        <span class="eyebrow">Secure checkout</span>
        <h1 style="margin-bottom:4px">Checkout</h1>
        <p class="muted" style="margin:0"><?= icon('lock') ?> Secure connection · <?= $readiness['paypal'] ? 'PayPal handles online payments' : 'No payment is collected online' ?></p>
      </div>
      <div class="checkout-steps small muted"><span class="done">Cart</span><span class="active">Details &amp; payment</span><span>Confirmation</span></div>
    </div>
  </div>
</div>

<section class="section-tight" style="padding-top:10px">
  <div class="container">
    <?php if (isset($errs['general']) || isset($errs['cart']) || isset($errs['payment']) || isset($errs['tax'])): ?>
      <div class="notice notice-error">
        <?= icon('bell') ?>
        <div>
          <?php foreach (['general', 'cart', 'payment', 'tax'] as $key): ?>
            <?php if (isset($errs[$key])): ?><div><?= e($errs[$key]) ?></div><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <form action="<?= url('/checkout') ?>" method="post" id="checkout-form">
      <?= csrf_field() ?>
      <div class="checkout">
        <div>
          <div class="card">
            <div class="card-head">
              <h2 style="font-size:1.15rem;margin:0">1. Contact</h2>
              <?php if (!auth()): ?><span class="small muted">Already have an account? <a href="<?= url('/login?next=' . urlencode('/checkout')) ?>" class="bold">Sign in</a></span><?php endif; ?>
            </div>
            <div class="card-body">
              <div class="form-row">
                <div class="field">
                  <label for="email">Email address</label>
                  <input class="input <?= isset($errs['email']) ? 'invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($v('email')) ?>" required autocomplete="email">
                  <span class="hint">Order confirmation and tracking go to this address.</span>
                  <?php if (isset($errs['email'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['email']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                  <label for="phone">Phone</label>
                  <input class="input <?= isset($errs['phone']) ? 'invalid' : '' ?>" id="phone" name="phone" type="tel" value="<?= e($v('phone')) ?>" required autocomplete="tel">
                  <?php if (isset($errs['phone'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['phone']) ?></span><?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <div class="card mt-3">
            <div class="card-head"><h2 style="font-size:1.15rem;margin:0">2. Delivery address</h2></div>
            <div class="card-body">
              <div class="form-row">
                <div class="field">
                  <label for="first_name">First name</label>
                  <input class="input <?= isset($errs['first_name']) ? 'invalid' : '' ?>" id="first_name" name="first_name" value="<?= e($v('first_name')) ?>" required autocomplete="given-name">
                  <?php if (isset($errs['first_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['first_name']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                  <label for="last_name">Last name</label>
                  <input class="input <?= isset($errs['last_name']) ? 'invalid' : '' ?>" id="last_name" name="last_name" value="<?= e($v('last_name')) ?>" required autocomplete="family-name">
                  <?php if (isset($errs['last_name'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['last_name']) ?></span><?php endif; ?>
                </div>
              </div>
              <div class="field">
                <label for="company">Company <span class="muted">(optional)</span></label>
                <input class="input" id="company" name="company" value="<?= e($v('company')) ?>" autocomplete="organization">
              </div>
              <div class="field">
                <label for="address1">Street address</label>
                <input class="input <?= isset($errs['address1']) ? 'invalid' : '' ?>" id="address1" name="address1" value="<?= e($v('address1')) ?>" required autocomplete="address-line1">
                <?php if (isset($errs['address1'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['address1']) ?></span><?php endif; ?>
              </div>
              <div class="field">
                <label for="address2">Apartment, suite, etc. <span class="muted">(optional)</span></label>
                <input class="input" id="address2" name="address2" value="<?= e($v('address2')) ?>" autocomplete="address-line2">
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="city">City</label>
                  <input class="input <?= isset($errs['city']) ? 'invalid' : '' ?>" id="city" name="city" value="<?= e($v('city')) ?>" required autocomplete="address-level2">
                  <?php if (isset($errs['city'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['city']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                  <label for="state">State / province</label>
                  <select class="select <?= isset($errs['state']) ? 'invalid' : '' ?>" id="state" name="state" required autocomplete="address-level1" data-quote-trigger>
                    <?= \App\Core\View::partial('storefront/partials/state-options', ['selected' => $v('state')]) ?>
                  </select>
                  <?php if (isset($errs['state'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['state']) ?></span><?php endif; ?>
                </div>
              </div>
              <div class="form-row">
                <div class="field">
                  <label for="postcode">ZIP / postal code</label>
                  <input class="input <?= isset($errs['postcode']) ? 'invalid' : '' ?>" id="postcode" name="postcode" value="<?= e($v('postcode')) ?>" required autocomplete="postal-code" data-quote-trigger>
                  <?php if (isset($errs['postcode'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['postcode']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                  <label for="country">Country</label>
                  <select class="select" id="country" name="country" required autocomplete="country" data-quote-trigger>
                    <option value="US"<?= selected('US', $v('country', 'US')) ?>>United States</option>
                    <option value="CA"<?= selected('CA', $v('country', 'US')) ?>>Canada</option>
                  </select>
                </div>
              </div>
              <?php if (auth()): ?>
                <p class="small muted" style="margin:4px 0 0">This address will be saved to your account. <a href="<?= url('/account/addresses') ?>" class="bold" target="_blank">Manage saved addresses</a>.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="card mt-3">
            <div class="card-head"><h2 style="font-size:1.15rem;margin:0">3. Shipping method</h2><span class="small muted" id="shipping-status" aria-live="polite"></span></div>
            <div class="card-body">
              <div id="shipping-methods" data-shipping-methods>
                <?php if ($summary['shipping_options'] === []): ?>
                  <div class="notice notice-warn"><?= icon('truck') ?><div>Enter your address to see shipping options. If none appear, call <a href="tel:8053386680" class="bold">805-338-6680</a>.</div></div>
                <?php else: ?>
                  <?php foreach ($summary['shipping_options'] as $option): ?>
                    <label class="method">
                      <input type="radio" name="shipping_method" value="<?= (int) $option['id'] ?>" <?= (int) $option['id'] === $selectedMethod ? 'checked' : '' ?> required>
                      <span>
                        <strong><?= e($option['name']) ?></strong>
                        <?php if ($option['description']): ?><span class="small muted"><?= e($option['description']) ?></span><?php endif; ?>
                      </span>
                      <strong><?= $option['cents'] === 0 ? 'Free' : e(money($option['cents'])) ?></strong>
                    </label>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
              <?php if (isset($errs['shipping_method'])): ?><div class="hint" style="color:var(--red)"><?= e($errs['shipping_method']) ?></div><?php endif; ?>
            </div>
          </div>

          <div class="card mt-3">
            <div class="card-head"><h2 style="font-size:1.15rem;margin:0">4. Billing address</h2></div>
            <div class="card-body">
              <label class="check" style="margin-bottom:12px">
                <input type="checkbox" name="billing_same" value="1" id="billing_same" <?= $v('billing_same', '1') ? 'checked' : '' ?>>
                <span>Same as delivery address</span>
              </label>
              <div id="billing-fields" <?= $v('billing_same', '1') ? 'hidden' : '' ?>>
                <div class="form-row">
                  <div class="field"><label for="billing_first_name">First name</label><input class="input" id="billing_first_name" name="billing_first_name" value="<?= e($v('billing_first_name')) ?>"></div>
                  <div class="field"><label for="billing_last_name">Last name</label><input class="input" id="billing_last_name" name="billing_last_name" value="<?= e($v('billing_last_name')) ?>"></div>
                </div>
                <div class="field"><label for="billing_address1">Street address</label><input class="input" id="billing_address1" name="billing_address1" value="<?= e($v('billing_address1')) ?>"></div>
                <div class="field"><label for="billing_address2">Apartment, suite, etc. <span class="muted">(optional)</span></label><input class="input" id="billing_address2" name="billing_address2" value="<?= e($v('billing_address2')) ?>"></div>
                <div class="form-row">
                  <div class="field"><label for="billing_city">City</label><input class="input" id="billing_city" name="billing_city" value="<?= e($v('billing_city')) ?>"></div>
                  <div class="field"><label for="billing_state">State / province</label><select class="select" id="billing_state" name="billing_state"><?= \App\Core\View::partial('storefront/partials/state-options', ['selected' => $v('billing_state')]) ?></select></div>
                </div>
                <div class="form-row">
                  <div class="field"><label for="billing_postcode">ZIP / postal code</label><input class="input" id="billing_postcode" name="billing_postcode" value="<?= e($v('billing_postcode')) ?>"></div>
                  <div class="field"><label for="billing_country">Country</label><select class="select" id="billing_country" name="billing_country"><option value="US"<?= selected('US', $v('billing_country', 'US')) ?>>United States</option><option value="CA"<?= selected('CA', $v('billing_country', 'US')) ?>>Canada</option></select></div>
                </div>
              </div>
            </div>
          </div>

          <div class="card mt-3">
            <div class="card-head"><h2 style="font-size:1.15rem;margin:0">5. Payment</h2></div>
            <div class="card-body">
              <div id="checkout-errors" class="notice notice-error" hidden></div>

              <?php if (!$readiness['paypal'] && $readiness['manual']): ?>
                <div class="notice notice-info"><?= icon('file-text') ?><div><strong>Pay by invoice.</strong> Place the order and we will email payment instructions. Your order is confirmed once payment is received.</div></div>
              <?php endif; ?>

              <?php if ($readiness['paypal']): ?>
                <div class="pay-block">
                  <div class="pay-heading">
                    <span class="pay-logo paypal">PayPal</span>
                    <span class="small muted">PayPal, cards, Venmo, Pay Later and wallets where available</span>
                  </div>
                  <div id="paypal-buttons" class="paypal-buttons" data-paypal-container></div>
                  <div id="paypal-loading" class="small muted" style="padding:8px 0"><?= icon('lock') ?> Loading secure payment options…</div>
                  <div id="paypal-failed" class="notice notice-warn" hidden><?= icon('lock') ?><div>Secure payment could not load. Check your connection and reload, or call <a href="tel:8053386680" class="bold">805-338-6680</a>.</div></div>

                  <div id="wallet-block" hidden>
                    <div class="or"><span>or pay with a wallet</span></div>
                    <div class="wallet-row">
                      <button type="button" class="apple-pay-btn" id="apple-pay-button" hidden aria-label="Pay with Apple Pay">&#63743; Pay</button>
                      <div id="google-pay-button" class="google-pay-slot"></div>
                    </div>
                  </div>

                  <div id="card-block" hidden>
                    <div class="or"><span>or pay with a card</span></div>
                    <div class="card-fields">
                      <div class="field"><label for="card-number-field">Card number</label><div id="card-number-field" class="card-field"></div></div>
                      <div class="form-row">
                        <div class="field"><label for="card-expiry-field">Expiry</label><div id="card-expiry-field" class="card-field"></div></div>
                        <div class="field"><label for="card-cvv-field">Security code</label><div id="card-cvv-field" class="card-field"></div></div>
                      </div>
                      <div class="field"><label for="card-name-field">Name on card</label><div id="card-name-field" class="card-field"></div></div>
                      <button type="button" class="btn btn-dark btn-block btn-lg" id="card-submit">Pay with card</button>
                      <p class="tiny muted" style="text-align:center;margin:8px 0 0">Card details are entered in PayPal's secure fields. You may be asked to authenticate with your bank.</p>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($readiness['manual']): ?>
                <div class="pay-block" style="margin-top:16px;border-top:1px solid var(--line);padding-top:16px">
                  <h3 style="font-size:1rem;margin:0 0 4px">Other ways to pay</h3>
                  <p class="small muted"><?= e(Settings::get('checkout.manual_payment_instructions', 'Place your order and we will email payment instructions.')) ?></p>
                  <button type="submit" class="btn btn-outline" formaction="<?= url('/checkout/manual') ?>" data-manual-order>Place order — <?= e(Settings::get('checkout.manual_payment_label', 'Pay by invoice')) ?></button>
                </div>
              <?php endif; ?>

              <div class="mt-3">
                <label class="check">
                  <input type="checkbox" name="accept_terms" value="1" id="accept_terms" <?= $v('accept_terms') ? 'checked' : '' ?> required>
                  <span>I agree to the <a href="<?= url('/page/terms') ?>" target="_blank" class="bold">Terms</a>, <a href="<?= url('/page/returns') ?>" target="_blank" class="bold">returns policy</a> and <a href="<?= url('/page/privacy') ?>" target="_blank" class="bold">Privacy Policy</a>.</span>
                </label>
                <?php if (isset($errs['accept_terms'])): ?><div class="hint" style="color:var(--red)"><?= e($errs['accept_terms']) ?></div><?php endif; ?>
              </div>

              <?php if (!auth()): ?>
                <div class="divider"></div>
                <label class="check">
                  <input type="checkbox" name="create_account" value="1" id="create_account" <?= $v('create_account') ? 'checked' : '' ?>>
                  <span>Create an account so I can track this order</span>
                </label>
                <div class="field" id="account-password-field" <?= $v('create_account') ? '' : 'hidden' ?> style="max-width:320px;margin-top:10px">
                  <label for="account-password">Choose a password</label>
                  <input class="input <?= isset($errs['password']) ? 'invalid' : '' ?>" id="account-password" name="password" type="password" minlength="8" autocomplete="new-password">
                  <span class="hint">At least 8 characters.</span>
                  <?php if (isset($errs['password'])): ?><span class="hint" style="color:var(--red)"><?= e($errs['password']) ?></span><?php endif; ?>
                </div>
              <?php endif; ?>

              <div class="mt-2">
                <label for="customer_note" class="small muted">Order note <span>(optional — delivery instructions or fitment questions)</span></label>
                <textarea class="textarea" id="customer_note" name="customer_note" rows="3" maxlength="1000"><?= e($v('customer_note')) ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <aside class="summary-sticky">
          <div class="card">
            <div class="card-head"><h2 style="font-size:1.1rem;margin:0">Order summary</h2><span class="small muted"><?= (int) $summary['count'] ?> item<?= (int) $summary['count'] === 1 ? '' : 's' ?></span></div>
            <div class="card-body">
              <?php foreach ($summary['items'] as $item): ?>
                <div class="cart-line">
                  <img src="<?= e(upload_url($item['image'])) ?>" alt="">
                  <div>
                    <div class="name"><?= e($item['product']['name']) ?></div>
                    <div class="meta">Qty <?= (int) $item['qty'] ?><?= $item['product']['price_info']['is_dealer'] ? ' · dealer price' : '' ?></div>
                  </div>
                  <strong><?= e(money($item['line_cents'])) ?></strong>
                </div>
              <?php endforeach; ?>

              <div class="totals mt-2">
                <div class="totals-row"><span>Subtotal</span><span data-total-subtotal><?= e(money($summary['subtotal_cents'])) ?></span></div>
                <?php if ($summary['discount_amount_cents'] > 0): ?>
                  <div class="totals-row" style="color:var(--green)"><span>Discount <?= e($summary['discount_code']) ?></span><span data-total-discount>−<?= e(money($summary['discount_amount_cents'])) ?></span></div>
                <?php endif; ?>
                <div class="totals-row"><span>Shipping</span><span data-total-shipping><?= e(money($summary['shipping_cents'])) ?></span></div>
                <div class="totals-row"><span>Tax <span class="small muted" data-tax-name><?= e($summary['tax_name']) ?></span></span><span data-total-tax><?= e(money($summary['tax_cents'])) ?></span></div>
                <div class="totals-row total"><span>Total</span><span data-total-total><?= e(money($summary['total_cents'])) ?></span></div>
              </div>
              <p class="tiny muted" style="margin:8px 0 0">USD. Shipping and tax are recalculated when you change your address.</p>
            </div>
          </div>

          <div class="card card-body mt-2 small">
            <strong><?= icon('lock') ?> <?= $readiness['paypal'] ? 'Secure payment' : 'Order review' ?></strong>
            <p class="muted" style="margin:4px 0 0"><?= $readiness['paypal'] ? 'Payment is processed by PayPal. We never see or store your card number.' : 'No online payment is taken. We will email you about the invoice and next steps.' ?> Need help? Call <a href="tel:8053386680" class="bold">805-338-6680</a> or email <a href="mailto:thunder@thundermounts.com" class="bold">thunder@thundermounts.com</a>.</p>
          </div>

          <div class="card card-body mt-2 small muted">
            <strong style="color:var(--ink)">Ordering for a business?</strong>
            <p style="margin:4px 0 0">Approved dealers get trade pricing automatically. <a href="<?= url('/dealer/apply') ?>" class="bold">Apply for a dealer account</a>.</p>
          </div>
        </aside>
      </div>
    </form>
  </div>
</section>

<?php if ($readiness['paypal'] && $paypalSdk !== ''): ?>
<script src="<?= e($paypalSdk) ?>" data-paypal-sdk></script>
<script>
  window.TM_CHECKOUT = {
    create: <?= json_encode(url('/checkout/paypal/create')) ?>,
    capture: <?= json_encode(url('/checkout/paypal/capture')) ?>,
    quote: <?= json_encode(url('/checkout/shipping')) ?>,
    csrf: <?= json_encode(Csrf::token()) ?>,
    mode: <?= json_encode($paypal->mode()) ?>,
    currency: 'USD',
    storeName: <?= json_encode(setting('store.name', 'Thunder Mounts')) ?>,
    totalCents: <?= (int) $summary['total_cents'] ?>,
    applePay: <?= $readiness['paypal'] ? 'true' : 'false' ?>
  };
</script>
<script src="<?= asset('js/checkout.js') ?>" defer></script>
<script src="https://pay.google.com/gp/p/js/pay.js" defer></script>
<?php endif; ?>
