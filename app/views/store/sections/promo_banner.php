<section <?= sec_attr($s, 'promo') ?> id="<?= $sid ?>">
  <?php if (!empty($s['image'])): ?><img class="promo-bg" src="<?= e(media($s['image'])) ?>" alt="" loading="lazy"><?php endif ?>
  <div class="wrap promo-in reveal">
    <div>
      <?php if (!empty($s['heading'])): ?><h2><?= e($s['heading']) ?></h2><?php endif ?>
      <?php if (!empty($s['subtitle'])): ?><p><?= e($s['subtitle']) ?></p><?php endif ?>
    </div>
    <div class="promo-act">
      <?php if (!empty($s['code'])): ?><button class="code" data-copy="<?= e($s['code']) ?>" title="Copy code"><?= icon('tag', 18) ?> <?= e($s['code']) ?></button><?php endif ?>
      <?php if (!empty($s['button_text'])): ?><a class="btn light" href="<?= e(url($s['button_link'] ?? '/shop')) ?>"><?= e($s['button_text']) ?></a><?php endif ?>
    </div>
  </div>
</section>
