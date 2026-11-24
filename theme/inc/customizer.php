<?php
/**
 * Theme options in Appearance > Customize > Stackless Theme: one panel holding Colors, Typography,
 * Layout, Header, Blog, Store, the shop toolbars, Footer and Custom code. The store options follow
 * the store plugins that are active, so a FluentCart site sees FluentCart's choices and nothing
 * for WooCommerce or Easy Digital Downloads.
 *
 * Setting IDs are unchanged from earlier versions; only the sections they appear in moved.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_RELATED_DEFAULT = 6;
const STACKLESS_THEME_RELATED_MAX = 12;
const STACKLESS_THEME_PANEL = 'stackless_theme_panel';

/**
 * Default for every on/off and choice option. Filter stackless_theme_option_defaults to ship a
 * site layout's defaults: they apply wherever the site owner has not saved a value of their own.
 */
function stackless_theme_option_defaults(): array
{
    return (array) apply_filters('stackless_theme_option_defaults', stackless_theme_core_option_defaults());
}

/** The theme's own defaults, before stackless_theme_option_defaults. */
function stackless_theme_core_option_defaults(): array
{
    return [
        'stackless_mobile_menu' => 'dropdown',
        'stackless_header_sticky' => true,
        'stackless_header_search' => true,
        'stackless_blog_sidebar' => true,
        'stackless_archive_layout' => 'list',
        'stackless_archive_image' => true,
        'stackless_archive_meta' => true,
        'stackless_archive_excerpt' => true,
        'stackless_excerpt_length' => 28,
        'stackless_archive_read_more' => true,
        'stackless_archive_prefix' => true,
        'stackless_archive_description' => true,
        'stackless_single_category' => true,
        'stackless_single_standfirst' => true,
        'stackless_single_author' => true,
        'stackless_single_date' => 'published',
        'stackless_single_reading_time' => true,
        'stackless_single_image' => 'wide',
        'stackless_single_tags' => true,
        'stackless_single_author_box' => true,
        'stackless_single_post_nav' => true,
        'stackless_related_title' => '',
        'stackless_single_layout' => 'centered',
        'stackless_single_sidebar_sticky' => true,
        'stackless_single_header' => 'center',
        'stackless_single_breadcrumbs' => false,
        'stackless_single_category_position' => 'above',
        'stackless_single_byline_position' => 'below',
        'stackless_footer_credit' => true,
        'stackless_store_header_cart' => true,
        'stackless_store_header_account' => true,
        'stackless_store_breadcrumb' => true,
        'stackless_store_categories' => true,
        'stackless_store_related' => true,
        'stackless_store_live_search' => true,
        'stackless_store_search_results' => true,
        'stackless_store_search_links' => true,
    ];
}

/**
 * A theme option, with its default when it has never been saved. The value passes through core's
 * theme_mod_{$key} filter and then stackless_theme_option_{$key} and stackless_theme_option.
 */
function stackless_theme_option(string $key)
{
    $defaults = stackless_theme_option_defaults();
    $value = get_theme_mod($key, $defaults[$key] ?? null);
    $value = is_bool($defaults[$key] ?? null) ? (bool) $value : $value;
    $value = apply_filters("stackless_theme_option_{$key}", $value);
    return apply_filters('stackless_theme_option', $value, $key);
}

/**
 * The store plugins Stackless styles that are active, keyed fluentcart, woocommerce and edd, each
 * with its display name. Empty when the site has no store.
 */
function stackless_theme_store_setup(): array
{
    return array_filter([
        'fluentcart' => defined('FLUENTCART_VERSION') ? 'FluentCart' : '',
        'woocommerce' => class_exists('WooCommerce') ? 'WooCommerce' : '',
        'edd' => class_exists('Easy_Digital_Downloads') ? 'Easy Digital Downloads' : '',
    ]);
}

/** How many related items to show for 'posts', 'products' or 'downloads'. 0 hides the section. */
function stackless_theme_related_count(string $type): int
{
    $count = (int) get_theme_mod('stackless_related_' . $type, STACKLESS_THEME_RELATED_DEFAULT);
    return max(0, min(STACKLESS_THEME_RELATED_MAX, $count));
}

function stackless_theme_sanitize_related_count($value): int
{
    return max(0, min(STACKLESS_THEME_RELATED_MAX, (int) $value));
}

function stackless_theme_sanitize_mobile_menu($value): string
{
    return in_array($value, ['dropdown', 'drawer'], true) ? $value : 'dropdown';
}

/** Body classes for the header options. */
add_filter('body_class', 'stackless_theme_option_body_class');
function stackless_theme_option_body_class(array $classes): array
{
    if (stackless_theme_option('stackless_mobile_menu') === 'drawer') {
        $classes[] = 'has-menu-drawer';
    }
    if (!stackless_theme_option('stackless_header_sticky')) {
        $classes[] = 'has-static-header';
    }
    return $classes;
}

/** The blog sidebar follows its Blog option. */
add_filter('stackless_theme_blog_sidebar', static function ($show) {
    return $show && stackless_theme_option('stackless_blog_sidebar');
});

/** With live results off, the header search is a plain site search. Runs after the stores add their endpoint. */
add_filter('stackless_theme_search_config', static function (array $config): array {
    if (!stackless_theme_option('stackless_store_live_search')) {
        $config['productsApi'] = '';
    }
    return $config;
}, 99);

/** Search results show products as plain post summaries when that option is off. Runs after the stores fill it in. */
add_filter('stackless_theme_search_product', static function ($product) {
    return stackless_theme_option('stackless_store_search_results') ? $product : null;
}, 99);

add_action('customize_register', 'stackless_theme_customize_register');
function stackless_theme_customize_register(WP_Customize_Manager $wp_customize): void
{
    $description = __('Colors, type, header, store and footer options for the Stackless theme.', 'stackless-theme');
    // One line about Pro, only while Pro is not active.
    if (!defined('STACKLESS_PRO_VERSION')) {
        $description .= '<br>' . sprintf(
            /* translators: %s: "Stackless Theme Pro", linked to its product page */
            esc_html__('More header layouts, design presets and modules come with %s.', 'stackless-theme'),
            '<a href="' . esc_url('https://gauravtiwari.org/product/stackless-theme-pro/') . '" target="_blank" rel="noopener">' . esc_html__('Stackless Theme Pro', 'stackless-theme') . '</a>'
        );
    }
    $wp_customize->add_panel(STACKLESS_THEME_PANEL, [
        'title' => __('Stackless Theme', 'stackless-theme'),
        'description' => $description,
        'priority' => 25,
    ]);

    $sections = [
        'stackless_theme_colors' => [__('Colors', 'stackless-theme'), __('Leave a color empty to keep the theme default.', 'stackless-theme')],
        'stackless_theme_typography' => [__('Typography', 'stackless-theme'), __('Fonts for body text and headings. System fonts load nothing extra.', 'stackless-theme')],
        'stackless_theme_layout' => [__('Layout', 'stackless-theme'), __('Content widths and page transitions. Leave a width empty to keep the default.', 'stackless-theme')],
        'stackless_theme_header' => [__('Header and menu', 'stackless-theme'), __('The bar at the top of every page and the menu on phones and tablets.', 'stackless-theme')],
        'stackless_theme' => [__('Blog and archives', 'stackless-theme'), __('The blog page, category, tag, author and date archives, custom post type archives and search results.', 'stackless-theme')],
        'stackless_theme_single' => [__('Single posts', 'stackless-theme'), __('What a blog post shows around its content.', 'stackless-theme')],
    ];
    $store = stackless_theme_store_setup();
    if ($store) {
        $sections['stackless_theme_store'] = [
            __('Store', 'stackless-theme'),
            /* translators: %s: store plugin names, e.g. "FluentCart" */
            sprintf(__('Store elements the theme adds. Detected: %s. Options for store plugins that are not active stay hidden.', 'stackless-theme'), implode(', ', $store)),
        ];
    }
    $sections['stackless_theme_footer'] = [__('Footer', 'stackless-theme'), ''];
    $sections['stackless_theme_code'] = [__('Custom code', 'stackless-theme'), __('Analytics, verification tags and similar snippets. Printed on every front-end page.', 'stackless-theme')];

    $priority = 10;
    foreach ($sections as $id => [$title, $description]) {
        $wp_customize->add_section($id, [
            'title' => $title,
            'description' => $description,
            'panel' => STACKLESS_THEME_PANEL,
            'priority' => $priority,
        ]);
        // Custom post types (content-options.php) sit at 65; the shop toolbars (store.php) after Store at 75.
        $priority += $id === 'stackless_theme_store' ? 20 : 10;
    }

    $checkbox = static function (string $id, string $section, string $label, string $description = '', ?callable $active = null) use ($wp_customize): void {
        $wp_customize->add_setting($id, ['default' => stackless_theme_option_defaults()[$id], 'sanitize_callback' => 'rest_sanitize_boolean']);
        $wp_customize->add_control($id, array_filter([
            'label' => $label,
            'description' => $description,
            'section' => $section,
            'type' => 'checkbox',
            'active_callback' => $active,
        ]));
    };

    /* Header and menu */
    $wp_customize->add_setting('stackless_mobile_menu', ['default' => 'dropdown', 'sanitize_callback' => 'stackless_theme_sanitize_mobile_menu']);
    $wp_customize->add_control('stackless_mobile_menu', [
        'label' => __('Mobile menu style', 'stackless-theme'),
        'description' => __('How the menu opens below 1080px wide.', 'stackless-theme'),
        'section' => 'stackless_theme_header',
        'type' => 'radio',
        'choices' => [
            'dropdown' => __('Dropdown below the header', 'stackless-theme'),
            'drawer' => __('Full-screen drawer', 'stackless-theme'),
        ],
    ]);
    $checkbox('stackless_header_sticky', 'stackless_theme_header', __('Keep the header in view while scrolling', 'stackless-theme'), __('Off, the header scrolls away with the page.', 'stackless-theme'));
    $checkbox('stackless_header_search', 'stackless_theme_header', __('Search button', 'stackless-theme'), __('Opens full-screen search from the header.', 'stackless-theme'));

    /* Blog */
    $checkbox('stackless_blog_sidebar', 'stackless_theme', __('Sidebar on the blog, archives and search', 'stackless-theme'), __('Shows the Blog widget area, or search, recent posts and categories until widgets are added.', 'stackless-theme'));
    $related = [
        'posts' => [
            'section' => 'stackless_theme_single',
            'label' => __('Related posts', 'stackless-theme'),
            'description' => __('How many to show below the post, from the same category first. 0 hides them.', 'stackless-theme'),
            'priority' => 200,
        ],
    ];

    /* Store: only what the active store plugins can use. */
    if ($store) {
        $checkbox('stackless_store_header_cart', 'stackless_theme_store', __('Cart button in the header', 'stackless-theme'), __('With the item count. Hide it for stores that only sell through Buy Now.', 'stackless-theme'));
        $checkbox('stackless_store_header_account', 'stackless_theme_store', __('Account button in the header', 'stackless-theme'), __('Links to the customer account page, or sign-in for visitors.', 'stackless-theme'));
        $checkbox('stackless_store_breadcrumb', 'stackless_theme_store', __('Breadcrumb on product pages', 'stackless-theme'));
        $checkbox('stackless_store_categories', 'stackless_theme_store', __('Category line on product pages', 'stackless-theme'));
        if (isset($store['fluentcart'])) {
            $checkbox('stackless_store_related', 'stackless_theme_store', __('Related products on FluentCart product pages', 'stackless-theme'), __('Also needs "Show Relevant In Single Page" on in FluentCart store settings, under Product Page.', 'stackless-theme'));
        }
        if (isset($store['woocommerce'])) {
            $related['products'] = [
                'section' => 'stackless_theme_store',
                'label' => __('Related WooCommerce products', 'stackless-theme'),
                'description' => __('Shown below a single product. 0 hides them.', 'stackless-theme'),
            ];
        }
        if (isset($store['edd'])) {
            $related['downloads'] = [
                'section' => 'stackless_theme_store',
                'label' => __('Related downloads', 'stackless-theme'),
                'description' => __('Shown below a single download, from the same category first. 0 hides them.', 'stackless-theme'),
            ];
        }
        if (isset($store['woocommerce']) || isset($store['edd'])) {
            $checkbox('stackless_store_live_search', 'stackless_theme_store', __('Live product results in header search', 'stackless-theme'), __('Products appear while typing. Off, search goes straight to the results page.', 'stackless-theme'));
        }
        $checkbox('stackless_store_search_results', 'stackless_theme_store', __('Products in search results show image and price', 'stackless-theme'), __('Off, products are listed like posts, with an excerpt.', 'stackless-theme'));
        $checkbox('stackless_store_search_links', 'stackless_theme_store', __('Shop links when a search finds nothing', 'stackless-theme'), __('Browse all products and the product categories that have products.', 'stackless-theme'));
    }

    foreach ($related as $type => $control) {
        $wp_customize->add_setting('stackless_related_' . $type, [
            'default' => STACKLESS_THEME_RELATED_DEFAULT,
            'sanitize_callback' => 'stackless_theme_sanitize_related_count',
        ]);
        $wp_customize->add_control('stackless_related_' . $type, $control + [
            'type' => 'number',
            'input_attrs' => ['min' => 0, 'max' => STACKLESS_THEME_RELATED_MAX, 'step' => 1],
        ]);
    }
}

/**
 * Fires once the theme has registered its own sections and controls (here at 10, content options at
 * 12, design at 15, the shop toolbars at 20), so an add-on's sections sort predictably among them.
 *
 * Section priorities in the Stackless Theme panel: Colors 10, Typography 20, Layout 30, Header and
 * menu 40, Blog and archives 50, Single posts 60, Custom post types 65, Store 70, the shop toolbars
 * 75 (WooCommerce) and 76 (EDD), Footer 90 and Custom code 100. Without a store plugin there is no
 * Store section or toolbar, and Footer and Custom code move up to 70 and 80.
 */
add_action('customize_register', 'stackless_theme_customize_sections_action', 50);
function stackless_theme_customize_sections_action(WP_Customize_Manager $wp_customize): void
{
    /**
     * Add sections to the Stackless Theme panel, or change the theme's.
     *
     * @param WP_Customize_Manager $wp_customize The Customizer.
     * @param string               $panel_id     The Stackless Theme panel ID (STACKLESS_THEME_PANEL).
     */
    do_action('stackless_theme_customize_sections', $wp_customize, STACKLESS_THEME_PANEL);
}
