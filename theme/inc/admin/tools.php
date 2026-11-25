<?php
/**
 * Appearance > Stackless tools: export and import the theme settings, and restore a backup.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_TOOLS_PAGE = 'stackless-tools';
const STACKLESS_THEME_BACKUPS = 'stackless_theme_backups';
const STACKLESS_THEME_BACKUPS_KEEP = 3;

function stackless_theme_tools_url(array $args = []): string
{
    return add_query_arg(array_merge(['page' => STACKLESS_THEME_TOOLS_PAGE], $args), admin_url('themes.php'));
}

add_action('admin_menu', 'stackless_theme_tools_menu');
function stackless_theme_tools_menu(): void
{
    add_theme_page(__('Stackless tools', 'stackless-theme'), __('Stackless tools', 'stackless-theme'), 'manage_options', STACKLESS_THEME_TOOLS_PAGE, 'stackless_theme_tools_render');
}

add_action('admin_post_stackless_export_settings', 'stackless_theme_export_settings');
add_action('admin_post_stackless_import_file', 'stackless_theme_import_file');
add_action('admin_post_stackless_restore_backup', 'stackless_theme_restore_backup_action');

/* ------------------------------------------------------------------ Settings */

/** The theme's own settings: every theme mod that starts with stackless_. */
function stackless_theme_settings_mods(): array
{
    $mods = get_theme_mods();
    if (!is_array($mods)) {
        return [];
    }
    return array_filter($mods, static function ($key): bool {
        return is_string($key) && strpos($key, 'stackless_') === 0;
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * The Customizer settings the theme registers, so imported values go through the same sanitizers
 * as values saved in the Customizer. Values without a registered setting are dropped.
 */
function stackless_theme_customize_manager(): ?WP_Customize_Manager
{
    static $manager = null;
    if ($manager instanceof WP_Customize_Manager) {
        return $manager;
    }
    require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
    // Widgets and menus components are not needed to read the theme's settings.
    add_filter('customize_loaded_components', '__return_empty_array', 999);
    $manager = new WP_Customize_Manager();
    remove_filter('customize_loaded_components', '__return_empty_array', 999);
    do_action('customize_register', $manager);
    return $manager;
}

/** Sanitizes [theme mod => value] through the registered settings. Returns [clean, dropped keys]. */
function stackless_theme_sanitize_mods(array $mods): array
{
    $manager = stackless_theme_customize_manager();
    $clean = [];
    $dropped = [];
    foreach ($mods as $key => $value) {
        $setting = ($manager && is_string($key) && strpos($key, 'stackless_') === 0) ? $manager->get_setting($key) : null;
        if (!$setting instanceof WP_Customize_Setting || !current_user_can($setting->capability)) {
            $dropped[] = (string) $key;
            continue;
        }
        $value = $setting->sanitize($value);
        if ($value === null || is_wp_error($value)) {
            $dropped[] = (string) $key;
            continue;
        }
        $clean[$key] = $value;
    }
    return [$clean, $dropped];
}

/* ------------------------------------------------------------------ Backups */

function stackless_theme_backups(): array
{
    $backups = get_option(STACKLESS_THEME_BACKUPS, []);
    return is_array($backups) ? $backups : [];
}

/**
 * Saves the current settings, logo, menu locations and widget areas before an import. Imports
 * that change posts add their old values with stackless_theme_backup_posts().
 */
function stackless_theme_backup(string $label): string
{
    $id = strtolower(wp_generate_password(12, false));
    $backups = stackless_theme_backups();
    $backups[$id] = [
        'time' => time(),
        'label' => $label,
        'mods' => stackless_theme_settings_mods(),
        'custom_logo' => (int) get_theme_mod('custom_logo', 0),
        'nav_menu_locations' => get_nav_menu_locations(),
        'sidebars_widgets' => wp_get_sidebars_widgets(),
        'posts' => [],
    ];
    $backups = array_slice($backups, -STACKLESS_THEME_BACKUPS_KEEP, null, true);
    update_option(STACKLESS_THEME_BACKUPS, $backups, false);
    return $id;
}

/** Records post meta values as they were before an import: [post ID => [meta key => old value or null]]. */
function stackless_theme_backup_posts(string $id, array $posts): void
{
    $backups = stackless_theme_backups();
    if (!isset($backups[$id])) {
        return;
    }
    $backups[$id]['posts'] = $posts + (array) $backups[$id]['posts'];
    update_option(STACKLESS_THEME_BACKUPS, $backups, false);
}

function stackless_theme_restore_backup(string $id): bool
{
    $backups = stackless_theme_backups();
    $backup = $backups[$id] ?? null;
    if (!is_array($backup)) {
        return false;
    }
    foreach (array_keys(stackless_theme_settings_mods()) as $key) {
        remove_theme_mod($key);
    }
    foreach ((array) $backup['mods'] as $key => $value) {
        if (is_string($key) && strpos($key, 'stackless_') === 0) {
            set_theme_mod($key, $value);
        }
    }
    if (!empty($backup['custom_logo'])) {
        set_theme_mod('custom_logo', (int) $backup['custom_logo']);
    } else {
        remove_theme_mod('custom_logo');
    }
    set_theme_mod('nav_menu_locations', array_map('intval', (array) $backup['nav_menu_locations']));
    if (is_array($backup['sidebars_widgets'] ?? null)) {
        wp_set_sidebars_widgets($backup['sidebars_widgets']);
    }
    foreach ((array) $backup['posts'] as $post_id => $meta) {
        foreach ((array) $meta as $key => $value) {
            if ($value === null || $value === '') {
                delete_post_meta((int) $post_id, (string) $key);
            } else {
                update_post_meta((int) $post_id, (string) $key, $value);
            }
        }
    }
    return true;
}

/* ------------------------------------------------------------------ Handlers */

function stackless_theme_tools_guard(string $action): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to manage the theme settings.', 'stackless-theme'), '', ['response' => 403]);
    }
    check_admin_referer($action);
}

function stackless_theme_tools_redirect(string $notice, array $args = []): void
{
    wp_safe_redirect(stackless_theme_tools_url(array_merge(['notice' => $notice], $args)));
    exit;
}

function stackless_theme_export_settings(): void
{
    stackless_theme_tools_guard('stackless_export_settings');
    $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    $data = [
        '_generator' => 'stackless-theme',
        '_version' => STACKLESS_THEME_VERSION,
        '_exported' => current_time('mysql'),
        '_site' => home_url(),
        'settings' => stackless_theme_settings_mods(),
    ];
    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="stackless-settings-' . sanitize_file_name($host) . '-' . gmdate('Y-m-d') . '.json"');
    echo wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Imports a Stackless settings export. */
function stackless_theme_import_file(): void
{
    stackless_theme_tools_guard('stackless_import_file');
    $file = $_FILES['stackless_import'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated below.
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        stackless_theme_tools_redirect('no-file');
    }
    if (strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION)) !== 'json' || (int) $file['size'] > 5 * MB_IN_BYTES) {
        stackless_theme_tools_redirect('bad-file');
    }
    $data = json_decode((string) file_get_contents((string) $file['tmp_name']), true); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local upload.
    if (!is_array($data) || !isset($data['settings']) || !is_array($data['settings'])) {
        stackless_theme_tools_redirect('bad-file');
    }

    if ((string) ($data['_generator'] ?? '') !== 'stackless-theme') {
        stackless_theme_tools_redirect('bad-file');
    }

    [$clean, $dropped] = stackless_theme_sanitize_mods($data['settings']);
    stackless_theme_backup(__('Before importing a settings file', 'stackless-theme'));
    foreach (array_keys(stackless_theme_settings_mods()) as $key) {
        remove_theme_mod($key);
    }
    foreach ($clean as $key => $value) {
        set_theme_mod($key, $value);
    }
    stackless_theme_tools_redirect('imported', ['count' => count($clean), 'dropped' => count($dropped)]);
}

function stackless_theme_restore_backup_action(): void
{
    // Not sanitize_key(): it lowercases, and backup IDs may hold capitals.
    $id = isset($_POST['backup']) ? (string) preg_replace('/[^A-Za-z0-9]/', '', (string) wp_unslash($_POST['backup'])) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reduced to [A-Za-z0-9].
    stackless_theme_tools_guard('stackless_restore_backup_' . $id);
    stackless_theme_tools_redirect(stackless_theme_restore_backup($id) ? 'restored' : 'no-backup');
}

/* ------------------------------------------------------------------ Admin look */

/**
 * The admin stylesheet for Stackless screens, shared with Stackless Theme Pro. It builds on core's
 * design tokens (wp-theme) where WordPress has them. Registered on every admin page, so Pro can
 * enqueue it by handle; printed only where a screen asks for it.
 */
add_action('admin_enqueue_scripts', 'stackless_theme_admin_style_register', 5);
function stackless_theme_admin_style_register(): void
{
    $path = stackless_theme_asset('assets/css/admin.css');
    $deps = wp_style_is('wp-theme', 'registered') ? ['wp-theme'] : [];
    wp_register_style('stackless-theme-admin', STACKLESS_THEME_URI . '/' . $path, $deps, stackless_theme_asset_version($path));
}

add_action('admin_enqueue_scripts', 'stackless_theme_tools_assets');
function stackless_theme_tools_assets(string $hook): void
{
    if ($hook === 'appearance_page_' . STACKLESS_THEME_TOOLS_PAGE) {
        wp_enqueue_style('stackless-theme-admin');
    }
}

/**
 * Links between the Stackless screens, shown as tabs at the top of each. Filter
 * stackless_theme_admin_tabs to add one: slug => [label, url, capability].
 */
function stackless_theme_admin_tabs(): array
{
    $tabs = [
        'tools' => [__('Tools and backups', 'stackless-theme'), stackless_theme_tools_url(), 'manage_options'],
        'customize' => [__('Customize', 'stackless-theme'), add_query_arg('autofocus[panel]', STACKLESS_THEME_PANEL, admin_url('customize.php')), 'customize'],
        'performance' => [__('Performance', 'stackless-theme'), add_query_arg('autofocus[section]', 'stackless_theme_performance', admin_url('customize.php')), 'customize'],
    ];
    $tabs = (array) apply_filters('stackless_theme_admin_tabs', $tabs);
    return array_filter($tabs, static function ($tab): bool {
        return is_array($tab) && isset($tab[0], $tab[1]) && current_user_can((string) ($tab[2] ?? 'manage_options'));
    });
}

/**
 * The header of a Stackless admin screen: title, a line under it, an optional note on the right,
 * and the tabs. Stackless Theme Pro calls it for its screens.
 */
function stackless_theme_admin_header(string $current, string $title, string $lead = '', string $meta = ''): void
{
    ?>
    <div class="stackless-admin__header">
        <div>
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($lead !== '') : ?>
                <p class="stackless-admin__lead"><?php echo esc_html($lead); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($meta !== '') : ?>
            <span class="stackless-admin__meta"><?php echo esc_html($meta); ?></span>
        <?php endif; ?>
    </div>
    <?php
    // WordPress moves notices printed by other plugins under the first h1 or .wp-header-end.
    echo '<hr class="wp-header-end">';
    $tabs = stackless_theme_admin_tabs();
    if (count($tabs) > 1) {
        echo '<nav aria-label="' . esc_attr__('Stackless screens', 'stackless-theme') . '"><ul class="stackless-admin__tabs">';
        foreach ($tabs as $slug => $tab) {
            printf(
                '<li><a href="%1$s"%2$s>%3$s</a></li>',
                esc_url((string) $tab[1]),
                $slug === $current ? ' aria-current="page"' : '',
                esc_html((string) $tab[0])
            );
        }
        echo '</ul></nav>';
    }
}

/** A notice inside a Stackless screen: 'success', 'error', 'warning' or 'info'. Text is escaped. */
function stackless_theme_admin_notice(string $type, string $message): void
{
    $role = $type === 'error' ? 'alert' : 'status';
    printf('<div class="stackless-notice is-%1$s" role="%2$s"><p>%3$s</p></div>', esc_attr($type), esc_attr($role), esc_html($message));
}

/* ------------------------------------------------------------------ Page */

function stackless_theme_tools_notice(): void
{
    $notice = isset($_GET['notice']) ? sanitize_key(wp_unslash($_GET['notice'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
    $count = isset($_GET['count']) ? absint($_GET['count']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $dropped = isset($_GET['dropped']) ? absint($_GET['dropped']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $messages = [
        'no-file' => ['error', __('Choose a .json file to import.', 'stackless-theme')],
        'bad-file' => ['error', __('That file is not a Stackless settings export.', 'stackless-theme')],
        /* translators: 1: settings imported, 2: settings skipped */
        'imported' => ['success', sprintf(__('Imported %1$d settings (%2$d skipped). The previous settings are saved as a backup below.', 'stackless-theme'), $count, $dropped)],
        'restored' => ['success', __('Backup restored.', 'stackless-theme')],
        'no-backup' => ['error', __('That backup no longer exists.', 'stackless-theme')],
    ];
    if (isset($messages[$notice])) {
        stackless_theme_admin_notice($messages[$notice][0], $messages[$notice][1]);
    }
}

/** The performance options that are on, as labels, for the summary card. */
function stackless_theme_tools_performance_summary(): array
{
    $labels = [
        'emoji' => __('Emoji script', 'stackless-theme'),
        'head_links' => __('Extra head links', 'stackless-theme'),
        'embeds' => __('Embed cards', 'stackless-theme'),
        'body_classes' => __('Body classes', 'stackless-theme'),
        'dashicons' => __('Dashicons', 'stackless-theme'),
        'jquery_migrate' => __('jQuery Migrate', 'stackless-theme'),
    ];
    $on = [];
    foreach ($labels as $key => $label) {
        if (function_exists('stackless_theme_performance') && stackless_theme_performance($key)) {
            $on[] = $label;
        }
    }
    return [$on, count($labels)];
}

function stackless_theme_tools_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $post_url = admin_url('admin-post.php');
    $backups = array_reverse(stackless_theme_backups(), true);
    [$perf_on, $perf_total] = stackless_theme_tools_performance_summary();
    ?>
    <div class="wrap stackless-admin">
        <?php
        stackless_theme_admin_header(
            'tools',
            __('Stackless', 'stackless-theme'),
            __('Move the theme settings between sites, and go back to the settings you had before an import.', 'stackless-theme'),
            /* translators: %s: theme version */
            sprintf(__('Theme %s', 'stackless-theme'), STACKLESS_THEME_VERSION)
        );
        stackless_theme_tools_notice();
        ?>

        <div class="stackless-admin__grid is-halves">
            <section class="stackless-card" aria-labelledby="stackless-export-title">
                <div class="stackless-card__header"><h2 id="stackless-export-title"><?php esc_html_e('Export settings', 'stackless-theme'); ?></h2></div>
                <div class="stackless-card__body">
                    <p class="stackless-help"><?php esc_html_e('A JSON file with every Stackless Customizer setting: design, header, blog, store, footer, code and performance. Menus, widgets and the logo stay with this site.', 'stackless-theme'); ?></p>
                </div>
                <form class="stackless-card__footer" method="post" action="<?php echo esc_url($post_url); ?>">
                    <input type="hidden" name="action" value="stackless_export_settings">
                    <?php wp_nonce_field('stackless_export_settings'); ?>
                    <span class="stackless-help"><?php esc_html_e('Nothing on this site changes.', 'stackless-theme'); ?></span>
                    <?php submit_button(__('Download settings', 'stackless-theme'), 'secondary', 'submit', false); ?>
                </form>
            </section>

            <section class="stackless-card" aria-labelledby="stackless-import-title">
                <div class="stackless-card__header"><h2 id="stackless-import-title"><?php esc_html_e('Import settings', 'stackless-theme'); ?></h2></div>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url($post_url); ?>">
                    <div class="stackless-card__body">
                        <input type="hidden" name="action" value="stackless_import_file">
                        <?php wp_nonce_field('stackless_import_file'); ?>
                        <label class="stackless-file" for="stackless-import-file">
                            <span class="screen-reader-text"><?php esc_html_e('Settings file', 'stackless-theme'); ?></span>
                            <input id="stackless-import-file" type="file" name="stackless_import" accept=".json,application/json" required aria-describedby="stackless-import-help">
                        </label>
                    </div>
                    <div class="stackless-card__footer">
                        <span class="stackless-help" id="stackless-import-help"><?php esc_html_e('Replaces the current settings. They are backed up first.', 'stackless-theme'); ?></span>
                        <?php submit_button(__('Import', 'stackless-theme'), 'primary', 'submit', false); ?>
                    </div>
                </form>
            </section>

            <section class="stackless-card is-full" aria-labelledby="stackless-backups-title">
                <div class="stackless-card__header">
                    <h2 id="stackless-backups-title"><?php esc_html_e('Backups', 'stackless-theme'); ?></h2>
                    <?php /* translators: 1: backups saved, 2: backups kept */ ?>
                    <span class="stackless-badge"><?php echo esc_html(sprintf(__('%1$d of %2$d', 'stackless-theme'), count($backups), STACKLESS_THEME_BACKUPS_KEEP)); ?></span>
                </div>
                <?php if (!$backups) : ?>
                    <div class="stackless-card__body">
                        <p class="stackless-help"><?php esc_html_e('Every import saves the settings, logo, menu locations, widget areas and changed page layouts first. The last three are kept here.', 'stackless-theme'); ?></p>
                    </div>
                <?php else : ?>
                    <ul class="stackless-rows">
                        <?php foreach ($backups as $id => $backup) : ?>
                            <li class="stackless-row">
                                <span class="stackless-row__title"><?php echo esc_html((string) $backup['label']); ?></span>
                                <p class="stackless-row__text">
                                    <?php
                                    echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $backup['time']));
                                    $posts = count((array) ($backup['posts'] ?? []));
                                    if ($posts) {
                                        /* translators: %d: number of posts */
                                        echo ' &middot; ' . esc_html(sprintf(_n('%d post layout', '%d post layouts', $posts, 'stackless-theme'), $posts));
                                    }
                                    ?>
                                </p>
                                <form class="stackless-row__control" method="post" action="<?php echo esc_url($post_url); ?>" onsubmit="return confirm('<?php echo esc_js(__('Restore this backup? The current settings are replaced.', 'stackless-theme')); ?>');">
                                    <input type="hidden" name="action" value="stackless_restore_backup">
                                    <input type="hidden" name="backup" value="<?php echo esc_attr((string) $id); ?>">
                                    <?php wp_nonce_field('stackless_restore_backup_' . $id); ?>
                                    <button type="submit" class="button" aria-label="<?php echo esc_attr(sprintf(/* translators: %s: backup label */ __('Restore: %s', 'stackless-theme'), (string) $backup['label'])); ?>"><?php esc_html_e('Restore', 'stackless-theme'); ?></button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <?php if (current_user_can('customize')) : ?>
                <section class="stackless-card is-full" aria-labelledby="stackless-performance-title">
                    <div class="stackless-card__header">
                        <h2 id="stackless-performance-title"><?php esc_html_e('Performance', 'stackless-theme'); ?></h2>
                        <?php /* translators: 1: options on, 2: all options */ ?>
                        <span class="stackless-badge<?php echo $perf_on ? ' is-success' : ''; ?>"><?php echo esc_html(sprintf(__('%1$d of %2$d on', 'stackless-theme'), count($perf_on), $perf_total)); ?></span>
                    </div>
                    <div class="stackless-card__body">
                        <p class="stackless-help">
                            <?php
                            echo esc_html($perf_on
                                /* translators: %s: list of options, e.g. "Emoji script, Dashicons" */
                                ? sprintf(__('Left out of every page: %s.', 'stackless-theme'), implode(', ', $perf_on))
                                : __('WordPress adds an emoji script, head links, embed discovery and more to every page. Choose what this site can do without.', 'stackless-theme'));
                            ?>
                        </p>
                        <p><a class="button" href="<?php echo esc_url(add_query_arg(['autofocus[section]' => 'stackless_theme_performance', 'return' => rawurlencode(stackless_theme_tools_url())], admin_url('customize.php'))); ?>"><?php esc_html_e('Open Performance in the Customizer', 'stackless-theme'); ?></a></p>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
