<?php
declare(strict_types=1);

const PUBLISHED_SQL = "p.status='active' AND (p.publish_at IS NULL OR p.publish_at <= NOW())";

function product_price(array $p): float { return (float)(($p['sale_price'] !== null && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price']) ? $p['sale_price'] : $p['price']); }
function variant_price(array $v, array $p): float { return $v['price'] !== null && (float)$v['price'] > 0 ? (float)$v['price'] : product_price($p); }
function product_on_sale(array $p): bool { return $p['sale_price'] !== null && (float)$p['sale_price'] > 0 && (float)$p['sale_price'] < (float)$p['price']; }

function categories_all(bool $visibleOnly = true): array {
    static $c = [];
    $k = (int)$visibleOnly;
    if (!isset($c[$k])) $c[$k] = rows('SELECT * FROM categories' . ($visibleOnly ? ' WHERE visible=1' : '') . ' ORDER BY sort, name');
    return $c[$k];
}
function collections_visible(): array {
    static $c = null;
    return $c ??= rows('SELECT * FROM collections WHERE visible=1 ORDER BY sort, name');
}

/**
 * Query storefront products.
 * $f keys: category(slug or id list), collection(id), q, featured, sort(new|best|price_asc|price_desc|name), size, color, min, max, ids, exclude, new_days, page, per
 */
function product_query(array $f): array {
    $where = [PUBLISHED_SQL]; $params = []; $join = '';
    if (!empty($f['category_ids'])) {
        $ids = array_map('intval', (array)$f['category_ids']);
        $where[] = 'p.category_id IN (' . implode(',', $ids ?: [0]) . ')';
    }
    if (!empty($f['collection'])) { $join .= ' JOIN product_collections pc ON pc.product_id=p.id AND pc.collection_id=' . (int)$f['collection']; }
    if (!empty($f['featured'])) $where[] = 'p.featured=1';
    if (!empty($f['ids'])) $where[] = 'p.id IN (' . implode(',', array_map('intval', (array)$f['ids']) ?: [0]) . ')';
    if (!empty($f['exclude'])) $where[] = 'p.id NOT IN (' . implode(',', array_map('intval', (array)$f['exclude'])) . ')';
    if (!empty($f['new_days'])) { $where[] = 'p.created_at >= ?'; $params[] = date('Y-m-d H:i:s', time() - 86400 * (int)$f['new_days']); }
    if (($f['q'] ?? '') !== '') {
        $term = mb_substr($f['q'], 0, 100);
        $where[] = '(p.name LIKE ? OR p.tags LIKE ? OR p.short_desc LIKE ? OR p.sku LIKE ? OR EXISTS(SELECT 1 FROM variants vs WHERE vs.product_id=p.id AND vs.sku LIKE ?))';
        $like = '%' . addcslashes($term, '%_\\') . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if (($f['size'] ?? '') !== '') { $where[] = 'EXISTS(SELECT 1 FROM variants v1 WHERE v1.product_id=p.id AND v1.active=1 AND v1.stock>0 AND v1.size=?)'; $params[] = $f['size']; }
    if (($f['color'] ?? '') !== '') { $where[] = 'EXISTS(SELECT 1 FROM variants v2 WHERE v2.product_id=p.id AND v2.active=1 AND v2.color=?)'; $params[] = $f['color']; }
    $priceExpr = "(CASE WHEN p.sale_price>0 AND p.sale_price<p.price THEN p.sale_price ELSE p.price END)";
    if (is_numeric($f['min'] ?? null)) { $where[] = "$priceExpr >= ?"; $params[] = (float)$f['min']; }
    if (is_numeric($f['max'] ?? null) && (float)$f['max'] > 0) { $where[] = "$priceExpr <= ?"; $params[] = (float)$f['max']; }
    $order = match ($f['sort'] ?? 'new') {
        'best' => 'p.sold_count DESC, p.id DESC',
        'price_asc' => "$priceExpr ASC",
        'price_desc' => "$priceExpr DESC",
        'name' => 'p.name ASC',
        'manual' => !empty($f['collection']) ? 'pc.sort, p.id DESC' : 'p.id DESC',
        'ids' => !empty($f['ids']) ? 'FIELD(p.id,' . implode(',', array_map('intval', (array)$f['ids'])) . ')' : 'p.id DESC',
        default => 'p.created_at DESC, p.id DESC',
    };
    $w = implode(' AND ', $where);
    $total = (int)val("SELECT COUNT(DISTINCT p.id) FROM products p $join WHERE $w", $params);
    $per = (int)($f['per'] ?? 24);
    $pg = paginate($total, $per, (int)($f['page'] ?? 1));
    $list = rows("SELECT p.* FROM products p $join WHERE $w ORDER BY $order LIMIT {$pg['per']} OFFSET {$pg['offset']}", $params);
    return ['items' => attach_card_data($list), 'pager' => $pg];
}

/** Add featured image, hover image, total stock and colour swatches to product rows. */
function attach_card_data(array $list): array {
    if (!$list) return [];
    $ids = implode(',', array_map(fn($p) => (int)$p['id'], $list));
    $imgs = [];
    foreach (rows("SELECT product_id, path, alt FROM product_images WHERE product_id IN ($ids) ORDER BY is_featured DESC, sort, id") as $i) $imgs[$i['product_id']][] = $i;
    $stock = []; $colors = []; $sizes = [];
    foreach (rows("SELECT product_id, color, color_hex, size, stock FROM variants WHERE active=1 AND product_id IN ($ids) ORDER BY sort, id") as $v) {
        $stock[$v['product_id']] = ($stock[$v['product_id']] ?? 0) + (int)$v['stock'];
        if ($v['color'] !== '') $colors[$v['product_id']][$v['color']] = $v['color_hex'];
        if ($v['size'] !== '' && $v['stock'] > 0) $sizes[$v['product_id']][$v['size']] = true;
    }
    $ratings = [];
    foreach (rows("SELECT product_id, AVG(rating) r, COUNT(*) n FROM reviews WHERE status='approved' AND product_id IN ($ids) GROUP BY product_id") as $r) $ratings[$r['product_id']] = $r;
    foreach ($list as &$p) {
        $pid = $p['id'];
        $p['image'] = $imgs[$pid][0]['path'] ?? null;
        $p['image2'] = $imgs[$pid][1]['path'] ?? null;
        $p['stock_total'] = $stock[$pid] ?? 0;
        $p['colors'] = $colors[$pid] ?? [];
        $p['sizes'] = array_keys($sizes[$pid] ?? []);
        $p['final_price'] = product_price($p);
        $p['rating'] = isset($ratings[$pid]) ? round((float)$ratings[$pid]['r'], 1) : 0;
        $p['rating_n'] = (int)($ratings[$pid]['n'] ?? 0);
    }
    return $list;
}

function product_by_slug(string $slug, bool $allowUnpublished = false): ?array {
    $p = row('SELECT p.* FROM products p WHERE p.slug=?' . ($allowUnpublished ? '' : ' AND ' . PUBLISHED_SQL), [$slug]);
    if (!$p) return null;
    $p['images'] = rows('SELECT * FROM product_images WHERE product_id=? ORDER BY is_featured DESC, sort, id', [$p['id']]);
    $p['variants'] = rows('SELECT * FROM variants WHERE product_id=? AND active=1 ORDER BY sort, id', [$p['id']]);
    $p['category'] = $p['category_id'] ? row('SELECT * FROM categories WHERE id=?', [$p['category_id']]) : null;
    $p['final_price'] = product_price($p);
    return $p;
}

function category_by_slug(string $slug): ?array { return row('SELECT * FROM categories WHERE slug=? AND visible=1', [$slug]); }
function category_descendant_ids(int $id): array {
    $all = categories_all(); $ids = [$id]; $changed = true;
    while ($changed) { $changed = false; foreach ($all as $c) if ($c['parent_id'] && in_array((int)$c['parent_id'], $ids, true) && !in_array((int)$c['id'], $ids, true)) { $ids[] = (int)$c['id']; $changed = true; } }
    return $ids;
}

/** Distinct sizes/colours available in the shop (for filters). */
function filter_options(): array {
    $sizes = array_column(rows("SELECT DISTINCT v.size FROM variants v JOIN products p ON p.id=v.product_id WHERE v.active=1 AND v.size<>'' AND " . PUBLISHED_SQL), 'size');
    $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2XL', '3XL', '4XL'];
    usort($sizes, function ($a, $b) use ($order) {
        $ia = array_search($a, $order, true); $ib = array_search($b, $order, true);
        if ($ia !== false && $ib !== false) return $ia <=> $ib;
        if (is_numeric($a) && is_numeric($b)) return $a <=> $b;
        return $ia !== false ? -1 : ($ib !== false ? 1 : strcmp($a, $b));
    });
    $colors = rows("SELECT v.color, MAX(v.color_hex) hex FROM variants v JOIN products p ON p.id=v.product_id WHERE v.active=1 AND v.color<>'' AND " . PUBLISHED_SQL . " GROUP BY v.color ORDER BY v.color");
    return ['sizes' => $sizes, 'colors' => $colors];
}

/**
 * Change one variant's stock and record it in the stock history.
 * Must be called inside a transaction when part of a bigger operation.
 */
function adjust_stock(int $variantId, int $delta, string $reason, string $ref = '', bool $allowNegative = false): int {
    $v = row('SELECT id, product_id, stock FROM variants WHERE id=? FOR UPDATE', [$variantId]);
    if (!$v) throw new RuntimeException('Variant not found');
    $new = (int)$v['stock'] + $delta;
    if ($new < 0 && !$allowNegative) throw new RuntimeException('Not enough stock');
    $new = max(0, $new);
    q('UPDATE variants SET stock=? WHERE id=?', [$new, $variantId]);
    $a = admin();
    insert('stock_log', ['variant_id' => $variantId, 'product_id' => $v['product_id'], 'change_qty' => $delta, 'stock_after' => $new, 'reason' => $reason, 'ref' => $ref,
        'admin_id' => $a['id'] ?? null, 'admin_name' => $a['name'] ?? '']);
    $th = (int)setting('low_stock_threshold', 5);
    if ($delta < 0 && $new <= $th && (int)$v['stock'] > $th) {
        $p = row('SELECT name FROM products WHERE id=?', [$v['product_id']]);
        notify_admin($new === 0 ? 'out_of_stock' : 'low_stock', ($new === 0 ? 'Out of stock: ' : 'Low stock (' . $new . '): ') . ($p['name'] ?? ''), '/admin/products/' . $v['product_id'] . '/edit');
    }
    return $new;
}

/* ---------- Wishlist ---------- */
function wishlist_ids(): array {
    if ($c = customer()) return array_map('intval', array_column(rows('SELECT product_id FROM wishlists WHERE customer_id=?', [$c['id']]), 'product_id'));
    return array_map('intval', $_SESSION['wishlist'] ?? []);
}
function wishlist_toggle(int $pid): bool {
    if (!val('SELECT id FROM products WHERE id=?', [$pid])) return false;
    if ($c = customer()) {
        if (val('SELECT 1 FROM wishlists WHERE customer_id=? AND product_id=?', [$c['id'], $pid])) { q('DELETE FROM wishlists WHERE customer_id=? AND product_id=?', [$c['id'], $pid]); return false; }
        q('INSERT IGNORE INTO wishlists (customer_id, product_id) VALUES (?,?)', [$c['id'], $pid]); return true;
    }
    $w = $_SESSION['wishlist'] ?? [];
    if (in_array($pid, $w, true)) { $_SESSION['wishlist'] = array_values(array_diff($w, [$pid])); return false; }
    $w[] = $pid; $_SESSION['wishlist'] = array_slice(array_values(array_unique($w)), -100); return true;
}
function wishlist_merge_guest(int $cid): void {
    foreach ($_SESSION['wishlist'] ?? [] as $pid) q('INSERT IGNORE INTO wishlists (customer_id, product_id) SELECT ?, id FROM products WHERE id=?', [$cid, (int)$pid]);
    unset($_SESSION['wishlist']);
}
