<?php
$errs = $_SESSION['errors'] ?? []; unset($_SESSION['errors']);
$c = $customer; $def = $addresses[0] ?? null;
$o = fn($k, $d = '') => e(old($k, $d));
$err = fn($k) => isset($errs[$k]) ? '<span class="ferr">' . e($errs[$k]) . '</span>' : '';
$t = cart_totals($lines, shipping_zone(old('district', $def['district'] ?? 'Dhaka')), $_SESSION['coupon'] ?? null, $c['email'] ?? '');
?>
<section class="wrap checkout">
  <div class="co-head"><a class="logo-sm" href="<?= e(url('/')) ?>"><?= e(mb_strtoupper(setting('store_name'))) ?></a><ol class="steps"><li class="done">Bag</li><li class="on">Details</li><li>Done</li></ol></div>
  <form method="post" action="<?= e(url('checkout')) ?>" class="co-grid" id="coForm" novalidate>
    <?= csrf_field() ?><input type="hidden" name="idem_key" value="<?= e($_SESSION['checkout_key']) ?>">
    <div class="co-main">
      <?php if (!$c): ?><p class="co-login">Have an account? <a href="<?= e(url('account/login')) ?>">Log in</a> for faster checkout.</p><?php endif ?>
      <h2>Contact</h2>
      <div class="f2">
        <label class="<?= isset($errs['name']) ? 'bad' : '' ?>">Full name *<input name="name" autocomplete="name" required value="<?= $o('name', $def['name'] ?? $c['name'] ?? '') ?>"><?= $err('name') ?></label>
        <label class="<?= isset($errs['phone']) ? 'bad' : '' ?>">Mobile number *<input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" required value="<?= $o('phone', $def['phone'] ?? $c['phone'] ?? '') ?>"><?= $err('phone') ?></label>
      </div>
      <label class="<?= isset($errs['email']) ? 'bad' : '' ?>">Email<?= setting('require_email', 1) ? ' *' : '' ?> <small class="muted">(order confirmation is sent here)</small><input name="email" type="email" autocomplete="email" <?= setting('require_email', 1) ? 'required' : '' ?> value="<?= $o('email', $c['email'] ?? '') ?>"><?= $err('email') ?></label>

      <h2>Delivery address</h2>
      <?php if ($addresses): ?><div class="saved-addr"><?php foreach ($addresses as $i => $a): ?>
        <label class="addr-pick"><input type="radio" name="_addr" <?= $i ? '' : 'checked' ?> data-fill='<?= e(json_encode(['name' => $a['name'], 'phone' => $a['phone'], 'district' => $a['district'], 'area' => $a['area'], 'address' => $a['address']])) ?>'><span><b><?= e($a['label']) ?></b> · <?= e($a['address']) ?>, <?= e($a['area']) ?>, <?= e($a['district']) ?></span></label>
      <?php endforeach ?></div><?php endif ?>
      <div class="f2">
        <label class="<?= isset($errs['district']) ? 'bad' : '' ?>">District *<select name="district" id="district" required>
          <option value="">Select district</option>
          <?php $dsel = old('district', $def['district'] ?? ''); foreach (bd_districts() as $d): ?><option <?= $dsel === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach ?>
        </select><?= $err('district') ?></label>
        <label class="<?= isset($errs['area']) ? 'bad' : '' ?>">Area / Thana *<input name="area" required placeholder="e.g. Dhanmondi" value="<?= $o('area', $def['area'] ?? '') ?>"><?= $err('area') ?></label>
      </div>
      <label class="<?= isset($errs['address']) ? 'bad' : '' ?>">Full address *<textarea name="address" rows="2" required placeholder="House, road, block, landmark"><?= $o('address', $def['address'] ?? '') ?></textarea><?= $err('address') ?></label>
      <label>Order note (optional)<textarea name="note" rows="2" placeholder="Anything we should know?"><?= $o('note') ?></textarea></label>
      <?php if ($c): ?><label class="check"><input type="checkbox" name="save_address" value="1" <?= $addresses ? '' : 'checked' ?>> Save this address to my account</label><?php endif ?>

      <h2>Payment</h2>
      <div class="pay-opts <?= isset($errs['payment_method']) ? 'bad' : '' ?>">
        <?php $psel = old('payment_method', array_key_first($methods)); foreach ($methods as $k => $l): ?>
        <label class="pay-opt"><input type="radio" name="payment_method" value="<?= e($k) ?>" <?= $psel === $k ? 'checked' : '' ?>><span class="po-dot"></span><span><b><?= e($l) ?></b><small><?= ['cod' => 'Pay in cash when your order arrives', 'bkash' => 'Pay securely with your bKash account', 'nagad' => 'Pay securely with your Nagad account', 'sslcommerz' => 'Visa, Mastercard, Amex, Rocket and more'][$k] ?></small></span></label>
        <?php endforeach ?>
        <?= $err('payment_method') ?>
      </div>
    </div>

    <aside class="summary co-sum">
      <h3>Order summary <span class="muted">(<?= array_sum(array_column($lines, 'qty')) ?>)</span></h3>
      <div class="co-lines">
      <?php foreach ($lines as $l): ?>
        <div class="co-line"><span class="cl-img"><?php if ($l['image']): ?><img src="<?= e(media($l['image'])) ?>" alt=""><?php endif ?><i><?= (int)$l['qty'] ?></i></span><span class="grow"><b><?= e($l['name']) ?></b><small class="muted"><?= e(trim($l['color'] . ($l['size'] ? ' / ' . $l['size'] : ''), ' /')) ?></small></span><span><?= money($l['line_total']) ?></span></div>
        <?php if (!$l['ok']): ?><div class="cl-warn">Only <?= (int)$l['stock'] ?> left of this item. <a href="<?= e(url('cart')) ?>">Update bag</a></div><?php endif ?>
      <?php endforeach ?>
      </div>
      <div class="coupon"><input name="coupon" id="couponIn" placeholder="Coupon code" value="<?= e(old('coupon', $_SESSION['coupon'] ?? '')) ?>" aria-label="Coupon code"><button type="button" class="btn ghost sm" id="couponBtn">Apply</button></div>
      <div class="ferr" id="couponErr"></div>
      <div class="row"><span>Subtotal</span><span id="tSub"><?= money($t['subtotal']) ?></span></div>
      <div class="row disc" id="tDiscRow" <?= $t['discount'] > 0 ? '' : 'hidden' ?>><span>Discount</span><span id="tDisc">-<?= money($t['discount']) ?></span></div>
      <div class="row"><span>Delivery <small class="muted" id="tZone"><?= $t['zone'] === 'inside' ? '(inside Dhaka)' : '(outside Dhaka)' ?></small></span><span id="tShip"><?= $t['shipping'] > 0 ? money($t['shipping']) : 'Free' ?></span></div>
      <div class="row total"><span>Total</span><span id="tTotal"><?= money($t['total']) ?></span></div>
      <button class="btn block lg" id="placeBtn">Place order</button>
      <p class="muted small center">By placing your order you agree to our <a href="<?= e(url('terms')) ?>">Terms</a> and <a href="<?= e(url('return-policy')) ?>">Return policy</a>.</p>
    </aside>
  </form>
</section>
<?php clear_old(); ?>
