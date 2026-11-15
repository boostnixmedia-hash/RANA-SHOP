<?php
declare(strict_types=1);

function cod_amount(array $o): float { return $o['payment_status'] === 'paid' ? 0.0 : (float)$o['total']; }

/* ---------- Steadfast (Packzy) ---------- */
function steadfast_call(string $method, string $path, ?array $payload = null): array {
    [$code, $body, $err] = http_json($method, 'https://portal.packzy.com/api/v1' . $path, $payload, ['Api-Key: ' . secret('steadfast_api_key'), 'Secret-Key: ' . secret('steadfast_secret_key')]);
    if ($err) throw new RuntimeException('Steadfast unreachable: ' . $err);
    return [$code, json_decode($body, true) ?: [], $body];
}
/** Returns error string or '' on success. */
function steadfast_create(array $o): string {
    if (!has_secret('steadfast_api_key')) return 'Steadfast API key is not set (Admin → Integrations).';
    [$code, $j] = steadfast_call('POST', '/create_order', [
        'invoice' => $o['order_no'], 'recipient_name' => $o['name'], 'recipient_phone' => $o['phone'],
        'recipient_address' => mb_substr($o['address'] . ', ' . $o['area'] . ', ' . $o['district'], 0, 250),
        'cod_amount' => cod_amount($o), 'note' => mb_substr($o['note'], 0, 200),
    ]);
    $c = $j['consignment'] ?? null;
    if ($code === 200 && $c && !empty($c['consignment_id'])) {
        update('orders', ['courier' => 'Steadfast', 'tracking_no' => (string)($c['tracking_code'] ?? ''), 'courier_ref' => (string)$c['consignment_id']], 'id=?', [$o['id']]);
        insert('order_history', ['order_id' => $o['id'], 'status' => 'courier', 'note' => 'Sent to Steadfast · ' . ($c['tracking_code'] ?? ''), 'admin_name' => admin()['name'] ?? 'System']);
        return '';
    }
    return 'Steadfast: ' . ($j['message'] ?? json_encode($j['errors'] ?? $j));
}
function steadfast_status(array $o): string {
    [, $j] = steadfast_call('GET', '/status_by_cid/' . rawurlencode($o['courier_ref']));
    return (string)($j['delivery_status'] ?? 'unknown');
}

/* ---------- Pathao ---------- */
function pathao_base(): string { return setting('pathao_sandbox', 1) ? 'https://courier-api-sandbox.pathao.com' : 'https://api-hermes.pathao.com'; }
function pathao_token(): string {
    $c = setting('pathao_token_cache', []);
    if (!empty($c['token']) && ($c['exp'] ?? 0) > time() + 60 && ($c['mode'] ?? '') === pathao_base()) return $c['token'];
    [$code, $body, $err] = http_json('POST', pathao_base() . '/aladdin/api/v1/issue-token', [
        'client_id' => secret('pathao_client_id'), 'client_secret' => secret('pathao_client_secret'),
        'grant_type' => 'password', 'username' => secret('pathao_username'), 'password' => secret('pathao_password'),
    ]);
    if ($err) throw new RuntimeException('Pathao unreachable: ' . $err);
    $j = json_decode($body, true) ?: [];
    if (empty($j['access_token'])) throw new RuntimeException('Pathao login failed: ' . ($j['message'] ?? "HTTP $code"));
    set_setting('pathao_token_cache', ['token' => $j['access_token'], 'exp' => time() + (int)($j['expires_in'] ?? 3600), 'mode' => pathao_base()]);
    return $j['access_token'];
}
function pathao_create(array $o, ?int $cityId = null, ?int $zoneId = null, float $weight = 0.5): string {
    if (!has_secret('pathao_client_id')) return 'Pathao credentials are not set (Admin → Integrations).';
    $items = order_items((int)$o['id']);
    $payload = [
        'store_id' => (int)setting('pathao_store_id'), 'merchant_order_id' => $o['order_no'], 'recipient_name' => $o['name'], 'recipient_phone' => $o['phone'],
        'recipient_address' => mb_substr($o['address'] . ', ' . $o['area'] . ', ' . $o['district'], 0, 220),
        'delivery_type' => 48, 'item_type' => 2, 'item_quantity' => array_sum(array_column($items, 'qty')), 'item_weight' => $weight,
        'amount_to_collect' => (int)round(cod_amount($o)), 'item_description' => mb_substr(implode(', ', array_column($items, 'name')), 0, 200), 'special_instruction' => mb_substr($o['note'], 0, 200),
    ];
    if ($cityId) $payload['recipient_city'] = $cityId;
    if ($zoneId) $payload['recipient_zone'] = $zoneId;
    [$code, $body, $err] = http_json('POST', pathao_base() . '/aladdin/api/v1/orders', $payload, ['Authorization: Bearer ' . pathao_token()]);
    if ($err) return 'Pathao unreachable: ' . $err;
    $j = json_decode($body, true) ?: [];
    if (($code === 200 || $code === 201) && !empty($j['data']['consignment_id'])) {
        update('orders', ['courier' => 'Pathao', 'tracking_no' => (string)$j['data']['consignment_id'], 'courier_ref' => (string)$j['data']['consignment_id']], 'id=?', [$o['id']]);
        insert('order_history', ['order_id' => $o['id'], 'status' => 'courier', 'note' => 'Sent to Pathao · ' . $j['data']['consignment_id'], 'admin_name' => admin()['name'] ?? 'System']);
        return '';
    }
    return 'Pathao: ' . ($j['message'] ?? "HTTP $code") . (isset($j['errors']) ? ' ' . json_encode($j['errors']) : '');
}
function pathao_status(array $o): string {
    [$code, $body] = http_json('GET', pathao_base() . '/aladdin/api/v1/orders/' . rawurlencode($o['courier_ref']) . '/info', null, ['Authorization: Bearer ' . pathao_token()]);
    $j = json_decode($body, true) ?: [];
    return (string)($j['data']['order_status'] ?? 'unknown');
}
function courier_tracking_url(array $o): string {
    return match ($o['courier']) {
        'Steadfast' => $o['tracking_no'] ? 'https://steadfast.com.bd/t/' . rawurlencode($o['tracking_no']) : '',
        'Pathao' => $o['tracking_no'] ? 'https://merchant.pathao.com/tracking?consignment_id=' . rawurlencode($o['tracking_no']) . '&phone=' . rawurlencode($o['phone']) : '',
        default => '',
    };
}
