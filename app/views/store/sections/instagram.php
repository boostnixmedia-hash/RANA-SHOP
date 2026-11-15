<?php $imgs = array_values(array_filter($s['images'] ?? [], fn($x) => !empty($x['image']))); if (!$imgs) return; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap">
  <div class="sec-head"><div><?php if (!empty($s['heading'])): ?><h2><?= e($s['heading']) ?></h2><?php endif ?><?php if (!empty($s['handle'])): ?><p><?= icon('brand-instagram', 16) ?> <?= e($s['handle']) ?></p><?php endif ?></div>
  <?php if (!empty($s['link'])): ?><a class="link-arrow" href="<?= e($s['link']) ?>" target="_blank" rel="noopener">Follow us <?= icon('arrow-right', 18) ?></a><?php endif ?></div>
  <div class="insta">
  <?php foreach (array_slice($imgs, 0, 12) as $im): ?><a href="<?= e(!empty($im['link']) ? $im['link'] : ($s['link'] ?? '#')) ?>" target="_blank" rel="noopener"><img src="<?= e(media($im['image'])) ?>" alt="" loading="lazy"></a><?php endforeach ?>
  </div>
</div></section>
