<nav class="acc-nav">
  <?php foreach (['overview' => ['account', 'Overview', 'user'], 'orders' => ['account/orders', 'My Orders', 'package'], 'addresses' => ['account/addresses', 'Addresses', 'map-pin'], 'profile' => ['account/profile', 'Profile & Password', 'shield']] as $k => [$u, $l, $i]): ?>
    <a href="<?= e(url($u)) ?>" class="<?= ($tab ?? '') === $k ? 'on' : '' ?>"><?= icon($i, 18) ?> <?= $l ?></a>
  <?php endforeach ?>
  <a href="<?= e(url('wishlist')) ?>"><?= icon('heart', 18) ?> Wishlist</a>
  <form method="post" action="<?= e(url('account/logout')) ?>"><?= csrf_field() ?><button><?= icon('logout', 18) ?> Log out</button></form>
</nav>
