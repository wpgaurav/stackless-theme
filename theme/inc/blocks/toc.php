<?php
/**
 * Table of Contents block.
 *
 * The block prints an empty <nav> that carries its settings. Once the whole post has rendered,
 * a the_content filter gives every heading an id, builds the list from those headings and fills
 * the <nav>. Reading the rendered content means the list and the anchors always match, and that
 * headings printed by other blocks are included. Posts without the block are never touched.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** Defaults for the settings a block leaves unset. */
function stackless_theme_blocks_toc_defaults(): array
{
    $defaults = [
        'title' => __('Table of Contents', 'stackless-theme'),
        'levels' => 'h2,h3',
        'numbering' => 'none',
        'hierarchical' => true,
        'collapsible' => false,
        'collapsed' => false,
        'min' => 3,
    ];
    return (array) apply_filters('stackless_theme_blocks_toc_defaults', $defaults);
}

/** Heading tags to list, validated and in document order. */
function stackless_theme_blocks_toc_levels($raw): array
{
    $all = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    $levels = array_intersect($all, array_map('strtolower', array_map('trim', explode(',', (string) $raw))));
    return $levels ? array_values($levels) : ['h2', 'h3'];
}

function stackless_theme_blocks_render_table_of_contents(array $attributes, string $content = ''): string
{
    $defaults = stackless_theme_blocks_toc_defaults();
    $args = [
        'title' => !empty($attributes['title']) ? (string) $attributes['title'] : (string) $defaults['title'],
        'levels' => stackless_theme_blocks_toc_levels(!empty($attributes['levels']) ? $attributes['levels'] : $defaults['levels']),
        'numbering' => !empty($attributes['numbering']) ? sanitize_key($attributes['numbering']) : (string) $defaults['numbering'],
        'exclude' => !empty($attributes['exclude']) ? array_values(array_filter(array_map('trim', explode(',', (string) $attributes['exclude'])))) : [],
        'min' => !empty($attributes['min']) ? (int) $attributes['min'] : (int) $defaults['min'],
    ];
    foreach (['hierarchical', 'collapsible', 'collapsed'] as $key) {
        $args[$key] = isset($attributes[$key]) ? (bool) $attributes[$key] : (bool) $defaults[$key];
    }

    $classes = ['stb-toc'];
    if ($args['collapsible']) {
        $classes[] = 'stb-toc-collapsible';
    }
    if ($args['collapsible'] && $args['collapsed']) {
        $classes[] = 'is-collapsed';
    }
    if ($args['numbering'] !== 'none') {
        $classes[] = 'stb-toc-numbered';
    }

    stackless_theme_blocks_toc_pending(true);

    return '<nav class="' . esc_attr(implode(' ', $classes)) . '" data-stb-toc data-numbering="' . esc_attr($args['numbering']) . '" data-st-toc="' . esc_attr(base64_encode((string) wp_json_encode($args))) . '"></nav>';
}

/** Whether a table of contents is waiting to be filled in the content being rendered. */
function stackless_theme_blocks_toc_pending(?bool $set = null): bool
{
    static $pending = false;
    if ($set !== null) {
        $pending = $set;
    }
    return $pending;
}

/** After blocks (9), wpautop (10) and shortcodes (11) have run. */
add_filter('the_content', 'stackless_theme_blocks_toc_fill', 12);
function stackless_theme_blocks_toc_fill($content)
{
    if (!is_string($content) || !stackless_theme_blocks_toc_pending() || strpos($content, 'data-st-toc="') === false) {
        return $content;
    }
    stackless_theme_blocks_toc_pending(false);

    // Give every heading an id and collect the list.
    $headings = [];
    $used = [];
    $content = (string) preg_replace_callback('/<(h[1-6])\b([^>]*)>(.*?)<\/\1>/is', static function (array $match) use (&$headings, &$used): string {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($match[3]), ENT_QUOTES, 'UTF-8')));
        if ($text === '') {
            return $match[0];
        }
        $attrs = $match[2];
        if (preg_match('/\bid=(["\'])(.*?)\1/i', $attrs, $found) && $found[2] !== '') {
            $id = $found[2];
            $used[$id] = true;
        } else {
            $slug = sanitize_title($text);
            $base = $id = $slug !== '' ? $slug : 'section';
            for ($n = 2; isset($used[$id]); $n++) {
                $id = $base . '-' . $n;
            }
            $used[$id] = true;
            $attrs .= ' id="' . esc_attr($id) . '"';
        }
        $headings[] = ['tag' => strtolower($match[1]), 'level' => (int) $match[1][1], 'text' => $text, 'id' => $id];
        return '<' . $match[1] . $attrs . '>' . $match[3] . '</' . $match[1] . '>';
    }, $content);

    return (string) preg_replace_callback('/<nav\b([^>]*)\sdata-st-toc="([^"]*)"([^>]*)><\/nav>/', static function (array $match) use ($headings): string {
        $args = json_decode((string) base64_decode($match[2], true), true);
        if (!is_array($args)) {
            return '';
        }
        $list = array_values(array_filter($headings, static function (array $heading) use ($args): bool {
            if (!in_array($heading['tag'], (array) $args['levels'], true)) {
                return false;
            }
            foreach ((array) $args['exclude'] as $needle) {
                if ($needle !== '' && stripos($heading['text'], (string) $needle) !== false) {
                    return false;
                }
            }
            return true;
        }));
        if (count($list) < max(1, (int) $args['min'])) {
            return '';
        }
        // Unique when a post holds more than one table of contents.
        static $count = 0;
        $count++;
        $id = 'stb-toc-' . substr(md5($list[0]['id']), 0, 8) . ($count > 1 ? '-' . $count : '');
        $inner = (string) apply_filters('stackless_theme_blocks_toc_markup', stackless_theme_blocks_toc_inner($list, $args, $id), $list, $args);
        return '<nav' . $match[1] . $match[3] . ' aria-labelledby="' . esc_attr($id) . '-title">' . $inner . '</nav>';
    }, $content);
}

/** The title row and the list. */
function stackless_theme_blocks_toc_inner(array $headings, array $args, string $id): string
{
    $out = '<div class="stb-toc-head">';
    // A paragraph, not a heading, so the title stays out of the document outline and the list.
    $out .= '<p class="stb-toc-title" id="' . esc_attr($id) . '-title">' . esc_html((string) $args['title']) . '</p>';
    if (!empty($args['collapsible'])) {
        $out .= '<button type="button" class="stb-toc-toggle" aria-expanded="' . (!empty($args['collapsed']) ? 'false' : 'true') . '" aria-controls="' . esc_attr($id) . '">';
        $out .= '<span class="screen-reader-text">' . esc_html__('Toggle table of contents', 'stackless-theme') . '</span>';
        $out .= '<span class="stb-toc-chevron" aria-hidden="true"></span></button>';
    }
    $out .= '</div><div class="stb-toc-body" id="' . esc_attr($id) . '"' . (!empty($args['collapsible']) && !empty($args['collapsed']) ? ' hidden' : '') . '>';

    $tag = ($args['numbering'] ?? 'none') === 'none' ? 'ul' : 'ol';
    $base = min(array_column($headings, 'level'));
    $item = static function (array $heading, ?int $depth = null): string {
        return '<li class="stb-toc-item"' . ($depth !== null ? ' data-depth="' . $depth . '"' : '') . '><a class="stb-toc-link" href="#' . esc_attr($heading['id']) . '" data-level="' . (int) $heading['level'] . '">' . esc_html($heading['text']) . '</a>';
    };

    if (empty($args['hierarchical'])) {
        $out .= '<' . $tag . ' class="stb-toc-list stb-toc-flat">';
        foreach ($headings as $heading) {
            $out .= $item($heading, $heading['level'] - $base) . '</li>';
        }
        return $out . '</' . $tag . '></div>';
    }

    // Nested: descend one level at a time, however far a heading jumps.
    $out .= '<' . $tag . ' class="stb-toc-list">';
    $depth = 0;
    $open = false;
    foreach ($headings as $heading) {
        $level = min($heading['level'] - $base, $depth + 1);
        if ($level > $depth && $open) {
            $out .= '<' . $tag . ' class="stb-toc-sublist">';
            $depth++;
        } elseif ($level < $depth) {
            $out .= $open ? '</li>' : '';
            while ($level < $depth) {
                $out .= '</' . $tag . '></li>';
                $depth--;
            }
        } elseif ($open) {
            $out .= '</li>';
        }
        $out .= $item($heading);
        $open = true;
    }
    $out .= $open ? '</li>' : '';
    while ($depth > 0) {
        $out .= '</' . $tag . '></li>';
        $depth--;
    }
    return $out . '</' . $tag . '></div>';
}
