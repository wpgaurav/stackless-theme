<?php
/**
 * Content options in Customize > Stackless Theme: Blog and archives, Single posts and Custom post
 * types. Every default reproduces the theme's output before these options existed.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Public custom post types that use the theme's own single and archive templates. Store types
 * (WooCommerce products, EDD downloads, FluentCart products) have templates and options of their own.
 *
 * @return array<string, WP_Post_Type>
 */
function stackless_theme_custom_post_types(): array
{
    $types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    foreach (['product', 'download', 'fluent-products'] as $store_type) {
        unset($types[$store_type]);
    }
    return (array) apply_filters('stackless_theme_custom_post_types', $types);
}

/** Defaults for a custom post type's options: off, which is how those singles looked before. */
function stackless_theme_cpt_option_defaults(string $post_type = ''): array
{
    return (array) apply_filters('stackless_theme_cpt_option_defaults', ['image' => false, 'meta' => false, 'nav' => false, 'related' => 0], $post_type);
}

/** One option for a custom post type: 'image', 'meta', 'nav' or 'related'. */
function stackless_theme_cpt_option(string $post_type, string $key)
{
    $defaults = stackless_theme_cpt_option_defaults($post_type);
    $value = get_theme_mod('stackless_cpt_' . $post_type . '_' . $key, $defaults[$key] ?? null);
    $value = is_bool($defaults[$key] ?? null) ? (bool) $value : (int) $value;
    return apply_filters('stackless_theme_cpt_option', $value, $post_type, $key);
}

/** Whether the single being shown is one of the customizable custom post types. */
function stackless_theme_is_custom_single(): bool
{
    return is_singular() && isset(stackless_theme_custom_post_types()[get_post_type()]);
}

/* ------------------------------------------------------------------ Single post layout */

/**
 * 'centered' (no sidebar), 'sidebar-right' or 'sidebar-left': the post's own choice, else the
 * Customizer's. Pages and custom post types have no Customizer layout, so without a choice of their
 * own they stay 'centered'.
 */
function stackless_theme_single_layout(): string
{
    $own = function_exists('stackless_theme_layout_choice') ? stackless_theme_layout_choice('_stackless_single_layout') : '';
    if ($own === '' && is_singular() && !is_singular('post')) {
        return 'centered';
    }
    return stackless_theme_sanitize_single_layout($own !== '' ? $own : stackless_theme_option('stackless_single_layout'));
}

/** 'center', 'left' or 'image-first': the post's own choice, else the Customizer's. */
function stackless_theme_single_header(): string
{
    $own = function_exists('stackless_theme_layout_choice') ? stackless_theme_layout_choice('_stackless_single_header') : '';
    return stackless_theme_sanitize_single_header($own !== '' ? $own : stackless_theme_option('stackless_single_header'));
}

/**
 * Whether the post, page or custom post type item being viewed sits beside a sidebar. Never on a
 * page template that owns its layout (Full width, Canvas, Page Blocks Builder's templates).
 */
function stackless_theme_single_has_sidebar(): bool
{
    if (!is_singular() || stackless_theme_single_layout() === 'centered') {
        return false;
    }
    return !stackless_theme_single_owns_layout();
}

/** Whether the single being viewed uses a page template that brings its own layout. */
function stackless_theme_single_owns_layout(): bool
{
    $template = (string) get_page_template_slug((int) get_queried_object_id());
    return $template !== '' && function_exists('stackless_theme_layout_owning_templates') && in_array($template, stackless_theme_layout_owning_templates(), true);
}

/**
 * The header style a page or custom post type item picked in its Layout panel: 'center', 'left',
 * 'image-first', 'image-full', or '' when it has no choice and keeps its usual header. Posts use
 * stackless_theme_single_header(), which falls back to the Customizer.
 */
function stackless_theme_entry_header_style(): string
{
    if (!function_exists('stackless_theme_layout_choice') || !is_singular() || is_singular('post') || stackless_theme_single_owns_layout()) {
        return '';
    }
    return stackless_theme_layout_choice('_stackless_single_header');
}

/**
 * Where the category goes: 'above' the title, in the 'byline', 'below' the post, or 'hidden'.
 * Before this option a checkbox only showed or hid it above the title; a site that unticked it
 * keeps it hidden.
 */
function stackless_theme_single_category_position(): string
{
    $saved = get_theme_mod('stackless_single_category_position', null);
    if ($saved === null && !get_theme_mod('stackless_single_category', true)) {
        return 'hidden';
    }
    return stackless_theme_sanitize_category_position($saved ?? 'above');
}

/** Where the byline (author, date, reading time) goes: 'below' the title, 'above' it, or at the 'end'. */
function stackless_theme_single_byline_position(): string
{
    return stackless_theme_sanitize_byline_position(stackless_theme_option('stackless_single_byline_position'));
}

add_action('widgets_init', static function (): void {
    register_sidebar([
        'name' => __('Single post', 'stackless-theme'),
        'id' => 'single',
        'description' => __('Beside a blog post when Single posts > Layout has a sidebar. Leave empty to show the Blog sidebar there.', 'stackless-theme'),
        'before_widget' => '<section id="%1$s" class="widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2 class="widget-title">',
        'after_title' => '</h2>',
    ]);
});

/* ------------------------------------------------------------------ Full-width featured image */

/** Whether the post being viewed opens with a full-width featured image above the post and sidebar. */
function stackless_theme_single_has_hero(): bool
{
    $chosen = is_singular('post')
        ? stackless_theme_single_header() === 'image-full' && stackless_theme_option('stackless_single_image') !== 'hidden'
        : stackless_theme_entry_header_style() === 'image-full';
    return $chosen && has_post_thumbnail((int) get_queried_object_id()) && !post_password_required((int) get_queried_object_id());
}

/** Prints that image: the post_hero part. */
function stackless_theme_single_hero(): void
{
    if (!stackless_theme_single_has_hero()) {
        return;
    }
    $context = ['post_id' => (int) get_queried_object_id(), 'layout' => stackless_theme_single_layout()];
    stackless_theme_part('post_hero', static function (array $context): void {
        printf(
            '<figure class="%1$s">%2$s</figure>',
            stackless_theme_class('post_hero', ['post-hero'], $context), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by the helper.
            get_the_post_thumbnail($context['post_id'], (string) apply_filters('stackless_theme_post_hero_size', 'full', $context), ['fetchpriority' => 'high', 'class' => 'post-hero__image']) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup.
        );
    }, $context);
}

add_filter('body_class', static function (array $classes): array {
    if (stackless_theme_single_has_hero()) {
        $classes[] = 'has-post-hero';
    }
    return $classes;
});

/* ------------------------------------------------------------------ Breadcrumbs */

/**
 * SEO and breadcrumb plugins that print a breadcrumb trail, in the order they are tried. Each
 * entry is [name, callable returning the trail's HTML]. The first active one with output wins.
 */
function stackless_theme_breadcrumb_providers(): array
{
    $capture = static function (callable $print): string {
        ob_start();
        $print();
        return (string) ob_get_clean();
    };
    $providers = [];
    if (function_exists('yoast_breadcrumb')) {
        $providers['yoast'] = ['Yoast SEO', static function (): string {
            return (string) yoast_breadcrumb('<nav class="yoast-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'stackless-theme') . '">', '</nav>', false);
        }];
    }
    if (function_exists('rank_math_the_breadcrumbs')) {
        $providers['rank-math'] = ['Rank Math', static function () use ($capture): string {
            return $capture('rank_math_the_breadcrumbs');
        }];
    }
    if (function_exists('seopress_display_breadcrumbs')) {
        $providers['seopress'] = ['SEOPress', static function () use ($capture): string {
            return $capture('seopress_display_breadcrumbs');
        }];
    }
    if (function_exists('aioseo_breadcrumbs')) {
        $providers['aioseo'] = ['All in One SEO', static function () use ($capture): string {
            return $capture('aioseo_breadcrumbs');
        }];
    }
    if (shortcode_exists('tsf_breadcrumb')) {
        $providers['tsf'] = ['The SEO Framework', static function (): string {
            return do_shortcode('[tsf_breadcrumb]');
        }];
    }
    if (shortcode_exists('slim_seo_breadcrumbs')) {
        $providers['slim-seo'] = ['Slim SEO', static function (): string {
            return do_shortcode('[slim_seo_breadcrumbs]');
        }];
    }
    if (function_exists('bcn_display')) {
        $providers['navxt'] = ['Breadcrumb NavXT', static function (): string {
            return '<nav class="breadcrumb-navxt" aria-label="' . esc_attr__('Breadcrumb', 'stackless-theme') . '">' . bcn_display(true) . '</nav>';
        }];
    }
    return (array) apply_filters('stackless_theme_breadcrumb_providers', $providers);
}

/** Yoast and Rank Math print their trail from a theme only when the theme says it supports them. */
add_action('after_setup_theme', static function (): void {
    add_theme_support('yoast-seo-breadcrumbs');
    add_theme_support('rank-math-breadcrumbs');
});

/**
 * Where the breadcrumb trail lines up: 'center' over a centered title, 'left' over a left-aligned
 * one or beside a sidebar. Pages and custom post types follow their Layout panel's header style.
 */
function stackless_theme_breadcrumbs_align(): string
{
    if (is_singular('post')) {
        $align = stackless_theme_single_header() === 'left' || stackless_theme_single_has_sidebar() ? 'left' : 'center';
    } else {
        $align = stackless_theme_entry_header_style() === 'center' && !stackless_theme_single_has_sidebar() ? 'center' : 'left';
    }
    return (string) apply_filters('stackless_theme_breadcrumbs_align', $align) === 'left' ? 'left' : 'center';
}

/** The breadcrumb trail for the current single: the first SEO plugin's, else Home / Category / Title. */
function stackless_theme_breadcrumbs(): string
{
    $wrap = '<div class="st-breadcrumbs st-breadcrumbs--' . stackless_theme_breadcrumbs_align() . '">';
    foreach (stackless_theme_breadcrumb_providers() as [, $render]) {
        $html = trim((string) $render());
        if (wp_strip_all_tags($html) !== '') {
            return $wrap . $html . '</div>';
        }
    }

    $sep = '<span class="breadcrumb-sep" aria-hidden="true">/</span>';
    $parts = ['<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'stackless-theme') . '</a>'];
    $type = get_post_type_object((string) get_post_type());
    if (is_singular('post')) {
        $blog = (int) get_option('page_for_posts');
        if ($blog > 0) {
            $parts[] = '<a href="' . esc_url((string) get_permalink($blog)) . '">' . esc_html(get_the_title($blog)) . '</a>';
        }
        $categories = get_the_category();
        if ($categories) {
            $parts[] = '<a href="' . esc_url(get_category_link($categories[0])) . '">' . esc_html($categories[0]->name) . '</a>';
        }
    } elseif ($type && $type->has_archive) {
        $parts[] = '<a href="' . esc_url((string) get_post_type_archive_link($type->name)) . '">' . esc_html($type->labels->name) . '</a>';
    }
    $parts[] = '<span aria-current="page">' . esc_html(get_the_title()) . '</span>';
    return $wrap . '<nav class="st-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'stackless-theme') . '">' . implode($sep, $parts) . '</nav></div>';
}

/** Breadcrumbs above a post or custom post type single, when switched on. */
function stackless_theme_maybe_breadcrumbs(): void
{
    if (!stackless_theme_option('stackless_single_breadcrumbs') || !(is_singular('post') || stackless_theme_is_custom_single())) {
        return;
    }
    stackless_theme_part('post_breadcrumbs', static function (): void {
        echo stackless_theme_breadcrumbs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the SEO plugin's own markup, or escaped above.
    }, ['post_id' => (int) get_the_ID()]);
}

/* ------------------------------------------------------------------ Sanitizers */

function stackless_theme_sanitize_single_layout($value): string
{
    return in_array($value, ['centered', 'sidebar-right', 'sidebar-left'], true) ? $value : 'centered';
}

function stackless_theme_sanitize_single_header($value): string
{
    return in_array($value, ['center', 'left', 'image-first', 'image-full'], true) ? $value : 'center';
}

function stackless_theme_sanitize_category_position($value): string
{
    return in_array($value, ['above', 'byline', 'below', 'hidden'], true) ? $value : 'above';
}

function stackless_theme_sanitize_byline_position($value): string
{
    return in_array($value, ['below', 'above', 'end'], true) ? $value : 'below';
}

function stackless_theme_sanitize_archive_layout($value): string
{
    return in_array($value, ['list', 'grid-2', 'grid-3'], true) ? $value : 'list';
}

function stackless_theme_sanitize_single_date($value): string
{
    return in_array($value, ['published', 'updated', 'hidden'], true) ? $value : 'published';
}

function stackless_theme_sanitize_single_image($value): string
{
    return in_array($value, ['wide', 'content', 'hidden'], true) ? $value : 'wide';
}

function stackless_theme_sanitize_excerpt_length($value): int
{
    return max(10, min(80, (int) $value ?: 28));
}

/* ------------------------------------------------------------------ Front end */

add_filter('excerpt_length', static function (): int {
    return stackless_theme_sanitize_excerpt_length(stackless_theme_option('stackless_excerpt_length'));
}, 20);

/** "Category: Design" becomes "Design" when the prefix is switched off. */
add_filter('get_the_archive_title_prefix', static function ($prefix) {
    return stackless_theme_option('stackless_archive_prefix') ? $prefix : '';
});

/** Archive layout classes on the post list. */
function stackless_theme_post_list_class(): string
{
    $layout = stackless_theme_option('stackless_archive_layout');
    if ($layout === 'list') {
        return 'post-list';
    }
    return 'post-list post-list--grid post-list--' . sanitize_html_class($layout);
}

/** The date a single post shows: '' when hidden, else the published or last updated date as <time>. */
function stackless_theme_single_date(): string
{
    $mode = stackless_theme_option('stackless_single_date');
    if ($mode === 'hidden') {
        return '';
    }
    if ($mode === 'updated' && get_the_modified_date('U') > get_the_date('U')) {
        return sprintf(
            '<time datetime="%1$s">%2$s</time>',
            esc_attr(get_the_modified_date(DATE_W3C)),
            /* translators: %s: date the post was last updated */
            esc_html(sprintf(__('Updated %s', 'stackless-theme'), get_the_modified_date()))
        );
    }
    return sprintf('<time datetime="%1$s">%2$s</time>', esc_attr(get_the_date(DATE_W3C)), esc_html(get_the_date()));
}

/** Related items below a custom post type single, when its count is above 0. */
add_action('stackless_theme_after_singular', 'stackless_theme_cpt_related');
function stackless_theme_cpt_related(): void
{
    if (!stackless_theme_is_custom_single()) {
        return;
    }
    $type = (string) get_post_type();
    $count = min(STACKLESS_THEME_RELATED_MAX, stackless_theme_cpt_option($type, 'related'));
    if ($count < 1) {
        return;
    }
    $object = get_post_type_object($type);
    stackless_theme_template_part('template-parts/related', 'posts', [
        'post_type' => $type,
        'count' => $count,
        /* translators: %s: post type name, e.g. "Case studies" */
        'title' => sprintf(__('More %s', 'stackless-theme'), $object ? strtolower($object->labels->name) : ''),
    ]);
}

/* ------------------------------------------------------------------ Customizer */

add_action('customize_register', 'stackless_theme_content_customize_register', 12);
function stackless_theme_content_customize_register(WP_Customize_Manager $wp_customize): void
{
    $defaults = stackless_theme_option_defaults();
    $add = static function (string $id, string $section, array $control, string $sanitize) use ($wp_customize, $defaults): void {
        $wp_customize->add_setting($id, ['default' => $defaults[$id], 'sanitize_callback' => $sanitize]);
        $wp_customize->add_control($id, ['section' => $section] + $control);
    };
    $check = static function (string $id, string $section, string $label, string $description = '') use ($add): void {
        $add($id, $section, array_filter(['label' => $label, 'description' => $description, 'type' => 'checkbox']), 'rest_sanitize_boolean');
    };

    /* Blog and archives (the section is registered in customizer.php). */
    $blog = 'stackless_theme';
    $add('stackless_archive_layout', $blog, [
        'label' => __('Post list layout', 'stackless-theme'),
        'type' => 'radio',
        'choices' => [
            'list' => __('List: image beside the text', 'stackless-theme'),
            'grid-2' => __('Grid: two columns', 'stackless-theme'),
            'grid-3' => __('Grid: three columns', 'stackless-theme'),
        ],
        'description' => __('Grids drop to fewer columns on smaller screens and beside the sidebar.', 'stackless-theme'),
    ], 'stackless_theme_sanitize_archive_layout');
    $check('stackless_archive_image', $blog, __('Featured images', 'stackless-theme'));
    $check('stackless_archive_meta', $blog, __('Date and author', 'stackless-theme'));
    $check('stackless_archive_excerpt', $blog, __('Excerpts', 'stackless-theme'));
    $add('stackless_excerpt_length', $blog, [
        'label' => __('Excerpt length (words)', 'stackless-theme'),
        'description' => __('For excerpts made from the post text. A handwritten excerpt shows in full. Default 28.', 'stackless-theme'),
        'type' => 'number',
        'input_attrs' => ['min' => 10, 'max' => 80, 'step' => 1],
    ], 'stackless_theme_sanitize_excerpt_length');
    $check('stackless_archive_read_more', $blog, __('"Read more" link', 'stackless-theme'));
    $check('stackless_archive_prefix', $blog, __('Archive type before the title', 'stackless-theme'), __('"Category: Design" instead of "Design".', 'stackless-theme'));
    $check('stackless_archive_description', $blog, __('Category and tag descriptions', 'stackless-theme'), __('Shown under the archive title when the term has one.', 'stackless-theme'));

    /* Single posts */
    $single = 'stackless_theme_single';
    $add('stackless_single_layout', $single, [
        'label' => __('Layout', 'stackless-theme'),
        'type' => 'radio',
        'choices' => [
            'centered' => __('Centered, no sidebar', 'stackless-theme'),
            'sidebar-right' => __('Sidebar on the right', 'stackless-theme'),
            'sidebar-left' => __('Sidebar on the left', 'stackless-theme'),
        ],
        'description' => __('The sidebar shows the Single post widget area, or the Blog one while that is empty. It moves below the post on phones.', 'stackless-theme'),
    ], 'stackless_theme_sanitize_single_layout');
    $wp_customize->add_setting('stackless_single_sidebar_sticky', ['default' => true, 'sanitize_callback' => 'rest_sanitize_boolean']);
    $wp_customize->add_control('stackless_single_sidebar_sticky', [
        'label' => __('Keep the sidebar in view while scrolling', 'stackless-theme'),
        'description' => __('A sidebar taller than the screen scrolls normally.', 'stackless-theme'),
        'section' => $single,
        'type' => 'checkbox',
        'active_callback' => static function (): bool {
            return stackless_theme_single_layout() !== 'centered' || stackless_theme_option('stackless_single_layout') !== 'centered';
        },
    ]);
    $add('stackless_single_header', $single, [
        'label' => __('Header style', 'stackless-theme'),
        'type' => 'radio',
        'choices' => [
            'center' => __('Centered title', 'stackless-theme'),
            'left' => __('Left-aligned title', 'stackless-theme'),
            'image-first' => __('Featured image above the title', 'stackless-theme'),
            'image-full' => __('Full-width featured image above the post and sidebar', 'stackless-theme'),
        ],
        'description' => __('With a sidebar the title is always left-aligned.', 'stackless-theme'),
    ], 'stackless_theme_sanitize_single_header');
    $providers = array_map(static function (array $provider): string {
        return $provider[0];
    }, stackless_theme_breadcrumb_providers());
    $check(
        'stackless_single_breadcrumbs',
        $single,
        __('Breadcrumbs', 'stackless-theme'),
        $providers
            /* translators: %s: SEO plugin name, e.g. "Rank Math" */
            ? sprintf(__('Uses the breadcrumb trail from %s, with its own settings and schema. Also shown on custom post type singles.', 'stackless-theme'), reset($providers))
            : __('Home / Category / Post title. With Yoast SEO, Rank Math, SEOPress, All in One SEO, The SEO Framework, Slim SEO or Breadcrumb NavXT active, their trail is used instead. Also shown on custom post type singles.', 'stackless-theme')
    );
    $add('stackless_single_category_position', $single, [
        'label' => __('Category', 'stackless-theme'),
        'type' => 'select',
        'choices' => [
            'above' => __('Above the title, as a label', 'stackless-theme'),
            'byline' => __('In the byline, with the author and date', 'stackless-theme'),
            'below' => __('After the post, with the tags', 'stackless-theme'),
            'hidden' => __('Hidden', 'stackless-theme'),
        ],
    ], 'stackless_theme_sanitize_category_position');
    $add('stackless_single_byline_position', $single, [
        'label' => __('Byline (author, date and reading time)', 'stackless-theme'),
        'type' => 'select',
        'choices' => [
            'below' => __('Below the title', 'stackless-theme'),
            'above' => __('Above the title', 'stackless-theme'),
            'end' => __('After the post', 'stackless-theme'),
        ],
        'description' => __('Pick "In the byline" for the category to keep the two together, or place them apart.', 'stackless-theme'),
    ], 'stackless_theme_sanitize_byline_position');
    $check('stackless_single_standfirst', $single, __('Excerpt under the title', 'stackless-theme'), __('Only for posts with a handwritten excerpt.', 'stackless-theme'));
    $check('stackless_single_author', $single, __('Author photo and name', 'stackless-theme'));
    $add('stackless_single_date', $single, [
        'label' => __('Date', 'stackless-theme'),
        'type' => 'select',
        'choices' => [
            'published' => __('Date published', 'stackless-theme'),
            'updated' => __('Date last updated', 'stackless-theme'),
            'hidden' => __('No date', 'stackless-theme'),
        ],
        'description' => __('"Date last updated" shows the published date until the post is edited.', 'stackless-theme'),
    ], 'stackless_theme_sanitize_single_date');
    $check('stackless_single_reading_time', $single, __('Reading time', 'stackless-theme'));
    $add('stackless_single_image', $single, [
        'label' => __('Featured image', 'stackless-theme'),
        'type' => 'select',
        'choices' => [
            'wide' => __('Wide, past the text column', 'stackless-theme'),
            'content' => __('Text column width', 'stackless-theme'),
            'hidden' => __('Hidden', 'stackless-theme'),
        ],
    ], 'stackless_theme_sanitize_single_image');
    $check('stackless_single_tags', $single, __('Tags', 'stackless-theme'));
    $check('stackless_single_author_box', $single, __('Author box', 'stackless-theme'), __('Photo, name and the biographical info from the user profile.', 'stackless-theme'));
    $check('stackless_single_post_nav', $single, __('Previous and next post links', 'stackless-theme'));
    $add('stackless_related_title', $single, [
        'label' => __('Related posts heading', 'stackless-theme'),
        'type' => 'text',
        'input_attrs' => ['placeholder' => __('More from the journal', 'stackless-theme')],
        'priority' => 210,
    ], 'sanitize_text_field');

    /* Custom post types: a group of options per type. */
    $types = stackless_theme_custom_post_types();
    $wp_customize->add_section('stackless_theme_cpt', [
        'title' => __('Custom post types', 'stackless-theme'),
        'description' => $types
            ? __('Single pages of custom post types. Their archives follow Blog and archives. Store products have options of their own.', 'stackless-theme')
            : __('No custom post types are registered. Post types added by a plugin, for example portfolio items or case studies, get options here.', 'stackless-theme'),
        'panel' => STACKLESS_THEME_PANEL,
        'priority' => 65,
    ]);
    $cpt_defaults = stackless_theme_cpt_option_defaults();
    foreach ($types as $type => $object) {
        $name = $object->labels->name;
        $prefix = 'stackless_cpt_' . $type . '_';
        $labels = [
            /* translators: %s: post type name */
            'image' => sprintf(__('%s: featured image', 'stackless-theme'), $name),
            /* translators: %s: post type name */
            'meta' => sprintf(__('%s: date and author', 'stackless-theme'), $name),
            /* translators: %s: post type name */
            'nav' => sprintf(__('%s: previous and next links', 'stackless-theme'), $name),
        ];
        foreach ($labels as $key => $label) {
            $wp_customize->add_setting($prefix . $key, ['default' => $cpt_defaults[$key], 'sanitize_callback' => 'rest_sanitize_boolean']);
            $wp_customize->add_control($prefix . $key, ['label' => $label, 'section' => 'stackless_theme_cpt', 'type' => 'checkbox']);
        }
        $wp_customize->add_setting($prefix . 'related', ['default' => 0, 'sanitize_callback' => 'stackless_theme_sanitize_related_count']);
        $wp_customize->add_control($prefix . 'related', [
            /* translators: %s: post type name */
            'label' => sprintf(__('%s: related items', 'stackless-theme'), $name),
            'description' => __('Shown below each single, sharing a category or term first. 0 hides them.', 'stackless-theme'),
            'section' => 'stackless_theme_cpt',
            'type' => 'number',
            'input_attrs' => ['min' => 0, 'max' => STACKLESS_THEME_RELATED_MAX, 'step' => 1],
        ]);
    }
}
