<?php $ids = array_filter(array_map('intval', (array)($s['product_ids'] ?? [])));
$items = $ids ? product_query(['ids' => $ids, 'sort' => 'ids', 'per' => 24])['items'] : product_query(['featured' => 1, 'sort' => 'best', 'per' => max(1, min(24, (int)($s['limit'] ?? 8)))])['items']; ?>
<section <?= sec_attr($s) ?> id="<?= $sid ?>"><div class="wrap"><?= sec_head($s) ?><?= sec_products($items, $s) ?></div></section>
