<?php
/**
 * Tabler Icons (MIT) bundled as SVG files in assets/icons/.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Returns an inline SVG icon. Decorative by default; pass a label to expose it to screen readers.
 */
function stackless_theme_icon(string $name, int $size = 20, string $label = ''): string
{
    static $cache = [];
    $name = sanitize_key($name);
    if (!isset($cache[$name])) {
        $file = STACKLESS_THEME_DIR . '/assets/icons/' . $name . '.svg';
        $cache[$name] = is_readable($file) ? (string) file_get_contents($file) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
    }
    if ($cache[$name] === '') {
        return '';
    }
    $a11y = $label !== ''
        ? 'role="img" aria-label="' . esc_attr($label) . '"'
        : 'aria-hidden="true" focusable="false"';
    $svg = preg_replace('/width="24" height="24"/', 'width="' . $size . '" height="' . $size . '"', $cache[$name], 1);
    return (string) preg_replace('/<svg /', '<svg class="st-icon st-icon--' . esc_attr($name) . '" ' . $a11y . ' ', (string) $svg, 1);
}

/** Echoes stackless_theme_icon(). Output is a trusted local SVG. */
function stackless_theme_the_icon(string $name, int $size = 20, string $label = ''): void
{
    echo stackless_theme_icon($name, $size, $label); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted local SVG.
}
