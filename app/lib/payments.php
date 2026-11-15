<?php
declare(strict_types=1);
/*
 * Payment gateways. Each start_* function returns a redirect URL for the customer.
 * A payment is only marked paid after the gateway's own server-side verification API says so.
 */

function payment_methods_enabled(): array {
    $m = [];
    if (setting('pay_cod', 1)) $m['cod'] = 'Cash on Delivery';
    if (setting('pay_bkash') && has_secret('bkash_app_key')) $m['bkash'] = 'bKash';
    if (setting('pay_nagad') && has_secret('nagad_merchant_id')) $m['nagad'] = 'Nagad';
    if (setting('pay_sslcommerz') && has_secret('sslcz_store_id')) $m['sslcommerz'] = 'Card / Mobile Banking (SSLCommerz)';
    return $m;
}

function new_payment(array $o): array {
    $n = (int)val('SELECT COUNT(*) FROM payments WHERE order_id=?', [$o['id']]) + 1;
    $tran = $o['order_no'] . 'P' . $n . strtoupper(substr(rand_token(2), 0, 3));
    $id = insert('payments', ['order_id' => $o['id'], 'method' => $o['payment_method'], 'tran_id' => $tran, 'amount' => $o['total']]);
    return row('SELECT * FROM payments WHERE id=?', [$id]);
}
function payment_log(int $id, string $status, string $ref, $raw): void {
    update('payments', ['status' => $status, 'gateway_ref' => mb_substr($ref, 0, 190), 'raw' => is_string($raw) ? mb_substr($raw, 0, 60000) : json_encode($raw)], 'id=?', [$id]);
}

/** Returns [url|null, error]. */
function start_payment(array $o): array {
    if ($o['payment_status'] === 'paid') return [null, 'This order is already paid.'];
    try {
        return match ($o['payment_method']) {
            'sslcommerz' => sslcz_start($o),
            'bkash' => bkash_start($o),
            'nagad' => nagad_start($o),
            default => [null, 'This order does not need online payment.'],
        };
    } catch (Throwable $e) {
        return [null, 'Payment gateway error: ' . $e->getMessage()];
    }
}

function payment_failed(array $pay, string $reason, $raw = null): void {
    payment_log((int)$pay['id'], 'failed', $reason, $raw ?? $reason);
    $o = row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]);
    if ($o && $o['payment_status'] === 'pending') {
        q("UPDATE orders SET payment_status='failed' WHERE id=?", [$o['id']]);
        insert('order_history', ['order_id' => $o['id'], 'status' => 'payment failed', 'note' => mb_substr($reason, 0, 500), 'admin_name' => 'System']);
    }
}

/* ===================== SSLCommerz ===================== */
function sslcz_base(): string { return setting('sslcz_sandbox', 1) ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com'; }

function sslcz_start(array $o): array {
    $pay = new_payment($o);
    $items = order_items((int)$o['id']);
    $data = [
        'store_id' => secret('sslcz_store_id'), 'store_passwd' => secret('sslcz_store_pass'),
        'total_amount' => number_format((float)$o['total'], 2, '.', ''), 'currency' => 'BDT', 'tran_id' => $pay['tran_id'],
        'success_url' => abs_url('pay/sslcommerz/return?r=success'), 'fail_url' => abs_url('pay/sslcommerz/return?r=fail'),
        'cancel_url' => abs_url('pay/sslcommerz/return?r=cancel'), 'ipn_url' => abs_url('pay/sslcommerz/ipn'),
        'cus_name' => $o['name'], 'cus_email' => $o['email'], 'cus_phone' => $o['phone'], 'cus_add1' => mb_substr($o['address'], 0, 100),
        'cus_city' => $o['district'], 'cus_country' => 'Bangladesh', 'shipping_method' => 'Courier', 'num_of_item' => count($items),
        'ship_name' => $o['name'], 'ship_add1' => mb_substr($o['address'], 0, 100), 'ship_city' => $o['district'], 'ship_postcode' => '1000', 'ship_country' => 'Bangladesh',
        'product_name' => mb_substr(implode(', ', array_column($items, 'name')), 0, 250), 'product_category' => 'Clothing', 'product_profile' => 'physical-goods',
        'value_a' => $o['order_no'],
    ];
    [$code, $body, $err] = http_json('POST', sslcz_base() . '/gwprocess/v4/api.php', $data, [], true);
    if ($err) return [null, 'Could not reach SSLCommerz: ' . $err];
    $j = json_decode($body, true);
    if (($j['status'] ?? '') === 'SUCCESS' && !empty($j['GatewayPageURL'])) { payment_log((int)$pay['id'], 'initiated', $j['sessionkey'] ?? '', $body); return [$j['GatewayPageURL'], '']; }
    payment_failed($pay, 'SSLCommerz init failed: ' . ($j['failedreason'] ?? "HTTP $code"), $body);
    return [null, 'SSLCommerz could not start the payment: ' . ($j['failedreason'] ?? 'unknown error')];
}

/** Verify with SSLCommerz Validation API. Used by both IPN and the browser return. Returns the order or null. */
function sslcz_verify(string $valId, string $tranId): array {
    $pay = row('SELECT * FROM payments WHERE tran_id=?', [$tranId]);
    if (!$pay) return [null, 'Unknown transaction'];
    if ($pay['status'] === 'paid') return [row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]), ''];
    if ($valId === '') { payment_failed($pay, 'No validation id (payment failed or cancelled)'); return [row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]), 'Payment was not completed.']; }
    $url = sslcz_base() . '/validator/api/validationserverAPI.php?' . http_build_query(['val_id' => $valId, 'store_id' => secret('sslcz_store_id'), 'store_passwd' => secret('sslcz_store_pass'), 'format' => 'json']);
    [$code, $body, $err] = http_json('GET', $url);
    if ($err) return [row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]), 'Could not verify payment yet. We will confirm it shortly.'];
    $j = json_decode($body, true) ?: [];
    $valid = in_array($j['status'] ?? '', ['VALID', 'VALIDATED'], true)
        && ($j['tran_id'] ?? '') === $tranId
        && ($j['currency_type'] ?? $j['currency'] ?? 'BDT') === 'BDT'
        && abs((float)($j['currency_amount'] ?? $j['amount'] ?? 0) - (float)$pay['amount']) < 0.01;
    if ($valid) {
        payment_log((int)$pay['id'], 'paid', ($j['bank_tran_id'] ?? '') . ' ' . ($j['card_type'] ?? ''), $body);
        mark_order_paid((int)$pay['order_id'], 'sslcommerz', $j['bank_tran_id'] ?? $valId);
        return [row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]), ''];
    }
    payment_failed($pay, 'SSLCommerz validation: ' . ($j['status'] ?? "HTTP $code"), $body);
    return [row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]), 'Payment could not be verified.'];
}

/* ===================== bKash Tokenized Checkout ===================== */
function bkash_base(): string { return setting('bkash_sandbox', 1) ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta' : 'https://tokenized.pay.bka.sh/v1.2.0-beta'; }
function bkash_token(): string {
    $cache = setting('bkash_token_cache', []);
    if (!empty($cache['token']) && ($cache['exp'] ?? 0) > time() + 60 && ($cache['mode'] ?? '') === bkash_base()) return $cache['token'];
    [$code, $body, $err] = http_json('POST', bkash_base() . '/tokenized/checkout/token/grant',
        ['app_key' => secret('bkash_app_key'), 'app_secret' => secret('bkash_app_secret')],
        ['username: ' . secret('bkash_username'), 'password: ' . secret('bkash_password')]);
    if ($err) throw new RuntimeException('bKash unreachable: ' . $err);
    $j = json_decode($body, true) ?: [];
    if (empty($j['id_token'])) throw new RuntimeException('bKash token failed: ' . ($j['statusMessage'] ?? $j['msg'] ?? "HTTP $code"));
    set_setting('bkash_token_cache', ['token' => $j['id_token'], 'exp' => time() + (int)($j['expires_in'] ?? 3600), 'mode' => bkash_base()]);
    return $j['id_token'];
}
function bkash_call(string $path, array $payload): array {
    [$code, $body, $err] = http_json('POST', bkash_base() . $path, $payload, ['Authorization: ' . bkash_token(), 'X-APP-Key: ' . secret('bkash_app_key')]);
    if ($err) throw new RuntimeException('bKash unreachable: ' . $err);
    return [json_decode($body, true) ?: [], $body];
}
function bkash_start(array $o): array {
    $pay = new_payment($o);
    [$j, $body] = bkash_call('/tokenized/checkout/create', [
        'mode' => '0011', 'payerReference' => $o['phone'], 'callbackURL' => abs_url('pay/bkash/callback'),
        'amount' => number_format((float)$o['total'], 2, '.', ''), 'currency' => 'BDT', 'intent' => 'sale', 'merchantInvoiceNumber' => $pay['tran_id'],
    ]);
    if (!empty($j['bkashURL']) && !empty($j['paymentID'])) { payment_log((int)$pay['id'], 'initiated', $j['paymentID'], $body); return [$j['bkashURL'], '']; }
    payment_failed($pay, 'bKash create failed: ' . ($j['statusMessage'] ?? 'unknown'), $body);
    return [null, 'bKash could not start the payment: ' . ($j['statusMessage'] ?? 'unknown error')];
}
function bkash_callback(string $paymentId, string $status): array {
    $pay = row("SELECT * FROM payments WHERE method='bkash' AND gateway_ref=?", [$paymentId]);
    if (!$pay) return [null, 'Unknown bKash payment'];
    $o = fn() => row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]);
    if ($pay['status'] === 'paid') return [$o(), ''];
    if ($status !== 'success') { payment_failed($pay, 'bKash: customer ' . $status); return [$o(), $status === 'cancel' ? 'Payment cancelled.' : 'Payment failed.']; }
    try {
        [$j, $body] = bkash_call('/tokenized/checkout/execute', ['paymentID' => $paymentId]);
        if (empty($j['transactionStatus'])) [$j, $body] = bkash_call('/tokenized/checkout/payment/status', ['paymentID' => $paymentId]); // execute timed out / already executed
    } catch (Throwable $e) { return [$o(), 'Could not verify payment yet: ' . $e->getMessage()]; }
    $ok = ($j['transactionStatus'] ?? '') === 'Completed' && ($j['merchantInvoiceNumber'] ?? '') === $pay['tran_id'] && abs((float)($j['amount'] ?? 0) - (float)$pay['amount']) < 0.01;
    if ($ok) {
        update('payments', ['status' => 'paid', 'raw' => $body], 'id=?', [$pay['id']]);
        mark_order_paid((int)$pay['order_id'], 'bkash', 'TrxID ' . ($j['trxID'] ?? ''));
        return [$o(), ''];
    }
    payment_failed($pay, 'bKash: ' . ($j['statusMessage'] ?? $j['transactionStatus'] ?? 'not completed'), $body);
    return [$o(), 'Payment failed: ' . ($j['statusMessage'] ?? 'not completed')];
}

/* ===================== Nagad (Online Payment Gateway API v0.2.0) ===================== */
function nagad_base(): string { return setting('nagad_sandbox', 1) ? 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs' : 'https://api.mynagad.com/api/dfs'; }
function nagad_pem(string $key, string $type): string {
    $key = trim($key);
    if (str_contains($key, '-----BEGIN')) return $key;
    return "-----BEGIN $type-----\n" . chunk_split(preg_replace('/\s+/', '', $key), 64, "\n") . "-----END $type-----\n";
}
function nagad_encrypt(string $data): string {
    $pub = openssl_pkey_get_public(nagad_pem(secret('nagad_pg_public_key'), 'PUBLIC KEY'));
    if (!$pub || !openssl_public_encrypt($data, $out, $pub, OPENSSL_PKCS1_PADDING)) throw new RuntimeException('Nagad public key is invalid');
    return base64_encode($out);
}
function nagad_sign(string $data): string {
    $priv = openssl_pkey_get_private(nagad_pem(secret('nagad_merchant_private_key'), 'PRIVATE KEY'));
    if (!$priv || !openssl_sign($data, $sig, $priv, OPENSSL_ALGO_SHA256)) throw new RuntimeException('Merchant private key is invalid');
    return base64_encode($sig);
}
function nagad_decrypt(string $b64): string {
    $priv = openssl_pkey_get_private(nagad_pem(secret('nagad_merchant_private_key'), 'PRIVATE KEY'));
    if (!$priv || !openssl_private_decrypt(base64_decode($b64), $out, $priv, OPENSSL_PKCS1_PADDING)) throw new RuntimeException('Could not decrypt Nagad response');
    return $out;
}
function nagad_headers(): array { return ['X-KM-Api-Version: v-0.2.0', 'X-KM-IP-V4: ' . (client_ip() ?: '127.0.0.1'), 'X-KM-Client-Type: PC_WEB']; }
function nagad_start(array $o): array {
    $pay = new_payment($o);
    $mid = secret('nagad_merchant_id');
    $orderId = preg_replace('/[^A-Za-z0-9]/', '', $pay['tran_id']);
    $dt = date('YmdHis');
    $sens = json_encode(['merchantId' => $mid, 'datetime' => $dt, 'orderId' => $orderId, 'challenge' => rand_token(20)]);
    [$code, $body, $err] = http_json('POST', nagad_base() . "/check-out/initialize/$mid/$orderId",
        ['accountNumber' => secret('nagad_merchant_number'), 'dateTime' => $dt, 'sensitiveData' => nagad_encrypt($sens), 'signature' => nagad_sign($sens)], nagad_headers());
    if ($err) return [null, 'Could not reach Nagad: ' . $err];
    $j = json_decode($body, true) ?: [];
    if (empty($j['sensitiveData'])) { payment_failed($pay, 'Nagad init: ' . ($j['message'] ?? "HTTP $code"), $body); return [null, 'Nagad could not start the payment: ' . ($j['message'] ?? 'unknown error')]; }
    $plain = json_decode(nagad_decrypt($j['sensitiveData']), true) ?: [];
    $ref = $plain['paymentReferenceId'] ?? ''; $challenge = $plain['challenge'] ?? '';
    $sens2 = json_encode(['merchantId' => $mid, 'orderId' => $orderId, 'currencyCode' => '050', 'amount' => number_format((float)$o['total'], 2, '.', ''), 'challenge' => $challenge]);
    [$code, $body2, $err] = http_json('POST', nagad_base() . "/check-out/complete/$ref",
        ['sensitiveData' => nagad_encrypt($sens2), 'signature' => nagad_sign($sens2), 'merchantCallbackURL' => abs_url('pay/nagad/callback'), 'additionalMerchantInfo' => ['order' => $o['order_no']]], nagad_headers());
    $j2 = json_decode($body2, true) ?: [];
    if (($j2['status'] ?? '') === 'Success' && !empty($j2['callBackUrl'])) { payment_log((int)$pay['id'], 'initiated', $ref, $body2); update('payments', ['tran_id' => $orderId], 'id=?', [$pay['id']]); return [$j2['callBackUrl'], '']; }
    payment_failed($pay, 'Nagad complete: ' . ($j2['message'] ?? "HTTP $code"), $body2);
    return [null, 'Nagad could not start the payment: ' . ($j2['message'] ?? 'unknown error')];
}
function nagad_callback(string $paymentRef): array {
    $pay = row("SELECT * FROM payments WHERE method='nagad' AND gateway_ref=?", [$paymentRef]);
    if (!$pay) return [null, 'Unknown Nagad payment'];
    $o = fn() => row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]);
    if ($pay['status'] === 'paid') return [$o(), ''];
    [$code, $body, $err] = http_json('GET', nagad_base() . '/verify/payment/' . rawurlencode($paymentRef), null, nagad_headers());
    if ($err) return [$o(), 'Could not verify payment yet. We will confirm it shortly.'];
    $j = json_decode($body, true) ?: [];
    $ok = ($j['status'] ?? '') === 'Success' && ($j['orderId'] ?? '') === $pay['tran_id'] && abs((float)($j['amount'] ?? 0) - (float)$pay['amount']) < 0.01;
    if ($ok) {
        update('payments', ['status' => 'paid', 'raw' => $body], 'id=?', [$pay['id']]);
        mark_order_paid((int)$pay['order_id'], 'nagad', 'Ref ' . ($j['issuerPaymentRefNo'] ?? $paymentRef));
        return [$o(), ''];
    }
    payment_failed($pay, 'Nagad: ' . ($j['status'] ?? "HTTP $code"), $body);
    return [$o(), 'Payment was not completed.'];
}
