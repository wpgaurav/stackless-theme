<?php
/**
 * The site footer: widgets, the footer line, the currency switcher and the footer menu.
 *
 * Parts (see inc/hooks.php): footer, footer_widgets, footer_bottom, footer_info, footer_menu.
 * Inside actions: stackless_theme_footer_start/_end, stackless_theme_footer_bottom_start/_end.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<?php if (stackless_theme_part_start('footer')) : ?>
<footer class="<?php echo stackless_theme_class('footer', ['site-footer']); ?>">
	<div class="container">
		<?php do_action('stackless_theme_footer_start'); ?>
		<?php if (is_active_sidebar('footer') && stackless_theme_part_start('footer_widgets')) : ?>
			<div class="<?php echo stackless_theme_class('footer_widgets', ['footer-widgets']); ?>">
				<?php dynamic_sidebar('footer'); ?>
			</div>
		<?php stackless_theme_part_end(); endif; ?>

		<?php if (stackless_theme_part_start('footer_bottom')) : ?>
		<div class="<?php echo stackless_theme_class('footer_bottom', ['site-footer__bottom']); ?>">
			<?php do_action('stackless_theme_footer_bottom_start'); ?>
			<?php if (stackless_theme_part_start('footer_info')) : ?>
			<p class="site-info">
				<?php echo stackless_theme_footer_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized with wp_kses on save and output. ?>
			</p>
			<?php stackless_theme_part_end(); endif; ?>
			<?php stackless_theme_currency_switcher('footer'); ?>
			<?php if (has_nav_menu('footer') && stackless_theme_part_start('footer_menu')) : ?>
				<nav class="footer-nav" aria-label="<?php echo esc_attr(stackless_theme_text('footer_menu_label', __('Footer', 'stackless-theme'))); ?>">
					<?php wp_nav_menu((array) apply_filters('stackless_theme_footer_menu_args', ['theme_location' => 'footer', 'container' => false, 'depth' => 1])); ?>
				</nav>
			<?php stackless_theme_part_end(); endif; ?>
			<?php do_action('stackless_theme_footer_bottom_end'); ?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php do_action('stackless_theme_footer_end'); ?>
	</div>
</footer>
<?php stackless_theme_part_end(); endif; ?>
