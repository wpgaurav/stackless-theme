<?php
/**
 * Slider and Carousel blocks.
 *
 * Both are a row that scrolls sideways with CSS scroll snap, so they work as plain horizontal
 * scrolling without JavaScript. assets/js/blocks/scroller.js, shared by the two, adds the arrows,
 * dots or progress bar, keyboard keys and autoplay with its pause button.
 *
 * - stackless/slider holds stackless/slide blocks: a photo with an overlay and inner blocks.
 * - stackless/carousel holds any blocks; each becomes one item. With a single Query Loop, Product
 *   Collection, product grid or Columns block inside, it scrolls that block's list instead.
 *
 * Like the other Stackless blocks they save only their attributes and inner blocks.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** The Slider's height presets: name => CSS length. "viewport" uses the viewportHeight attribute. */
function stackless_theme_blocks_slider_heights(): array
{
    return (array) apply_filters('stackless_theme_blocks_slider_heights', [
        'small' => '24rem',
        'medium' => '32rem',
        'large' => '40rem',
    ]);
}

/** The Carousel's gap presets: name => CSS length. */
function stackless_theme_blocks_carousel_gaps(): array
{
    return (array) apply_filters('stackless_theme_blocks_carousel_gaps', [
        'none' => '0px',
        'small' => '.75rem',
        'medium' => '1.5rem',
        'large' => '2.5rem',
    ]);
}

/**
 * Class names of the lists a Carousel scrolls when it holds a single block: core Query Loop and
 * Gallery, Columns, WooCommerce product blocks, Easy Digital Downloads, FluentCart and the
 * Stackless Query Loop. A Carousel's listClass attribute adds one more.
 */
function stackless_theme_blocks_carousel_lists(): array
{
    return (array) apply_filters('stackless_theme_blocks_carousel_lists', [
        'wp-block-post-template',
        'wc-block-product-template',
        'wc-block-grid__products',
        'products',
        'edd-blocks__downloads',
        'edd_downloads_list',
        'fct-products-container',
        'loop-cards',
        'loop-teasers',
        'loop-blocks',
        'wp-block-columns',
        'wp-block-gallery',
    ]);
}

/**
 * Tells each slide, and each block inside a Carousel, its place: WordPress renders inner blocks
 * before their parent, so the parent cannot number them afterwards. The note rides on the parsed
 * block and is read by the slide's callback and by stackless_theme_blocks_carousel_item().
 *
 * A Slider or Carousel opens a counter here as it starts rendering, and closes it in
 * stackless_theme_blocks_carousel_item() once it has rendered, so nested ones count apart.
 */
add_filter('render_block_data', 'stackless_theme_blocks_scroller_data', 10, 3);
function stackless_theme_blocks_scroller_data($parsed_block, $source_block = null, $parent_block = null)
{
    if (!is_array($parsed_block) || empty($parsed_block['blockName'])) {
        return $parsed_block;
    }
    $parent = $parent_block instanceof WP_Block ? (string) $parent_block->name : '';
    if ($parent === 'stackless/slider' || $parent === 'stackless/carousel') {
        $total = 0;
        foreach ((array) ($parent_block->parsed_block['innerBlocks'] ?? []) as $inner) {
            if (!empty($inner['blockName']) && ($parent === 'stackless/carousel' || $inner['blockName'] === 'stackless/slide')) {
                ++$total;
            }
        }
        $index = stackless_theme_blocks_scroller_count('next');
        if ($total > 0 && $index > 0) {
            $parsed_block['stackless_scroller'] = ['parent' => $parent, 'index' => min($index, $total), 'total' => $total];
        }
    }
    if (in_array($parsed_block['blockName'], ['stackless/slider', 'stackless/carousel'], true)) {
        stackless_theme_blocks_scroller_count('open');
    }
    return $parsed_block;
}

/** The stack of counters for the Sliders and Carousels being rendered: open, next or close. */
function stackless_theme_blocks_scroller_count(string $op): int
{
    static $stack = [];
    if ($op === 'open') {
        $stack[] = 0;
        return 0;
    }
    if ($op === 'close') {
        array_pop($stack);
        return 0;
    }
    if (!$stack) {
        return 0;
    }
    $top = count($stack) - 1;
    return ++$stack[$top];
}

/** "2 of 5", the label of one slide or item. */
function stackless_theme_blocks_slide_label(int $index, int $total): string
{
    /* translators: 1: slide number, 2: number of slides. */
    return sprintf(__('%1$d of %2$d', 'stackless-theme'), $index, $total);
}

/** A boolean attribute, falling back to its default when unset. */
function stackless_theme_blocks_flag(array $attributes, string $key, bool $default): bool
{
    return isset($attributes[$key]) ? (bool) $attributes[$key] : $default;
}

/**
 * The shared root attributes of a Slider or Carousel: the carousel role description, its label,
 * the script's settings and the interface text the script needs.
 */
function stackless_theme_blocks_scroller_attrs(array $attributes, string $kind, string $label): string
{
    $interval = isset($attributes['interval']) ? (float) $attributes['interval'] : 6.0;
    $interval = max(2.0, min(30.0, $interval));
    $autoplay = stackless_theme_blocks_flag($attributes, 'autoplay', false);
    $loop = stackless_theme_blocks_flag($attributes, 'loop', $kind === 'slider');
    $is_slider = $kind === 'slider';

    $data = [
        'aria-roledescription' => __('carousel', 'stackless-theme'),
        'aria-label' => $label,
        'data-stb-scroller' => $kind,
        'data-loop' => $loop ? 'true' : 'false',
        'data-autoplay' => $autoplay ? (string) $interval : '0',
        /* translators: %d: slide number. */
        'data-label-dot' => $is_slider ? __('Go to slide %d', 'stackless-theme') : __('Go to page %d', 'stackless-theme'),
        /* translators: 1: current slide number, 2: number of slides. */
        'data-label-status' => $is_slider ? __('Slide %1$d of %2$d', 'stackless-theme') : __('Page %1$d of %2$d', 'stackless-theme'),
        'data-label-dots' => $is_slider ? __('Choose a slide', 'stackless-theme') : __('Choose a page', 'stackless-theme'),
        'data-label-pause' => $is_slider ? __('Pause the slideshow', 'stackless-theme') : __('Pause the carousel', 'stackless-theme'),
        'data-label-play' => $is_slider ? __('Play the slideshow', 'stackless-theme') : __('Play the carousel', 'stackless-theme'),
    ];
    $html = '';
    foreach ($data as $name => $value) {
        $html .= ' ' . $name . '="' . esc_attr($value) . '"';
    }
    return $html;
}

/** Arrow buttons, the dots or progress bar, the pause button and the status line. */
function stackless_theme_blocks_scroller_controls(array $attributes, string $kind, string $track_id): array
{
    $is_slider = $kind === 'slider';
    $arrows = stackless_theme_blocks_flag($attributes, 'arrows', true);
    $autoplay = stackless_theme_blocks_flag($attributes, 'autoplay', false);
    if ($is_slider) {
        $pagination = stackless_theme_blocks_flag($attributes, 'dots', true) ? 'dots' : 'none';
    } else {
        $pagination = isset($attributes['pagination']) && in_array($attributes['pagination'], ['dots', 'progress', 'none'], true) ? (string) $attributes['pagination'] : 'dots';
    }

    // The arrows come before the row, so the keyboard meets them before the slides' links.
    $arrow_html = '';
    if ($arrows) {
        $prev = $is_slider ? __('Previous slide', 'stackless-theme') : __('Previous items', 'stackless-theme');
        $next = $is_slider ? __('Next slide', 'stackless-theme') : __('Next items', 'stackless-theme');
        // Hidden until the script runs: without it the buttons would do nothing.
        $arrow_html = '<button type="button" class="stb-scroller-arrow stb-scroller-prev" aria-controls="' . esc_attr($track_id) . '" aria-label="' . esc_attr($prev) . '" hidden><span class="stb-icon-angle-left"></span></button>'
            . '<button type="button" class="stb-scroller-arrow stb-scroller-next" aria-controls="' . esc_attr($track_id) . '" aria-label="' . esc_attr($next) . '" hidden><span class="stb-icon-angle-right"></span></button>';
    }

    $nav = '';
    if ($pagination !== 'none' || $autoplay) {
        $nav .= '<div class="stb-scroller-nav">';
        if ($autoplay) {
            $pause = $is_slider ? __('Pause the slideshow', 'stackless-theme') : __('Pause the carousel', 'stackless-theme');
            $nav .= '<button type="button" class="stb-scroller-pause" aria-controls="' . esc_attr($track_id) . '" aria-label="' . esc_attr($pause) . '" hidden>'
                . '<span class="stb-scroller-pause-icon stb-icon-player-pause"></span><span class="stb-scroller-play-icon stb-icon-player-play" hidden></span></button>';
        }
        if ($pagination === 'dots') {
            $nav .= '<div class="stb-scroller-dots"></div>';
        } elseif ($pagination === 'progress') {
            $nav .= '<div class="stb-scroller-progress" aria-hidden="true"><span class="stb-scroller-progress-bar"></span></div>';
        }
        $nav .= '</div>';
    }

    return [
        'arrows' => $arrow_html,
        'nav' => $nav,
        'status' => '<p class="stb-scroller-status screen-reader-text" aria-live="polite" aria-atomic="true"></p>',
        'pagination' => $pagination,
    ];
}

/** A unique id for a scroller's track. */
function stackless_theme_blocks_scroller_id(string $kind): string
{
    return 'stb-' . $kind . '-' . wp_unique_id();
}

/* ------------------------------------------------------------------ Slider */

function stackless_theme_blocks_render_slider(array $attributes, string $content = ''): string
{
    if (trim($content) === '') {
        return '';
    }
    $heights = stackless_theme_blocks_slider_heights();
    $height = isset($attributes['height']) ? (string) $attributes['height'] : 'large';
    if ($height === 'viewport') {
        $vh = isset($attributes['viewportHeight']) ? (int) $attributes['viewportHeight'] : 100;
        $vh = max(30, min(100, $vh));
        $css_height = 'calc(' . $vh . 'svh - var(--wp-admin--admin-bar--height, 0px) * ' . ($vh / 100) . ')';
    } else {
        $height = isset($heights[$height]) ? $height : 'large';
        $css_height = (string) $heights[$height];
    }

    $label = isset($attributes['label']) && trim((string) $attributes['label']) !== '' ? (string) $attributes['label'] : __('Slideshow', 'stackless-theme');
    $track_id = stackless_theme_blocks_scroller_id('slider');
    $controls = stackless_theme_blocks_scroller_controls($attributes, 'slider', $track_id);

    $classes = ['stb-slider', 'stb-scroller', 'stb-slider-h-' . sanitize_html_class($height)];
    if (!stackless_theme_blocks_flag($attributes, 'arrows', true)) {
        $classes[] = 'has-no-arrows';
    }

    return '<section class="' . esc_attr(implode(' ', array_unique($classes))) . '" style="' . esc_attr('--stb-slider-height:' . $css_height) . '"' . stackless_theme_blocks_scroller_attrs($attributes, 'slider', $label) . '>'
        . '<div class="stb-scroller-viewport">'
        . $controls['arrows']
        . '<div class="stb-scroller-track" id="' . esc_attr($track_id) . '" data-stb-track tabindex="0">' . $content . '</div>'
        . '</div>'
        . $controls['nav']
        . $controls['status']
        . '</section>';
}

/** The first Slider on the page gets fetchpriority="high" on its first photo. */
function stackless_theme_blocks_slider_first(): bool
{
    static $done = false;
    if ($done) {
        return false;
    }
    $done = true;
    return true;
}

function stackless_theme_blocks_render_slide(array $attributes, string $content = '', $block = null): string
{
    $place = $block instanceof WP_Block && isset($block->parsed_block['stackless_scroller']) ? (array) $block->parsed_block['stackless_scroller'] : [];
    $index = (int) ($place['index'] ?? 1);
    $total = (int) ($place['total'] ?? 1);

    $media_id = isset($attributes['mediaId']) ? (int) $attributes['mediaId'] : 0;
    $media_url = isset($attributes['mediaUrl']) ? (string) $attributes['mediaUrl'] : '';
    $focal = isset($attributes['focalPoint']) && is_array($attributes['focalPoint']) ? $attributes['focalPoint'] : [];
    $fx = isset($focal['x']) ? max(0.0, min(1.0, (float) $focal['x'])) : 0.5;
    $fy = isset($focal['y']) ? max(0.0, min(1.0, (float) $focal['y'])) : 0.5;
    $position = round($fx * 100, 2) . '% ' . round($fy * 100, 2) . '%';
    $dim = isset($attributes['dimRatio']) ? max(0, min(100, (int) $attributes['dimRatio'])) : 40;

    $content_position = isset($attributes['contentPosition']) ? strtolower(trim((string) $attributes['contentPosition'])) : 'center center';
    $parts = explode(' ', $content_position);
    $vertical = in_array($parts[0] ?? '', ['top', 'center', 'bottom'], true) ? $parts[0] : 'center';
    $horizontal = in_array($parts[1] ?? '', ['left', 'center', 'right'], true) ? $parts[1] : 'center';

    // The first slide's photo is the slider's first paint: never lazy. The rest wait until near.
    $first = $index === 1;
    $image_attrs = [
        'class' => 'stb-slide-image',
        'alt' => '',
        'sizes' => '100vw',
        'decoding' => $first ? 'auto' : 'async',
        'loading' => $first ? 'eager' : 'lazy',
        'style' => 'object-position:' . $position,
    ];
    if ($first && stackless_theme_blocks_slider_first()) {
        $image_attrs['fetchpriority'] = 'high';
    }
    $image = '';
    if ($media_id && wp_attachment_is_image($media_id)) {
        $alt = trim((string) get_post_meta($media_id, '_wp_attachment_image_alt', true));
        $image_attrs['alt'] = $alt;
        $image = wp_get_attachment_image($media_id, 'full', false, $image_attrs);
    } elseif ($media_url !== '') {
        $image = '<img src="' . esc_url($media_url) . '" class="stb-slide-image" alt="" decoding="' . esc_attr($image_attrs['decoding']) . '" loading="' . esc_attr($image_attrs['loading']) . '" style="' . esc_attr($image_attrs['style']) . '" />';
    }

    $classes = ['stb-slide', 'is-v-' . $vertical, 'is-h-' . $horizontal];
    if ($image !== '') {
        $classes[] = 'has-image';
    }

    $html = '<div class="' . esc_attr(implode(' ', array_unique($classes))) . '" role="group" aria-roledescription="' . esc_attr__('slide', 'stackless-theme') . '" aria-label="' . esc_attr(stackless_theme_blocks_slide_label($index, $total)) . '">';
    $html .= $image;
    if ($image !== '' && $dim > 0) {
        $html .= '<span class="stb-slide-overlay" aria-hidden="true" style="opacity:' . esc_attr((string) ($dim / 100)) . '"></span>';
    }
    $html .= '<div class="stb-slide-content"><div class="stb-slide-inner">' . $content . '</div></div>';
    return $html . '</div>';
}

/**
 * A Slider that opens the page lays a clear header over a photo: switch the header to light text,
 * as a dark cover block does.
 */
add_filter('stackless_theme_header_on_dark', 'stackless_theme_blocks_slider_header_on_dark');
function stackless_theme_blocks_slider_header_on_dark($dark)
{
    if ($dark || !is_singular() || !function_exists('stackless_theme_shows') || stackless_theme_shows('title')) {
        return $dark;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post || strpos((string) $post->post_content, '<!-- wp:stackless/slider') === false) {
        return $dark;
    }
    foreach (parse_blocks((string) $post->post_content) as $block) {
        if (!empty($block['blockName'])) {
            return $block['blockName'] === 'stackless/slider';
        }
    }
    return $dark;
}

/* ------------------------------------------------------------------ Carousel */

/**
 * Wraps each block inside a Carousel in an item, numbered "2 of 5", and closes a Slider's or
 * Carousel's counter once it has rendered.
 */
add_filter('render_block', 'stackless_theme_blocks_carousel_item', 10, 2);
function stackless_theme_blocks_carousel_item($block_content, $parsed_block)
{
    if (is_array($parsed_block) && in_array($parsed_block['blockName'] ?? '', ['stackless/slider', 'stackless/carousel'], true)) {
        stackless_theme_blocks_scroller_count('close');
    }
    $place = is_array($parsed_block) && isset($parsed_block['stackless_scroller']) ? (array) $parsed_block['stackless_scroller'] : [];
    if (($place['parent'] ?? '') !== 'stackless/carousel' || (int) ($place['total'] ?? 0) < 2 || trim((string) $block_content) === '') {
        return $block_content;
    }
    return '<div class="stb-carousel-item" role="group" aria-roledescription="' . esc_attr__('slide', 'stackless-theme') . '" aria-label="' . esc_attr(stackless_theme_blocks_slide_label((int) $place['index'], (int) $place['total'])) . '">' . $block_content . '</div>';
}

/**
 * Marks the list inside a Carousel's only block as the row that scrolls. Returns the markup with
 * the list marked, or '' when no known list is found.
 */
function stackless_theme_blocks_carousel_list(string $content, string $track_id, string $extra_class = '', ?string &$used_id = null): string
{
    $used_id = $track_id;
    if (!class_exists('WP_HTML_Tag_Processor')) {
        return '';
    }
    $lists = stackless_theme_blocks_carousel_lists();
    foreach (stackless_theme_blocks_classes($extra_class) as $class) {
        array_unshift($lists, $class);
    }
    foreach ($lists as $class) {
        $tags = new WP_HTML_Tag_Processor($content);
        while ($tags->next_tag()) {
            if ($tags->has_class((string) $class)) {
                $tags->add_class('stb-scroller-track');
                // A list that already has an id (an anchor) keeps it; the controls are pointed at it.
                $used_id = $tags->get_attribute('id') ? (string) $tags->get_attribute('id') : $track_id;
                $tags->set_attribute('id', $used_id);
                $tags->set_attribute('data-stb-track', true);
                $tags->set_attribute('tabindex', '0');
                return $tags->get_updated_html();
            }
        }
    }
    return '';
}

function stackless_theme_blocks_render_carousel(array $attributes, string $content = '', $block = null): string
{
    if (trim($content) === '') {
        return '';
    }
    $per_view = static function (string $key, float $default) use ($attributes): string {
        $value = isset($attributes[$key]) ? (float) $attributes[$key] : $default;
        return (string) round(max(1.0, min(8.0, $value)), 2);
    };
    $gaps = stackless_theme_blocks_carousel_gaps();
    $gap = isset($attributes['gap'], $gaps[$attributes['gap']]) ? (string) $gaps[$attributes['gap']] : (string) ($gaps['medium'] ?? '1.5rem');
    $style = '--stb-per-view:' . $per_view('perView', 3) . ';--stb-per-view-tablet:' . $per_view('perViewTablet', 2) . ';--stb-per-view-phone:' . $per_view('perViewPhone', 1) . ';--stb-gap:' . $gap;

    $label = isset($attributes['label']) && trim((string) $attributes['label']) !== '' ? (string) $attributes['label'] : __('Carousel', 'stackless-theme');
    $track_id = stackless_theme_blocks_scroller_id('carousel');
    $controls = stackless_theme_blocks_scroller_controls($attributes, 'carousel', $track_id);

    $classes = ['stb-carousel', 'stb-scroller'];
    if (!stackless_theme_blocks_flag($attributes, 'snap', true)) {
        $classes[] = 'has-no-snap';
    }

    // A single block: scroll its list (a Query Loop's posts, a product grid's products).
    $count = $block instanceof WP_Block ? count($block->inner_blocks) : 2;
    $body = '';
    if ($count === 1) {
        $list_id = $track_id;
        $list = stackless_theme_blocks_carousel_list($content, $track_id, (string) ($attributes['listClass'] ?? ''), $list_id);
        if ($list !== '' && $list_id !== $track_id) {
            foreach (['arrows', 'nav'] as $part) {
                $controls[$part] = str_replace('aria-controls="' . esc_attr($track_id) . '"', 'aria-controls="' . esc_attr($list_id) . '"', $controls[$part]);
            }
        }
        if ($list !== '') {
            $classes[] = 'is-list';
            $body = '<div class="stb-carousel-body">' . $list . '</div>';
        } else {
            $content = '<div class="stb-carousel-item" role="group" aria-roledescription="' . esc_attr__('slide', 'stackless-theme') . '" aria-label="' . esc_attr(stackless_theme_blocks_slide_label(1, 1)) . '">' . $content . '</div>';
        }
    }
    if ($body === '') {
        $body = '<div class="stb-scroller-track" id="' . esc_attr($track_id) . '" data-stb-track tabindex="0">' . $content . '</div>';
    }

    return '<section class="' . esc_attr(implode(' ', array_unique($classes))) . '" style="' . esc_attr($style) . '"' . stackless_theme_blocks_scroller_attrs($attributes, 'carousel', $label) . '>'
        . '<div class="stb-scroller-viewport">' . $controls['arrows'] . $body . '</div>'
        . $controls['nav']
        . $controls['status']
        . '</section>';
}
