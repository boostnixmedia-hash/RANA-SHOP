<div class="stats">
  <div class="stat"><span>Today's sales</span><b><?= money($stats['today']) ?></b></div>
  <div class="stat"><span>This month</span><b><?= money($stats['month']) ?></b></div>
  <a class="stat" href="<?= e(url('admin/orders?status=new')) ?>"><span>New orders</span><b><?= $stats['new'] ?></b></a>
  <div class="stat"><span>Total orders</span><b><?= $stats['orders'] ?></b></div>
  <a class="stat" href="<?= e(url('admin/products')) ?>"><span>Products</span><b><?= $stats['products'] ?></b></a>
  <div class="stat <?= $stats['low'] ? 'warn' : '' ?>"><span>Low stock products</span><b><?= $stats['low'] ?></b></div>
</div>
<div class="quick">
  <a class="btn" href="<?= e(url('admin/products/new')) ?>"><?= icon('plus', 18) ?> Add new product</a>
  <a class="btn ghost" href="<?= e(url('admin/orders')) ?>">See all orders</a>
</div>
<div class="card"><h2>Recent orders</h2><?php $list = $recent; include __DIR__ . '/_orders_table.php'; ?></div>
