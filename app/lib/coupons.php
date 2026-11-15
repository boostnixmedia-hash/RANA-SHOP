<?php
declare(strict_types=1);

/**
 * Validate a coupon against cart lines. Returns [coupon|null, error message, discount amount].
 * Pass $lock=true inside the order transaction so usage counts can't race.
 */
function coupon_evaluate(string $code, array $lines, string $email = '', ?int $customerId = null, bool $lock = false): array {
    $code = strtoupper(trim($code));
    if ($code === '') return [null, '', 0.0];
    $c = row('SELECT * FROM coupons WHERE code=?' . ($lock ? ' FOR UPDATE' : ''), [$code]);
    if (!$c || !$c['active']) return [null, 'This coupon code is not valid.', 0.0];
    $now = time();
    if ($c['starts_at'] && strtotime($c['starts_at']) > $now) return [null, 'This coupon is not active yet.', 0.0];
    if ($c['ends_at'] && strtotime($c['ends_at']) < $now) return [null, 'This coupon has expired.', 0.0];
    if ($c['usage_limit'] !== null && (int)$c['used_count'] >= (int)$c['usage_limit']) return [null, 'This coupon has reached its usage limit.', 0.0];
    $email = mb_strtolower(trim($email));
    if ($c['customer_email']) {
        $allowed = array_map(fn($x) => mb_strtolower(trim($x)), explode(',', $c['customer_email']));
        if ($email === '' && $customerId) $email = mb_strtolower((string)val('SELECT email FROM customers WHERE id=?', [$customerId]));
        if ($email === '') return [null, 'Enter your email at checkout to use this coupon.', 0.0];
        if (!in_array($email, $allowed, true)) return [null, 'This coupon is not valid for your account.', 0.0];
    }
    if ($c['per_customer_limit'] !== null && $email !== '') {
        $used = (int)val('SELECT COUNT(*) FROM coupon_uses WHERE coupon_id=? AND email=?', [$c['id'], $email]);
        if ($used >= (int)$c['per_customer_limit']) return [null, 'You have already used this coupon.', 0.0];
    }
    // Eligible lines (product/category restricted coupons)
    $pids = array_filter(array_map('intval', explode(',', $c['product_ids'])));
    $cids = array_filter(array_map('intval', explode(',', $c['category_ids'])));
    if ($cids) { $exp = []; foreach ($cids as $cid) $exp = array_merge($exp, category_descendant_ids($cid)); $cids = $exp; }
    $eligible = array_filter($lines, function ($l) use ($pids, $cids) {
        if (!$pids && !$cids) return true;
        return ($pids && in_array((int)$l['product_id'], $pids, true)) || ($cids && in_array((int)$l['category_id'], $cids, true));
    });
    if (!$eligible) return [null, 'This coupon does not apply to the items in your cart.', 0.0];
    $base = array_sum(array_column($eligible, 'line_total'));
    $cartSub = array_sum(array_column($lines, 'line_total'));
    if ($cartSub < (float)$c['min_purchase']) return [null, 'Minimum purchase for this coupon is ' . money($c['min_purchase']) . '.', 0.0];
    $d = $c['type'] === 'percent' ? $base * min(100, (float)$c['value']) / 100 : min((float)$c['value'], $base);
    if ($c['max_discount'] !== null && (float)$c['max_discount'] > 0) $d = min($d, (float)$c['max_discount']);
    return [$c, '', round($d, 2)];
}
