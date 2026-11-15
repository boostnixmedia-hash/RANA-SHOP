<?php
declare(strict_types=1);

const ORDER_STATUSES = ['new', 'pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
const UNPAID_ONLINE_HOLD_MIN = 60; // unpaid online-payment orders release their stock after this many minutes

function new_order_no(): string {
    do { $no = 'RS' . date('ymd') . random_int(1000, 9999); } while (val('SELECT 1 FROM orders WHERE order_no=?', [$no]));
    return $no;
}

/**
 * Create an order atomically. Locks each variant row, checks stock, deducts it, records coupon use.
 * $d: name,email,phone,district,area,address,note,payment_method,coupon,idem_key
 * Returns the order row. Throws RuntimeException with a customer-friendly message.
 */
function place_order(array $d, array $lines): array {
    if (!$lines) throw new RuntimeException('Your cart is empty.');
    // Same submission twice (double click, refresh): return the first order.
    if ($existing = row('SELECT * FROM orders WHERE idem_key=?', [$d['idem_key']])) return $existing;

    $order = tx(function () use ($d, $lines) {
        $subtotal = 0.0; $items = [];
        // Lock in a stable order to avoid deadlocks between concurrent checkouts
        usort($lines, fn($a, $b) => $a['variant_id'] <=> $b['variant_id']);
        foreach ($lines as $l) {
            $v = row('SELECT v.*, p.name, p.price p_price, p.sale_price p_sale, p.status, p.publish_at, p.category_id FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE', [$l['variant_id']]);
            if (!$v || !$v['active'] || $v['status'] !== 'active') throw new RuntimeException("“{$l['name']}” is no longer available. Please remove it from your cart.");
            if ($l['qty'] < 1 || $l['qty'] > CART_MAX_QTY) throw new RuntimeException('Invalid quantity.');
            if ((int)$v['stock'] < $l['qty']) throw new RuntimeException("Only {$v['stock']} left of “{$v['name']}” ({$v['color']} {$v['size']}). Please update your cart.");
            $price = variant_price($v, ['price' => $v['p_price'], 'sale_price' => $v['p_sale']]);
            $subtotal += $price * $l['qty'];
            $items[] = ['v' => $v, 'qty' => $l['qty'], 'price' => $price, 'image' => $l['image'] ?? '',
                'product_id' => (int)$v['product_id'], 'category_id' => $v['category_id'], 'line_total' => $price * $l['qty']];
        }
        $discount = 0.0; $coupon = null;
        if (!empty($d['coupon'])) {
            [$coupon, $err, $discount] = coupon_evaluate($d['coupon'], $items, $d['email'], $d['customer_id'] ?? null, true);
            if (!$coupon) throw new RuntimeException($err ?: 'Coupon is not valid.');
        }
        $zone = shipping_zone($d['district']);
        $ship = shipping_fee($zone, $subtotal - $discount);
        $total = round(max(0, $subtotal - $discount) + $ship, 2);
        $isOnline = $d['payment_method'] !== 'cod';
        $id = insert('orders', [
            'order_no' => new_order_no(), 'customer_id' => $d['customer_id'] ?? null,
            'name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'], 'district' => $d['district'], 'area' => $d['area'], 'address' => $d['address'], 'note' => $d['note'] ?? '',
            'shipping_zone' => $zone, 'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $ship, 'total' => $total,
            'coupon_code' => $coupon['code'] ?? null, 'payment_method' => $d['payment_method'], 'payment_status' => 'pending',
            'status' => $isOnline ? 'pending' : 'new', 'idem_key' => $d['idem_key'], 'access_token' => rand_token(16), 'ip' => client_ip(),
        ]);
        $orderNo = val('SELECT order_no FROM orders WHERE id=?', [$id]);
        foreach ($items as $it) {
            $v = $it['v'];
            insert('order_items', ['order_id' => $id, 'product_id' => $v['product_id'], 'variant_id' => $v['id'], 'name' => $v['name'], 'sku' => $v['sku'],
                'color' => $v['color'], 'size' => $v['size'], 'image' => $it['image'] ?? '', 'price' => $it['price'], 'qty' => $it['qty'], 'line_total' => $it['line_total']]);
            adjust_stock((int)$v['id'], -$it['qty'], 'order', $orderNo);
            q('UPDATE products SET sold_count=sold_count+? WHERE id=?', [$it['qty'], $v['product_id']]);
        }
        if ($coupon) {
            q('UPDATE coupons SET used_count=used_count+1 WHERE id=?', [$coupon['id']]);
            insert('coupon_uses', ['coupon_id' => $coupon['id'], 'order_id' => $id, 'email' => mb_strtolower($d['email'])]);
        }
        insert('order_history', ['order_id' => $id, 'status' => $isOnline ? 'pending' : 'new', 'note' => 'Order placed (' . pay_label($d['payment_method']) . ')']);
        return row('SELECT * FROM orders WHERE id=?', [$id]);
    });

    notify_admin('order', 'New order ' . $order['order_no'] . ' from ' . $order['name'] . ' · ' . money($order['total']), '/admin/orders/' . $order['id']);
    // COD orders are final now; online orders send "received" after payment succeeds (or immediately if admin prefers).
    if ($order['payment_method'] === 'cod') order_placed_emails($order);
    return $order;
}

function order_placed_emails(array $order): void {
    queue_order_email('order_received', $order);
    if (setting('notify_enabled', 1) && setting('notify_email')) {
        foreach (array_filter(array_map('trim', explode(',', (string)setting('notify_email')))) as $to) queue_template_email('admin_new_order', $to, 'Store owner', order_email_vars($order), 'admin_new_order:' . $order['id'] . ':' . $to, (int)$order['id']);
    }
    process_email_queue(5);
}

function order_items(int $orderId): array { return rows('SELECT * FROM order_items WHERE order_id=? ORDER BY id', [$orderId]); }

/** Status change with history, stock restore and customer email. Returns error string or ''. */
function change_order_status(int $orderId, string $status, string $note = ''): string {
    if (!in_array($status, ORDER_STATUSES, true)) return 'Unknown status.';
    $res = tx(function () use ($orderId, $status, $note) {
        $o = row('SELECT * FROM orders WHERE id=? FOR UPDATE', [$orderId]);
        if (!$o) return 'Order not found.';
        if ($o['status'] === $status) return 'same';
        $restore = in_array($status, ['cancelled', 'returned'], true) && !$o['stock_restored'];
        $retake = !in_array($status, ['cancelled', 'returned'], true) && $o['stock_restored'];
        if ($retake) {
            // Re-opening a cancelled order: take stock again, refuse if not enough
            foreach (order_items($orderId) as $it) if ($it['variant_id']) {
                $s = (int)val('SELECT stock FROM variants WHERE id=? FOR UPDATE', [$it['variant_id']]);
                if ($s < $it['qty']) return "Not enough stock to re-open this order ({$it['name']} {$it['color']} {$it['size']}: $s left).";
            }
            foreach (order_items($orderId) as $it) if ($it['variant_id']) adjust_stock((int)$it['variant_id'], -(int)$it['qty'], 'order reopened', $o['order_no']);
            q('UPDATE orders SET stock_restored=0 WHERE id=?', [$orderId]);
        }
        if ($restore) {
            foreach (order_items($orderId) as $it) {
                if ($it['variant_id'] && val('SELECT id FROM variants WHERE id=?', [$it['variant_id']])) adjust_stock((int)$it['variant_id'], (int)$it['qty'], $status === 'returned' ? 'return' : 'cancel', $o['order_no']);
                if ($it['product_id']) q('UPDATE products SET sold_count=GREATEST(0, sold_count-?) WHERE id=?', [$it['qty'], $it['product_id']]);
            }
            q('UPDATE orders SET stock_restored=1 WHERE id=?', [$orderId]);
        }
        q('UPDATE orders SET status=? WHERE id=?', [$status, $orderId]);
        $a = admin();
        insert('order_history', ['order_id' => $orderId, 'status' => $status, 'note' => mb_substr($note, 0, 500), 'admin_name' => $a['name'] ?? 'System']);
        return '';
    });
    if ($res === 'same') return '';
    if ($res !== '') return $res;
    $o = row('SELECT * FROM orders WHERE id=?', [$orderId]);
    log_activity('Order status changed', 'order', $orderId, $o['order_no'] . ' → ' . $status);
    $map = ['confirmed' => 'order_confirmed', 'processing' => 'order_processing', 'shipped' => 'order_shipped', 'delivered' => 'order_delivered', 'cancelled' => 'order_cancelled'];
    if (isset($map[$status])) { queue_order_email($map[$status], $o); process_email_queue(3); }
    return '';
}

function set_payment_status(int $orderId, string $ps, string $note = ''): void {
    $o = row('SELECT * FROM orders WHERE id=?', [$orderId]);
    if (!$o || $o['payment_status'] === $ps) return;
    q('UPDATE orders SET payment_status=? WHERE id=?', [$ps, $orderId]);
    $a = admin();
    insert('order_history', ['order_id' => $orderId, 'status' => 'payment ' . $ps, 'note' => mb_substr($note, 0, 500), 'admin_name' => $a['name'] ?? 'System']);
    if ($ps === 'refunded') { queue_order_email('refund_update', row('SELECT * FROM orders WHERE id=?', [$orderId])); process_email_queue(3); }
}

/** Called when an online payment is verified with the gateway. Idempotent. */
function mark_order_paid(int $orderId, string $method, string $ref): void {
    $o = row('SELECT * FROM orders WHERE id=?', [$orderId]);
    if (!$o || $o['payment_status'] === 'paid') return;
    if ($o['stock_restored']) {
        // Paid after the hold expired: try to take stock again; flag for admin if impossible
        $err = change_order_status($orderId, 'pending', 'Payment arrived after hold expired');
        if ($err) notify_admin('payment', 'PAID order ' . $o['order_no'] . ' could not re-reserve stock: ' . $err, '/admin/orders/' . $orderId);
    }
    q("UPDATE orders SET payment_status='paid', status=IF(status IN ('pending','new'),'new',status) WHERE id=?", [$orderId]);
    insert('order_history', ['order_id' => $orderId, 'status' => 'payment paid', 'note' => pay_label($method) . ' ' . $ref, 'admin_name' => 'System']);
    $o = row('SELECT * FROM orders WHERE id=?', [$orderId]);
    notify_admin('payment', 'Payment received ' . money($o['total']) . ' for ' . $o['order_no'] . ' (' . pay_label($method) . ')', '/admin/orders/' . $orderId);
    order_placed_emails($o);
}

/** Release stock of online-payment orders that were never paid. Runs lazily and from cron. */
function release_expired_holds(): int {
    $list = rows("SELECT id FROM orders WHERE payment_method<>'cod' AND payment_status IN ('pending','failed') AND status='pending' AND stock_restored=0 AND created_at < ?",
        [date('Y-m-d H:i:s', time() - UNPAID_ONLINE_HOLD_MIN * 60)]);
    foreach ($list as $o) change_order_status((int)$o['id'], 'cancelled', 'Auto-cancelled: online payment not completed within ' . UNPAID_ONLINE_HOLD_MIN . ' minutes');
    return count($list);
}

function order_for_customer_view(string $orderNo, ?string $token = null, ?string $phone = null): ?array {
    $o = row('SELECT * FROM orders WHERE order_no=?', [strtoupper(trim($orderNo))]);
    if (!$o) return null;
    $c = customer();
    $ok = ($c && (int)$o['customer_id'] === (int)$c['id'])
        || ($token && hash_equals($o['access_token'], $token))
        || ($phone !== null && normalize_bd_phone($phone) === normalize_bd_phone($o['phone']));
    return $ok ? $o : null;
}
function order_view_url(array $o): string { return '/order/' . $o['order_no'] . '?t=' . $o['access_token']; }
