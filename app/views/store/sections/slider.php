<?php $slides = array_values(array_filter($s['slides'] ?? [], fn($x) => !empty($x['image']))); if (!$slides) return; ?>
<section class="slider h-<?= e($s['height'] ?? 'large') ?>" id="<?= $sid ?>" data-autoplay="<?= (int)($s['autoplay'] ?? 5) ?>" style="color:<?= e(safe_color($s['color'] ?? '', '#fff')) ?>">
  <div class="slides">
  <?php foreach ($slides as $i => $sl): ?>
    <div class="slide<?= $i ? '' : ' on' ?>">
      <img src="<?= e(media($sl['image'])) ?>" alt="" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>>
      <div class="wrap slide-txt">
        <?php if (!empty($sl['heading'])): ?><h2><?= e($sl['heading']) ?></h2><?php endif ?>
        <?php if (!empty($sl['subtitle'])): ?><p><?= e($sl['subtitle']) ?></p><?php endif ?>
        <?php if (!empty($sl['button_text'])): ?><a class="btn light" href="<?= e(url($sl['button_link'] ?? '/shop')) ?>"><?= e($sl['button_text']) ?></a><?php endif ?>
      </div>
    </div>
  <?php endforeach ?>
  </div>
  <?php if (count($slides) > 1): ?>
  <button class="sl-nav prev" aria-label="Previous"><?= icon('chevron-left', 26) ?></button><button class="sl-nav next" aria-label="Next"><?= icon('chevron-right', 26) ?></button>
  <div class="dots"><?php foreach ($slides as $i => $_): ?><button class="<?= $i ? '' : 'on' ?>" aria-label="Slide <?= $i + 1 ?>"></button><?php endforeach ?></div>
  <?php endif ?>
</section>
