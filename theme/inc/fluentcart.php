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

    // Keyboard and screen-reader behavior for FluentCart's cart drawer, mobile filters and checkout summary.
    $js = stackless_theme_asset('assets/js/fluentcart.js');
    wp_enqueue_script('stackless-theme-fluentcart', STACKLESS_THEME_URI . '/' . $js, ['stackless-theme-focus'], stackless_theme_asset_version($js), ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('stackless-theme-fluentcart', 'stacklessFluentCart', [
        'i18n' => [
            'filters' => __('Filters', 'stackless-theme'),
            /* translators: word after the item count in the cart drawer heading, as in "Shopping Cart (1 item)" */
            'item'    => _n('item', 'items', 1, 'stackless-theme'),
            /* translators: word after the item count in the cart drawer heading, as in "Shopping Cart (3 items)" */
            'items'   => _n('item', 'items', 2, 'stackless-theme'),
        ],
    ]);
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

/** Keeps the header count's visibility and the link's label in step as FluentCart changes the number. */
function stackless_theme_fluentcart_cart_count_script(): void
{
    if (wp_script_is('stackless-theme-fluentcart-cart', 'enqueued')) {
        return;
    }
    wp_register_script('stackless-theme-fluentcart-cart', false, [], STACKLESS_THEME_VERSION, ['in_footer' => true]);
    wp_enqueue_script('stackless-theme-fluentcart-cart');
    // PHP picks the plural form. Gettext's plural rules repeat every 100 from 100, so counts 0-199 cover every number.
    $forms = [];
    $map = [];
    for ($n = 0; $n < 200; $n++) {
        /* translators: %d: number of items in the cart */
        $form = _n('Cart, %d item', 'Cart, %d items', $n, 'stackless-theme');
        if (!in_array($form, $forms, true)) {
            $forms[] = $form;
        }
        $map[] = array_search($form, $forms, true);
    }
    $labels = wp_json_encode(['forms' => $forms, 'map' => implode('', $map)], JSON_HEX_TAG | JSON_HEX_AMP);
    wp_add_inline_script('stackless-theme-fluentcart-cart', "(function(l){var c=document.querySelector('.header-cart__count[data-cart-badge-count]');if(!c||!window.MutationObserver){return;}function sync(){var n=parseInt(c.textContent,10)||0,f=l.forms[+l.map.charAt(n<200?n:100+n%100)];c.classList.toggle('is-empty',n<1);if(f){c.parentNode.setAttribute('aria-label',f.replace(/%(1\\$)?d/,n));}}new MutationObserver(sync).observe(c,{childList:true,characterData:true,subtree:true});})(" . $labels . ");");
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

/** The fallback menu skips FluentCart's cart, checkout, receipt, order review and account pages. */
add_filter('stackless_theme_menu_fallback_exclude', 'stackless_theme_fluentcart_menu_exclude');
function stackless_theme_fluentcart_menu_exclude(array $exclude): array
{
    foreach (['cart', 'checkout', 'receipt', 'order_review', 'customer_profile'] as $page) {
        $exclude[] = stackless_theme_fluentcart_page_id($page);
    }
    return array_values(array_filter($exclude));
}

/**
 * Single products use the theme's own template, laid out like a WooCommerce product or an EDD
 * download: breadcrumb, the gallery beside a sticky summary, then the description and related
 * products. FluentCart's automatic product header is switched off on those pages so nothing prints
 * twice.
 */
function stackless_theme_fluentcart_single_ready(): bool
{
    // A product on a page template (Page Blocks Builder's, or the theme's Full width and Canvas)
    // gets that template; the theme's product layout steps aside.
    return is_singular('fluent-products')
        && (string) get_page_template_slug((int) get_queried_object_id()) === ''
        && class_exists('\FluentCart\App\Services\Renderer\ProductRenderer')
        && class_exists('\FluentCart\App\Modules\Data\ProductDataSetup')
        && locate_template('single-fluent-products.php') !== '';
}

add_filter('template_include', 'stackless_theme_fluentcart_single_template', 20);
function stackless_theme_fluentcart_single_template(string $template): string
{
    if (!stackless_theme_fluentcart_single_ready()) {
        return $template;
    }
    return locate_template('single-fluent-products.php') ?: $template;
}

add_filter('fluent_cart/disable_auto_single_product_page', 'stackless_theme_fluentcart_disable_auto_single', 20);
function stackless_theme_fluentcart_disable_auto_single($disabled)
{
    return stackless_theme_fluentcart_single_ready() ? true : $disabled;
}

/** FluentCart's renderer for a product, or null when the product or the classes are missing. */
function stackless_theme_fluentcart_renderer(int $product_id)
{
    $product = \FluentCart\App\Modules\Data\ProductDataSetup::getProductModel($product_id);
    if (!$product || !$product->detail) {
        return null;
    }
    $settings = class_exists('\FluentCart\Api\StoreSettings') ? new \FluentCart\Api\StoreSettings() : null;
    return new \FluentCart\App\Services\Renderer\ProductRenderer($product, [
        'view_type'   => $settings ? $settings->get('variation_view', 'both') : 'both',
        'column_type' => $settings ? $settings->get('variation_columns', 'masonry') : 'masonry',
    ]);
}

/** Related products in FluentCart's own card list, when its Product Page setting shows them. */
function stackless_theme_fluentcart_related(int $product_id): void
{
    if (!class_exists('\FluentCart\Api\StoreSettings') || !class_exists('\FluentCart\App\Services\Renderer\ProductListRenderer') || !class_exists('\FluentCart\Api\Resource\ShopResource')) {
        return;
    }
    $show = stackless_theme_option('stackless_store_related')
        && (new \FluentCart\Api\StoreSettings())->get('show_relevant_product_in_single_page') === 'yes';
    if (!apply_filters('fluent_cart/single_product_page/show_relevant_products', $show, $product_id)) {
        return;
    }
    $products = \FluentCart\Api\Resource\ShopResource::getSimilarProducts($product_id, false);
    if (!$products || (is_countable($products) && !count($products))) {
        return;
    }
    echo '<section class="st-related-downloads st-fc-related">';
    (new \FluentCart\App\Services\Renderer\ProductListRenderer(
        $products,
        stackless_theme_text('related_products_title', __('Related products', 'stackless-theme'), ['store' => 'fluentcart']),
        'fct-similar-product-list-container',
        ['rating_context' => 'relevant']
    ))->render();
    echo '</section>';
}

/**
 * Whether a FluentCart product has a variation priced below its compare-at price. Answers are kept
 * per request, so a card that already holds its product model costs no second query.
 */
function stackless_theme_fluentcart_model_on_sale($product): bool
{
    static $seen = [];
    if (!is_object($product) || empty($product->ID)) {
        return false;
    }
    $id = (int) $product->ID;
    if (!isset($seen[$id])) {
        $seen[$id] = false;
        // Read the relation directly: empty() on a model property that is not loaded yet is true.
        $variants = $product->variants;
        foreach (is_iterable($variants) ? $variants : [] as $variant) {
            $compare = (int) ($variant->compare_price ?? 0);
            if ($compare > 0 && $compare > (int) ($variant->item_price ?? 0)) {
                $seen[$id] = true;
                break;
            }
        }
    }
    return $seen[$id];
}

/** The theme's own answer for FluentCart products; plugins filter after it at the default priority. */
add_filter('stackless_theme_product_on_sale', 'stackless_theme_fluentcart_on_sale', 5, 3);
function stackless_theme_fluentcart_on_sale($on_sale, int $post_id, string $store): bool
{
    if ($store !== 'fluentcart' || $post_id < 1 || !class_exists('\FluentCart\App\Modules\Data\ProductDataSetup')) {
        return (bool) $on_sale;
    }
    return stackless_theme_fluentcart_model_on_sale(\FluentCart\App\Modules\Data\ProductDataSetup::getProductModel($post_id));
}

/**
 * The sale badge on the product cards FluentCart draws itself (product shortcodes, lists, related
 * products), over the image. Cards built from FluentCart's blocks are left alone: those templates
 * carry FluentCart's own Sale badge block, placed (or removed) by the site owner.
 */
add_action('fluent_cart/product/group/before_image_block', 'stackless_theme_fluentcart_card_sale_badge');
function stackless_theme_fluentcart_card_sale_badge($payload): void
{
    $product = is_array($payload) ? ($payload['product'] ?? null) : null;
    if (!is_object($product) || empty($product->ID) || ($payload['scope'] ?? '') !== 'product_card' || ($payload['source'] ?? '') === 'gutenberg') {
        return;
    }
    stackless_theme_fluentcart_model_on_sale($product);
    stackless_theme_sale_badge(['store' => 'fluentcart', 'post_id' => (int) $product->ID, 'placement' => 'card']);
}

/** The shop page, for the empty search state and other "browse all" links. */
add_filter('stackless_theme_shop_url', 'stackless_theme_fluentcart_shop_url');
function stackless_theme_fluentcart_shop_url(string $url): string
{
    if ($url !== '') {
        return $url;
    }
    $page = stackless_theme_fluentcart_page_id('shop');
    if ($page > 0 && get_post_status($page) === 'publish') {
        return (string) get_permalink($page);
    }
    return (string) get_post_type_archive_link('fluent-products');
}

/** A product in search results shows its image and FluentCart's own card price, not a blog teaser. */
add_filter('stackless_theme_search_product', 'stackless_theme_fluentcart_search_product', 10, 2);
function stackless_theme_fluentcart_search_product($product, WP_Post $post)
{
    if ($product !== null || $post->post_type !== 'fluent-products' || !class_exists('\FluentCart\App\Modules\Data\ProductDataSetup')) {
        return $product;
    }
    $model = \FluentCart\App\Modules\Data\ProductDataSetup::getProductModel($post->ID);
    if (!$model || !$model->detail) {
        return $product;
    }
    $image = '';
    if (has_post_thumbnail($post)) {
        $image = get_the_post_thumbnail($post, 'medium', ['loading' => 'lazy', 'alt' => '']);
    } else {
        $url = is_array($model->detail->featured_media) ? (string) ($model->detail->featured_media['url'] ?? '') : '';
        if ($url !== '') {
            $image = sprintf('<img src="%s" alt="" loading="lazy" decoding="async">', esc_url($url));
        }
    }
    $price = '';
    if (class_exists('\FluentCart\App\Services\Renderer\ProductCardRender')) {
        ob_start();
        (new \FluentCart\App\Services\Renderer\ProductCardRender($model))->renderPrices();
        $price = (string) ob_get_clean();
    }
    return ['image' => $image, 'price' => $price];
}
