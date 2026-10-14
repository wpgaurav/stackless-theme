<?php
/**
 * Showcase content for the public demo at demo.gatilab.com/stackless/ (Stackless theme + plugin).
 * Idempotent: pages and posts are matched by slug and updated in place; the Tour page gets its
 * theme section once. Run: wp --url=https://demo.gatilab.com/stackless/ eval-file demo-content.php <dir>
 * where <dir> contains content/style-guide.html.
 *
 * Creates or updates: Theme, Style guide and Journal pages, three Journal posts (by an existing demo
 * persona), Primary/Footer menus, footer widgets, page_for_posts. Updates the Tour intro and adds a
 * theme section. Drafts the default "Hello world!" post. Prints a JSON manifest of object IDs.
 */

defined('ABSPATH') || exit;

if (untrailingslashit(home_url()) !== 'https://demo.gatilab.com/stackless' || get_option('stylesheet') !== 'stackless-theme') {
    WP_CLI::error('Wrong target or theme not active.');
}
$dir = rtrim((string) ($args[0] ?? ''), '/');
$manifest = [];

function sd_upsert(string $type, string $slug, string $title, string $content, array $extra = []): int
{
    $existing = get_page_by_path($slug, OBJECT, $type);
    $data = array_merge(['post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish'], $extra);
    if ($existing) {
        $data['ID'] = $existing->ID;
    }
    $id = $existing ? wp_update_post(wp_slash($data), true) : wp_insert_post(wp_slash($data), true);
    if (is_wp_error($id)) {
        WP_CLI::error($slug . ': ' . $id->get_error_message());
    }
    return (int) $id;
}

$url = static function (string $path = ''): string {
    return home_url('/' . ltrim($path, '/'));
};
$product = static function (string $slug) {
    $p = get_page_by_path($slug, OBJECT, 'product');
    return $p ? (int) $p->ID : 0;
};
$productUrl = static function (string $slug) use ($product): string {
    $id = $product($slug);
    return $id ? (string) get_permalink($id) : home_url('/shop/');
};

/* ------------------------------------------------------------------ Style guide */
$imageId = (int) get_post_thumbnail_id($product('stoneware-mug'));
$img = $imageId ? (string) wp_get_attachment_image_url($imageId, 'large') : '';
$imageBlock = $img
    ? '<!-- wp:image {"aspectRatio":"16/9","scale":"cover","sizeSlug":"large","align":"wide"} --><figure class="wp-block-image alignwide size-large"><img src="' . esc_url($img) . '" alt="Stoneware mug product photo" style="aspect-ratio:16/9;object-fit:cover"/><figcaption class="wp-element-caption">A wide image with a caption.</figcaption></figure><!-- /wp:image -->'
    : '';
$guide = str_replace('{{IMAGE}}', $imageBlock, (string) file_get_contents($dir . '/content/style-guide.html'));
$manifest['style-guide'] = sd_upsert('page', 'style-guide', 'Style guide', $guide);

/* ------------------------------------------------------------------ Journal posts */
$author = get_user_by('login', 'maya.r');
$authorId = $author ? (int) $author->ID : 1;
if ($author && !get_user_meta($authorId, 'description', true)) {
    update_user_meta($authorId, 'description', 'Maya runs the studio side of Fernleaf Supply Co. and writes about packing, pricing and the tools behind a small online shop. Fernleaf is a fictional store for this demo.');
}
$cat = term_exists('Shop notes', 'category') ?: wp_insert_term('Shop notes', 'category');
$catId = (int) (is_array($cat) ? $cat['term_id'] : $cat);

$posts = [
    'how-we-pack-stoneware' => [
        'How we pack stoneware so it arrives in one piece',
        'Molded pulp, double-wall boxes and no plastic: the packing changes that cut breakage on our mug orders.',
        ['shipping', 'packaging'],
        'stoneware-mug',
        "<!-- wp:paragraph -->\n<p>Every mug ships in a molded pulp cradle inside a double-wall box. This is a sample post for the demo store, written to show how the Stackless theme handles a long-form article.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What failed first</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Bubble wrap alone let handles snap on long routes. A cradle per mug holds the handle away from the box wall.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item --><li>Double-wall box</li><!-- /wp:list-item --><!-- wp:list-item --><li>Pulp cradle per mug</li><!-- /wp:list-item --><!-- wp:list-item --><li>Paper fill, no plastic</li><!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>Pack for the worst route, not the average one.</p>\n<!-- /wp:paragraph --><cite>Shop notes</cite></blockquote>\n<!-- /wp:quote -->\n\n<!-- wp:paragraph -->\n<p>See the result on the <a href=\"" . esc_url($productUrl('stoneware-mug')) . "\">Stoneware Mug</a> page, where the sold count and delivery slots come from Stackless.</p>\n<!-- /wp:paragraph -->",
    ],
    'subscriptions-for-coffee' => [
        'Running a coffee subscription without a second plugin',
        'How the Roastery Coffee Box renews every month with Stackless Subscriptions, and what customers can change themselves.',
        ['subscriptions', 'coffee'],
        'roastery-coffee-box',
        "<!-- wp:paragraph -->\n<p>The <a href=\"" . esc_url($productUrl('roastery-coffee-box')) . "\">Roastery Coffee Box</a> renews monthly through Stackless Subscriptions, with a signup fee and shipping on every renewal. This is a sample post for the demo store.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What customers control</h2>\n<!-- /wp:heading -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item --><li>Pause and resume from My Account</li><!-- /wp:list-item --><!-- wp:list-item --><li>Switch plans with proration</li><!-- /wp:list-item --><!-- wp:list-item --><li>Update the saved card</li><!-- /wp:list-item --></ul>\n<!-- /wp:list -->\n\n<!-- wp:paragraph -->\n<p>Add the box and a mug to the cart to see one-time items and recurring totals side by side.</p>\n<!-- /wp:paragraph -->",
    ],
    'wholesale-pricing' => [
        'Hiding wholesale prices until a trade account signs in',
        'Trade buyers see case pricing after signing in; everyone else gets a sign-in prompt and a quote form.',
        ['wholesale', 'pricing'],
        'cafe-mug-case-wholesale',
        "<!-- wp:paragraph -->\n<p>The <a href=\"" . esc_url($productUrl('cafe-mug-case-wholesale')) . "\">Café Mug Case of 12</a> shows a sign-in prompt instead of a price. Shop grids and search keep it quiet; the prompt appears on the product page only. This is a sample post for the demo store.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Buyers without an account can <a href=\"" . esc_url($url('request-a-quote/')) . "\">request a quote</a> instead.</p>\n<!-- /wp:paragraph -->",
    ],
];
$i = 0;
foreach ($posts as $slug => [$title, $excerpt, $tags, $productSlug, $body]) {
    $id = sd_upsert('post', $slug, $title, $body, ['post_excerpt' => $excerpt, 'post_author' => $authorId, 'post_category' => [$catId], 'post_date' => gmdate('Y-m-d H:i:s', strtotime('2026-10-0' . (8 - $i++) . ' 10:00:00'))]);
    wp_set_post_tags($id, $tags);
    $thumb = (int) get_post_thumbnail_id($product($productSlug));
    if ($thumb) {
        set_post_thumbnail($id, $thumb);
    }
    $manifest['post:' . $slug] = $id;
}
$hello = get_page_by_path('hello-world', OBJECT, 'post');
if ($hello && $hello->post_status === 'publish' && strpos($hello->post_content, 'Welcome to WordPress') !== false) {
    wp_update_post(['ID' => $hello->ID, 'post_status' => 'draft']);
    $manifest['drafted:hello-world'] = (int) $hello->ID;
}

$journalId = sd_upsert('page', 'journal', 'Journal', '');
update_option('page_for_posts', $journalId);
$manifest['journal'] = $journalId;

/* ------------------------------------------------------------------ Theme showcase page */
$try = static function (string $title, string $text, string $href, string $label): string {
    return '<!-- wp:group {"className":"is-style-default","style":{"border":{"width":"1px","color":"#e5e5e5","radius":"8px"},"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem","left":"1.5rem","right":"1.5rem"}}},"backgroundColor":"white"} -->'
        . '<div class="wp-block-group is-style-default has-border-color has-white-background-color has-background" style="border-color:#e5e5e5;border-width:1px;border-radius:8px;padding-top:1.25rem;padding-right:1.5rem;padding-bottom:1.25rem;padding-left:1.5rem">'
        . '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">' . esc_html($title) . '</h3><!-- /wp:heading -->'
        . '<!-- wp:paragraph --><p>' . $text . '</p><!-- /wp:paragraph -->'
        . '<!-- wp:paragraph --><p><a href="' . esc_url($href) . '">' . esc_html($label) . ' →</a></p><!-- /wp:paragraph -->'
        . '</div><!-- /wp:group -->';
};
$cards = [
    ['Full-screen search', 'Press the search icon in the header and type <strong>mug</strong>. Results appear as you type, with prices from the store. Before you type, it shows popular categories and best sellers. Esc closes it.', $url('shop/'), 'Open the shop and try it'],
    ['Sticky product summary', 'On a product page the title, price and Add to cart stay in view while the gallery, tabs and related products scroll on the left.', $productUrl('stoneware-mug'), 'Stoneware Mug'],
    ['Stackless on every card', 'Sold counts, stock urgency, wishlist buttons and custom add-to-cart text sit in the same grid with one set of spacing rules.', $url('shop/'), 'Shop grid'],
    ['Cart and Checkout blocks', 'Fields, radios, totals and order bumps match the rest of the theme. Orders and payments are switched off on this demo.', $productUrl('formwise-pro'), 'Add Formwise Pro, then open checkout'],
    ['Sign-in and quotes', 'The account sign-in form and the quote form use the same 44px controls and stacked labels as every other form.', $url('request-a-quote/'), 'Request a quote form'],
    ['Editorial posts', 'Category label, summary line, author byline with reading time, tags, author box and related posts.', $url('journal/'), 'Read the Journal'],
    ['Every element styled', 'Typography, lists, tables, code, media, every form control and the core blocks, on one page.', $url('style-guide/'), 'Open the style guide'],
    ['A friendly 404', 'Missing pages get a clear message, search and a way back.', $url('this-page-does-not-exist/'), 'See the 404 page'],
];
$grid = '<!-- wp:group {"align":"wide","layout":{"type":"grid","minimumColumnWidth":"18rem"}} --><div class="wp-block-group alignwide">';
foreach ($cards as [$t, $x, $h, $l]) {
    $grid .= $try($t, $x, $h, $l);
}
$grid .= '</div><!-- /wp:group -->';
$themePage = '<!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">This store runs the free <a href="https://gauravtiwari.org/product/stackless-theme/">Stackless WordPress theme</a> with <a href="https://gauravtiwari.org/product/stackless-for-woocommerce/">Stackless for WooCommerce</a> ' . esc_html(defined('STACKLESS_VERSION') ? STACKLESS_VERSION : '') . '. The theme is a minimal classic theme built on Tabler\'s design foundation, and it styles every screen the plugin adds.</p><!-- /wp:paragraph -->'
    . '<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://gauravtiwari.org/product/stackless-theme/">Get the theme free</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="https://github.com/wpgaurav/stackless-theme">Source on GitHub</a></div><!-- /wp:button --></div><!-- /wp:buttons -->'
    . '<!-- wp:heading --><h2 class="wp-block-heading">Things to try</h2><!-- /wp:heading -->'
    . $grid
    . '<!-- wp:heading --><h2 class="wp-block-heading">What the theme includes</h2><!-- /wp:heading -->'
    . '<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Classic PHP templates with Block Editor support: theme.json palette, type scale and spacing, wide and full alignment, editor styles.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Tabler design tokens and Tabler Icons; system fonts, no jQuery, one small script.</li><!-- /wp:list-item --><!-- wp:list-item --><li>WooCommerce classic templates and the Cart, Checkout and product blocks.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Every Stackless module: wishlist, quotes, subscriptions, licenses, saved carts, order bumps, page access, consents and more.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Free updates in the download from gauravtiwari.org with a free lifetime license key, unlimited sites.</li><!-- /wp:list-item --></ul><!-- /wp:list -->'
    . '<!-- wp:paragraph --><p>Want the plugin side? The <a href="' . esc_url($url('/')) . '">store tour</a> maps every Stackless feature to a product you can try.</p><!-- /wp:paragraph -->';
$manifest['theme'] = sd_upsert('page', 'theme', 'The Stackless theme', $themePage, ['post_excerpt' => 'What the free Stackless WordPress theme does on this demo store, with things to try.']);

/* ------------------------------------------------------------------ Tour page */
$tour = get_page_by_path('tour');
// Once demo-home.php has rebuilt the front page, it owns the Tour content.
if ($tour && strpos($tour->post_content, 'id="tour-features"') === false) {
    $content = $tour->post_content;
    $content = preg_replace('/running <a href="https:\/\/gauravtiwari\.org\/product\/stackless-for-woocommerce\/">Stackless for WooCommerce<\/a> [0-9.]+ on WooCommerce with the [A-Za-z ]+ theme\./', 'running <a href="https://gauravtiwari.org/product/stackless-for-woocommerce/">Stackless for WooCommerce</a> ' . esc_html(defined('STACKLESS_VERSION') ? STACKLESS_VERSION : '') . ' on WooCommerce with the free <a href="' . esc_url($url('theme/')) . '">Stackless theme</a>.', $content, 1);
    if (strpos($content, 'id="tour-theme"') === false) {
        $section = '<!-- wp:heading {"anchor":"tour-theme"} --><h2 class="wp-block-heading" id="tour-theme">The theme</h2><!-- /wp:heading -->'
            . '<!-- wp:list --><ul class="wp-block-list">'
            . '<!-- wp:list-item --><li><a href="' . esc_url($url('theme/')) . '">The Stackless theme</a>: what it adds and what to try, including full-screen search and the sticky product summary.</li><!-- /wp:list-item -->'
            . '<!-- wp:list-item --><li><a href="' . esc_url($url('style-guide/')) . '">Style guide</a>: every text element, form control and core block.</li><!-- /wp:list-item -->'
            . '<!-- wp:list-item --><li><a href="' . esc_url($url('journal/')) . '">Journal</a>: sample posts with bylines, author box and related posts.</li><!-- /wp:list-item -->'
            . '</ul><!-- /wp:list -->';
        $pos = strpos($content, '<!-- wp:heading -->');
        $content = $pos === false ? $content . $section : substr($content, 0, $pos) . $section . "\n\n" . substr($content, $pos);
    }
    wp_update_post(wp_slash(['ID' => $tour->ID, 'post_content' => $content]));
    $manifest['tour'] = (int) $tour->ID;
}

/* ------------------------------------------------------------------ Menus */
function sd_menu(string $name, string $location, array $items): int
{
    $menu = wp_get_nav_menu_object($name);
    $menuId = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    foreach (wp_get_nav_menu_items($menuId) ?: [] as $old) {
        wp_delete_post($old->ID, true);
    }
    $parents = [];
    foreach ($items as $key => $item) {
        $args = ['menu-item-title' => $item[0], 'menu-item-status' => 'publish'];
        if (is_int($item[1])) {
            $args += ['menu-item-object-id' => $item[1], 'menu-item-object' => get_post_type($item[1]), 'menu-item-type' => 'post_type'];
        } else {
            $args += ['menu-item-url' => $item[1], 'menu-item-type' => 'custom'];
        }
        if (!empty($item[2])) {
            $args['menu-item-parent-id'] = $parents[$item[2]];
        }
        $parents[$key] = (int) wp_update_nav_menu_item($menuId, 0, $args);
    }
    $locations = (array) get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $menuId;
    set_theme_mod('nav_menu_locations', $locations);
    return $menuId;
}
$page = static function (string $slug): int {
    $p = get_page_by_path($slug);
    return $p ? (int) $p->ID : 0;
};
$cat = static function (string $slug): string {
    $link = get_term_link($slug, 'product_cat');
    return is_wp_error($link) ? home_url('/shop/') : $link;
};
$manifest['menu:primary'] = sd_menu('Stackless Primary', 'primary', [
    'tour' => ['Feature tour', home_url('/#tour-features')],
    'shop' => ['Shop', (int) wc_get_page_id('shop')],
    'goods' => ['Studio goods', $cat('studio-goods'), 'shop'],
    'plugins' => ['Themes and plugins', $cat('themes-plugins'), 'shop'],
    'courses' => ['Courses and services', $cat('courses-services'), 'shop'],
    'members' => ['Memberships', $cat('memberships'), 'shop'],
    'wholesale' => ['Wholesale', $cat('wholesale'), 'shop'],
    'theme' => ['Theme', $manifest['theme']],
    'theme-tour' => ['What the theme does', $manifest['theme'], 'theme'],
    'guide' => ['Style guide', $manifest['style-guide'], 'theme'],
    'journal' => ['Journal', $journalId, 'theme'],
    'quote' => ['Request a quote', $page('request-a-quote')],
    'course' => ['Course', $page('launch-your-store')],
]);
$manifest['menu:footer'] = sd_menu('Stackless Footer', 'footer', [
    'privacy' => ['Privacy', $page('privacy-policy')],
    'refunds' => ['Refunds', $page('refund_returns')],
    'wishlist' => ['Wishlist', $page('wishlist')],
    'account' => ['My account', (int) wc_get_page_id('myaccount')],
]);

/* ------------------------------------------------------------------ Footer widgets */
$blocks = (array) get_option('widget_block', []);
foreach ($blocks as $k => $v) {
    if (is_array($v) && !empty($v['sd'])) {
        unset($blocks[$k]);
    }
}
$widgets = [
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Fernleaf Supply Co.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>A fictional store for the Stackless demo. Orders, payments and emails are switched off, so try anything.</p><!-- /wp:paragraph -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Explore</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li><a href="' . esc_url($url('/')) . '">Store tour</a></li><li><a href="' . esc_url($url('theme/')) . '">The theme</a></li><li><a href="' . esc_url($url('shop/')) . '">All products</a></li><li><a href="' . esc_url(wc_get_cart_url()) . '">Cart</a></li></ul><!-- /wp:list -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Get Stackless</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li><a href="https://gauravtiwari.org/product/stackless-for-woocommerce/">Stackless for WooCommerce</a></li><li><a href="https://gauravtiwari.org/product/stackless-theme/">Stackless theme (free)</a></li><li><a href="https://gauravtiwari.org/portal/course/stackless-for-woocommerce-training/lessons">Documentation</a></li></ul><!-- /wp:list -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Journal</h2><!-- /wp:heading --><!-- wp:latest-posts {"postsToShow":3,"displayPostDate":true} /-->',
];
$next = max(array_filter(array_keys($blocks), 'is_int') ?: [0]) + 1;
$footer = [];
foreach ($widgets as $content) {
    $blocks[$next] = ['content' => $content, 'sd' => 1];
    $footer[] = 'block-' . $next++;
}
update_option('widget_block', $blocks);
$sidebars = (array) get_option('sidebars_widgets', []);
$sidebars['footer'] = $footer;
update_option('sidebars_widgets', $sidebars);
$manifest['widgets:footer'] = $footer;

echo wp_json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
