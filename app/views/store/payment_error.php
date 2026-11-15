<section class="wrap narrow center" style="padding:70px 16px">
  <h1>Payment could not start</h1>
  <p class="muted"><?= e($error) ?></p>
  <p>Your order <b><?= e($o['order_no']) ?></b> is saved. You can try again, or contact us to pay another way.</p>
  <div class="btns center"><a class="btn" href="<?= e(url('pay/' . $o['order_no'] . '?t=' . $o['access_token'])) ?>">Try again</a><a class="btn ghost" href="<?= e(url(order_view_url($o))) ?>">View order</a></div>
</section>
