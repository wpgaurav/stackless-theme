<?php
/**
 * Query Loop block: a custom WP_Query rendered as one of six layouts (default, blocks, cards,
 * list, teasers, docs). The wrapper and item class names follow Marketers Delight's loop
 * templates; the styles are the block's own (assets/css/blocks/query-loop.css).
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

function stackless_theme_blocks_render_query_loop(array $attributes, string $content = ''): string
{
    // Logged-in renders skip the shared cache: the HTML has no viewer in its key.
    $cache_key = '';
    if (!is_admin() && !is_user_logged_in()) {
        $cache_key = 'ql_' . (is_singular() ? get_the_ID() : 0) . '_' . substr(md5((string) wp_json_encode($attributes)), 0, 12) . '_v' . stackless_theme_blocks_query_cache_version();
        $cached = wp_cache_get($cache_key, 'stackless_query_loop');
        if ($cached !== false) {
            return (string) $cached;
        }
    }

    $post_type = !empty($attributes['postType']) ? (string) $attributes['postType'] : 'post';
    $order = !empty($attributes['order']) ? $attributes['order'] : 'DESC';
    $args = [
        'post_type' => sanitize_key($post_type),
        'posts_per_page' => isset($attributes['postsPerPage']) ? (int) $attributes['postsPerPage'] : 6,
        'orderby' => sanitize_key(!empty($attributes['orderBy']) ? $attributes['orderBy'] : 'date'),
        'order' => $order === 'ASC' ? 'ASC' : 'DESC',
        'offset' => isset($attributes['offset']) ? (int) $attributes['offset'] : 0,
        'ignore_sticky_posts' => true,
    ];

    if ((!isset($attributes['excludeCurrent']) || $attributes['excludeCurrent']) && is_singular()) {
        $args['post__not_in'] = [get_the_ID()];
    }

    // Categories and tags accept IDs or slugs.
    foreach (['categories' => ['category', 'category__in'], 'tags' => ['post_tag', 'tag__in']] as $key => $map) {
        if (!empty($attributes[$key])) {
            $ids = stackless_theme_blocks_query_term_ids(explode(',', (string) $attributes[$key]), $map[0]);
            if ($ids) {
                $args[$map[1]] = $ids;
            }
        }
    }

    // A custom taxonomy, and an optional second one joined with OR.
    foreach ([['taxonomy', 'terms'], ['secondTaxonomy', 'secondTerms']] as $index => $keys) {
        if (empty($attributes[$keys[0]]) || empty($attributes[$keys[1]])) {
            continue;
        }
        $values = array_map('trim', explode(',', (string) $attributes[$keys[1]]));
        $slugs = (bool) array_filter($values, static function ($value): bool {
            return !is_numeric($value);
        });
        if ($index === 1) {
            $args['tax_query'] = $args['tax_query'] ?? [];
            $args['tax_query']['relation'] = 'OR';
        }
        $args['tax_query'][] = [
            'taxonomy' => sanitize_key($attributes[$keys[0]]),
            'field' => $slugs ? 'slug' : 'term_id',
            'terms' => $slugs ? array_map('sanitize_title', $values) : array_map('intval', $values),
        ];
    }

    // Included and excluded posts accept IDs or slugs.
    if (!empty($attributes['postIn'])) {
        $ids = stackless_theme_blocks_query_post_ids((string) $attributes['postIn'], $post_type);
        if ($ids) {
            $args['post__in'] = $ids;
            $args['orderby'] = 'post__in';
        }
    }
    if (!empty($attributes['postNotIn'])) {
        $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], stackless_theme_blocks_query_post_ids((string) $attributes['postNotIn'], $post_type));
    }

    // Author accepts an ID, a login or a nicename.
    if (!empty($attributes['author'])) {
        $author = trim((string) $attributes['author']);
        if (is_numeric($author)) {
            $args['author'] = (int) $author;
        } else {
            $user = get_user_by('login', $author) ?: get_user_by('slug', $author);
            if ($user) {
                $args['author'] = $user->ID;
            }
        }
    }

    if (!empty($attributes['secondPostType'])) {
        $args['post_type'] = array_unique(array_filter(array_map('sanitize_key', array_map('trim', explode(',', $post_type . ',' . $attributes['secondPostType'])))));
    }

    $args = stackless_theme_blocks_query_guard($args, $attributes);
    $query = $args ? new WP_Query($args) : null;

    if (!$query || !$query->have_posts()) {
        $text = !empty($attributes['noResultsText']) ? $attributes['noResultsText'] : __('No posts found.', 'stackless-theme');
        return '<p class="text-sec text-center">' . esc_html($text) . '</p>';
    }

    $excerpt_length = isset($attributes['excerptLength']) ? (int) $attributes['excerptLength'] : 0;
    $output = stackless_theme_blocks_query_loop_html($query, $attributes, $excerpt_length);
    wp_reset_postdata();

    if ($cache_key !== '') {
        wp_cache_set($cache_key, $output, 'stackless_query_loop', HOUR_IN_SECONDS);
    }
    return $output;
}

/**
 * Constrains the query to what a visitor may see. The block renderer endpoint renders this block
 * for any user who can edit posts, with whatever attributes they send.
 */
function stackless_theme_blocks_query_guard(array $args, array $attributes): ?array
{
    $types = array_values(array_filter((array) $args['post_type'], static function ($type): bool {
        return is_string($type) && $type !== '' && is_post_type_viewable($type);
    }));
    if (!$types) {
        return null;
    }
    $args['post_type'] = count($types) === 1 ? $types[0] : $types;
    $args['post_status'] = 'publish';

    /** Filters the most posts one Query Loop block may render. */
    $max = max(1, (int) apply_filters('stackless_theme_blocks_query_loop_max_posts', 50, $attributes));
    $per_page = (int) $args['posts_per_page'];
    $args['posts_per_page'] = $per_page < 1 || $per_page > $max ? $max : $per_page;
    $args['offset'] = absint($args['offset'] ?? 0);
    return $args;
}

/** Term IDs from a mixed list of IDs and slugs. */
function stackless_theme_blocks_query_term_ids(array $values, string $taxonomy): array
{
    $ids = [];
    foreach (array_map('trim', $values) as $value) {
        if (is_numeric($value)) {
            $ids[] = (int) $value;
        } elseif ($value !== '') {
            $term = get_term_by('slug', sanitize_title($value), $taxonomy);
            if ($term) {
                $ids[] = (int) $term->term_id;
            }
        }
    }
    return $ids;
}

/** Post IDs from a comma-separated list of IDs and slugs. */
function stackless_theme_blocks_query_post_ids(string $input, string $post_type = 'post'): array
{
    $ids = [];
    foreach (array_map('trim', explode(',', $input)) as $value) {
        if (is_numeric($value)) {
            $ids[] = (int) $value;
        } elseif ($value !== '') {
            $found = get_page_by_path(sanitize_title($value), OBJECT, $post_type);
            if ($found) {
                $ids[] = (int) $found->ID;
            }
        }
    }
    return $ids;
}

function stackless_theme_blocks_query_cache_version(): string
{
    $version = wp_cache_get('version', 'stackless_query_loop');
    if ($version === false) {
        $version = '1';
        wp_cache_set('version', $version, 'stackless_query_loop');
    }
    return (string) $version;
}

/** Saving any post makes every cached loop stale. */
add_action('save_post', 'stackless_theme_blocks_query_cache_bust');
function stackless_theme_blocks_query_cache_bust($post_id): void
{
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    wp_cache_set('version', (string) ((int) wp_cache_get('version', 'stackless_query_loop') + 1), 'stackless_query_loop');
}

/** The loop's markup for one of the six layouts. */
function stackless_theme_blocks_query_loop_html(WP_Query $query, array $attributes, int $excerpt_length = 0): string
{
    $layouts = [
        // layout => [wrapper, item, image, content, title, default image size, columns?]
        'default' => ['loop-default', 'loop-default-item mb-single pb-single has-border-bottom', 'featured-image loop-default-image mb-half', 'loop-default-inner', 'med-title lh-tight loop-default-title mb-half', 'large', false],
        'blocks' => ['loop-blocks', 'loop-block', 'featured-image block-image mb-none', 'loop-content block-half', 'med-title lh-tight', 'medium_large', false],
        'cards' => ['loop-cards', 'card-item col', 'card-image featured-image', 'block-single', 'small-title lh-tight card-title mb-half', 'medium_large', true],
        'list' => ['loop-list', 'list-item mb-single', 'col list-image', 'list-inner columns-80-20 columns-single', 'small-title lh-tight list-title', 'thumbnail', false],
        'teasers' => ['loop-teasers', 'teaser-item col', 'teaser-image featured-image mb-half', 'teaser-content block-half-tb', 'headline teaser-title small-title lh-tight mb-half', 'medium_large', true],
        'docs' => ['loop-docs', 'docs-item list-item mb-single', 'col list-image', 'list-inner columns-80-20 columns-single', 'small-title list-title', 'thumbnail', false],
    ];
    $layout = !empty($attributes['layout']) && isset($layouts[$attributes['layout']]) ? (string) $attributes['layout'] : 'cards';
    [$wrap_class, $item_class, $image_class, $content_class, $title_class, $default_size, $has_columns] = $layouts[$layout];

    $on = static function (string $key) use ($attributes): bool {
        return !isset($attributes[$key]) || $attributes[$key];
    };
    $extra = static function (string $key) use ($attributes): string {
        $classes = stackless_theme_blocks_classes($attributes[$key] ?? '');
        return $classes ? ' ' . implode(' ', $classes) : '';
    };

    $columns = max(1, min(6, isset($attributes['columns']) ? (int) $attributes['columns'] : 2));
    $featured = isset($attributes['featuredCount']) ? (int) $attributes['featuredCount'] : 0;
    $show_byline = $on('showByline');
    $read_more = !empty($attributes['readMoreText']) ? (string) $attributes['readMoreText'] : '';
    $title_tag = !empty($attributes['titleTag']) ? strtolower((string) $attributes['titleTag']) : 'h2';
    if (!in_array($title_tag, ['h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div'], true)) {
        $title_tag = 'h2';
    }
    $is_row = in_array($layout, ['list', 'docs'], true);

    $out = '<div class="' . esc_attr($wrap_class . ($has_columns ? ' columns-' . $columns . ' columns-single' : '')) . $extra('classLoop') . '">';
    $count = 0;
    while ($query->have_posts()) {
        $query->the_post();
        $is_featured = $layout === 'teasers' && $columns > 1 && $count < $featured;
        $size = !empty($attributes['imageSize']) ? (string) $attributes['imageSize'] : ($is_featured ? 'large' : $default_size);
        // Marketers Delight's own image sizes, when the site does not register them.
        if (($size === 'md-block' || $size === 'md-banner') && !has_image_size($size)) {
            $size = $size === 'md-block' ? 'medium_large' : 'large';
        }
        $link = esc_url((string) get_permalink());

        $excerpt = '';
        if ($on('showExcerpt')) {
            $source = has_excerpt() ? get_the_excerpt() : strip_shortcodes(excerpt_remove_blocks((string) get_post()->post_content));
            $excerpt = wp_trim_words(wp_strip_all_tags($source), $excerpt_length > 0 ? $excerpt_length : ($is_featured ? 28 : 20), '&hellip;');
        }

        $image = '';
        if ($on('showImage') && has_post_thumbnail()) {
            $image = '<a href="' . $link . '" class="' . esc_attr($is_row ? 'featured-image' : $image_class) . ($is_row ? '' : $extra('classImage')) . '" tabindex="-1" aria-hidden="true">' . get_the_post_thumbnail(null, $size) . '</a>';
            if ($is_row) {
                $image = '<div class="' . esc_attr($image_class) . $extra('classImage') . '">' . $image . '</div>';
            }
        }

        $category = '';
        if ($show_byline && $on('showCategory')) {
            $terms = get_the_category();
            if ($terms) {
                $category = '<a class="byline-item byline-category" href="' . esc_url((string) get_category_link($terms[0])) . '">' . esc_html($terms[0]->name) . '</a>';
            }
        }
        $author = '';
        if ($show_byline && $on('showAuthor')) {
            $author = '<span class="byline-item byline-author">' . ($on('showAvatar') ? get_avatar((int) get_the_author_meta('ID'), 28, '', '', ['class' => 'byline-avatar']) : '') . esc_html((string) get_the_author()) . '</span>';
        }
        $date = '';
        if ($show_byline && $on('showDate')) {
            $date = '<time class="byline-item byline-date" datetime="' . esc_attr((string) get_the_date('c')) . '">' . esc_html((string) get_the_date()) . '</time>';
        }
        $byline = static function (string $items, string $class = '') use ($extra): string {
            return $items === '' ? '' : '<div class="' . esc_attr(trim('byline ' . $class)) . $extra('classByline') . '">' . $items . '</div>';
        };

        $title = '<' . $title_tag . ' class="' . esc_attr($is_featured ? str_replace('small-title', 'med-title', $title_class) : $title_class) . $extra('classTitle') . '"><a href="' . $link . '">' . get_the_title() . '</a></' . $title_tag . '>';
        $text = $excerpt !== '' ? '<p class="text-sec ' . esc_attr($layout === 'cards' ? 'card-excerpt mb-single' : ($layout === 'teasers' ? 'teaser-excerpt' : 'loop-excerpt mb-half')) . $extra('classExcerpt') . '">' . wp_kses_post($excerpt) . '</p>' : '';
        $more = $read_more !== '' ? '<p class="' . esc_attr($is_row ? 'mt-small' : 'mb-half') . $extra('classReadMore') . '"><a href="' . $link . '" class="read-more">' . esc_html($read_more) . ' &rarr;</a></p>' : '';

        $item = $is_featured ? 'teaser-item col-span-full' : $item_class;
        $out .= '<article class="' . esc_attr(implode(' ', get_post_class($item))) . $extra('classItem') . '">';
        if ($layout === 'cards') {
            $out .= '<div class="card-inner white has-border border-radius">' . $image . '<div class="' . esc_attr($content_class) . $extra('classContent') . '">'
                . $byline($category, 'mb-half') . $title . $text . $more . $byline($author . $date) . '</div></div>';
        } elseif ($is_row) {
            $out .= '<div class="' . esc_attr($content_class) . $extra('classContent') . '"><div class="col">'
                . ($layout === 'list' ? $byline($category . $date, 'mb-small') : '') . $title . $text . $more
                . ($layout === 'docs' ? $byline($category . $author . $date) : '') . '</div>' . $image . '</div>';
        } elseif ($layout === 'default') {
            $out .= '<div class="' . esc_attr($content_class) . $extra('classContent') . '">' . $image . $byline($category, 'mb-half') . $title . $text . $more . $byline($author . $date, 'text-small') . '</div>';
        } else {
            $out .= $image . '<div class="' . esc_attr($content_class) . $extra('classContent') . '">' . $title . $text . $more
                . $byline($layout === 'teasers' ? $category . $author . $date : $author . $date . $category, $layout === 'teasers' ? 'mt-half text-small' : 'text-small') . '</div>';
        }
        $out .= '</article>';
        $count++;
    }
    return $out . '</div>';
}
