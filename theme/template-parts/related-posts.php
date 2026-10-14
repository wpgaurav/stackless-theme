<?php
/**
 * "More from the journal": up to three recent posts from the same category.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_related = new WP_Query([
	'post_type' => 'post',
	'posts_per_page' => 3,
	'post__not_in' => [get_the_ID()],
	'category__in' => wp_get_post_categories(get_the_ID()),
	'ignore_sticky_posts' => true,
	'no_found_rows' => true,
]);

if (!$stackless_theme_related->have_posts()) {
	return;
}
?>
<section class="related-posts container" aria-labelledby="related-posts-title">
	<h2 id="related-posts-title" class="related-posts__title"><?php esc_html_e('More from the journal', 'stackless-theme'); ?></h2>
	<div class="related-posts__grid">
		<?php
		while ($stackless_theme_related->have_posts()) {
			$stackless_theme_related->the_post();
			?>
			<article class="related-post">
				<a class="related-post__link" href="<?php the_permalink(); ?>">
					<span class="related-post__media">
						<?php
						if (has_post_thumbnail()) {
							the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']);
						}
						?>
					</span>
					<span class="related-post__date"><?php echo esc_html(get_the_date()); ?></span>
					<span class="related-post__title"><?php the_title(); ?></span>
				</a>
			</article>
			<?php
		}
		wp_reset_postdata();
		?>
	</div>
</section>
