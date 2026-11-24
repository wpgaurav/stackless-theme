<?php
/**
 * Menu item icons: an "Icon" field on every menu item in Appearance > Menus, picked from the
 * theme's bundled Tabler icons (assets/icons). The icon prints before the item's label in any
 * menu; such items get the menu-item-has-icon class and their label a menu-item-label span.
 *
 * In the sidebar layout's rail every label is wrapped, so the rail can hide labels when it
 * collapses to icons, and an item without an icon shows its first letter there instead.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

const STACKLESS_THEME_MENU_ICON = '_stackless_menu_icon';

/** Icon names the field offers: every SVG in assets/icons, filterable with stackless_theme_menu_icons. */
function stackless_theme_menu_icons(): array
{
    static $icons = null;
    if ($icons === null) {
        $icons = array_map(static function (string $file): string {
            return basename($file, '.svg');
        }, (array) glob(STACKLESS_THEME_DIR . '/assets/icons/*.svg'));
        sort($icons);
        $icons = array_values(array_filter((array) apply_filters('stackless_theme_menu_icons', $icons), 'is_string'));
    }
    return $icons;
}

/** A menu item's icon name, or '' when it has none (or the icon no longer exists). */
function stackless_theme_menu_item_icon($item): string
{
    $id = is_object($item) && isset($item->ID) ? (int) $item->ID : 0;
    $icon = $id ? (string) get_post_meta($id, STACKLESS_THEME_MENU_ICON, true) : '';
    return $icon !== '' && in_array($icon, stackless_theme_menu_icons(), true) ? $icon : '';
}

add_action('wp_nav_menu_item_custom_fields', 'stackless_theme_menu_icon_field', 10, 2);
function stackless_theme_menu_icon_field($item_id, $item): void
{
    $current = stackless_theme_menu_item_icon($item);
    $id = 'edit-menu-item-stackless-icon-' . (int) $item_id;
    ?>
	<p class="field-stackless-icon description description-wide">
		<label for="<?php echo esc_attr($id); ?>">
			<?php esc_html_e('Icon', 'stackless-theme'); ?><br>
			<select id="<?php echo esc_attr($id); ?>" class="widefat" name="menu-item-stackless-icon[<?php echo (int) $item_id; ?>]">
				<option value=""><?php esc_html_e('None', 'stackless-theme'); ?></option>
				<?php foreach (stackless_theme_menu_icons() as $icon) : ?>
				<option value="<?php echo esc_attr($icon); ?>"<?php selected($current, $icon); ?>><?php echo esc_html($icon); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	</p>
	<?php
}

/** Saved with the menu; core has checked the nonce and the edit_theme_options capability by now. */
add_action('wp_update_nav_menu_item', 'stackless_theme_menu_icon_save', 10, 2);
function stackless_theme_menu_icon_save(int $menu_id, int $item_id): void
{
    // phpcs:disable WordPress.Security.NonceVerification.Missing -- core verified update-nav_menu.
    if (!current_user_can('edit_theme_options') || !isset($_POST['menu-item-stackless-icon']) || !is_array($_POST['menu-item-stackless-icon'])) {
        return;
    }
    $icon = isset($_POST['menu-item-stackless-icon'][$item_id]) ? sanitize_key(wp_unslash($_POST['menu-item-stackless-icon'][$item_id])) : '';
    // phpcs:enable
    if ($icon !== '' && in_array($icon, stackless_theme_menu_icons(), true)) {
        update_post_meta($item_id, STACKLESS_THEME_MENU_ICON, $icon);
    } else {
        delete_post_meta($item_id, STACKLESS_THEME_MENU_ICON);
    }
}

add_filter('nav_menu_css_class', 'stackless_theme_menu_icon_class', 10, 2);
function stackless_theme_menu_icon_class(array $classes, $item): array
{
    if (stackless_theme_menu_item_icon($item) !== '') {
        $classes[] = 'menu-item-has-icon';
    }
    return $classes;
}

add_filter('nav_menu_item_title', 'stackless_theme_menu_icon_title', 10, 4);
function stackless_theme_menu_icon_title(string $title, $item, $args, int $depth): string
{
    $icon = stackless_theme_menu_item_icon($item);
    $rail = is_object($args) && !empty($args->stackless_rail);
    if ($icon === '' && !$rail) {
        return $title;
    }
    $before = $icon !== '' ? stackless_theme_icon($icon, 18) : '';
    if ($before === '' && $rail && $depth === 0) {
        // Shown in place of an icon while the rail is collapsed to icons.
        $letter = function_exists('mb_substr') ? mb_substr(trim(wp_strip_all_tags($title)), 0, 1) : substr(trim(wp_strip_all_tags($title)), 0, 1);
        $before = '<span class="menu-item-initial" aria-hidden="true">' . esc_html($letter) . '</span>';
    }
    return $before . '<span class="menu-item-label">' . $title . '</span>';
}
