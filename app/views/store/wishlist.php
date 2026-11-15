<section class="page-head"><div class="wrap"><h1>Wishlist</h1><p class="muted"><?= count($items) ?> saved item<?= count($items) === 1 ? '' : 's' ?><?= customer() ? '' : ' · <a href="' . e(url('account/login')) . '">Log in</a> to keep them on every device' ?></p></div></section>
<section class="wrap" style="padding-bottom:60px">
<?php if ($items): ?><div class="grid cols-4"><?php foreach ($items as $p) echo view('store/partials/product_card', ['p' => $p]); ?></div>
<?php else: ?><div class="empty"><?= icon('heart', 40) ?><p>Nothing saved yet. Tap the heart on any product to save it here.</p><a class="btn" href="<?= e(url('shop')) ?>">Browse products</a></div><?php endif ?>
</section>
