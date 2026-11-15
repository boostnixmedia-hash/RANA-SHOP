<?php
declare(strict_types=1);

const CART_MAX_QTY = 20;

function cart_raw(): array { return $_SESSION['cart'] ?? []; }
function cart_save(array $c): void { $_SESSION['cart'] = $c; }

/** Lines with live product data. Drops lines whose product/variant vanished. */
function cart_lines(): array {
    $raw = cart_raw();
    if (!$raw) return [];
    $ids = implode(',', array_map('intval', array_keys($raw)));
    $vs = rows("SELECT v.*, p.name, p.slug, p.price p_price, p.sale_price p_sale, p.category_id, p.status, p.publish_at,
        (SELECT path FROM product_images i WHERE i.id=v.image_id) v_img,
        (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_featured DESC, sort, id LIMIT 1) p_img
        FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id IN ($ids)");
    $lines = []; $changed = false;
    foreach ($vs as $v) {
        $published = $v['status'] === 'active' && (!$v['publish_at'] || strtotime($v['publish_at']) <= time()) && $v['active'];
        if (!$published) { $changed = true; continue; }
        $qty = (int)$raw[$v['id']];
        $price = variant_price($v, ['price' => $v['p_price'], 'sale_price' => $v['p_sale']]);
        $lines[] = [
            'variant_id' => (int)$v['id'], 'product_id' => (int)$v['product_id'], 'name' => $v['name'], 'slug' => $v['slug'],
            'color' => $v['color'], 'size' => $v['size'], 'sku' => $v['sku'], 'image' => $v['v_img'] ?: $v['p_img'],
            'price' => $price, 'compare' => (float)$v['p_price'], 'qty' => $qty, 'stock' => (int)$v['stock'], 'category_id' => $v['category_id'],
            'line_total' => $price * $qty, 'ok' => $qty <= (int)$v['stock'],
        ];
    }
    if ($changed || count($lines) !== count($raw)) { $c = []; foreach ($lines as $l) $c[$l['variant_id']] = $l['qty']; cart_save($c); }
    return $lines;
}
function cart_count(): int { return array_sum(cart_raw()); }

/** Returns [ok, message]. */
function cart_add(int $variantId, int $qty): array {
    $v = row("SELECT v.id, v.stock, v.active, p.status, p.publish_at FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=?", [$variantId]);
    if (!$v || !$v['active'] || $v['status'] !== 'active' || ($v['publish_at'] && strtotime($v['publish_at']) > time())) return [false, 'This item is not available.'];
    $c = cart_raw();
    $want = ($c[$variantId] ?? 0) + max(1, $qty);
    if ($v['stock'] <= 0) return [false, 'Sorry, this size/colour is out of stock.'];
    if ($want > $v['stock']) return [false, 'Only ' . $v['stock'] . ' left in stock.'];
    $c[$variantId] = min($want, CART_MAX_QTY);
    cart_save($c);
    return [true, 'Added to cart'];
}
function cart_set(int $variantId, int $qty): array {
    $c = cart_raw();
    if (!isset($c[$variantId])) return [false, 'Item not in cart'];
    if ($qty <= 0) { unset($c[$variantId]); cart_save($c); return [true, 'Removed']; }
    $stock = (int)val('SELECT stock FROM variants WHERE id=?', [$variantId]);
    if ($qty > $stock) { $c[$variantId] = max(1, min($stock, CART_MAX_QTY)); cart_save($c); return [false, 'Only ' . $stock . ' left in stock.']; }
    $c[$variantId] = min($qty, CART_MAX_QTY);
    cart_save($c);
    return [true, 'Updated'];
}
function cart_clear(): void { unset($_SESSION['cart'], $_SESSION['coupon']); }

function shipping_zone(string $district): string {
    $inside = array_map('mb_strtolower', (array)setting('inside_districts', ['Dhaka']));
    return in_array(mb_strtolower(trim($district)), $inside, true) ? 'inside' : 'outside';
}
function shipping_fee(string $zone, float $subtotalAfterDiscount): float {
    $free = (float)setting('free_ship_over', 0);
    if ($free > 0 && $subtotalAfterDiscount >= $free) return 0.0;
    return (float)($zone === 'inside' ? setting('ship_inside', 70) : setting('ship_outside', 130));
}

/**
 * Totals for a set of lines. Coupon is validated here (server side) every time.
 * Returns subtotal, discount, shipping, total, coupon (array|null), coupon_error.
 */
function cart_totals(array $lines, string $zone = 'inside', ?string $couponCode = null, string $email = ''): array {
    $sub = array_sum(array_column($lines, 'line_total'));
    $discount = 0.0; $coupon = null; $err = '';
    if ($couponCode) {
        [$coupon, $err, $discount] = coupon_evaluate($couponCode, $lines, $email, customer()['id'] ?? null);
        if (!$coupon) $discount = 0.0;
    }
    $ship = $lines ? shipping_fee($zone, $sub - $discount) : 0.0;
    return ['subtotal' => round($sub, 2), 'discount' => round($discount, 2), 'shipping' => $ship, 'total' => round(max(0, $sub - $discount) + $ship, 2),
        'coupon' => $coupon, 'coupon_error' => $err, 'zone' => $zone];
}
