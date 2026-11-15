<?php
// Rana Shop front controller: every request that isn't a real file comes here (see .htaccess).
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_DIR . '/router.php';
require APP_DIR . '/store.php';
require APP_DIR . '/admin.php';

$path = '/' . trim(substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', strlen(base_path())), '/');
$path = rawurldecode($path);

try {
    // Housekeeping a few times per hour without needing cron (cron is still recommended)
    if (random_int(1, 20) === 1) { release_expired_holds(); process_email_queue(3); }
    dispatch($path);
} catch (Throwable $e) {
    error_log('[rana] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    global $CONFIG;
    if (!empty($CONFIG['debug'])) { echo '<pre>' . e((string)$e) . '</pre>'; exit; }
    echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Error</title><div style="font:16px system-ui;max-width:520px;margin:15vh auto;padding:0 16px;text-align:center"><h1 style="font-weight:500">Something went wrong</h1><p>Please go back and try again. If it keeps happening, contact us.</p><p><a href="' . e(url('/')) . '">Back to home</a></p></div>';
}
