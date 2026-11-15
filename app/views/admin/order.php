<?php $wa = '88' . ltrim($o['phone'], '0'); $wa = preg_replace('/^8888/', '88', $wa); ?>
<div class="order-top">
  <span class="st st-<?= e($o['status']) ?> lg"><?= e(status_label($o['status'])) ?></span>
  <span class="st st-<?= e($o['payment_status']) ?> lg"><?= e(pay_label($o['payment_method'])) ?>: <?= e(status_label($o['payment_status'])) ?></span>
  <span class="muted"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></span>
  <a class="btn ghost sm" href="<?= e(url('admin/orders')) ?>">← All orders</a>
</div>
<div class="grid2">
  <div>
    <div class="card"><h2>Items</h2>
      <?php foreach ($items as $it): ?><div class="oi"><?php if ($it['image']): ?><img src="<?= e(media($it['image'])) ?>" alt=""><?php endif ?><div class="grow"><b><?= e($it['name']) ?></b><small class="muted">Color: <?= e($it['color'] ?: '—') ?> · Size: <?= e($it['size'] ?: '—') ?> · SKU <?= e($it['sku']) ?></small></div><div class="r"><?= (int)$it['qty'] ?> × <?= money($it['price']) ?><br><b><?= money($it['line_total']) ?></b></div></div><?php endforeach ?>
      <div class="sum"><span>Subtotal</span><span><?= money($o['subtotal']) ?></span></div>
      <div class="sum"><span>Discount<?= $o['coupon_code'] ? ' (' . e($o['coupon_code']) . ')' : '' ?></span><span>-<?= money($o['discount']) ?></span></div>
      <div class="sum"><span>Delivery (<?= $o['shipping_zone'] === 'inside' ? 'inside Dhaka' : 'outside Dhaka' ?>)</span><span><?= money($o['shipping']) ?></span></div>
      <div class="sum total"><span>Total</span><span><?= money($o['total']) ?></span></div>
    </div>
    <div class="card"><h2>Update status</h2>
      <form method="post" class="row-form"><?= csrf_field() ?><input type="hidden" name="action" value="status">
        <select name="status"><?php foreach (ORDER_STATUSES as $s): ?><option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= status_label($s) ?></option><?php endforeach ?></select>
        <input name="note" placeholder="Note (optional)"><button class="btn sm">Save</button></form>
      <p class="muted small">The customer gets an email when you set Confirmed, Processing, Shipped, Delivered or Cancelled. Cancelled/Returned puts the stock back.</p>
      <form method="post" class="row-form"><?= csrf_field() ?><input type="hidden" name="action" value="payment"><label class="muted small">Payment</label>
        <select name="payment_status"><?php foreach (['pending', 'paid', 'failed', 'refunded'] as $s): ?><option value="<?= $s ?>" <?= $o['payment_status'] === $s ? 'selected' : '' ?>><?= status_label($s) ?></option><?php endforeach ?></select><button class="btn ghost sm">Save</button></form>
      <form method="post" class="row-form"><?= csrf_field() ?><input type="hidden" name="action" value="tracking">
        <input name="courier" placeholder="Courier (e.g. Steadfast)" value="<?= e($o['courier']) ?>"><input name="tracking_no" placeholder="Tracking number" value="<?= e($o['tracking_no']) ?>"><button class="btn ghost sm">Save</button></form>
    </div>
  </div>
  <div>
    <div class="card"><h2>Customer</h2>
      <p><b><?= e($o['name']) ?></b><br><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a><br><?php if ($o['email']): ?><a href="mailto:<?= e($o['email']) ?>"><?= e($o['email']) ?></a><?php endif ?></p>
      <p><?= e($o['address']) ?><br><?= e($o['area']) ?>, <?= e($o['district']) ?></p>
      <?php if ($o['note']): ?><p class="note">Customer note: <?= e($o['note']) ?></p><?php endif ?>
      <div class="btn-row"><a class="btn sm" href="tel:<?= e($o['phone']) ?>">Call</a><a class="btn ghost sm" target="_blank" rel="noopener" href="https://wa.me/<?= e($wa) ?>?text=<?= rawurlencode('Hello ' . $o['name'] . ', about your ' . setting('store_name') . ' order ' . $o['order_no']) ?>">WhatsApp</a></div>
    </div>
    <div class="card"><h2>Internal note</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="note"><textarea name="admin_note" rows="3" placeholder="Only admins see this"><?= e($o['admin_note']) ?></textarea><button class="btn ghost sm">Save note</button></form></div>
    <div class="card"><h2>History</h2><ul class="hist"><?php foreach ($history as $h): ?><li><b><?= e(status_label($h['status'])) ?></b> <?= e($h['note']) ?><small class="muted"><?= date('d M, h:i A', strtotime($h['created_at'])) ?> · <?= e($h['admin_name'] ?: 'Customer') ?></small></li><?php endforeach ?></ul></div>
  </div>
</div>
