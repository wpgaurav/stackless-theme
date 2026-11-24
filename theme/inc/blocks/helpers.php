<?php
/**
 * Shared helpers for Stackless blocks.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Wraps a block's render callback.
 *
 * The block renders as the theme part "block_<name>", so it gets the theme's standard hooks
 * (show, pre, before, _html, after). Inside the part, the empty stb-icon-* elements are filled
 * with their SVG and the block-support classes and styles are merged into the root element,
 * which also gives it the wp-block-stackless-<name> class.
 */
function stackless_theme_blocks_render(string $slug, callable $callback): callable
{
    return static function ($attributes = [], $content = '', $block = null) use ($slug, $callback): string {
        $attributes = (array) $attributes;
        $content = (string) $content;
        $context = ['block' => $slug, 'attributes' => $attributes, 'content' => $content];

        ob_start();
        stackless_theme_part(stackless_theme_blocks_part($slug), static function () use ($slug, $callback, $attributes, $content, $block): void {
            $html = (string) call_user_func($callback, $attributes, $content, $block);
            if ($html === '') {
                return;
            }
            $html = stackless_theme_blocks_inline_icons($html);
            echo stackless_theme_blocks_merge_wrapper($html, $attributes, $slug); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the block's callback.
        }, $context);
        $html = (string) ob_get_clean();

        if ($html !== '' && !is_admin()) {
            $handle = stackless_theme_blocks_style_handle($slug);
            if ($handle !== '') {
                wp_enqueue_style($handle);
            }
        }
        return $html;
    };
}

/**
 * Merges get_block_wrapper_attributes() into the first element the block printed. The templates
 * build their own markup, so without this the inspector's spacing, color and typography
 * controls would do nothing on the front end. Also sets the anchor as the id.
 */
function stackless_theme_blocks_merge_wrapper(string $html, array $attributes = [], string $slug = ''): string
{
    $trimmed = ltrim($html);
    if ($trimmed === '' || $trimmed[0] !== '<' || !class_exists('WP_HTML_Tag_Processor')) {
        return $html;
    }

    $anchor = isset($attributes['anchor']) ? trim((string) $attributes['anchor']) : '';
    $extra = class_exists('WP_Block_Supports') && !empty(WP_Block_Supports::$block_to_render) ? get_block_wrapper_attributes() : '';
    $class = $slug !== '' ? 'wp-block-stackless-' . $slug : '';
    $style = '';
    if ($extra !== '') {
        $parser = new WP_HTML_Tag_Processor('<div ' . $extra . '></div>');
        if ($parser->next_tag()) {
            $class .= ' ' . (string) $parser->get_attribute('class');
            $style = (string) $parser->get_attribute('style');
        }
    }

    // The root is the first element that renders; a block may print a <style> ahead of it.
    $processor = new WP_HTML_Tag_Processor($html);
    $found = false;
    while ($processor->next_tag()) {
        if (!in_array($processor->get_tag(), ['STYLE', 'SCRIPT', 'LINK', 'META'], true)) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        return $html;
    }

    foreach (preg_split('/\s+/', trim($class)) as $name) {
        // A bare "align" is what the align support prints for an empty align attribute.
        if ($name !== '' && $name !== 'align') {
            $processor->add_class($name);
        }
    }
    if ($style !== '') {
        $existing = (string) $processor->get_attribute('style');
        $processor->set_attribute('style', $existing !== '' ? rtrim(trim($existing), ';') . ';' . $style : $style);
    }
    if ($anchor !== '' && !$processor->get_attribute('id')) {
        $processor->set_attribute('id', $anchor);
    }
    return $processor->get_updated_html();
}

/** An attribute's value, or '' when it is unset or empty. */
function stackless_theme_blocks_field(array $attributes, string $field)
{
    return !empty($attributes[$field]) ? $attributes[$field] : '';
}

/** One line of author text: inline formatting and links only. */
function stackless_theme_blocks_text($text): string
{
    static $allowed = null;
    if ($allowed === null) {
        $allowed = [
            'a' => ['href' => true, 'title' => true, 'target' => true, 'rel' => true, 'class' => true, 'id' => true],
            'abbr' => ['title' => true],
            'b' => [], 'br' => [], 'cite' => [], 'code' => [], 'del' => [], 'em' => [], 'i' => ['class' => true],
            'ins' => [], 'kbd' => [], 'mark' => ['class' => true, 'style' => true], 's' => [], 'small' => [],
            'span' => ['class' => true, 'style' => true], 'strong' => [], 'sub' => [], 'sup' => [], 'u' => [],
        ];
    }
    return wp_kses((string) $text, $allowed);
}

/** Class names from an attribute, each sanitized, as a list. */
function stackless_theme_blocks_classes($value): array
{
    $classes = [];
    foreach (preg_split('/\s+/', trim((string) $value)) as $name) {
        $name = sanitize_html_class($name);
        if ($name !== '') {
            $classes[] = $name;
        }
    }
    return $classes;
}

/** Removes microdata attributes and <meta itemprop> tags, for a block with its schema off. */
function stackless_theme_blocks_strip_microdata(string $html): string
{
    $html = (string) preg_replace('/<meta\b[^>]*\bitemprop=[^>]*>/i', '', $html);
    if (!class_exists('WP_HTML_Tag_Processor')) {
        return $html;
    }
    $tags = new WP_HTML_Tag_Processor($html);
    while ($tags->next_tag()) {
        foreach (['itemprop', 'itemscope', 'itemtype', 'itemid'] as $attribute) {
            $tags->remove_attribute($attribute);
        }
    }
    return $tags->get_updated_html();
}

/** An <img> for a URL: the media library's responsive image when the URL is an attachment. */
function stackless_theme_blocks_image(string $url, string $size = 'large', array $attributes = []): string
{
    static $ids = [];
    if ($url === '') {
        return '';
    }
    if (!array_key_exists($url, $ids)) {
        $ids[$url] = (int) attachment_url_to_postid($url);
    }
    if ($ids[$url]) {
        return wp_get_attachment_image($ids[$url], $size, false, $attributes);
    }
    $html = '<img src="' . esc_url($url) . '"';
    foreach (array_merge(['alt' => '', 'decoding' => 'async'], $attributes) as $key => $value) {
        if (in_array($key, ['alt', 'class', 'width', 'height', 'loading', 'decoding'], true) && $value !== false) {
            $html .= ' ' . $key . '="' . esc_attr((string) $value) . '"';
        }
    }
    return $html . ' />';
}

/**
 * Palette slugs and colors for the blocks' color-class pickers: the theme's editor palette.
 * The class a picker stores is has-<slug>-color or has-<slug>-background-color. WordPress styles
 * those for the theme's own slugs; bridge.css styles the blocks' extra slugs.
 *
 * @return array<string, string>
 */
function stackless_theme_blocks_palette(): array
{
    $palette = [];
    $settings = function_exists('wp_get_global_settings') ? (array) wp_get_global_settings(['color', 'palette']) : [];
    foreach ((array) ($settings['theme'] ?? []) as $entry) {
        if (!empty($entry['slug']) && !empty($entry['color'])) {
            $palette[(string) $entry['slug']] = (string) $entry['color'];
        }
    }
    return (array) apply_filters('stackless_theme_blocks_palette', $palette);
}
