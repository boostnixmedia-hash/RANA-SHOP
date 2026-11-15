<?php
declare(strict_types=1);
/* Storefront routes */

function store_page(string $view, array $vars = []): string {
    $vars['content'] = view('store/' . $view, $vars);
    return view('store/layout', $vars);
}
function render(string $view, array $vars = []): never { echo store_page($view, $vars); exit; }
function wants_json(): bool { return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'); }

/* ---------- Home ---------- */
route('GET', '/', function () {
    $draft = !empty($_GET['preview']) && admin() && can('content');
    render('home', ['sections' => home_sections($draft), 'preview' => $draft, 'seo' => []]);
});

/* ---------- Listings ---------- */
function listing(array $base, string $title, string $intro = '', array $seo = [], array $extra = []): never {
    $f = $base + [
        'sort' => get('sort', $base['sort'] ?? 'new'), 'size' => get('size'), 'color' => get('color'), 'min' => get('min'), 'max' => get('max'),
        'page' => (int)get('page', 1), 'per' => 24,
    ];
    if (get('cat') !== '' && empty($base['category_ids'])) { $c = category_by_slug(get('cat')); if ($c) $f['category_ids'] = category_descendant_ids((int)$c['id']); }
    $res = product_query($f);
    render('listing', ['title' => $title, 'intro' => $intro, 'res' => $res, 'filters' => $f, 'opts' => filter_options(), 'seo' => $seo] + $extra);
}
route('GET', '/shop', fn() => listing([], 'Shop All', '', ['title' => 'Shop All']));
route('GET', '/new-arrivals', fn() => listing(['sort' => 'new'], 'New Arrivals', 'The latest pieces, just landed.', ['title' => 'New Arrivals']));
route('GET', '/best-sellers', fn() => listing(['sort' => 'best'], 'Best Sellers', 'What everyone is wearing right now.', ['title' => 'Best Sellers']));
route('GET', '/featured', fn() => listing(['featured' => 1], 'Featured', 'Hand-picked by us.', ['title' => 'Featured Products']));
route('GET', '/search', function () {
    $q = mb_substr(get('q'), 0, 100);
    listing(['q' => $q], $q !== '' ? 'Search: “' . $q . '”' : 'Search', '', ['title' => 'Search', 'noindex' => 1], ['q' => $q]);
});
route('GET', '/category/{slug}', function ($slug) {
    $c = category_by_slug($slug) ?? not_found();
    listing(['category_ids' => category_descendant_ids((int)$c['id'])], $c['name'], (string)$c['description'],
        ['title' => $c['seo_title'] ?: $c['name'], 'desc' => $c['seo_desc'] ?: $c['description'], 'image' => $c['image'] ? abs_url('uploads/' . $c['image']) : null],
        ['banner' => $c['image'], 'category' => $c]);
});
route('GET', '/collections', function () {
    render('collections', ['collections' => collections_visible(), 'seo' => ['title' => 'Collections']]);
});
route('GET', '/collection/{slug}', function ($slug) {
    $c = row('SELECT * FROM collections WHERE slug=? AND visible=1', [$slug]) ?? not_found();
    listing(['collection' => $c['id'], 'sort' => get('sort', 'manual')], $c['name'], (string)$c['description'],
        ['title' => $c['seo_title'] ?: $c['name'], 'desc' => $c['seo_desc'] ?: $c['description'], 'image' => $c['cover'] ? abs_url('uploads/' . $c['cover']) : null],
        ['banner' => $c['cover'], 'collection' => $c]);
});

/* ---------- Product ---------- */
route('GET', '/product/{slug}', function ($slug) {
    $preview = admin() && can('products');
    $p = product_by_slug($slug, $preview) ?? not_found();
    $reviews = rows("SELECT * FROM reviews WHERE product_id=? AND status='approved' ORDER BY created_at DESC LIMIT 50", [$p['id']]);
    $related = product_query(['category_ids' => $p['category_id'] ? [$p['category_id']] : [], 'exclude' => [$p['id']], 'sort' => 'best', 'per' => 8])['items'];
    $canReview = false;
    if ($c = customer()) $canReview = !val('SELECT 1 FROM reviews WHERE product_id=? AND customer_id=?', [$p['id'], $c['id']]);
    render('product', ['p' => $p, 'reviews' => $reviews, 'related' => $related, 'canReview' => $canReview,
        'seo' => ['title' => $p['seo_title'] ?: $p['name'], 'desc' => $p['seo_desc'] ?: ($p['short_desc'] ?: strip_tags((string)$p['description'])),
            'image' => !empty($p['images'][0]) ? abs_url('uploads/' . $p['images'][0]['path']) : null, 'type' => 'product', 'jsonld' => [product_jsonld($p)], 'noindex' => $p['status'] !== 'active']]);
});
route('POST', '/product/{slug}/review', function ($slug) {
    $c = require_customer();
    $p = product_by_slug($slug) ?? not_found();
    rate_limit_or_fail('review', 5, 3600);
    $rating = (int)post('rating'); $body = mb_substr(post('body'), 0, 2000);
    if ($rating < 1 || $rating > 5 || mb_strlen($body) < 5) { flash('error', 'Please choose a star rating and write a few words.'); redirect('/product/' . $slug . '#reviews'); }
    if (val('SELECT 1 FROM reviews WHERE product_id=? AND customer_id=?', [$p['id'], $c['id']])) { flash('error', 'You have already reviewed this product.'); redirect('/product/' . $slug . '#reviews'); }
    $imgs = [];
    foreach (array_slice(files_list('images'), 0, 3) as $f) { try { $imgs[] = store_image($f, 'reviews', 1200); } catch (RuntimeException $e) { flash('error', $e->getMessage()); } }
    $verified = (bool)val("SELECT 1 FROM order_items oi JOIN orders o ON o.id=oi.order_id WHERE oi.product_id=? AND o.status='delivered' AND (o.customer_id=? OR o.email=?)", [$p['id'], $c['id'], $c['email']]);
    insert('reviews', ['product_id' => $p['id'], 'customer_id' => $c['id'], 'name' => $c['name'], 'rating' => $rating, 'body' => $body, 'images' => $imgs ? json_encode($imgs) : null, 'verified' => (int)$verified]);
    notify_admin('review', 'New review (' . $rating . '★) on ' . $p['name'] . ' waiting for approval', '/admin/reviews');
    flash('success', 'Thank you! Your review will appear after approval.');
    redirect('/product/' . $slug . '#reviews');
});

/* ---------- Search suggestions / quick view API ---------- */
route('GET', '/api/search', function () {
    $q = mb_substr(get('q'), 0, 60);
    if (mb_strlen($q) < 2) json_out(['items' => []]);
    $items = product_query(['q' => $q, 'per' => 6, 'sort' => 'best'])['items'];
    json_out(['items' => array_map(fn($p) => ['name' => $p['name'], 'url' => url('product/' . $p['slug']), 'price' => money($p['final_price']), 'image' => media($p['image'])], $items),
        'all' => url('search?q=' . rawurlencode($q))]);
});
route('GET', '/api/product/{id}', function ($id) {
    $p = row('SELECT slug FROM products WHERE id=?', [(int)$id]) ?? json_out(['error' => 'not found'], 404);
    $p = product_by_slug($p['slug']) ?? json_out(['error' => 'not found'], 404);
    json_out(['id' => $p['id'], 'name' => $p['name'], 'url' => url('product/' . $p['slug']), 'price' => $p['final_price'], 'image' => media($p['images'][0]['path'] ?? null),
        'variants' => array_map(fn($v) => ['id' => $v['id'], 'color' => $v['color'], 'hex' => $v['color_hex'], 'size' => $v['size'], 'stock' => (int)$v['stock'], 'price' => variant_price($v, $p)], $p['variants'])]);
});

/* ---------- Wishlist ---------- */
route('GET', '/wishlist', function () {
    $ids = wishlist_ids();
    $items = $ids ? product_query(['ids' => $ids, 'per' => 100, 'sort' => 'ids'])['items'] : [];
    render('wishlist', ['items' => $items, 'seo' => ['title' => 'Wishlist', 'noindex' => 1]]);
});
route('POST', '/wishlist/toggle', function () {
    $on = wishlist_toggle((int)post('product_id'));
    if (wants_json()) json_out(['ok' => true, 'on' => $on, 'count' => count(wishlist_ids())]);
    back('/wishlist');
});

/* ---------- Cart ---------- */
function cart_json(string $msg = '', bool $ok = true): never {
    $lines = cart_lines();
    $t = cart_totals($lines, 'inside', $_SESSION['coupon'] ?? null);
    json_out(['ok' => $ok, 'message' => $msg, 'count' => cart_count(), 'html' => view('store/partials/cart_drawer', ['lines' => $lines, 't' => $t]), 'subtotal' => money($t['subtotal'])]);
}
route('GET', '/cart', function () {
    $lines = cart_lines();
    $t = cart_totals($lines, 'inside', $_SESSION['coupon'] ?? null);
    if ($t['coupon_error'] && !empty($_SESSION['coupon'])) { flash('error', $t['coupon_error']); unset($_SESSION['coupon']); $t = cart_totals($lines, 'inside'); }
    render('cart', ['lines' => $lines, 't' => $t, 'seo' => ['title' => 'Shopping Cart', 'noindex' => 1]]);
});
route('GET', '/cart/drawer', fn() => cart_json());
route('POST', '/cart/add', function () {
    rate_limit_or_fail('cart', 120, 600);
    [$ok, $msg] = cart_add((int)post('variant_id'), (int)post('qty', 1));
    if (wants_json()) cart_json($msg, $ok);
    flash($ok ? 'success' : 'error', $msg);
    post('buy_now') === '1' && $ok ? redirect('/checkout') : back('/cart');
});
route('POST', '/cart/update', function () {
    [$ok, $msg] = cart_set((int)post('variant_id'), (int)post('qty'));
    if (wants_json()) cart_json($msg, $ok);
    if (!$ok) flash('error', $msg);
    redirect('/cart');
});
route('POST', '/cart/coupon', function () {
    rate_limit_or_fail('coupon', 20, 600);
    $code = strtoupper(mb_substr(post('code'), 0, 40));
    if ($code === '' || post('remove') === '1') { unset($_SESSION['coupon']); flash('info', 'Coupon removed.'); back('/cart'); }
    $t = cart_totals(cart_lines(), 'inside', $code, customer()['email'] ?? '');
    if ($t['coupon']) { $_SESSION['coupon'] = $code; flash('success', 'Coupon applied: you save ' . money($t['discount']) . '.'); }
    else flash('error', $t['coupon_error'] ?: 'This coupon code is not valid.');
    back('/cart');
});

/* ---------- Checkout ---------- */
route('GET', '/checkout', function () {
    $lines = cart_lines();
    if (!$lines) { flash('info', 'Your cart is empty.'); redirect('/cart'); }
    $c = customer();
    if (!$c && !setting('guest_checkout', 1)) { $_SESSION['customer_next'] = '/checkout'; flash('info', 'Please log in or create an account to check out.'); redirect('/account/login'); }
    $addr = $c ? rows('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id', [$c['id']]) : [];
    $_SESSION['checkout_key'] = $_SESSION['checkout_key'] ?? rand_token(16);
    render('checkout', ['lines' => $lines, 'customer' => $c, 'addresses' => $addr, 'methods' => payment_methods_enabled(), 'seo' => ['title' => 'Checkout', 'noindex' => 1]]);
});
route('POST', '/checkout/totals', function () {
    $lines = cart_lines();
    $t = cart_totals($lines, shipping_zone(post('district')), post('coupon') ?: ($_SESSION['coupon'] ?? null), post('email'));
    json_out(['subtotal' => money($t['subtotal']), 'discount' => money($t['discount']), 'discount_raw' => $t['discount'], 'shipping' => $t['shipping'] > 0 ? money($t['shipping']) : 'Free',
        'total' => money($t['total']), 'coupon_error' => $t['coupon_error'], 'coupon_ok' => (bool)$t['coupon'], 'zone' => $t['zone']]);
});
route('POST', '/checkout', function () {
    rate_limit_or_fail('checkout', 10, 600);
    $lines = cart_lines();
    $d = [
        'name' => mb_substr(post('name'), 0, 120), 'email' => mb_strtolower(mb_substr(post('email'), 0, 190)), 'phone' => post('phone'),
        'district' => post('district'), 'area' => mb_substr(post('area'), 0, 120), 'address' => mb_substr(post('address'), 0, 500), 'note' => mb_substr(post('note'), 0, 1000),
        'payment_method' => post('payment_method', 'cod'), 'coupon' => post('coupon') ?: ($_SESSION['coupon'] ?? ''),
        'idem_key' => preg_match('/^[a-f0-9]{32}$/', post('idem_key')) ? post('idem_key') : rand_token(16),
        'customer_id' => customer()['id'] ?? null,
    ];
    $errors = [];
    if (mb_strlen($d['name']) < 2) $errors['name'] = 'Please enter your full name.';
    if ((setting('require_email', 1) || $d['email'] !== '') && !valid_email($d['email'])) $errors['email'] = 'Please enter a valid email address. Your order confirmation is sent here.';
    $phone = normalize_bd_phone($d['phone']);
    if (!$phone) $errors['phone'] = 'Please enter a valid Bangladeshi mobile number (01XXXXXXXXX).';
    if (!in_array($d['district'], bd_districts(), true)) $errors['district'] = 'Please choose your district.';
    if (mb_strlen($d['area']) < 2) $errors['area'] = 'Please enter your area / thana.';
    if (mb_strlen($d['address']) < 6) $errors['address'] = 'Please enter your full address.';
    if (!isset(payment_methods_enabled()[$d['payment_method']])) $errors['payment_method'] = 'Please choose a payment method.';
    if (!setting('guest_checkout', 1) && !customer()) $errors['name'] = 'Please log in to check out.';
    if ($errors) { keep_old($_POST); $_SESSION['errors'] = $errors; flash('error', 'Please check the highlighted fields.'); redirect('/checkout'); }
    $d['phone'] = $phone;
    try {
        $order = place_order($d, $lines);
    } catch (RuntimeException $e) {
        keep_old($_POST); flash('error', $e->getMessage()); redirect('/checkout');
    }
    clear_old(); cart_clear(); unset($_SESSION['checkout_key'], $_SESSION['errors']);
    if (post('save_address') === '1' && ($c = customer()) && !val('SELECT 1 FROM addresses WHERE customer_id=? AND address=?', [$c['id'], $d['address']])) {
        insert('addresses', ['customer_id' => $c['id'], 'label' => 'Home', 'name' => $d['name'], 'phone' => $phone, 'district' => $d['district'], 'area' => $d['area'], 'address' => $d['address'],
            'is_default' => val('SELECT COUNT(*) FROM addresses WHERE customer_id=?', [$c['id']]) ? 0 : 1]);
    }
    $_SESSION['last_order'] = $order['order_no'];
    if ($order['payment_method'] !== 'cod' && $order['payment_status'] !== 'paid') redirect('/pay/' . $order['order_no'] . '?t=' . $order['access_token']);
    redirect('/order/success/' . $order['order_no'] . '?t=' . $order['access_token']);
});

/* ---------- Payment ---------- */
route('GET', '/pay/{no}', function ($no) {
    $o = order_for_customer_view($no, get('t')) ?? not_found();
    if ($o['payment_status'] === 'paid' || $o['payment_method'] === 'cod') redirect(order_view_url($o));
    if (in_array($o['status'], ['cancelled', 'returned'], true)) { flash('error', 'This order was cancelled. Please place a new order.'); redirect(order_view_url($o)); }
    [$urlTo, $err] = start_payment($o);
    if ($urlTo) redirect($urlTo);
    render('payment_error', ['o' => $o, 'error' => $err, 'seo' => ['title' => 'Payment', 'noindex' => 1]]);
});
function payment_return(?array $o, string $err): never {
    if (!$o) not_found();
    if ($err) flash('error', $err . ' You can try again below.');
    redirect('/order/success/' . $o['order_no'] . '?t=' . $o['access_token']);
}
route('POST|GET', '/pay/sslcommerz/return', function () {
    $r = get('r');
    $tran = (string)($_POST['tran_id'] ?? get('tran_id'));
    if ($r !== 'success') {
        $pay = row('SELECT * FROM payments WHERE tran_id=?', [$tran]);
        if ($pay && $pay['status'] !== 'paid') payment_failed($pay, 'SSLCommerz: ' . $r);
        payment_return($pay ? row('SELECT * FROM orders WHERE id=?', [$pay['order_id']]) : null, $r === 'cancel' ? 'Payment cancelled.' : 'Payment failed.');
    }
    [$o, $err] = sslcz_verify((string)($_POST['val_id'] ?? ''), $tran);
    payment_return($o, $err);
});
route('POST', '/pay/sslcommerz/ipn', function () {
    [$o, $err] = sslcz_verify((string)($_POST['val_id'] ?? ''), (string)($_POST['tran_id'] ?? ''));
    http_response_code($o && !$err ? 200 : 400); echo $err ?: 'OK'; exit;
});
route('GET', '/pay/bkash/callback', function () {
    [$o, $err] = bkash_callback(get('paymentID'), get('status'));
    payment_return($o, $err);
});
route('GET', '/pay/nagad/callback', function () {
    [$o, $err] = nagad_callback(get('payment_ref_id'));
    payment_return($o, $err);
});

/* ---------- Order pages ---------- */
route('GET', '/order/success/{no}', function ($no) {
    $o = order_for_customer_view($no, get('t')) ?? not_found();
    render('order_success', ['o' => $o, 'items' => order_items((int)$o['id']), 'seo' => ['title' => 'Thank you', 'noindex' => 1]]);
});
route('GET', '/order/{no}', function ($no) {
    $o = order_for_customer_view($no, get('t')) ?? not_found();
    render('order_view', ['o' => $o, 'items' => order_items((int)$o['id']), 'history' => rows('SELECT * FROM order_history WHERE order_id=? ORDER BY id', [$o['id']]), 'seo' => ['title' => 'Order ' . $o['order_no'], 'noindex' => 1]]);
});
route('GET|POST', '/track', function () {
    $o = null; $err = '';
    if (is_post()) {
        rate_limit_or_fail('track', 15, 600);
        $o = order_for_customer_view(post('order_no'), null, post('phone'));
        if ($o) redirect(order_view_url($o));
        $err = 'We could not find an order with that order ID and phone number.';
    }
    render('track', ['error' => $err, 'seo' => ['title' => 'Track Your Order']]);
});

/* ---------- Customer accounts ---------- */
route('GET|POST', '/account/login', function () {
    if (customer()) redirect('/account');
    $err = '';
    if (is_post()) {
        rate_limit_or_fail('clogin', 10, 900);
        $c = row('SELECT * FROM customers WHERE email=?', [mb_strtolower(post('email'))]);
        if ($c && $c['active'] && $c['pass_hash'] && password_verify((string)($_POST['password'] ?? ''), $c['pass_hash'])) {
            customer_login_session((int)$c['id']);
            $next = $_SESSION['customer_next'] ?? '/account'; unset($_SESSION['customer_next']);
            redirect(str_starts_with($next, '/') && !str_starts_with($next, '//') ? substr($next, strlen(base_path())) ?: '/account' : '/account');
        }
        $err = 'Email or password is wrong.';
    }
    render('account/login', ['error' => $err, 'seo' => ['title' => 'Login', 'noindex' => 1]]);
});
route('GET|POST', '/account/register', function () {
    if (customer()) redirect('/account');
    $errors = [];
    if (is_post()) {
        rate_limit_or_fail('register', 5, 3600);
        $name = mb_substr(post('name'), 0, 120); $email = mb_strtolower(post('email')); $phone = post('phone'); $pass = (string)($_POST['password'] ?? '');
        if (mb_strlen($name) < 2) $errors['name'] = 'Please enter your name.';
        if (!valid_email($email)) $errors['email'] = 'Please enter a valid email.';
        if ($phone !== '' && !normalize_bd_phone($phone)) $errors['phone'] = 'Please enter a valid mobile number.';
        if ($m = valid_password($pass)) $errors['password'] = $m;
        if ($pass !== ($_POST['password2'] ?? '')) $errors['password2'] = 'Passwords do not match.';
        $existing = valid_email($email) ? row('SELECT * FROM customers WHERE email=?', [$email]) : null;
        if ($existing && $existing['pass_hash']) $errors['email'] = 'An account with this email already exists. Please log in.';
        if (!$errors) {
            $data = ['name' => $name, 'email' => $email, 'phone' => normalize_bd_phone($phone) ?? '', 'pass_hash' => password_hash($pass, PASSWORD_DEFAULT)];
            if ($existing) { update('customers', $data, 'id=?', [$existing['id']]); $id = (int)$existing['id']; }
            else $id = insert('customers', $data);
            // Link earlier guest orders placed with this email
            q('UPDATE orders SET customer_id=? WHERE customer_id IS NULL AND email=?', [$id, $email]);
            customer_login_session($id);
            queue_template_email('account_registration', $email, $name, ['customer_name' => $name, 'account_link' => abs_url('account'), 'store_name' => setting('store_name'), 'support_email' => setting('contact_email'), 'support_phone' => setting('contact_phone')], 'register:' . $id);
            process_email_queue(2);
            flash('success', 'Welcome, ' . $name . '! Your account is ready.');
            $next = $_SESSION['customer_next'] ?? ''; unset($_SESSION['customer_next']);
            redirect($next === base_path() . '/checkout' || $next === '/checkout' ? '/checkout' : '/account');
        }
        keep_old($_POST);
    }
    render('account/register', ['errors' => $errors, 'seo' => ['title' => 'Create Account', 'noindex' => 1]]);
});
route('GET|POST', '/account/forgot', function () {
    $sent = false;
    if (is_post()) {
        rate_limit_or_fail('forgot', 5, 3600);
        $c = row('SELECT * FROM customers WHERE email=? AND active=1', [mb_strtolower(post('email'))]);
        if ($c) {
            $tok = create_reset_token('customer', (int)$c['id']);
            queue_template_email('password_reset', $c['email'], $c['name'], ['customer_name' => $c['name'], 'reset_link' => abs_url('account/reset?token=' . $tok), 'store_name' => setting('store_name'), 'support_email' => setting('contact_email'), 'support_phone' => setting('contact_phone')], 'reset:' . $tok);
            process_email_queue(2);
        }
        $sent = true; // same message either way, so emails can't be probed
    }
    render('account/forgot', ['sent' => $sent, 'seo' => ['title' => 'Forgot Password', 'noindex' => 1]]);
});
route('GET|POST', '/account/reset', function () {
    $tok = (string)($_POST['token'] ?? get('token'));
    $r = find_reset_token('customer', $tok);
    $err = '';
    if ($r && is_post()) {
        $pass = (string)($_POST['password'] ?? '');
        if ($m = valid_password($pass)) $err = $m;
        elseif ($pass !== ($_POST['password2'] ?? '')) $err = 'Passwords do not match.';
        else {
            update('customers', ['pass_hash' => password_hash($pass, PASSWORD_DEFAULT)], 'id=?', [$r['user_id']]);
            update('password_resets', ['used' => 1], 'id=?', [$r['id']]);
            customer_login_session((int)$r['user_id']);
            flash('success', 'Your password has been changed.');
            redirect('/account');
        }
    }
    render('account/reset', ['valid' => (bool)$r, 'token' => $tok, 'error' => $err, 'seo' => ['title' => 'Reset Password', 'noindex' => 1]]);
});
route('POST', '/account/logout', function () { unset($_SESSION['customer_id']); session_regenerate_id(true); redirect('/'); });
route('GET', '/account', function () {
    $c = require_customer();
    $orders = rows('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC LIMIT 5', [$c['id']]);
    render('account/dashboard', ['c' => $c, 'orders' => $orders, 'tab' => 'overview', 'seo' => ['title' => 'My Account', 'noindex' => 1]]);
});
route('GET', '/account/orders', function () {
    $c = require_customer();
    render('account/orders', ['c' => $c, 'orders' => rows('SELECT * FROM orders WHERE customer_id=? ORDER BY id DESC', [$c['id']]), 'tab' => 'orders', 'seo' => ['title' => 'My Orders', 'noindex' => 1]]);
});
route('GET', '/account/orders/{no}', function ($no) {
    $c = require_customer();
    $o = row('SELECT * FROM orders WHERE order_no=? AND customer_id=?', [$no, $c['id']]) ?? not_found();
    render('order_view', ['o' => $o, 'items' => order_items((int)$o['id']), 'history' => rows('SELECT * FROM order_history WHERE order_id=? ORDER BY id', [$o['id']]), 'in_account' => true, 'seo' => ['title' => 'Order ' . $o['order_no'], 'noindex' => 1]]);
});
route('GET|POST', '/account/profile', function () {
    $c = require_customer(); $errors = [];
    if (is_post()) {
        $name = mb_substr(post('name'), 0, 120); $phone = post('phone');
        if (mb_strlen($name) < 2) $errors['name'] = 'Please enter your name.';
        if ($phone !== '' && !normalize_bd_phone($phone)) $errors['phone'] = 'Invalid mobile number.';
        $newPass = (string)($_POST['new_password'] ?? '');
        if ($newPass !== '') {
            $hash = val('SELECT pass_hash FROM customers WHERE id=?', [$c['id']]);
            if (!password_verify((string)($_POST['current_password'] ?? ''), (string)$hash)) $errors['current_password'] = 'Current password is wrong.';
            elseif ($m = valid_password($newPass)) $errors['new_password'] = $m;
        }
        if (!$errors) {
            $data = ['name' => $name, 'phone' => normalize_bd_phone($phone) ?? ''];
            if ($newPass !== '') $data['pass_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
            update('customers', $data, 'id=?', [$c['id']]);
            flash('success', 'Profile saved.'); redirect('/account/profile');
        }
    }
    render('account/profile', ['c' => $c, 'errors' => $errors, 'tab' => 'profile', 'seo' => ['title' => 'My Profile', 'noindex' => 1]]);
});
route('GET|POST', '/account/addresses', function () {
    $c = require_customer();
    if (is_post()) {
        $act = post('action'); $id = (int)post('id');
        if ($act === 'delete') q('DELETE FROM addresses WHERE id=? AND customer_id=?', [$id, $c['id']]);
        elseif ($act === 'default') { q('UPDATE addresses SET is_default=(id=?) WHERE customer_id=?', [$id, $c['id']]); }
        else {
            $d = ['label' => mb_substr(post('label', 'Home'), 0, 60), 'name' => mb_substr(post('name'), 0, 120), 'phone' => normalize_bd_phone(post('phone')) ?? '', 'district' => post('district'), 'area' => mb_substr(post('area'), 0, 120), 'address' => mb_substr(post('address'), 0, 500)];
            if (!$d['name'] || !$d['phone'] || !in_array($d['district'], bd_districts(), true) || !$d['area'] || !$d['address']) { flash('error', 'Please fill every field with a valid mobile number.'); redirect('/account/addresses'); }
            if ($id) update('addresses', $d, 'id=? AND customer_id=?', [$id, $c['id']]);
            else { $d['customer_id'] = $c['id']; $d['is_default'] = val('SELECT COUNT(*) FROM addresses WHERE customer_id=?', [$c['id']]) ? 0 : 1; insert('addresses', $d); }
        }
        flash('success', 'Addresses updated.'); redirect('/account/addresses');
    }
    render('account/addresses', ['c' => $c, 'addresses' => rows('SELECT * FROM addresses WHERE customer_id=? ORDER BY is_default DESC, id', [$c['id']]), 'tab' => 'addresses', 'seo' => ['title' => 'My Addresses', 'noindex' => 1]]);
});

/* ---------- CMS pages ---------- */
function show_page(string $slug, array $extra = []): never {
    $p = row('SELECT * FROM pages WHERE slug=? AND visible=1', [$slug]) ?? not_found();
    render('page', ['page' => $p, 'seo' => ['title' => $p['seo_title'] ?: $p['title'], 'desc' => $p['seo_desc'] ?: mb_substr(strip_tags((string)$p['body']), 0, 160)]] + $extra);
}
route('GET', '/page/{slug}', fn($slug) => show_page($slug));
foreach (['about' => 'about', 'faq' => 'faq', 'privacy-policy' => 'privacy-policy', 'terms' => 'terms', 'shipping-policy' => 'shipping-policy', 'return-policy' => 'return-policy'] as $r => $slug)
    route('GET', '/' . $r, fn() => show_page($slug));
route('GET|POST', '/contact', function () {
    if (is_post()) {
        rate_limit_or_fail('contact', 5, 3600);
        $d = ['name' => mb_substr(post('name'), 0, 120), 'email' => mb_substr(post('email'), 0, 190), 'phone' => mb_substr(post('phone'), 0, 30), 'subject' => mb_substr(post('subject'), 0, 190), 'message' => mb_substr(post('message'), 0, 5000)];
        if (post('website') !== '') redirect('/contact'); // honeypot
        if (mb_strlen($d['name']) < 2 || !valid_email($d['email']) || mb_strlen($d['message']) < 5) { keep_old($_POST); flash('error', 'Please enter your name, a valid email and a message.'); redirect('/contact'); }
        $id = insert('contacts', $d);
        notify_admin('message', 'New message from ' . $d['name'] . ': ' . mb_substr($d['subject'] ?: $d['message'], 0, 80), '/admin/messages');
        if ($to = setting('notify_email')) foreach (array_filter(array_map('trim', explode(',', $to))) as $t) queue_template_email('contact_message', $t, '', $d + ['store_name' => setting('store_name')], 'contact:' . $id . ':' . $t);
        process_email_queue(2);
        clear_old();
        flash('success', 'Thank you! We received your message and will reply soon.');
        redirect('/contact');
    }
    show_page('contact', ['contact_form' => true]);
});
route('POST', '/newsletter', function () {
    rate_limit_or_fail('newsletter', 5, 3600);
    $email = mb_strtolower(post('email'));
    $ok = valid_email($email);
    if ($ok) q('INSERT IGNORE INTO newsletter (email) VALUES (?)', [$email]);
    if (wants_json()) json_out(['ok' => $ok, 'message' => $ok ? 'Thanks for subscribing!' : 'Please enter a valid email.']);
    flash($ok ? 'success' : 'error', $ok ? 'Thanks for subscribing!' : 'Please enter a valid email.');
    back('/');
});

/* ---------- SEO files & cron ---------- */
route('GET', '/sitemap.xml', function () { header('Content-Type: application/xml; charset=utf-8'); echo sitemap_xml(); exit; });
route('GET', '/robots.txt', function () { header('Content-Type: text/plain; charset=utf-8'); echo robots_txt(); exit; });
route('GET', '/cron', function () {
    global $CONFIG;
    if (!hash_equals((string)($CONFIG['cron_key'] ?? ''), get('key')) || get('key') === '') { http_response_code(403); exit('Forbidden'); }
    $r = ['released' => release_expired_holds(), 'emails' => process_email_queue(30), 'published' => 0];
    header('Content-Type: application/json'); echo json_encode($r); exit;
});
