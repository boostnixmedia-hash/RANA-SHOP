<?php $qs = array_filter(['q' => $q ?? null, 'sort' => get('sort'), 'size' => get('size'), 'color' => get('color'), 'min' => get('min'), 'max' => get('max'), 'cat' => get('cat')], fn($v) => $v !== null && $v !== '');
$pg = $res['pager']; $active = array_intersect_key($qs, array_flip(['size', 'color', 'min', 'max', 'cat'])); ?>
<?php if (!empty($banner)): ?>
<section class="page-banner"><img src="<?= e(media($banner)) ?>" alt=""><div class="wrap"><h1><?= e($title) ?></h1><?php if ($intro): ?><p><?= e($intro) ?></p><?php endif ?></div></section>
<?php else: ?>
<section class="page-head"><div class="wrap"><nav class="crumbs"><a href="<?= e(url('/')) ?>">Home</a><span>/</span><?= e($title) ?></nav><h1><?= e($title) ?></h1><?php if ($intro): ?><p class="muted"><?= e($intro) ?></p><?php endif ?></div></section>
<?php endif ?>
<section class="wrap listing">
  <?php if (isset($q)): ?>
  <form class="search-big" action="<?= e(url('search')) ?>"><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products" aria-label="Search"><button class="btn"><?= icon('search', 18) ?> Search</button></form>
  <?php endif ?>
  <div class="toolbar">
    <button class="btn ghost sm" data-open="filters"><?= icon('filter', 16) ?> Filter<?= $active ? ' (' . count($active) . ')' : '' ?></button>
    <span class="muted count"><?= $pg['total'] ?> product<?= $pg['total'] === 1 ? '' : 's' ?></span>
    <form class="sort" method="get"><?php foreach ($qs as $k => $v) if ($k !== 'sort'): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif ?>
      <label>Sort <select name="sort" onchange="this.form.submit()">
        <?php foreach ((!empty($collection) ? ['manual' => 'Featured'] : []) + ['new' => 'Newest', 'best' => 'Best selling', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name A–Z'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= ($filters['sort'] ?? 'new') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?>
      </select></label>
    </form>
  </div>
  <?php if ($active): ?><div class="chips"><?php foreach ($active as $k => $v): $rest = $qs; unset($rest[$k], $rest['page']); ?><a class="chip" href="?<?= e(http_build_query($rest)) ?>"><?= e(ucfirst($k) . ': ' . $v) ?> <?= icon('x', 14) ?></a><?php endforeach ?><a class="chip clear" href="?<?= e(http_build_query(array_diff_key($qs, $active))) ?>">Clear all</a></div><?php endif ?>

  <?php if ($res['items']): ?>
    <div class="grid cols-4"><?php foreach ($res['items'] as $p) echo view('store/partials/product_card', ['p' => $p]); ?></div>
    <?= page_links($pg, $qs) ?>
  <?php else: ?>
    <div class="empty"><p>No products match<?= isset($q) && $q !== '' ? ' “' . e($q) . '”' : '' ?>.</p><a class="btn" href="<?= e(url('shop')) ?>">Browse all products</a></div>
  <?php endif ?>
</section>

<aside class="drawer left" id="filters" hidden aria-label="Filters">
  <div class="drawer-hd"><b>Filter</b><button class="icon-btn" data-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <form class="filter-form" method="get">
    <?php if (isset($q)): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif ?>
    <input type="hidden" name="sort" value="<?= e($filters['sort'] ?? 'new') ?>">
    <?php if (empty($category) && empty($q)): ?>
    <fieldset><legend>Category</legend><div class="opt-list">
      <label><input type="radio" name="cat" value="" <?= get('cat') === '' ? 'checked' : '' ?>> All</label>
      <?php foreach (categories_all() as $c) if (!$c['parent_id']): ?><label><input type="radio" name="cat" value="<?= e($c['slug']) ?>" <?= get('cat') === $c['slug'] ? 'checked' : '' ?>> <?= e($c['name']) ?></label><?php endif ?>
    </div></fieldset>
    <?php endif ?>
    <?php if ($opts['sizes']): ?><fieldset><legend>Size</legend><div class="size-pills">
      <label><input type="radio" name="size" value="" <?= get('size') === '' ? 'checked' : '' ?>><span>Any</span></label>
      <?php foreach ($opts['sizes'] as $sz): ?><label><input type="radio" name="size" value="<?= e($sz) ?>" <?= get('size') === $sz ? 'checked' : '' ?>><span><?= e($sz) ?></span></label><?php endforeach ?>
    </div></fieldset><?php endif ?>
    <?php if ($opts['colors']): ?><fieldset><legend>Colour</legend><div class="color-pills">
      <label><input type="radio" name="color" value="" <?= get('color') === '' ? 'checked' : '' ?>><span>Any</span></label>
      <?php foreach ($opts['colors'] as $c): ?><label><input type="radio" name="color" value="<?= e($c['color']) ?>" <?= get('color') === $c['color'] ? 'checked' : '' ?>><span><i style="background:<?= e(safe_color((string)$c['hex'], '#ccc')) ?>"></i><?= e($c['color']) ?></span></label><?php endforeach ?>
    </div></fieldset><?php endif ?>
    <fieldset><legend>Price (৳)</legend><div class="price-range"><input type="number" name="min" min="0" placeholder="Min" value="<?= e(get('min')) ?>" inputmode="numeric"><span>–</span><input type="number" name="max" min="0" placeholder="Max" value="<?= e(get('max')) ?>" inputmode="numeric"></div></fieldset>
    <div class="filter-act"><a class="btn ghost" href="?<?= e(http_build_query(array_intersect_key($qs, ['q' => 1, 'sort' => 1]))) ?>">Clear</a><button class="btn">Show results</button></div>
  </form>
</aside>
