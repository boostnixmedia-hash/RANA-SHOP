<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin login</title><meta name="robots" content="noindex"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login-body"><form method="post" class="login-box"><?= csrf_field() ?>
  <div class="brand"><?= e(mb_strtoupper(setting('store_name'))) ?><small>Admin</small></div>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif ?>
  <label>Email<input type="email" name="email" required autofocus autocomplete="username" value="<?= e(post('email')) ?>"></label>
  <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn block">Log in</button>
</form></body></html>
