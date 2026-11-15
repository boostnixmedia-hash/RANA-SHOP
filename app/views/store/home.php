<?php
function sec_attr(array $s, string $extra = ''): string {
    $style = '';
    if (!empty($s['bg'])) $style .= 'background:' . safe_color($s['bg'], 'transparent') . ';';
    if (!empty($s['color'])) $style .= 'color:' . safe_color($s['color'], 'inherit') . ';--text:' . safe_color($s['color'], 'inherit') . ';';
    $sp = $s['spacing'] ?? 'normal';
    return 'class="sec sp-' . e($sp) . ($extra ? ' ' . $extra : '') . ($s['bg'] ?? '' ? ' has-bg' : '') . '"' . ($style ? ' style="' . e($style) . '"' : '');
}
function sec_head(array $s): string {
    if (empty($s['heading']) && empty($s['subtitle'])) return '';
    $h = '<div class="sec-head"><div>';
    if (!empty($s['heading'])) $h .= '<h2>' . e($s['heading']) . '</h2>';
    if (!empty($s['subtitle'])) $h .= '<p>' . e($s['subtitle']) . '</p>';
    $h .= '</div>';
    if (!empty($s['button_text']) && !empty($s['button_link'])) $h .= '<a class="link-arrow" href="' . e(url($s['button_link'])) . '">' . e($s['button_text']) . ' ' . icon('arrow-right', 18) . '</a>';
    return $h . '</div>';
}
function sec_products(array $items, array $s): string {
    if (!$items) return '<p class="muted">No products yet.</p>';
    $cls = ($s['layout'] ?? 'grid') === 'carousel' ? 'rail' : 'grid cols-' . (int)($s['columns'] ?? 4);
    $h = '<div class="' . $cls . '">';
    foreach ($items as $p) $h .= view('store/partials/product_card', ['p' => $p]);
    return $h . '</div>';
}
foreach ($sections as $sec) {
    if (empty($sec['visible'])) continue;
    $type = preg_replace('/[^a-z_]/', '', $sec['type'] ?? '');
    $file = __DIR__ . '/sections/' . $type . '.php';
    if (!is_file($file)) continue;
    $s = $sec['settings'] ?? [];
    $sid = 's-' . preg_replace('/[^a-z0-9]/', '', $sec['id'] ?? '');
    include $file;
}
if (!$sections): ?>
<section class="sec"><div class="wrap center"><h1>Welcome</h1><p class="muted">Add homepage sections from Admin → Homepage.</p></div></section>
<?php endif;
