<?php
/**
 * Easy Digital Downloads integration: theme support, store body classes, the header cart and
 * account links, the single download and archive templates, and the EDD stylesheet, which loads
 * only on EDD surfaces.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

add_action('after_setup_theme', 'stackless_theme_edd_setup', 20);
function stackless_theme_edd_setup(): void
{
    add_theme_support('stackless-edd');
    add_post_type_support('download', ['thumbnail', 'excerpt']);
}

/** EDD shortcodes and blocks that put store markup on a page. */
function stackless_theme_edd_markers(): array
{
    return [
        'shortcodes' => ['downloads', 'purchase_link', 'download_cart', 'download_checkout', 'edd_receipt', 'purchase_history', 'download_history', 'edd_profile_editor', 'edd_login', 'edd_register', 'download_discounts', 'purchase_collection', 'stackless_edd_account'],
        'blocks' => ['edd/downloads', 'edd/buy-button', 'edd/cart', 'edd/checkout', 'edd/receipt', 'edd/confirmation', 'edd/order-history', 'edd/user-downloads', 'edd/profile-editor', 'edd/login', 'edd/register', 'edd/terms'],
    ];
}

/** Whether a post's content carries EDD blocks or shortcodes. */
function stackless_theme_edd_content_has_store($post = null): bool
{
    $post = get_post($post);
    if (!$post instanceof WP_Post || $post->post_content === '') {
        return false;
    }
    $markers = stackless_theme_edd_markers();
    foreach ($markers['blocks'] as $block) {
        if (has_block($block, $post)) {
            return true;
        }
    }
    foreach ($markers['shortcodes'] as $shortcode) {
        if (has_shortcode($post->post_content, $shortcode)) {
            return true;
        }
    }
    return false;
}

/** The Stackless for EDD account page, or 0. */
function stackless_theme_edd_account_page_id(): int
{
    return class_exists('\StacklessEDD\Core\Account') ? (int) \StacklessEDD\Core\Account::pageId() : 0;
}

/** True on downloads, download archives, EDD's own pages and any page with EDD blocks, shortcodes or the cart widget. */
function stackless_theme_is_edd_surface(): bool
{
    static $is = null;
    if ($is !== null) {
        return $is;
    }
    $is = is_singular('download') || is_post_type_archive('download') || is_tax(['download_category', 'download_tag'])
        || (function_exists('edd_is_checkout') && edd_is_checkout())
        || (function_exists('edd_is_success_page') && edd_is_success_page())
        || (function_exists('edd_is_failed_transaction_page') && edd_is_failed_transaction_page())
        || (function_exists('edd_is_purchase_history_page') && edd_is_purchase_history_page())
        || (is_singular() && stackless_theme_edd_content_has_store())
        || is_active_widget(false, false, 'edd_cart_widget')
        || is_active_widget(false, false, 'edd_categories_tags_widget')
        || is_active_widget(false, false, 'edd_product_details');
    $is = (bool) apply_filters('stackless_theme_is_edd_surface', $is);
    return $is;
}

/** Layout of the Checkout block on the current page ('full' unless the block sets another). */
function stackless_theme_edd_checkout_layout(): string
{
    $post = get_post();
    if (!$post instanceof WP_Post || !has_block('edd/checkout', $post)) {
        return '';
    }
    $find = static function (array $blocks) use (&$find): string {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === 'edd/checkout') {
                return (string) ($block['attrs']['layout'] ?? 'full');
            }
            if (!empty($block['innerBlocks'])) {
                $layout = $find($block['innerBlocks']);
                if ($layout !== '') {
                    return $layout;
                }
            }
        }
        return '';
    };
    return $find(parse_blocks($post->post_content));
}

/**
 * Store pages use the wide container: the account page, pages with a downloads grid or category
 * grid, and a checkout whose block puts the cart beside the form. A one-column checkout keeps the
 * reading width so the form never stretches across the screen.
 */
add_filter('body_class', 'stackless_theme_edd_body_class');
function stackless_theme_edd_body_class(array $classes): array
{
    if (!stackless_theme_is_edd_surface()) {
        return $classes;
    }
    $classes[] = 'is-edd-page';
    if (!is_singular() || is_singular('download')) {
        return $classes;
    }
    $post = get_post();
    $wide = (int) get_queried_object_id() === stackless_theme_edd_account_page_id() && stackless_theme_edd_account_page_id() > 0;
    if (!$wide && $post instanceof WP_Post) {
        $wide = has_block('edd/downloads', $post) || has_block('edd/terms', $post) || has_shortcode($post->post_content, 'downloads')
            || has_shortcode($post->post_content, 'stackless_edd_account');
        $layout = stackless_theme_edd_checkout_layout();
        if ($layout !== '' && $layout !== 'full') {
            $wide = true;
        }
    }
    if ($wide && !in_array('is-store-page', $classes, true)) {
        $classes[] = 'is-store-page';
    }
    return $classes;
}

/** The EDD layer loads after EDD's own stylesheet, and only where EDD prints markup. */
add_action('wp_enqueue_scripts', 'stackless_theme_edd_assets', 25);
function stackless_theme_edd_assets(): void
{
    if (!stackless_theme_is_edd_surface()) {
        return;
    }
    stackless_theme_edd_enqueue_style();
}

function stackless_theme_edd_enqueue_style(): void
{
    if (wp_style_is('stackless-theme-edd', 'enqueued') || !function_exists('stackless_theme_style')) {
        return;
    }
    $deps = ['stackless-theme-layout'];
    foreach (['edd-styles', 'edd-blocks', 'stackless-edd-account'] as $handle) {
        if (wp_style_is($handle, 'registered')) {
            $deps[] = $handle;
        }
    }
    stackless_theme_style('stackless-theme-edd', 'assets/css/edd.css', $deps);
}

/**
 * EDD blocks and shortcodes placed somewhere the checks above cannot see (a widget, a template part,
 * a page builder) still get the layer; it then prints in the footer.
 */
add_filter('render_block', 'stackless_theme_edd_late_block_style', 10, 2);
function stackless_theme_edd_late_block_style($content, array $block)
{
    if (!is_admin() && isset($block['blockName']) && strpos((string) $block['blockName'], 'edd/') === 0) {
        stackless_theme_edd_enqueue_style();
    }
    return $content;
}

add_filter('do_shortcode_tag', 'stackless_theme_edd_late_shortcode_style', 10, 2);
function stackless_theme_edd_late_shortcode_style($output, string $tag)
{
    if (!is_admin() && in_array($tag, stackless_theme_edd_markers()['shortcodes'], true)) {
        stackless_theme_edd_enqueue_style();
    }
    return $output;
}

/** Header cart link: count of items, linking to checkout. EDD's AJAX keeps .edd-cart-quantity current. */
add_filter('stackless_theme_cart_link', 'stackless_theme_edd_cart_link');
function stackless_theme_edd_cart_link(string $html): string
{
    if ($html !== '' || !function_exists('edd_get_cart_quantity') || !function_exists('edd_get_checkout_uri')) {
        return $html;
    }
    $count = (int) edd_get_cart_quantity();
    /* translators: %d: number of items in the cart */
    $label = sprintf(_n('Cart, %d item', 'Cart, %d items', $count, 'stackless-theme'), $count);
    return sprintf(
        '<a class="header-action header-cart" href="%1$s" aria-label="%2$s">%3$s<span class="header-cart__count edd-cart-quantity%4$s" aria-hidden="true">%5$d</span></a>',
        esc_url(edd_get_checkout_uri()),
        esc_attr($label),
        stackless_theme_icon('shopping-bag', 22),
        $count ? '' : ' is-empty',
        $count
    );
}

/** Header account link: the Stackless account page, else EDD's purchase history or login page. */
add_filter('stackless_theme_account_url', 'stackless_theme_edd_account_url');
function stackless_theme_edd_account_url(string $url): string
{
    if ($url !== '') {
        return $url;
    }
    $page = stackless_theme_edd_account_page_id();
    if ($page < 1 && function_exists('edd_get_option')) {
        $page = (int) edd_get_option(is_user_logged_in() ? 'purchase_history_page' : 'login_page', 0)
            ?: (int) edd_get_option('purchase_history_page', 0);
    }
    return $page > 0 ? (string) get_permalink($page) : '';
}

/**
 * Shows the header count once EDD's AJAX sets a number (the server marks an empty cart is-empty).
 * A handle of its own keeps the theme script deferred.
 */
add_action('wp_enqueue_scripts', 'stackless_theme_edd_cart_count_script', 30);
function stackless_theme_edd_cart_count_script(): void
{
    wp_register_script('stackless-theme-edd-cart', false, [], STACKLESS_THEME_VERSION, ['in_footer' => true]);
    wp_enqueue_script('stackless-theme-edd-cart');
    wp_add_inline_script('stackless-theme-edd-cart', "(function(){var c=document.querySelector('.header-cart__count.edd-cart-quantity');if(!c||!window.MutationObserver){return;}new MutationObserver(function(){c.classList.toggle('is-empty',!(parseInt(c.textContent,10)>0));}).observe(c,{childList:true,characterData:true,subtree:true});})();");
}

/** The fallback menu skips EDD's checkout, receipt, history and account pages. */
add_filter('stackless_theme_menu_fallback_exclude', 'stackless_theme_edd_menu_exclude');
function stackless_theme_edd_menu_exclude(array $exclude): array
{
    if (function_exists('edd_get_option')) {
        foreach (['purchase_page', 'success_page', 'failure_page', 'purchase_history_page', 'confirmation_page', 'login_page'] as $option) {
            $exclude[] = (int) edd_get_option($option, 0);
        }
    }
    $exclude[] = stackless_theme_edd_account_page_id();
    return array_values(array_filter($exclude));
}

/** Downloads, download categories and download tags share one archive template. */
add_filter('taxonomy_template', 'stackless_theme_edd_taxonomy_template');
function stackless_theme_edd_taxonomy_template(string $template): string
{
    if (is_tax(['download_category', 'download_tag'])) {
        $archive = locate_template('archive-download.php');
        if ($archive) {
            return $archive;
        }
    }
    return $template;
}

/** Twelve downloads per archive page, three rows of four or four rows of three. */
add_action('pre_get_posts', 'stackless_theme_edd_archive_query');
function stackless_theme_edd_archive_query(WP_Query $query): void
{
    if (is_admin() || !$query->is_main_query()) {
        return;
    }
    if ($query->is_post_type_archive('download') || $query->is_tax(['download_category', 'download_tag'])) {
        $query->set('posts_per_page', (int) apply_filters('stackless_theme_downloads_per_page', 12));
    }
}

/**
 * The single download template prints the purchase form in its summary column, so EDD's copy under
 * the content is removed there. Everything else hooked to edd_after_download_content still runs.
 */
add_action('template_redirect', 'stackless_theme_edd_single_hooks');
function stackless_theme_edd_single_hooks(): void
{
    if (is_singular('download') && locate_template('single-download.php')) {
        remove_action('edd_after_download_content', 'edd_append_purchase_link');
    }
}

/** Price, or price range, for a download, as plain markup safe inside a card. */
function stackless_theme_edd_price_html(int $download_id): string
{
    if (!function_exists('edd_price')) {
        return '';
    }
    if (function_exists('edd_is_free_download') && edd_is_free_download($download_id)) {
        return '<span class="edd_price">' . esc_html__('Free', 'stackless-theme') . '</span>';
    }
    if (edd_has_variable_prices($download_id)) {
        return '<span class="edd_price edd_price_range">' . wp_kses_post(edd_price_range($download_id)) . '</span>';
    }
    return (string) edd_price($download_id, false);
}

/** The purchase form for a download, unless its "hide purchase link" option is on. */
function stackless_theme_edd_purchase_form(int $download_id, array $args = []): string
{
    if (!function_exists('edd_get_purchase_link') || get_post_meta($download_id, '_edd_hide_purchase_link', true)) {
        return '';
    }
    return (string) edd_get_purchase_link(['download_id' => $download_id] + $args);
}

/** Related downloads for the single download page: same category first, then tag, then newest. */
function stackless_theme_edd_related_downloads(int $download_id, int $count): array
{
    if ($count < 1) {
        return [];
    }
    $ids = [];
    foreach (['download_category', 'download_tag'] as $taxonomy) {
        $terms = wp_get_post_terms($download_id, $taxonomy, ['fields' => 'ids']);
        if (is_wp_error($terms) || !$terms || count($ids) >= $count) {
            continue;
        }
        $ids = array_merge($ids, get_posts([
            'post_type' => 'download',
            'post_status' => 'publish',
            'posts_per_page' => $count - count($ids),
            'post__not_in' => array_merge([$download_id], $ids),
            'tax_query' => [['taxonomy' => $taxonomy, 'terms' => $terms]], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
            'fields' => 'ids',
            'no_found_rows' => true,
        ]));
    }
    if (count($ids) < $count) {
        $ids = array_merge($ids, get_posts([
            'post_type' => 'download',
            'post_status' => 'publish',
            'posts_per_page' => $count - count($ids),
            'post__not_in' => array_merge([$download_id], $ids),
            'fields' => 'ids',
            'no_found_rows' => true,
        ]));
    }
    return array_map('intval', $ids);
}
