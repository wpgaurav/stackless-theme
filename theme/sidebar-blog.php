<?php
/**
 * Blog sidebar beside the post list. Shows the Blog widget area, or search,
 * recent posts and categories until widgets are added.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_widget_args = [
    'before_widget' => '<section class="widget %s">',
    'after_widget' => '</section>',
    'before_title' => '<h2 class="widget-title">',
    'after_title' => '</h2>',
];
?>
<aside class="blog-sidebar" aria-label="<?php esc_attr_e('Blog sidebar', 'stackless-theme'); ?>">
	<?php if (is_active_sidebar('blog')) : ?>
		<?php dynamic_sidebar('blog'); ?>
	<?php else : ?>
		<?php
		the_widget('WP_Widget_Search', ['title' => __('Search', 'stackless-theme')], $stackless_theme_widget_args);
		the_widget('WP_Widget_Recent_Posts', ['title' => __('Recent posts', 'stackless-theme'), 'number' => 5], $stackless_theme_widget_args);
		the_widget('WP_Widget_Categories', ['title' => __('Categories', 'stackless-theme'), 'count' => 1], $stackless_theme_widget_args);
		?>
	<?php endif; ?>
</aside>
