<?php
/**
 * Small template helpers.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

function stackless_theme_site_brand(): void
{
    if (has_custom_logo()) {
        the_custom_logo();
        return;
    }
    $tag = is_front_page() && is_home() ? 'h1' : 'p';
    printf(
        '<%1$s class="site-title"><a href="%2$s" rel="home">%3$s</a></%1$s>',
        tag_escape($tag),
        esc_url(home_url('/')),
        esc_html(get_bloginfo('name'))
    );
}

/**
 * The header parts a layout can place, by slug. Each maps to a part in template-parts/site-header.php:
 * branding (header_brand), navigation (header_nav), navigation_right (header_nav_right, the Header
 * right menu), currency (the header currency switcher), search (header_search_button),
 * account (header_account), cart (header_cart), menu_toggle (header_menu_toggle), and for the
 * sidebar layout search_field (header_search_field), rail_list (header_rail_list),
 * rail_toggle (header_rail_toggle) and account_menu (header_account_menu).
 */
function stackless_theme_header_part_slugs(): array
{
    return ['branding', 'navigation', 'navigation_right', 'currency', 'search', 'account', 'cart', 'menu_toggle', 'search_field', 'rail_list', 'rail_toggle', 'account_menu'];
}

/** The groups a header layout has: the top header's start, center and end, and the sidebar layout's rail and bar. */
function stackless_theme_header_layout_slots(): array
{
    return ['start', 'center', 'end', 'rail_top', 'rail_nav', 'rail_list', 'rail_bottom', 'bar'];
}

/**
 * The layout a Header and menu > Header layout preset stands for: 'default' (logo and menu on the
 * left), 'centered' (menu left, logo centered, buttons right), 'split' (primary menu left, logo
 * centered, the Header right menu and the buttons right) or 'sidebar' (a fixed rail with the logo,
 * search, primary menu, list menu and account menu, and a slim top bar with the buttons).
 *
 * @return array<string, string[]>
 */
function stackless_theme_header_layout_preset(string $preset): array
{
    $end = ['currency', 'search', 'account', 'cart', 'menu_toggle'];
    $rail = ['rail_top' => [], 'rail_nav' => [], 'rail_list' => [], 'rail_bottom' => [], 'bar' => []];
    switch ($preset) {
        case 'centered':
            return ['start' => ['navigation'], 'center' => ['branding'], 'end' => $end] + $rail;
        case 'split':
            return ['start' => ['navigation'], 'center' => ['branding'], 'end' => array_merge(['navigation_right'], $end)] + $rail;
        case 'sidebar':
            return [
                'start' => [],
                'center' => [],
                'end' => [],
                'rail_top' => ['branding', 'rail_toggle'],
                'rail_nav' => ['search_field', 'navigation'],
                'rail_list' => ['rail_list'],
                'rail_bottom' => ['account_menu'],
                'bar' => ['currency', 'search', 'cart'],
            ];
        default:
            return ['start' => ['branding', 'navigation'], 'center' => [], 'end' => $end] + $rail;
    }
}

/**
 * Whether the header is the sidebar layout's rail and top bar: some part sits in a rail group,
 * the site header shows, no Page Blocks header region replaces it, and the page did not pick
 * "Top header" in its Layout panel.
 */
function stackless_theme_header_has_rail(): bool
{
    if (!stackless_theme_shows('header') || (function_exists('stackless_theme_has_region') && stackless_theme_has_region('header'))) {
        return false;
    }
    $layout = stackless_theme_header_layout();
    return (bool) array_merge($layout['rail_top'], $layout['rail_nav'], $layout['rail_list'], $layout['rail_bottom']);
}

/**
 * Where each header part sits: ['start' => [...], 'center' => [...], 'end' => [...]], plus the
 * sidebar layout's 'rail_top', 'rail_nav', 'rail_list', 'rail_bottom' and 'bar', each a list of
 * slugs from stackless_theme_header_part_slugs(). A filter that returns only start, center and end
 * gets empty rail groups, so it keeps the top header. The Header layout option picks the starting
 * layout (stackless_theme_header_layout_preset()); filter stackless_theme_header_layout to move
 * parts, and the filter wins. Unknown slugs are dropped, and a slug placed twice keeps its first
 * place. A part left out does not print. The 'end' group sits in the header actions row; a
 * non-empty 'center' gives the header the site-header--has-center class and wraps 'start' and
 * 'center' in their own elements.
 *
 * @return array{start: string[], center: string[], end: string[], rail_top: string[], rail_nav: string[], rail_list: string[], rail_bottom: string[], bar: string[]}
 */
function stackless_theme_header_layout(): array
{
    $preset = (string) stackless_theme_option('stackless_header_layout');
    // A page's "Top header" Layout choice drops the sidebar layout's rail for that page.
    if ($preset === 'sidebar' && stackless_theme_layout_flag('top_header')) {
        $preset = 'default';
    }
    $default = stackless_theme_header_layout_preset($preset);
    $layout = apply_filters('stackless_theme_header_layout', $default);
    $layout = is_array($layout) ? $layout : $default;
    $known = stackless_theme_header_part_slugs();
    $placed = [];
    $clean = [];
    foreach (stackless_theme_header_layout_slots() as $slot) {
        $clean[$slot] = [];
        foreach ((array) ($layout[$slot] ?? []) as $slug) {
            $slug = is_string($slug) ? $slug : '';
            if (!in_array($slug, $known, true) || isset($placed[$slug])) {
                continue;
            }
            $placed[$slug] = true;
            $clean[$slot][] = $slug;
        }
    }
    return $clean;
}

/** Fallback for the primary menu: the shop plus a few top-level pages, never the cart/checkout/account pages. */
function stackless_theme_menu_fallback(): void
{
    $exclude = [];
    if (function_exists('wc_get_page_id')) {
        foreach (['shop', 'cart', 'checkout', 'myaccount'] as $page) {
            $id = wc_get_page_id($page);
            if ($id > 0) {
                $exclude[] = $id;
            }
        }
    }
    /** Store pages (checkout, account and the like) that never belong in the fallback menu. */
    $exclude = array_map('intval', (array) apply_filters('stackless_theme_menu_fallback_exclude', $exclude));
    $pages = get_pages(['parent' => 0, 'exclude' => $exclude, 'number' => 4, 'sort_column' => 'menu_order,post_title']);
    echo '<ul id="primary-menu" class="menu">';
    if (function_exists('wc_get_page_permalink') && wc_get_page_id('shop') > 0) {
        printf('<li class="menu-item%s"><a href="%s"%s>%s</a></li>', function_exists('is_shop') && is_shop() ? ' current-menu-item' : '', esc_url(wc_get_page_permalink('shop')), function_exists('is_shop') && is_shop() ? ' aria-current="page"' : '', esc_html(get_the_title(wc_get_page_id('shop'))));
    }
    foreach ($pages as $page) {
        $current = is_page($page->ID);
        printf('<li class="menu-item%s"><a href="%s"%s>%s</a></li>', $current ? ' current-menu-item' : '', esc_url(get_permalink($page)), $current ? ' aria-current="page"' : '', esc_html(get_the_title($page)));
    }
    echo '</ul>';
}

function stackless_theme_posted_on(): void
{
    $time = sprintf(
        '<time class="entry-date published" datetime="%1$s">%2$s</time>',
        esc_attr(get_the_date(DATE_W3C)),
        esc_html(get_the_date())
    );
    echo '<span class="posted-on">';
    stackless_theme_the_icon('calendar', 16);
    echo ' <a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $time . '</a></span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
    // A post with no author (imported, or its user deleted) gets no empty byline.
    if ((string) get_the_author() === '') {
        return;
    }
    echo '<span class="byline">';
    stackless_theme_the_icon('user-circle', 16);
    echo ' <a class="url fn n" href="' . esc_url(get_author_posts_url((int) get_the_author_meta('ID'))) . '">' . esc_html(get_the_author()) . '</a></span>';
}

function stackless_theme_entry_footer(): void
{
    if (get_post_type() !== 'post') {
        return;
    }
    $categories = get_the_category_list(', ');
    $tags = get_the_tag_list('', ', ');
    if ($categories) {
        echo '<span class="cat-links">';
        stackless_theme_the_icon('folder', 16);
        echo ' <span class="screen-reader-text">' . esc_html__('Categories:', 'stackless-theme') . '</span>' . wp_kses_post($categories) . '</span>';
    }
    if ($tags) {
        echo '<span class="tags-links">';
        stackless_theme_the_icon('tag', 16);
        echo ' <span class="screen-reader-text">' . esc_html__('Tags:', 'stackless-theme') . '</span>' . wp_kses_post($tags) . '</span>';
    }
    if (!is_single() && !post_password_required() && (comments_open() || get_comments_number())) {
        echo '<span class="comments-link">';
        stackless_theme_the_icon('message-circle', 16);
        echo ' ';
        comments_popup_link(esc_html__('Leave a comment', 'stackless-theme'));
        echo '</span>';
    }
}

function stackless_theme_pagination(): void
{
    the_posts_pagination([
        'mid_size' => 1,
        'prev_text' => stackless_theme_icon('chevron-left', 16) . '<span class="screen-reader-text">' . esc_html__('Previous page', 'stackless-theme') . '</span>',
        'next_text' => '<span class="screen-reader-text">' . esc_html__('Next page', 'stackless-theme') . '</span>' . stackless_theme_icon('chevron-right', 16),
    ]);
}

/**
 * Header cart link; the count is refreshed by WooCommerce cart fragments. Without WooCommerce,
 * `stackless_theme_cart_link` lets another store (Easy Digital Downloads) supply the link.
 */
function stackless_theme_cart_link(): string
{
    if (!function_exists('WC') || !WC()->cart || !function_exists('wc_get_cart_url')) {
        return (string) apply_filters('stackless_theme_cart_link', '');
    }
    $count = (int) WC()->cart->get_cart_contents_count();
    /* translators: %d: number of items in the cart */
    $label = sprintf(_n('Cart, %d item', 'Cart, %d items', $count, 'stackless-theme'), $count);
    return sprintf(
        '<a class="header-action header-cart" href="%1$s" aria-label="%2$s">%3$s<span class="header-cart__count%4$s" aria-hidden="true">%5$d</span></a>',
        esc_url(wc_get_cart_url()),
        esc_attr($label),
        stackless_theme_icon('shopping-bag', 22),
        $count ? '' : ' is-empty',
        $count
    );
}

/** Header account link: WooCommerce's My Account page, else whatever `stackless_theme_account_url` returns. */
function stackless_theme_account_url(): string
{
    if (function_exists('wc_get_page_permalink') && wc_get_page_id('myaccount') > 0) {
        return (string) wc_get_page_permalink('myaccount');
    }
    return (string) apply_filters('stackless_theme_account_url', '');
}

/** "4 min read", from the post's word count at about 220 words a minute. */
function stackless_theme_reading_time(): string
{
    $words = str_word_count(wp_strip_all_tags((string) get_post_field('post_content', get_the_ID())));
    $minutes = max(1, (int) ceil($words / 220));
    /* translators: %d: minutes */
    return sprintf(_n('%d min read', '%d min read', $minutes, 'stackless-theme'), $minutes);
}

/**
 * Compact currency switcher from Stackless for WooCommerce's Storefront Currency Selector.
 * Renders nothing unless that module is on and at least two currencies are enabled.
 * Filter `stackless_theme_currency_switcher` (bool, $location) to hide it per location.
 */
function stackless_theme_currency_switcher(string $location): void
{
    if (!shortcode_exists('stackless_currency_selector') || !apply_filters('stackless_theme_currency_switcher', true, $location)) {
        return;
    }
    $format = $location === 'header' ? 'code' : 'code_symbol';
    echo do_shortcode(sprintf(
        '[stackless_currency_selector show_label="%s" label="%s" format="%s" class="st-currency st-currency--%s"]',
        $location === 'menu' ? 'yes' : 'no',
        esc_attr__('Currency', 'stackless-theme'),
        $format,
        sanitize_html_class($location)
    ));
}

/**
 * IDs of related items of a post type: for posts, same-category posts first; for other types,
 * items sharing a term in the type's first taxonomy first. Topped up with recent items. Cached per
 * item for a day; publishing, editing or unpublishing any of them bumps the version and refreshes all.
 */
function stackless_theme_related_post_ids(int $post_id, int $count, string $post_type = 'post'): array
{
    $version = (string) get_option('stackless_theme_related_version', '1');
    $key = 'stackless_related_' . $post_id;
    $cached = get_transient($key);
    if (is_array($cached) && ($cached['v'] ?? '') === $version && (int) ($cached['n'] ?? 0) === $count) {
        return array_map('intval', (array) $cached['ids']);
    }

    $args = [
        'post_type' => $post_type,
        'posts_per_page' => $count,
        'post__not_in' => [$post_id],
        'ignore_sticky_posts' => true,
        'no_found_rows' => true,
        'fields' => 'ids',
    ];
    $first = [];
    if ($post_type === 'post') {
        $first = ['category__in' => wp_get_post_categories($post_id)];
    } else {
        $taxonomies = get_object_taxonomies($post_type);
        $taxonomy = $taxonomies ? (string) reset($taxonomies) : '';
        $terms = $taxonomy !== '' ? wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']) : [];
        if ($terms && !is_wp_error($terms)) {
            $first = ['tax_query' => [['taxonomy' => $taxonomy, 'terms' => $terms]]];
        }
    }
    $ids = $first ? get_posts($args + $first) : [];
    if (count($ids) < $count) {
        $ids = array_merge($ids, get_posts(array_merge($args, [
            'posts_per_page' => $count - count($ids),
            'post__not_in' => array_merge([$post_id], $ids),
        ])));
    }
    $ids = array_map('intval', $ids);
    set_transient($key, ['v' => $version, 'n' => $count, 'ids' => $ids], DAY_IN_SECONDS);
    return $ids;
}

add_action('transition_post_status', 'stackless_theme_related_bump', 10, 3);
function stackless_theme_related_bump(string $new_status, string $old_status, $post): void
{
    $types = array_merge(['post'], function_exists('stackless_theme_custom_post_types') ? array_keys(stackless_theme_custom_post_types()) : []);
    if ($post instanceof WP_Post && in_array($post->post_type, $types, true) && ($new_status === 'publish' || $old_status === 'publish')) {
        update_option('stackless_theme_related_version', (string) time(), true);
    }
}

/**
 * The store's main product listing: the WooCommerce shop, the download archive, or whatever
 * `stackless_theme_shop_url` returns (FluentCart's shop page). Empty when there is no store.
 */
function stackless_theme_shop_url(): string
{
    if (function_exists('wc_get_page_id') && wc_get_page_id('shop') > 0) {
        return (string) wc_get_page_permalink('shop');
    }
    if (class_exists('Easy_Digital_Downloads') && post_type_exists('download')) {
        return (string) get_post_type_archive_link('download');
    }
    return (string) apply_filters('stackless_theme_shop_url', '');
}

/** The store's product category taxonomy, when it exists. */
function stackless_theme_store_taxonomy(): string
{
    foreach (['product_cat', 'download_category', 'product-categories'] as $taxonomy) {
        if (taxonomy_exists($taxonomy)) {
            return (string) apply_filters('stackless_theme_store_taxonomy', $taxonomy);
        }
    }
    return (string) apply_filters('stackless_theme_store_taxonomy', '');
}

/** Product categories that hold at least one product, most used first. */
function stackless_theme_store_terms_with_products(int $limit = 8): array
{
    $taxonomy = stackless_theme_store_taxonomy();
    if ($taxonomy === '') {
        return [];
    }
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => $limit]);
    return is_array($terms) ? $terms : [];
}

/**
 * A product in search results: its image and price markup, from whichever store owns the post
 * type. Null for posts and pages, which keep the post summary.
 *
 * @return array{image: string, price: string}|null
 */
function stackless_theme_search_product(WP_Post $post): ?array
{
    $product = apply_filters('stackless_theme_search_product', null, $post);
    if (!is_array($product)) {
        return null;
    }
    return ['image' => (string) ($product['image'] ?? ''), 'price' => (string) ($product['price'] ?? '')];
}

/**
 * Whether a FluentCart product or EDD download is on sale, through stackless_theme_product_on_sale
 * ($on_sale, $post_id, $store). FluentCart answers from its prices; EDD has no sale price, so a
 * download is never on sale unless a filter says so.
 */
function stackless_theme_product_on_sale(int $post_id, string $store): bool
{
    return (bool) apply_filters('stackless_theme_product_on_sale', false, $post_id, $store);
}

/**
 * The sale badge on a store card or product page, like WooCommerce's .onsale. Part:
 * product_sale_badge, with $context['store'], ['post_id'] and ['placement'] ('card' or 'single').
 * Prints nothing, and fires no hooks, when the product is not on sale.
 */
function stackless_theme_sale_badge(array $context): void
{
    if (!stackless_theme_product_on_sale((int) ($context['post_id'] ?? 0), (string) ($context['store'] ?? ''))) {
        return;
    }
    stackless_theme_part('product_sale_badge', static function (array $context): void {
        printf(
            '<span class="%1$s">%2$s</span>',
            stackless_theme_class('product_sale_badge', ['stackless-sale-badge'], $context), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
            esc_html(stackless_theme_text('sale_badge', __('Sale', 'stackless-theme'), $context))
        );
    }, $context);
}
