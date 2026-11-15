<section class="wrap narrow auth">
  <h1>Log in</h1>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif ?>
  <form method="post" class="form"><?= csrf_field() ?>
    <label>Email<input type="email" name="email" autocomplete="email" required value="<?= e(post('email')) ?>"></label>
    <label>Password<input type="password" name="password" autocomplete="current-password" required></label>
    <a class="link small" href="<?= e(url('account/forgot')) ?>">Forgot password?</a>
    <button class="btn block">Log in</button>
  </form>
  <p class="center muted">New here? <a href="<?= e(url('account/register')) ?>">Create an account</a></p>
</section>
