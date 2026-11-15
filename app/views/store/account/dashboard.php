<section class="wrap acct">
  <h1>Hello, <?= e(explode(' ', $c['name'])[0]) ?></h1>
  <div class="acc-grid"><?php include __DIR__ . '/_nav.php'; ?>
    <div>
      <div class="acc-cards">
        <div class="acc-card"><b><?= (int)val('SELECT COUNT(*) FROM orders WHERE customer_id=?', [$c['id']]) ?></b><span>Orders</span></div>
        <div class="acc-card"><b><?= count(wishlist_ids()) ?></b><span>Wishlist</span></div>
        <div class="acc-card"><b><?= (int)val('SELECT COUNT(*) FROM addresses WHERE customer_id=?', [$c['id']]) ?></b><span>Addresses</span></div>
      </div>
      <h2>Recent orders</h2>
      <?php include __DIR__ . '/_orders_table.php'; ?>
    </div>
  </div>
</section>
