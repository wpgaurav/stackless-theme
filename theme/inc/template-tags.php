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

/** Header cart link; the count is refreshed by WooCommerce cart fragments. */
function stackless_theme_cart_link(): string
{
    if (!function_exists('WC') || !WC()->cart || !function_exists('wc_get_cart_url')) {
        return '';
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
    $format = $location === 'footer' ? 'code_symbol' : 'code';
    echo do_shortcode(sprintf(
        '[stackless_currency_selector show_label="no" label="%s" format="%s" class="st-currency st-currency--%s"]',
        esc_attr__('Currency', 'stackless-theme'),
        $format,
        sanitize_html_class($location)
    ));
}
