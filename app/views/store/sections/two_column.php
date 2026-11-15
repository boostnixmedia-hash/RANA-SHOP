<section <?= sec_attr($s, 'twocol img-' . e($s['image_side'] ?? 'left')) ?> id="<?= $sid ?>">
  <div class="tc-img"><?php if (!empty($s['image'])): ?><img src="<?= e(media($s['image'])) ?>" alt="" loading="lazy"><?php endif ?></div>
  <div class="tc-txt reveal">
    <?php if (!empty($s['eyebrow'])): ?><span class="eyebrow"><?= e($s['eyebrow']) ?></span><?php endif ?>
    <?php if (!empty($s['heading'])): ?><h2><?= e($s['heading']) ?></h2><?php endif ?>
    <div class="rte"><?= clean_html((string)($s['text'] ?? '')) ?></div>
    <?php if (!empty($s['button_text'])): ?><a class="btn" href="<?= e(url($s['button_link'] ?? '/shop')) ?>"><?= e($s['button_text']) ?></a><?php endif ?>
  </div>
</section>
