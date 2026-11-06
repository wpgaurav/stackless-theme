<?php
/**
 * Page layouts: the Full width and Canvas templates, and per-page options to hide the title,
 * header or footer and to lay the header over the first section.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_TEMPLATE_FULL = 'page-templates/full-width.php';
const STACKLESS_THEME_TEMPLATE_CANVAS = 'page-templates/canvas.php';

/** Per-page layout options: meta key => label. */
function stackless_theme_layout_options(): array
{
    return [
        '_stackless_hide_title' => __('Hide the title', 'stackless-theme'),
        '_stackless_hide_header' => __('Hide the site header', 'stackless-theme'),
        '_stackless_hide_footer' => __('Hide the site footer', 'stackless-theme'),
        '_stackless_transparent_header' => __('Header over the first section', 'stackless-theme'),
    ];
}

/** Post types that get the templates and layout options: every public type but attachments. */
function stackless_theme_layout_post_types(): array
{
    $types = array_values(array_diff(get_post_types(['public' => true]), ['attachment']));
    return (array) apply_filters('stackless_theme_layout_post_types', $types);
}

add_action('init', 'stackless_theme_register_layout_meta', 20);
function stackless_theme_register_layout_meta(): void
{
    foreach (stackless_theme_layout_post_types() as $type) {
        // Block editor meta needs custom-fields support to reach the REST API.
        add_post_type_support($type, 'custom-fields');
        foreach (array_keys(stackless_theme_layout_options()) as $key) {
            register_post_meta($type, $key, [
                'type' => 'boolean',
                'single' => true,
                'default' => false,
                'show_in_rest' => true,
                'sanitize_callback' => 'rest_sanitize_boolean',
                'auth_callback' => static function ($allowed, $meta_key, $post_id): bool {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
        }
    }
}

/** Full width and Canvas on every public post type, beside the plugin and theme templates. */
add_filter('theme_templates', 'stackless_theme_register_templates', 10, 4);
function stackless_theme_register_templates(array $templates, $theme, $post, string $post_type): array
{
    // Products and downloads keep their store templates (and buy box), so they get only the layout options.
    if (in_array($post_type, ['product', 'download'], true) || !in_array($post_type, stackless_theme_layout_post_types(), true)) {
        return $templates;
    }
    $templates[STACKLESS_THEME_TEMPLATE_FULL] = __('Full width (no title)', 'stackless-theme');
    $templates[STACKLESS_THEME_TEMPLATE_CANVAS] = __('Canvas (no header or footer)', 'stackless-theme');
    return $templates;
}

/** A layout flag for the current singular view: 'hide_title', 'hide_header', 'hide_footer' or 'transparent_header'. */
function stackless_theme_layout_flag(string $flag): bool
{
    static $flags = [];
    if (isset($flags[$flag])) {
        return $flags[$flag];
    }
    $on = false;
    if (is_singular()) {
        $id = (int) get_queried_object_id();
        $template = (string) get_page_template_slug($id);
        $on = (bool) get_post_meta($id, '_stackless_' . $flag, true)
            || ($template === STACKLESS_THEME_TEMPLATE_CANVAS && in_array($flag, ['hide_header', 'hide_footer'], true))
            || ($template === STACKLESS_THEME_TEMPLATE_FULL && $flag === 'hide_title');
    }
    $flags[$flag] = (bool) apply_filters('stackless_theme_layout_flag', $on, $flag);
    return $flags[$flag];
}

/** Whether to print the site 'header', site 'footer' or entry 'title'. */
function stackless_theme_shows(string $part): bool
{
    return !stackless_theme_layout_flag('hide_' . $part);
}

add_filter('body_class', 'stackless_theme_layout_body_class');
function stackless_theme_layout_body_class(array $classes): array
{
    if (!stackless_theme_shows('header')) {
        $classes[] = 'no-site-header';
    } elseif (stackless_theme_layout_flag('transparent_header')) {
        $classes[] = 'has-transparent-header';
        if (stackless_theme_header_on_dark()) {
            $classes[] = 'has-header-on-dark';
        }
    }
    if (!stackless_theme_shows('footer')) {
        $classes[] = 'no-site-footer';
    }
    return $classes;
}

/**
 * Whether the clear header sits on a dark first section, so it switches to light text until it turns
 * solid. True when the page shows no title and opens with a cover block left dark (core's default).
 * Filter `stackless_theme_header_on_dark` for other heroes, such as a Page Blocks hero region.
 */
function stackless_theme_header_on_dark(): bool
{
    $dark = false;
    $post = is_singular() ? get_queried_object() : null;
    if ($post instanceof WP_Post && !stackless_theme_shows('title') && !stackless_theme_has_region('hero')) {
        foreach (parse_blocks((string) $post->post_content) as $block) {
            if (empty($block['blockName'])) {
                continue;
            }
            $dark = $block['blockName'] === 'core/cover' && ($block['attrs']['isDark'] ?? true);
            break;
        }
    }
    return (bool) apply_filters('stackless_theme_header_on_dark', $dark);
}

/* ------------------------------------------------------------------ Editor controls */

/** Block editor: a "Layout" panel in the document sidebar. */
add_action('enqueue_block_editor_assets', 'stackless_theme_layout_editor_assets');
function stackless_theme_layout_editor_assets(): void
{
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->post_type, stackless_theme_layout_post_types(), true)) {
        return;
    }
    $js = stackless_theme_asset('assets/js/editor-layout.js');
    wp_enqueue_script('stackless-theme-editor-layout', STACKLESS_THEME_URI . '/' . $js, ['wp-plugins', 'wp-editor', 'wp-data', 'wp-core-data', 'wp-components', 'wp-element', 'wp-i18n'], stackless_theme_asset_version($js), ['in_footer' => true]);
    wp_localize_script('stackless-theme-editor-layout', 'stacklessThemeLayout', [
        'title' => __('Layout', 'stackless-theme'),
        'options' => stackless_theme_layout_options(),
        'transparentHelp' => __('The header sits over the first section and turns solid once you scroll. Give the first section a background and room at the top.', 'stackless-theme'),
    ]);
}

/** Classic editor (WooCommerce products, for one): the same options in a side meta box. */
add_action('add_meta_boxes', 'stackless_theme_layout_meta_box');
function stackless_theme_layout_meta_box(string $post_type): void
{
    if (!in_array($post_type, stackless_theme_layout_post_types(), true)) {
        return;
    }
    add_meta_box('stackless-theme-layout', __('Layout', 'stackless-theme'), 'stackless_theme_layout_meta_box_render', $post_type, 'side', 'default', ['__back_compat_meta_box' => true]);
}

function stackless_theme_layout_meta_box_render(WP_Post $post): void
{
    wp_nonce_field('stackless_theme_layout', 'stackless_theme_layout_nonce');
    foreach (stackless_theme_layout_options() as $key => $label) {
        printf(
            '<p><label><input type="checkbox" name="%1$s" value="1"%2$s> %3$s</label></p>',
            esc_attr($key),
            checked((bool) get_post_meta($post->ID, $key, true), true, false),
            esc_html($label)
        );
    }
}

add_action('save_post', 'stackless_theme_layout_meta_box_save', 10, 2);
function stackless_theme_layout_meta_box_save(int $post_id, WP_Post $post): void
{
    if (!isset($_POST['stackless_theme_layout_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['stackless_theme_layout_nonce'])), 'stackless_theme_layout')) {
        return;
    }
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($post_id) || !current_user_can('edit_post', $post_id)) {
        return;
    }
    foreach (array_keys(stackless_theme_layout_options()) as $key) {
        if (!empty($_POST[$key])) {
            update_post_meta($post_id, $key, true);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
}
