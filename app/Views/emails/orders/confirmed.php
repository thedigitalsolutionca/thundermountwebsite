<h1 style="font-size:22px;margin:0 0 12px;letter-spacing:-.02em;">Thank you — we have your order</h1>
<p>Hi <?= e($order['shipping_address_data']['first_name'] ?? 'there') ?>,</p>
<p>Your Thunder Mounts order is confirmed and we are preparing it for shipment. We will email tracking details as soon as it leaves our shop in Simi Valley.</p>
<?= \App\Core\View::partial('emails/partials/order-summary', ['order' => $order]) ?>
<p style="margin:0;">Questions? Reply to this email or call <a href="tel:8053386680" style="color:#C82026;">805-338-6680</a>.</p>
