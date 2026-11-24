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

/**
 * Per-post choices that override a Customizer setting for one post: meta key => label, the post
 * types it applies to, and its choices. An empty value follows the Customizer on posts, and keeps
 * a page or custom post type item as it is (centered, with its usual title).
 *
 * The layout and header style apply to posts, pages and public custom post types: the types in
 * stackless_theme_layout_post_types() except store products, which have templates of their own.
 */
function stackless_theme_layout_choices(): array
{
    $default = __('Site default (Customizer)', 'stackless-theme');
    $types = array_values(array_diff(stackless_theme_layout_post_types(), ['product', 'download', 'fluent-products']));
    return (array) apply_filters('stackless_theme_layout_choices', [
        '_stackless_single_layout' => [
            'label' => __('Post layout', 'stackless-theme'),
            'types' => $types,
            'choices' => [
                '' => $default,
                'centered' => __('Centered, no sidebar', 'stackless-theme'),
                'sidebar-right' => __('Sidebar on the right', 'stackless-theme'),
                'sidebar-left' => __('Sidebar on the left', 'stackless-theme'),
            ],
        ],
        '_stackless_single_header' => [
            'label' => __('Header style', 'stackless-theme'),
            'types' => $types,
            'choices' => [
                '' => $default,
                'center' => __('Centered title', 'stackless-theme'),
                'left' => __('Left-aligned title', 'stackless-theme'),
                'image-first' => __('Featured image above the title', 'stackless-theme'),
                'image-full' => __('Full-width featured image above the post and sidebar', 'stackless-theme'),
            ],
        ],
    ]);
}

/**
 * The choices one post type's Layout panel offers. Pages and custom post types have no Customizer
 * setting behind them, so their empty choice reads "Default" and the layout select "Sidebar".
 */
function stackless_theme_layout_choices_for(string $post_type): array
{
    $choices = array_filter(stackless_theme_layout_choices(), static function (array $choice) use ($post_type): bool {
        return in_array($post_type, (array) ($choice['types'] ?? []), true);
    });
    if ($post_type === 'post') {
        return $choices;
    }
    foreach ($choices as $key => $choice) {
        if (isset($choice['choices'][''])) {
            $choices[$key]['choices'][''] = __('Default', 'stackless-theme');
        }
    }
    if (isset($choices['_stackless_single_layout']) && $choices['_stackless_single_layout']['label'] === __('Post layout', 'stackless-theme')) {
        $choices['_stackless_single_layout']['label'] = __('Sidebar', 'stackless-theme');
    }
    return $choices;
}

/** A per-post choice for the post being viewed, or '' to follow the Customizer. */
function stackless_theme_layout_choice(string $key): string
{
    $choices = stackless_theme_layout_choices();
    if (!isset($choices[$key]) || !is_singular($choices[$key]['types'])) {
        return '';
    }
    $value = (string) get_post_meta((int) get_queried_object_id(), $key, true);
    return array_key_exists($value, $choices[$key]['choices']) ? $value : '';
}

/**
 * Page templates that bring their own layout: the theme's Full width and Canvas and Page Blocks
 * Builder's two. A post on one of them skips the single-post layout options.
 */
function stackless_theme_layout_owning_templates(): array
{
    return (array) apply_filters('stackless_theme_layout_owning_templates', [
        STACKLESS_THEME_TEMPLATE_FULL,
        STACKLESS_THEME_TEMPLATE_CANVAS,
        'page-blocks-builder.php',
        'page-blocks-full-builder.php',
    ]);
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
        foreach (stackless_theme_layout_choices() as $key => $choice) {
            if (!in_array($type, $choice['types'], true)) {
                continue;
            }
            $allowed = array_keys($choice['choices']);
            register_post_meta($type, $key, [
                'type' => 'string',
                'single' => true,
                'default' => '',
                'show_in_rest' => true,
                'sanitize_callback' => static function ($value) use ($allowed): string {
                    return in_array((string) $value, $allowed, true) ? (string) $value : '';
                },
                'auth_callback' => static function ($allowed_cap, $meta_key, $post_id): bool {
                    return current_user_can('edit_post', (int) $post_id);
                },
            ]);
        }
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
        'choices' => array_map(static function (array $choice): array {
            return [
                'label' => $choice['label'],
                'options' => array_map(static function ($value, $label): array {
                    return ['value' => (string) $value, 'label' => $label];
                }, array_keys($choice['choices']), array_values($choice['choices'])),
            ];
        }, stackless_theme_layout_choices_for($screen->post_type)),
        'owningTemplates' => stackless_theme_layout_owning_templates(),
        'owningHelp' => __('This post uses a page template with its own layout, so the post layout and header style do not apply.', 'stackless-theme'),
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
    foreach (stackless_theme_layout_choices_for($post->post_type) as $key => $choice) {
        $current = (string) get_post_meta($post->ID, $key, true);
        printf('<p><label for="%1$s">%2$s</label><br><select id="%1$s" name="%1$s" style="width:100%%">', esc_attr($key), esc_html($choice['label']));
        foreach ($choice['choices'] as $value => $label) {
            printf('<option value="%1$s"%2$s>%3$s</option>', esc_attr((string) $value), selected($current, (string) $value, false), esc_html($label));
        }
        echo '</select></p>';
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
    foreach (stackless_theme_layout_choices() as $key => $choice) {
        if (!in_array($post->post_type, $choice['types'], true) || !isset($_POST[$key])) {
            continue;
        }
        $value = sanitize_key(wp_unslash($_POST[$key]));
        if ($value !== '' && array_key_exists($value, $choice['choices'])) {
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }
}
