<?php
declare(strict_types=1);

/** Default values for every setting. The DB overrides these; admins edit them in Settings. */
function setting_defaults(): array {
    return [
        'store_name' => 'Rana Shop',
        'tagline' => 'Premium menswear, made for everyday',
        'logo' => '', 'logo_light' => '', 'favicon' => '',
        'currency_symbol' => '৳',
        'contact_email' => '', 'contact_phone' => '', 'whatsapp' => '', 'address' => 'Dhaka, Bangladesh',
        'social' => ['facebook' => '', 'instagram' => '', 'tiktok' => '', 'youtube' => ''],
        'announcement' => ['enabled' => 1, 'text' => 'Free delivery inside Dhaka on orders over ৳3,000 · Cash on Delivery all over Bangladesh', 'link' => '/shop', 'bg' => '#111111', 'color' => '#ffffff'],
        'footer_about' => 'Rana Shop makes clean, well-made menswear: shirts, tees, polos, pants and outerwear, delivered across Bangladesh.',
        'footer_copyright' => '© {year} Rana Shop. All rights reserved.',
        // shipping
        'ship_inside' => 70, 'ship_outside' => 130, 'free_ship_over' => 3000, 'inside_districts' => ['Dhaka'],
        // stock
        'low_stock_threshold' => 5,
        // checkout
        'require_email' => 1, 'guest_checkout' => 1,
        'pay_cod' => 1, 'pay_bkash' => 0, 'pay_nagad' => 0, 'pay_sslcommerz' => 0,
        // email
        'mail_driver' => 'resend', // resend | smtp | mail
        'mail_from_name' => 'Rana Shop', 'mail_from_email' => '',
        'notify_email' => '', 'notify_enabled' => 1, 'customer_emails_enabled' => 1,
        'smtp' => ['host' => '', 'port' => 587, 'user' => '', 'secure' => 'tls'],
        // tracking / seo
        'ga4_id' => '', 'meta_pixel_id' => '', 'gsc_verification' => '',
        'seo_title' => 'Rana Shop | Premium Menswear in Bangladesh', 'seo_desc' => 'Shop shirts, t-shirts, polos, pants, jeans, cargo pants, hoodies and jackets. Cash on Delivery all over Bangladesh.', 'og_image' => '',
        // payment gateway config (secrets stored in secrets table below)
        'sslcz_sandbox' => 1, 'bkash_sandbox' => 1, 'nagad_sandbox' => 1,
        // courier
        'steadfast_enabled' => 0, 'pathao_enabled' => 0, 'pathao_sandbox' => 1, 'pathao_store_id' => '',
    ];
}

function settings_all(bool $fresh = false): array {
    static $cache = null;
    if ($cache !== null && !$fresh) return $cache;
    $cache = setting_defaults();
    try {
        foreach (rows('SELECT k, v FROM settings') as $r) {
            $d = json_decode((string)$r['v'], true);
            $cache[$r['k']] = (json_last_error() === JSON_ERROR_NONE) ? $d : $r['v'];
        }
    } catch (Throwable $e) { /* before install */ }
    return $cache;
}
function setting(string $k, $default = null) {
    $all = settings_all();
    return array_key_exists($k, $all) ? $all[$k] : $default;
}
function set_setting(string $k, $v): void {
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v=VALUES(v)', [$k, json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    settings_all(true);
}

/**
 * Secrets (API keys, passwords) live in app/secrets.php, not the database or the browser.
 * The admin can write them from Settings; the file is in the web-blocked app folder.
 */
function secrets_file(): string { return APP_DIR . '/secrets.php'; }
function secrets_all(): array {
    if (!isset($GLOBALS['__secrets'])) {
        $f = secrets_file(); $s = is_file($f) ? (require $f) : [];
        $GLOBALS['__secrets'] = is_array($s) ? $s : [];
    }
    return $GLOBALS['__secrets'];
}
function secret(string $k, string $default = ''): string { return (string)(secrets_all()[$k] ?? $default); }
function save_secrets(array $changes): void {
    $s = secrets_all();
    foreach ($changes as $k => $v) { if ($v === null) unset($s[$k]); else $s[$k] = (string)$v; }
    $php = "<?php\n// Rana Shop secrets. Never share this file.\nreturn " . var_export($s, true) . ";\n";
    $tmp = secrets_file() . '.tmp';
    file_put_contents($tmp, $php, LOCK_EX);
    @chmod($tmp, 0600);
    rename($tmp, secrets_file());
    $GLOBALS['__secrets'] = $s;
    if (function_exists('opcache_invalidate')) @opcache_invalidate(secrets_file(), true);
}
function has_secret(string $k): bool { return secret($k) !== ''; }
