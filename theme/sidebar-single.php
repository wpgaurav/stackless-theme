<?php
/**
 * The sidebar beside a blog post (Single posts > Layout). The Page Blocks "Post sidebar" region
 * leads, then the Single post widget area, or the Blog sidebar while that is empty. The Blog
 * sidebar brings the Page Blocks "Sidebar" region with it; the widget path prints it too, so a
 * region placed in Sidebar shows beside posts either way.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_sticky = stackless_theme_option('stackless_single_sidebar_sticky');
?>
<div class="<?php echo stackless_theme_class('single_sidebar', ['single-layout__aside', $stackless_theme_sticky ? 'is-sticky' : '']); ?>">
	<?php do_action('stackless_theme_single_sidebar_start'); ?>
	<?php stackless_theme_region('single_sidebar'); ?>
	<?php if (is_active_sidebar('single')) : ?>
		<aside class="blog-sidebar single-sidebar" aria-label="<?php esc_attr_e('Sidebar', 'stackless-theme'); ?>">
			<?php stackless_theme_region('sidebar'); ?>
			<?php dynamic_sidebar('single'); ?>
		</aside>
	<?php else : ?>
		<?php get_sidebar('blog'); ?>
	<?php endif; ?>
	<?php do_action('stackless_theme_single_sidebar_end'); ?>
</div>
