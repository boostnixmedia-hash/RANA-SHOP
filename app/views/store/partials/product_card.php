<?php
/** @var array $p */
$wish = in_array((int)$p['id'], $GLOBALS['__wl'] ??= wishlist_ids(), true);
$sale = product_on_sale($p);
$isNew = strtotime($p['created_at']) > time() - 86400 * 21;
$out = $p['stock_total'] <= 0;
$t = theme();
?>
<article class="card<?= $out ? ' is-out' : '' ?>">
  <div class="card-media">
    <a href="<?= e(url('product/' . $p['slug'])) ?>" class="card-img" tabindex="-1" aria-hidden="true">
      <?php if ($p['image']): ?><img src="<?= e(media($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy" decoding="async"><?php endif ?>
      <?php if ($p['image2'] && $t['card_hover'] === 'swap'): ?><img class="alt" src="<?= e(media($p['image2'])) ?>" alt="" loading="lazy" decoding="async"><?php endif ?>
    </a>
    <div class="badges">
      <?php if ($out): ?><span class="badge b-out">Sold out</span><?php elseif ($sale): ?><span class="badge b-sale">-<?= (int)round(100 - $p['sale_price'] / $p['price'] * 100) ?>%</span><?php elseif ($isNew): ?><span class="badge">New</span><?php endif ?>
    </div>
    <button class="wish<?= $wish ? ' on' : '' ?>" data-wish="<?= (int)$p['id'] ?>" aria-label="Add to wishlist" aria-pressed="<?= $wish ? 'true' : 'false' ?>"><?= icon('heart', 20) ?></button>
    <?php if ($t['card_quick_add'] && !$out): ?><button class="quick-add" data-quick="<?= (int)$p['id'] ?>">Quick add</button><?php endif ?>
  </div>
  <div class="card-body">
    <h3 class="card-title"><a href="<?= e(url('product/' . $p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
    <div class="price"><span class="now<?= $sale ? ' sale' : '' ?>"><?= money($p['final_price']) ?></span><?php if ($sale): ?><s><?= money($p['price']) ?></s><?php endif ?></div>
    <?php if (count($p['colors']) > 1): ?><div class="swatches"><?php foreach (array_slice($p['colors'], 0, 5, true) as $name => $hex): ?><i style="background:<?= e(safe_color((string)$hex, '#ccc')) ?>" title="<?= e($name) ?>"></i><?php endforeach ?></div><?php endif ?>
  </div>
</article>
