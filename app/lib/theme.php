<?php
declare(strict_types=1);

function font_choices(): array {
    return [
        'heading' => ['Cormorant Garamond' => 'Cormorant Garamond (elegant serif)', 'Playfair Display' => 'Playfair Display (classic serif)', 'Bodoni Moda' => 'Bodoni Moda (fashion serif)',
            'Jost' => 'Jost (modern sans)', 'Montserrat' => 'Montserrat (bold sans)', 'Inter' => 'Inter (clean sans)', 'Oswald' => 'Oswald (condensed)'],
        'body' => ['Jost' => 'Jost', 'Inter' => 'Inter', 'DM Sans' => 'DM Sans', 'Poppins' => 'Poppins', 'Hind Siliguri' => 'Hind Siliguri (Bangla friendly)'],
    ];
}

function theme_defaults(): array {
    return [
        'primary' => '#111111', 'secondary' => '#b89b72', 'background' => '#ffffff', 'surface' => '#f5f1ea', 'text' => '#111111', 'muted' => '#6f6a63',
        'button_bg' => '#111111', 'button_text' => '#ffffff', 'sale' => '#b3261e',
        'font_heading' => 'Cormorant Garamond', 'font_body' => 'Jost', 'font_scale' => 'md', 'radius' => '0',
        'card_ratio' => 'portrait', 'card_hover' => 'swap', 'card_quick_add' => 1, 'card_align' => 'left',
        'header_style' => 'center', 'header_sticky' => 1, 'footer_style' => 'dark',
    ];
}

/** Live theme, or the draft when an admin is previewing. */
function theme(): array {
    static $t = null;
    if ($t) return $t;
    $useDraft = !empty($_GET['preview']) && admin() && can('content');
    $saved = setting($useDraft ? 'theme_draft' : 'theme', []) ?: setting('theme', []);
    return $t = array_merge(theme_defaults(), is_array($saved) ? $saved : []);
}

function safe_color(string $c, string $fallback): string { return preg_match('/^#[0-9a-fA-F]{3,8}$/', $c) ? $c : $fallback; }

function theme_css(): string {
    $t = theme(); $d = theme_defaults();
    $scale = ['sm' => '15px', 'md' => '16px', 'lg' => '17px'][$t['font_scale']] ?? '16px';
    $radius = in_array((string)$t['radius'], ['0', '4', '8', '14', '24'], true) ? $t['radius'] . 'px' : '0px';
    $fonts = font_choices();
    $fh = isset($fonts['heading'][$t['font_heading']]) ? $t['font_heading'] : $d['font_heading'];
    $fb = isset($fonts['body'][$t['font_body']]) ? $t['font_body'] : $d['font_body'];
    $vars = [];
    foreach (['primary', 'secondary', 'background', 'surface', 'text', 'muted', 'button_bg', 'button_text', 'sale'] as $k) $vars[] = '--' . str_replace('_', '-', $k) . ':' . safe_color((string)$t[$k], $d[$k]);
    $vars[] = "--radius:$radius"; $vars[] = "--fs:$scale";
    $vars[] = "--font-h:'$fh',Georgia,serif"; $vars[] = "--font-b:'$fb',system-ui,sans-serif";
    $vars[] = '--card-ratio:' . ($t['card_ratio'] === 'square' ? '1/1' : ($t['card_ratio'] === 'tall' ? '2/3' : '3/4'));
    return ':root{' . implode(';', $vars) . '}';
}
function theme_fonts_link(): string {
    $t = theme();
    $fams = array_unique([$t['font_heading'], $t['font_body']]);
    $q = implode('&', array_map(fn($f) => 'family=' . str_replace(' ', '+', $f) . ':wght@300;400;500;600;700', $fams));
    return '<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?' . e($q) . '&display=swap" rel="stylesheet">';
}

/* ---------------- Homepage sections ---------------- */

/** Field schema per section type: [label, fields[name => [type, label, options?]]] */
function section_types(): array {
    $style = ['bg' => ['color', 'Background colour'], 'color' => ['color', 'Text colour'], 'spacing' => ['select', 'Spacing', ['normal' => 'Normal', 'tight' => 'Tight', 'none' => 'None', 'large' => 'Large']]];
    $btn = ['button_text' => ['text', 'Button text'], 'button_link' => ['link', 'Button link']];
    $grid = ['heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle'], 'limit' => ['number', 'How many products'], 'layout' => ['select', 'Layout', ['grid' => 'Grid', 'carousel' => 'Horizontal scroll']], 'columns' => ['select', 'Columns (desktop)', ['4' => '4', '3' => '3', '5' => '5']]] + $btn;
    return [
        'hero' => ['Hero Banner', ['image' => ['image', 'Image (desktop)'], 'mobile_image' => ['image', 'Image (phone, optional)'], 'eyebrow' => ['text', 'Small text above heading'], 'heading' => ['text', 'Heading'], 'subtitle' => ['textarea', 'Subtitle']] + $btn + ['button2_text' => ['text', 'Second button text'], 'button2_link' => ['link', 'Second button link'],
            'align' => ['select', 'Text position', ['left' => 'Left', 'center' => 'Center', 'right' => 'Right']], 'height' => ['select', 'Height', ['full' => 'Full screen', 'large' => 'Large', 'medium' => 'Medium']], 'overlay' => ['number', 'Dark overlay (0-80)'], 'color' => ['color', 'Text colour']]],
        'slider' => ['Image Slider', ['slides' => ['repeater', 'Slides', ['image' => ['image', 'Image'], 'heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle'], 'button_text' => ['text', 'Button text'], 'button_link' => ['link', 'Button link']]],
            'autoplay' => ['number', 'Seconds per slide (0 = off)'], 'height' => ['select', 'Height', ['large' => 'Large', 'medium' => 'Medium', 'full' => 'Full screen']], 'color' => ['color', 'Text colour']]],
        'promo_banner' => ['Promotional Banner', ['image' => ['image', 'Background image (optional)'], 'heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Text'], 'code' => ['text', 'Coupon code to show (optional)']] + $btn + $style],
        'new_arrivals' => ['New Arrivals', $grid + $style],
        'best_sellers' => ['Best Sellers', $grid + $style],
        'featured_collection' => ['Featured Collection', ['collection_id' => ['collection', 'Collection']] + $grid + $style],
        'featured_collections' => ['Collections Grid', ['heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle'], 'collection_ids' => ['collections', 'Collections (empty = all featured)']] + $style],
        'shop_by_category' => ['Shop by Category', ['heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle'], 'category_ids' => ['categories', 'Categories (empty = all)'], 'style' => ['select', 'Style', ['tiles' => 'Image tiles', 'circles' => 'Circles']]] + $style],
        'trending' => ['Trending / Hand-picked Products', ['product_ids' => ['products', 'Products (empty = featured products)']] + $grid + $style],
        'two_column' => ['Two-column Image + Text', ['image' => ['image', 'Image'], 'eyebrow' => ['text', 'Small text'], 'heading' => ['text', 'Heading'], 'text' => ['richtext', 'Text']] + $btn + ['image_side' => ['select', 'Image side', ['left' => 'Left', 'right' => 'Right']]] + $style],
        'video_banner' => ['Video Banner', ['video' => ['video', 'Video (MP4 upload)'], 'video_url' => ['text', 'or YouTube link'], 'poster' => ['image', 'Cover image'], 'heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle']] + $btn + ['color' => ['color', 'Text colour'], 'height' => ['select', 'Height', ['large' => 'Large', 'medium' => 'Medium', 'full' => 'Full screen']]]],
        'testimonials' => ['Customer Testimonials', ['heading' => ['text', 'Heading'], 'items' => ['repeater', 'Testimonials', ['name' => ['text', 'Name'], 'location' => ['text', 'City'], 'rating' => ['number', 'Stars (1-5)'], 'text' => ['textarea', 'What they said'], 'avatar' => ['image', 'Photo (optional)']]]] + $style],
        'brand_story' => ['Brand Story', ['image' => ['image', 'Image'], 'eyebrow' => ['text', 'Small text'], 'heading' => ['text', 'Heading'], 'text' => ['richtext', 'Story']] + $btn + $style],
        'newsletter' => ['Newsletter', ['heading' => ['text', 'Heading'], 'subtitle' => ['text', 'Subtitle'], 'button_text' => ['text', 'Button text']] + $style],
        'instagram' => ['Instagram Gallery', ['heading' => ['text', 'Heading'], 'handle' => ['text', 'Instagram handle (e.g. @ranashop)'], 'link' => ['link', 'Profile link'], 'images' => ['repeater', 'Photos', ['image' => ['image', 'Photo'], 'link' => ['link', 'Link (optional)']]]] + $style],
        'features' => ['Store Benefits Bar', ['items' => ['repeater', 'Benefits', ['icon' => ['select', 'Icon', ['truck' => 'Delivery', 'cash' => 'Cash', 'refresh' => 'Return', 'shield' => 'Secure', 'headset' => 'Support', 'star' => 'Quality']], 'title' => ['text', 'Title'], 'text' => ['text', 'Text']]]] + $style],
        'custom' => ['Custom Content', ['heading' => ['text', 'Heading'], 'html' => ['richtext', 'Content'], 'width' => ['select', 'Width', ['narrow' => 'Narrow', 'wide' => 'Wide']]] + $style],
    ];
}

function home_sections(bool $draft = false): array {
    $s = setting($draft ? 'home_draft' : 'home_live', []);
    return is_array($s) ? $s : [];
}
