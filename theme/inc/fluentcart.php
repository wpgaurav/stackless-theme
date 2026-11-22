<?php
/**
 * FluentCart integration: store body classes, the header cart and account links, and the FluentCart
 * stylesheet.
 *
 * The stylesheet loads on every front-end page, as FluentCart's own cart drawer and checkout modal
 * do. It hands the theme's tokens to FluentCart's color and radius properties; colors chosen in
 * FluentCart's settings print later and win.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** A FluentCart page setting: cart, checkout, receipt, shop or customer_profile. 0 when unset. */
function stackless_theme_fluentcart_page_id(string $page): int
{
    static $settings = null;
    if ($settings === null) {
        $settings = class_exists('\FluentCart\Api\StoreSettings') ? (new \FluentCart\Api\StoreSettings())->getPagesSettings() : [];
    }
    return (int) ($settings[$page . '_page_id'] ?? 0);
}

/** Whether a post carries any of these FluentCart shortcodes or blocks. */
function stackless_theme_fluentcart_content_has(WP_Post $post, array $names): bool
{
    foreach ($names as $name) {
        if (has_shortcode($post->post_content, 'fluent_cart_' . $name) || has_block('fluent-cart/' . str_replace('_', '-', $name), $post)) {
            return true;
        }
    }
    return false;
}

/**
 * Products, product archives, the shop, cart, checkout and account use the wide container, so a page
 * title lines up with the store under it. The receipt keeps the reading width.
 */
add_filter('body_class', 'stackless_theme_fluentcart_body_class');
function stackless_theme_fluentcart_body_class(array $classes): array
{
    $product = is_singular('fluent-products');
    $archive = is_post_type_archive('fluent-products') || is_tax((array) get_object_taxonomies('fluent-products'));
    $wide = $product || $archive;
    $receipt = false;
    $checkout = false;

    $post = is_singular() ? get_queried_object() : null;
    if (!$product && $post instanceof WP_Post) {
        $id = (int) $post->ID;
        $is = static function (string $page) use ($id): bool {
            return $id === stackless_theme_fluentcart_page_id($page);
        };
        $checkout = $is('checkout') || stackless_theme_fluentcart_content_has($post, ['checkout']);
        $receipt = $is('receipt') || stackless_theme_fluentcart_content_has($post, ['receipt']);
        $wide = $checkout || $is('cart') || $is('shop') || $is('customer_profile')
            || stackless_theme_fluentcart_content_has($post, ['cart', 'customer_profile', 'products']);
    }
    if (!$wide && !$receipt) {
        return $classes;
    }
    $classes[] = 'is-fluentcart-page';
    if ($product) {
        $classes[] = 'is-fluentcart-product';
    }
    if ($checkout) {
        $classes[] = 'is-fluentcart-checkout';
    }
    if ($receipt) {
        $classes[] = 'is-fluentcart-receipt';
    } elseif (!in_array('is-store-page', $classes, true)) {
        $classes[] = 'is-store-page';
    }
    return $classes;
}

add_action('wp_enqueue_scripts', 'stackless_theme_fluentcart_assets', 25);
function stackless_theme_fluentcart_assets(): void
{
    stackless_theme_style('stackless-theme-fluentcart', 'assets/css/fluentcart.css', ['stackless-theme-layout']);
}

/** Items in the buyer's cart. An instant checkout has a cart of its own, which is not counted. */
function stackless_theme_fluentcart_cart_count(): int
{
    $helper = '\FluentCart\App\Helpers\CartHelper';
    if (!class_exists($helper) || !method_exists($helper, 'getCart')) {
        return 0;
    }
    if (method_exists($helper, 'doingInstantCheckout') && $helper::doingInstantCheckout()) {
        return 0;
    }
    $cart = $helper::getCart(null, false);
    $count = 0;
    foreach ($cart ? (array) $cart->cart_data : [] as $item) {
        $count += (int) ($item['quantity'] ?? 1);
    }
    return $count;
}

/** Header cart link. FluentCart's script keeps every [data-cart-badge-count] current. */
add_filter('stackless_theme_cart_link', 'stackless_theme_fluentcart_cart_link', 20);
function stackless_theme_fluentcart_cart_link(string $html): string
{
    if ($html !== '') {
        return $html;
    }
    $page = stackless_theme_fluentcart_page_id('cart') ?: stackless_theme_fluentcart_page_id('checkout');
    if ($page < 1 || get_post_status($page) !== 'publish') {
        return $html;
    }
    $count = stackless_theme_fluentcart_cart_count();
    stackless_theme_fluentcart_cart_count_script();
    /* translators: %d: number of items in the cart */
    $label = sprintf(_n('Cart, %d item', 'Cart, %d items', $count, 'stackless-theme'), $count);
    return sprintf(
        '<a class="header-action header-cart" href="%1$s" aria-label="%2$s">%3$s<span class="header-cart__count%4$s" data-cart-badge-count data-cart-count-mode="total_quantity" aria-hidden="true">%5$d</span></a>',
        esc_url((string) get_permalink($page)),
        esc_attr($label),
        stackless_theme_icon('shopping-bag', 22),
        $count ? '' : ' is-empty',
        $count
    );
}

/** Shows or hides the header count as FluentCart changes the number. */
function stackless_theme_fluentcart_cart_count_script(): void
{
    if (wp_script_is('stackless-theme-fluentcart-cart', 'enqueued')) {
        return;
    }
    wp_register_script('stackless-theme-fluentcart-cart', false, [], STACKLESS_THEME_VERSION, ['in_footer' => true]);
    wp_enqueue_script('stackless-theme-fluentcart-cart');
    wp_add_inline_script('stackless-theme-fluentcart-cart', "(function(){var c=document.querySelector('.header-cart__count[data-cart-badge-count]');if(!c||!window.MutationObserver){return;}new MutationObserver(function(){c.classList.toggle('is-empty',!(parseInt(c.textContent,10)>0));}).observe(c,{childList:true,characterData:true,subtree:true});})();");
}

add_filter('stackless_theme_account_url', 'stackless_theme_fluentcart_account_url', 20);
function stackless_theme_fluentcart_account_url(string $url): string
{
    if ($url !== '') {
        return $url;
    }
    $page = stackless_theme_fluentcart_page_id('customer_profile');
    return $page > 0 && get_post_status($page) === 'publish' ? (string) get_permalink($page) : '';
}

/** The fallback menu skips FluentCart's cart, checkout, receipt and account pages. */
add_filter('stackless_theme_menu_fallback_exclude', 'stackless_theme_fluentcart_menu_exclude');
function stackless_theme_fluentcart_menu_exclude(array $exclude): array
{
    foreach (['cart', 'checkout', 'receipt', 'customer_profile'] as $page) {
        $exclude[] = stackless_theme_fluentcart_page_id($page);
    }
    return array_values(array_filter($exclude));
}
