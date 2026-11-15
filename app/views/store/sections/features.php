<?php $items = $s['items'] ?? []; if (!$items) return; ?>
<section <?= sec_attr($s, 'feats') ?> id="<?= $sid ?>"><div class="wrap feat-grid n<?= min(4, count($items)) ?>">
  <?php foreach ($items as $it): ?><div class="feat"><?= icon($it['icon'] ?? 'star', 28) ?><div><b><?= e($it['title'] ?? '') ?></b><span><?= e($it['text'] ?? '') ?></span></div></div><?php endforeach ?>
</div></section>
