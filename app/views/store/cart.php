<section class="page-head"><div class="wrap"><h1>Your Bag</h1></div></section>
<section class="wrap cart-page">
<?php if (!$lines): ?>
  <div class="empty"><?= icon('bag', 40) ?><p>Your bag is empty.</p><a class="btn" href="<?= e(url('shop')) ?>">Start shopping</a></div>
<?php else: ?>
  <div class="cart-grid">
    <div>
      <table class="cart-table">
        <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($lines as $l): ?>
          <tr class="<?= $l['ok'] ? '' : 'warn' ?>">
            <td class="ct-prod"><a href="<?= e(url('product/' . $l['slug'])) ?>" class="cl-img"><?php if ($l['image']): ?><img src="<?= e(media($l['image'])) ?>" alt=""><?php endif ?></a>
              <div><a href="<?= e(url('product/' . $l['slug'])) ?>"><b><?= e($l['name']) ?></b></a><div class="muted small"><?= e(trim($l['color'] . ($l['size'] ? ' / ' . $l['size'] : ''), ' /')) ?></div>
              <?php if (!$l['ok']): ?><div class="cl-warn">Only <?= (int)$l['stock'] ?> left. Please reduce quantity.</div><?php endif ?>
              <form method="post" action="<?= e(url('cart/update')) ?>"><?= csrf_field() ?><input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>"><input type="hidden" name="qty" value="0"><button class="link small"><?= icon('trash', 14) ?> Remove</button></form></div></td>
            <td data-l="Price"><?= money($l['price']) ?></td>
            <td data-l="Qty"><form method="post" action="<?= e(url('cart/update')) ?>" class="qty-form"><?= csrf_field() ?><input type="hidden" name="variant_id" value="<?= (int)$l['variant_id'] ?>">
              <div class="qty sm"><button name="qty" value="<?= $l['qty'] - 1 ?>" aria-label="Decrease"><?= icon('minus', 14) ?></button><span><?= (int)$l['qty'] ?></span><button name="qty" value="<?= $l['qty'] + 1 ?>" aria-label="Increase"><?= icon('plus', 14) ?></button></div></form></td>
            <td data-l="Total"><b><?= money($l['line_total']) ?></b></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <a class="link-arrow" href="<?= e(url('shop')) ?>"><?= icon('chevron-left', 16) ?> Continue shopping</a>
    </div>
    <aside class="summary">
      <h3>Order summary</h3>
      <form method="post" action="<?= e(url('cart/coupon')) ?>" class="coupon"><?= csrf_field() ?>
        <?php if ($t['coupon']): ?><div class="coupon-on"><?= icon('tag', 16) ?> <b><?= e($t['coupon']['code']) ?></b> applied <button name="remove" value="1" class="link small">Remove</button></div>
        <?php else: ?><input name="code" placeholder="Coupon code" aria-label="Coupon code"><button class="btn ghost sm">Apply</button><?php endif ?>
      </form>
      <div class="row"><span>Subtotal</span><span><?= money($t['subtotal']) ?></span></div>
      <?php if ($t['discount'] > 0): ?><div class="row disc"><span>Discount</span><span>-<?= money($t['discount']) ?></span></div><?php endif ?>
      <div class="row muted"><span>Delivery</span><span>Calculated at checkout</span></div>
      <div class="row total"><span>Total</span><span><?= money($t['subtotal'] - $t['discount']) ?></span></div>
      <a class="btn block" href="<?= e(url('checkout')) ?>">Proceed to checkout</a>
      <p class="muted small center"><?= icon('shield', 14) ?> Secure checkout · Cash on Delivery available</p>
    </aside>
  </div>
<?php endif ?>
</section>
