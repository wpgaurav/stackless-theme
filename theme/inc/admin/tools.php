<?php
/**
 * Appearance > Stackless tools: export and import the theme settings, restore a backup, and the
 * Marketers Delight importer (inc/admin/md-import.php).
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

/** One upload box: a Stackless export imports at once; a Marketers Delight export opens the import preview. */
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

    $generator = (string) ($data['_generator'] ?? '');
    if ($generator === 'marketers-delight') {
        set_transient('stackless_md_file_' . get_current_user_id(), $data['settings'], HOUR_IN_SECONDS);
        wp_safe_redirect(stackless_theme_tools_url(['md' => 'preview', 'source' => 'file']));
        exit;
    }
    if ($generator !== 'stackless-theme') {
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

/* ------------------------------------------------------------------ Page */

function stackless_theme_tools_notice(): void
{
    $notice = isset($_GET['notice']) ? sanitize_key(wp_unslash($_GET['notice'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
    $count = isset($_GET['count']) ? absint($_GET['count']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $dropped = isset($_GET['dropped']) ? absint($_GET['dropped']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $messages = [
        'no-file' => ['error', __('Choose a .json file to import.', 'stackless-theme')],
        'bad-file' => ['error', __('That file is not a Stackless or Marketers Delight settings export.', 'stackless-theme')],
        /* translators: 1: settings imported, 2: settings skipped */
        'imported' => ['success', sprintf(__('Imported %1$d settings (%2$d skipped). The previous settings are saved as a backup below.', 'stackless-theme'), $count, $dropped)],
        'restored' => ['success', __('Backup restored.', 'stackless-theme')],
        'no-backup' => ['error', __('That backup no longer exists.', 'stackless-theme')],
        'md-imported' => ['success', __('Marketers Delight import finished. Check the summary below; the previous Stackless settings are saved as a backup.', 'stackless-theme')],
        'md-nothing' => ['warning', __('Nothing was selected to import.', 'stackless-theme')],
    ];
    if (isset($messages[$notice])) {
        printf('<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr($messages[$notice][0]), esc_html($messages[$notice][1]));
    }
}

function stackless_theme_tools_render(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $view = isset($_GET['md']) ? sanitize_key(wp_unslash($_GET['md'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- view switch only.
    ?>
	<div class="wrap stackless-tools">
		<h1><?php esc_html_e('Stackless tools', 'stackless-theme'); ?></h1>
		<?php stackless_theme_tools_notice(); ?>

		<?php if ($view === 'preview') : ?>
			<?php stackless_theme_md_render_preview(); ?>
		<?php else : ?>
			<?php stackless_theme_md_render_intro(); ?>

			<div class="card">
				<h2><?php esc_html_e('Export settings', 'stackless-theme'); ?></h2>
				<p><?php esc_html_e('Download the Stackless Customizer settings (design, footer, code, store toolbars and related items) as a JSON file for another site or a backup. Menus, widgets and the logo stay with this site.', 'stackless-theme'); ?></p>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="stackless_export_settings">
					<?php wp_nonce_field('stackless_export_settings'); ?>
					<?php submit_button(__('Download settings', 'stackless-theme'), 'secondary', 'submit', false); ?>
				</form>
			</div>

			<div class="card">
				<h2><?php esc_html_e('Import a file', 'stackless-theme'); ?></h2>
				<p><?php esc_html_e('A Stackless export replaces the current settings straight away. A Marketers Delight export (MD Settings > Import/Export) opens a preview first.', 'stackless-theme'); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="stackless_import_file">
					<?php wp_nonce_field('stackless_import_file'); ?>
					<p><input type="file" name="stackless_import" accept=".json,application/json" required></p>
					<?php submit_button(__('Import', 'stackless-theme'), 'secondary', 'submit', false); ?>
				</form>
			</div>

			<?php $backups = array_reverse(stackless_theme_backups(), true); ?>
			<div class="card">
				<h2><?php esc_html_e('Backups', 'stackless-theme'); ?></h2>
				<?php if (!$backups) : ?>
					<p><?php esc_html_e('Every import saves the settings, logo, menu locations, widget areas and changed page layouts first. The last three are kept here.', 'stackless-theme'); ?></p>
				<?php else : ?>
					<table class="widefat striped">
						<tbody>
						<?php foreach ($backups as $id => $backup) : ?>
							<tr>
								<td>
									<strong><?php echo esc_html((string) $backup['label']); ?></strong><br>
									<span class="description">
										<?php
										echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $backup['time']));
										$posts = count((array) ($backup['posts'] ?? []));
										if ($posts) {
											/* translators: %d: number of posts */
											echo ' &middot; ' . esc_html(sprintf(_n('%d post layout', '%d post layouts', $posts, 'stackless-theme'), $posts));
										}
										?>
									</span>
								</td>
								<td style="text-align:right">
									<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Restore this backup? The current settings are replaced.', 'stackless-theme')); ?>');">
										<input type="hidden" name="action" value="stackless_restore_backup">
										<input type="hidden" name="backup" value="<?php echo esc_attr((string) $id); ?>">
										<?php wp_nonce_field('stackless_restore_backup_' . $id); ?>
										<?php submit_button(__('Restore', 'stackless-theme'), 'small', 'submit', false); ?>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<style>.stackless-tools .card{max-width:56rem}.stackless-tools .card h2{margin-top:.5em}.stackless-tools .st-md-table td,.stackless-tools .st-md-table th{vertical-align:top}.stackless-tools .st-md-status{display:inline-block;min-width:4.5rem;padding:1px 6px;border-radius:2px;font-size:12px;text-align:center}.st-md-status--map{background:#edfaef;color:#1a6a28}.st-md-status--partial{background:#fef5e6;color:#7a4f00}.st-md-status--skip{background:#f0f0f1;color:#50575e}.st-md-status--report{background:#fbebeb;color:#8f2020}</style>
	<?php
}
