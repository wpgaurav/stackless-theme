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
<aside class="<?php echo stackless_theme_class('blog_sidebar', ['blog-sidebar']); ?>" aria-label="<?php echo esc_attr(stackless_theme_text('blog_sidebar_label', __('Blog sidebar', 'stackless-theme'))); ?>">
	<?php do_action('stackless_theme_blog_sidebar_start'); ?>
	<?php stackless_theme_region('sidebar'); ?>
	<?php if (is_active_sidebar('blog')) : ?>
		<?php dynamic_sidebar('blog'); ?>
	<?php else : ?>
		<?php
		// A search with no results already offers a search field in the empty state.
		// The main loop has already run here, so count its posts rather than call have_posts().
		if (!(is_search() && (int) $GLOBALS['wp_query']->post_count === 0)) {
			the_widget('WP_Widget_Search', ['title' => __('Search', 'stackless-theme')], $stackless_theme_widget_args);
		}
		the_widget('WP_Widget_Recent_Posts', ['title' => __('Recent posts', 'stackless-theme'), 'number' => 5], $stackless_theme_widget_args);

		// Search covers the store too, so it lists product categories. Blog pages list post
		// categories. Either list is left out when it has nothing in it.
		$stackless_theme_store_terms = is_search() ? stackless_theme_store_terms_with_products() : [];
		if ($stackless_theme_store_terms) :
			?>
			<section class="widget widget_categories">
				<h2 class="widget-title"><?php esc_html_e('Product categories', 'stackless-theme'); ?></h2>
				<ul>
					<?php foreach ($stackless_theme_store_terms as $stackless_theme_term) : ?>
						<li><a href="<?php echo esc_url((string) get_term_link($stackless_theme_term)); ?>"><?php echo esc_html($stackless_theme_term->name); ?></a> (<?php echo esc_html(number_format_i18n((int) $stackless_theme_term->count)); ?>)</li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
		elseif (get_terms(['taxonomy' => 'category', 'hide_empty' => true, 'number' => 1, 'fields' => 'ids'])) :
			the_widget('WP_Widget_Categories', ['title' => __('Categories', 'stackless-theme'), 'count' => 1], $stackless_theme_widget_args);
		endif;
		?>
	<?php endif; ?>
	<?php do_action('stackless_theme_blog_sidebar_end'); ?>
</aside>
