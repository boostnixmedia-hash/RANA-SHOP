<section class="wrap narrow auth">
  <h1>Forgot password</h1>
  <?php if ($sent): ?><div class="alert ok">If an account exists for that email, we've sent a reset link. Please check your inbox (and spam folder).</div>
  <?php else: ?><p class="muted">Enter your email and we'll send you a link to reset your password.</p>
  <form method="post" class="form"><?= csrf_field() ?><label>Email<input type="email" name="email" required></label><button class="btn block">Send reset link</button></form><?php endif ?>
  <p class="center"><a href="<?= e(url('account/login')) ?>">Back to login</a></p>
</section>
