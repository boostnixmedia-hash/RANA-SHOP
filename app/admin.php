<?php
declare(strict_types=1);
/* Admin panel routes (/admin) */

function admin_page(string $view, array $vars = []): string {
    $vars['content'] = view('admin/' . $view, $vars);
    return view('admin/layout', $vars);
}
function admin_render(string $view, array $vars = []): never { echo admin_page($view, $vars); exit; }

/* ---------- auth ---------- */
route('GET|POST', '/admin/login', function () {
    if (admin()) redirect('/admin');
    $err = '';
    if (is_post()) {
        if (!rate_limit('alogin', 10, 900)) $err = 'Too many attempts. Please wait 15 minutes.';
        else {
            [$ok, $err] = admin_login(post('email'), (string)($_POST['password'] ?? ''));
            if ($ok) { $n = $_SESSION['admin_next'] ?? ''; unset($_SESSION['admin_next']); redirect($n && str_starts_with($n, base_path() . '/admin') ? substr($n, strlen(base_path())) : '/admin'); }
        }
    }
    echo view('admin/login', ['error' => $err]); exit;
});
route('POST', '/admin/logout', function () { admin_logout(); redirect('/admin/login'); });

/* ---------- dashboard ---------- */
route('GET', '/admin', function () {
    require_admin('dashboard');
    $stats = [
        'today' => (float)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','returned') AND DATE(created_at)=CURDATE()"),
        'month' => (float)val("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','returned') AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')"),
        'orders' => (int)val('SELECT COUNT(*) FROM orders'),
        'new' => (int)val("SELECT COUNT(*) FROM orders WHERE status IN ('new','pending')"),
        'products' => (int)val("SELECT COUNT(*) FROM products WHERE status<>'archived'"),
        'low' => (int)val('SELECT COUNT(DISTINCT product_id) FROM variants WHERE active=1 AND stock<=?', [(int)setting('low_stock_threshold', 5)]),
    ];
    admin_render('dashboard', ['title' => 'Dashboard', 'stats' => $stats, 'recent' => rows('SELECT * FROM orders ORDER BY id DESC LIMIT 8')]);
});

/* ---------- products ---------- */
route('GET', '/admin/products', function () {
    require_admin('products');
    $where = ['1=1']; $p = [];
    if (($q = get('q')) !== '') { $where[] = '(p.name LIKE ? OR p.sku LIKE ?)'; $p[] = "%$q%"; $p[] = "%$q%"; }
    if (($st = get('status')) !== '' && in_array($st, ['draft', 'active', 'archived'], true)) { $where[] = 'p.status=?'; $p[] = $st; }
    if (($c = (int)get('cat')) > 0) { $where[] = 'p.category_id=?'; $p[] = $c; }
    $w = implode(' AND ', $where);
    $pg = paginate((int)val("SELECT COUNT(*) FROM products p WHERE $w", $p), 30, (int)get('page', 1));
    $list = rows("SELECT p.*, c.name cat_name,
        (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_featured DESC, sort, id LIMIT 1) image,
        (SELECT COALESCE(SUM(stock),0) FROM variants v WHERE v.product_id=p.id AND v.active=1) stock
        FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $w ORDER BY p.id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p);
    admin_render('products', ['title' => 'Products', 'list' => $list, 'pg' => $pg]);
});
route('POST', '/admin/products/bulk', function () {
    require_admin('products');
    $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));
    $act = post('action');
    if (!$ids) { flash('error', 'Select at least one product.'); redirect('/admin/products'); }
    $in = implode(',', $ids);
    match ($act) {
        'active', 'draft', 'archived' => q("UPDATE products SET status=? WHERE id IN ($in)", [$act]),
        'feature' => q("UPDATE products SET featured=1 WHERE id IN ($in)"),
        'unfeature' => q("UPDATE products SET featured=0 WHERE id IN ($in)"),
        'delete' => array_map('delete_product', $ids),
        default => null,
    };
    log_activity('Bulk product action: ' . $act, 'product', null, 'IDs ' . $in);
    flash('success', count($ids) . ' product(s) updated.');
    redirect('/admin/products');
});
function delete_product(int $id): void {
    $p = row('SELECT * FROM products WHERE id=?', [$id]);
    if (!$p) return;
    $imgs = array_column(rows('SELECT path FROM product_images WHERE product_id=?', [$id]), 'path');
    q('DELETE FROM products WHERE id=?', [$id]);
    foreach ($imgs as $i) if (!str_starts_with($i, 'demo/')) delete_upload($i);
    log_activity('Product deleted', 'product', $id, $p['name']);
}
route('GET', '/admin/products/new', function () {
    require_admin('products');
    admin_render('product_form', ['title' => 'Add product', 'p' => null, 'images' => [], 'variants' => [], 'cols' => []]);
});
route('GET', '/admin/products/{id}/edit', function ($id) {
    require_admin('products');
    $p = row('SELECT * FROM products WHERE id=?', [(int)$id]) ?? not_found();
    admin_render('product_form', ['title' => 'Edit product', 'p' => $p,
        'images' => rows('SELECT * FROM product_images WHERE product_id=? ORDER BY is_featured DESC, sort, id', [$p['id']]),
        'variants' => rows('SELECT * FROM variants WHERE product_id=? AND active=1 ORDER BY sort, id', [$p['id']]),
        'cols' => array_map('intval', array_column(rows('SELECT collection_id FROM product_collections WHERE product_id=?', [$p['id']]), 'collection_id'))]);
});
route('POST', '/admin/products/{id}/duplicate', function ($id) {
    require_admin('products');
    $p = row('SELECT * FROM products WHERE id=?', [(int)$id]) ?? not_found();
    $new = tx(function () use ($p) {
        $d = $p; unset($d['id'], $d['created_at'], $d['updated_at']);
        $d['name'] .= ' (copy)'; $d['slug'] = unique_slug('products', $d['name']); $d['sku'] = null; $d['status'] = 'draft'; $d['sold_count'] = 0;
        $nid = insert('products', $d);
        $map = [];
        foreach (rows('SELECT * FROM product_images WHERE product_id=?', [$p['id']]) as $i) { $old = $i['id']; unset($i['id']); $i['product_id'] = $nid; $map[$old] = insert('product_images', $i); }
        foreach (rows('SELECT * FROM variants WHERE product_id=? AND active=1', [$p['id']]) as $v) { unset($v['id']); $v['product_id'] = $nid; $v['sku'] = $v['sku'] . '-C' . $nid; $v['stock'] = 0; $v['image_id'] = $v['image_id'] ? ($map[$v['image_id']] ?? null) : null; insert('variants', $v); }
        q('INSERT INTO product_collections (product_id, collection_id, sort) SELECT ?, collection_id, sort FROM product_collections WHERE product_id=?', [$nid, $p['id']]);
        return $nid;
    });
    log_activity('Product duplicated', 'product', $new, $p['name']);
    flash('success', 'Copy created as a draft. Stock is set to 0.');
    redirect('/admin/products/' . $new . '/edit');
});
route('POST', '/admin/products/{id}/delete', function ($id) {
    require_admin('products');
    delete_product((int)$id);
    flash('success', 'Product deleted.');
    redirect('/admin/products');
});
route('POST', '/admin/products/save', function () { save_product(null); });
route('POST', '/admin/products/{id}/save', function ($id) { save_product((int)$id); });

function save_product(?int $id): never {
    $me = require_admin('products');
    $old = $id ? (row('SELECT * FROM products WHERE id=?', [$id]) ?? not_found()) : null;
    $num = fn($k) => post($k) === '' ? null : round((float)post($k), 2);
    $d = [
        'name' => mb_substr(post('name'), 0, 190), 'short_desc' => mb_substr(post('short_desc'), 0, 500), 'description' => clean_html((string)($_POST['description'] ?? '')),
        'price' => $num('price') ?? 0, 'sale_price' => $num('sale_price'), 'cost_price' => $num('cost_price'),
        'category_id' => (int)post('category_id') ?: null, 'tags' => mb_substr(post('tags'), 0, 500), 'video_url' => mb_substr(post('video_url'), 0, 500) ?: null,
        'status' => in_array(post('status'), ['draft', 'active', 'archived'], true) ? post('status') : 'draft',
        'featured' => post('featured') === '1' ? 1 : 0, 'publish_at' => post('publish_at') ? date('Y-m-d H:i:s', strtotime(post('publish_at'))) : null,
        'seo_title' => mb_substr(post('seo_title'), 0, 190) ?: null, 'seo_desc' => mb_substr(post('seo_desc'), 0, 320) ?: null,
        'sku' => mb_substr(post('sku'), 0, 80) ?: null,
    ];
    $errs = [];
    if (mb_strlen($d['name']) < 2) $errs[] = 'Product name is required.';
    if ($d['price'] <= 0) $errs[] = 'Regular price must be more than 0.';
    if ($d['sale_price'] !== null && $d['sale_price'] >= $d['price']) $errs[] = 'Sale price must be lower than the regular price (leave it empty for no sale).';
    if ($d['sku'] && val('SELECT id FROM products WHERE sku=?' . ($id ? ' AND id<>?' : ''), $id ? [$d['sku'], $id] : [$d['sku']])) $errs[] = 'This SKU is already used by another product.';
    $d['slug'] = unique_slug('products', post('slug') !== '' ? post('slug') : $d['name'], $id);

    // Variant matrix rows: v[n][color,hex,size,stock,price,sku]
    $vrows = [];
    $posted = (array)($_POST['v'] ?? []);
    foreach ($posted as $r) {
        $color = mb_substr(trim((string)($r['color'] ?? '')), 0, 60); $size = mb_substr(trim((string)($r['size'] ?? '')), 0, 30);
        $key = mb_strtolower($color . '|' . $size);
        if (isset($vrows[$key])) { $errs[] = "Size/colour “{$color} {$size}” is listed twice."; continue; }
        $vrows[$key] = ['color' => $color, 'color_hex' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($r['hex'] ?? '')) ? $r['hex'] : '', 'size' => $size,
            'stock' => max(0, (int)($r['stock'] ?? 0)), 'price' => ($r['price'] ?? '') === '' ? null : round((float)$r['price'], 2), 'sku' => mb_substr(trim((string)($r['sku'] ?? '')), 0, 100)];
    }
    if (!$vrows) $vrows['|'] = ['color' => '', 'color_hex' => '', 'size' => '', 'stock' => max(0, (int)post('simple_stock')), 'price' => null, 'sku' => ''];

    if ($errs) { keep_old($_POST); foreach ($errs as $e) flash('error', $e); redirect($id ? "/admin/products/$id/edit" : '/admin/products/new'); }

    $stored = [];
    foreach (files_list('images') as $f) { try { $stored[] = store_image($f, 'products', 1600); } catch (RuntimeException $e) { flash('error', $f['name'] . ': ' . $e->getMessage()); } }
    if ($vf = files_list('video_file')) { try { $d['video_url'] = store_video($vf[0], 'products'); } catch (RuntimeException $e) { flash('error', $e->getMessage()); } }

    $pid = tx(function () use ($id, $d, $vrows, $stored, $me) {
        if ($id) update('products', $d, 'id=?', [$id]); else $id = insert('products', $d);
        $base = $d['sku'] ?: 'P' . $id;
        // images
        foreach ((array)($_POST['img_delete'] ?? []) as $iid) {
            $im = row('SELECT * FROM product_images WHERE id=? AND product_id=?', [(int)$iid, $id]);
            if ($im) { q('DELETE FROM product_images WHERE id=?', [$im['id']]); if (!str_starts_with($im['path'], 'demo/')) delete_upload($im['path']); }
        }
        foreach ((array)($_POST['img_sort'] ?? []) as $iid => $s) q('UPDATE product_images SET sort=? WHERE id=? AND product_id=?', [(int)$s, (int)$iid, $id]);
        $sort = (int)val('SELECT COALESCE(MAX(sort),0) FROM product_images WHERE product_id=?', [$id]);
        foreach ($stored as $path) insert('product_images', ['product_id' => $id, 'path' => $path, 'alt' => $d['name'], 'sort' => ++$sort]);
        $feat = (int)post('img_featured');
        if ($feat && val('SELECT 1 FROM product_images WHERE id=? AND product_id=?', [$feat, $id])) q('UPDATE product_images SET is_featured=(id=?) WHERE product_id=?', [$feat, $id]);
        elseif (!val('SELECT 1 FROM product_images WHERE product_id=? AND is_featured=1', [$id])) q('UPDATE product_images SET is_featured=1 WHERE product_id=? ORDER BY sort, id LIMIT 1', [$id]);
        // colour → image
        $colorImg = (array)($_POST['color_img'] ?? []);
        $keep = []; $i = 0;
        foreach ($vrows as $v) {
            $sku = $v['sku'] ?: strtoupper($base . ($v['color'] ? '-' . substr(preg_replace('/[^A-Za-z0-9]/', '', $v['color']), 0, 4) : '') . ($v['size'] ? '-' . preg_replace('/[^A-Za-z0-9]/', '', $v['size']) : ''));
            $n = 1; $try = $sku;
            while (val('SELECT id FROM variants WHERE sku=? AND NOT (product_id=? AND color=? AND size=?)', [$try, $id, $v['color'], $v['size']])) $try = $sku . '-' . ++$n;
            $imgId = (int)($colorImg[$v['color']] ?? 0);
            $imgId = $imgId && val('SELECT 1 FROM product_images WHERE id=? AND product_id=?', [$imgId, $id]) ? $imgId : null;
            $ex = row('SELECT * FROM variants WHERE product_id=? AND color=? AND size=? FOR UPDATE', [$id, $v['color'], $v['size']]);
            $data = ['color_hex' => $v['color_hex'], 'sku' => $try, 'price' => $v['price'], 'active' => 1, 'sort' => $i++, 'image_id' => $imgId];
            if ($ex) {
                update('variants', $data, 'id=?', [$ex['id']]);
                if ((int)$ex['stock'] !== $v['stock'] || !$ex['active']) {
                    q('UPDATE variants SET stock=? WHERE id=?', [$v['stock'], $ex['id']]);
                    insert('stock_log', ['variant_id' => $ex['id'], 'product_id' => $id, 'change_qty' => $v['stock'] - (int)$ex['stock'], 'stock_after' => $v['stock'], 'reason' => 'manual', 'ref' => 'product edit', 'admin_id' => $me['id'], 'admin_name' => $me['name']]);
                }
                $keep[] = (int)$ex['id'];
            } else {
                $vid = insert('variants', $data + ['product_id' => $id, 'color' => $v['color'], 'size' => $v['size'], 'stock' => $v['stock']]);
                if ($v['stock']) insert('stock_log', ['variant_id' => $vid, 'product_id' => $id, 'change_qty' => $v['stock'], 'stock_after' => $v['stock'], 'reason' => 'manual', 'ref' => 'new variant', 'admin_id' => $me['id'], 'admin_name' => $me['name']]);
                $keep[] = $vid;
            }
        }
        // Variants removed from the matrix: delete if never ordered, otherwise hide
        foreach (rows('SELECT id FROM variants WHERE product_id=? AND id NOT IN (' . implode(',', $keep ?: [0]) . ')', [$id]) as $gone) {
            if (val('SELECT 1 FROM order_items WHERE variant_id=?', [$gone['id']])) q('UPDATE variants SET active=0, stock=0 WHERE id=?', [$gone['id']]);
            else q('DELETE FROM variants WHERE id=?', [$gone['id']]);
        }
        q('DELETE FROM product_collections WHERE product_id=?', [$id]);
        foreach (array_unique(array_map('intval', (array)($_POST['collections'] ?? []))) as $cid) if (val('SELECT 1 FROM collections WHERE id=?', [$cid])) insert('product_collections', ['product_id' => $id, 'collection_id' => $cid]);
        return $id;
    });
    clear_old();
    if (!$old) log_activity('Product created', 'product', $pid, $d['name']);
    else {
        log_activity('Product updated', 'product', $pid, $d['name']);
        if ((float)$old['price'] !== (float)$d['price'] || (float)$old['sale_price'] !== (float)$d['sale_price']) log_activity('Price changed', 'product', $pid, $d['name'] . ': ' . $old['price'] . '/' . ($old['sale_price'] ?? '-') . ' → ' . $d['price'] . '/' . ($d['sale_price'] ?? '-'));
    }
    flash('success', 'Product saved.' . ($d['status'] !== 'active' ? ' It is hidden from the website until Status is “Active”.' : ''));
    redirect('/admin/products/' . $pid . '/edit');
}

/* ---------- orders ---------- */
route('GET', '/admin/orders', function () {
    require_admin('orders');
    $where = ['1=1']; $p = [];
    $st = get('status');
    if ($st !== '' && in_array($st, ORDER_STATUSES, true)) { $where[] = 'status=?'; $p[] = $st; }
    if (($q = get('q')) !== '') { $where[] = '(order_no LIKE ? OR name LIKE ? OR phone LIKE ? OR email LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%", "%$q%"); }
    $w = implode(' AND ', $where);
    $pg = paginate((int)val("SELECT COUNT(*) FROM orders WHERE $w", $p), 30, (int)get('page', 1));
    $counts = [];
    foreach (rows('SELECT status, COUNT(*) n FROM orders GROUP BY status') as $r) $counts[$r['status']] = (int)$r['n'];
    admin_render('orders', ['title' => 'Orders', 'list' => rows("SELECT * FROM orders WHERE $w ORDER BY id DESC LIMIT {$pg['per']} OFFSET {$pg['offset']}", $p), 'pg' => $pg, 'counts' => $counts]);
});
route('GET', '/admin/orders/{id}', function ($id) {
    require_admin('orders');
    $o = row('SELECT * FROM orders WHERE id=?', [(int)$id]) ?? not_found();
    if (!$o['seen']) q('UPDATE orders SET seen=1 WHERE id=?', [$o['id']]);
    admin_render('order', ['title' => 'Order ' . $o['order_no'], 'o' => $o, 'items' => order_items((int)$o['id']), 'history' => rows('SELECT * FROM order_history WHERE order_id=? ORDER BY id DESC', [$o['id']])]);
});
route('POST', '/admin/orders/{id}', function ($id) {
    $me = require_admin('orders');
    $o = row('SELECT * FROM orders WHERE id=?', [(int)$id]) ?? not_found();
    $act = post('action');
    if ($act === 'status') {
        $err = change_order_status((int)$o['id'], post('status'), post('note'));
        flash($err ? 'error' : 'success', $err ?: 'Status updated.');
    } elseif ($act === 'payment' && in_array(post('payment_status'), ['pending', 'paid', 'failed', 'refunded'], true)) {
        set_payment_status((int)$o['id'], post('payment_status'), 'Changed by admin');
        log_activity('Payment status changed', 'order', (int)$o['id'], $o['order_no'] . ' → ' . post('payment_status'));
        flash('success', 'Payment status updated.');
    } elseif ($act === 'tracking') {
        update('orders', ['courier' => mb_substr(post('courier'), 0, 60), 'tracking_no' => mb_substr(post('tracking_no'), 0, 120)], 'id=?', [$o['id']]);
        insert('order_history', ['order_id' => $o['id'], 'status' => 'tracking', 'note' => post('courier') . ' ' . post('tracking_no'), 'admin_name' => $me['name']]);
        flash('success', 'Tracking saved.');
    } elseif ($act === 'note') {
        update('orders', ['admin_note' => mb_substr(post('admin_note'), 0, 5000)], 'id=?', [$o['id']]);
        flash('success', 'Note saved.');
    }
    redirect('/admin/orders/' . $o['id']);
});
