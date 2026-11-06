<?php
/**
 * WooCommerce integration: supports, wrappers and the header cart count.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', 'stackless_theme_woocommerce_setup');
function stackless_theme_woocommerce_setup(): void
{
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 600,
        'single_image_width' => 900,
        'product_grid' => [
            'default_rows' => 4,
            'min_rows' => 1,
            'default_columns' => 3,
            'min_columns' => 1,
            'max_columns' => 6,
        ],
    ]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}

// One wrapper for every classic WooCommerce template, and no sidebar.
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

add_action('woocommerce_before_main_content', 'stackless_theme_shop_open', 10);
function stackless_theme_shop_open(): void
{
    stackless_theme_before_content();
    echo '<main id="main" class="site-main site-main--shop container">';
}

add_action('woocommerce_after_main_content', 'stackless_theme_shop_close', 10);
function stackless_theme_shop_close(): void
{
    echo '</main>';
    stackless_theme_after_content();
}

// Optional filters above the product grid.
add_action('woocommerce_before_shop_loop', 'stackless_theme_shop_widgets', 5);
function stackless_theme_shop_widgets(): void
{
    if (is_active_sidebar('shop')) {
        echo '<aside class="shop-widgets" aria-label="' . esc_attr__('Product filters', 'stackless-theme') . '">';
        dynamic_sidebar('shop');
        echo '</aside>';
    }
}

add_filter('woocommerce_breadcrumb_defaults', 'stackless_theme_breadcrumb_defaults');
function stackless_theme_breadcrumb_defaults(array $args): array
{
    $args['delimiter'] = '<span class="breadcrumb-sep" aria-hidden="true">/</span>';
    $args['wrap_before'] = '<nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'stackless-theme') . '">';
    return $args;
}

add_filter('woocommerce_output_related_products_args', 'stackless_theme_related_products');
function stackless_theme_related_products(array $args): array
{
    $args['posts_per_page'] = function_exists('stackless_theme_related_count') ? stackless_theme_related_count('products') : 6;
    $args['columns'] = 3;
    return $args;
}

/** A related-products count of 0 removes the section. */
add_action('woocommerce_after_single_product_summary', 'stackless_theme_maybe_hide_related', 1);
function stackless_theme_maybe_hide_related(): void
{
    if (function_exists('stackless_theme_related_count') && stackless_theme_related_count('products') === 0) {
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    }
}

add_filter('woocommerce_upsell_display_args', 'stackless_theme_upsells');
function stackless_theme_upsells(array $args): array
{
    $args['posts_per_page'] = 3;
    $args['columns'] = 3;
    return $args;
}

add_filter('woocommerce_cross_sells_columns', static function (): int {
    return 3;
});

/** Keeps the header cart count current after classic AJAX add-to-cart. */
add_filter('woocommerce_add_to_cart_fragments', 'stackless_theme_cart_fragment');
function stackless_theme_cart_fragment(array $fragments): array
{
    $fragments['a.header-cart'] = stackless_theme_cart_link();
    return $fragments;
}

add_filter('body_class', 'stackless_theme_woocommerce_body_class');
function stackless_theme_woocommerce_body_class(array $classes): array
{
    if (is_cart() || is_checkout() || is_account_page()) {
        $classes[] = 'is-store-page';
    }
    return $classes;
}

/** Price HTML safe to place inside a link: keeps sale markup, drops nested links and the sign-in prompt. */
function stackless_theme_price_without_links(string $html): string
{
    // Hide Price Until Login's prompt belongs on the product page only.
    if (strpos($html, 'stackless-login-to-see-price') !== false) {
        return '';
    }
    return wp_kses($html, [
        'span' => ['class' => true],
        'bdi' => [],
        'del' => ['aria-hidden' => true],
        'ins' => ['aria-hidden' => true],
        'small' => ['class' => true],
        'strong' => [],
    ]);
}

/** WooCommerce and Stackless shortcodes that put store markup on a page. */
function stackless_theme_woo_shortcodes(): array
{
    return ['products', 'product', 'product_page', 'product_category', 'product_categories', 'add_to_cart', 'recent_products', 'featured_products', 'sale_products', 'best_selling_products', 'top_rated_products', 'product_attribute', 'related_products', 'shop_messages', 'woocommerce_cart', 'woocommerce_checkout', 'woocommerce_my_account', 'woocommerce_order_tracking'];
}

/** Whether a post's content carries WooCommerce or Stackless blocks or shortcodes. */
function stackless_theme_woo_content_has_store($post = null): bool
{
    $post = get_post($post);
    if (!$post instanceof WP_Post || $post->post_content === '') {
        return false;
    }
    $content = $post->post_content;
    if (strpos($content, '<!-- wp:woocommerce/') !== false || strpos($content, '<!-- wp:stackless') !== false || strpos($content, '[stackless') !== false) {
        return true;
    }
    foreach (stackless_theme_woo_shortcodes() as $shortcode) {
        if (has_shortcode($content, $shortcode)) {
            return true;
        }
    }
    return false;
}

/**
 * True on WooCommerce screens, pages with store blocks or shortcodes, and wherever a WooCommerce
 * widget is active. Filter `stackless_theme_is_woo_surface` to force the layer on or off.
 */
function stackless_theme_is_woo_surface(): bool
{
    static $is = null;
    if ($is !== null) {
        return $is;
    }
    $is = (function_exists('is_woocommerce') && is_woocommerce())
        || (function_exists('is_cart') && is_cart())
        || (function_exists('is_checkout') && is_checkout())
        || (function_exists('is_account_page') && is_account_page())
        || (is_singular() && stackless_theme_woo_content_has_store());
    if (!$is) {
        foreach (['woocommerce_widget_cart', 'woocommerce_products', 'woocommerce_product_categories', 'woocommerce_product_search', 'woocommerce_top_rated_products', 'woocommerce_recently_viewed_products', 'woocommerce_recent_reviews', 'woocommerce_price_filter', 'woocommerce_layered_nav', 'woocommerce_layered_nav_filters', 'woocommerce_rating_filter', 'woocommerce_product_tag_cloud'] as $widget) {
            if (is_active_widget(false, false, $widget)) {
                $is = true;
                break;
            }
        }
    }
    $is = (bool) apply_filters('stackless_theme_is_woo_surface', $is);
    return $is;
}

function stackless_theme_woo_enqueue_style(): void
{
    if (wp_style_is('stackless-theme-woocommerce', 'enqueued') || !function_exists('stackless_theme_style')) {
        return;
    }
    $deps = ['stackless-theme-layout'];
    foreach (['woocommerce-general', 'wc-blocks-style'] as $handle) {
        if (wp_style_is($handle, 'registered')) {
            $deps[] = $handle;
        }
    }
    stackless_theme_style('stackless-theme-woocommerce', 'assets/css/woocommerce.css', $deps);
}

/**
 * WooCommerce blocks and shortcodes placed where the checks above cannot see (a block widget, a
 * Page Blocks section, a template part) still get the layer; it then prints in the footer.
 */
add_filter('render_block', 'stackless_theme_woo_late_block_style', 10, 2);
function stackless_theme_woo_late_block_style($content, array $block)
{
    if (!is_admin() && isset($block['blockName']) && strpos((string) $block['blockName'], 'woocommerce/') === 0) {
        stackless_theme_woo_enqueue_style();
    }
    return $content;
}

add_filter('do_shortcode_tag', 'stackless_theme_woo_late_shortcode_style', 10, 2);
function stackless_theme_woo_late_shortcode_style($output, string $tag)
{
    if (!is_admin() && in_array($tag, stackless_theme_woo_shortcodes(), true)) {
        stackless_theme_woo_enqueue_style();
    }
    return $output;
}
