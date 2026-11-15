<?php
declare(strict_types=1);

function is_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('rana_sid');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => base_path() ?: '/', 'secure' => is_https(), 'httponly' => true, 'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_start();
}

function send_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    if (is_https()) header('Strict-Transport-Security: max-age=31536000');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = rand_token(32);
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function verify_csrf(): void {
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(419);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_out(['ok' => false, 'error' => 'Session expired. Please reload the page.'], 419);
        exit('Session expired. Please go back, reload the page and try again.');
    }
}

/** Returns true if allowed. Records a hit. $max hits per $window seconds per key. */
function rate_limit(string $key, int $max, int $window): bool {
    $k = substr($key . '|' . client_ip(), 0, 190);
    $since = time() - $window;
    if (random_int(1, 50) === 1) q('DELETE FROM rate_limits WHERE at < ?', [time() - 86400]);
    $n = (int)val('SELECT COUNT(*) FROM rate_limits WHERE k=? AND at>=?', [$k, $since]);
    if ($n >= $max) return false;
    q('INSERT INTO rate_limits (k, at) VALUES (?, ?)', [$k, time()]);
    return true;
}
function rate_limit_or_fail(string $key, int $max, int $window, string $msg = 'Too many attempts. Please wait a few minutes and try again.'): void {
    if (!rate_limit($key, $max, $window)) {
        http_response_code(429);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) json_out(['ok' => false, 'error' => $msg], 429);
        flash('error', $msg);
        back();
    }
}
