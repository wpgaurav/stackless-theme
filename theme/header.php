<?php
/**
 * Site header.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'stackless-theme'); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<div class="site-brand">
			<?php stackless_theme_site_brand(); ?>
		</div>

		<nav id="site-navigation" class="site-nav" aria-label="<?php esc_attr_e('Primary', 'stackless-theme'); ?>" data-st-nav>
			<?php
			wp_nav_menu([
				'theme_location' => 'primary',
				'menu_id' => 'primary-menu',
				'container' => false,
				'depth' => 2,
				'fallback_cb' => 'stackless_theme_menu_fallback',
			]);
			?>
		</nav>

		<div class="site-header__actions">
			<button type="button" class="header-action" aria-controls="site-search" aria-expanded="false" data-st-toggle="search">
				<?php stackless_theme_the_icon('search', 22); ?>
				<span class="screen-reader-text"><?php esc_html_e('Search', 'stackless-theme'); ?></span>
			</button>
			<?php if (function_exists('wc_get_page_permalink') && wc_get_page_id('myaccount') > 0) : ?>
				<a class="header-action" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">
					<?php stackless_theme_the_icon('user', 22); ?>
					<span class="screen-reader-text"><?php is_user_logged_in() ? esc_html_e('My account', 'stackless-theme') : esc_html_e('Sign in', 'stackless-theme'); ?></span>
				</a>
			<?php endif; ?>
			<?php echo stackless_theme_cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?>
			<button type="button" class="header-action site-nav-toggle" aria-controls="site-navigation" aria-expanded="false" data-st-toggle="nav">
				<span class="site-nav-toggle__open"><?php stackless_theme_the_icon('menu-2', 22); ?></span>
				<span class="site-nav-toggle__close"><?php stackless_theme_the_icon('x', 22); ?></span>
				<span class="screen-reader-text"><?php esc_html_e('Menu', 'stackless-theme'); ?></span>
			</button>
		</div>
	</div>

</header>

<?php get_template_part('template-parts/header', 'search'); ?>
