<?php
/**
 * Design settings (colors, widths, fonts, logo), the footer text, header and footer code, and view
 * transitions. Everything lives in theme mods and prints only when it differs from the defaults,
 * so an untouched site gets no extra markup.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** Color settings: theme mod suffix => [label, CSS custom property, base.css default]. */
function stackless_theme_design_colors(): array
{
    return [
        'primary' => [__('Primary (links and buttons)', 'stackless-theme'), '--st-primary', '#066fd1'],
        'text' => [__('Text', 'stackless-theme'), '--st-text', '#262626'],
        'heading' => [__('Headings', 'stackless-theme'), '--st-heading', '#171717'],
        'bg' => [__('Page background', 'stackless-theme'), '--st-bg', '#fafafa'],
        'surface' => [__('Cards and header', 'stackless-theme'), '--st-surface', '#ffffff'],
    ];
}

/** Font stacks offered for body and headings. '' keeps the theme's system sans. */
function stackless_theme_font_stacks(): array
{
    return [
        '' => [__('System sans (default)', 'stackless-theme'), ''],
        'serif' => [__('System serif', 'stackless-theme'), 'Charter, "Bitstream Charter", "Sitka Text", Cambria, Georgia, serif'],
        'humanist' => [__('Humanist sans', 'stackless-theme'), 'Seravek, "Gill Sans Nova", Ubuntu, Calibri, "DejaVu Sans", source-sans-pro, sans-serif'],
        'geometric' => [__('Geometric sans', 'stackless-theme'), 'Avenir, Montserrat, Corbel, "URW Gothic", source-sans-pro, sans-serif'],
        'rounded' => [__('Rounded sans', 'stackless-theme'), 'ui-rounded, "Hiragino Maru Gothic ProN", Quicksand, Comfortaa, Manjari, "Arial Rounded MT", Calibri, source-sans-pro, sans-serif'],
        'mono' => [__('Monospace', 'stackless-theme'), 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace'],
        'custom' => [__('Custom stack', 'stackless-theme'), ''],
    ];
}

/** Width limits in px: setting => [min, max, default]. */
function stackless_theme_design_widths(): array
{
    return [
        'content' => [560, 960, 720],
        'wide' => [960, 1600, 1200],
    ];
}

/** A width in px clamped to its range; 0 keeps the default. */
function stackless_theme_clamp_width(string $type, $value): int
{
    $value = absint($value);
    if ($value === 0 || !isset(stackless_theme_design_widths()[$type])) {
        return 0;
    }
    [$min, $max] = stackless_theme_design_widths()[$type];
    return max($min, min($max, $value));
}

function stackless_theme_sanitize_width_content($value): int
{
    return stackless_theme_clamp_width('content', $value);
}

function stackless_theme_sanitize_width_wide($value): int
{
    return stackless_theme_clamp_width('wide', $value);
}

function stackless_theme_sanitize_font_choice($value): string
{
    return array_key_exists((string) $value, stackless_theme_font_stacks()) ? (string) $value : '';
}

/** A CSS font-family list: names, quotes, commas, spaces and hyphens only. */
function stackless_theme_sanitize_font_stack($value): string
{
    $value = trim(preg_replace('/[^A-Za-z0-9 ,"\'_-]/', '', (string) $value));
    return substr($value, 0, 300);
}

function stackless_theme_sanitize_logo_width($value): int
{
    $value = absint($value);
    return $value === 0 ? 0 : max(40, min(400, $value));
}

/** Footer line: a little inline markup and the {year} and {site} tokens. */
function stackless_theme_sanitize_footer_text($value): string
{
    return wp_kses(trim((string) $value), [
        'a' => ['href' => true, 'rel' => true, 'target' => true],
        'strong' => [],
        'em' => [],
        'br' => [],
        'span' => ['class' => true],
    ]);
}

/** Code fields keep their markup only for users who may post unfiltered HTML. */
function stackless_theme_sanitize_code($value, $setting = null): string
{
    if (current_user_can('unfiltered_html')) {
        return (string) $value;
    }
    $id = $setting instanceof WP_Customize_Setting ? $setting->id : '';
    return $id !== '' ? (string) get_theme_mod($id, '') : '';
}

/** A theme mod hex color, or '' when unset or invalid. */
function stackless_theme_design_color(string $key): string
{
    return (string) sanitize_hex_color((string) get_theme_mod('stackless_color_' . $key, ''));
}

/** Mixes two hex colors; $weight is the share of $a (0-1). */
function stackless_theme_mix_hex(string $a, string $b, float $weight): string
{
    $rgb = static function (string $hex): array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    };
    [$r1, $g1, $b1] = $rgb($a);
    [$r2, $g2, $b2] = $rgb($b);
    $mix = static function (int $x, int $y) use ($weight): int {
        return (int) round($x * $weight + $y * (1 - $weight));
    };
    return sprintf('#%02x%02x%02x', $mix($r1, $r2), $mix($g1, $g2), $mix($b1, $b2));
}

/** The font stack chosen for 'body' or 'heading', or ''. */
function stackless_theme_design_font(string $where): string
{
    $choice = stackless_theme_sanitize_font_choice(get_theme_mod('stackless_font_' . $where, ''));
    if ($choice === 'custom') {
        return stackless_theme_sanitize_font_stack(get_theme_mod('stackless_font_' . $where . '_custom', ''));
    }
    return $choice === '' ? '' : stackless_theme_font_stacks()[$choice][1];
}

/** Custom properties that differ from base.css, as [property => value]. */
function stackless_theme_design_vars(): array
{
    $vars = [];
    foreach (stackless_theme_design_colors() as $key => [, $property, $default]) {
        $color = stackless_theme_design_color($key);
        if ($color !== '' && strtolower($color) !== $default) {
            $vars[$property] = $color;
        }
    }
    if (isset($vars['--st-primary'])) {
        $primary = $vars['--st-primary'];
        $vars['--st-primary-hover'] = stackless_theme_mix_hex($primary, '#000000', .85);
        $vars['--st-primary-light'] = stackless_theme_mix_hex($primary, '#ffffff', .1);
        $vars['--st-primary-text'] = stackless_theme_mix_hex($primary, '#000000', .65);
        [$r, $g, $b] = sscanf($primary, '#%02x%02x%02x');
        $vars['--st-ring'] = sprintf('0 0 0 3px rgba(%d, %d, %d, .25)', $r, $g, $b);
    }
    if (isset($vars['--st-surface'])) {
        $vars['--st-surface-muted'] = stackless_theme_mix_hex($vars['--st-surface'], '#000000', .965);
    }
    foreach (stackless_theme_design_widths() as $key => [, , $default]) {
        $width = absint(get_theme_mod('stackless_width_' . $key, 0));
        if ($width > 0 && $width !== $default) {
            $vars['--st-' . $key] = $width . 'px';
        }
    }
    $body = stackless_theme_design_font('body');
    if ($body !== '') {
        $vars['--st-font'] = $body;
    }
    $heading = stackless_theme_design_font('heading');
    if ($heading !== '') {
        $vars['--st-font-heading'] = $heading;
    }
    return (array) apply_filters('stackless_theme_design_vars', $vars);
}

/** The design overrides as CSS, or '' when nothing is customized. */
function stackless_theme_design_css(): string
{
    $css = '';
    $vars = stackless_theme_design_vars();
    if ($vars) {
        $css .= ':root{';
        foreach ($vars as $property => $value) {
            $css .= $property . ':' . $value . ';';
        }
        $css .= '}';
    }
    $logo = stackless_theme_sanitize_logo_width(get_theme_mod('stackless_logo_width', 0));
    if ($logo > 0) {
        $css .= '.custom-logo{width:min(' . $logo . 'px,60vw);max-height:none;height:auto}';
    }
    // Strip anything that could close the style element; values are sanitized above.
    return str_replace(['<', '>'], '', $css);
}

add_action('wp_enqueue_scripts', 'stackless_theme_design_inline_css', 21);
function stackless_theme_design_inline_css(): void
{
    $css = stackless_theme_design_css();
    if ($css !== '') {
        wp_add_inline_style('stackless-theme-base', $css);
    }
}

/** The editor gets the same overrides, so blocks preview in the site's colors, fonts and widths. */
add_filter('block_editor_settings_all', 'stackless_theme_design_editor_css');
function stackless_theme_design_editor_css(array $settings): array
{
    $css = stackless_theme_design_css();
    if ($css !== '') {
        $settings['styles'][] = ['css' => $css];
    }
    return $settings;
}

/** Keeps the theme.json palette and layout widths in step with the design settings. */
add_filter('wp_theme_json_data_theme', 'stackless_theme_design_theme_json');
function stackless_theme_design_theme_json($theme_json)
{
    $vars = stackless_theme_design_vars();
    $map = ['--st-primary' => 'primary', '--st-primary-light' => 'primary-light', '--st-heading' => 'dark', '--st-text' => 'body', '--st-surface-muted' => 'surface'];
    $data = $theme_json->get_data();
    $palette = $data['settings']['color']['palette']['theme'] ?? $data['settings']['color']['palette'] ?? [];
    $changed = false;
    foreach ($palette as $i => $entry) {
        foreach ($map as $property => $slug) {
            if (($entry['slug'] ?? '') === $slug && isset($vars[$property])) {
                $palette[$i]['color'] = $vars[$property];
                $changed = true;
            }
        }
    }
    $update = ['version' => 3, 'settings' => []];
    if ($changed) {
        $update['settings']['color']['palette'] = $palette;
    }
    if (isset($vars['--st-content'])) {
        $update['settings']['layout']['contentSize'] = $vars['--st-content'];
    }
    if (isset($vars['--st-wide'])) {
        $update['settings']['layout']['wideSize'] = $vars['--st-wide'];
    }
    return $update['settings'] ? $theme_json->update_with($update) : $theme_json;
}

/** The footer line: the saved text with {year} and {site}, else "© year Site". */
function stackless_theme_footer_text(): string
{
    $text = stackless_theme_sanitize_footer_text(get_theme_mod('stackless_footer_text', ''));
    if ($text === '') {
        return sprintf('&copy; %1$s <a href="%2$s">%3$s</a>', esc_html(wp_date('Y')), esc_url(home_url('/')), esc_html(get_bloginfo('name')));
    }
    return strtr($text, ['{year}' => esc_html(wp_date('Y')), '{site}' => esc_html(get_bloginfo('name'))]);
}

add_action('wp_head', 'stackless_theme_print_header_code', 99);
function stackless_theme_print_header_code(): void
{
    if (get_theme_mod('stackless_view_transitions', false)) {
        echo "<style id=\"stackless-view-transitions\">@media (prefers-reduced-motion: no-preference){@view-transition{navigation:auto}}</style>\n";
    }
    $code = (string) get_theme_mod('stackless_header_code', '');
    if ($code !== '') {
        echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- saved only by users with unfiltered_html.
    }
}

add_action('wp_footer', 'stackless_theme_print_footer_code', 99);
function stackless_theme_print_footer_code(): void
{
    $code = (string) get_theme_mod('stackless_footer_code', '');
    if ($code !== '') {
        echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- saved only by users with unfiltered_html.
    }
}

add_action('customize_register', 'stackless_theme_design_customize_register', 15);
function stackless_theme_design_customize_register(WP_Customize_Manager $wp_customize): void
{
    $wp_customize->add_section('stackless_theme_design', [
        'title' => __('Stackless: Design', 'stackless-theme'),
        'description' => __('Leave a field empty to keep the theme default.', 'stackless-theme'),
        'priority' => 29,
    ]);

    foreach (stackless_theme_design_colors() as $key => [$label, , $default]) {
        $id = 'stackless_color_' . $key;
        $wp_customize->add_setting($id, ['default' => '', 'sanitize_callback' => 'sanitize_hex_color']);
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $id, [
            'label' => $label,
            /* translators: %s: default hex color */
            'description' => sprintf(__('Default %s', 'stackless-theme'), $default),
            'section' => 'stackless_theme_design',
        ]));
    }

    $labels = [
        'content' => [__('Reading width (px)', 'stackless-theme'), __('Text column of posts and pages. Default 720.', 'stackless-theme')],
        'wide' => [__('Wide width (px)', 'stackless-theme'), __('Header, footer, grids and wide blocks. Default 1200.', 'stackless-theme')],
    ];
    foreach (stackless_theme_design_widths() as $key => [$min, $max]) {
        $id = 'stackless_width_' . $key;
        $wp_customize->add_setting($id, ['default' => 0, 'sanitize_callback' => 'stackless_theme_sanitize_width_' . $key]);
        $wp_customize->add_control($id, [
            'label' => $labels[$key][0],
            'description' => $labels[$key][1],
            'section' => 'stackless_theme_design',
            'type' => 'number',
            'input_attrs' => ['min' => 0, 'max' => $max, 'step' => 10, 'placeholder' => (string) $min . '-' . $max],
        ]);
    }

    $choices = array_map(static function (array $font): string {
        return $font[0];
    }, stackless_theme_font_stacks());
    foreach (['body' => __('Body font', 'stackless-theme'), 'heading' => __('Heading font', 'stackless-theme')] as $where => $label) {
        $id = 'stackless_font_' . $where;
        $wp_customize->add_setting($id, ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_font_choice']);
        $wp_customize->add_control($id, [
            'label' => $label,
            'section' => 'stackless_theme_design',
            'type' => 'select',
            'choices' => $choices,
        ]);
        $wp_customize->add_setting($id . '_custom', ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_font_stack']);
        $wp_customize->add_control($id . '_custom', [
            /* translators: %s: Body font or Heading font */
            'label' => sprintf(__('%s: custom stack', 'stackless-theme'), $label),
            'description' => __('Used when "Custom stack" is picked, e.g. Inter, system-ui, sans-serif. Load web fonts with a font plugin first.', 'stackless-theme'),
            'section' => 'stackless_theme_design',
            'type' => 'text',
            'active_callback' => static function () use ($id): bool {
                return get_theme_mod($id, '') === 'custom';
            },
        ]);
    }

    $wp_customize->add_setting('stackless_logo_width', ['default' => 0, 'sanitize_callback' => 'stackless_theme_sanitize_logo_width']);
    $wp_customize->add_control('stackless_logo_width', [
        'label' => __('Logo width (px)', 'stackless-theme'),
        'description' => __('Empty fits the logo to the header height.', 'stackless-theme'),
        'section' => 'title_tagline',
        'type' => 'number',
        'priority' => 9,
        'input_attrs' => ['min' => 0, 'max' => 400, 'step' => 1],
    ]);

    $wp_customize->add_setting('stackless_view_transitions', ['default' => false, 'sanitize_callback' => 'rest_sanitize_boolean']);
    $wp_customize->add_control('stackless_view_transitions', [
        'label' => __('Fade between pages', 'stackless-theme'),
        'description' => __('Cross-document view transitions in browsers that support them. Off for visitors who prefer reduced motion.', 'stackless-theme'),
        'section' => 'stackless_theme_design',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_section('stackless_theme_footer', [
        'title' => __('Stackless: Footer and code', 'stackless-theme'),
        'priority' => 32,
    ]);
    $wp_customize->add_setting('stackless_footer_text', ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_footer_text']);
    $wp_customize->add_control('stackless_footer_text', [
        'label' => __('Footer text', 'stackless-theme'),
        'description' => __('Replaces "© year Site name". {year} and {site} are filled in; links, bold and italics are allowed.', 'stackless-theme'),
        'section' => 'stackless_theme_footer',
        'type' => 'textarea',
    ]);
    foreach (['header' => __('Code in <head>', 'stackless-theme'), 'footer' => __('Code before </body>', 'stackless-theme')] as $where => $label) {
        $id = 'stackless_' . $where . '_code';
        $wp_customize->add_setting($id, ['default' => '', 'capability' => 'unfiltered_html', 'sanitize_callback' => 'stackless_theme_sanitize_code']);
        $wp_customize->add_control($id, [
            'label' => $label,
            'description' => __('Analytics, verification tags and similar snippets. Printed on every front-end page.', 'stackless-theme'),
            'section' => 'stackless_theme_footer',
            'type' => 'textarea',
            'input_attrs' => ['class' => 'code', 'spellcheck' => 'false'],
        ]);
    }
}
