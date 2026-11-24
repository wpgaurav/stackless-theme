<?php
/**
 * The site header: brand, primary menu, search, account and cart, and the search overlay.
 *
 * Parts (see inc/hooks.php): header, header_brand, header_nav, header_nav_right, header_actions,
 * header_rail, header_search_field, header_rail_list, header_rail_toggle, header_account_menu,
 * header_search_button, header_account, header_cart, header_menu_toggle, search_overlay.
 * Inside actions: stackless_theme_header_start/_end, stackless_theme_header_actions_start/_end.
 *
 * Where the parts sit follows stackless_theme_header_layout() (filter stackless_theme_header_layout):
 * 'start' parts print first, 'center' parts in a centered middle column, 'end' parts in the actions
 * row. The default layout prints the same markup as the fixed header before 1.8.0.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_layout = stackless_theme_header_layout();
$stackless_theme_center = $stackless_theme_layout['center'] !== [];
// The Header right menu (split layout) prints only when placed and given a menu. Below 1080px its
// items join the primary menu that the Menu button opens.
$stackless_theme_right_menu = in_array('navigation_right', array_merge(...array_values($stackless_theme_layout)), true) && has_nav_menu('header-right');
$stackless_theme_right_menu_args = static function (array $args): array {
	return (array) apply_filters('stackless_theme_header_right_menu_args', $args + [
		'theme_location' => 'header-right',
		'container' => false,
		'depth' => 2,
		'fallback_cb' => false,
	]);
};

/*
 * One header part by its layout slug. Each branch keeps the indentation it had in the fixed header,
 * so the default layout's markup is unchanged byte for byte.
 */
$stackless_theme_rail = stackless_theme_header_has_rail();

$stackless_theme_header_part = static function (string $slug) use ($stackless_theme_right_menu, $stackless_theme_right_menu_args, $stackless_theme_rail): void {
	switch ($slug) {
		case 'branding':
			?>
		<?php if (stackless_theme_part_start('header_brand')) : ?>
		<div class="<?php echo stackless_theme_class('header_brand', ['site-brand']); ?>">
			<?php stackless_theme_site_brand(); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'navigation':
			?>
		<?php if (stackless_theme_part_start('header_nav')) : ?>
		<nav id="site-navigation" class="<?php echo stackless_theme_class('header_nav', ['site-nav']); ?>" aria-label="<?php echo esc_attr(stackless_theme_text('primary_menu_label', __('Primary', 'stackless-theme'))); ?>" data-st-nav>
			<?php
			wp_nav_menu((array) apply_filters('stackless_theme_primary_menu_args', [
				'theme_location' => 'primary',
				'menu_id' => 'primary-menu',
				'container' => false,
				'depth' => 2,
				'fallback_cb' => 'stackless_theme_menu_fallback',
			] + ($stackless_theme_rail ? ['stackless_rail' => true] : [])));
			if ($stackless_theme_right_menu) {
				// The Header right menu again, shown only in the phone and tablet menu.
				wp_nav_menu($stackless_theme_right_menu_args(['menu_id' => 'header-right-menu-merged', 'menu_class' => 'menu site-nav__merged', 'stackless_no_item_ids' => true]));
			}
			stackless_theme_currency_switcher('menu');
			?>
		</nav>
		<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'navigation_right':
			?>
			<?php if ($stackless_theme_right_menu && stackless_theme_part_start('header_nav_right')) : ?>
			<nav id="site-navigation-right" class="<?php echo stackless_theme_class('header_nav_right', ['site-nav', 'site-nav--right']); ?>" aria-label="<?php echo esc_attr(stackless_theme_text('header_right_menu_label', __('Secondary', 'stackless-theme'))); ?>">
				<?php wp_nav_menu($stackless_theme_right_menu_args(['menu_id' => 'header-right-menu'])); ?>
			</nav>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'currency':
			?>
			<?php stackless_theme_currency_switcher('header'); ?>
<?php
			break;
		case 'search':
			?>
			<?php if (stackless_theme_option('stackless_header_search') && stackless_theme_part_start('header_search_button')) : ?>
				<button type="button" class="header-action" aria-controls="site-search" aria-expanded="false" data-st-toggle="search">
					<?php stackless_theme_the_icon('search', 22); ?>
					<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('search', __('Search', 'stackless-theme'))); ?></span>
				</button>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'account':
			?>
			<?php $stackless_theme_account = stackless_theme_option('stackless_store_header_account') ? stackless_theme_account_url() : ''; ?>
			<?php if ($stackless_theme_account && stackless_theme_part_start('header_account', ['url' => $stackless_theme_account])) : ?>
				<a class="header-action" href="<?php echo esc_url($stackless_theme_account); ?>">
					<?php stackless_theme_the_icon('user', 22); ?>
					<span class="screen-reader-text"><?php echo esc_html(is_user_logged_in() ? stackless_theme_text('my_account', __('My account', 'stackless-theme')) : stackless_theme_text('sign_in', __('Sign in', 'stackless-theme'))); ?></span>
				</a>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'cart':
			?>
			<?php
			if (stackless_theme_option('stackless_store_header_cart') && stackless_theme_part_start('header_cart')) {
				echo stackless_theme_cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
				stackless_theme_part_end();
			}
			?>
<?php
			break;
		case 'search_field':
			?>
			<?php if (stackless_theme_option('stackless_header_rail_search') && stackless_theme_part_start('header_search_field')) : ?>
			<form role="search" method="get" class="<?php echo stackless_theme_class('header_search_field', ['site-rail-search']); ?>" action="<?php echo esc_url(home_url('/')); ?>">
				<label class="screen-reader-text" for="site-rail-search-field"><?php echo esc_html(stackless_theme_text('search_for', __('Search for:', 'stackless-theme'))); ?></label>
				<?php stackless_theme_the_icon('search', 18); ?>
				<input type="search" id="site-rail-search-field" class="site-rail-search__field" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr(stackless_theme_text('search_placeholder', __('Search', 'stackless-theme'))); ?>" autocomplete="off" data-st-rail-search>
			</form>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'rail_list':
			$stackless_theme_list_title = trim((string) stackless_theme_option('stackless_header_rail_list_title'));
			?>
			<?php if (has_nav_menu('sidebar-list') && stackless_theme_part_start('header_rail_list', ['title' => $stackless_theme_list_title])) : ?>
			<nav class="<?php echo stackless_theme_class('header_rail_list', ['site-rail-list']); ?>" aria-label="<?php echo esc_attr($stackless_theme_list_title !== '' ? $stackless_theme_list_title : stackless_theme_text('rail_list_label', __('Lists', 'stackless-theme'))); ?>">
				<?php if ($stackless_theme_list_title !== '') : ?>
				<p class="site-rail-list__title" aria-hidden="true"><?php echo esc_html($stackless_theme_list_title); ?></p>
				<?php endif; ?>
				<?php
				wp_nav_menu((array) apply_filters('stackless_theme_rail_list_menu_args', [
					'theme_location' => 'sidebar-list',
					'menu_id' => 'sidebar-list-menu',
					'container' => false,
					'depth' => 1,
					'fallback_cb' => false,
					'stackless_rail' => true,
				]));
				?>
			</nav>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'rail_toggle':
			?>
			<?php if ($stackless_theme_rail && stackless_theme_part_start('header_rail_toggle')) : ?>
			<button type="button" class="header-action site-rail-toggle" aria-controls="site-rail" aria-expanded="true" data-st-rail-toggle>
				<span class="site-rail-toggle__collapse"><?php stackless_theme_the_icon('square-arrow-left', 22); ?></span>
				<span class="site-rail-toggle__expand"><?php stackless_theme_the_icon('square-arrow-right', 22); ?></span>
				<span class="site-rail-toggle__close"><?php stackless_theme_the_icon('x', 22); ?></span>
				<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('sidebar', __('Sidebar', 'stackless-theme'))); ?></span>
			</button>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'account_menu':
			$stackless_theme_account_url = stackless_theme_account_url();
			?>
			<?php if (stackless_theme_part_start('header_account_menu', ['url' => $stackless_theme_account_url])) : ?>
			<div class="<?php echo stackless_theme_class('header_account_menu', ['site-account']); ?>">
				<?php if (is_user_logged_in()) : ?>
				<?php $stackless_theme_user = wp_get_current_user(); ?>
				<button type="button" class="site-account__toggle" aria-controls="site-account-menu" aria-expanded="false" data-st-account>
					<?php echo get_avatar($stackless_theme_user->ID, 32, '', '', ['class' => 'site-account__avatar']); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core avatar markup. ?>
					<span class="site-account__name menu-item-label"><?php echo esc_html($stackless_theme_user->display_name); ?></span>
					<?php stackless_theme_the_icon('chevron-up', 16); ?>
				</button>
				<div id="site-account-menu" class="site-account__menu" hidden>
					<?php
					if (has_nav_menu('account')) {
						wp_nav_menu((array) apply_filters('stackless_theme_account_menu_args', [
							'theme_location' => 'account',
							'menu_id' => 'account-menu',
							'container' => false,
							'depth' => 1,
							'fallback_cb' => false,
						]));
					} else {
						echo '<ul id="account-menu" class="menu">';
						if ($stackless_theme_account_url !== '') {
							printf('<li class="menu-item"><a href="%1$s">%2$s</a></li>', esc_url($stackless_theme_account_url), esc_html(stackless_theme_text('my_account', __('My account', 'stackless-theme'))));
						}
						printf('<li class="menu-item"><a href="%1$s">%2$s</a></li>', esc_url(wp_logout_url(home_url('/'))), esc_html(stackless_theme_text('log_out', __('Log out', 'stackless-theme'))));
						echo '</ul>';
					}
					?>
				</div>
				<?php else : ?>
				<a class="site-account__signin" href="<?php echo esc_url($stackless_theme_account_url !== '' ? $stackless_theme_account_url : wp_login_url()); ?>">
					<?php stackless_theme_the_icon('login', 20); ?>
					<span class="menu-item-label"><?php echo esc_html(stackless_theme_text('sign_in', __('Sign in', 'stackless-theme'))); ?></span>
				</a>
				<?php endif; ?>
			</div>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
		case 'menu_toggle':
			?>
			<?php if (stackless_theme_part_start('header_menu_toggle')) : ?>
			<button type="button" class="header-action site-nav-toggle" aria-controls="site-navigation" aria-expanded="false" data-st-toggle="nav">
				<span class="site-nav-toggle__open"><?php stackless_theme_the_icon('menu-2', 22); ?></span>
				<span class="site-nav-toggle__close"><?php stackless_theme_the_icon('x', 22); ?></span>
				<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('menu', __('Menu', 'stackless-theme'))); ?></span>
			</button>
			<?php stackless_theme_part_end(); endif; ?>
<?php
			break;
	}
};
?>
<?php if ($stackless_theme_rail) : ?>
<?php
/*
 * Sidebar layout: the header holds a slim top bar and the rail (#site-rail). Above 1080px the rail
 * is fixed at the left and the page is offset by its width; below, it is a drawer that the bar's
 * Menu button opens. The rail's groups: rail_top, rail_nav and rail_list scroll together between
 * rail_top and rail_bottom.
 */
?>
<?php if (stackless_theme_part_start('header')) : ?>
<header class="<?php echo stackless_theme_class('header', ['site-header', 'site-header--sidebar']); ?>">
	<div class="<?php echo stackless_theme_class('header_inner', ['container', 'site-header__inner', 'site-bar']); ?>">
		<?php do_action('stackless_theme_header_start'); ?>
		<button type="button" class="header-action site-rail-open" aria-controls="site-rail" aria-expanded="false" data-st-rail-toggle>
			<?php stackless_theme_the_icon('menu-2', 22); ?>
			<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('menu', __('Menu', 'stackless-theme'))); ?></span>
		</button>
		<div class="site-bar__brand">
			<?php
			if (has_custom_logo()) {
				the_custom_logo();
			} else {
				printf('<a class="site-bar__title" href="%1$s" rel="home">%2$s</a>', esc_url(home_url('/')), esc_html(get_bloginfo('name')));
			}
			?>
		</div>
		<?php if (stackless_theme_part_start('header_actions')) : ?>
		<div class="<?php echo stackless_theme_class('header_actions', ['site-header__actions']); ?>">
			<?php do_action('stackless_theme_header_actions_start'); ?>
<?php
	foreach ($stackless_theme_layout['bar'] as $stackless_theme_slug) {
		$stackless_theme_header_part($stackless_theme_slug);
	}
	?>
			<?php do_action('stackless_theme_header_actions_end'); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php do_action('stackless_theme_header_end'); ?>
	</div>
	<?php if (stackless_theme_part_start('header_rail')) : ?>
	<div id="site-rail" class="<?php echo stackless_theme_class('header_rail', ['site-rail']); ?>">
		<?php do_action('stackless_theme_header_rail_start'); ?>
<?php
	foreach (['rail_top' => 'site-rail__top', 'rail_nav' => 'site-rail__body', 'rail_bottom' => 'site-rail__bottom'] as $stackless_theme_slot => $stackless_theme_slot_class) {
		$stackless_theme_slugs = $stackless_theme_slot === 'rail_nav' ? array_merge($stackless_theme_layout['rail_nav'], $stackless_theme_layout['rail_list']) : $stackless_theme_layout[$stackless_theme_slot];
		if ($stackless_theme_slugs === []) {
			continue;
		}
		echo '<div class="' . esc_attr($stackless_theme_slot_class) . '">' . "\n";
		foreach ($stackless_theme_slugs as $stackless_theme_slug) {
			$stackless_theme_header_part($stackless_theme_slug);
		}
		echo "</div>\n";
	}
	?>
		<?php do_action('stackless_theme_header_rail_end'); ?>
	</div>
	<?php stackless_theme_part_end(); endif; ?>
</header>
<?php stackless_theme_part_end(); endif; ?>
<?php else : ?>
<?php if (stackless_theme_part_start('header')) : ?>
<header class="<?php echo stackless_theme_class('header', ['site-header', $stackless_theme_center ? 'site-header--has-center' : '', $stackless_theme_right_menu ? 'site-header--split' : '']); ?>">
	<div class="<?php echo stackless_theme_class('header_inner', ['container', 'site-header__inner']); ?>">
		<?php do_action('stackless_theme_header_start'); ?>
<?php
$stackless_theme_first = true;
if ($stackless_theme_center) {
	// Start and center get a column each, so the center group sits in the middle of the bar.
	foreach (['start', 'center'] as $stackless_theme_slot) {
		echo '<div class="' . esc_attr('site-header__' . $stackless_theme_slot) . '">' . "\n";
		foreach ($stackless_theme_layout[$stackless_theme_slot] as $stackless_theme_slug) {
			$stackless_theme_header_part($stackless_theme_slug);
		}
		echo "</div>\n";
	}
	$stackless_theme_first = false;
} else {
	foreach ($stackless_theme_layout['start'] as $stackless_theme_slug) {
		echo $stackless_theme_first ? '' : "\n";
		$stackless_theme_first = false;
		$stackless_theme_header_part($stackless_theme_slug);
	}
}
$stackless_theme_actions = $stackless_theme_center || $stackless_theme_layout['end'] !== []
	|| has_action('stackless_theme_header_actions_start') || has_action('stackless_theme_header_actions_end');
if ($stackless_theme_actions) :
	echo $stackless_theme_first ? '' : "\n";
	?>
		<?php if (stackless_theme_part_start('header_actions')) : ?>
		<div class="<?php echo stackless_theme_class('header_actions', ['site-header__actions']); ?>">
			<?php do_action('stackless_theme_header_actions_start'); ?>
<?php
	foreach ($stackless_theme_layout['end'] as $stackless_theme_slug) {
		$stackless_theme_header_part($stackless_theme_slug);
	}
	?>
			<?php do_action('stackless_theme_header_actions_end'); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
<?php endif; ?>
		<?php do_action('stackless_theme_header_end'); ?>
	</div>
</header>
<?php stackless_theme_part_end(); endif; ?>
<?php endif; ?>

<?php
if (stackless_theme_option('stackless_header_search')) {
	stackless_theme_template_part('template-parts/header', 'search');
}
?>
