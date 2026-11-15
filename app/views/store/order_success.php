<section class="wrap narrow success">
  <?php if ($o['payment_method'] !== 'cod' && $o['payment_status'] !== 'paid'): ?>
    <div class="ok-ic warn"><?= icon('cash', 34) ?></div>
    <h1>Payment pending</h1>
    <p>Your order <b><?= e($o['order_no']) ?></b> is saved but the payment is not complete yet. Items are held for <?= UNPAID_ONLINE_HOLD_MIN ?> minutes.</p>
    <div class="btns center"><a class="btn" href="<?= e(url('pay/' . $o['order_no'] . '?t=' . $o['access_token'])) ?>">Pay now with <?= e(pay_label($o['payment_method'])) ?></a></div>
  <?php else: ?>
    <div class="ok-ic"><?= icon('check', 34) ?></div>
    <h1>Thank you, <?= e(explode(' ', $o['name'])[0]) ?>!</h1>
    <p>Your order <b><?= e($o['order_no']) ?></b> has been placed<?= $o['payment_status'] === 'paid' ? ' and paid' : '' ?>. <?= $o['email'] ? 'A confirmation has been sent to <b>' . e($o['email']) . '</b>.' : '' ?> We will call you on <b><?= e($o['phone']) ?></b> to confirm.</p>
  <?php endif ?>
  <div class="order-box">
    <?php foreach ($items as $it): ?><div class="co-line"><span class="cl-img"><?php if ($it['image']): ?><img src="<?= e(media($it['image'])) ?>" alt=""><?php endif ?><i><?= (int)$it['qty'] ?></i></span><span class="grow"><b><?= e($it['name']) ?></b><small class="muted"><?= e(trim($it['color'] . ' / ' . $it['size'], ' /')) ?></small></span><span><?= money($it['line_total']) ?></span></div><?php endforeach ?>
    <div class="row"><span>Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
    <?php if ($o['discount'] > 0): ?><div class="row disc"><span>Discount</span><span>-<?= money($o['discount']) ?></span></div><?php endif ?>
    <div class="row"><span>Delivery</span><span><?= $o['shipping'] > 0 ? money($o['shipping']) : 'Free' ?></span></div>
    <div class="row total"><span>Total</span><span><?= money($o['total']) ?></span></div>
    <div class="row muted"><span>Payment</span><span><?= e(pay_label($o['payment_method'])) ?> · <?= e(status_label($o['payment_status'])) ?></span></div>
    <p class="muted small">Deliver to: <?= e($o['name']) ?>, <?= e($o['address']) ?>, <?= e($o['area']) ?>, <?= e($o['district']) ?></p>
  </div>
  <div class="btns center"><a class="btn ghost" href="<?= e(url(order_view_url($o))) ?>">Track this order</a><a class="btn" href="<?= e(url('shop')) ?>">Continue shopping</a></div>
  <?php if (!customer() && !val('SELECT pass_hash FROM customers WHERE email=?', [$o['email']])): ?><p class="muted small center"><a href="<?= e(url('account/register')) ?>">Create an account</a> with <?= e($o['email']) ?> to see all your orders in one place.</p><?php endif ?>
</section>
<?php if (setting('meta_pixel_id') && empty($_SESSION['tracked'][$o['order_no']])): $_SESSION['tracked'][$o['order_no']] = 1; ?>
<script>window.addEventListener('load',function(){if(window.fbq)fbq('track','Purchase',{value:<?= (float)$o['total'] ?>,currency:'BDT'});if(window.gtag)gtag('event','purchase',{transaction_id:<?= json_encode($o['order_no']) ?>,value:<?= (float)$o['total'] ?>,currency:'BDT'});});</script>
<?php endif ?>
