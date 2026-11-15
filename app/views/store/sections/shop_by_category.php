<?php $ids = array_filter(array_map('intval', (array)($s['category_ids'] ?? [])));
$cats = array_values(array_filter(categories_all(), fn($c) => $ids ? in_array((int)$c['id'], $ids, true) : !$c['parent_id']));
if (!$cats) return; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap"><?= sec_head($s) ?>
  <div class="cat-grid st-<?= e($s['style'] ?? 'tiles') ?> n<?= min(4, count($cats)) ?>">
  <?php foreach ($cats as $c): ?>
    <a class="cat-tile reveal" href="<?= e(url('category/' . $c['slug'])) ?>">
      <span class="cat-img"><?php if ($c['image']): ?><img src="<?= e(media($c['image'])) ?>" alt="" loading="lazy"><?php endif ?></span>
      <span class="cat-name"><?= e($c['name']) ?> <?= icon('arrow-right', 16) ?></span>
    </a>
  <?php endforeach ?>
  </div>
</div></section>
