<?php
/**
 * Site footer.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<?php
if (stackless_theme_shows('footer')) {
	stackless_theme_chrome('footer', static function (): void {
		get_template_part('template-parts/site', 'footer');
	});
}
?>
<?php wp_footer(); ?>
</body>
</html>
