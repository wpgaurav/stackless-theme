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
    return (array) apply_filters('stackless_theme_design_colors', [
        'primary' => [__('Primary (links and buttons)', 'stackless-theme'), '--st-primary', '#066fd1'],
        'text' => [__('Text', 'stackless-theme'), '--st-text', '#262626'],
        'heading' => [__('Headings', 'stackless-theme'), '--st-heading', '#171717'],
        'bg' => [__('Page background', 'stackless-theme'), '--st-bg', '#fafafa'],
        'surface' => [__('Cards and header', 'stackless-theme'), '--st-surface', '#ffffff'],
    ]);
}

/**
 * Fonts that ship with the theme in assets/fonts, each under the SIL Open Font License 1.1 (see
 * the OFL.txt beside it), subset to Latin and Latin Extended. Nothing loads until a font is picked
 * in Typography; then only that family's @font-face rules print.
 *
 * slug => [
 *   name     CSS family name,
 *   fallback generic stack after it,
 *   use      'both' (body or headings) or 'heading' (display faces),
 *   faces    [file, weight or weight range, style],
 *   heading  optional heading weight and letter spacing that suit the face,
 * ]
 */
function stackless_theme_bundled_fonts(): array
{
    $sans = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
    $serif = 'Charter, "Bitstream Charter", Cambria, Georgia, serif';
    $display = 'Impact, "Arial Narrow Bold", "Helvetica Neue", sans-serif';
    return (array) apply_filters('stackless_theme_bundled_fonts', [
        'inter' => ['name' => 'Inter', 'fallback' => $sans, 'use' => 'both', 'faces' => [['Inter.woff2', '100 900', 'normal'], ['Inter-Italic.woff2', '100 900', 'italic']]],
        'geist' => ['name' => 'Geist', 'fallback' => $sans, 'use' => 'both', 'faces' => [['Geist-Regular.woff2', '400', 'normal'], ['Geist-Italic.woff2', '400', 'italic'], ['Geist-Medium.woff2', '500', 'normal'], ['Geist-SemiBold.woff2', '600', 'normal'], ['Geist-Bold.woff2', '700', 'normal']]],
        'intern' => ['name' => 'Intern', 'fallback' => $sans, 'use' => 'both', 'faces' => [['Intern-Regular.woff2', '400', 'normal'], ['Intern-Medium.woff2', '500', 'normal'], ['Intern-SemiBold.woff2', '600', 'normal'], ['Intern-Bold.woff2', '700', 'normal']]],
        'allo' => ['name' => 'Allo', 'fallback' => $sans, 'use' => 'both', 'faces' => [['Allo.woff2', '100 900', 'normal']]],
        'newsreader' => ['name' => 'Newsreader', 'fallback' => $serif, 'use' => 'both', 'faces' => [['Newsreader.woff2', '200 800', 'normal'], ['Newsreader-Italic.woff2', '200 800', 'italic']], 'heading' => ['weight' => 500, 'tracking' => '-.015em']],
        'geist-serif' => ['name' => 'Geist Serif', 'fallback' => $serif, 'use' => 'both', 'faces' => [['GeistSerif-Regular.woff2', '400', 'normal']], 'heading' => ['weight' => 400, 'tracking' => '-.02em']],
        'anton' => ['name' => 'Anton', 'fallback' => $display, 'use' => 'heading', 'faces' => [['Anton-Regular.woff2', '400', 'normal']], 'heading' => ['weight' => 400, 'tracking' => '.005em']],
        'bebas-neue' => ['name' => 'Bebas Neue', 'fallback' => $display, 'use' => 'heading', 'faces' => [['BebasNeue-Regular.woff2', '400', 'normal']], 'heading' => ['weight' => 400, 'tracking' => '.02em']],
        'oswald' => ['name' => 'Oswald', 'fallback' => $display, 'use' => 'heading', 'faces' => [['Oswald.woff2', '200 700', 'normal']], 'heading' => ['weight' => 600, 'tracking' => '0']],
        'archivo-black' => ['name' => 'Archivo Black', 'fallback' => $display, 'use' => 'heading', 'faces' => [['ArchivoBlack-Regular.woff2', '400', 'normal']], 'heading' => ['weight' => 400, 'tracking' => '-.01em']],
    ]);
}

/** Font stacks offered for body and headings. '' keeps the theme's system sans. */
function stackless_theme_font_stacks(): array
{
    $bundled = [];
    foreach (stackless_theme_bundled_fonts() as $slug => $font) {
        $bundled['font-' . $slug] = [
            /* translators: %s: font name, e.g. Inter */
            sprintf($font['use'] === 'heading' ? __('%s (included, headings)', 'stackless-theme') : __('%s (included)', 'stackless-theme'), $font['name']),
            '"' . $font['name'] . '", ' . $font['fallback'],
        ];
    }
    return (array) apply_filters('stackless_theme_font_stacks', [
        '' => [__('System sans (default)', 'stackless-theme'), ''],
        'serif' => [__('System serif', 'stackless-theme'), 'Charter, "Bitstream Charter", "Sitka Text", Cambria, Georgia, serif'],
        'humanist' => [__('Humanist sans', 'stackless-theme'), 'Seravek, "Gill Sans Nova", Ubuntu, Calibri, "DejaVu Sans", source-sans-pro, sans-serif'],
        'geometric' => [__('Geometric sans', 'stackless-theme'), 'Avenir, Montserrat, Corbel, "URW Gothic", source-sans-pro, sans-serif'],
        'rounded' => [__('Rounded sans', 'stackless-theme'), 'ui-rounded, "Hiragino Maru Gothic ProN", Quicksand, Comfortaa, Manjari, "Arial Rounded MT", Calibri, source-sans-pro, sans-serif'],
        'mono' => [__('Monospace', 'stackless-theme'), 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace'],
    ] + $bundled + [
        'custom' => [__('Custom stack', 'stackless-theme'), ''],
    ]);
}

/** The bundled font chosen for 'body' or 'heading': its slug, or '' for a system or custom stack. */
function stackless_theme_design_bundled_font(string $where): string
{
    $choice = stackless_theme_sanitize_font_choice(get_theme_mod('stackless_font_' . $where, ''));
    if (strpos($choice, 'font-') !== 0) {
        return '';
    }
    $slug = substr($choice, 5);
    $fonts = stackless_theme_bundled_fonts();
    // A heading-only display face never sets body text.
    return isset($fonts[$slug]) && ($where === 'heading' || $fonts[$slug]['use'] === 'both') ? $slug : '';
}

/** @font-face rules for the bundled fonts in use, and nothing for the rest. */
function stackless_theme_font_face_css(): string
{
    $fonts = stackless_theme_bundled_fonts();
    $css = '';
    foreach (array_unique(array_filter([stackless_theme_design_bundled_font('body'), stackless_theme_design_bundled_font('heading')])) as $slug) {
        foreach ($fonts[$slug]['faces'] as [$file, $weight, $style]) {
            $css .= sprintf(
                '@font-face{font-family:"%1$s";src:url("%2$s") format("woff2");font-weight:%3$s;font-style:%4$s;font-display:swap}',
                $fonts[$slug]['name'],
                esc_url(STACKLESS_THEME_URI . '/assets/fonts/' . $slug . '/' . $file),
                $weight,
                $style
            );
        }
    }
    return $css;
}

/** The face a role reads first, to preload: the upright face that covers the weight it is set in. */
function stackless_theme_font_preload_url(string $slug, int $weight): string
{
    $font = stackless_theme_bundled_fonts()[$slug] ?? null;
    if (!$font) {
        return '';
    }
    foreach ($font['faces'] as [$file, $range, $style]) {
        $bounds = array_map('intval', explode(' ', $range));
        if ($style === 'normal' && $weight >= $bounds[0] && $weight <= ($bounds[1] ?? $bounds[0])) {
            return STACKLESS_THEME_URI . '/assets/fonts/' . $slug . '/' . $file;
        }
    }
    return STACKLESS_THEME_URI . '/assets/fonts/' . $slug . '/' . $font['faces'][0][0];
}

/** Preloads the bundled fonts in use, so text does not swap late. */
add_action('wp_head', 'stackless_theme_font_preload', 2);
function stackless_theme_font_preload(): void
{
    $urls = [];
    $body = stackless_theme_design_bundled_font('body');
    if ($body !== '') {
        $urls[] = stackless_theme_font_preload_url($body, 400);
    }
    $heading = stackless_theme_design_bundled_font('heading');
    if ($heading !== '') {
        $urls[] = stackless_theme_font_preload_url($heading, (int) (stackless_theme_bundled_fonts()[$heading]['heading']['weight'] ?? 600));
    }
    foreach (array_unique(array_filter($urls)) as $url) {
        printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url($url));
    }
}

/** Heading letter case: 'none' or 'uppercase'. */
function stackless_theme_sanitize_heading_case($value): string
{
    return $value === 'uppercase' ? 'uppercase' : 'none';
}

/** Width limits in px: setting => [min, max, default]. */
function stackless_theme_design_widths(): array
{
    return (array) apply_filters('stackless_theme_design_widths', [
        'content' => [560, 960, 720],
        'wide' => [960, 1600, 1200],
    ]);
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
    if (strpos($choice, 'font-') === 0 && stackless_theme_design_bundled_font($where) === '') {
        return '';
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
    $heading_font = stackless_theme_design_bundled_font('heading');
    $tuning = $heading_font !== '' ? (stackless_theme_bundled_fonts()[$heading_font]['heading'] ?? []) : [];
    if (isset($tuning['weight'])) {
        $vars['--st-heading-weight'] = (string) (int) $tuning['weight'];
    }
    if (isset($tuning['tracking'])) {
        $vars['--st-heading-tracking'] = preg_replace('/[^0-9.em-]/', '', (string) $tuning['tracking']);
    }
    if (stackless_theme_sanitize_heading_case(get_theme_mod('stackless_heading_case', 'none')) === 'uppercase') {
        $vars['--st-heading-transform'] = 'uppercase';
    }
    return (array) apply_filters('stackless_theme_design_vars', $vars);
}

/**
 * Custom properties the Customizer preview redraws live, without reloading, as the color and font
 * settings change (assets/js/customize-preview.js). In the preview they print on their own.
 */
function stackless_theme_design_live_properties(): array
{
    $properties = ['--st-primary-hover', '--st-primary-light', '--st-primary-text', '--st-ring', '--st-surface-muted', '--st-font', '--st-font-heading', '--st-heading-weight', '--st-heading-tracking', '--st-heading-transform'];
    foreach (stackless_theme_design_colors() as [, $property]) {
        $properties[] = $property;
    }
    return $properties;
}

/** A :root rule for [property => value], or '' when there is nothing to set. */
function stackless_theme_design_root_css(array $vars): string
{
    if (!$vars) {
        return '';
    }
    $css = ':root{';
    foreach ($vars as $property => $value) {
        $css .= $property . ':' . $value . ';';
    }
    return $css . '}';
}

/** The design overrides as CSS, or '' when nothing is customized. */
function stackless_theme_design_css(): string
{
    $vars = stackless_theme_design_vars();
    if (is_customize_preview()) {
        // The font faces and live properties print in stackless-theme-live-design instead.
        $css = stackless_theme_design_root_css(array_diff_key($vars, array_flip(stackless_theme_design_live_properties())));
    } else {
        $css = stackless_theme_font_face_css() . stackless_theme_design_root_css($vars);
    }
    $logo = stackless_theme_sanitize_logo_width(get_theme_mod('stackless_logo_width', 0));
    if ($logo > 0) {
        $css .= '.custom-logo{width:min(' . $logo . 'px,60vw);max-height:none;height:auto}';
    }
    $css = (string) apply_filters('stackless_theme_design_css', $css);
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
    if (is_customize_preview()) {
        // Redrawn by customize-preview.js; holds the live properties and the font faces in use.
        wp_register_style('stackless-theme-live-design', false, ['stackless-theme-base'], STACKLESS_THEME_VERSION);
        wp_enqueue_style('stackless-theme-live-design');
        wp_add_inline_style('stackless-theme-live-design', stackless_theme_design_live_css());
    }
}

/**
 * The live part of the design CSS in the Customizer preview: the font faces in use, the live
 * properties, and the block palette pointed at them so palette colors follow along.
 */
function stackless_theme_design_live_css(): string
{
    $vars = array_intersect_key(stackless_theme_design_vars(), array_flip(stackless_theme_design_live_properties()));
    $palette = [];
    foreach (stackless_theme_design_palette_map() as $property => $slug) {
        $palette['--wp--preset--color--' . $slug] = 'var(' . $property . ')';
    }
    return str_replace(['<', '>'], '', stackless_theme_font_face_css() . stackless_theme_design_root_css($vars) . stackless_theme_design_root_css($palette));
}

/** Design properties that set a theme.json palette color: property => palette slug. */
function stackless_theme_design_palette_map(): array
{
    return ['--st-primary' => 'primary', '--st-primary-light' => 'primary-light', '--st-heading' => 'dark', '--st-text' => 'body', '--st-surface-muted' => 'surface'];
}

/** Redraws the colors and fonts in the preview as they change, so those settings skip the reload. */
add_action('customize_preview_init', 'stackless_theme_design_preview_script');
function stackless_theme_design_preview_script(): void
{
    $js = stackless_theme_asset('assets/js/customize-preview.js');
    wp_enqueue_script('stackless-theme-customize-preview', STACKLESS_THEME_URI . '/' . $js, ['customize-preview'], stackless_theme_asset_version($js), ['in_footer' => true]);

    $fonts = [];
    foreach (stackless_theme_bundled_fonts() as $slug => $font) {
        $fonts[$slug] = [
            'name' => $font['name'],
            'use' => $font['use'],
            'faces' => array_map(static function (array $face) use ($slug): array {
                return [esc_url_raw(STACKLESS_THEME_URI . '/assets/fonts/' . $slug . '/' . $face[0]), $face[1], $face[2]];
            }, $font['faces']),
            'heading' => $font['heading'] ?? (object) [],
        ];
    }
    $colors = [];
    foreach (stackless_theme_design_colors() as $key => [, $property, $default]) {
        $colors[$key] = [$property, strtolower($default)];
    }
    $data = [
        'colors' => $colors,
        'stacks' => array_map(static function (array $stack): string {
            return $stack[1];
        }, stackless_theme_font_stacks()),
        'fonts' => $fonts,
        'palette' => stackless_theme_design_palette_map(),
    ];
    wp_add_inline_script('stackless-theme-customize-preview', 'window.stacklessThemePreview = ' . wp_json_encode($data) . ';', 'before');
}

/** Shows the custom stack fields as soon as "Custom stack" is picked, without waiting on the preview. */
add_action('customize_controls_enqueue_scripts', 'stackless_theme_design_controls_script');
function stackless_theme_design_controls_script(): void
{
    $js = stackless_theme_asset('assets/js/customize-controls.js');
    wp_enqueue_script('stackless-theme-customize-controls', STACKLESS_THEME_URI . '/' . $js, ['customize-controls'], stackless_theme_asset_version($js), ['in_footer' => true]);
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
    $map = stackless_theme_design_palette_map();
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
    return (string) apply_filters('stackless_theme_footer_text', stackless_theme_footer_text_default());
}

/** The footer line before stackless_theme_footer_text: the saved text, or the copyright and credit. */
function stackless_theme_footer_text_default(): string
{
    $text = stackless_theme_sanitize_footer_text(get_theme_mod('stackless_footer_text', ''));
    if ($text === '') {
        $line = sprintf('&copy; %1$s <a href="%2$s">%3$s</a>', esc_html(wp_date('Y')), esc_url(home_url('/')), esc_html(get_bloginfo('name')));
        // The theme credit rides along with the default line only; custom footer text replaces both.
        if (stackless_theme_option('stackless_footer_credit')) {
            $theme = wp_get_theme(get_template());
            $line .= ' <span class="site-info__sep" aria-hidden="true">&middot;</span> <span class="site-info__credit">' . sprintf(
                /* translators: %s: theme name linked to its home page */
                esc_html(stackless_theme_text('theme_credit', __('Built with %s', 'stackless-theme'))),
                '<a href="' . esc_url((string) $theme->get('ThemeURI')) . '">' . esc_html((string) $theme->get('Name')) . '</a>'
            ) . '</span>';
        }
        return $line;
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
    foreach (stackless_theme_design_colors() as $key => [$label, , $default]) {
        $id = 'stackless_color_' . $key;
        $wp_customize->add_setting($id, ['default' => '', 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'postMessage']);
        $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, $id, [
            'label' => $label,
            /* translators: %s: default hex color */
            'description' => sprintf(__('Default %s', 'stackless-theme'), $default),
            'section' => 'stackless_theme_colors',
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
            'section' => 'stackless_theme_layout',
            'type' => 'number',
            'input_attrs' => ['min' => 0, 'max' => $max, 'step' => 10, 'placeholder' => (string) $min . '-' . $max],
        ]);
    }

    $choices = array_map(static function (array $font): string {
        return $font[0];
    }, stackless_theme_font_stacks());
    $fonts = stackless_theme_bundled_fonts();
    foreach (['body' => __('Body font', 'stackless-theme'), 'heading' => __('Heading font', 'stackless-theme')] as $where => $label) {
        $id = 'stackless_font_' . $where;
        $wp_customize->add_setting($id, ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_font_choice', 'transport' => 'postMessage']);
        $wp_customize->add_control($id, [
            'label' => $label,
            'description' => $where === 'heading'
                ? __('"Included" fonts ship with the theme and load only when picked. The "headings" ones are bold display faces.', 'stackless-theme')
                : __('"Included" fonts ship with the theme and load only when picked.', 'stackless-theme'),
            'section' => 'stackless_theme_typography',
            'type' => 'select',
            // Display faces are for headings only.
            'choices' => $where === 'heading' ? $choices : array_filter($choices, static function ($key) use ($fonts): bool {
                return strpos((string) $key, 'font-') !== 0 || ($fonts[substr((string) $key, 5)]['use'] ?? '') === 'both';
            }, ARRAY_FILTER_USE_KEY),
        ]);
        $wp_customize->add_setting($id . '_custom', ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_font_stack', 'transport' => 'postMessage']);
        $wp_customize->add_control($id . '_custom', [
            /* translators: %s: Body font or Heading font */
            'label' => sprintf(__('%s: custom stack', 'stackless-theme'), $label),
            'description' => sprintf(
                /* translators: %s: link to the Functionalities plugin on WordPress.org */
                __('Used when "Custom stack" is picked, e.g. Inter, system-ui, sans-serif. Stackless loads no web fonts itself: add them first with the free %s plugin, whose Fonts module adds web fonts to your site.', 'stackless-theme'),
                '<a href="' . esc_url('https://wordpress.org/plugins/functionalities/') . '" target="_blank" rel="noopener">' . esc_html__('Functionalities', 'stackless-theme') . '</a>'
            ),
            'section' => 'stackless_theme_typography',
            'type' => 'text',
            'active_callback' => static function () use ($id): bool {
                return get_theme_mod($id, '') === 'custom';
            },
        ]);
    }

    $wp_customize->add_setting('stackless_heading_case', ['default' => 'none', 'sanitize_callback' => 'stackless_theme_sanitize_heading_case', 'transport' => 'postMessage']);
    $wp_customize->add_control('stackless_heading_case', [
        'label' => __('Heading letter case', 'stackless-theme'),
        'description' => __('Capitals suit the condensed display faces.', 'stackless-theme'),
        'section' => 'stackless_theme_typography',
        'type' => 'radio',
        'choices' => [
            'none' => __('As written', 'stackless-theme'),
            'uppercase' => __('All capitals', 'stackless-theme'),
        ],
    ]);

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
        'description' => __('A short cross-fade when moving between pages, in browsers that support it. Skipped for visitors who prefer reduced motion.', 'stackless-theme'),
        'section' => 'stackless_theme_layout',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('stackless_footer_text', ['default' => '', 'sanitize_callback' => 'stackless_theme_sanitize_footer_text']);
    $wp_customize->add_control('stackless_footer_text', [
        'label' => __('Footer text', 'stackless-theme'),
        'description' => __('Replaces the default "© year Site name · Built with Stackless" line. {year} and {site} are filled in; links, bold and italics are allowed.', 'stackless-theme'),
        'section' => 'stackless_theme_footer',
        'type' => 'textarea',
    ]);
    $wp_customize->add_setting('stackless_footer_credit', ['default' => true, 'sanitize_callback' => 'rest_sanitize_boolean']);
    $wp_customize->add_control('stackless_footer_credit', [
        'label' => __('Theme credit', 'stackless-theme'),
        'description' => __('"Built with Stackless" after the copyright. Only shown with the default footer line.', 'stackless-theme'),
        'section' => 'stackless_theme_footer',
        'type' => 'checkbox',
        'active_callback' => static function (): bool {
            return trim((string) get_theme_mod('stackless_footer_text', '')) === '';
        },
    ]);
    foreach (['header' => __('Code in the page head', 'stackless-theme'), 'footer' => __('Code at the end of the page', 'stackless-theme')] as $where => $label) {
        $id = 'stackless_' . $where . '_code';
        $wp_customize->add_setting($id, ['default' => '', 'capability' => 'unfiltered_html', 'sanitize_callback' => 'stackless_theme_sanitize_code']);
        $wp_customize->add_control($id, [
            'label' => $label,
            'section' => 'stackless_theme_code',
            'type' => 'textarea',
            'input_attrs' => ['class' => 'code', 'spellcheck' => 'false'],
        ]);
    }
}
