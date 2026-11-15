<?php
// Rana Shop bootstrap: loaded by every entry point.
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));
define('APP_VERSION', '1.0.0');

$cfgFile = APP_DIR . '/config.php';
if (!is_file($cfgFile)) {
    if (PHP_SAPI !== 'cli' && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'install.php') {
        header('Location: ' . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\') . '/install.php');
        exit;
    }
    $CONFIG = [];
} else {
    $CONFIG = require $cfgFile;
}

date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Dhaka');
ini_set('display_errors', !empty($CONFIG['debug']) ? '1' : '0');
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

foreach (['db', 'helpers', 'settings', 'security', 'auth', 'upload', 'catalog', 'cart', 'coupons', 'orders', 'mailer', 'payments', 'courier', 'seo', 'theme'] as $lib) {
    require_once APP_DIR . "/lib/$lib.php";
}

if (PHP_SAPI !== 'cli' && $CONFIG) {
    start_session();
    send_security_headers();
}
