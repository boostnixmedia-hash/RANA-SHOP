<?php
declare(strict_types=1);

function e($s): string { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Base path the shop is installed under, e.g. '' or '/shop'. */
function base_path(): string {
    global $CONFIG;
    return rtrim($CONFIG['base_path'] ?? '', '/');
}
function url(string $path = ''): string { return base_path() . '/' . ltrim($path, '/'); }
function abs_url(string $path = ''): string {
    global $CONFIG;
    $site = rtrim($CONFIG['site_url'] ?? '', '/');
    if (!$site) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $site = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
    }
    return $site . '/' . ltrim($path, '/');
}
function asset(string $path): string {
    $f = ROOT_DIR . '/assets/' . $path;
    $v = is_file($f) ? filemtime($f) : 1;
    return url('assets/' . $path) . '?v=' . $v;
}
/** URL for an uploaded file path stored in DB (relative to /uploads) or an absolute http URL. */
function media(?string $p, string $fallback = ''): string {
    if (!$p) return $fallback;
    if (preg_match('~^https?://~', $p)) return $p;
    return url('uploads/' . ltrim($p, '/'));
}

function redirect(string $to, int $code = 302): never {
    if (!preg_match('~^https?://~', $to)) $to = url($to);
    header('Location: ' . $to, true, $code);
    exit;
}
function back(string $fallback = ''): never {
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === $host) { header('Location: ' . $ref); exit; }
    redirect($fallback ?: '/');
}

function json_out($data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }
function old(string $k, $default = '') { return $_SESSION['old'][$k] ?? $default; }
function keep_old(array $data): void { $_SESSION['old'] = $data; }
function clear_old(): void { unset($_SESSION['old']); }

function post(string $k, $default = '') { $v = $_POST[$k] ?? $default; return is_string($v) ? trim($v) : $v; }
function get(string $k, $default = '') { $v = $_GET[$k] ?? $default; return is_string($v) ? trim($v) : $v; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function now(): string { return date('Y-m-d H:i:s'); }
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 64); }

function money($n): string {
    $n = (float)$n;
    $s = number_format($n, fmod($n, 1.0) ? 2 : 0);
    return setting('currency_symbol', '৳') . $s;
}

function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('~[^\pL\pN]+~u', '-', $s);
    $s = trim($s, '-');
    return $s !== '' ? mb_substr($s, 0, 180) : 'item';
}
function unique_slug(string $table, string $base, ?int $exceptId = null): string {
    $slug = slugify($base); $try = $slug; $i = 2;
    while (val("SELECT id FROM `$table` WHERE slug=?" . ($exceptId ? ' AND id<>?' : ''), $exceptId ? [$try, $exceptId] : [$try])) $try = $slug . '-' . $i++;
    return $try;
}

function valid_email(string $e): bool { return (bool)filter_var($e, FILTER_VALIDATE_EMAIL) && strlen($e) <= 190; }
function normalize_bd_phone(string $p): ?string {
    $d = preg_replace('/\D/', '', $p);
    if (str_starts_with($d, '880')) $d = substr($d, 2);
    return preg_match('/^01[3-9]\d{8}$/', $d) ? $d : null;
}

/** Render a view file with variables. Returns HTML. */
function view(string $name, array $vars = []): string {
    extract($vars, EXTR_SKIP);
    ob_start();
    include APP_DIR . '/views/' . $name . '.php';
    return ob_get_clean();
}

function paginate(int $total, int $per, int $page): array {
    $pages = max(1, (int)ceil($total / $per));
    $page = max(1, min($page, $pages));
    return ['total' => $total, 'per' => $per, 'page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per];
}
function page_links(array $p, array $query = []): string {
    if ($p['pages'] <= 1) return '';
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($p['pages'] > 9 && abs($i - $p['page']) > 2 && $i !== 1 && $i !== $p['pages']) {
            if (abs($i - $p['page']) === 3) $h .= '<span>…</span>';
            continue;
        }
        $qs = http_build_query(array_merge($query, ['page' => $i]));
        $h .= $i === $p['page'] ? "<b>$i</b>" : '<a href="?' . e($qs) . "\">$i</a>";
    }
    return $h . '</nav>';
}

function bd_districts(): array {
    return ['Dhaka','Gazipur','Narayanganj','Narsingdi','Munshiganj','Manikganj','Tangail','Kishoreganj','Faridpur','Madaripur','Shariatpur','Rajbari','Gopalganj',
        'Chattogram','Cox\'s Bazar','Cumilla','Feni','Noakhali','Lakshmipur','Chandpur','Brahmanbaria','Rangamati','Khagrachhari','Bandarban',
        'Rajshahi','Bogura','Pabna','Sirajganj','Natore','Naogaon','Chapainawabganj','Joypurhat',
        'Khulna','Jashore','Satkhira','Bagerhat','Kushtia','Jhenaidah','Magura','Narail','Chuadanga','Meherpur',
        'Barishal','Patuakhali','Bhola','Pirojpur','Barguna','Jhalokati',
        'Sylhet','Moulvibazar','Habiganj','Sunamganj',
        'Rangpur','Dinajpur','Kurigram','Gaibandha','Nilphamari','Lalmonirhat','Thakurgaon','Panchagarh',
        'Mymensingh','Jamalpur','Netrokona','Sherpur'];
}

function rand_token(int $bytes = 16): string { return bin2hex(random_bytes($bytes)); }

function status_label(string $s): string {
    return ['new' => 'New', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'shipped' => 'Shipped',
        'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'returned' => 'Returned', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'][$s] ?? ucfirst($s);
}
function pay_label(string $m): string {
    return ['cod' => 'Cash on Delivery', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'sslcommerz' => 'Card / Mobile Banking (SSLCommerz)'][$m] ?? $m;
}

/** Simple HTML sanitizer for rich text written by admins (keeps formatting, strips scripts/handlers). */
function clean_html(string $html): string {
    $allowed = '<p><br><b><strong><i><em><u><s><h2><h3><h4><ul><ol><li><a><blockquote><img><span><div><hr><table><thead><tbody><tr><th><td>';
    $html = strip_tags($html, $allowed);
    $html = preg_replace('/\s+on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/(href|src)\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\2/i', '$1="#"', $html);
    $html = preg_replace('/\sstyle\s*=\s*("[^"]*expression[^"]*"|\'[^\']*expression[^\']*\')/i', '', $html);
    return $html;
}

function csv_download(string $filename, array $header, iterable $rows): never {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $header);
    foreach ($rows as $r) {
        // Neutralize spreadsheet formula injection
        $r = array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, array_values($r));
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

function not_found(): never {
    http_response_code(404);
    echo store_page('404', ['title' => 'Page not found']);
    exit;
}

function time_ago(string $dt): string {
    $d = time() - strtotime($dt);
    if ($d < 60) return 'just now';
    if ($d < 3600) return floor($d / 60) . ' min ago';
    if ($d < 86400) return floor($d / 3600) . ' h ago';
    return date('d M Y', strtotime($dt));
}

/** Inline Tabler icons (MIT licence, tabler.io). */
function icon(string $name, int $size = 20, string $class = ''): string {
    static $p = [
        'search' => '<path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/>',
        'user' => '<path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/>',
        'heart' => '<path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/>',
        'bag' => '<path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"/><path d="M9 11v-5a3 3 0 0 1 6 0v5"/>',
        'menu' => '<path d="M4 6l16 0"/><path d="M4 12l16 0"/><path d="M4 18l16 0"/>',
        'x' => '<path d="M18 6l-12 12"/><path d="M6 6l12 12"/>',
        'chevron-right' => '<path d="M9 6l6 6l-6 6"/>', 'chevron-left' => '<path d="M15 6l-6 6l6 6"/>', 'chevron-down' => '<path d="M6 9l6 6l6 -6"/>',
        'arrow-right' => '<path d="M5 12l14 0"/><path d="M13 18l6 -6"/><path d="M13 6l6 6"/>',
        'truck' => '<path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M5 17h-2v-11a1 1 0 0 1 1 -1h9v12m-4 0h6m4 0h2v-6h-8m0 -5h5l3 5"/>',
        'cash' => '<path d="M7 9m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"/><path d="M14 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"/>',
        'refresh' => '<path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>',
        'shield' => '<path d="M12 3a12 12 0 0 0 8.5 3a12 12 0 0 1 -8.5 15a12 12 0 0 1 -8.5 -15a12 12 0 0 0 8.5 -3"/><path d="M9 12l2 2l4 -4"/>',
        'headset' => '<path d="M4 14v-3a8 8 0 1 1 16 0v3"/><path d="M18 19c0 1.657 -2.686 3 -6 3"/><path d="M4 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3z"/><path d="M15 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3z"/>',
        'star' => '<path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"/>',
        'star-fill' => '<path fill="currentColor" d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"/>',
        'minus' => '<path d="M5 12l14 0"/>', 'plus' => '<path d="M12 5l0 14"/><path d="M5 12l14 0"/>',
        'trash' => '<path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>',
        'check' => '<path d="M5 12l5 5l10 -10"/>',
        'filter' => '<path d="M4 4h16v2.172a2 2 0 0 1 -.586 1.414l-4.414 4.414v7l-6 2v-8.5l-4.48 -4.928a2 2 0 0 1 -.52 -1.345v-2.227z"/>',
        'phone' => '<path d="M5 4h4l2 5l-2.5 1.5a11 11 0 0 0 5 5l1.5 -2.5l5 2v4a2 2 0 0 1 -2 2a16 16 0 0 1 -15 -15a2 2 0 0 1 2 -2"/>',
        'mail' => '<path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z"/><path d="M3 7l9 6l9 -6"/>',
        'map-pin' => '<path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"/>',
        'brand-facebook' => '<path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3"/>',
        'brand-instagram' => '<path d="M4 8a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M16.5 7.5v.01"/>',
        'brand-tiktok' => '<path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917z"/>',
        'brand-youtube' => '<path d="M2 8a4 4 0 0 1 4 -4h12a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-12a4 4 0 0 1 -4 -4v-8z"/><path d="M10 9l5 3l-5 3z"/>',
        'brand-whatsapp' => '<path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"/><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1"/>',
        'package' => '<path d="M12 3l8 4.5l0 9l-8 4.5l-8 -4.5l0 -9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12l0 9"/><path d="M12 12l-8 -4.5"/>',
        'logout' => '<path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"/><path d="M9 12h12l-3 -3"/><path d="M18 15l3 -3"/>',
        'ruler' => '<path d="M5 4h14a1 1 0 0 1 1 1v5a1 1 0 0 1 -1 1h-7a1 1 0 0 0 -1 1v7a1 1 0 0 1 -1 1h-5a1 1 0 0 1 -1 -1v-14a1 1 0 0 1 1 -1"/><path d="M4 8l2 0"/><path d="M4 12l3 0"/><path d="M4 16l2 0"/><path d="M8 4l0 2"/><path d="M12 4l0 3"/><path d="M16 4l0 2"/>',
        'eye' => '<path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>',
        'tag' => '<path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z"/>',
        'home' => '<path d="M5 12l-2 0l9 -9l9 9l-2 0"/><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"/>',
        'grid' => '<path d="M4 4h6v6h-6z"/><path d="M14 4h6v6h-6z"/><path d="M4 14h6v6h-6z"/><path d="M14 14h6v6h-6z"/>',
        'play' => '<path d="M7 4v16l13 -8z"/>',
    ];
    return '<svg class="ic ' . $class . '" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}
