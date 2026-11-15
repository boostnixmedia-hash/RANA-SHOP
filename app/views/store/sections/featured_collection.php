<?php $col = row('SELECT * FROM collections WHERE id=? AND visible=1', [(int)($s['collection_id'] ?? 0)]); if (!$col) return;
$s['heading'] = $s['heading'] ?: $col['name']; if (empty($s['button_link'])) $s['button_link'] = '/collection/' . $col['slug']; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap"><?= sec_head($s) ?><?= sec_products(product_query(['collection' => $col['id'], 'sort' => 'manual', 'per' => max(1, min(24, (int)($s['limit'] ?? 8)))])['items'], $s) ?></div></section>
