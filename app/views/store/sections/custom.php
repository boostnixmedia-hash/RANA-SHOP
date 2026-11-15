<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap <?= ($s['width'] ?? 'narrow') === 'narrow' ? 'narrow' : '' ?>">
  <?php if (!empty($s['heading'])): ?><h2 class="center"><?= e($s['heading']) ?></h2><?php endif ?>
  <div class="rte"><?= clean_html((string)($s['html'] ?? '')) ?></div>
</div></section>
