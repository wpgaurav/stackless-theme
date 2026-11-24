<?php
/**
 * Stackless blocks: content and card blocks under the stackless/ namespace.
 *
 * The blocks are dynamic: they save only their attributes, and PHP renders the markup. Their
 * names, attributes and parent and child rules are a storage contract, so do not rename, add or
 * drop them.
 *
 * This folder is self-contained: it needs only stackless_theme_part(), stackless_theme_icon()
 * and the asset helpers from the theme.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

require __DIR__ . '/icons.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/render.php';
require __DIR__ . '/cards.php';
require __DIR__ . '/toc.php';
require __DIR__ . '/query-loop.php';
require __DIR__ . '/slider.php';
require __DIR__ . '/core-styles.php';

/**
 * Block definitions, keyed by the name after the slash.
 *
 * - callback:   the render callback.
 * - attributes: only for the blocks that declare them in PHP. The other blocks declare
 *               theirs in the editor script, and the callbacks rely on unset attributes staying
 *               unset, so do not add defaults here.
 * - style:      false when the block has no stylesheet of its own.
 * - style_deps: shared stylesheets (assets/css/blocks/<name>.css) the block's stylesheet needs.
 * - script:     true when the block has a front-end script (assets/js/blocks/<name>.js), or the
 *               name of a script it shares with other blocks.
 * - editor:     false for child blocks that are registered by their parent's editor script.
 *
 * @return array<string, array<string, mixed>>
 */
function stackless_theme_blocks(): array
{
    static $blocks = null;
    if ($blocks !== null) {
        return $blocks;
    }
    $blocks = [
        'arrow' => [
            'title' => __('Arrow', 'stackless-theme'),
            'description' => __('Show an icon to encourage scrolling or draw attention.', 'stackless-theme'),
            'icon' => 'arrow-down-alt2',
            'callback' => 'stackless_theme_blocks_render_arrow',
        ],
        'book-card' => [
            'title' => __('Book Card', 'stackless-theme'),
            'description' => __('Display book information. Fetch data automatically from Open Library.', 'stackless-theme'),
            'icon' => 'book-alt',
            'callback' => 'stackless_theme_blocks_render_book_card',
        ],
        'callout' => [
            'title' => __('Callout', 'stackless-theme'),
            'description' => __('Create a helpful callout with icon, bullet list, and text.', 'stackless-theme'),
            'icon' => 'megaphone',
            'callback' => 'stackless_theme_blocks_render_callout',
        ],
        'content-upgrade' => [
            'title' => __('Content Upgrade', 'stackless-theme'),
            'description' => __('Display an offer box in your content with a link.', 'stackless-theme'),
            'icon' => 'awards',
            'callback' => 'stackless_theme_blocks_render_content_upgrade',
        ],
        'faq' => [
            'title' => __('FAQ', 'stackless-theme'),
            'description' => __('Create a schema-ready FAQ section with accordions.', 'stackless-theme'),
            'icon' => 'editor-help',
            'callback' => 'stackless_theme_blocks_render_faq',
            'script' => true,
            'attributes' => [
                'schemaEnabled' => ['type' => 'boolean', 'default' => true],
                'multiOpen' => ['type' => 'boolean', 'default' => false],
                'firstOpen' => ['type' => 'boolean', 'default' => true],
            ],
        ],
        'faq-item' => [
            'title' => __('FAQ Item', 'stackless-theme'),
            'description' => __('A single question and answer pair.', 'stackless-theme'),
            'icon' => 'editor-help',
            'parent' => ['stackless/faq'],
            'callback' => 'stackless_theme_blocks_render_faq_item',
            'style' => false,
            'editor' => false,
        ],
        'github-card' => [
            'title' => __('GitHub Card', 'stackless-theme'),
            'description' => __('Display GitHub repository information. Fetch data automatically from GitHub.', 'stackless-theme'),
            'icon' => 'editor-code',
            'callback' => 'stackless_theme_blocks_render_github_card',
        ],
        'key-takeaway' => [
            'title' => __('Key Takeaway', 'stackless-theme'),
            'description' => __('Highlight important points or TL;DR summaries.', 'stackless-theme'),
            'icon' => 'lightbulb',
            'callback' => 'stackless_theme_blocks_render_key_takeaway',
        ],
        'npm-card' => [
            'title' => __('npm Package Card', 'stackless-theme'),
            'description' => __('Display npm package information. Fetch data automatically from the npm registry.', 'stackless-theme'),
            'icon' => 'editor-code',
            'callback' => 'stackless_theme_blocks_render_npm_card',
        ],
        'plugin-card' => [
            'title' => __('Plugin Card', 'stackless-theme'),
            'description' => __('Display WordPress plugin information. Fetch data automatically from WordPress.org.', 'stackless-theme'),
            'icon' => 'admin-plugins',
            'callback' => 'stackless_theme_blocks_render_plugin_card',
        ],
        'podcast-card' => [
            'title' => __('Podcast Card', 'stackless-theme'),
            'description' => __('Display a podcast episode card with artwork, metadata, and playback link. Fetches data from RSS feeds.', 'stackless-theme'),
            'icon' => 'microphone',
            'callback' => 'stackless_theme_blocks_render_podcast_card',
        ],
        'pull-quote' => [
            'title' => __('Pull Quote', 'stackless-theme'),
            'description' => __('Highlight a key quote or statement from your content.', 'stackless-theme'),
            'icon' => 'format-quote',
            'callback' => 'stackless_theme_blocks_render_pull_quote',
        ],
        'query-loop' => [
            'title' => __('Query Loop', 'stackless-theme'),
            'description' => __('Display posts in a grid or list with a custom query.', 'stackless-theme'),
            'icon' => 'list-view',
            'callback' => 'stackless_theme_blocks_render_query_loop',
            'deps' => ['wp-server-side-render'],
            'attributes' => [
                'layout' => ['type' => 'string', 'default' => 'cards'],
                'postType' => ['type' => 'string', 'default' => 'post'],
                'postsPerPage' => ['type' => 'number', 'default' => 6],
                'columns' => ['type' => 'number', 'default' => 2],
                'orderBy' => ['type' => 'string', 'default' => 'date'],
                'order' => ['type' => 'string', 'default' => 'DESC'],
                'categories' => ['type' => 'string', 'default' => ''],
                'tags' => ['type' => 'string', 'default' => ''],
                'taxonomy' => ['type' => 'string', 'default' => ''],
                'terms' => ['type' => 'string', 'default' => ''],
                'offset' => ['type' => 'number', 'default' => 0],
                'excludeCurrent' => ['type' => 'boolean', 'default' => true],
                'showExcerpt' => ['type' => 'boolean', 'default' => true],
                'excerptLength' => ['type' => 'number', 'default' => 0],
                'postIn' => ['type' => 'string', 'default' => ''],
                'postNotIn' => ['type' => 'string', 'default' => ''],
                'author' => ['type' => 'string', 'default' => ''],
                'secondPostType' => ['type' => 'string', 'default' => ''],
                'secondTaxonomy' => ['type' => 'string', 'default' => ''],
                'secondTerms' => ['type' => 'string', 'default' => ''],
                'showImage' => ['type' => 'boolean', 'default' => true],
                'imageSize' => ['type' => 'string', 'default' => ''],
                'titleTag' => ['type' => 'string', 'default' => 'h2'],
                'showByline' => ['type' => 'boolean', 'default' => true],
                'showCategory' => ['type' => 'boolean', 'default' => true],
                'showAuthor' => ['type' => 'boolean', 'default' => true],
                'showDate' => ['type' => 'boolean', 'default' => true],
                'showAvatar' => ['type' => 'boolean', 'default' => true],
                'readMoreText' => ['type' => 'string', 'default' => ''],
                'featuredCount' => ['type' => 'number', 'default' => 0],
                'noResultsText' => ['type' => 'string', 'default' => ''],
                'classLoop' => ['type' => 'string', 'default' => ''],
                'classItem' => ['type' => 'string', 'default' => ''],
                'classImage' => ['type' => 'string', 'default' => ''],
                'classContent' => ['type' => 'string', 'default' => ''],
                'classTitle' => ['type' => 'string', 'default' => ''],
                'classExcerpt' => ['type' => 'string', 'default' => ''],
                'classByline' => ['type' => 'string', 'default' => ''],
                'classReadMore' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'quote' => [
            'title' => __('Quote', 'stackless-theme'),
            'description' => __('Versatile quote block with blockquote styles, testimonials, callout quotes, and more.', 'stackless-theme'),
            'icon' => 'format-quote',
            'callback' => 'stackless_theme_blocks_render_quote',
        ],
        'related-content' => [
            'title' => __('Related Content', 'stackless-theme'),
            'description' => __('Link to related articles to keep readers engaged.', 'stackless-theme'),
            'icon' => 'admin-links',
            'callback' => 'stackless_theme_blocks_render_related_content',
        ],
        'section' => [
            'title' => __('Section', 'stackless-theme'),
            'description' => __('A flexible container with semantic HTML tags, custom attributes, and CSS.', 'stackless-theme'),
            'icon' => 'editor-insertmore',
            'callback' => 'stackless_theme_blocks_render_section',
            'style' => false,
            'attributes' => [
                'htmlTag' => ['type' => 'string', 'default' => 'div'],
                'customTag' => ['type' => 'string', 'default' => ''],
                'sectionCSS' => ['type' => 'string', 'default' => ''],
                'htmlAttributes' => ['type' => 'string', 'default' => ''],
                'ariaHidden' => ['type' => 'boolean', 'default' => false],
                'ariaLabel' => ['type' => 'string', 'default' => ''],
                'ariaLabelledBy' => ['type' => 'string', 'default' => ''],
                'role' => ['type' => 'string', 'default' => ''],
                'customRole' => ['type' => 'string', 'default' => ''],
                'hidden' => ['type' => 'boolean', 'default' => false],
                'anchor' => ['type' => 'string', 'default' => ''],
                'className' => ['type' => 'string', 'default' => ''],
                'align' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'share-notice' => [
            'title' => __('Share Notice', 'stackless-theme'),
            'description' => __('Display quoteable text with social media share buttons.', 'stackless-theme'),
            'icon' => 'networking',
            'callback' => 'stackless_theme_blocks_render_share_notice',
        ],
        'side-promotion' => [
            'title' => __('Side Promotion', 'stackless-theme'),
            'description' => __('Display promotional panels in the sidebar or content margins.', 'stackless-theme'),
            'icon' => 'megaphone',
            'callback' => 'stackless_theme_blocks_render_side_promotion',
        ],
        'slider' => [
            'title' => __('Slider', 'stackless-theme'),
            'description' => __('Full-width photo slides with a heading, text and buttons on each.', 'stackless-theme'),
            'icon' => 'slides',
            'keywords' => ['slider', 'slideshow', 'hero', 'banner', 'carousel'],
            'callback' => 'stackless_theme_blocks_render_slider',
            'style_deps' => ['scroller'],
            'script' => 'scroller',
            'supports' => [
                'anchor' => true,
                'className' => true,
                'html' => false,
                'align' => ['wide', 'full'],
                'spacing' => ['margin' => ['top', 'bottom']],
            ],
            'attributes' => [
                'label' => ['type' => 'string', 'default' => ''],
                'height' => ['type' => 'string', 'default' => 'large'],
                'viewportHeight' => ['type' => 'number', 'default' => 100],
                'autoplay' => ['type' => 'boolean', 'default' => false],
                'interval' => ['type' => 'number', 'default' => 6],
                'arrows' => ['type' => 'boolean', 'default' => true],
                'dots' => ['type' => 'boolean', 'default' => true],
                'loop' => ['type' => 'boolean', 'default' => true],
                'align' => ['type' => 'string', 'default' => 'full'],
            ],
        ],
        'slide' => [
            'title' => __('Slide', 'stackless-theme'),
            'description' => __('One slide: a photo, an overlay, and blocks laid over it.', 'stackless-theme'),
            'icon' => 'format-image',
            'parent' => ['stackless/slider'],
            'callback' => 'stackless_theme_blocks_render_slide',
            'style' => false,
            'editor' => false,
            'supports' => [
                'anchor' => true,
                'className' => true,
                'html' => false,
                'reusable' => false,
                'color' => ['text' => true, 'background' => true, 'gradients' => true],
                'spacing' => ['padding' => true],
            ],
            'attributes' => [
                'mediaId' => ['type' => 'number', 'default' => 0],
                'mediaUrl' => ['type' => 'string', 'default' => ''],
                'focalPoint' => ['type' => 'object', 'default' => ['x' => 0.5, 'y' => 0.5]],
                'dimRatio' => ['type' => 'number', 'default' => 40],
                'contentPosition' => ['type' => 'string', 'default' => 'center center'],
            ],
        ],
        'carousel' => [
            'title' => __('Carousel', 'stackless-theme'),
            'description' => __('A row of cards, images, products or posts that scrolls sideways.', 'stackless-theme'),
            'icon' => 'images-alt2',
            'keywords' => ['carousel', 'scroller', 'products', 'cards', 'gallery'],
            'callback' => 'stackless_theme_blocks_render_carousel',
            'style_deps' => ['scroller'],
            'script' => 'scroller',
            'supports' => [
                'anchor' => true,
                'className' => true,
                'html' => false,
                'align' => ['wide', 'full'],
                'spacing' => ['margin' => ['top', 'bottom']],
            ],
            'attributes' => [
                'label' => ['type' => 'string', 'default' => ''],
                'perView' => ['type' => 'number', 'default' => 3],
                'perViewTablet' => ['type' => 'number', 'default' => 2],
                'perViewPhone' => ['type' => 'number', 'default' => 1],
                'gap' => ['type' => 'string', 'default' => 'medium'],
                'arrows' => ['type' => 'boolean', 'default' => true],
                'pagination' => ['type' => 'string', 'default' => 'dots'],
                'snap' => ['type' => 'boolean', 'default' => true],
                'loop' => ['type' => 'boolean', 'default' => false],
                'autoplay' => ['type' => 'boolean', 'default' => false],
                'interval' => ['type' => 'number', 'default' => 6],
                'listClass' => ['type' => 'string', 'default' => ''],
            ],
        ],
        'side-promotion-item' => [
            'title' => __('Promotion Item', 'stackless-theme'),
            'description' => __('A single promotional panel with image, title, and link.', 'stackless-theme'),
            'icon' => 'format-image',
            'parent' => ['stackless/side-promotion'],
            'callback' => 'stackless_theme_blocks_render_side_promotion_item',
            'style' => false,
            'editor' => false,
        ],
        'spoiler' => [
            'title' => __('Spoiler', 'stackless-theme'),
            'description' => __('Click to reveal hidden content.', 'stackless-theme'),
            'icon' => 'hidden',
            'callback' => 'stackless_theme_blocks_render_spoiler',
            'script' => true,
        ],
        'step' => [
            'title' => __('Step', 'stackless-theme'),
            'icon' => 'editor-ol',
            'parent' => ['stackless/steps'],
            'callback' => 'stackless_theme_blocks_render_step',
            'style' => false,
            'editor' => false,
        ],
        'steps' => [
            'title' => __('Steps', 'stackless-theme'),
            'description' => __('Create a sequence of steps.', 'stackless-theme'),
            'icon' => 'editor-ol',
            'callback' => 'stackless_theme_blocks_render_steps',
        ],
        'theme-card' => [
            'title' => __('Theme Card', 'stackless-theme'),
            'description' => __('Display WordPress theme information. Fetch data automatically from WordPress.org.', 'stackless-theme'),
            'icon' => 'admin-appearance',
            'callback' => 'stackless_theme_blocks_render_theme_card',
        ],
        'table-of-contents' => [
            'title' => __('Table of Contents', 'stackless-theme'),
            'description' => __('List the headings in this post, with links that jump to each section.', 'stackless-theme'),
            'icon' => 'list-view',
            'keywords' => ['table of contents', 'toc', 'outline', 'headings', 'jump links'],
            'callback' => 'stackless_theme_blocks_render_table_of_contents',
            'script' => true,
            'deps' => ['wp-data'],
            'attributes' => [
                'title' => ['type' => 'string', 'default' => ''],
                'levels' => ['type' => 'string', 'default' => ''],
                // No defaults on the three toggles: unset means "use the default" (see toc.php).
                'hierarchical' => ['type' => 'boolean'],
                'numbering' => ['type' => 'string', 'default' => ''],
                'collapsible' => ['type' => 'boolean'],
                'collapsed' => ['type' => 'boolean'],
                'exclude' => ['type' => 'string', 'default' => ''],
                'min' => ['type' => 'number', 'default' => 0],
            ],
        ],
        'youtube-card' => [
            'title' => __('YouTube Card', 'stackless-theme'),
            'description' => __('Display a YouTube video card. Fetch title and channel automatically from YouTube.', 'stackless-theme'),
            'icon' => 'video-alt3',
            'callback' => 'stackless_theme_blocks_render_youtube_card',
        ],
    ];
    /**
     * Filters the block definitions. Stackless Pro and child plugins add blocks here.
     */
    $blocks = (array) apply_filters('stackless_theme_blocks', $blocks);
    return $blocks;
}

/** The hook part name of a block: "key-takeaway" is the part "block_key_takeaway". */
function stackless_theme_blocks_part(string $slug): string
{
    return 'block_' . str_replace('-', '_', $slug);
}

/** Whether WordPress prints a block's stylesheet only on pages that render the block. */
function stackless_theme_blocks_on_demand(): bool
{
    if (function_exists('wp_should_load_block_assets_on_demand')) {
        return wp_should_load_block_assets_on_demand();
    }
    return function_exists('wp_should_load_separate_core_block_assets') && wp_should_load_separate_core_block_assets();
}

/** A block asset's theme-relative path, or '' when the file does not exist. */
function stackless_theme_blocks_asset(string $type, string $name): string
{
    $path = 'assets/' . $type . '/blocks/' . $name . '.' . $type;
    return is_readable(STACKLESS_THEME_DIR . '/' . $path) ? stackless_theme_asset($path) : '';
}

add_action('init', 'stackless_theme_blocks_register');
function stackless_theme_blocks_register(): void
{
    // The token bridge and the utility classes the block markup uses. Every block stylesheet
    // depends on it, so it loads with the first block on a page and never without one.
    $bridge = stackless_theme_blocks_asset('css', 'bridge');
    if ($bridge !== '') {
        wp_register_style('stackless-blocks', STACKLESS_THEME_URI . '/' . $bridge, [], stackless_theme_asset_version($bridge));
    }

    // In the editor every block's stylesheet loads, since any block can be inserted. On the
    // front end WordPress prints a registered block style on demand when the site loads block
    // assets that way; where it would print them all on every page, the handle stays off the
    // block type and the render callback enqueues it instead (see stackless_theme_blocks_render).
    $attach = is_admin() || stackless_theme_blocks_on_demand();

    foreach (stackless_theme_blocks() as $slug => $block) {
        $args = [
            'api_version' => 3,
            'title' => $block['title'] ?? $slug,
            'category' => 'stackless',
            'textdomain' => 'stackless-theme',
            'supports' => stackless_theme_blocks_supports((string) $slug, $block),
        ];
        foreach (['description', 'icon', 'parent', 'keywords', 'attributes'] as $key) {
            if (isset($block[$key])) {
                $args[$key] = $block[$key];
            }
        }
        if (!empty($block['callback']) && is_callable($block['callback'])) {
            $args['render_callback'] = stackless_theme_blocks_render((string) $slug, $block['callback']);
        }

        $handle = 'stackless-block-' . $slug;
        $css = ($block['style'] ?? true) ? stackless_theme_blocks_asset('css', (string) $slug) : '';
        if ($css !== '') {
            $style_deps = ['stackless-blocks'];
            foreach ((array) ($block['style_deps'] ?? []) as $shared) {
                $shared_css = stackless_theme_blocks_asset('css', (string) $shared);
                if ($shared_css === '') {
                    continue;
                }
                if (!wp_style_is('stackless-block-' . $shared, 'registered')) {
                    wp_register_style('stackless-block-' . $shared, STACKLESS_THEME_URI . '/' . $shared_css, ['stackless-blocks'], stackless_theme_asset_version($shared_css));
                }
                $style_deps[] = 'stackless-block-' . $shared;
            }
            wp_register_style($handle, STACKLESS_THEME_URI . '/' . $css, $style_deps, stackless_theme_asset_version($css));
            if ($attach) {
                $args['style_handles'] = [$handle];
            }
        } elseif ($attach && $bridge !== '') {
            // No stylesheet of its own: the block still needs the utility classes.
            $args['style_handles'] = ['stackless-blocks'];
        }

        // A shared script (the Slider's and Carousel's) registers once under its own name.
        $script = !empty($block['script']) ? (is_string($block['script']) ? $block['script'] : (string) $slug) : '';
        $js = $script !== '' ? stackless_theme_blocks_asset('js', $script) : '';
        if ($js !== '') {
            $script_handle = 'stackless-block-' . $script;
            if (!wp_script_is($script_handle, 'registered')) {
                wp_register_script($script_handle, STACKLESS_THEME_URI . '/' . $js, [], stackless_theme_asset_version($js), ['in_footer' => true, 'strategy' => 'defer']);
            }
            $args['view_script_handles'] = [$script_handle];
        }

        register_block_type('stackless/' . $slug, $args);
    }
}

/** The stylesheet handle a block needs on the front end, or '' when it needs none. */
function stackless_theme_blocks_style_handle(string $slug): string
{
    if (wp_style_is('stackless-block-' . $slug, 'registered')) {
        return 'stackless-block-' . $slug;
    }
    return wp_style_is('stackless-blocks', 'registered') ? 'stackless-blocks' : '';
}

add_filter('block_categories_all', 'stackless_theme_blocks_category');
function stackless_theme_blocks_category(array $categories): array
{
    return array_merge([['slug' => 'stackless', 'title' => __('Stackless', 'stackless-theme')]], $categories);
}

/**
 * Supports. Keep the baseline as it is: the inspector controls write these attributes, and
 * stored content depends on them.
 */
function stackless_theme_blocks_supports(string $slug, array $block = []): array
{
    if (isset($block['supports']) && is_array($block['supports'])) {
        $supports = $block['supports'];
    } elseif (in_array($slug, ['faq-item', 'step', 'side-promotion-item'], true)) {
        $supports = [
            'anchor' => true,
            'className' => true,
            'spacing' => ['padding' => true],
            'typography' => ['fontSize' => true, 'lineHeight' => true],
            'color' => ['text' => true, 'background' => true],
        ];
    } else {
        $supports = [
            'anchor' => true,
            'className' => true,
            'spacing' => ['margin' => ['top', 'bottom'], 'padding' => true],
            'typography' => ['fontSize' => true, 'lineHeight' => true, '__experimentalFontFamily' => true],
            'color' => ['text' => true, 'background' => true],
            'background' => ['backgroundImage' => true, 'gradient' => true],
            'dimensions' => ['minWidth' => true],
        ];
    }

    $extras = [
        'callout' => ['align' => true],
        'content-upgrade' => ['align' => true],
        'share-notice' => ['align' => true],
        'pull-quote' => ['align' => ['wide', 'full']],
        'quote' => ['align' => ['wide', 'full']],
        'query-loop' => ['align' => ['wide', 'full']],
        'section' => ['align' => ['wide', 'full'], 'anchor' => true, 'html' => false, 'className' => true],
        'faq' => ['html' => false],
        'faq-item' => ['html' => false],
        'steps' => ['html' => false],
        'step' => ['html' => false],
    ];
    if (isset($extras[$slug])) {
        $supports = array_merge($supports, $extras[$slug]);
    }

    // These blocks own textColor, or fontSize and fontFamily, as attributes that hold raw CSS
    // values. The core support of the same name would write a preset slug into them.
    if (in_array($slug, ['callout', 'content-upgrade', 'key-takeaway', 'spoiler'], true)) {
        unset($supports['color']);
    }
    if (in_array($slug, ['pull-quote', 'quote'], true)) {
        $supports['typography'] = ['lineHeight' => true];
    }

    return (array) apply_filters('stackless_theme_blocks_supports', $supports, $slug);
}

/* ------------------------------------------------------------------ Editor */

add_action('enqueue_block_editor_assets', 'stackless_theme_blocks_editor_assets');
function stackless_theme_blocks_editor_assets(): void
{
    $common = stackless_theme_blocks_asset('js', 'editor/common');
    if ($common === '') {
        return;
    }
    $base = ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-data'];
    wp_enqueue_script('stackless-blocks-editor', STACKLESS_THEME_URI . '/' . $common, $base, stackless_theme_asset_version($common), ['in_footer' => true]);
    wp_add_inline_script('stackless-blocks-editor', 'window.StacklessBlocks = Object.assign(window.StacklessBlocks || {}, ' . wp_json_encode(stackless_theme_blocks_editor_data()) . ');', 'before');
    wp_add_inline_script('stackless-blocks-editor', 'window.StacklessTocDefaults = ' . wp_json_encode(stackless_theme_blocks_toc_defaults()) . ';', 'before');
    wp_add_inline_script('stackless-blocks-editor', 'window.StacklessCardImages = ' . wp_json_encode(stackless_theme_blocks_card_editor_data()) . ';', 'before');
    wp_add_inline_script('stackless-blocks-editor', 'window.StacklessRelatedContent = ' . wp_json_encode([
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('stackless_search_posts'),
    ]) . ';', 'before');

    $post_types = [];
    foreach (get_post_types(['public' => true], 'objects') as $type) {
        $post_types[] = ['label' => $type->label, 'value' => $type->name];
    }
    $taxonomies = [];
    foreach (get_taxonomies(['public' => true], 'objects') as $taxonomy) {
        $taxonomies[] = ['label' => $taxonomy->label, 'value' => $taxonomy->name];
    }
    wp_add_inline_script('stackless-blocks-editor', 'window.StacklessQueryLoop = ' . wp_json_encode(['postTypes' => $post_types, 'taxonomies' => $taxonomies]) . ';', 'before');

    foreach (stackless_theme_blocks() as $slug => $block) {
        if (($block['editor'] ?? true) === false) {
            continue;
        }
        $js = stackless_theme_blocks_asset('js', 'editor/' . $slug);
        if ($js === '') {
            continue;
        }
        $deps = array_merge(['stackless-blocks-editor'], (array) ($block['deps'] ?? []));
        wp_enqueue_script('stackless-block-editor-' . $slug, STACKLESS_THEME_URI . '/' . $js, $deps, stackless_theme_asset_version($js), ['in_footer' => true]);
    }
    if (function_exists('wp_set_script_translations')) {
        wp_set_script_translations('stackless-blocks-editor', 'stackless-theme');
    }
}

/** Editor-only styles. Loaded through enqueue_block_assets so they reach the editor's iframe. */
add_action('enqueue_block_assets', 'stackless_theme_blocks_editor_styles');
function stackless_theme_blocks_editor_styles(): void
{
    if (!is_admin()) {
        return;
    }
    $css = stackless_theme_blocks_asset('css', 'editor');
    if ($css !== '') {
        wp_enqueue_style('stackless-blocks-editor', STACKLESS_THEME_URI . '/' . $css, ['stackless-blocks'], stackless_theme_asset_version($css));
    }
}

/**
 * Data for the editor scripts: the palette for the color-class pickers, the icon picker's
 * options with their SVG, the card blocks' small icons, and the popup list (empty until
 * Stackless Pro's Popups module fills it through the filter).
 */
function stackless_theme_blocks_editor_data(): array
{
    $colors = ['slug' => [], 'hex' => []];
    foreach (stackless_theme_blocks_palette() as $slug => $color) {
        $colors['slug'][$slug] = $color;
        $colors['hex'][$color] = $slug;
    }
    $icons = stackless_theme_blocks_icon_options();
    $svg = [];
    foreach ($icons as $icon) {
        $svg[$icon['value']] = stackless_theme_blocks_icon($icon['value']);
    }
    $data = [
        'colors' => $colors,
        'icons' => $icons,
        'iconSvg' => $svg,
        'cardIcons' => stackless_theme_blocks_card_icons(),
        'popups' => [['label' => __('None', 'stackless-theme'), 'value' => '']],
    ];
    return (array) apply_filters('stackless_theme_blocks_editor_data', $data);
}

/** Post search for the Related Content block's picker. */
add_action('wp_ajax_stackless_search_posts', 'stackless_theme_blocks_search_posts');
function stackless_theme_blocks_search_posts(): void
{
    check_ajax_referer('stackless_search_posts', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(__('You do not have permission to search posts.', 'stackless-theme'), 403);
    }
    $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
    if (strlen($search) < 2) {
        wp_send_json_success([]);
    }
    $query = new WP_Query([
        'post_type' => get_post_types(['public' => true]),
        'post_status' => 'publish',
        's' => $search,
        'posts_per_page' => 5,
        'no_found_rows' => true,
    ]);
    $results = [];
    foreach ($query->posts as $post) {
        $thumb = get_the_post_thumbnail_url($post, 'thumbnail');
        $source = $post->post_excerpt !== '' ? $post->post_excerpt : strip_shortcodes(excerpt_remove_blocks($post->post_content));
        $type = get_post_type_object($post->post_type);
        $results[] = [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'excerpt' => wp_trim_words(wp_strip_all_tags($source), 20, '&hellip;'),
            'url' => get_permalink($post),
            'thumbnail' => $thumb ? $thumb : '',
            'type' => $type ? $type->labels->singular_name : $post->post_type,
        ];
    }
    wp_send_json_success($results);
}
