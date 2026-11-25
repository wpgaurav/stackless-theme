<?php
/**
 * Performance: Customize > Stackless Theme > Performance.
 *
 * Each option takes out something WordPress adds to every page that a site on this theme may not
 * need. All are off by default, so updating the theme changes nothing until a box is ticked. Every
 * setting is a theme mod named stackless_perf_*, and every change is front end only: wp-admin, the
 * block editor and the Customizer pane keep what WordPress gives them.
 *
 * Stackless Theme Pro adds store and loading options to the same section.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_PERFORMANCE_SECTION = 'stackless_theme_performance';

/** Option => default. All off: each one removes something, so it waits for the site owner. */
function stackless_theme_performance_defaults(): array
{
    return [
        'stackless_perf_emoji' => false,
        'stackless_perf_head_links' => false,
        'stackless_perf_embeds' => false,
        'stackless_perf_body_classes' => false,
        'stackless_perf_dashicons' => false,
        'stackless_perf_jquery_migrate' => false,
    ];
}

add_filter('stackless_theme_option_defaults', static function (array $defaults): array {
    return $defaults + stackless_theme_performance_defaults();
});

/** Whether a performance option is on. Filter stackless_theme_option_{key} to force one. */
function stackless_theme_performance(string $key): bool
{
    return (bool) stackless_theme_option('stackless_perf_' . $key);
}

/** Front-end page views only: not wp-admin, AJAX, REST, cron, feeds' XML or WP-CLI. */
function stackless_theme_performance_front(): bool
{
    return !is_admin() && !wp_doing_ajax() && !wp_doing_cron() && !(defined('REST_REQUEST') && REST_REQUEST) && !(defined('WP_CLI') && WP_CLI);
}

/* ------------------------------------------------------------------ Customizer */

add_action('customize_register', 'stackless_theme_performance_customize', 11);
function stackless_theme_performance_customize(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_section(STACKLESS_THEME_PERFORMANCE_SECTION, [
        'title' => __('Performance', 'stackless-theme'),
        'description' => __('Leave out what WordPress adds to every page that this site does not need. All start off and none touch wp-admin. If something stops working, untick the last one you ticked.', 'stackless-theme'),
        'panel' => STACKLESS_THEME_PANEL,
        // After Custom code (100); without a store plugin the theme's last section sits lower still.
        'priority' => 110,
    ]);

    $options = [
        'stackless_perf_emoji' => [
            __('Leave out the emoji script', 'stackless-theme'),
            __('Browsers draw emoji themselves. Saves a 23 KB script on every page.', 'stackless-theme'),
        ],
        'stackless_perf_head_links' => [
            __('Leave out extra head links', 'stackless-theme'),
            __('Old blog-editor links (RSD, Windows Live Writer), the shortlink, the REST API link and the WordPress version tag. The REST API itself keeps working.', 'stackless-theme'),
        ],
        'stackless_perf_embeds' => [
            __('Stop other sites embedding your posts as cards', 'stackless-theme'),
            __('Other WordPress sites show a plain link to your posts instead of a card. Your own YouTube, X and other embeds keep working.', 'stackless-theme'),
        ],
        'stackless_perf_body_classes' => [
            __('Shorter body class list', 'stackless-theme'),
            __('Drops classes that name one item, like postid-42 or category-news, and the theme-name classes. Classes the theme and store plugins use stay. Custom CSS aimed at those classes stops matching.', 'stackless-theme'),
        ],
        'stackless_perf_dashicons' => [
            __('Dashicons only for signed-in users', 'stackless-theme'),
            __('The 36 KB admin icon font is left off for visitors; signed-in users keep it for the admin bar. A plugin\'s front-end Dashicons disappear for visitors.', 'stackless-theme'),
        ],
        'stackless_perf_jquery_migrate' => [
            __('Leave out jQuery Migrate', 'stackless-theme'),
            __('A 13 KB helper for jQuery code from before 2016. The theme uses no jQuery. If a slider, form or popup breaks, untick this.', 'stackless-theme'),
        ],
    ];
    $priority = 10;
    foreach ($options as $id => [$label, $description]) {
        $wp_customize->add_setting($id, ['default' => false, 'sanitize_callback' => 'rest_sanitize_boolean']);
        $wp_customize->add_control($id, [
            'label' => $label,
            'description' => $description,
            'section' => STACKLESS_THEME_PERFORMANCE_SECTION,
            'type' => 'checkbox',
            'priority' => $priority,
        ]);
        $priority += 10;
    }
}

/* ------------------------------------------------------------------ Front end */

/**
 * On `wp`, the first front-end action after the Customizer has applied its preview values and
 * before the head, the header links (template_redirect) or any style is printed.
 */
add_action('wp', 'stackless_theme_performance_init', 0);
function stackless_theme_performance_init(): void
{
    if (stackless_theme_performance('emoji')) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
        // Feeds and email keep their emoji as images (wp_staticize_emoji): those readers may not draw them.
    }

    if (stackless_theme_performance('head_links')) {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('template_redirect', 'wp_shortlink_header', 11);
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);
        foreach (['rss2_head', 'atom_head', 'rdf_header', 'rss_head', 'comments_atom_head', 'commentsrss2_head', 'opml_head', 'app_head'] as $feed) {
            remove_action($feed, 'the_generator');
        }
    }

    if (stackless_theme_performance('embeds')) {
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
        remove_action('wp_head', 'wp_oembed_add_host_js');
        add_action('wp_enqueue_scripts', static function (): void {
            wp_dequeue_script('wp-embed');
        }, 100);
    }
}

/**
 * The body classes WordPress adds that name one object (an ID or a slug), plus the default-template,
 * post-format "standard" and theme-name classes. Built from the query rather than matched by
 * pattern, so a plugin's own class that happens to look similar is never touched.
 */
function stackless_theme_performance_noisy_body_classes(): array
{
    $drop = ['wp-theme-' . sanitize_html_class(get_template()), 'theme-' . sanitize_html_class(get_template())];
    if (is_child_theme()) {
        $drop[] = 'wp-child-theme-' . sanitize_html_class(get_stylesheet());
    }

    $object = get_queried_object();
    $id = (int) get_queried_object_id();
    if (is_singular() && $object instanceof WP_Post) {
        $type = sanitize_html_class($object->post_type);
        $drop[] = 'postid-' . $id;
        $drop[] = 'page-id-' . $id;
        $drop[] = 'attachmentid-' . $id;
        $drop[] = $type . '-template-default';
        $drop[] = 'page-template-default';
        $drop[] = 'single-format-standard';
        if ($object->post_parent) {
            $drop[] = 'parent-pageid-' . (int) $object->post_parent;
        }
    } elseif ($object instanceof WP_Term) {
        $tax = $object->taxonomy === 'post_tag' ? 'tag' : ($object->taxonomy === 'category' ? 'category' : 'term');
        $slug = sanitize_html_class($object->slug, (string) $object->term_id);
        $drop[] = $tax . '-' . $slug;
        $drop[] = $tax . '-' . $object->term_id;
    } elseif ($object instanceof WP_User) {
        $drop[] = 'author-' . sanitize_html_class($object->user_nicename, (string) $object->ID);
        $drop[] = 'author-' . $object->ID;
    }

    $paged = (int) get_query_var('paged');
    if ($paged > 1) {
        $drop[] = 'paged-' . $paged;
        foreach (['single', 'page', 'category', 'tag', 'date', 'author', 'search', 'attachment'] as $where) {
            $drop[] = $where . '-paged-' . $paged;
        }
        if (is_post_type_archive()) {
            $drop[] = sanitize_html_class((string) get_query_var('post_type')) . '-paged-' . $paged;
        }
    }

    return (array) apply_filters('stackless_theme_performance_body_classes_removed', $drop);
}

/** Runs last, after the theme, store plugins and Pro have added theirs. */
add_filter('body_class', 'stackless_theme_performance_body_class', PHP_INT_MAX);
function stackless_theme_performance_body_class(array $classes): array
{
    if (!stackless_theme_performance('body_classes') || is_admin()) {
        return $classes;
    }
    return array_values(array_diff(array_unique($classes), stackless_theme_performance_noisy_body_classes()));
}

/**
 * Dashicons for signed-out visitors: off the queue and out of other stylesheets' dependencies, so
 * a stylesheet that lists it still prints, without the icon font.
 */
add_action('wp_print_styles', 'stackless_theme_performance_dashicons', 100);
function stackless_theme_performance_dashicons(): void
{
    if (!stackless_theme_performance('dashicons') || is_user_logged_in() || is_customize_preview() || is_admin_bar_showing()) {
        return;
    }
    $styles = wp_styles();
    $styles->dequeue('dashicons');
    foreach ($styles->registered as $handle => $style) {
        if ($handle !== 'dashicons' && in_array('dashicons', (array) $style->deps, true)) {
            $style->deps = array_values(array_diff($style->deps, ['dashicons']));
        }
    }
}

/**
 * jQuery without Migrate on the front end; wp-admin keeps it. Done when the page's scripts are
 * queued rather than when they are registered, so a Customizer preview sees the box it is showing.
 */
add_action('wp_enqueue_scripts', 'stackless_theme_performance_jquery_migrate', 0);
function stackless_theme_performance_jquery_migrate(): void
{
    $scripts = wp_scripts();
    if (!stackless_theme_performance('jquery_migrate') || !isset($scripts->registered['jquery'])) {
        return;
    }
    $scripts->registered['jquery']->deps = array_values(array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']));
}
