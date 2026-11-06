<?php
/**
 * Marketers Delight importer. Reads what MD left in this site's database (or an MD settings
 * export), shows what maps to Stackless, then applies the parts you keep: design, header and
 * footer code, menus, widgets, page templates and per-page layouts. A backup is saved first, MD
 * data is never changed, and running it again is safe.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/** MD page templates and the Stackless or Page Blocks Builder template each one becomes. */
function stackless_theme_md_template_map(): array
{
    $pbb = defined('GT_PB_BUILDER_DIR') && !wp_is_block_theme() ? 'page-blocks-builder.php' : STACKLESS_THEME_TEMPLATE_FULL;
    return [
        'pbb-template.php' => $pbb,
        'blank-template.php' => STACKLESS_THEME_TEMPLATE_FULL,
        'builder.php' => STACKLESS_THEME_TEMPLATE_FULL,
        'premium-builder.php' => STACKLESS_THEME_TEMPLATE_FULL,
    ];
}

/** Import groups: key => label. */
function stackless_theme_md_groups(): array
{
    return [
        'design' => __('Design: logo, colors, widths, fonts, page transitions', 'stackless-theme'),
        'code' => __('Header and footer code', 'stackless-theme'),
        'menus' => __('Menu locations', 'stackless-theme'),
        'widgets' => __('Widgets and footer text', 'stackless-theme'),
        'templates' => __('Page templates', 'stackless-theme'),
        'layout' => __('Per-page header, footer and title settings', 'stackless-theme'),
    ];
}

/** A value deep inside an MD settings array, or $default. */
function stackless_theme_md_get(array $data, array $path, $default = null)
{
    foreach ($path as $key) {
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return $default;
        }
        $data = $data[$key];
    }
    return $data;
}

/** The marketers_delight settings from this site ('db') or from an uploaded export ('file'). */
function stackless_theme_md_settings(string $source): array
{
    $settings = $source === 'file'
        ? get_transient('stackless_md_file_' . get_current_user_id())
        : get_option('marketers_delight', []);
    return is_array($settings) ? $settings : [];
}

/** Whether this database still holds Marketers Delight settings. */
function stackless_theme_md_has_data(): bool
{
    $settings = get_option('marketers_delight', []);
    return is_array($settings) && $settings !== [];
}

/**
 * MD's theme mods (menu locations and the widget snapshot WordPress saves on a theme switch).
 * Found by theme name when MD is installed, else by MD's own menu locations.
 */
function stackless_theme_md_theme_mods(): array
{
    static $mods = null;
    if ($mods !== null) {
        return $mods;
    }
    global $wpdb;
    $mods = [];
    $current = 'theme_mods_' . get_stylesheet();
    $rows = $wpdb->get_results($wpdb->prepare("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like('theme_mods_') . '%')); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $fallback = [];
    foreach ((array) $rows as $row) {
        if ($row->option_name === $current) {
            continue;
        }
        $value = maybe_unserialize($row->option_value);
        if (!is_array($value)) {
            continue;
        }
        $slug = substr($row->option_name, strlen('theme_mods_'));
        $theme = wp_get_theme($slug);
        $is_md = stripos($slug, 'marketers-delight') !== false
            || ($theme->exists() && (stripos((string) $theme->get('Name'), 'Marketers Delight') !== false || stripos($theme->get_template(), 'marketers-delight') !== false));
        if ($is_md) {
            $mods = $value;
            return $mods;
        }
        if (!$fallback && array_intersect(array_keys((array) ($value['nav_menu_locations'] ?? [])), ['main', 'header_loggedin', 'social'])) {
            $fallback = $value;
        }
    }
    $mods = $fallback;
    return $mods;
}

/** MD's widget areas: the snapshot from the theme switch, else the live assignment if MD's areas are still there. */
function stackless_theme_md_sidebars(): array
{
    $snapshot = stackless_theme_md_get(stackless_theme_md_theme_mods(), ['sidebars_widgets', 'data'], []);
    if (is_array($snapshot) && $snapshot) {
        return $snapshot;
    }
    $live = get_option('sidebars_widgets', []);
    return is_array($live) && isset($live['sidebar-main']) ? $live : [];
}

/** Whether a widget instance ID (e.g. text-3) still has its settings. */
function stackless_theme_md_widget_exists(string $widget_id): bool
{
    if (!preg_match('/^(.+)-(\d+)$/', $widget_id, $m)) {
        return false;
    }
    $instances = get_option('widget_' . $m[1], []);
    return is_array($instances) && isset($instances[(int) $m[2]]);
}

/** Text of a simple widget (text, custom HTML, paragraph block) for the footer line, or null. */
function stackless_theme_md_widget_text(string $widget_id): ?string
{
    if (!preg_match('/^(text|custom_html|block)-(\d+)$/', $widget_id, $m)) {
        return null;
    }
    $instance = (array) (get_option('widget_' . $m[1], [])[(int) $m[2]] ?? []);
    $text = (string) ($instance['text'] ?? $instance['content'] ?? '');
    if ($m[1] === 'block') {
        $text = (string) preg_replace('/<!--.*?-->/s', '', $text);
    }
    // Images, forms, lists and scripts are more than a footer line: those widgets move instead.
    if (preg_match('/<(?:img|form|iframe|script|ul|ol|table|svg)\b/i', $text)) {
        return null;
    }
    return trim($text);
}

/** Post IDs that carry MD per-post settings (the marketers_delight meta key). */
function stackless_theme_md_meta_post_ids(): array
{
    global $wpdb;
    return array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", 'marketers_delight'))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

/** Per-post MD layout flags mapped to Stackless meta keys. */
function stackless_theme_md_layout_flags(array $meta): array
{
    $layout = (array) ($meta['layout'] ?? []);
    $flags = [];
    if (!empty($layout['header']['remove'])) {
        $flags['_stackless_hide_header'] = true;
    }
    if (!empty($layout['footer']['remove'])) {
        $flags['_stackless_hide_footer'] = true;
    }
    if (!empty($layout['content']['headline'])) {
        $flags['_stackless_hide_title'] = true;
    }
    if (empty($layout['header']['remove']) && (!empty($layout['transparent_header']) || !empty($layout['pbb_header']['transparent']))) {
        $flags['_stackless_transparent_header'] = true;
    }
    return $flags;
}

/** Normalizes a color for comparison. */
function stackless_theme_md_color($value): string
{
    return strtolower((string) sanitize_hex_color(is_string($value) ? trim($value) : ''));
}

/**
 * Builds the import plan: what each piece of MD data becomes, plus the data needed to apply it.
 *
 * @return array{items: array, mods: array, menus: array, widgets: array, templates: array, layout: array, counts: array}
 */
function stackless_theme_md_plan(string $source): array
{
    $md = stackless_theme_md_settings($source);
    $plan = ['items' => [], 'mods' => [], 'menus' => [], 'widgets' => [], 'templates' => [], 'layout' => [], 'counts' => []];
    $item = static function (string $group, string $label, string $from, string $to, string $status, string $note = '') use (&$plan): void {
        $plan['items'][] = compact('group', 'label', 'from', 'to', 'status', 'note');
    };
    $mod = static function (string $group, string $key, $value) use (&$plan): void {
        $plan['mods'][$key] = ['group' => $group, 'value' => $value];
    };

    /* Design ------------------------------------------------------------------ */
    $logo_id = (int) stackless_theme_md_get($md, ['header', 'logo', 'id'], 0);
    $logo_url = (string) stackless_theme_md_get($md, ['header', 'logo', 'url'], '');
    if ($logo_id || $logo_url) {
        $here = $logo_id && wp_attachment_is_image($logo_id) && ($source === 'db' || $logo_url === '' || wp_get_attachment_url($logo_id) === $logo_url);
        if ($here) {
            $mod('design', 'custom_logo', $logo_id);
            $item('design', __('Logo', 'stackless-theme'), basename((string) wp_get_attachment_url($logo_id)), __('Site logo', 'stackless-theme'), 'map');
        } else {
            $item('design', __('Logo', 'stackless-theme'), $logo_url !== '' ? basename($logo_url) : '#' . $logo_id, __('Site logo', 'stackless-theme'), 'skip', __('The image is not in this media library. Upload it under Customize > Site Identity.', 'stackless-theme'));
        }
    }
    $logo_width = absint(stackless_theme_md_get($md, ['header', 'logo_width', 'desktop'], 0));
    if ($logo_width > 0) {
        $mod('design', 'stackless_logo_width', stackless_theme_sanitize_logo_width($logo_width));
        $item('design', __('Logo width', 'stackless-theme'), $logo_width . 'px', __('Logo width', 'stackless-theme'), 'map');
    }

    $md_defaults = ['primary' => '#ae2525', 'links' => '#ae2525', 'text' => '#1e1e1e', 'headline' => '#1e1e1e', 'bg_color' => '#f0f0f0', 'content_bg' => '#ffffff'];
    $site = (array) stackless_theme_md_get($md, ['colors', 'site'], []);
    $colors = [
        'primary' => [stackless_theme_md_color($site['links'] ?? '') ?: stackless_theme_md_color($site['primary'] ?? ''), 'links', __('Links / primary', 'stackless-theme')],
        'text' => [stackless_theme_md_color($site['text'] ?? ''), 'text', __('Text', 'stackless-theme')],
        'heading' => [stackless_theme_md_color($site['headline'] ?? ''), 'headline', __('Headlines', 'stackless-theme')],
        'bg' => [stackless_theme_md_color($site['bg_color'] ?? ''), 'bg_color', __('Site background', 'stackless-theme')],
        'surface' => [stackless_theme_md_color(stackless_theme_md_get($md, ['colors', 'content', 'bg_color'], '')), 'content_bg', __('Content background', 'stackless-theme')],
    ];
    foreach ($colors as $key => [$value, $md_key, $label]) {
        if ($value === '') {
            continue;
        }
        if ($value === $md_defaults[$md_key]) {
            $item('design', $label, $value, stackless_theme_design_colors()[$key][0], 'skip', __('MD default; Stackless keeps its own.', 'stackless-theme'));
            continue;
        }
        $mod('design', 'stackless_color_' . $key, $value);
        $item('design', $label, $value, stackless_theme_design_colors()[$key][0], 'map');
    }

    foreach (['content' => ['content', __('Reading width', 'stackless-theme')], 'wide' => ['site', __('Wide width', 'stackless-theme')]] as $key => [$md_key, $label]) {
        $width = absint(stackless_theme_md_get($md, ['content', 'width', $md_key], 0));
        if ($width < 1) {
            continue;
        }
        $clamped = stackless_theme_clamp_width($key, $width);
        $mod('design', 'stackless_width_' . $key, $clamped);
        $item('design', $label, $width . 'px', $clamped . 'px', $clamped === $width ? 'map' : 'partial', $clamped === $width ? '' : __('Kept inside the range Stackless supports.', 'stackless-theme'));
    }

    $md_system = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", "SF Pro Text", sans-serif';
    foreach (['body' => ['body', __('Body font', 'stackless-theme')], 'heading' => ['h1', __('Heading font', 'stackless-theme')]] as $key => [$group, $label]) {
        $google = trim((string) stackless_theme_md_get($md, ['typography', $group, 'google_family'], ''));
        $family = trim((string) stackless_theme_md_get($md, ['typography', $group, 'font_family'], ''));
        $fallbacks = trim((string) stackless_theme_md_get($md, ['typography', $group, 'font_fallbacks'], ''));
        if ($google !== '') {
            $stack = stackless_theme_sanitize_font_stack('"' . $google . '", ' . ($fallbacks !== '' ? $fallbacks : 'system-ui, sans-serif'));
            $mod('design', 'stackless_font_' . $key, 'custom');
            $mod('design', 'stackless_font_' . $key . '_custom', $stack);
            $item('design', $label, $google . ' (Google)', $stack, 'partial', __('Stackless does not load Google Fonts. Load this font with a font plugin, or it falls back to the system font.', 'stackless-theme'));
        } elseif ($family !== '' && $family !== $md_system && stripos($family, 'system-ui') !== 0) {
            $stack = stackless_theme_sanitize_font_stack($family);
            $mod('design', 'stackless_font_' . $key, 'custom');
            $mod('design', 'stackless_font_' . $key . '_custom', $stack);
            $item('design', $label, $family, __('Custom stack', 'stackless-theme'), 'map');
        }
    }

    if (stackless_theme_md_get($md, ['view_transitions', 'enable'])) {
        $mod('design', 'stackless_view_transitions', true);
        $item('design', __('View transitions', 'stackless-theme'), __('On', 'stackless-theme'), __('Fade between pages', 'stackless-theme'), 'partial', __('Stackless uses one cross-fade; MD animation and easing options are not carried over.', 'stackless-theme'));
    }
    if (stackless_theme_md_get($md, ['dark_mode', 'enabled'])) {
        $item('design', __('Dark mode', 'stackless-theme'), __('On', 'stackless-theme'), '-', 'skip', __('Stackless has no dark mode yet.', 'stackless-theme'));
    }

    /* Code -------------------------------------------------------------------- */
    foreach (['header' => __('Header scripts', 'stackless-theme'), 'footer' => __('Footer scripts', 'stackless-theme')] as $where => $label) {
        $code = trim((string) stackless_theme_md_get($md, ['settings', $where . '_scripts'], ''));
        if ($code === '') {
            continue;
        }
        /* translators: %d: number of characters */
        $size = sprintf(__('%d characters', 'stackless-theme'), strlen($code));
        if (current_user_can('unfiltered_html')) {
            $mod('code', 'stackless_' . $where . '_code', $code);
            $item('code', $label, $size, $where === 'header' ? __('Code in <head>', 'stackless-theme') : __('Code before </body>', 'stackless-theme'), 'map');
        } else {
            $item('code', $label, $size, '-', 'skip', __('Your account cannot save unfiltered HTML. Ask an administrator who can to run the import.', 'stackless-theme'));
        }
    }

    // Everything below lives in this site's database, whatever the settings source.
    /* Menus ------------------------------------------------------------------- */
    $locations = array_filter(array_map('intval', (array) stackless_theme_md_get(stackless_theme_md_theme_mods(), ['nav_menu_locations'], [])), static function (int $id): bool {
        return $id > 0 && wp_get_nav_menu_object($id) !== false;
    });
    if ($locations) {
        $plan['menus']['md'] = $locations;
        $plan['menus']['defaults'] = [
            'primary' => isset($locations['main']) ? 'main' : (isset($locations['header']) ? 'header' : ''),
            'footer' => isset($locations['social']) ? 'social' : (isset($locations['main'], $locations['header']) ? 'header' : ''),
        ];
        foreach ($locations as $location => $menu_id) {
            $menu = wp_get_nav_menu_object($menu_id);
            $to = array_search($location, $plan['menus']['defaults'], true);
            $item('menus', $location, $menu ? $menu->name : '#' . $menu_id, $to ? ucfirst((string) $to) : '-', $to ? 'map' : 'skip', $to ? '' : __('Pick a location below to use it.', 'stackless-theme'));
        }
    }

    /* Widgets ------------------------------------------------------------------ */
    $sidebars = stackless_theme_md_sidebars();
    if ($sidebars) {
        $valid = static function ($ids): array {
            return array_values(array_filter(array_map('strval', (array) $ids), 'stackless_theme_md_widget_exists'));
        };
        $blog = $valid($sidebars['sidebar-main'] ?? []);
        $footer = [];
        $columns = preg_grep('/^md-footer-col-\d+$/', array_keys($sidebars));
        natsort($columns);
        foreach (array_merge($columns, ['md-footer-bottom-left', 'md-footer-bottom-right']) as $area) {
            $footer = array_merge($footer, $valid($sidebars[$area] ?? []));
        }
        $copy = $valid($sidebars['footer-copy'] ?? []);
        $texts = array_map('stackless_theme_md_widget_text', $copy);
        $footer_text = null;
        if ($copy && !in_array(null, $texts, true)) {
            $footer_text = stackless_theme_sanitize_footer_text(implode(' ', array_filter($texts)));
        } else {
            $footer = array_merge($footer, $copy);
        }
        $plan['widgets'] = ['blog' => $blog, 'footer' => $footer, 'footer_text' => $footer_text];
        if ($blog) {
            /* translators: %d: number of widgets */
            $item('widgets', __('Main sidebar', 'stackless-theme'), sprintf(_n('%d widget', '%d widgets', count($blog), 'stackless-theme'), count($blog)), __('Blog sidebar', 'stackless-theme'), 'map', __('Shown beside the post list; single posts have no sidebar.', 'stackless-theme'));
        }
        if ($footer) {
            /* translators: %d: number of widgets */
            $item('widgets', __('Footer columns', 'stackless-theme'), sprintf(_n('%d widget', '%d widgets', count($footer), 'stackless-theme'), count($footer)), __('Footer', 'stackless-theme'), 'map', __('Stackless lays them out in auto-fitting columns.', 'stackless-theme'));
        }
        if ($footer_text !== null && $footer_text !== '') {
            $mod('widgets', 'stackless_footer_text', $footer_text);
            $item('widgets', __('Footer copyright', 'stackless-theme'), wp_trim_words(wp_strip_all_tags($footer_text), 12), __('Footer text', 'stackless-theme'), 'map');
        }
        $custom = array_keys((array) stackless_theme_md_get($md, ['sidebars', 'areas'], []));
        if ($custom) {
            $item('widgets', __('Custom sidebars', 'stackless-theme'), implode(', ', $custom), '-', 'skip', __('Stackless has no per-page sidebars; their widgets stay under Inactive widgets.', 'stackless-theme'));
        }
    }

    /* Templates ---------------------------------------------------------------- */
    $map = stackless_theme_md_template_map();
    global $wpdb;
    $placeholders = implode(',', array_fill(0, count($map), '%s'));
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_wp_page_template' AND pm.meta_value IN ({$placeholders}) AND p.post_status IN ('publish','draft','pending','private','future') AND p.post_type <> 'revision'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders only.
        array_keys($map)
    )); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $by_template = [];
    foreach ((array) $rows as $row) {
        $plan['templates'][(int) $row->post_id] = $map[$row->meta_value];
        $by_template[$row->meta_value][] = (int) $row->post_id;
    }
    $labels = ['page-blocks-builder.php' => __('Page Blocks Builder', 'stackless-theme'), STACKLESS_THEME_TEMPLATE_FULL => __('Full width (no title)', 'stackless-theme')];
    foreach ($by_template as $old => $ids) {
        $titles = array_map(static function (int $id): string {
            return get_the_title($id) ?: '#' . $id;
        }, array_slice($ids, 0, 3));
        $more = count($ids) > 3 ? ' +' . (count($ids) - 3) : '';
        /* translators: 1: number of posts, 2: sample titles */
        $item('templates', $old, sprintf(_n('%1$d post: %2$s', '%1$d posts: %2$s', count($ids), 'stackless-theme'), count($ids), implode(', ', $titles) . $more), $labels[$map[$old]], 'map', $old === 'pbb-template.php' && $map[$old] !== 'page-blocks-builder.php' ? __('Activate Page Blocks Builder first to keep these on its template.', 'stackless-theme') : '');
    }

    /* Per-page layout ---------------------------------------------------------- */
    $counts = ['_stackless_hide_header' => 0, '_stackless_hide_footer' => 0, '_stackless_hide_title' => 0, '_stackless_transparent_header' => 0];
    $scripts = 0;
    $ids = stackless_theme_md_meta_post_ids();
    foreach (array_chunk($ids, 500) as $chunk) {
        update_meta_cache('post', $chunk);
    }
    foreach ($ids as $post_id) {
        $meta = get_post_meta($post_id, 'marketers_delight', true);
        if (!is_array($meta)) {
            continue;
        }
        $flags = stackless_theme_md_layout_flags($meta);
        if ($flags) {
            $plan['layout'][$post_id] = $flags;
            foreach (array_keys($flags) as $flag) {
                $counts[$flag]++;
            }
        }
        if (trim((string) stackless_theme_md_get($meta, ['scripts', 'header_scripts'], '')) !== '' || trim((string) stackless_theme_md_get($meta, ['scripts', 'footer_scripts'], '')) !== '') {
            $scripts++;
        }
    }
    $options = stackless_theme_layout_options();
    foreach ($counts as $flag => $count) {
        if ($count) {
            /* translators: %d: number of posts */
            $item('layout', $options[$flag], sprintf(_n('%d post', '%d posts', $count, 'stackless-theme'), $count), $options[$flag], 'map');
        }
    }
    if ($scripts) {
        /* translators: %d: number of posts */
        $item('report', __('Per-page scripts', 'stackless-theme'), sprintf(_n('%d post', '%d posts', $scripts, 'stackless-theme'), $scripts), '-', 'report', __('Scripts added to single pages in MD are not carried over. Move them to a code snippets plugin.', 'stackless-theme'));
    }

    stackless_theme_md_report($plan, $item);
    return $plan;
}

/** Things Stackless cannot take over: they are listed so nothing breaks unnoticed. */
function stackless_theme_md_report(array &$plan, callable $item): void
{
    global $wpdb;

    // Page Blocks: the plugin brings its own importer for MD's library.
    $table = $wpdb->prefix . 'md_page_blocks';
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table))) === $table) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name built from the prefix.
        if (defined('GT_PB_BUILDER_DIR')) {
            /* translators: %d: number of page blocks */
            $item('report', __('MD page blocks', 'stackless-theme'), sprintf(_n('%d block', '%d blocks', $rows, 'stackless-theme'), $rows), __('Page Blocks Builder', 'stackless-theme'), 'partial', __('Copy them with Page Blocks > Settings > Import from dropin. Existing page_block shortcodes and blocks keep working.', 'stackless-theme'));
            $plan['counts']['pbb_settings'] = admin_url('admin.php?page=gt_pb_settings');
        } else {
            /* translators: %d: number of page blocks */
            $item('report', __('MD page blocks', 'stackless-theme'), sprintf(_n('%d block', '%d blocks', $rows, 'stackless-theme'), $rows), '-', 'report', __('Install and activate Page Blocks Builder, then use its Import from dropin. Until then [page_block] shortcodes and page-block blocks print nothing.', 'stackless-theme'));
        }
    }

    // MD-only shortcodes and blocks left in content. One scan of the posts table.
    $tags = ['book', 'content_upgrade', 'courses_archive', 'data_table', 'doc_callout', 'fn', 'md_countdown', 'md_email', 'md_feedback', 'md_gdpr_consent', 'md_popup', 'md_progress_bar', 'md_social_proof', 'md_success_message', 'md_toc', 'md_trust_badges', 'md_two_step_button', 'md_urgency', 'testimonial', 'testimonials', 'toc'];
    $markers = [];
    foreach ($tags as $tag) {
        $markers['[' . $tag . ']'] = ['%' . $wpdb->esc_like('[' . $tag . ']') . '%', '%' . $wpdb->esc_like('[' . $tag . ' ') . '%'];
    }
    $markers[__('MD blocks', 'stackless-theme')] = ['%' . $wpdb->esc_like('<!-- wp:marketers-delight/testimonial') . '%', '%' . $wpdb->esc_like('<!-- wp:md/') . '%'];
    if (!defined('GT_PB_BUILDER_DIR')) {
        $markers['[page_block]'] = ['%' . $wpdb->esc_like('[page_block') . '%', '%' . $wpdb->esc_like('<!-- wp:marketers-delight/') . '%'];
    }
    $select = [];
    $args = [];
    foreach ($markers as $patterns) {
        $select[] = 'SUM(post_content LIKE %s OR post_content LIKE %s)';
        $args[] = $patterns[0];
        $args[] = $patterns[1];
    }
    $sql = 'SELECT ' . implode(', ', $select) . " FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','pending','private','future') AND post_type NOT IN ('revision','nav_menu_item')";
    $counts = $wpdb->get_row($wpdb->prepare($sql, $args), ARRAY_N); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared -- placeholders only.
    $found = [];
    foreach (array_keys($markers) as $i => $label) {
        $n = (int) ($counts[$i] ?? 0);
        if ($n > 0 && !shortcode_exists(trim($label, '[]'))) {
            $found[] = $label . ' (' . $n . ')';
        }
    }
    if ($found) {
        $item('report', __('MD shortcodes and blocks in content', 'stackless-theme'), implode(', ', $found), '-', 'report', __('These print as plain text or nothing without MD. Replace them, or keep the matching MD dropin running as a plugin.', 'stackless-theme'));
    }

    // Dropin post types with content.
    $types = ['docs' => 'Docs', 'glossary' => 'Glossary', 'lesson' => 'Courses', 'testimonials' => 'Testimonials', 'bookshelf' => 'Bookshelf', 'stream' => 'Stream', 'md_data_table' => 'Data tables', 'md_snippet' => 'Hooks snippets'];
    $placeholders = implode(',', array_fill(0, count($types), '%s'));
    $rows = $wpdb->get_results($wpdb->prepare("SELECT post_type, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND post_status NOT IN ('auto-draft','trash') GROUP BY post_type", array_keys($types))); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $content = [];
    foreach ((array) $rows as $row) {
        if (!post_type_exists($row->post_type)) {
            $content[] = $types[$row->post_type] . ' (' . (int) $row->n . ')';
        }
    }
    if ($content) {
        $item('report', __('MD content types', 'stackless-theme'), implode(', ', $content), '-', 'report', __('The posts are safe in the database but have no pages without the MD dropins that register them.', 'stackless-theme'));
    }

    $features = [];
    foreach (['md_lead_capture_settings' => 'Lead capture', 'md_smtp_settings' => 'SMTP mailer', 'md_cache_settings' => 'Cache preloader', 'md_scripts_manager' => 'Scripts manager'] as $option => $label) {
        if (get_option($option)) {
            $features[] = $label;
        }
    }
    $md = stackless_theme_md_settings('db');
    foreach (['optins' => 'Optins', 'popups' => 'Popups', 'share' => 'Share buttons', 'related_posts' => 'Related posts', 'pwa' => 'PWA'] as $key => $label) {
        if (!empty($md[$key])) {
            $features[] = $label;
        }
    }
    if ($features) {
        $item('report', __('MD features', 'stackless-theme'), implode(', ', array_unique($features)), '-', 'report', __('These were part of MD and stop with it. Their settings stay in the database if you switch back.', 'stackless-theme'));
    }
}

/* ------------------------------------------------------------------ Apply */

add_action('admin_post_stackless_md_apply', 'stackless_theme_md_apply_action');
function stackless_theme_md_apply_action(): void
{
    stackless_theme_tools_guard('stackless_md_apply');
    $source = (isset($_POST['source']) && sanitize_key(wp_unslash($_POST['source'])) === 'file') ? 'file' : 'db';
    $groups = array_intersect(array_map('sanitize_key', (array) wp_unslash($_POST['groups'] ?? [])), array_keys(stackless_theme_md_groups()));
    if (!$groups) {
        stackless_theme_tools_redirect('md-nothing', ['md' => 'preview', 'source' => $source]);
    }
    $menus = [
        'primary' => isset($_POST['menu_primary']) ? sanitize_key(wp_unslash($_POST['menu_primary'])) : '',
        'footer' => isset($_POST['menu_footer']) ? sanitize_key(wp_unslash($_POST['menu_footer'])) : '',
    ];
    if (function_exists('set_time_limit')) {
        @set_time_limit(300); // phpcs:ignore WordPress.PHP.NoSilencedErrors -- not allowed on every host.
    }
    $summary = stackless_theme_md_apply(stackless_theme_md_plan($source), $groups, $menus);
    update_option('stackless_theme_md_import_log', ['time' => time(), 'source' => $source, 'groups' => $groups, 'summary' => $summary], false);
    delete_option('stackless_theme_md_notice');
    if ($source === 'file') {
        delete_transient('stackless_md_file_' . get_current_user_id());
    }
    stackless_theme_tools_redirect('md-imported');
}

/**
 * Applies the chosen groups of a plan. Returns [label => count] for the summary.
 *
 * @param array $plan   From stackless_theme_md_plan().
 * @param array $groups Group keys to apply.
 * @param array $menus  ['primary' => MD location or '', 'footer' => MD location or ''].
 */
function stackless_theme_md_apply(array $plan, array $groups, array $menus): array
{
    $summary = [];
    $backup = stackless_theme_backup(__('Before the Marketers Delight import', 'stackless-theme'));

    // Theme mods go through the Customizer sanitizers; the logo is a core setting checked here.
    $mods = [];
    foreach ($plan['mods'] as $key => $entry) {
        if (in_array($entry['group'], $groups, true)) {
            $mods[$key] = $entry['value'];
        }
    }
    if (isset($mods['custom_logo'])) {
        if (wp_attachment_is_image((int) $mods['custom_logo'])) {
            set_theme_mod('custom_logo', (int) $mods['custom_logo']);
        }
        unset($mods['custom_logo']);
    }
    [$clean] = stackless_theme_sanitize_mods($mods);
    foreach ($clean as $key => $value) {
        set_theme_mod($key, $value);
    }
    $summary['settings'] = count($clean);

    if (in_array('menus', $groups, true) && !empty($plan['menus']['md'])) {
        $locations = get_nav_menu_locations();
        foreach (['primary', 'footer'] as $target) {
            $from = $menus[$target] ?? '';
            if ($from !== '' && isset($plan['menus']['md'][$from])) {
                $locations[$target] = (int) $plan['menus']['md'][$from];
            }
        }
        set_theme_mod('nav_menu_locations', $locations);
        $summary['menus'] = count(array_filter([$menus['primary'] ?? '', $menus['footer'] ?? '']));
    }

    if (in_array('widgets', $groups, true) && $plan['widgets']) {
        $sidebars = wp_get_sidebars_widgets();
        $moving = array_merge($plan['widgets']['blog'], $plan['widgets']['footer']);
        foreach ($sidebars as $area => $ids) {
            if (is_array($ids)) {
                $sidebars[$area] = array_values(array_diff($ids, $moving));
            }
        }
        foreach (['blog', 'footer'] as $area) {
            $sidebars[$area] = array_values(array_unique(array_merge((array) ($sidebars[$area] ?? []), $plan['widgets'][$area])));
        }
        wp_set_sidebars_widgets($sidebars);
        $summary['widgets'] = count($moving);
    }

    $posts = [];
    if (in_array('templates', $groups, true)) {
        foreach ($plan['templates'] as $post_id => $template) {
            $posts[$post_id]['_wp_page_template'] = (string) get_post_meta($post_id, '_wp_page_template', true);
            update_post_meta($post_id, '_wp_page_template', $template);
        }
        $summary['templates'] = count($plan['templates']);
    }
    if (in_array('layout', $groups, true)) {
        foreach ($plan['layout'] as $post_id => $flags) {
            foreach ($flags as $key => $value) {
                $old = get_post_meta($post_id, $key, true);
                $posts[$post_id][$key] = $old === '' ? null : $old;
                update_post_meta($post_id, $key, (bool) $value);
            }
        }
        $summary['layouts'] = count($plan['layout']);
    }
    if ($posts) {
        stackless_theme_backup_posts($backup, $posts);
    }
    return $summary;
}

/* ------------------------------------------------------------------ Screens */

function stackless_theme_md_render_intro(): void
{
    $log = get_option('stackless_theme_md_import_log');
    ?>
	<div class="card">
		<h2><?php esc_html_e('Import from Marketers Delight', 'stackless-theme'); ?></h2>
		<p><?php esc_html_e('Moves the logo, colors, widths, fonts, header and footer code, menus, widgets, page templates and per-page header, footer and title settings. You see every change before anything is saved, a backup is taken first, and MD\'s own data is left untouched.', 'stackless-theme'); ?></p>
		<?php if (is_array($log)) : ?>
			<p class="description">
				<?php
				/* translators: %s: date and time */
				printf(esc_html__('Last run %s.', 'stackless-theme'), esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $log['time'])));
				$parts = [];
				foreach ((array) $log['summary'] as $key => $count) {
					$parts[] = $key . ': ' . (int) $count;
				}
				echo ' ' . esc_html(implode(', ', $parts));
				?>
			</p>
		<?php endif; ?>
		<?php if (stackless_theme_md_has_data()) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url(stackless_theme_tools_url(['md' => 'preview', 'source' => 'db'])); ?>"><?php esc_html_e('Preview the import', 'stackless-theme'); ?></a></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e('No Marketers Delight settings in this database. To bring them from another site, export them there (MD Settings > Import/Export) and upload the file under "Import a file" below. Menus, widgets and page layouts only move on the same site.', 'stackless-theme'); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

function stackless_theme_md_render_preview(): void
{
    $source = (isset($_GET['source']) && sanitize_key(wp_unslash($_GET['source'])) === 'file') ? 'file' : 'db'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preview.
    if (!stackless_theme_md_settings($source) && !($source === 'db' && stackless_theme_md_meta_post_ids())) {
        echo '<div class="notice notice-warning"><p>' . esc_html__('No Marketers Delight data found. If you uploaded a file, it expires after an hour; upload it again.', 'stackless-theme') . '</p></div>';
        echo '<p><a href="' . esc_url(stackless_theme_tools_url()) . '">' . esc_html__('Back to Stackless tools', 'stackless-theme') . '</a></p>';
        return;
    }
    $plan = stackless_theme_md_plan($source);
    $groups = stackless_theme_md_groups();
    $statuses = [
        'map' => __('Moves', 'stackless-theme'),
        'partial' => __('Partly', 'stackless-theme'),
        'skip' => __('Skipped', 'stackless-theme'),
        'report' => __('Check', 'stackless-theme'),
    ];
    $present = array_unique(array_column($plan['items'], 'group'));
    ?>
	<p><a href="<?php echo esc_url(stackless_theme_tools_url()); ?>">&larr; <?php esc_html_e('Stackless tools', 'stackless-theme'); ?></a></p>
	<h2><?php echo $source === 'file' ? esc_html__('Marketers Delight import: from the uploaded file', 'stackless-theme') : esc_html__('Marketers Delight import: from this site', 'stackless-theme'); ?></h2>
	<p><?php esc_html_e('Nothing is saved until you press Import. Untick a group to leave it out.', 'stackless-theme'); ?></p>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<input type="hidden" name="action" value="stackless_md_apply">
		<input type="hidden" name="source" value="<?php echo esc_attr($source); ?>">
		<?php wp_nonce_field('stackless_md_apply'); ?>

		<?php foreach (array_merge(array_keys($groups), ['report']) as $group) : ?>
			<?php
			$rows = array_filter($plan['items'], static function (array $row) use ($group): bool {
				return $row['group'] === $group;
			});
			if (!$rows) {
				continue;
			}
			?>
			<h3>
				<?php if ($group === 'report') : ?>
					<?php esc_html_e('Needs your attention', 'stackless-theme'); ?>
				<?php else : ?>
					<label><input type="checkbox" name="groups[]" value="<?php echo esc_attr($group); ?>" checked> <?php echo esc_html($groups[$group]); ?></label>
				<?php endif; ?>
			</h3>
			<table class="widefat striped st-md-table">
				<thead><tr><th style="width:9rem"><?php esc_html_e('Status', 'stackless-theme'); ?></th><th><?php esc_html_e('Marketers Delight', 'stackless-theme'); ?></th><th><?php esc_html_e('Stackless', 'stackless-theme'); ?></th></tr></thead>
				<tbody>
				<?php foreach ($rows as $row) : ?>
					<tr>
						<td><span class="st-md-status st-md-status--<?php echo esc_attr($row['status']); ?>"><?php echo esc_html($statuses[$row['status']]); ?></span></td>
						<td><strong><?php echo esc_html($row['label']); ?></strong><br><span class="description"><?php echo esc_html($row['from']); ?></span></td>
						<td><?php echo esc_html($row['to']); ?><?php if ($row['note'] !== '') : ?><br><span class="description"><?php echo esc_html($row['note']); ?></span><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ($group === 'menus' && !empty($plan['menus']['md'])) : ?>
				<p>
					<?php foreach (['primary' => __('Primary menu', 'stackless-theme'), 'footer' => __('Footer menu', 'stackless-theme')] as $target => $label) : ?>
						<label style="margin-right:1.5rem"><?php echo esc_html($label); ?>
							<select name="menu_<?php echo esc_attr($target); ?>">
								<option value=""><?php esc_html_e('Leave as is', 'stackless-theme'); ?></option>
								<?php foreach ($plan['menus']['md'] as $location => $menu_id) : ?>
									<?php $menu = wp_get_nav_menu_object($menu_id); ?>
									<option value="<?php echo esc_attr($location); ?>" <?php selected($plan['menus']['defaults'][$target], $location); ?>><?php echo esc_html($location . ': ' . ($menu ? $menu->name : '#' . $menu_id)); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
					<?php endforeach; ?>
				</p>
			<?php endif; ?>
			<?php if ($group === 'report' && !empty($plan['counts']['pbb_settings'])) : ?>
				<p><a class="button" href="<?php echo esc_url($plan['counts']['pbb_settings']); ?>"><?php esc_html_e('Open Page Blocks settings', 'stackless-theme'); ?></a></p>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if (array_diff($present, ['report'])) : ?>
			<?php submit_button(__('Import', 'stackless-theme')); ?>
		<?php else : ?>
			<p><?php esc_html_e('There is nothing to move; the list above only covers things to check.', 'stackless-theme'); ?></p>
		<?php endif; ?>
	</form>
	<?php
}

/* ------------------------------------------------------------------ Discovery */

add_action('admin_init', 'stackless_theme_md_register_importer');
function stackless_theme_md_register_importer(): void
{
    if (!function_exists('register_importer')) {
        require_once ABSPATH . 'wp-admin/includes/import.php';
    }
    register_importer('stackless-marketers-delight', __('Marketers Delight', 'stackless-theme'), __('Move the Marketers Delight design, menus, widgets and page layouts to the Stackless theme.', 'stackless-theme'), 'stackless_theme_md_importer_screen');
}

function stackless_theme_md_importer_screen(): void
{
    echo '<div class="wrap"><h1>' . esc_html__('Marketers Delight', 'stackless-theme') . '</h1><p>' . esc_html__('The importer lives with the other Stackless tools.', 'stackless-theme') . '</p><p><a class="button button-primary" href="' . esc_url(stackless_theme_tools_url()) . '">' . esc_html__('Open Stackless tools', 'stackless-theme') . '</a></p></div>';
}

/** After switching from MD, point to the importer once. */
add_action('after_switch_theme', 'stackless_theme_md_flag_notice');
function stackless_theme_md_flag_notice(): void
{
    if (stackless_theme_md_has_data() && !get_option('stackless_theme_md_import_log')) {
        update_option('stackless_theme_md_notice', 1, false);
    }
}

add_action('admin_notices', 'stackless_theme_md_notice');
function stackless_theme_md_notice(): void
{
    if (!get_option('stackless_theme_md_notice') || !current_user_can('manage_options')) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && $screen->id === 'appearance_page_' . STACKLESS_THEME_TOOLS_PAGE) {
        return;
    }
    $dismiss = wp_nonce_url(admin_url('admin-post.php?action=stackless_md_dismiss'), 'stackless_md_dismiss');
    printf(
        '<div class="notice notice-info"><p>%1$s</p><p><a class="button button-primary" href="%2$s">%3$s</a> <a class="button-link" href="%4$s">%5$s</a></p></div>',
        esc_html__('Coming from Marketers Delight? Stackless can bring over your logo, colors, menus, widgets and page layouts.', 'stackless-theme'),
        esc_url(stackless_theme_tools_url(['md' => 'preview', 'source' => 'db'])),
        esc_html__('Preview the import', 'stackless-theme'),
        esc_url($dismiss),
        esc_html__('Dismiss', 'stackless-theme')
    );
}

add_action('admin_post_stackless_md_dismiss', 'stackless_theme_md_dismiss');
function stackless_theme_md_dismiss(): void
{
    stackless_theme_tools_guard('stackless_md_dismiss');
    delete_option('stackless_theme_md_notice');
    wp_safe_redirect(wp_get_referer() ?: admin_url());
    exit;
}
