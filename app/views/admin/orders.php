<div class="tabs">
  <a href="<?= e(url('admin/orders')) ?>" class="<?= get('status') === '' ? 'on' : '' ?>">All <small><?= array_sum($counts) ?></small></a>
  <?php foreach (ORDER_STATUSES as $s): ?><a href="?status=<?= $s ?>" class="<?= get('status') === $s ? 'on' : '' ?>"><?= status_label($s) ?> <small><?= $counts[$s] ?? 0 ?></small></a><?php endforeach ?>
</div>
<form class="filters"><input type="hidden" name="status" value="<?= e(get('status')) ?>"><input type="search" name="q" value="<?= e(get('q')) ?>" placeholder="Search order ID, name, phone, email"><button class="btn ghost sm">Search</button></form>
<div class="card"><?php include __DIR__ . '/_orders_table.php'; ?><?= page_links($pg, array_filter(['status' => get('status'), 'q' => get('q')])) ?></div>
