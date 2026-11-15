<?php
declare(strict_types=1);

/* ---------------- Admin ---------------- */

function role_permissions(): array {
    $all = ['dashboard', 'orders', 'customers', 'products', 'inventory', 'collections', 'categories', 'coupons', 'reviews',
        'content', 'emails', 'settings', 'integrations', 'admins', 'logs', 'backup', 'reports', 'messages'];
    return [
        'super_admin' => $all,
        'manager' => array_values(array_diff($all, ['admins', 'integrations', 'backup'])),
        'order_manager' => ['dashboard', 'orders', 'customers', 'messages', 'reports'],
        'product_manager' => ['dashboard', 'products', 'inventory', 'collections', 'categories', 'reviews'],
    ];
}
function role_label(string $r): string {
    return ['super_admin' => 'Super Admin', 'manager' => 'Manager', 'order_manager' => 'Order Manager', 'product_manager' => 'Product Manager'][$r] ?? $r;
}

function admin(): ?array {
    static $a = false;
    if ($a !== false) return $a;
    $id = $_SESSION['admin_id'] ?? null;
    if (!$id) return $a = null;
    // Re-check from DB on every request so disabling/role change takes effect immediately
    $a = row('SELECT id, name, email, role, active FROM admins WHERE id=?', [$id]);
    if (!$a || !$a['active'] || ($_SESSION['admin_fp'] ?? '') !== admin_fingerprint()) { unset($_SESSION['admin_id']); return $a = null; }
    if (time() - ($_SESSION['admin_seen'] ?? 0) > 4 * 3600) { unset($_SESSION['admin_id']); return $a = null; }
    $_SESSION['admin_seen'] = time();
    return $a;
}
function admin_fingerprint(): string { return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|rana'); }

function can(string $perm): bool {
    $a = admin();
    return $a && in_array($perm, role_permissions()[$a['role']] ?? [], true);
}
function require_admin(?string $perm = null): array {
    $a = admin();
    if (!$a) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_out(['ok' => false, 'error' => 'Login required'], 401);
        $_SESSION['admin_next'] = $_SERVER['REQUEST_URI'] ?? '';
        redirect('/admin/login');
    }
    if ($perm && !can($perm)) {
        http_response_code(403);
        echo admin_page('forbidden', ['title' => 'No access']);
        exit;
    }
    return $a;
}

/** Returns [ok(bool), message]. Locks the account for 15 minutes after 5 bad passwords. */
function admin_login(string $email, string $pass): array {
    $a = row('SELECT * FROM admins WHERE email=?', [mb_strtolower(trim($email))]);
    $generic = 'Email or password is wrong.';
    if (!$a) { password_verify($pass, '$2y$10$abcdefghijklmnopqrstuuW3bPDxkYxtwN6Th5Yf6yQe5V5b5n5G'); return [false, $generic]; }
    if (!$a['active']) return [false, 'This admin account is disabled.'];
    if ($a['locked_until'] && strtotime($a['locked_until']) > time()) return [false, 'Too many wrong attempts. Try again after ' . date('h:i A', strtotime($a['locked_until'])) . '.'];
    if (!password_verify($pass, $a['pass_hash'])) {
        $n = $a['failed_count'] + 1;
        update('admins', ['failed_count' => $n >= 5 ? 0 : $n, 'locked_until' => $n >= 5 ? date('Y-m-d H:i:s', time() + 900) : null], 'id=?', [$a['id']]);
        return [false, $generic];
    }
    if (password_needs_rehash($a['pass_hash'], PASSWORD_DEFAULT)) update('admins', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id=?', [$a['id']]);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$a['id'];
    $_SESSION['admin_fp'] = admin_fingerprint();
    $_SESSION['admin_seen'] = time();
    update('admins', ['failed_count' => 0, 'locked_until' => null, 'last_login' => now()], 'id=?', [$a['id']]);
    log_activity('Admin login', 'admin', (int)$a['id'], '', ['id' => $a['id'], 'name' => $a['name']]);
    return [true, ''];
}
function admin_logout(): void {
    unset($_SESSION['admin_id'], $_SESSION['admin_fp']);
    session_regenerate_id(true);
}

function log_activity(string $action, string $entity = '', ?int $entityId = null, string $details = '', ?array $who = null): void {
    $who = $who ?? admin();
    try {
        insert('activity_log', ['admin_id' => $who['id'] ?? null, 'admin_name' => $who['name'] ?? 'System', 'action' => $action, 'entity' => $entity,
            'entity_id' => $entityId, 'details' => mb_substr($details, 0, 2000), 'ip' => client_ip()]);
    } catch (Throwable $e) {}
}

function notify_admin(string $type, string $title, string $link = ''): void {
    insert('notifications', ['type' => $type, 'title' => mb_substr($title, 0, 250), 'link' => $link]);
}

/* ---------------- Customer ---------------- */

function customer(): ?array {
    static $c = false;
    if ($c !== false) return $c;
    $id = $_SESSION['customer_id'] ?? null;
    if (!$id) return $c = null;
    $c = row('SELECT id, name, email, phone, active FROM customers WHERE id=?', [$id]);
    if (!$c || !$c['active']) { unset($_SESSION['customer_id']); return $c = null; }
    return $c;
}
function require_customer(): array {
    $c = customer();
    if (!$c) { $_SESSION['customer_next'] = $_SERVER['REQUEST_URI'] ?? ''; flash('info', 'Please log in first.'); redirect('/account/login'); }
    return $c;
}
function customer_login_session(int $id): void {
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $id;
    update('customers', ['last_login' => now()], 'id=?', [$id]);
    wishlist_merge_guest($id);
}
function valid_password(string $p): ?string {
    if (strlen($p) < 8) return 'Password must be at least 8 characters.';
    if (strlen($p) > 200) return 'Password is too long.';
    return null;
}

/** Create a single-use reset token; returns the raw token for the email link. */
function create_reset_token(string $kind, int $userId): string {
    $raw = rand_token(32);
    q('UPDATE password_resets SET used=1 WHERE kind=? AND user_id=? AND used=0', [$kind, $userId]);
    insert('password_resets', ['kind' => $kind, 'user_id' => $userId, 'token_hash' => hash('sha256', $raw), 'expires_at' => date('Y-m-d H:i:s', time() + 3600)]);
    return $raw;
}
function find_reset_token(string $kind, string $raw): ?array {
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) return null;
    return row('SELECT * FROM password_resets WHERE kind=? AND token_hash=? AND used=0 AND expires_at>NOW()', [$kind, hash('sha256', $raw)]);
}
