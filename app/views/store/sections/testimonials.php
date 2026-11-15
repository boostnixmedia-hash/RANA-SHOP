<?php $items = array_values(array_filter($s['items'] ?? [], fn($x) => !empty($x['text']))); if (!$items) return; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap"><?= sec_head($s) ?>
  <div class="testi rail-m">
  <?php foreach ($items as $it): $r = max(1, min(5, (int)($it['rating'] ?? 5))); ?>
    <figure class="t-card reveal">
      <div class="stars" aria-label="<?= $r ?> out of 5"><?= str_repeat(icon('star-fill', 16), $r) ?></div>
      <blockquote>“<?= e($it['text']) ?>”</blockquote>
      <figcaption><?php if (!empty($it['avatar'])): ?><img src="<?= e(media($it['avatar'])) ?>" alt="" loading="lazy"><?php endif ?><b><?= e($it['name'] ?? '') ?></b><?php if (!empty($it['location'])): ?><span><?= e($it['location']) ?></span><?php endif ?></figcaption>
    </figure>
  <?php endforeach ?>
  </div>
</div></section>
