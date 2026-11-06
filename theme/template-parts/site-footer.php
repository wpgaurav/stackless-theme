<?php
/**
 * The site footer: widgets, the footer line, the currency switcher and the footer menu.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<footer class="site-footer">
	<div class="container">
		<?php if (is_active_sidebar('footer')) : ?>
			<div class="footer-widgets">
				<?php dynamic_sidebar('footer'); ?>
			</div>
		<?php endif; ?>

		<div class="site-footer__bottom">
			<p class="site-info">
				<?php echo stackless_theme_footer_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized with wp_kses on save and output. ?>
			</p>
			<?php stackless_theme_currency_switcher('footer'); ?>
			<?php if (has_nav_menu('footer')) : ?>
				<nav class="footer-nav" aria-label="<?php esc_attr_e('Footer', 'stackless-theme'); ?>">
					<?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'depth' => 1]); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</footer>
