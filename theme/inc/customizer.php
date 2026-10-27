<?php
/**
 * Theme options in Appearance > Customize > Stackless.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_RELATED_DEFAULT = 6;
const STACKLESS_THEME_RELATED_MAX = 12;

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

add_action('customize_register', 'stackless_theme_customize_register');
function stackless_theme_customize_register(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_section('stackless_theme', [
        'title' => __('Stackless', 'stackless-theme'),
        'priority' => 30,
    ]);

    $controls = [
        'posts' => [
            'label' => __('Related posts', 'stackless-theme'),
            'description' => __('Posts shown below a single post, from the same category first. Set 0 to hide them.', 'stackless-theme'),
        ],
    ];
    if (class_exists('WooCommerce')) {
        $controls['products'] = [
            'label' => __('Related products', 'stackless-theme'),
            'description' => __('Products shown below a single product. Set 0 to hide them.', 'stackless-theme'),
        ];
    }
    if (class_exists('Easy_Digital_Downloads')) {
        $controls['downloads'] = [
            'label' => __('Related downloads', 'stackless-theme'),
            'description' => __('Downloads shown below a single download, from the same category first. Set 0 to hide them.', 'stackless-theme'),
        ];
    }

    foreach ($controls as $type => $control) {
        $wp_customize->add_setting('stackless_related_' . $type, [
            'default' => STACKLESS_THEME_RELATED_DEFAULT,
            'sanitize_callback' => 'stackless_theme_sanitize_related_count',
        ]);
        $wp_customize->add_control('stackless_related_' . $type, $control + [
            'section' => 'stackless_theme',
            'type' => 'number',
            'input_attrs' => ['min' => 0, 'max' => STACKLESS_THEME_RELATED_MAX, 'step' => 1],
        ]);
    }
}
