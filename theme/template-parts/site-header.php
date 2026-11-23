<?php
/**
 * The site header: brand, primary menu, search, account and cart, and the search overlay.
 *
 * Parts (see inc/hooks.php): header, header_brand, header_nav, header_actions,
 * header_search_button, header_account, header_cart, header_menu_toggle, search_overlay.
 * Inside actions: stackless_theme_header_start/_end, stackless_theme_header_actions_start/_end.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<?php if (stackless_theme_part_start('header')) : ?>
<header class="<?php echo stackless_theme_class('header', ['site-header']); ?>">
	<div class="<?php echo stackless_theme_class('header_inner', ['container', 'site-header__inner']); ?>">
		<?php do_action('stackless_theme_header_start'); ?>
		<?php if (stackless_theme_part_start('header_brand')) : ?>
		<div class="<?php echo stackless_theme_class('header_brand', ['site-brand']); ?>">
			<?php stackless_theme_site_brand(); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>

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

		<?php if (stackless_theme_part_start('header_actions')) : ?>
		<div class="<?php echo stackless_theme_class('header_actions', ['site-header__actions']); ?>">
			<?php do_action('stackless_theme_header_actions_start'); ?>
			<?php stackless_theme_currency_switcher('header'); ?>
			<?php if (stackless_theme_option('stackless_header_search') && stackless_theme_part_start('header_search_button')) : ?>
				<button type="button" class="header-action" aria-controls="site-search" aria-expanded="false" data-st-toggle="search">
					<?php stackless_theme_the_icon('search', 22); ?>
					<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('search', __('Search', 'stackless-theme'))); ?></span>
				</button>
			<?php stackless_theme_part_end(); endif; ?>
			<?php $stackless_theme_account = stackless_theme_option('stackless_store_header_account') ? stackless_theme_account_url() : ''; ?>
			<?php if ($stackless_theme_account && stackless_theme_part_start('header_account', ['url' => $stackless_theme_account])) : ?>
				<a class="header-action" href="<?php echo esc_url($stackless_theme_account); ?>">
					<?php stackless_theme_the_icon('user', 22); ?>
					<span class="screen-reader-text"><?php echo esc_html(is_user_logged_in() ? stackless_theme_text('my_account', __('My account', 'stackless-theme')) : stackless_theme_text('sign_in', __('Sign in', 'stackless-theme'))); ?></span>
				</a>
			<?php stackless_theme_part_end(); endif; ?>
			<?php
			if (stackless_theme_option('stackless_store_header_cart') && stackless_theme_part_start('header_cart')) {
				echo stackless_theme_cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
				stackless_theme_part_end();
			}
			?>
			<?php if (stackless_theme_part_start('header_menu_toggle')) : ?>
			<button type="button" class="header-action site-nav-toggle" aria-controls="site-navigation" aria-expanded="false" data-st-toggle="nav">
				<span class="site-nav-toggle__open"><?php stackless_theme_the_icon('menu-2', 22); ?></span>
				<span class="site-nav-toggle__close"><?php stackless_theme_the_icon('x', 22); ?></span>
				<span class="screen-reader-text"><?php echo esc_html(stackless_theme_text('menu', __('Menu', 'stackless-theme'))); ?></span>
			</button>
			<?php stackless_theme_part_end(); endif; ?>
			<?php do_action('stackless_theme_header_actions_end'); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php do_action('stackless_theme_header_end'); ?>
	</div>
</header>
<?php stackless_theme_part_end(); endif; ?>

<?php
if (stackless_theme_option('stackless_header_search')) {
	stackless_theme_template_part('template-parts/header', 'search');
}
?>
