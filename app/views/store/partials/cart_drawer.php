<?php if (!$lines): ?>
<div class="empty-cart">
  <?= icon('bag', 40) ?>
  <p>Your bag is empty.</p>
  <a class="btn" href="<?= e(url('shop')) ?>">Start shopping</a>
</div>
<?php else: ?>
<div class="cart-lines">
<?php foreach ($lines as $l): ?>
  <div class="cline<?= $l['ok'] ? '' : ' warn' ?>">
    <a href="<?= e(url('product/' . $l['slug'])) ?>" class="cl-img"><?php if ($l['image']): ?><img src="<?= e(media($l['image'])) ?>" alt=""><?php endif ?></a>
    <div class="cl-info">
      <a href="<?= e(url('product/' . $l['slug'])) ?>" class="cl-name"><?= e($l['name']) ?></a>
      <div class="cl-opt"><?= e(trim($l['color'] . ($l['size'] ? ' / ' . $l['size'] : ''), ' /')) ?></div>
      <?php if (!$l['ok']): ?><div class="cl-warn">Only <?= (int)$l['stock'] ?> left</div><?php endif ?>
      <div class="cl-row">
        <div class="qty sm" data-variant="<?= (int)$l['variant_id'] ?>">
          <button data-q="-1" aria-label="Decrease"><?= icon('minus', 14) ?></button><span><?= (int)$l['qty'] ?></span><button data-q="1" aria-label="Increase"><?= icon('plus', 14) ?></button>
        </div>
        <b><?= money($l['line_total']) ?></b>
      </div>
    </div>
    <button class="cl-rm" data-remove="<?= (int)$l['variant_id'] ?>" aria-label="Remove"><?= icon('x', 16) ?></button>
  </div>
<?php endforeach ?>
</div>
<div class="cart-foot">
  <div class="row"><span>Subtotal</span><b><?= money($t['subtotal']) ?></b></div>
  <?php if ($t['discount'] > 0): ?><div class="row muted"><span>Discount (<?= e($t['coupon']['code']) ?>)</span><span>-<?= money($t['discount']) ?></span></div><?php endif ?>
  <?php $free = (float)setting('free_ship_over', 0); if ($free > 0): $left = $free - ($t['subtotal'] - $t['discount']); ?>
    <div class="ship-bar"><?= $left > 0 ? 'Add <b>' . money($left) . '</b> more for free delivery' : 'You get <b>free delivery</b>' ?><i style="--p:<?= min(100, (int)(($t['subtotal'] - $t['discount']) / $free * 100)) ?>%"></i></div>
  <?php endif ?>
  <p class="muted small">Delivery charge is calculated at checkout.</p>
  <a class="btn block" href="<?= e(url('checkout')) ?>">Checkout</a>
  <a class="btn ghost block" href="<?= e(url('cart')) ?>">View bag</a>
</div>
<?php endif ?>
