<?php
declare(strict_types=1);

/** Template keys and the variables each one can use (shown in the admin editor). */
function email_template_keys(): array {
    $order = ['customer_name', 'order_no', 'order_date', 'items_table', 'subtotal', 'discount', 'shipping', 'order_total', 'payment_method', 'payment_status', 'delivery_address', 'customer_phone', 'customer_email', 'tracking_link', 'courier', 'tracking_no', 'store_name', 'support_email', 'support_phone'];
    return [
        'order_received' => ['Order Received', $order],
        'order_confirmed' => ['Order Confirmed', $order],
        'order_processing' => ['Order Processing', $order],
        'order_shipped' => ['Order Shipped', $order],
        'order_delivered' => ['Order Delivered', $order],
        'order_cancelled' => ['Order Cancelled', $order],
        'refund_update' => ['Refund Update', $order],
        'password_reset' => ['Password Reset', ['customer_name', 'reset_link', 'store_name', 'support_email', 'support_phone']],
        'account_registration' => ['Account Registration', ['customer_name', 'account_link', 'store_name', 'support_email', 'support_phone']],
        'admin_new_order' => ['Admin: New Order', array_merge($order, ['admin_order_link'])],
        'contact_message' => ['Admin: Contact Message', ['name', 'email', 'phone', 'subject', 'message', 'store_name']],
    ];
}

function render_vars(string $tpl, array $vars, bool $html): string {
    return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function ($m) use ($vars, $html) {
        if (!array_key_exists($m[1], $vars)) return '';
        $raw = in_array($m[1], ['items_table'], true);
        return ($html && !$raw) ? e($vars[$m[1]]) : (string)$vars[$m[1]];
    }, $tpl);
}

function email_layout(string $inner, string $preheader = ''): string {
    $name = e(setting('store_name'));
    $logo = setting('logo') ? '<img src="' . e(abs_url('uploads/' . setting('logo'))) . '" alt="' . $name . '" height="40" style="height:40px;max-width:200px;display:block;margin:0 auto">' : '<div style="font:700 24px/1 Georgia,serif;letter-spacing:4px;color:#111">' . mb_strtoupper($name) . '</div>';
    $support = [];
    if ($e = setting('contact_email')) $support[] = e($e);
    if ($p = setting('contact_phone')) $support[] = e($p);
    $site = e(abs_url(''));
    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>' . $name . '</title></head>
<body style="margin:0;background:#f4f1ec;font-family:Helvetica,Arial,sans-serif;color:#1a1a1a">
<span style="display:none;max-height:0;overflow:hidden">' . e($preheader) . '</span>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1ec;padding:24px 12px"><tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff">
<tr><td style="padding:28px 24px;border-bottom:1px solid #eee;text-align:center"><a href="' . $site . '" style="text-decoration:none;color:#111">' . $logo . '</a></td></tr>
<tr><td style="padding:28px 28px 8px;font-size:15px;line-height:1.6">' . $inner . '</td></tr>
<tr><td style="padding:20px 28px 28px;font-size:12px;color:#777;line-height:1.6;border-top:1px solid #eee;text-align:center">
Need help? ' . implode(' · ', $support) . '<br><a href="' . $site . '" style="color:#777">' . $name . '</a></td></tr>
</table></td></tr></table></body></html>';
}

function items_table_html(array $items): string {
    $h = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:8px 0 16px">';
    foreach ($items as $it) {
        $img = $it['image'] ? '<img src="' . e(abs_url('uploads/' . $it['image'])) . '" width="64" height="80" style="width:64px;height:80px;object-fit:cover;display:block;background:#f1ede6">' : '';
        $opt = trim(($it['color'] ? 'Color: ' . $it['color'] : '') . ($it['size'] ? ' · Size: ' . $it['size'] : ''), ' ·');
        $h .= '<tr><td style="padding:10px 0;border-bottom:1px solid #eee;width:72px;vertical-align:top">' . $img . '</td>
<td style="padding:10px 8px;border-bottom:1px solid #eee;vertical-align:top;font-size:14px"><b>' . e($it['name']) . '</b><br><span style="color:#777">' . e($opt) . '</span><br><span style="color:#777">Qty: ' . (int)$it['qty'] . ' × ' . e(money($it['price'])) . '</span></td>
<td style="padding:10px 0;border-bottom:1px solid #eee;text-align:right;vertical-align:top;font-size:14px;white-space:nowrap">' . e(money($it['line_total'])) . '</td></tr>';
    }
    return $h . '</table>';
}

function order_email_vars(array $o): array {
    return [
        'customer_name' => $o['name'], 'order_no' => $o['order_no'], 'order_date' => date('d M Y, h:i A', strtotime($o['created_at'])),
        'items_table' => items_table_html(order_items((int)$o['id'])),
        'subtotal' => money($o['subtotal']), 'discount' => money($o['discount']), 'shipping' => money($o['shipping']), 'order_total' => money($o['total']),
        'payment_method' => pay_label($o['payment_method']), 'payment_status' => status_label($o['payment_status']),
        'delivery_address' => $o['address'] . ', ' . $o['area'] . ', ' . $o['district'], 'customer_phone' => $o['phone'], 'customer_email' => $o['email'],
        'tracking_link' => abs_url(order_view_url($o)), 'courier' => $o['courier'] ?: '—', 'tracking_no' => $o['tracking_no'] ?: '—',
        'store_name' => setting('store_name'), 'support_email' => setting('contact_email'), 'support_phone' => setting('contact_phone'),
        'admin_order_link' => abs_url('admin/orders/' . $o['id']),
    ];
}

/** Build subject+html from a DB template. Returns null if the template is disabled/missing. */
function build_template_email(string $key, array $vars): ?array {
    $t = row('SELECT * FROM email_templates WHERE tkey=?', [$key]);
    if (!$t || !$t['enabled']) return null;
    $subject = render_vars($t['subject'], $vars, false);
    $html = email_layout(render_vars($t['body'], $vars, true), $subject);
    return ['subject' => $subject, 'html' => $html];
}

/** Queue an email. The idempotency key makes sure the same notification is never queued twice. */
function queue_email(string $to, string $name, string $subject, string $html, string $idem, string $tkey = '', ?int $orderId = null): bool {
    if (!valid_email($to)) return false;
    $st = q('INSERT IGNORE INTO email_queue (idem_key, to_email, to_name, subject, html, template_key, order_id) VALUES (?,?,?,?,?,?,?)',
        [mb_substr($idem, 0, 120), $to, mb_substr($name, 0, 120), mb_substr($subject, 0, 250), $html, $tkey, $orderId]);
    return $st->rowCount() > 0;
}
function queue_template_email(string $key, string $to, string $name, array $vars, string $idem, ?int $orderId = null): bool {
    $m = build_template_email($key, $vars);
    if (!$m) return false;
    return queue_email($to, $name, $m['subject'], $m['html'], $idem, $key, $orderId);
}
function queue_order_email(string $key, array $o): bool {
    if (!setting('customer_emails_enabled', 1) || !$o['email']) return false;
    // Status emails can repeat if an order goes back and forth; include a counter so each real change sends once.
    $n = (int)val("SELECT COUNT(*) FROM email_queue WHERE order_id=? AND template_key=?", [$o['id'], $key]);
    $idem = $key . ':' . $o['id'] . ($key === 'order_received' ? '' : ':' . $n . ':' . $o['status'] . ':' . $o['payment_status']);
    return queue_template_email($key, $o['email'], $o['name'], order_email_vars($o), $idem, (int)$o['id']);
}

/** Send up to $limit queued emails now. Failed ones retry up to 3 attempts total, spaced out. */
function process_email_queue(int $limit = 10): array {
    $done = ['sent' => 0, 'failed' => 0];
    $list = rows("SELECT id FROM email_queue WHERE status='queued' OR (status='failed' AND attempts<3 AND created_at < NOW() - INTERVAL attempts*5 MINUTE) ORDER BY id LIMIT " . (int)$limit);
    foreach ($list as $r) {
        // Claim the row so two processes never send the same email
        if (q("UPDATE email_queue SET status='sending', attempts=attempts+1 WHERE id=? AND status IN ('queued','failed')", [$r['id']])->rowCount() !== 1) continue;
        $m = row('SELECT * FROM email_queue WHERE id=?', [$r['id']]);
        [$ok, $info] = send_mail_now($m['to_email'], $m['to_name'], $m['subject'], $m['html']);
        if ($ok) { update('email_queue', ['status' => 'sent', 'provider_id' => mb_substr($info, 0, 190), 'last_error' => '', 'sent_at' => now()], 'id=?', [$m['id']]); $done['sent']++; }
        else { update('email_queue', ['status' => 'failed', 'last_error' => mb_substr($info, 0, 1000)], 'id=?', [$m['id']]); $done['failed']++; }
    }
    // Rows stuck in 'sending' (process died) go back to failed so they can be retried
    q("UPDATE email_queue SET status='failed', last_error='Interrupted while sending' WHERE status='sending' AND created_at < NOW() - INTERVAL 30 MINUTE");
    return $done;
}

/** Returns [ok, provider id or error]. Never reports success unless the provider accepted it. */
function send_mail_now(string $to, string $name, string $subject, string $html): array {
    $fromEmail = setting('mail_from_email') ?: setting('contact_email');
    $fromName = setting('mail_from_name') ?: setting('store_name');
    if (!$fromEmail) return [false, 'Sender email is not set (Admin → Email → Settings).'];
    $text = trim(html_entity_decode(strip_tags(preg_replace('~<(br|/p|/tr|/h\d)[^>]*>~i', "\n", $html)), ENT_QUOTES, 'UTF-8'));
    $text = preg_replace("/\n\s*\n+/", "\n\n", $text);
    return match (setting('mail_driver', 'resend')) {
        'smtp' => smtp_send($to, $name, $subject, $html, $text, $fromEmail, $fromName),
        'mail' => php_mail_send($to, $name, $subject, $html, $fromEmail, $fromName),
        default => resend_send($to, $name, $subject, $html, $text, $fromEmail, $fromName),
    };
}

function resend_send(string $to, string $name, string $subject, string $html, string $text, string $from, string $fromName): array {
    $key = secret('resend_api_key');
    if (!$key) return [false, 'Resend API key is missing (Admin → Integrations).'];
    $payload = ['from' => "$fromName <$from>", 'to' => [$name ? "$name <$to>" : $to], 'subject' => $subject, 'html' => $html, 'text' => $text];
    if ($rt = setting('contact_email')) $payload['reply_to'] = $rt;
    [$code, $body, $err] = http_json('POST', 'https://api.resend.com/emails', $payload, ['Authorization: Bearer ' . $key]);
    if ($err) return [false, 'Network error: ' . $err];
    $j = json_decode($body, true);
    if ($code >= 200 && $code < 300 && !empty($j['id'])) return [true, $j['id']];
    return [false, 'Resend HTTP ' . $code . ': ' . ($j['message'] ?? mb_substr($body, 0, 300))];
}

function php_mail_send(string $to, string $name, string $subject, string $html, string $from, string $fromName): array {
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\nReply-To: $from\r\n";
    $ok = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $html, $headers, '-f' . $from);
    return $ok ? [true, 'php-mail'] : [false, 'The server mail() function refused the message.'];
}

/** Minimal SMTP client (STARTTLS/SSL + AUTH LOGIN). */
function smtp_send(string $to, string $name, string $subject, string $html, string $text, string $from, string $fromName): array {
    $c = (array)setting('smtp', []);
    $host = $c['host'] ?? ''; $port = (int)($c['port'] ?? 587); $user = $c['user'] ?? ''; $pass = secret('smtp_password'); $secure = $c['secure'] ?? 'tls';
    if (!$host) return [false, 'SMTP host is not set.'];
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]));
    if (!$fp) return [false, "SMTP connect failed: $errstr"];
    stream_set_timeout($fp, 20);
    $read = function () use ($fp) { $d = ''; while (($l = fgets($fp, 515)) !== false) { $d .= $l; if (isset($l[3]) && $l[3] === ' ') break; } return $d; };
    $cmd = function (string $c, array $okCodes) use ($fp, $read) { if ($c !== '') fwrite($fp, $c . "\r\n"); $r = $read(); return [in_array((int)substr($r, 0, 3), $okCodes, true), $r]; };
    try {
        [$ok, $r] = $cmd('', [220]); if (!$ok) throw new RuntimeException($r);
        $ehlo = 'EHLO ' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        [$ok, $r] = $cmd($ehlo, [250]); if (!$ok) throw new RuntimeException($r);
        if ($secure === 'tls') {
            [$ok, $r] = $cmd('STARTTLS', [220]); if (!$ok) throw new RuntimeException($r);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) throw new RuntimeException('TLS failed');
            [$ok, $r] = $cmd($ehlo, [250]); if (!$ok) throw new RuntimeException($r);
        }
        if ($user !== '') {
            [$ok, $r] = $cmd('AUTH LOGIN', [334]); if (!$ok) throw new RuntimeException($r);
            [$ok, $r] = $cmd(base64_encode($user), [334]); if (!$ok) throw new RuntimeException($r);
            [$ok, $r] = $cmd(base64_encode($pass), [235]); if (!$ok) throw new RuntimeException('Login failed: ' . $r);
        }
        [$ok, $r] = $cmd("MAIL FROM:<$from>", [250]); if (!$ok) throw new RuntimeException($r);
        [$ok, $r] = $cmd("RCPT TO:<$to>", [250, 251]); if (!$ok) throw new RuntimeException($r);
        [$ok, $r] = $cmd('DATA', [354]); if (!$ok) throw new RuntimeException($r);
        $b = 'b' . rand_token(8); $mid = '<' . rand_token(12) . '@' . (explode('@', $from)[1] ?? 'localhost') . '>';
        $msg = "Date: " . date('r') . "\r\nMessage-ID: $mid\r\nFrom: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\nTo: " . ($name ? '=?UTF-8?B?' . base64_encode($name) . '?= ' : '') . "<$to>\r\nSubject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\nMIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"$b\"\r\n\r\n"
            . "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text)) . "\r\n"
            . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n--$b--\r\n";
        [$ok, $r] = $cmd($msg . "\r\n.", [250]); if (!$ok) throw new RuntimeException($r);
        $cmd('QUIT', [221]);
        fclose($fp);
        return [true, 'smtp ' . $mid];
    } catch (Throwable $e) { @fclose($fp); return [false, 'SMTP: ' . trim($e->getMessage())]; }
}

/** Small cURL helper used by email, payment and courier APIs. Returns [httpCode, body, error]. */
function http_json(string $method, string $url, $payload = null, array $headers = [], bool $form = false, int $timeout = 30): array {
    $ch = curl_init($url);
    $h = array_merge(['Accept: application/json'], $headers);
    if ($payload !== null) {
        if ($form) { curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($payload) ? http_build_query($payload) : $payload); }
        else { curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)); $h[] = 'Content-Type: application/json'; }
    }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $h, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true]);
    $body = curl_exec($ch);
    $err = $body === false ? curl_error($ch) : '';
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, (string)$body, $err];
}
