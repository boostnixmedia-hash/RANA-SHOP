<?php $err = fn($k) => isset($errors[$k]) ? '<span class="ferr">' . e($errors[$k]) . '</span>' : ''; ?>
<section class="wrap narrow auth">
  <h1>Create account</h1>
  <p class="muted">Track orders, save addresses and keep a wishlist.</p>
  <form method="post" class="form"><?= csrf_field() ?>
    <label>Full name<input name="name" autocomplete="name" required value="<?= e(old('name')) ?>"><?= $err('name') ?></label>
    <label>Email<input type="email" name="email" autocomplete="email" required value="<?= e(old('email')) ?>"><?= $err('email') ?></label>
    <label>Mobile (optional)<input name="phone" type="tel" placeholder="01XXXXXXXXX" value="<?= e(old('phone')) ?>"><?= $err('phone') ?></label>
    <label>Password (8+ characters)<input type="password" name="password" autocomplete="new-password" required minlength="8"><?= $err('password') ?></label>
    <label>Confirm password<input type="password" name="password2" autocomplete="new-password" required><?= $err('password2') ?></label>
    <button class="btn block">Create account</button>
  </form>
  <p class="center muted">Already have an account? <a href="<?= e(url('account/login')) ?>">Log in</a></p>
</section>
<?php clear_old(); ?>
