<?php
$sale = product_on_sale($p);
$vjson = array_map(fn($v) => ['id' => (int)$v['id'], 'color' => $v['color'], 'size' => $v['size'], 'stock' => (int)$v['stock'], 'price' => variant_price($v, $p), 'image' => $v['image_id'] ? (int)$v['image_id'] : null], $p['variants']);
$colors = []; $sizes = [];
foreach ($p['variants'] as $v) { if ($v['color'] !== '') $colors[$v['color']] = $v['color_hex']; if ($v['size'] !== '') $sizes[$v['size']] = true; }
$stockTotal = array_sum(array_column($p['variants'], 'stock'));
$wish = in_array((int)$p['id'], wishlist_ids(), true);
$rAvg = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;
$yt = ($p['video_url'] && preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([A-Za-z0-9_-]{11})~', $p['video_url'], $m)) ? $m[1] : '';
?>
<div class="wrap">
  <nav class="crumbs pd-crumbs"><a href="<?= e(url('/')) ?>">Home</a><span>/</span><?php if ($p['category']): ?><a href="<?= e(url('category/' . $p['category']['slug'])) ?>"><?= e($p['category']['name']) ?></a><span>/</span><?php endif ?><?= e($p['name']) ?></nav>
  <?php if ($p['status'] !== 'active'): ?><div class="alert">Admin preview: this product is <?= e($p['status']) ?> and not visible to customers.</div><?php endif ?>
</div>
<section class="wrap pd" data-product='<?= e(json_encode(['id' => (int)$p['id'], 'variants' => $vjson, 'base' => $p['final_price']])) ?>'>
  <div class="pd-gallery">
    <div class="pd-main" id="pdMain">
      <?php foreach ($p['images'] as $i => $im): ?><img src="<?= e(media($im['path'])) ?>" alt="<?= e($im['alt'] ?: $p['name']) ?>" data-img="<?= (int)$im['id'] ?>" <?= $i ? 'loading="lazy"' : 'fetchpriority="high"' ?>><?php endforeach ?>
      <?php if ($yt): ?><div class="pd-video"><iframe src="https://www.youtube-nocookie.com/embed/<?= e($yt) ?>" title="Product video" loading="lazy" allowfullscreen></iframe></div>
      <?php elseif ($p['video_url'] && !preg_match('~^https?://~', $p['video_url'])): ?><video src="<?= e(media($p['video_url'])) ?>" controls playsinline preload="metadata"></video><?php endif ?>
    </div>
    <?php if (count($p['images']) > 1): ?><div class="pd-thumbs"><?php foreach ($p['images'] as $i => $im): ?><button data-thumb="<?= $i ?>" class="<?= $i ? '' : 'on' ?>" aria-label="Image <?= $i + 1 ?>"><img src="<?= e(media($im['path'])) ?>" alt="" loading="lazy"></button><?php endforeach ?></div><?php endif ?>
  </div>
  <div class="pd-info">
    <h1><?= e($p['name']) ?></h1>
    <?php if ($reviews): ?><a href="#reviews" class="pd-rating"><span class="stars"><?= str_repeat(icon('star-fill', 15), (int)round($rAvg)) ?></span> <?= $rAvg ?> (<?= count($reviews) ?>)</a><?php endif ?>
    <div class="price lg"><span class="now<?= $sale ? ' sale' : '' ?>" id="pdPrice"><?= money($p['final_price']) ?></span><?php if ($sale): ?><s><?= money($p['price']) ?></s><span class="badge b-sale">Save <?= money($p['price'] - $p['final_price']) ?></span><?php endif ?></div>
    <?php if ($p['short_desc']): ?><p class="pd-short"><?= e($p['short_desc']) ?></p><?php endif ?>

    <form method="post" action="<?= e(url('cart/add')) ?>" class="pd-form" id="pdForm">
      <?= csrf_field() ?><input type="hidden" name="variant_id" id="variantId">
      <?php if ($colors): ?>
      <div class="opt"><div class="opt-l">Colour: <b id="colorName"></b></div>
        <div class="color-opts"><?php foreach ($colors as $cn => $hex): ?><button type="button" class="cbtn" data-color="<?= e($cn) ?>" title="<?= e($cn) ?>" aria-label="<?= e($cn) ?>"><i style="background:<?= e(safe_color((string)$hex, '#ccc')) ?>"></i></button><?php endforeach ?></div>
      </div><?php endif ?>
      <?php if ($sizes): ?>
      <div class="opt"><div class="opt-l">Size: <b id="sizeName"></b><button type="button" class="link" data-open="sizeguide"><?= icon('ruler', 16) ?> Size guide</button></div>
        <div class="size-opts"><?php foreach (array_keys($sizes) as $sz): ?><button type="button" class="sbtn" data-size="<?= e($sz) ?>"><?= e($sz) ?></button><?php endforeach ?></div>
      </div><?php endif ?>
      <div class="stock-msg" id="stockMsg" aria-live="polite"></div>
      <div class="pd-buy">
        <div class="qty"><button type="button" data-step="-1" aria-label="Decrease"><?= icon('minus', 16) ?></button><input name="qty" id="qty" value="1" inputmode="numeric" aria-label="Quantity"><button type="button" data-step="1" aria-label="Increase"><?= icon('plus', 16) ?></button></div>
        <button class="btn grow" id="addBtn" <?= $stockTotal ? '' : 'disabled' ?>><?= $stockTotal ? 'Add to bag' : 'Sold out' ?></button>
        <button type="button" class="icon-btn wish lg<?= $wish ? ' on' : '' ?>" data-wish="<?= (int)$p['id'] ?>" aria-label="Wishlist" aria-pressed="<?= $wish ? 'true' : 'false' ?>"><?= icon('heart', 22) ?></button>
      </div>
      <?php if ($stockTotal): ?><button class="btn ghost block" name="buy_now" value="1" id="buyBtn">Buy it now</button><?php endif ?>
    </form>
    <ul class="pd-perks">
      <li><?= icon('truck', 20) ?> Delivery inside Dhaka <?= money(setting('ship_inside')) ?>, outside <?= money(setting('ship_outside')) ?></li>
      <li><?= icon('cash', 20) ?> Cash on Delivery available</li>
      <li><?= icon('refresh', 20) ?> 7-day easy exchange</li>
    </ul>
    <details class="acc" open><summary>Description</summary><div class="rte"><?= clean_html((string)$p['description']) ?></div></details>
    <details class="acc"><summary>Delivery &amp; Returns</summary><div class="rte"><p>Inside Dhaka 1–2 days, outside Dhaka 2–4 days. Exchange within 7 days if unworn with tags. <a href="<?= e(url('return-policy')) ?>">Read the full policy</a>.</p></div></details>
    <?php if ($p['sku']): ?><p class="muted small">SKU: <span id="skuTxt"><?= e($p['sku']) ?></span></p><?php endif ?>
  </div>
</section>

<section class="wrap reviews" id="reviews">
  <div class="sec-head"><div><h2>Reviews</h2><p><?= $reviews ? $rAvg . ' out of 5 · ' . count($reviews) . ' review' . (count($reviews) > 1 ? 's' : '') : 'No reviews yet' ?></p></div></div>
  <div class="rv-list">
  <?php foreach ($reviews as $r): $imgs = json_decode((string)$r['images'], true) ?: []; ?>
    <article class="rv">
      <div class="stars"><?= str_repeat(icon('star-fill', 15), (int)$r['rating']) . str_repeat(icon('star', 15), 5 - (int)$r['rating']) ?></div>
      <p><?= nl2br(e($r['body'])) ?></p>
      <?php if ($imgs): ?><div class="rv-imgs"><?php foreach ($imgs as $im): ?><a href="<?= e(media($im)) ?>" target="_blank"><img src="<?= e(media($im)) ?>" alt="" loading="lazy"></a><?php endforeach ?></div><?php endif ?>
      <footer><b><?= e($r['name']) ?></b><?php if ($r['verified']): ?><span class="verified"><?= icon('check', 14) ?> Verified purchase</span><?php endif ?><span class="muted"><?= date('d M Y', strtotime($r['created_at'])) ?></span></footer>
    </article>
  <?php endforeach ?>
  </div>
  <?php if ($canReview): ?>
  <details class="rv-form"><summary class="btn ghost">Write a review</summary>
    <form method="post" action="<?= e(url('product/' . $p['slug'] . '/review')) ?>" enctype="multipart/form-data" class="form"><?= csrf_field() ?>
      <div class="star-input"><?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" id="r<?= $i ?>" value="<?= $i ?>" required><label for="r<?= $i ?>" title="<?= $i ?> stars"><?= icon('star-fill', 26) ?></label><?php endfor ?></div>
      <label>Your review<textarea name="body" rows="4" required minlength="5" maxlength="2000"></textarea></label>
      <label>Photos (optional, up to 3)<input type="file" name="images[]" accept="image/*" multiple></label>
      <button class="btn">Submit review</button>
    </form>
  </details>
  <?php elseif (!customer()): ?><p class="muted"><a href="<?= e(url('account/login')) ?>">Log in</a> to write a review.</p><?php endif ?>
</section>

<?php if ($related): ?>
<section class="sec"><div class="wrap"><div class="sec-head"><div><h2>You may also like</h2></div></div><div class="rail"><?php foreach ($related as $rp) echo view('store/partials/product_card', ['p' => $rp]); ?></div></div></section>
<?php endif ?>

<div class="modal" id="sizeguide" hidden><div class="modal-box">
  <div class="drawer-hd"><b>Size guide</b><button class="icon-btn" data-close aria-label="Close"><?= icon('x', 22) ?></button></div>
  <div class="rte modal-body"><?= clean_html((string)(val("SELECT body FROM pages WHERE slug='size-guide' AND visible=1") ?: '<table><thead><tr><th>Size</th><th>Chest (in)</th><th>Length (in)</th></tr></thead><tbody><tr><td>S</td><td>38</td><td>27</td></tr><tr><td>M</td><td>40</td><td>28</td></tr><tr><td>L</td><td>42</td><td>29</td></tr><tr><td>XL</td><td>44</td><td>30</td></tr><tr><td>XXL</td><td>46</td><td>31</td></tr></tbody></table><p>Pants: size = waist in inches. Shoes: EU sizes (40 = UK 6.5).</p>')) ?></div>
</div></div>
<div class="sticky-buy only-m" id="stickyBuy" hidden><div><b><?= e($p['name']) ?></b><span id="stickyPrice"><?= money($p['final_price']) ?></span></div><button class="btn" data-scroll="pdForm">Select size</button></div>
