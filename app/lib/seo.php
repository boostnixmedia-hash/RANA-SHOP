<?php
declare(strict_types=1);

/** $m: title, desc, image, canonical, type, noindex, jsonld(array of arrays) */
function seo_head(array $m): string {
    $store = setting('store_name');
    $title = $m['title'] ?? setting('seo_title');
    if (!empty($m['title']) && !str_contains($title, $store)) $title .= ' | ' . $store;
    $desc = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($m['desc'] ?? '') ?: setting('seo_desc'))), 0, 300);
    $img = $m['image'] ?? (setting('og_image') ? abs_url('uploads/' . setting('og_image')) : '');
    $canon = $m['canonical'] ?? abs_url(strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/');
    if (base_path() && str_starts_with($canon, abs_url('') . ltrim(base_path(), '/'))) $canon = abs_url(substr(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), strlen(base_path())));
    $h = '<title>' . e($title) . "</title>\n";
    $h .= '<meta name="description" content="' . e($desc) . "\">\n";
    $h .= '<link rel="canonical" href="' . e($canon) . "\">\n";
    if (!empty($m['noindex'])) $h .= "<meta name=\"robots\" content=\"noindex,nofollow\">\n";
    $h .= '<meta property="og:site_name" content="' . e($store) . "\">\n";
    $h .= '<meta property="og:title" content="' . e($title) . "\">\n";
    $h .= '<meta property="og:description" content="' . e($desc) . "\">\n";
    $h .= '<meta property="og:type" content="' . e($m['type'] ?? 'website') . "\">\n";
    $h .= '<meta property="og:url" content="' . e($canon) . "\">\n";
    if ($img) $h .= '<meta property="og:image" content="' . e($img) . "\">\n<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
    if ($v = setting('gsc_verification')) $h .= '<meta name="google-site-verification" content="' . e($v) . "\">\n";
    foreach ($m['jsonld'] ?? [] as $ld) $h .= '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . "</script>\n";
    return $h;
}

function tracking_head(): string {
    $h = '';
    if (($ga = setting('ga4_id')) && preg_match('/^G-[A-Z0-9]+$/', $ga)) {
        $h .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id=$ga\"></script><script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','$ga');</script>\n";
    }
    if (($px = setting('meta_pixel_id')) && preg_match('/^\d{6,20}$/', $px)) {
        $h .= "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','$px');fbq('track','PageView');</script>\n";
    }
    return $h;
}

function product_jsonld(array $p): array {
    $imgs = array_map(fn($i) => abs_url('uploads/' . $i['path']), $p['images']);
    $stock = array_sum(array_column($p['variants'], 'stock'));
    $ld = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p['name'], 'image' => $imgs,
        'description' => strip_tags($p['short_desc'] ?: $p['description'] ?: $p['name']), 'sku' => $p['sku'] ?: ('RS-' . $p['id']),
        'brand' => ['@type' => 'Brand', 'name' => setting('store_name')],
        'offers' => ['@type' => 'Offer', 'url' => abs_url('product/' . $p['slug']), 'priceCurrency' => 'BDT', 'price' => number_format($p['final_price'], 2, '.', ''),
            'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'itemCondition' => 'https://schema.org/NewCondition']];
    $r = row("SELECT AVG(rating) a, COUNT(*) n FROM reviews WHERE product_id=? AND status='approved'", [$p['id']]);
    if ($r && $r['n'] > 0) $ld['aggregateRating'] = ['@type' => 'AggregateRating', 'ratingValue' => round((float)$r['a'], 1), 'reviewCount' => (int)$r['n']];
    return $ld;
}

function sitemap_xml(): string {
    $u = [['', null], ['shop', null], ['collections', null], ['new-arrivals', null], ['best-sellers', null], ['featured', null]];
    foreach (categories_all() as $c) $u[] = ['category/' . $c['slug'], null];
    foreach (collections_visible() as $c) $u[] = ['collection/' . $c['slug'], null];
    foreach (rows('SELECT slug, updated_at FROM products p WHERE ' . PUBLISHED_SQL) as $p) $u[] = ['product/' . $p['slug'], $p['updated_at']];
    foreach (rows('SELECT slug, updated_at FROM pages WHERE visible=1') as $p) $u[] = ['page/' . $p['slug'], $p['updated_at']];
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
    foreach ($u as [$path, $mod]) $x .= '<url><loc>' . e(abs_url($path)) . '</loc>' . ($mod ? '<lastmod>' . date('Y-m-d', strtotime($mod)) . '</lastmod>' : '') . "</url>\n";
    return $x . '</urlset>';
}
function robots_txt(): string {
    return "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /order/\nDisallow: /pay/\nAllow: /\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
}
