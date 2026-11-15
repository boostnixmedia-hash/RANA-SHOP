<section class="wrap narrow auth">
  <h1>Set a new password</h1>
  <?php if (!$valid): ?><div class="alert">This reset link is invalid or has expired.</div><p class="center"><a class="btn" href="<?= e(url('account/forgot')) ?>">Request a new link</a></p>
  <?php else: ?>
  <?php if ($error): ?><div class="alert"><?= e($error) ?></div><?php endif ?>
  <form method="post" class="form"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <label>New password (8+ characters)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label>Confirm password<input type="password" name="password2" required autocomplete="new-password"></label>
    <button class="btn block">Save password</button>
  </form><?php endif ?>
</section>
