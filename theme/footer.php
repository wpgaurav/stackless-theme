<?php
/**
 * Site footer.
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
				&copy; <?php echo esc_html(wp_date('Y')); ?> <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
			</p>
			<?php if (has_nav_menu('footer')) : ?>
				<nav class="footer-nav" aria-label="<?php esc_attr_e('Footer', 'stackless-theme'); ?>">
					<?php wp_nav_menu(['theme_location' => 'footer', 'container' => false, 'depth' => 1]); ?>
				</nav>
			<?php endif; ?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
