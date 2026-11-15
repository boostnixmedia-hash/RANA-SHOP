<?php $ids = array_filter(array_map('intval', (array)($s['collection_ids'] ?? [])));
$cols = $ids ? rows('SELECT * FROM collections WHERE visible=1 AND id IN (' . implode(',', $ids) . ') ORDER BY FIELD(id,' . implode(',', $ids) . ')') : rows('SELECT * FROM collections WHERE visible=1 AND featured=1 ORDER BY sort, name');
if (!$cols) return; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap"><?= sec_head($s) ?>
  <div class="col-grid n<?= min(3, count($cols)) ?>">
  <?php foreach ($cols as $c): ?>
    <a class="col-tile reveal" href="<?= e(url('collection/' . $c['slug'])) ?>">
      <?php if ($c['cover']): ?><img src="<?= e(media($c['cover'])) ?>" alt="" loading="lazy"><?php endif ?>
      <div class="col-txt"><?php if ($c['kind'] !== 'regular'): ?><span class="eyebrow"><?= $c['kind'] === 'limited' ? 'Limited Edition' : 'Seasonal' ?></span><?php endif ?><h3><?= e($c['name']) ?></h3><span class="link-arrow">Shop now <?= icon('arrow-right', 16) ?></span></div>
    </a>
  <?php endforeach ?>
  </div>
</div></section>
