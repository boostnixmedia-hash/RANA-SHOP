<?php $steps = ['new' => 'Placed', 'confirmed' => 'Confirmed', 'processing' => 'Packing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$order = array_keys($steps); $cur = $o['status'] === 'pending' ? 'new' : $o['status']; $ci = array_search($cur, $order, true);
$trackUrl = courier_tracking_url($o); ?>
<section class="wrap narrow order-view">
  <?php if (!empty($in_account)): ?><a class="link-arrow" href="<?= e(url('account/orders')) ?>"><?= icon('chevron-left', 16) ?> My orders</a><?php endif ?>
  <h1>Order <?= e($o['order_no']) ?></h1>
  <p class="muted">Placed on <?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></p>
  <?php if (in_array($o['status'], ['cancelled', 'returned'], true)): ?>
    <div class="alert">This order was <?= e($o['status']) ?>.</div>
  <?php else: ?>
    <ol class="track-steps"><?php foreach ($steps as $k => $l): $i = array_search($k, $order, true); ?><li class="<?= $ci !== false && $i <= $ci ? 'done' : '' ?><?= $i === $ci ? ' cur' : '' ?>"><span></span><?= $l ?></li><?php endforeach ?></ol>
  <?php endif ?>
  <?php if ($o['tracking_no']): ?><div class="track-box"><?= icon('truck', 20) ?> <span><b><?= e($o['courier'] ?: 'Courier') ?></b> tracking: <?= e($o['tracking_no']) ?></span><?php if ($trackUrl): ?><a class="btn sm" href="<?= e($trackUrl) ?>" target="_blank" rel="noopener">Track parcel</a><?php endif ?></div><?php endif ?>
  <?php if ($o['payment_method'] !== 'cod' && in_array($o['payment_status'], ['pending', 'failed'], true) && !in_array($o['status'], ['cancelled', 'returned'], true)): ?>
    <div class="alert">Payment not completed. <a class="btn sm" href="<?= e(url('pay/' . $o['order_no'] . '?t=' . $o['access_token'])) ?>">Pay now</a></div>
  <?php endif ?>
  <div class="order-box">
    <?php foreach ($items as $it): ?><div class="co-line"><span class="cl-img"><?php if ($it['image']): ?><img src="<?= e(media($it['image'])) ?>" alt=""><?php endif ?><i><?= (int)$it['qty'] ?></i></span><span class="grow"><b><?= e($it['name']) ?></b><small class="muted"><?= e(trim($it['color'] . ' / ' . $it['size'], ' /')) ?> · <?= money($it['price']) ?></small></span><span><?= money($it['line_total']) ?></span></div><?php endforeach ?>
    <div class="row"><span>Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
    <?php if ($o['discount'] > 0): ?><div class="row disc"><span>Discount<?= $o['coupon_code'] ? ' (' . e($o['coupon_code']) . ')' : '' ?></span><span>-<?= money($o['discount']) ?></span></div><?php endif ?>
    <div class="row"><span>Delivery</span><span><?= $o['shipping'] > 0 ? money($o['shipping']) : 'Free' ?></span></div>
    <div class="row total"><span>Total</span><span><?= money($o['total']) ?></span></div>
  </div>
  <div class="two">
    <div><h3>Delivery</h3><p><?= e($o['name']) ?><br><?= e($o['phone']) ?><br><?= e($o['address']) ?><br><?= e($o['area']) ?>, <?= e($o['district']) ?></p></div>
    <div><h3>Payment</h3><p><?= e(pay_label($o['payment_method'])) ?><br>Status: <b><?= e(status_label($o['payment_status'])) ?></b></p></div>
  </div>
  <?php $public = array_filter($history, fn($h) => in_array($h['status'], array_merge(ORDER_STATUSES, ['payment paid', 'payment refunded']), true)); if ($public): ?>
  <h3>Updates</h3><ul class="timeline"><?php foreach (array_reverse($public) as $h): ?><li><b><?= e(status_label(str_replace('payment ', '', $h['status']))) ?><?= str_starts_with($h['status'], 'payment') ? ' (payment)' : '' ?></b><span class="muted"><?= date('d M, h:i A', strtotime($h['created_at'])) ?></span></li><?php endforeach ?></ul>
  <?php endif ?>
  <p class="muted small">Questions about this order? <?php if ($p = setting('contact_phone')): ?>Call <a href="tel:<?= e($p) ?>"><?= e($p) ?></a> or <?php endif ?><a href="<?= e(url('contact')) ?>">contact us</a>.</p>
</section>
