<div class="quick"><a class="btn" href="<?= e(url('admin/products/new')) ?>"><?= icon('plus', 18) ?> Add new product</a></div>
<form class="filters">
  <input type="search" name="q" value="<?= e(get('q')) ?>" placeholder="Search name or SKU">
  <select name="cat"><option value="">All categories</option><?php foreach (categories_all(false) as $c): ?><option value="<?= $c['id'] ?>" <?= (int)get('cat') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?></select>
  <select name="status"><option value="">Any status</option><?php foreach (['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $k => $l): ?><option value="<?= $k ?>" <?= get('status') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select>
  <button class="btn ghost sm">Filter</button>
</form>
<form method="post" action="<?= e(url('admin/products/bulk')) ?>" class="card"><?= csrf_field() ?>
  <div class="bulk"><label><input type="checkbox" data-checkall> Select all</label>
    <select name="action"><option value="">Bulk action…</option><option value="active">Publish (Active)</option><option value="draft">Unpublish (Draft)</option><option value="archived">Archive</option><option value="feature">Mark featured</option><option value="unfeature">Remove featured</option><option value="delete">Delete</option></select>
    <button class="btn ghost sm" onclick="return this.form.action.value!=='delete'||confirm('Delete the selected products? This cannot be undone.')">Apply</button></div>
  <div class="plist">
  <?php foreach ($list as $p): $sale = product_on_sale($p); ?>
    <div class="prow">
      <input type="checkbox" name="ids[]" value="<?= $p['id'] ?>">
      <a href="<?= e(url('admin/products/' . $p['id'] . '/edit')) ?>" class="thumb"><?php if ($p['image']): ?><img src="<?= e(media($p['image'])) ?>" alt=""><?php endif ?></a>
      <a href="<?= e(url('admin/products/' . $p['id'] . '/edit')) ?>" class="grow"><b><?= e($p['name']) ?></b><?= $p['featured'] ? ' <span class="pill star">★</span>' : '' ?><br><small class="muted"><?= e($p['cat_name'] ?? 'No category') ?><?= $p['sku'] ? ' · ' . e($p['sku']) : '' ?></small></a>
      <span class="pr"><?= money($sale ? $p['sale_price'] : $p['price']) ?><?php if ($sale): ?><br><s class="muted"><?= money($p['price']) ?></s><?php endif ?></span>
      <span class="stk <?= $p['stock'] <= 0 ? 'out' : ($p['stock'] <= 10 ? 'low' : '') ?>"><?= (int)$p['stock'] ?> in stock</span>
      <span class="st st-<?= e($p['status']) ?>"><?= ucfirst($p['status']) ?></span>
    </div>
  <?php endforeach ?>
  <?php if (!$list): ?><p class="muted">No products found.</p><?php endif ?>
  </div>
  <?= page_links($pg, array_filter(['q' => get('q'), 'cat' => get('cat'), 'status' => get('status')])) ?>
</form>
