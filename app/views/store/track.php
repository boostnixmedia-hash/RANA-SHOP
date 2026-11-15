<section class="wrap narrow auth">
  <h1>Track your order</h1>
  <p class="muted">Enter the order ID from your confirmation email and the phone number you used.</p>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif ?>
  <form method="post" class="form"><?= csrf_field() ?>
    <label>Order ID<input name="order_no" placeholder="e.g. RS2611151234" required value="<?= e(post('order_no')) ?>"></label>
    <label>Phone number<input name="phone" inputmode="tel" placeholder="01XXXXXXXXX" required value="<?= e(post('phone')) ?>"></label>
    <button class="btn block">Track order</button>
  </form>
</section>
