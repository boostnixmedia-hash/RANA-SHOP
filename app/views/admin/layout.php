<?php $me = admin(); $unseen = (int)val("SELECT COUNT(*) FROM orders WHERE seen=0");
$nav = [['dashboard', 'admin', 'Dashboard', 'home'], ['orders', 'admin/orders', 'Orders', 'package'], ['products', 'admin/products', 'Products', 'tag']];
$path = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', strlen(base_path())), '/'); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Admin') ?> · <?= e(setting('store_name')) ?> Admin</title><meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= e(csrf_token()) ?>"><meta name="base" content="<?= e(base_path()) ?>">
<link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body>
<aside class="side" id="side">
  <a class="brand" href="<?= e(url('admin')) ?>"><?= e(mb_strtoupper(setting('store_name'))) ?><small>Admin</small></a>
  <nav>
    <?php foreach ($nav as [$perm, $u, $l, $i]) if (can($perm)): $on = $path === '/' . $u || ($u !== 'admin' && str_starts_with($path, '/' . $u)); ?>
      <a href="<?= e(url($u)) ?>" class="<?= $on ? 'on' : '' ?>"><?= icon($i, 19) ?> <?= $l ?><?php if ($perm === 'orders' && $unseen): ?><b class="pill"><?= $unseen ?></b><?php endif ?></a>
    <?php endif ?>
  </nav>
  <div class="side-foot">
    <a href="<?= e(url('/')) ?>" target="_blank"><?= icon('eye', 18) ?> View website</a>
    <form method="post" action="<?= e(url('admin/logout')) ?>"><?= csrf_field() ?><button><?= icon('logout', 18) ?> Log out</button></form>
    <small><?= e($me['name']) ?> · <?= e(role_label($me['role'])) ?></small>
  </div>
</aside>
<div class="main">
  <header class="top"><button class="menu-btn" onclick="document.body.classList.toggle('nav-open')" aria-label="Menu"><?= icon('menu', 22) ?></button><h1><?= e($title ?? '') ?></h1><div class="top-act"><?= $actions ?? '' ?></div></header>
  <?php foreach (flashes() as [$t, $m]): ?><div class="flash <?= e($t) ?>"><?= e($m) ?></div><?php endforeach ?>
  <div class="content"><?= $content ?></div>
</div>
<div class="nav-shade" onclick="document.body.classList.remove('nav-open')"></div>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body></html>
