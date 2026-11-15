<section class="page-head"><div class="wrap"><nav class="crumbs"><a href="<?= e(url('/')) ?>">Home</a><span>/</span>Collections</nav><h1>Collections</h1></div></section>
<section class="wrap" style="padding-bottom:60px">
  <?php if (!$collections): ?><p class="muted">No collections yet.</p><?php endif ?>
  <div class="col-grid n3">
  <?php foreach ($collections as $c): ?>
    <a class="col-tile" href="<?= e(url('collection/' . $c['slug'])) ?>">
      <?php if ($c['cover']): ?><img src="<?= e(media($c['cover'])) ?>" alt="" loading="lazy"><?php endif ?>
      <div class="col-txt"><?php if ($c['kind'] !== 'regular'): ?><span class="eyebrow"><?= $c['kind'] === 'limited' ? 'Limited Edition' : 'Seasonal' ?></span><?php endif ?><h3><?= e($c['name']) ?></h3><?php if ($c['description']): ?><p><?= e(mb_substr($c['description'], 0, 90)) ?></p><?php endif ?><span class="link-arrow">Shop now <?= icon('arrow-right', 16) ?></span></div>
    </a>
  <?php endforeach ?>
  </div>
</section>
