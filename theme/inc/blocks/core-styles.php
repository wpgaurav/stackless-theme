<?php
/**
 * Core block style variations: the "is-style-*" looks Marketers Delight adds to core blocks.
 *
 * Every variation keeps Marketers Delight's block, name and label, so content saved with one
 * needs no change. A block's variations live in assets/css/blocks/core-<block>.css, which loads
 * in the editor and, on the front end, only on a page where a block uses one of them.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Style variations per core block: block name => [style name => label].
 *
 * Holds the legacy variations too (see stackless_theme_blocks_core_legacy_styles()), so their
 * stylesheet still loads for content that already uses them.
 */
function stackless_theme_blocks_core_styles(): array
{
    static $styles = null;
    if ($styles !== null) {
        return $styles;
    }

    $styles = [
        'core/paragraph' => [
            'warning' => __('Warning', 'stackless-theme'),
            'error' => __('Error', 'stackless-theme'),
            'info' => __('Info', 'stackless-theme'),
            'tip' => __('Tip', 'stackless-theme'),
            'note' => __('Note', 'stackless-theme'),
            'lead' => __('Lead', 'stackless-theme'),
            'quote' => __('Quote', 'stackless-theme'),
            'key-point' => __('Key Point', 'stackless-theme'),
        ],
        'core/heading' => [
            'serif' => __('Serif', 'stackless-theme'),
            'accent-bar' => __('Accent Bar', 'stackless-theme'),
            'section' => __('Section', 'stackless-theme'),
        ],
        'core/list' => [
            'numbered' => __('Numbered', 'stackless-theme'),
            'list-checkmark' => __('Checkmark', 'stackless-theme'),
            'list-cross' => __('Cross', 'stackless-theme'),
        ],
        'core/image' => [
            'rounder' => __('Rounder', 'stackless-theme'),
            'browser-shot' => __('Browser Shot', 'stackless-theme'),
        ],
        'core/post-featured-image' => [
            'rounder' => __('Rounder', 'stackless-theme'),
            'browser-shot' => __('Browser Shot', 'stackless-theme'),
        ],
        'core/query' => [
            'md-cards' => __('MD Cards', 'stackless-theme'),
            'md-magazine' => __('MD Magazine', 'stackless-theme'),
            'md-list' => __('MD List', 'stackless-theme'),
        ],
        'core/group' => [
            'aside' => __('Aside L', 'stackless-theme'),
            'aside-right' => __('Aside R', 'stackless-theme'),
            'bordered-accent' => __('Bordered Accent', 'stackless-theme'),
            'dashed' => __('Dashed', 'stackless-theme'),
        ],
        'core/button' => [
            'primary' => __('Primary', 'stackless-theme'),
            'secondary' => __('Secondary', 'stackless-theme'),
            // Marketers Delight also lists "outline". That one is WordPress's own button
            // variation, which the theme already styles, so it is not registered again and a
            // page that uses it loads nothing from here.
            'link' => __('Link', 'stackless-theme'),
        ],
        'core/table' => [
            'small-table' => __('Small Table', 'stackless-theme'),
            'striped' => __('Striped', 'stackless-theme'),
            'ratings' => __('Ratings', 'stackless-theme'),
            'services' => __('Services', 'stackless-theme'),
            'comparison' => __('Comparison', 'stackless-theme'),
        ],
        'core/cover' => [
            'rounded-cover' => __('Rounded', 'stackless-theme'),
        ],
        'core/video' => [
            'media-boxed' => __('Boxed', 'stackless-theme'),
        ],
    ];

    foreach (stackless_theme_blocks_core_legacy_styles() as $block => $legacy) {
        $styles[$block] = array_merge($styles[$block] ?? [], $legacy);
    }

    $styles = (array) apply_filters('stackless_theme_blocks_core_styles', $styles);
    return $styles;
}

/**
 * Variations Marketers Delight keeps for existing content but leaves out of the editor's style
 * picker: decorative and duplicate choices. Their CSS still loads where content uses them.
 * Return true from the stackless_theme_blocks_core_legacy_styles_enabled filter to offer them
 * in the picker as well.
 */
function stackless_theme_blocks_core_legacy_styles(): array
{
    return [
        'core/paragraph' => [
            'notice' => __('Notice', 'stackless-theme'),
            'minimal' => __('Minimal', 'stackless-theme'),
            'intro' => __('Intro', 'stackless-theme'),
            'serif' => __('Serif', 'stackless-theme'),
            'glass' => __('Glass', 'stackless-theme'),
            'spotlight' => __('Spotlight', 'stackless-theme'),
            'ribbon-box' => __('Ribbon', 'stackless-theme'),
            'numbered-box' => __('Numbered', 'stackless-theme'),
            'aside' => __('Aside L', 'stackless-theme'),
            'aside-right' => __('Aside R', 'stackless-theme'),
        ],
        'core/heading' => [
            'label' => __('Label', 'stackless-theme'),
            'numbered' => __('Numbered', 'stackless-theme'),
            'centered-heading' => __('Centered', 'stackless-theme'),
            'thick-border' => __('Thick Border', 'stackless-theme'),
            'box-heading' => __('Box Heading', 'stackless-theme'),
            'ribbon' => __('Ribbon', 'stackless-theme'),
            'highlight' => __('Highlight', 'stackless-theme'),
            'sticker' => __('Sticker', 'stackless-theme'),
            'brackets' => __('Brackets', 'stackless-theme'),
        ],
        'core/list' => [
            'intro' => __('Intro', 'stackless-theme'),
            'serif' => __('Serif', 'stackless-theme'),
            'small' => __('Small', 'stackless-theme'),
            'large' => __('Large', 'stackless-theme'),
            'checked' => __('Checked', 'stackless-theme'),
            'cancel' => __('Cancel', 'stackless-theme'),
            'list' => __('List', 'stackless-theme'),
            'arrow-square-right' => __('Arrow Right', 'stackless-theme'),
        ],
        'core/image' => [
            'shadow' => __('Shadow', 'stackless-theme'),
            'shadow-round' => __('Shadow Round', 'stackless-theme'),
        ],
        'core/post-featured-image' => [
            'shadow' => __('Shadow', 'stackless-theme'),
            'shadow-round' => __('Shadow Round', 'stackless-theme'),
        ],
        'core/group' => [
            'glass' => __('Glass', 'stackless-theme'),
            'soft-glow' => __('Soft Glow', 'stackless-theme'),
            'raised' => __('Raised', 'stackless-theme'),
            'spotlight' => __('Spotlight', 'stackless-theme'),
        ],
        'core/button' => [
            'glow' => __('Glow', 'stackless-theme'),
            'pill' => __('Pill', 'stackless-theme'),
            '3d' => __('3D', 'stackless-theme'),
            'ghost' => __('Ghost', 'stackless-theme'),
            'shine' => __('Shine', 'stackless-theme'),
            'arrow' => __('Arrow', 'stackless-theme'),
        ],
        'core/separator' => [
            'dotted-sep' => __('Dotted', 'stackless-theme'),
            'dashed-sep' => __('Dashed', 'stackless-theme'),
            'wave-sep' => __('Wave', 'stackless-theme'),
            'slash-sep' => __('Slash', 'stackless-theme'),
        ],
        'core/cover' => [
            'blur-image' => __('Blur Image', 'stackless-theme'),
            'blur-image-more' => __('Blur More', 'stackless-theme'),
        ],
        'core/column' => [
            'column-box-shadow' => __('Box Shadow', 'stackless-theme'),
        ],
    ];
}

/** The stylesheet handle for a core block's variations: core/paragraph => stackless-core-paragraph. */
function stackless_theme_blocks_core_style_handle(string $block): string
{
    return 'stackless-core-' . substr($block, 5);
}

/**
 * Registers the variations with the editor and one stylesheet handle per block.
 *
 * The handle is not passed to register_block_style(): core would then print the file on every
 * page that has the block, whether or not the block uses a variation.
 */
add_action('init', 'stackless_theme_blocks_core_styles_register');
function stackless_theme_blocks_core_styles_register(): void
{
    $hidden = apply_filters('stackless_theme_blocks_core_legacy_styles_enabled', false)
        ? []
        : stackless_theme_blocks_core_legacy_styles();

    foreach (stackless_theme_blocks_core_styles() as $block => $styles) {
        $block = (string) $block;
        foreach ((array) $styles as $name => $label) {
            if (!isset($hidden[$block][$name])) {
                register_block_style($block, ['name' => (string) $name, 'label' => (string) $label]);
            }
        }

        if (strpos($block, 'core/') !== 0) {
            continue;
        }
        $path = stackless_theme_asset('assets/css/blocks/core-' . substr($block, 5) . '.css');
        $file = STACKLESS_THEME_DIR . '/' . $path;
        if (!is_readable($file)) {
            continue;
        }
        $handle = stackless_theme_blocks_core_style_handle($block);
        wp_register_style($handle, STACKLESS_THEME_URI . '/' . $path, [], stackless_theme_asset_version($path));
        // Lets WordPress inline the small ones instead of requesting them.
        wp_style_add_data($handle, 'path', $file);
    }
}

/**
 * Front end: a block's variation stylesheet loads when a rendered block of that type carries
 * one of its "is-style-<name>" classes, and not otherwise.
 */
add_filter('render_block', 'stackless_theme_blocks_core_style_enqueue', 10, 2);
function stackless_theme_blocks_core_style_enqueue($content, array $block)
{
    $class = $block['attrs']['className'] ?? '';
    if (!is_string($class) || strpos($class, 'is-style-') === false) {
        return $content;
    }

    $name = (string) ($block['blockName'] ?? '');
    $styles = stackless_theme_blocks_core_styles()[$name] ?? [];
    if (!$styles) {
        return $content;
    }

    $handle = stackless_theme_blocks_core_style_handle($name);
    if (wp_style_is($handle) || !wp_style_is($handle, 'registered')) {
        return $content;
    }

    foreach (preg_split('/\s+/', $class, -1, PREG_SPLIT_NO_EMPTY) as $token) {
        if (strpos($token, 'is-style-') === 0 && isset($styles[substr($token, 9)])) {
            wp_enqueue_style($handle);
            break;
        }
    }
    return $content;
}

/** Editor: every variation stylesheet, so each one previews in the canvas and the style picker. */
add_action('enqueue_block_assets', 'stackless_theme_blocks_core_styles_editor');
function stackless_theme_blocks_core_styles_editor(): void
{
    if (!is_admin()) {
        return;
    }
    foreach (array_keys(stackless_theme_blocks_core_styles()) as $block) {
        $handle = stackless_theme_blocks_core_style_handle((string) $block);
        if (wp_style_is($handle, 'registered')) {
            wp_enqueue_style($handle);
        }
    }
}
