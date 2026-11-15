<?php
// Rana Shop one-time installer. After a successful install it refuses to run again.
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
require APP_DIR . '/seed.php';

function do_install(array $in): array {
    $errors = [];
    foreach (['db_host', 'db_name', 'db_user', 'admin_name', 'admin_email', 'admin_pass', 'store_name'] as $k) if (trim((string)($in[$k] ?? '')) === '') $errors[] = "Please fill in: $k";
    if (!filter_var($in['admin_email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Admin email is not valid.';
    if (strlen((string)($in['admin_pass'] ?? '')) < 10) $errors[] = 'Admin password must be at least 10 characters.';
    if ($errors) return $errors;
    global $CONFIG;
    $CONFIG = [
        'db' => ['host' => trim($in['db_host']), 'port' => (int)(($in['db_port'] ?? '') ?: 3306), 'name' => trim($in['db_name']), 'user' => trim($in['db_user']), 'pass' => (string)$in['db_pass']],
        'site_url' => rtrim(trim((string)($in['site_url'] ?? '')), '/'),
        'base_path' => rtrim((string)($in['base_path'] ?? ''), '/'),
        'timezone' => 'Asia/Dhaka', 'debug' => false, 'cron_key' => bin2hex(random_bytes(16)),
    ];
    try { db(); } catch (Throwable $e) { return ['Could not connect to the database: ' . $e->getMessage()]; }
    if ((int)val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='admins'") > 0) return ['This database already has a Rana Shop installed. Use an empty database.'];
    try {
        migrate();
        set_setting('store_name', trim($in['store_name']));
        set_setting('mail_from_name', trim($in['store_name']));
        set_setting('contact_email', trim($in['admin_email']));
        set_setting('notify_email', trim($in['admin_email']));
        if (!empty($in['seed'])) seed_all();
        else { foreach (default_email_templates() as $k => [$s, $b]) insert('email_templates', ['tkey' => $k, 'name' => email_template_keys()[$k][0], 'subject' => $s, 'body' => $b]); set_setting('theme', theme_defaults()); }
        insert('admins', ['name' => trim($in['admin_name']), 'email' => mb_strtolower(trim($in['admin_email'])), 'pass_hash' => password_hash((string)$in['admin_pass'], PASSWORD_DEFAULT), 'role' => 'super_admin']);
    } catch (Throwable $e) { return ['Install failed: ' . $e->getMessage()]; }
    $php = "<?php\n// Rana Shop configuration. Keep this file private.\nreturn " . var_export($CONFIG, true) . ";\n";
    if (file_put_contents(APP_DIR . '/config.php', $php) === false) return ['Could not write app/config.php. Make the app folder writable and try again.'];
    @chmod(APP_DIR . '/config.php', 0600);
    if (!is_file(APP_DIR . '/secrets.php')) save_secrets([]);
    return [];
}

if (PHP_SAPI === 'cli') {
    // php install.php db_host=... db_name=... (used by automated tests)
    parse_str(implode('&', array_slice($argv, 1)), $in);
    if (is_file(APP_DIR . '/config.php')) { fwrite(STDERR, "Already installed\n"); exit(1); }
    $err = do_install($in);
    echo $err ? implode("\n", $err) . "\n" : "Installed\n";
    exit($err ? 1 : 0);
}

if (is_file(APP_DIR . '/config.php')) { http_response_code(403); exit('Rana Shop is already installed. Delete install.php from the server.'); }
session_start();
$errors = []; $done = false;
$guessBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$guessUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $guessBase;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['inst_csrf'] ?? '', $_POST['_csrf'] ?? '')) $errors[] = 'Session expired, please submit again.';
    else { $errors = do_install($_POST + ['base_path' => $guessBase]); $done = !$errors; }
}
$_SESSION['inst_csrf'] = $_SESSION['inst_csrf'] ?? bin2hex(random_bytes(16));
$v = fn($k, $d = '') => htmlspecialchars((string)($_POST[$k] ?? $d), ENT_QUOTES);
$checks = ['PHP 8.1 or newer' => version_compare(PHP_VERSION, '8.1', '>='), 'PDO MySQL' => extension_loaded('pdo_mysql'), 'GD (images)' => extension_loaded('gd'),
    'cURL (payments, email)' => extension_loaded('curl'), 'OpenSSL' => extension_loaded('openssl'), 'mbstring' => extension_loaded('mbstring'),
    'app/ folder writable' => is_writable(APP_DIR), 'uploads/ folder writable' => is_writable(__DIR__ . '/uploads')];
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install Rana Shop</title>
<style>body{font:15px/1.5 system-ui,sans-serif;background:#f5f1ea;color:#111;margin:0;padding:30px 16px}.box{max-width:620px;margin:auto;background:#fff;padding:28px;border:1px solid #e5ded2}h1{font:500 28px Georgia,serif;margin:0 0 4px}label{display:block;margin:12px 0 4px;font-weight:600}input{width:100%;box-sizing:border-box;padding:10px;border:1px solid #ccc;font:inherit}.row{display:grid;grid-template-columns:2fr 1fr;gap:10px}button{margin-top:20px;background:#111;color:#fff;border:0;padding:13px 22px;font:inherit;cursor:pointer;width:100%}.err{background:#fde8e6;color:#8a1c12;padding:10px 14px;margin:12px 0}.ok{background:#e7f4ea;color:#14532d;padding:14px}fieldset{border:1px solid #eee;margin:18px 0 0;padding:4px 16px 16px}legend{font-weight:700;padding:0 6px}ul.c{list-style:none;padding:0;columns:2}ul.c li:before{content:attr(data-s);margin-right:6px}small{color:#666}</style></head><body><div class="box">
<h1>Install Rana Shop</h1><p><small>One-time setup. Takes about a minute.</small></p>
<?php if ($done): ?>
  <div class="ok"><b>Installed!</b><br>Your store is ready. For security, <b>delete install.php</b> from File Manager now.</div>
  <p><a href="<?= htmlspecialchars($guessBase) ?>/admin">Go to Admin →</a> &nbsp; <a href="<?= htmlspecialchars($guessBase) ?>/">View store →</a></p>
<?php else: ?>
  <ul class="c"><?php foreach ($checks as $k => $ok): ?><li data-s="<?= $ok ? '✅' : '❌' ?>"><?= $k ?></li><?php endforeach ?></ul>
  <?php foreach ($errors as $e): ?><div class="err"><?= htmlspecialchars($e) ?></div><?php endforeach ?>
  <form method="post"><input type="hidden" name="_csrf" value="<?= $_SESSION['inst_csrf'] ?>">
    <fieldset><legend>1. Database (from cPanel → MySQL Databases)</legend>
      <div class="row"><div><label>Database host</label><input name="db_host" value="<?= $v('db_host', 'localhost') ?>"></div><div><label>Port</label><input name="db_port" value="<?= $v('db_port', '3306') ?>"></div></div>
      <label>Database name</label><input name="db_name" value="<?= $v('db_name') ?>" required>
      <label>Database user</label><input name="db_user" value="<?= $v('db_user') ?>" required>
      <label>Database password</label><input name="db_pass" type="password" value="<?= $v('db_pass') ?>">
    </fieldset>
    <fieldset><legend>2. Store</legend>
      <label>Store name</label><input name="store_name" value="<?= $v('store_name', 'Rana Shop') ?>" required>
      <label>Website address</label><input name="site_url" value="<?= $v('site_url', $guessUrl) ?>">
      <label><input type="checkbox" name="seed" value="1" checked style="width:auto"> Add sample products, pages and homepage (recommended; edit or delete later)</label>
    </fieldset>
    <fieldset><legend>3. Super Admin account</legend>
      <label>Your name</label><input name="admin_name" value="<?= $v('admin_name') ?>" required>
      <label>Email (used to log in and to receive order emails)</label><input name="admin_email" type="email" value="<?= $v('admin_email') ?>" required>
      <label>Password (at least 10 characters)</label><input name="admin_pass" type="password" minlength="10" required>
    </fieldset>
    <button>Install</button>
  </form>
<?php endif ?>
</div></body></html>
