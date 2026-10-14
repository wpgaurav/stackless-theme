<?php
/**
 * Seeds theme review content on the Stackless staging site. Idempotent: re-running updates in place.
 * Run: wp eval-file staging-content.php <dir containing content/>
 *
 * Creates a Style guide page (every semantic element, form control and core block), three Journal
 * posts, Primary and Footer menus, and footer widgets. Front page settings are left alone.
 */

defined('ABSPATH') || exit;

function stt_upsert(string $type, string $slug, string $title, string $content, array $extra = []): int
{
    $existing = get_page_by_path($slug, OBJECT, $type);
    $data = array_merge(['post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish'], $extra);
    if ($existing) {
        $data['ID'] = $existing->ID;
        $id = wp_update_post(wp_slash($data), true);
    } else {
        $id = wp_insert_post(wp_slash($data), true);
    }
    if (is_wp_error($id)) {
        WP_CLI::error($slug . ': ' . $id->get_error_message());
    }
    WP_CLI::log('  ' . $type . ' ' . $slug . ' #' . $id);
    return (int) $id;
}

$shop = (int) wc_get_page_id('shop');
$img = '';
$attachments = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 3, 'fields' => 'ids']);
if ($attachments) {
    $img = (string) wp_get_attachment_image_url($attachments[0], 'large');
}

/* ------------------------------------------------------------------ Style guide */
$imageBlock = $img
    ? '<!-- wp:image {"aspectRatio":"16/9","scale":"cover","sizeSlug":"large","align":"wide"} --><figure class="wp-block-image alignwide size-large"><img src="' . esc_url($img) . '" alt="Product photo" style="aspect-ratio:16/9;object-fit:cover"/><figcaption class="wp-element-caption">A wide image with a caption.</figcaption></figure><!-- /wp:image -->'
    : '';

$guide = str_replace('{{IMAGE}}', $imageBlock, (string) file_get_contents(rtrim((string) ($args[0] ?? __DIR__), '/') . '/content/style-guide.html'));

$guideId = stt_upsert('page', 'style-guide', 'Style guide', $guide);

/* ------------------------------------------------------------------ Journal */
// The blog index stays on the home page; the menu links there.
$journalUrl = home_url('/');

// Demo author for Journal posts (a byline needs a display name, not a login).
$author = get_user_by('login', 'fernleaf-editor');
$authorId = $author ? (int) $author->ID : (int) wp_insert_user([
    'user_login' => 'fernleaf-editor',
    'user_pass' => wp_generate_password(32),
    'user_email' => 'editor@fernleaf.example',
    'role' => 'author',
]);
wp_update_user([
    'ID' => $authorId,
    'first_name' => 'Maya',
    'last_name' => 'Chen',
    'display_name' => 'Maya Chen',
    'nickname' => 'Maya Chen',
    'description' => 'Maya runs the studio side of Fernleaf Supply Co. and writes about packing, pricing and the tools behind a small online shop.',
]);

$cat = term_exists('Shop notes', 'category') ?: wp_insert_term('Shop notes', 'category');
$catId = (int) (is_array($cat) ? $cat['term_id'] : $cat);

$posts = [
    'how-we-pack-stoneware' => ['How we pack stoneware so it arrives in one piece', "<!-- wp:paragraph -->\n<p>Every mug ships in a molded pulp cradle inside a double-wall box. Here is what we learned after 2,000 orders.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What failed first</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Bubble wrap alone let handles snap on long routes. Pulp cradles cut breakage to under one in two hundred.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item --><li>Double-wall box</li><!-- /wp:list-item --><!-- wp:list-item --><li>Pulp cradle per mug</li><!-- /wp:list-item --><!-- wp:list-item --><li>Paper fill, no plastic</li><!-- /wp:list-item --></ul>\n<!-- /wp:list -->"],
    'subscriptions-for-coffee' => ['Running a coffee subscription without a second plugin', "<!-- wp:paragraph -->\n<p>The Roastery Coffee Box renews monthly through Stackless Subscriptions, with Stripe saving the card at checkout.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>Customers can skip, pause or switch roasts from My Account.</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->"],
    'wholesale-pricing' => ['Hiding wholesale prices until a trade account signs in', "<!-- wp:paragraph -->\n<p>Trade buyers see case pricing after signing in. Everyone else sees a sign-in prompt and a quote form.</p>\n<!-- /wp:paragraph -->"],
];
$i = 0;
$excerpts = [
    'how-we-pack-stoneware' => 'Molded pulp, double-wall boxes and no plastic: the packing changes that cut breakage after 2,000 orders.',
    'subscriptions-for-coffee' => 'How the Roastery Coffee Box renews every month with Stackless Subscriptions and Stripe, and what customers can change themselves.',
    'wholesale-pricing' => 'Trade buyers see case pricing after signing in; everyone else gets a sign-in prompt and a quote form.',
];
$tags = [
    'how-we-pack-stoneware' => ['shipping', 'packaging'],
    'subscriptions-for-coffee' => ['subscriptions', 'stripe', 'coffee'],
    'wholesale-pricing' => ['wholesale', 'pricing'],
];
foreach ($posts as $slug => [$title, $body]) {
    $id = stt_upsert('post', $slug, $title, $body, ['post_author' => $authorId, 'post_category' => [$catId], 'post_date' => gmdate('Y-m-d H:i:s', time() - (++$i) * DAY_IN_SECONDS)]);
    if (!empty($attachments[$i - 1])) {
        set_post_thumbnail($id, $attachments[$i - 1]);
    }
    wp_update_post(['ID' => $id, 'post_excerpt' => $excerpts[$slug]]);
    wp_set_post_tags($id, $tags[$slug]);
}

/* ------------------------------------------------------------------ Menus */
function stt_menu(string $name, string $location, array $items): void
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
    WP_CLI::log('  menu ' . $name . ' → ' . $location);
}

$page = static function (string $slug): int {
    $p = get_page_by_path($slug);
    return $p ? (int) $p->ID : 0;
};

$cat_link = static function (string $slug): string {
    $link = get_term_link($slug, 'product_cat');
    return is_wp_error($link) ? home_url('/shop/') : $link;
};

stt_menu('Stackless Primary', 'primary', [
    'shop' => ['Shop', $shop],
    'plugins' => ['Themes and plugins', $cat_link('themes-plugins'), 'shop'],
    'goods' => ['Studio goods', $cat_link('studio-goods'), 'shop'],
    'courses' => ['Courses and services', $cat_link('courses-services'), 'shop'],
    'journal' => ['Journal', $journalUrl],
    'quote' => ['Request a quote', $page('request-a-quote')],
    'library' => ['Members', $page('makers-club-library')],
    'guide' => ['Style guide', $guideId],
]);
stt_menu('Stackless Footer', 'footer', [
    'privacy' => ['Privacy', $page('privacy-policy')],
    'refunds' => ['Refunds', $page('refund_returns')],
    'wishlist' => ['Wishlist', $page('wishlist')],
]);

/* ------------------------------------------------------------------ Footer widgets */
$blocks = (array) get_option('widget_block', []);
$footer = [];
$widgets = [
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Fernleaf Supply Co.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ceramics, paper goods and courses for small makers, shipped from Portland.</p><!-- /wp:paragraph -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Shop</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li><a href="' . esc_url(get_permalink($shop)) . '">All products</a></li><li><a href="' . esc_url(wc_get_cart_url()) . '">Cart</a></li><li><a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">My account</a></li></ul><!-- /wp:list -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Journal</h2><!-- /wp:heading --><!-- wp:latest-posts {"postsToShow":3,"displayPostDate":true} /-->',
];
foreach ($blocks as $k => $v) {
    if (is_array($v) && !empty($v['stt'])) {
        unset($blocks[$k]);
    }
}
$next = max(array_filter(array_keys($blocks), 'is_int') ?: [0]) + 1;
foreach ($widgets as $content) {
    $blocks[$next] = ['content' => $content, 'stt' => 1];
    $footer[] = 'block-' . $next;
    $next++;
}
update_option('widget_block', $blocks);
$sidebars = (array) get_option('sidebars_widgets', []);
$sidebars['footer'] = $footer;
update_option('sidebars_widgets', $sidebars);
WP_CLI::log('  footer widgets: ' . count($footer));

WP_CLI::success('Theme review content ready: ' . get_permalink($guideId));
