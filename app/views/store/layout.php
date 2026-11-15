<?php
$t = theme();
$ann = setting('announcement', []);
$menu = rows("SELECT * FROM menus WHERE location='header' ORDER BY sort, id");
$cust = customer();
$wl = count(wishlist_ids());
$store = setting('store_name');
$logo = setting('logo');
$social = (array)setting('social', []);
$isPreview = !empty($preview) || (!empty($_GET['preview']) && admin());
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= seo_head($seo ?? []) ?>
<link rel="icon" href="<?= e(($fav = setting('favicon')) ? media($fav) : asset('img/favicon.png')) ?>">
<meta name="theme-color" content="<?= e($t['background']) ?>">
<?= theme_fonts_link() ?>
<link rel="stylesheet" href="<?= asset('css/store.css') ?>">
<style><?= theme_css() ?></style>
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
<?= $isPreview ? '' : tracking_head() ?>
</head>
<body class="hdr-<?= e($t['header_style']) ?> <?= $t['header_sticky'] ? 'hdr-sticky' : '' ?> card-<?= e($t['card_hover']) ?> align-<?= e($t['card_align']) ?>">
<?php if ($isPreview): ?><div class="preview-bar">Preview mode: this is the unpublished draft. <a href="<?= e(url('admin/homepage')) ?>">Back to editor</a></div><?php endif ?>
<a class="skip" href="#main">Skip to content</a>
<?php if (!empty($ann['enabled']) && !empty($ann['text'])): ?>
<div class="announce" style="background:<?= e(safe_color($ann['bg'] ?? '', '#111')) ?>;color:<?= e(safe_color($ann['color'] ?? '', '#fff')) ?>">
  <?php if (!empty($ann['link'])): ?><a href="<?= e(url($ann['link'])) ?>"><?= e($ann['text']) ?></a><?php else: ?><?= e($ann['text']) ?><?php endif ?>
</div>
<?php endif ?>
<header class="site-header" id="hdr">
  <div class="wrap hdr-in">
    <button class="icon-btn only-m" data-open="mnav" aria-label="Menu"><?= icon('menu', 24) ?></button>
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="<?= e($store) ?> home">
      <?php if ($logo): ?><img src="<?= e(media($logo)) ?>" alt="<?= e($store) ?>"><?php else: ?><i class="mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($store, 0, 1))) ?></i><span><?= e(mb_strtoupper($store)) ?></span><?php endif ?>
    </a>
    <nav class="main-nav" aria-label="Main">
      <?php foreach ($menu as $m): ?><a href="<?= e(url($m['url'])) ?>"><?= e($m['label']) ?></a><?php endforeach ?>
    </nav>
    <div class="hdr-icons">
      <button class="icon-btn" data-open="search" aria-label="Search"><?= icon('search', 22) ?></button>
      <a class="icon-btn only-d" href="<?= e(url($cust ? 'account' : 'account/login')) ?>" aria-label="Account"><?= icon('user', 22) ?></a>
      <a class="icon-btn" href="<?= e(url('wishlist')) ?>" aria-label="Wishlist"><?= icon('heart', 22) ?><span class="dot" id="wlCount" <?= $wl ? '' : 'hidden' ?>><?= $wl ?></span></a>
      <a class="icon-btn" href="<?= e(url('cart')) ?>" data-open="cart" aria-label="Cart"><?= icon('bag', 22) ?><span class="dot" id="cartCount" <?= cart_count() ? '' : 'hidden' ?>><?= cart_count() ?></span></a>
    </div>
  </div>
</header>

<!-- search overlay -->
<div class="search-pop" id="search" hidden>
  <div class="wrap">
    <form action="<?= e(url('search')) ?>" class="search-form" role="search">
      <?= icon('search', 22) ?><input type="search" name="q" id="searchInput" placeholder="Search shirts, jeans, sneakers…" autocomplete="off" aria-label="Search products">
      <button type="button" class="icon-btn" data-close aria-label="Close"><?= icon('x', 22) ?></button>
    </form>
    <div class="suggest" id="suggest"></div>
  </div>
</div>

<!-- mobile nav -->
<aside class="drawer left" id="mnav" hidden aria-label="Menu">
  <div class="drawer-hd"><b><?= e($store) ?></b><button class="icon-btn" data-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <nav class="mnav">
    <?php foreach ($menu as $m): ?><a href="<?= e(url($m['url'])) ?>"><?= e($m['label']) ?><?= icon('chevron-right', 18) ?></a><?php endforeach ?>
    <a href="<?= e(url('shop')) ?>">Shop All<?= icon('chevron-right', 18) ?></a>
  </nav>
  <div class="mnav-foot">
    <a href="<?= e(url($cust ? 'account' : 'account/login')) ?>"><?= icon('user', 20) ?> <?= $cust ? 'My Account' : 'Login / Register' ?></a>
    <a href="<?= e(url('wishlist')) ?>"><?= icon('heart', 20) ?> Wishlist</a>
    <a href="<?= e(url('track')) ?>"><?= icon('truck', 20) ?> Track Order</a>
    <?php if ($p = setting('contact_phone')): ?><a href="tel:<?= e($p) ?>"><?= icon('phone', 20) ?> <?= e($p) ?></a><?php endif ?>
  </div>
</aside>

<!-- cart drawer -->
<aside class="drawer right" id="cart" hidden aria-label="Cart">
  <div class="drawer-hd"><b>Your Bag</b><button class="icon-btn" data-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <div id="cartBody" class="cart-body"><div class="loading">Loading…</div></div>
</aside>
<div class="shade" id="shade" hidden></div>

<main id="main">
<?php foreach (flashes() as [$type, $msg]): ?><div class="flash flash-<?= e($type) ?>" role="status"><div class="wrap"><?= e($msg) ?></div></div><?php endforeach ?>
<?= $content ?>
</main>

<?php $ft = (array)setting('footer_titles', []); $fstyle = $t['footer_style'] === 'light' ? 'light' : 'dark'; ?>
<footer class="site-footer f-<?= $fstyle ?>">
  <div class="wrap foot-grid">
    <div class="foot-brand">
      <a class="logo" href="<?= e(url('/')) ?>"><?php $fl = setting('logo_light') ?: ($fstyle === 'light' ? $logo : ''); ?><?php if ($fl): ?><img src="<?= e(media($fl)) ?>" alt="<?= e($store) ?>"><?php else: ?><i class="mark" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($store, 0, 1))) ?></i><span><?= e(mb_strtoupper($store)) ?></span><?php endif ?></a>
      <p><?= e(setting('footer_about')) ?></p>
      <div class="social">
        <?php foreach (['facebook', 'instagram', 'tiktok', 'youtube'] as $s) if (!empty($social[$s])): ?><a href="<?= e($social[$s]) ?>" target="_blank" rel="noopener" aria-label="<?= ucfirst($s) ?>"><?= icon('brand-' . $s, 20) ?></a><?php endif ?>
        <?php if ($w = setting('whatsapp')): ?><a href="https://wa.me/<?= e(preg_replace('/\D/', '', $w)) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('brand-whatsapp', 20) ?></a><?php endif ?>
      </div>
    </div>
    <?php foreach (['footer1', 'footer2', 'footer3'] as $loc): $links = rows('SELECT * FROM menus WHERE location=? ORDER BY sort, id', [$loc]); if (!$links) continue; ?>
    <div><h4><?= e($ft[$loc] ?? '') ?></h4><ul><?php foreach ($links as $m): ?><li><a href="<?= e(url($m['url'])) ?>"><?= e($m['label']) ?></a></li><?php endforeach ?></ul></div>
    <?php endforeach ?>
    <div>
      <h4>Contact</h4>
      <ul class="contact-list">
        <?php if ($v = setting('contact_phone')): ?><li><?= icon('phone', 16) ?><a href="tel:<?= e($v) ?>"><?= e($v) ?></a></li><?php endif ?>
        <?php if ($v = setting('contact_email')): ?><li><?= icon('mail', 16) ?><a href="mailto:<?= e($v) ?>"><?= e($v) ?></a></li><?php endif ?>
        <?php if ($v = setting('address')): ?><li><?= icon('map-pin', 16) ?><span><?= e($v) ?></span></li><?php endif ?>
      </ul>
    </div>
  </div>
  <div class="wrap foot-bottom">
    <span><?= e(str_replace('{year}', date('Y'), (string)setting('footer_copyright'))) ?></span>
    <span class="pay-icons"><?php foreach (payment_methods_enabled() as $k => $l): ?><em><?= e($k === 'cod' ? 'COD' : ($k === 'sslcommerz' ? 'VISA · Mastercard' : $l)) ?></em><?php endforeach ?></span>
  </div>
</footer>

<nav class="tabbar only-m" aria-label="Quick links">
  <a href="<?= e(url('/')) ?>"><?= icon('home', 22) ?><span>Home</span></a>
  <a href="<?= e(url('shop')) ?>"><?= icon('grid', 22) ?><span>Shop</span></a>
  <a href="<?= e(url('wishlist')) ?>"><?= icon('heart', 22) ?><span>Wishlist</span></a>
  <a href="<?= e(url($cust ? 'account' : 'account/login')) ?>"><?= icon('user', 22) ?><span>Account</span></a>
</nav>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<div class="modal" id="quick" hidden></div>
<script src="<?= asset('js/store.js') ?>" defer></script>
</body>
</html>
