<?php
/**
 * Theme supports, menus, widget areas and assets.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

if (!isset($content_width)) {
    $content_width = 720; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- core global.
}

add_action('after_setup_theme', 'stackless_theme_setup');
function stackless_theme_setup(): void
{
    load_theme_textdomain('stackless-theme', STACKLESS_THEME_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('automatic-feed-links');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);
    add_theme_support('custom-logo', ['height' => 64, 'width' => 240, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('customize-selective-refresh-widgets');

    // Block Editor.
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_theme_support('link-color');
    add_theme_support('border');
    add_theme_support('appearance-tools');
    add_editor_style([stackless_theme_asset('assets/css/base.css'), stackless_theme_asset('assets/css/editor.css')]);

    register_nav_menus([
        'primary' => __('Primary menu', 'stackless-theme'),
        'footer' => __('Footer menu', 'stackless-theme'),
    ]);
}

add_action('widgets_init', 'stackless_theme_widgets');
function stackless_theme_widgets(): void
{
    register_sidebar([
        'name' => __('Footer', 'stackless-theme'),
        'id' => 'footer',
        'description' => __('Widgets in the site footer, laid out in columns.', 'stackless-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ]);
    register_sidebar([
        'name' => __('Blog', 'stackless-theme'),
        'id' => 'blog',
        'description' => __('Beside the post list on the blog, post archives and search results. Leave empty to show search, recent posts and categories.', 'stackless-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ]);
    if (class_exists('WooCommerce')) {
        register_sidebar([
            'name' => __('Shop', 'stackless-theme'),
            'id' => 'shop',
            'description' => __('Filters and widgets above the product grid. Leave empty for a clean shop.', 'stackless-theme'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h2 class="widget-title">',
            'after_title' => '</h2>',
        ]);
    }
}

/**
 * Whether the blog sidebar shows: on the posts page, post archives and search results,
 * never on WooCommerce screens. Filter `stackless_theme_blog_sidebar` to turn it off.
 */
function stackless_theme_has_blog_sidebar(): bool
{
    $show = (is_home() || is_search() || is_category() || is_tag() || is_author() || is_date()
        || (is_post_type_archive('post') || (is_tax() && is_object_in_taxonomy('post', get_queried_object()->taxonomy ?? '')))
    ) && !(function_exists('is_woocommerce') && is_woocommerce());
    return (bool) apply_filters('stackless_theme_blog_sidebar', $show);
}

/** Asset version from the file's modification time, so edits bust browser and CDN caches. */
function stackless_theme_asset_version(string $path): string
{
    $file = STACKLESS_THEME_DIR . '/' . $path;
    return is_readable($file) ? STACKLESS_THEME_VERSION . '.' . filemtime($file) : STACKLESS_THEME_VERSION;
}

/** The minified copy of an asset beside its source, unless SCRIPT_DEBUG is on or no copy exists. */
function stackless_theme_asset(string $path): string
{
    if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
        return $path;
    }
    $min = preg_replace('/\.(css|js)$/', '.min.$1', $path);
    return is_readable(STACKLESS_THEME_DIR . '/' . $min) ? $min : $path;
}

function stackless_theme_style(string $handle, string $path, array $deps = []): void
{
    $path = stackless_theme_asset($path);
    wp_enqueue_style($handle, STACKLESS_THEME_URI . '/' . $path, $deps, stackless_theme_asset_version($path));
}

add_action('wp_enqueue_scripts', 'stackless_theme_assets', 20);
function stackless_theme_assets(): void
{
    stackless_theme_style('stackless-theme-base', 'assets/css/base.css');
    stackless_theme_style('stackless-theme-layout', 'assets/css/layout.css', ['stackless-theme-base']);
    if (class_exists('WooCommerce')) {
        $deps = ['stackless-theme-layout'];
        foreach (['woocommerce-general', 'wc-blocks-style'] as $handle) {
            if (wp_style_is($handle, 'registered')) {
                $deps[] = $handle;
            }
        }
        stackless_theme_style('stackless-theme-woocommerce', 'assets/css/woocommerce.css', $deps);
    }
    if (defined('STACKLESS_VERSION')) {
        stackless_theme_style('stackless-theme-stackless', 'assets/css/stackless.css', ['stackless-theme-layout']);
    }

    $js = stackless_theme_asset('assets/js/theme.js');
    wp_enqueue_script('stackless-theme', STACKLESS_THEME_URI . '/' . $js, [], stackless_theme_asset_version($js), ['in_footer' => true, 'strategy' => 'defer']);
    wp_localize_script('stackless-theme', 'stacklessTheme', apply_filters('stackless_theme_search_config', [
        'productsApi' => class_exists('WooCommerce') ? esc_url_raw(rest_url('wc/store/v1/products')) : '',
        'postType' => 'product',
        'searchUrl' => esc_url_raw(home_url('/')),
        'i18n' => [
            'noResults' => __('No products match "%s". Try a shorter or different word.', 'stackless-theme'),
            'results' => __('Products for "%s"', 'stackless-theme'),
            'searching' => __('Searching…', 'stackless-theme'),
        ],
    ]));

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }
}

/** Stackless module styles load late; print the theme's Stackless layer after them. */
add_action('wp_print_styles', 'stackless_theme_stackless_order', 99);
function stackless_theme_stackless_order(): void
{
    global $wp_styles;
    if (!$wp_styles instanceof WP_Styles || !wp_style_is('stackless-theme-stackless', 'enqueued')) {
        return;
    }
    foreach ($wp_styles->queue as $handle) {
        if ($handle !== 'stackless-theme-stackless' && strpos($handle, 'stackless-') === 0 && strpos($handle, 'stackless-theme') !== 0) {
            $wp_styles->registered['stackless-theme-stackless']->deps[] = $handle;
        }
    }
}

add_filter('body_class', 'stackless_theme_body_class');
function stackless_theme_body_class(array $classes): array
{
    $classes[] = 'stackless-theme';
    if (is_singular() && !is_front_page() && has_post_thumbnail()) {
        $classes[] = 'has-featured-image';
    }
    return $classes;
}

/** Adds a "skip to content" target and marks the current menu item for screen readers. */
add_filter('nav_menu_link_attributes', 'stackless_theme_menu_link_attributes', 10, 2);
function stackless_theme_menu_link_attributes(array $atts, $item): array
{
    if (is_object($item) && !empty($item->current)) {
        $atts['aria-current'] = 'page';
    }
    return $atts;
}

add_filter('excerpt_length', static function (): int {
    return 28;
});
add_filter('excerpt_more', static function (): string {
    return '&hellip;';
});
