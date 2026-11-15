<?php $err = fn($k) => isset($errors[$k]) ? '<span class="ferr">' . e($errors[$k]) . '</span>' : ''; ?>
<section class="wrap acct"><h1>Profile</h1><div class="acc-grid"><?php include __DIR__ . '/_nav.php'; ?>
  <form method="post" class="form card-form"><?= csrf_field() ?>
    <label>Name<input name="name" required value="<?= e($c['name']) ?>"><?= $err('name') ?></label>
    <label>Email<input value="<?= e($c['email']) ?>" disabled></label>
    <label>Mobile<input name="phone" type="tel" value="<?= e($c['phone']) ?>"><?= $err('phone') ?></label>
    <h3>Change password</h3>
    <label>Current password<input type="password" name="current_password" autocomplete="current-password"><?= $err('current_password') ?></label>
    <label>New password<input type="password" name="new_password" autocomplete="new-password"><?= $err('new_password') ?></label>
    <button class="btn">Save changes</button>
  </form>
</div></section>
