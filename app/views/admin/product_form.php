<?php
$o = fn($k, $d = '') => e(old($k, $p[$k] ?? $d));
$colors = []; $sizes = []; $vmap = [];
foreach ($variants as $v) {
    if ($v['color'] !== '' && !isset($colors[$v['color']])) $colors[$v['color']] = ['name' => $v['color'], 'hex' => $v['color_hex'] ?: '#cccccc', 'img' => (int)$v['image_id']];
    if ($v['size'] !== '' && !in_array($v['size'], $sizes, true)) $sizes[] = $v['size'];
    $vmap[$v['color'] . '|' . $v['size']] = ['stock' => (int)$v['stock'], 'price' => $v['price'], 'sku' => $v['sku']];
}
$simple = $p && count($variants) <= 1 && !$colors && !$sizes;
$imgJson = array_map(fn($i) => ['id' => (int)$i['id'], 'url' => media($i['path'])], $images);
?>
<form method="post" action="<?= e(url($p ? 'admin/products/' . $p['id'] . '/save' : 'admin/products/save')) ?>" enctype="multipart/form-data" class="pform" id="pform">
<?= csrf_field() ?>
<div class="grid2 wide-left">
  <div>
    <div class="card">
      <label>Product name *<input name="name" required value="<?= $o('name') ?>" placeholder="e.g. Oversized Cotton T-Shirt"></label>
      <label>Short description <small class="muted">(shown under the price)</small><input name="short_desc" value="<?= $o('short_desc') ?>" maxlength="500"></label>
      <label>Full description</label>
      <div class="rte-bar" data-for="desc"><button type="button" data-cmd="bold"><b>B</b></button><button type="button" data-cmd="italic"><i>I</i></button><button type="button" data-cmd="insertUnorderedList">• List</button><button type="button" data-cmd="formatBlock" data-val="h3">Heading</button><button type="button" data-cmd="createLink">Link</button></div>
      <div class="rte-edit" contenteditable="true" id="desc"><?= clean_html((string)old('description', $p['description'] ?? '')) ?></div>
      <textarea name="description" id="descField" hidden></textarea>
    </div>

    <div class="card">
      <h2>Photos</h2>
      <p class="muted small">Upload as many as you like (JPG, PNG or WEBP, up to 8 MB each). Click ★ to choose the main photo. Drag to change the order.</p>
      <div class="imgs" id="imgs">
        <?php foreach ($images as $i => $im): ?>
          <div class="img-tile" draggable="true" data-id="<?= $im['id'] ?>"><img src="<?= e(media($im['path'])) ?>" alt="">
            <input type="hidden" name="img_sort[<?= $im['id'] ?>]" value="<?= $i ?>">
            <label class="feat-pick" title="Main photo"><input type="radio" name="img_featured" value="<?= $im['id'] ?>" <?= $im['is_featured'] ? 'checked' : '' ?>>★</label>
            <label class="del-pick" title="Delete"><input type="checkbox" name="img_delete[]" value="<?= $im['id'] ?>">✕</label>
          </div>
        <?php endforeach ?>
        <label class="img-add"><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple id="imgInput"><?= icon('plus', 26) ?><span>Add photos</span></label>
      </div>
      <div class="imgs" id="imgPreview"></div>
    </div>

    <div class="card">
      <h2>Sizes, colours &amp; stock</h2>
      <label class="check"><input type="checkbox" id="hasVariants" <?= $simple ? '' : 'checked' ?>> This product has sizes and/or colours</label>
      <div id="simpleBox" <?= $simple ? '' : 'hidden' ?>><label>Stock quantity<input type="number" name="simple_stock" min="0" value="<?= (int)($variants[0]['stock'] ?? 0) ?>"></label></div>
      <div id="varBox" <?= $simple ? 'hidden' : '' ?>>
        <label>Sizes <small class="muted">(separate with commas)</small><input id="sizesIn" value="<?= e(implode(', ', $sizes)) ?>" placeholder="S, M, L, XL  or  28, 30, 32  or  40, 41, 42"></label>
        <div class="presets"><small class="muted">Quick fill:</small> <button type="button" data-sizes="S, M, L, XL, XXL">Shirt/T-shirt</button><button type="button" data-sizes="28, 30, 32, 34, 36">Pant</button><button type="button" data-sizes="39, 40, 41, 42, 43, 44">Shoe</button></div>
        <label>Colours</label>
        <div id="colorList" class="color-list"></div>
        <button type="button" class="btn ghost sm" id="addColor"><?= icon('plus', 16) ?> Add colour</button>
        <h3 style="margin-top:20px">Stock for each size &amp; colour</h3>
        <p class="muted small">Price is optional; leave empty to use the product price.</p>
        <div class="tbl-wrap"><table class="tbl matrix" id="matrix"></table></div>
      </div>
    </div>

    <div class="card">
      <h2>Search engine (SEO)</h2>
      <label>SEO title<input name="seo_title" value="<?= $o('seo_title') ?>" maxlength="190" placeholder="Leave empty to use the product name"></label>
      <label>SEO description<textarea name="seo_desc" rows="2" maxlength="320"><?= $o('seo_desc') ?></textarea></label>
      <label>URL<div class="url-in"><span><?= e(abs_url('product/')) ?></span><input name="slug" value="<?= $o('slug') ?>" placeholder="auto"></div></label>
    </div>
  </div>

  <div>
    <div class="card sticky">
      <label>Status
        <select name="status"><?php $st = old('status', $p['status'] ?? 'active'); foreach (['active' => 'Active (visible on website)', 'draft' => 'Draft (hidden)', 'archived' => 'Archived'] as $k => $l): ?><option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></label>
      <label>Publish at <small class="muted">(optional, schedules it)</small><input type="datetime-local" name="publish_at" value="<?= !empty($p['publish_at']) ? date('Y-m-d\TH:i', strtotime($p['publish_at'])) : '' ?>"></label>
      <label class="check"><input type="checkbox" name="featured" value="1" <?= old('featured', $p['featured'] ?? 0) ? 'checked' : '' ?>> Featured product</label>
      <button class="btn block"><?= $p ? 'Save changes' : 'Add product' ?></button>
      <?php if ($p): ?><a class="btn ghost block" href="<?= e(url('product/' . $p['slug'])) ?>" target="_blank">View on website</a><?php endif ?>
    </div>
    <div class="card">
      <h2>Price</h2>
      <label>Regular price (৳) *<input type="number" name="price" min="1" step="0.01" required value="<?= $o('price') ?>"></label>
      <label>Sale price (৳) <small class="muted">(optional)</small><input type="number" name="sale_price" min="0" step="0.01" value="<?= $o('sale_price') ?>"></label>
      <label>Cost price (৳) <small class="muted">(only admins see this)</small><input type="number" name="cost_price" min="0" step="0.01" value="<?= $o('cost_price') ?>"></label>
    </div>
    <div class="card">
      <h2>Organize</h2>
      <label>Category<select name="category_id"><option value="">— None —</option><?php $cs = old('category_id', $p['category_id'] ?? ''); foreach (categories_all(false) as $c): ?><option value="<?= $c['id'] ?>" <?= (string)$cs === (string)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?></select></label>
      <?php $allCols = rows('SELECT id, name FROM collections ORDER BY sort, name'); if ($allCols): ?>
      <label>Collections</label><div class="checks"><?php foreach ($allCols as $c): ?><label class="check"><input type="checkbox" name="collections[]" value="<?= $c['id'] ?>" <?= in_array((int)$c['id'], $cols, true) ? 'checked' : '' ?>> <?= e($c['name']) ?></label><?php endforeach ?></div>
      <?php endif ?>
      <label>Tags <small class="muted">(comma separated, helps search)</small><input name="tags" value="<?= $o('tags') ?>"></label>
      <label>SKU<input name="sku" value="<?= $o('sku') ?>" placeholder="auto"></label>
    </div>
    <div class="card">
      <h2>Video <small class="muted">(optional)</small></h2>
      <label>YouTube link<input name="video_url" value="<?= e(preg_match('~^https?://~', (string)($p['video_url'] ?? '')) ? $p['video_url'] : '') ?>" placeholder="https://youtube.com/..."></label>
      <label>or upload MP4 (max 60 MB)<input type="file" name="video_file" accept="video/mp4,video/webm"></label>
    </div>
    <?php if ($p): ?>
    <div class="card danger-zone">
      <button class="btn ghost block" formaction="<?= e(url('admin/products/' . $p['id'] . '/duplicate')) ?>" formnovalidate>Duplicate product</button>
      <button class="btn danger block" formaction="<?= e(url('admin/products/' . $p['id'] . '/delete')) ?>" formnovalidate onclick="return confirm('Delete this product permanently?')">Delete product</button>
    </div>
    <?php endif ?>
  </div>
</div>
<script type="application/json" id="varData"><?= json_encode(['colors' => array_values($colors), 'sizes' => $sizes, 'map' => $vmap, 'images' => $imgJson], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
</form>
<?php clear_old(); ?>
