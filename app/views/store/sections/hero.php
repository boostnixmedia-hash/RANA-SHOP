<?php $ov = max(0, min(80, (int)($s['overlay'] ?? 0))); ?>
<section class="hero h-<?= e($s['height'] ?? 'large') ?> a-<?= e($s['align'] ?? 'left') ?>" id="<?= $sid ?>" style="color:<?= e(safe_color($s['color'] ?? '', '#111')) ?>">
  <picture class="hero-bg">
    <?php if (!empty($s['mobile_image'])): ?><source media="(max-width:700px)" srcset="<?= e(media($s['mobile_image'])) ?>"><?php endif ?>
    <?php if (!empty($s['image'])): ?><img src="<?= e(media($s['image'])) ?>" alt="" fetchpriority="high"><?php endif ?>
  </picture>
  <?php if ($ov): ?><div class="hero-ov" style="opacity:<?= $ov / 100 ?>"></div><?php endif ?>
  <div class="wrap hero-in">
    <div class="hero-txt reveal">
      <?php if (!empty($s['eyebrow'])): ?><span class="eyebrow"><?= e($s['eyebrow']) ?></span><?php endif ?>
      <?php if (!empty($s['heading'])): ?><h1><?= e($s['heading']) ?></h1><?php endif ?>
      <?php if (!empty($s['subtitle'])): ?><p><?= e($s['subtitle']) ?></p><?php endif ?>
      <div class="btns">
        <?php if (!empty($s['button_text'])): ?><a class="btn" href="<?= e(url($s['button_link'] ?? '/shop')) ?>"><?= e($s['button_text']) ?></a><?php endif ?>
        <?php if (!empty($s['button2_text'])): ?><a class="btn ghost" href="<?= e(url($s['button2_link'] ?? '/')) ?>"><?= e($s['button2_text']) ?></a><?php endif ?>
      </div>
    </div>
  </div>
</section>
