<?php
declare(strict_types=1);
/* Default content for a fresh install. Everything here is editable later in Admin. */

function demo_art(string $type, string $color, string $bg = '#f1ece4', bool $check = false): string {
    $d = shade_hex($color, -30); $l = shade_hex($color, 22);
    $light = hexdec(substr($color, 1)) > 0xbbbbbb; $st = $light ? '#c9c2b6' : shade_hex($color, -55);
    $body = match ($type) {
        'pant' => "<path d='M240 150h120l14 330h-62l-12-210-12 210h-62z' fill='$color' stroke='$st' stroke-width='2'/><path d='M240 150h120v22H240z' fill='$d'/><path d='M300 172v60' stroke='$d' stroke-width='2'/>",
        'shoe' => "<path d='M150 380c0-40 30-60 60-80l40-30c20 30 70 40 110 30l30 60c50 10 90 30 90 60v20H150z' fill='$color' stroke='$st' stroke-width='2'/><path d='M150 440h330v22H150z' fill='" . ($light ? '#e9e4dc' : '#f4f1ec') . "' stroke='$st' stroke-width='2'/><path d='M255 290l25 30M275 282l25 30M295 278l22 28' stroke='$l' stroke-width='5' stroke-linecap='round'/>",
        default => "<path d='M255 140 215 156 175 212l34 24 22-26v260h138V210l22 26 34-24-40-56-40-16q-40 30-90 0z' fill='$color' stroke='$st' stroke-width='2'/>"
            . ($type === 'shirt' ? "<path d='M300 168v300' stroke='$d' stroke-width='2'/><path d='M255 140l45 30 45-30-10-8-35 20-35-20z' fill='$l' stroke='$st' stroke-width='2'/>" . implode('', array_map(fn($y) => "<circle cx='306' cy='$y' r='3.5' fill='" . ($light ? '#999' : $l) . "'/>", [200, 240, 280, 320, 360, 400])) : '')
            . ($type === 'polo' ? "<path d='M265 142l35 28 35-28-8-6-27 18-27-18z' fill='$l' stroke='$st' stroke-width='2'/><path d='M300 170v42' stroke='$d' stroke-width='2'/>" : '')
            . ($type === 'tee' ? "<path d='M262 144q38 26 76 0' fill='none' stroke='$d' stroke-width='5'/>" : '')
            . ($check ? "<g opacity='.3' stroke='" . shade_hex($color, -80) . "' stroke-width='6'><path d='M231 230h138M231 290h138M231 350h138M231 410h138M265 170v300M300 170v300M335 170v300'/></g>" : ''),
    };
    return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 600 800'><rect width='600' height='800' fill='$bg'/><g transform='translate(0 120)'>$body</g></svg>";
}
function shade_hex(string $hex, int $amt): string {
    $n = hexdec(ltrim($hex, '#'));
    $r = max(0, min(255, ($n >> 16) + $amt)); $g = max(0, min(255, (($n >> 8) & 255) + $amt)); $b = max(0, min(255, ($n & 255) + $amt));
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}
function demo_file(string $name, string $svg): string {
    $dir = ROOT_DIR . '/uploads/demo';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents("$dir/$name.svg", $svg);
    return "demo/$name.svg";
}
function demo_photo(string $name): string {
    $dir = ROOT_DIR . '/uploads/demo';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    copy(__DIR__ . "/seed-photos/$name.webp", "$dir/photo-$name.webp");
    return "demo/photo-$name.webp";
}
function demo_banner(string $name, string $bg1, string $bg2, array $items): string {
    $g = '';
    foreach ($items as [$type, $color, $x, $y, $s, $rot]) {
        $inner = preg_replace(["~<svg[^>]*>~", "~</svg>~", "~<rect width='600' height='800'[^>]*/>~"], '', demo_art($type, $color));
        $g .= "<g transform='translate($x $y) rotate($rot 300 400) scale($s)'>$inner</g>";
    }
    return demo_file($name, "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1600 900'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='$bg1'/><stop offset='1' stop-color='$bg2'/></linearGradient></defs><rect width='1600' height='900' fill='url(#g)'/>$g</svg>");
}

function seed_all(): void {
    // ---- Settings & theme
    set_setting('theme', theme_defaults());
    set_setting('theme_draft', theme_defaults());

    // ---- Categories (owner asked for 4)
    $cats = [];
    foreach ([['Shirts', 'shirts', 'Formal and casual shirts in breathable cotton.', 'shirt', '#e7edf5'], ['Pants', 'pants', 'Chinos, jeans, cargos and formal trousers.', 'pant', '#2f4766'],
              ['T-Shirts', 't-shirts', 'Everyday tees and polos in heavyweight cotton.', 'tee', '#1c1c1c'], ['Shoes', 'shoes', 'Sneakers, loafers and formal shoes.', 'shoe', '#6b4a32']] as $i => [$n, $s, $d, $t, $c]) {
        $photo = ['shirts' => 'overshirt', 'pants' => 'pant', 't-shirts' => 'tee', 'shoes' => 'shoe'][$s] ?? null;
        $img = $photo ? demo_photo($photo) : demo_file('cat-' . $s, demo_art($t, $c, '#ebe5db'));
        $cats[$s] = insert('categories', ['name' => $n, 'slug' => $s, 'description' => $d, 'image' => $img, 'sort' => $i, 'seo_title' => $n . ' for Men', 'seo_desc' => $d]);
    }
    // ---- Collections
    $cols = [];
    foreach ([['New Season 2027', 'new-season-2027', 'Fresh colours and clean cuts for the new season.', 'seasonal', 1, ['tee', '#d9cbb3']],
              ['Premium Essentials', 'premium-essentials', 'The pieces you will wear every week.', 'regular', 1, ['shirt', '#ffffff']],
              ['Limited Edition', 'limited-edition', 'Small runs. Once they are gone, they are gone.', 'limited', 1, ['polo', '#1f4d3c']]] as $i => [$n, $s, $d, $k, $f, $art]) {
        $cover = demo_banner('col-' . $s, '#efe9df', '#e2d8c8', [[$art[0], $art[1], 500, 40, 1.1, -6]]);
        $cols[$s] = insert('collections', ['name' => $n, 'slug' => $s, 'description' => $d, 'cover' => $cover, 'kind' => $k, 'featured' => $f, 'sort' => $i]);
    }
    // ---- Products with size × colour variants
    $tops = ['S', 'M', 'L', 'XL', 'XXL']; $waist = ['28', '30', '32', '34', '36']; $shoe = ['39', '40', '41', '42', '43', '44'];
    $products = [
        ['Oxford Button-Down Shirt', 'shirts', 'shirt', 1890, 1590, 900, $tops, [['White', '#f7f7f5'], ['Sky Blue', '#bcd3ea']], 'Crisp 100% cotton Oxford with a soft hand-feel. Button-down collar, regular fit.', ['premium-essentials'], 1],
        ['Linen Blend Casual Shirt', 'shirts', 'shirt', 2190, null, 1100, $tops, [['Sand', '#d9c8a9'], ['Olive', '#6b6b47']], 'Breathable linen-cotton blend made for hot Dhaka days.', ['new-season-2027'], 1],
        ['Flannel Check Shirt', 'shirts', 'shirt', 1750, 1490, 820, $tops, [['Rust', '#a5462d']], 'Brushed flannel in a classic check. Easy layering piece.', [], 0, true],
        ['Slim Fit Formal Shirt', 'shirts', 'shirt', 1650, null, 780, $tops, [['Black', '#1b1b1b'], ['White', '#fafafa']], 'Stretch poplin with a sharp slim cut for office and events.', ['premium-essentials'], 0],
        ['Stretch Chino Pant', 'pants', 'pant', 2250, 1950, 1050, $waist, [['Beige', '#c8b48f'], ['Navy', '#22324d']], 'Soft stretch twill chino with a tapered leg.', ['premium-essentials'], 1],
        ['Classic Denim Jeans', 'pants', 'pant', 2490, null, 1200, $waist, [['Indigo', '#2e4a70'], ['Black', '#202024']], '12oz stretch denim, slim straight fit.', ['new-season-2027'], 1],
        ['Utility Cargo Pant', 'pants', 'pant', 2390, 2090, 1150, $waist, [['Khaki', '#8d8461'], ['Black', '#1d1d1d']], 'Relaxed cargo with six pockets and adjustable hem.', ['limited-edition'], 0],
        ['Formal Trouser', 'pants', 'pant', 1990, null, 950, $waist, [['Charcoal', '#38393d']], 'Wrinkle-resistant formal trouser with a clean crease.', [], 0],
        ['Heavyweight Oversized Tee', 't-shirts', 'tee', 990, 850, 380, $tops, [['Black', '#161616'], ['White', '#f6f4ef'], ['Sand', '#d8c8ac']], '240 GSM combed cotton, dropped shoulders, boxy fit.', ['new-season-2027'], 1],
        ['Essential Crew Neck Tee', 't-shirts', 'tee', 690, null, 260, $tops, [['Navy', '#1f2b45'], ['Grey', '#9a9a9a']], '180 GSM soft cotton for everyday wear.', ['premium-essentials'], 0],
        ['Piqué Polo Shirt', 't-shirts', 'polo', 1290, 1090, 520, $tops, [['Forest', '#1f4d3c'], ['White', '#f8f8f6']], 'Classic piqué polo with a ribbed collar.', ['limited-edition'], 1],
        ['Graphic Print Tee', 't-shirts', 'tee', 890, null, 340, $tops, [['Maroon', '#6e1f2a']], 'Soft-hand screen print on combed cotton.', [], 0],
        ['Leather Low-Top Sneaker', 'shoes', 'shoe', 3990, 3490, 2100, $shoe, [['White', '#f4f2ee'], ['Black', '#1a1a1a']], 'Full-grain leather upper with a cushioned rubber sole.', ['new-season-2027'], 1],
        ['Suede Penny Loafer', 'shoes', 'shoe', 4290, null, 2300, $shoe, [['Tan', '#9b6b43']], 'Soft suede loafer, hand-stitched, leather lining.', ['limited-edition'], 1],
        ['Oxford Formal Shoe', 'shoes', 'shoe', 4590, 3990, 2500, $shoe, [['Black', '#151515'], ['Brown', '#5a3a26']], 'Polished leather Oxford for weddings and office.', ['premium-essentials'], 0],
        ['Canvas Everyday Sneaker', 'shoes', 'shoe', 2190, null, 1100, $shoe, [['Navy', '#283a5c'], ['Off White', '#efebe2']], 'Lightweight canvas sneaker for everyday wear.', [], 0],
    ];
    $n = 0;
    foreach ($products as [$name, $cat, $type, $price, $sale, $cost, $sizes, $colors, $desc, $inCols, $featured]) {
        $n++;
        $slug = slugify($name);
        $pid = insert('products', ['name' => $name, 'slug' => $slug, 'sku' => 'RS' . str_pad((string)$n, 4, '0', STR_PAD_LEFT), 'short_desc' => $desc,
            'description' => '<p>' . e($desc) . '</p><ul><li>Premium quality fabric</li><li>Colour-fast, pre-washed</li><li>Machine wash cold, dry in shade</li></ul>',
            'price' => $price, 'sale_price' => $sale, 'cost_price' => $cost, 'category_id' => $cats[$cat], 'tags' => strtolower($cat . ', ' . $type . ', men'),
            'status' => 'active', 'featured' => $featured, 'sold_count' => random_int(0, 60), 'created_at' => date('Y-m-d H:i:s', time() - $n * 86400)]);
        $vs = 0;
        foreach ($colors as $ci => [$cname, $hex]) {
            $isCheck = str_contains($name, 'Check');
            $img = demo_file("p$n-$ci", demo_art($type, $hex, '#f1ece4', $isCheck));
            $imgId = insert('product_images', ['product_id' => $pid, 'path' => $img, 'alt' => "$name - $cname", 'sort' => $ci, 'is_featured' => $ci === 0 ? 1 : 0]);
            foreach ($sizes as $s) {
                insert('variants', ['product_id' => $pid, 'color' => $cname, 'color_hex' => $hex, 'size' => $s, 'sku' => 'RS' . str_pad((string)$n, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(preg_replace('/\s/', '', $cname), 0, 3)) . '-' . $s,
                    'stock' => random_int(0, 4) === 0 ? random_int(0, 3) : random_int(6, 25), 'image_id' => $imgId, 'sort' => $vs++]);
            }
        }
        foreach ($inCols as $i => $cs) insert('product_collections', ['product_id' => $pid, 'collection_id' => $cols[$cs], 'sort' => $i]);
    }

    // ---- Real photo products (owner's own photos), newest and featured
    $real = [
        ['Iridescent High-Top Sneaker', 'shoes', 'shoe', 8990, 7990, 5200, ['40', '41', '42', '43', '44'], ['Olive Gold', '#6b6a2e'], 'Patent high-top sneaker with an iridescent finish and a translucent icy sole.', ['limited-edition']],
        ['Embroidered Cuff Panjabi', 'shirts', 'panjabi', 3490, null, 1800, ['M', 'L', 'XL', 'XXL'], ['Silver Grey', '#a9a9b3'], 'Fine-weave panjabi with hand-embroidered cuffs. Made for Eid and weddings.', ['limited-edition']],
        ['Butterfly Graphic Oversized Tee', 't-shirts', 'tee', 1290, 990, 450, $tops, ['Stone', '#a59d94'], '240 GSM oversized tee with a soft-focus butterfly print.', ['new-season-2027']],
        ['Wide-Leg Drawstring Pant', 'pants', 'pant', 2190, null, 1000, $waist, ['Coffee', '#7a5a3a'], 'Relaxed wide-leg pant in soft twill with an elastic drawstring waist.', ['new-season-2027']],
        ['Tie-Dye Overshirt', 'shirts', 'overshirt', 2490, 1990, 1150, $tops, ['Charcoal', '#2e2e2e'], 'Heavyweight cotton overshirt with a tonal tie-dye wash. Snap buttons and a chest pocket.', ['new-season-2027', 'premium-essentials']],
    ];
    foreach ($real as $k => [$name, $cat, $photo, $price, $sale, $cost, $sizes, [$cname, $hex], $desc, $inCols]) {
        $n++; $sku = 'RS' . str_pad((string)$n, 4, '0', STR_PAD_LEFT);
        $pid = insert('products', ['name' => $name, 'slug' => slugify($name), 'sku' => $sku, 'short_desc' => $desc,
            'description' => '<p>' . e($desc) . '</p><ul><li>Premium quality fabric</li><li>Colour-fast, pre-washed</li><li>Machine wash cold, dry in shade</li></ul>',
            'price' => $price, 'sale_price' => $sale, 'cost_price' => $cost, 'category_id' => $cats[$cat], 'tags' => strtolower($cat . ', ' . $photo . ', men'),
            'status' => 'active', 'featured' => 1, 'sold_count' => random_int(20, 90), 'created_at' => date('Y-m-d H:i:s', time() - $k * 3600)]);
        $imgId = insert('product_images', ['product_id' => $pid, 'path' => demo_photo($photo), 'alt' => "$name - $cname", 'sort' => 0, 'is_featured' => 1]);
        foreach ($sizes as $vs => $s) insert('variants', ['product_id' => $pid, 'color' => $cname, 'color_hex' => $hex, 'size' => $s, 'sku' => "$sku-$s", 'stock' => 12, 'image_id' => $imgId, 'sort' => $vs]);
        foreach ($inCols as $i => $cs) insert('product_collections', ['product_id' => $pid, 'collection_id' => $cols[$cs], 'sort' => $i]);
    }

    // ---- Menus
    foreach ([['header', 'New In', '/new-arrivals'], ['header', 'Shirts', '/category/shirts'], ['header', 'Pants', '/category/pants'], ['header', 'T-Shirts', '/category/t-shirts'], ['header', 'Shoes', '/category/shoes'], ['header', 'Collections', '/collections'],
              ['footer1', 'Shop All', '/shop'], ['footer1', 'New Arrivals', '/new-arrivals'], ['footer1', 'Best Sellers', '/best-sellers'], ['footer1', 'Collections', '/collections'],
              ['footer2', 'About Us', '/about'], ['footer2', 'Contact Us', '/contact'], ['footer2', 'FAQ', '/faq'], ['footer2', 'Track Order', '/track'],
              ['footer3', 'Shipping Policy', '/shipping-policy'], ['footer3', 'Return & Refund', '/return-policy'], ['footer3', 'Privacy Policy', '/privacy-policy'], ['footer3', 'Terms & Conditions', '/terms']] as $i => [$loc, $l, $u])
        insert('menus', ['location' => $loc, 'label' => $l, 'url' => $u, 'sort' => $i]);
    set_setting('footer_titles', ['footer1' => 'Shop', 'footer2' => 'Help', 'footer3' => 'Policies']);

    // ---- Pages
    $store = setting('store_name');
    $pages = [
        'about' => ['About Us', "<h2>Made for the everyday man</h2><p>$store started with a simple idea: well-made clothes should not cost a fortune. We design shirts, pants, t-shirts and shoes in clean, timeless styles and make them with fabrics that feel good in Bangladesh's climate.</p><h3>What we care about</h3><ul><li><b>Fabric first.</b> Every piece is wear-tested before it reaches you.</li><li><b>Honest prices.</b> No inflated MRP, no fake discounts.</li><li><b>Real service.</b> Talk to a real person on phone or WhatsApp.</li></ul>"],
        'contact' => ['Contact Us', '<p>Questions about sizes, an order or a return? Send us a message and we will reply within one working day.</p>'],
        'faq' => ['Frequently Asked Questions', '<h3>How long does delivery take?</h3><p>Inside Dhaka 1–2 days, outside Dhaka 2–4 days.</p><h3>Can I pay on delivery?</h3><p>Yes. Cash on Delivery is available all over Bangladesh. You can also pay by bKash, Nagad or card where enabled.</p><h3>What if the size doesn\'t fit?</h3><p>You can exchange within 7 days of delivery if the item is unworn with tags. See our Return &amp; Refund policy.</p><h3>How do I track my order?</h3><p>Use the Track Order page with your order ID and phone number, or log in to your account.</p>'],
        'privacy-policy' => ['Privacy Policy', "<p>This policy explains how $store collects and uses your information.</p><h3>What we collect</h3><p>Your name, email, phone number and delivery address when you place an order or create an account, and basic analytics about how the site is used.</p><h3>How we use it</h3><p>To deliver your order, send order updates, provide support and improve our store. We never sell your personal data.</p><h3>Payments</h3><p>Online payments are processed by bKash, Nagad or SSLCommerz. We never see or store your card or wallet PIN.</p><h3>Your rights</h3><p>You can ask us to update or delete your data at any time by contacting us.</p>"],
        'terms' => ['Terms & Conditions', "<p>By using this website you agree to these terms.</p><h3>Orders</h3><p>An order is confirmed when we confirm it by phone, SMS or email. We may cancel an order if an item is out of stock or the information provided is incorrect.</p><h3>Prices</h3><p>All prices are in Bangladeshi Taka (BDT) and include VAT where applicable. Delivery charges are shown at checkout.</p><h3>Product images</h3><p>Colours may look slightly different on different screens.</p>"],
        'shipping-policy' => ['Shipping Policy', '<ul><li>Inside Dhaka: delivered in 1–2 working days.</li><li>Outside Dhaka: delivered in 2–4 working days by courier.</li><li>Delivery charges are shown at checkout before you place the order.</li><li>You will receive the courier tracking number by email when your order ships.</li></ul>'],
        'return-policy' => ['Return & Refund Policy', '<h3>Exchanges</h3><p>Exchange within 7 days of delivery for a different size or colour, if the item is unworn, unwashed and has its tags.</p><h3>Refunds</h3><p>If an item arrives damaged or wrong, we will replace it or refund you in full. Refunds go back to your original payment method (or bKash for COD orders) within 7 working days.</p><h3>How to request</h3><p>Contact us with your order ID and photos of the item.</p>'],
    ];
    foreach ($pages as $slug => [$t, $b]) insert('pages', ['slug' => $slug, 'title' => $t, 'body' => $b]);

    // ---- Email templates
    foreach (default_email_templates() as $k => [$subject, $body]) insert('email_templates', ['tkey' => $k, 'name' => email_template_keys()[$k][0], 'subject' => $subject, 'body' => $body]);

    // ---- Homepage
    $hero = demo_photo('hero');
    $story = demo_banner('story', '#1a1a1a', '#2a2622', [['polo', '#b89b72', 500, 80, 1.2, -4]]);
    $two = demo_banner('two', '#efe9df', '#e3d9c9', [['shoe', '#9b6b43', 480, 60, 1.3, 0]]);
    $sid = fn() => substr(rand_token(4), 0, 8);
    $home = [
        ['id' => $sid(), 'type' => 'hero', 'visible' => 1, 'settings' => ['image' => $hero, 'eyebrow' => 'New Season 2027', 'heading' => 'Quiet luxury, everyday prices', 'subtitle' => 'Shirts, pants, tees and shoes made from fabrics that feel as good as they look.', 'button_text' => 'Shop New In', 'button_link' => '/new-arrivals', 'button2_text' => 'Explore Collections', 'button2_link' => '/collections', 'align' => 'left', 'height' => 'large', 'overlay' => '0', 'color' => '#111111']],
        ['id' => $sid(), 'type' => 'features', 'visible' => 1, 'settings' => ['items' => [['icon' => 'truck', 'title' => 'Nationwide delivery', 'text' => '1–2 days inside Dhaka'], ['icon' => 'cash', 'title' => 'Cash on Delivery', 'text' => 'Pay when it arrives'], ['icon' => 'refresh', 'title' => '7-day exchange', 'text' => 'Size didn\'t fit? Swap it'], ['icon' => 'shield', 'title' => 'Secure payment', 'text' => 'bKash, Nagad & cards']], 'bg' => '#f5f1ea', 'color' => '#111111', 'spacing' => 'tight']],
        ['id' => $sid(), 'type' => 'shop_by_category', 'visible' => 1, 'settings' => ['heading' => 'Shop by Category', 'subtitle' => '', 'category_ids' => [], 'style' => 'tiles', 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'new_arrivals', 'visible' => 1, 'settings' => ['heading' => 'New Arrivals', 'subtitle' => 'Just landed this week', 'limit' => '8', 'layout' => 'grid', 'columns' => '4', 'button_text' => 'View all', 'button_link' => '/new-arrivals', 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'promo_banner', 'visible' => 1, 'settings' => ['heading' => 'Get 10% off your first order', 'subtitle' => 'Use the code at checkout.', 'code' => 'WELCOME10', 'button_text' => 'Start shopping', 'button_link' => '/shop', 'bg' => '#111111', 'color' => '#ffffff', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'featured_collection', 'visible' => 1, 'settings' => ['collection_id' => (string)$cols['premium-essentials'], 'heading' => 'Premium Essentials', 'subtitle' => 'The pieces you will wear every week', 'limit' => '8', 'layout' => 'carousel', 'columns' => '4', 'button_text' => 'Shop the collection', 'button_link' => '/collection/premium-essentials', 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'two_column', 'visible' => 1, 'settings' => ['image' => $two, 'eyebrow' => 'Footwear', 'heading' => 'Step out in leather', 'text' => '<p>Hand-finished loafers, sneakers and formal shoes, built to last for years.</p>', 'button_text' => 'Shop Shoes', 'button_link' => '/category/shoes', 'image_side' => 'left', 'bg' => '#f5f1ea', 'color' => '#111111', 'spacing' => 'none']],
        ['id' => $sid(), 'type' => 'best_sellers', 'visible' => 1, 'settings' => ['heading' => 'Best Sellers', 'subtitle' => 'What everyone is wearing', 'limit' => '8', 'layout' => 'grid', 'columns' => '4', 'button_text' => 'View all', 'button_link' => '/best-sellers', 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'featured_collections', 'visible' => 1, 'settings' => ['heading' => 'Collections', 'subtitle' => '', 'collection_ids' => [], 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'brand_story', 'visible' => 1, 'settings' => ['image' => $story, 'eyebrow' => 'Our story', 'heading' => 'Designed in Dhaka', 'text' => '<p>We started ' . e($store) . ' to make clean, well-made menswear that fits Bangladeshi weather and budgets. No logos shouting, no fast-fashion shortcuts.</p>', 'button_text' => 'About us', 'button_link' => '/about', 'bg' => '#111111', 'color' => '#ffffff', 'spacing' => 'none']],
        ['id' => $sid(), 'type' => 'testimonials', 'visible' => 1, 'settings' => ['heading' => 'What customers say', 'items' => [['name' => 'Tanvir H.', 'location' => 'Dhaka', 'rating' => '5', 'text' => 'Fabric quality is excellent and the fit is spot on. Delivered the next day.', 'avatar' => ''], ['name' => 'Arif R.', 'location' => 'Chattogram', 'rating' => '5', 'text' => 'Ordered two shirts and a chino. Exactly like the photos. Will order again.', 'avatar' => ''], ['name' => 'Sakib M.', 'location' => 'Sylhet', 'rating' => '4', 'text' => 'Exchange for a bigger size was quick and easy.', 'avatar' => '']], 'bg' => '', 'color' => '', 'spacing' => 'normal']],
        ['id' => $sid(), 'type' => 'newsletter', 'visible' => 1, 'settings' => ['heading' => 'Join the list', 'subtitle' => 'New drops and members-only offers. No spam.', 'button_text' => 'Subscribe', 'bg' => '#f5f1ea', 'color' => '#111111', 'spacing' => 'normal']],
    ];
    set_setting('home_live', $home);
    set_setting('home_draft', $home);

    // ---- Coupon
    insert('coupons', ['code' => 'WELCOME10', 'description' => '10% off first order', 'type' => 'percent', 'value' => 10, 'min_purchase' => 1000, 'max_discount' => 500, 'per_customer_limit' => 1]);
}

function default_email_templates(): array {
    $hi = '<p>Hi {{customer_name}},</p>';
    $summary = '<p style="margin:18px 0 6px"><b>Order {{order_no}}</b> · {{order_date}}</p>{{items_table}}
<table role="presentation" width="100%" style="font-size:14px"><tr><td>Subtotal</td><td align="right">{{subtotal}}</td></tr><tr><td>Discount</td><td align="right">-{{discount}}</td></tr><tr><td>Delivery</td><td align="right">{{shipping}}</td></tr><tr><td><b>Total</b></td><td align="right"><b>{{order_total}}</b></td></tr></table>
<p style="margin-top:18px"><b>Payment:</b> {{payment_method}} ({{payment_status}})<br><b>Deliver to:</b> {{customer_name}}, {{customer_phone}}<br>{{delivery_address}}</p>';
    $btn = fn($t) => '<p style="margin:24px 0"><a href="{{tracking_link}}" style="background:#111;color:#fff;padding:12px 22px;text-decoration:none;display:inline-block">' . $t . '</a></p>';
    return [
        'order_received' => ['We received your order {{order_no}}', $hi . '<p>Thank you for shopping with {{store_name}}! We have received your order and will call you shortly to confirm it.</p>' . $btn('Track your order') . $summary],
        'order_confirmed' => ['Your order {{order_no}} is confirmed', $hi . '<p>Good news: your order is confirmed and we are getting it ready.</p>' . $btn('View order') . $summary],
        'order_processing' => ['Your order {{order_no}} is being packed', $hi . '<p>Your order is being packed with care. We will email you the tracking number as soon as it ships.</p>' . $btn('View order') . $summary],
        'order_shipped' => ['Your order {{order_no}} is on the way', $hi . '<p>Your order has been handed to the courier.</p><p><b>Courier:</b> {{courier}}<br><b>Tracking number:</b> {{tracking_no}}</p>' . $btn('Track your order') . $summary],
        'order_delivered' => ['Delivered: order {{order_no}}', $hi . '<p>Your order has been delivered. We hope you love it! If anything isn\'t right, just reply to this email.</p>' . $btn('Leave a review') . $summary],
        'order_cancelled' => ['Your order {{order_no}} was cancelled', $hi . '<p>Your order has been cancelled. If you paid online, the refund will be processed to your original payment method. If you didn\'t ask for this, please contact us.</p>' . $summary],
        'refund_update' => ['Refund update for order {{order_no}}', $hi . '<p>A refund of <b>{{order_total}}</b> for your order has been processed. Depending on your bank or wallet it can take 3–7 working days to show.</p>' . $btn('View order') . $summary],
        'password_reset' => ['Reset your {{store_name}} password', $hi . '<p>We received a request to reset your password. This link works for 1 hour:</p><p style="margin:24px 0"><a href="{{reset_link}}" style="background:#111;color:#fff;padding:12px 22px;text-decoration:none;display:inline-block">Reset password</a></p><p style="color:#777">If you didn\'t ask for this, you can ignore this email.</p>'],
        'account_registration' => ['Welcome to {{store_name}}', $hi . '<p>Your account is ready. You can now track orders, save addresses and keep a wishlist.</p><p style="margin:24px 0"><a href="{{account_link}}" style="background:#111;color:#fff;padding:12px 22px;text-decoration:none;display:inline-block">Go to my account</a></p>'],
        'admin_new_order' => ['New order {{order_no}} · {{order_total}} · {{customer_name}}', '<p><b>New order received.</b></p><p><b>{{customer_name}}</b><br>{{customer_phone}} · {{customer_email}}<br>{{delivery_address}}</p><p style="margin:20px 0"><a href="{{admin_order_link}}" style="background:#111;color:#fff;padding:12px 22px;text-decoration:none;display:inline-block">Open order in admin</a></p>' . $summary],
        'contact_message' => ['Contact form: {{subject}}', '<p><b>{{name}}</b> ({{email}}, {{phone}}) wrote:</p><p><b>{{subject}}</b></p><p>{{message}}</p>'],
    ];
}
