<?php
/**
 * Page Blocks Builder support: theme regions, the edge-to-edge builder template, and the builder
 * preview styled like the site. Every call checks that the plugin's functions exist, so the theme
 * runs the same without it.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** Whether Page Blocks Builder is active. */
function stackless_theme_has_page_blocks(): bool
{
    return function_exists('gt_pb_region') && function_exists('gt_pb_has_region');
}

/** Whether a Page Blocks region has published blocks for this request. */
function stackless_theme_has_region(string $name): bool
{
    return stackless_theme_has_page_blocks() && gt_pb_has_region($name);
}

/** Prints a Page Blocks region when it has blocks. Returns whether it printed. */
function stackless_theme_region(string $name): bool
{
    if (!stackless_theme_has_region($name)) {
        return false;
    }
    gt_pb_region($name);
    return true;
}

/**
 * The site header or footer, or the Page Blocks region of the same name. A filled region replaces
 * the theme's own; filter `stackless_theme_region_mode` to 'before' or 'after' to keep both.
 */
function stackless_theme_chrome(string $name, callable $fallback): void
{
    if (!stackless_theme_has_region($name)) {
        $fallback();
        return;
    }
    $mode = (string) apply_filters('stackless_theme_region_mode', 'replace', $name);
    if ($mode === 'after') {
        $fallback();
    }
    gt_pb_region($name);
    if ($mode === 'before') {
        $fallback();
    }
}

/** Before and after the main content of every theme template. */
function stackless_theme_before_content(): void
{
    do_action('stackless_theme_before_content');
    stackless_theme_region('before_content');
}

function stackless_theme_after_content(): void
{
    stackless_theme_region('after_content');
    do_action('stackless_theme_after_content');
}

/** Store regions of the theme's own, offered in the Page Blocks position list. */
add_filter('gt_pb_positions', 'stackless_theme_page_blocks_positions');
function stackless_theme_page_blocks_positions($positions)
{
    if (!is_array($positions)) {
        return $positions;
    }
    if (class_exists('WooCommerce')) {
        $positions['region:shop_top'] = __('Stackless: Above the shop grid', 'stackless-theme');
    }
    if (class_exists('Easy_Digital_Downloads')) {
        $positions['region:store_top'] = __('Stackless: Above the download grid', 'stackless-theme');
    }
    return $positions;
}

add_action('woocommerce_before_shop_loop', 'stackless_theme_shop_top_region', 3);
function stackless_theme_shop_top_region(): void
{
    stackless_theme_region('shop_top');
}

/**
 * Builder preview: the theme's base layer (tokens, type, forms, buttons), so sections preview in
 * the site's type and colors. The layout layer stays out; it styles the theme's own header,
 * footer and body flex column, which the preview has no use for.
 */
add_filter('gt_page_blocks_builder_preview_style_urls', 'stackless_theme_page_blocks_preview_styles', 10, 2);
function stackless_theme_page_blocks_preview_styles($urls, $post_id = 0)
{
    if (!is_array($urls)) {
        return $urls;
    }
    foreach (['assets/css/base.css'] as $path) {
        $path = stackless_theme_asset($path);
        $urls[] = add_query_arg('ver', stackless_theme_asset_version($path), STACKLESS_THEME_URI . '/' . $path);
    }
    return $urls;
}

/** Builder preview: the design settings on top. */
add_filter('gt_page_blocks_builder_preview_injection', 'stackless_theme_page_blocks_preview_injection', 10, 2);
function stackless_theme_page_blocks_preview_injection($injection, $post_id = 0)
{
    if (!is_array($injection) || !function_exists('stackless_theme_design_css')) {
        return $injection;
    }
    $css = stackless_theme_design_css();
    if ($css !== '') {
        $injection['css'] = trim(($injection['css'] ?? '') . "\n" . $css);
    }
    return $injection;
}
