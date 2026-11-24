<?php
/**
 * The site header: brand, primary menu, search, account and cart, and the search overlay.
 *
 * Parts (see inc/hooks.php): header, header_brand, header_nav, header_actions,
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

/*
 * One header part by its layout slug. Each branch keeps the indentation it had in the fixed header,
 * so the default layout's markup is unchanged byte for byte.
 */
$stackless_theme_header_part = static function (string $slug): void {
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
			]));
			stackless_theme_currency_switcher('menu');
			?>
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
<?php if (stackless_theme_part_start('header')) : ?>
<header class="<?php echo stackless_theme_class('header', ['site-header', $stackless_theme_center ? 'site-header--has-center' : '']); ?>">
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

<?php
if (stackless_theme_option('stackless_header_search')) {
	stackless_theme_template_part('template-parts/header', 'search');
}
?>
