<?php
/**
 * Stylesheet parts: layout.css and edd.css are split into a core file and parts that only some
 * views need. Each view gets the core file plus the parts it prints markup for, decided before the
 * head is printed, from the template WordPress chose, the queried object and the page's content.
 *
 * Parts print straight after their core file, in the order the rules had in the single file, so
 * the cascade is unchanged. Inline CSS other code attached to the core handle moves behind the
 * last part, where it printed before the split. Views the theme cannot be sure about (a child
 * theme's template, a plugin's template, the Customizer preview) get every part.
 *
 * Filters: stackless_theme_layout_parts (array of part names), stackless_theme_edd_parts (the
 * same for edd.css), and stackless_theme_split_css (false: every part on every view).
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** The layout parts in print order (their order in the former single layout.css). */
const STACKLESS_THEME_LAYOUT_PARTS = ['transparent', 'archive', 'single', 'comments', 'store', 'search', 'toolbar'];

/** The edd.css parts in print order (inc/edd.php decides which a view needs). */
const STACKLESS_THEME_EDD_PARTS = ['grid', 'single', 'cart', 'checkout', 'account'];

/** The template file WordPress is about to load, recorded last so every filter has had its say. */
add_filter('template_include', 'stackless_theme_record_template', PHP_INT_MAX);
function stackless_theme_record_template($template)
{
    $GLOBALS['stackless_theme_template'] = is_string($template) ? $template : '';
    return $template;
}

/** The theme's file name for the template in use ('singular.php', 'page-templates/canvas.php'), or '' for any other file. */
function stackless_theme_current_template(): string
{
    $file = (string) ($GLOBALS['stackless_theme_template'] ?? '');
    if ($file === '') {
        return '';
    }
    $file = wp_normalize_path($file);
    $theme = trailingslashit(wp_normalize_path(STACKLESS_THEME_DIR));
    return strpos($file, $theme) === 0 ? substr($file, strlen($theme)) : '';
}

/** Whether stylesheets load as parts. Off (every part everywhere) in the Customizer preview, where options change the page live. */
function stackless_theme_split_css(): bool
{
    return (bool) apply_filters('stackless_theme_split_css', !is_customize_preview());
}

/** Whether the store toolbar prints on this view (inc/store.php). */
function stackless_theme_store_toolbar_shows(): bool
{
    if (!function_exists('stackless_theme_store_option')) {
        return false;
    }
    if (class_exists('Easy_Digital_Downloads') && stackless_theme_store_option('edd', 'toolbar')) {
        $edd_page = function_exists('stackless_theme_edd_store_page_id') ? stackless_theme_edd_store_page_id() : 0;
        if (is_post_type_archive('download') || is_tax(['download_category', 'download_tag'])
            || (is_search() && get_query_var('post_type') === 'download')
            || ($edd_page > 0 && is_page($edd_page))) {
            return true;
        }
    }
    return class_exists('WooCommerce') && stackless_theme_store_option('woo', 'toolbar')
        && function_exists('stackless_theme_is_woo_surface') && stackless_theme_is_woo_surface();
}

/** The layout parts this view needs, in print order. */
function stackless_theme_layout_parts(): array
{
    static $parts = null;
    if ($parts !== null) {
        return $parts;
    }
    if (!stackless_theme_split_css()) {
        $parts = STACKLESS_THEME_LAYOUT_PARTS;
    } else {
        $template = stackless_theme_current_template();
        $woo = function_exists('is_woocommerce') && is_woocommerce();
        $known = ['singular.php', 'index.php', '404.php', 'archive-download.php', 'single-download.php', 'single-fluent-products.php', 'page-templates/full-width.php', 'page-templates/canvas.php'];
        // A template from somewhere else (child theme, plugin) may print any of the theme's markup.
        $unknown = !$woo && !in_array($template, $known, true);
        $id = (int) get_queried_object_id();
        $toolbar = stackless_theme_store_toolbar_shows();
        $parts = [];
        if ($unknown || $template === 'index.php') {
            $parts[] = 'archive';
        }
        if ($unknown || $template === 'singular.php') {
            $parts[] = 'single';
        }
        if (is_singular() && $id && (comments_open($id) || get_comments_number($id))) {
            $parts[] = 'comments';
        }
        if (stackless_theme_store_setup() || defined('STACKLESS_VERSION')) {
            $parts[] = 'store';
        }
        if ($toolbar || stackless_theme_option('stackless_header_search')) {
            $parts[] = 'search';
        }
        if ($toolbar) {
            $parts[] = 'toolbar';
        }
        if (in_array('has-transparent-header', get_body_class(), true)) {
            $parts[] = 'transparent';
        }
    }
    /**
     * Filters the layout.css parts a view loads: transparent, archive, single, comments, store,
     * search, toolbar. They always print in that order.
     *
     * @param string[] $parts
     */
    $parts = (array) apply_filters('stackless_theme_layout_parts', $parts);
    $parts = array_values(array_intersect(STACKLESS_THEME_LAYOUT_PARTS, $parts));
    return $parts;
}

/** Queues each part of a split stylesheet after its core handle: stackless-theme-{core}-{part}. */
function stackless_theme_style_parts(string $core, array $parts): void
{
    foreach ($parts as $part) {
        $handle = 'stackless-theme-' . $core . '-' . $part;
        if (wp_style_is($handle, 'enqueued')) {
            continue;
        }
        $path = 'assets/css/' . $core . '-' . $part . '.css';
        stackless_theme_style($handle, $path, ['stackless-theme-' . $core]);
        // A small part may be printed inline in its place (wp_maybe_inline_styles), saving a request.
        $file = STACKLESS_THEME_DIR . '/' . stackless_theme_asset($path);
        if (is_readable($file) && (int) filesize($file) <= (int) apply_filters('stackless_theme_inline_part_limit', 4096, $handle)) {
            wp_style_add_data($handle, 'path', $file);
        }
    }
}

/**
 * Inline CSS added to a core handle (wp_add_inline_style) printed after the whole file before the
 * split. It moves behind the last queued part, so it still prints after every rule of the file.
 */
add_action('wp_print_styles', 'stackless_theme_style_parts_inline', 1);
function stackless_theme_style_parts_inline(): void
{
    $styles = wp_styles();
    foreach (['layout' => STACKLESS_THEME_LAYOUT_PARTS, 'edd' => STACKLESS_THEME_EDD_PARTS] as $core => $all) {
        $core_handle = 'stackless-theme-' . $core;
        $after = $styles->get_data($core_handle, 'after');
        if (!$after) {
            continue;
        }
        $last = '';
        foreach ($all as $part) {
            if (wp_style_is($core_handle . '-' . $part, 'enqueued')) {
                $last = $core_handle . '-' . $part;
            }
        }
        if ($last === '') {
            continue;
        }
        $styles->add_data($last, 'after', array_merge((array) ($styles->get_data($last, 'after') ?: []), (array) $after));
        $styles->add_data($core_handle, 'after', []);
    }
}
