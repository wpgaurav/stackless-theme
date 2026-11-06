<?php
/**
 * "More from the journal": related posts below a single post. Same-category posts
 * come first, topped up with recent posts. The count is set in Customize > Stackless.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_count = function_exists('stackless_theme_related_count') ? stackless_theme_related_count('posts') : 6;
if ($stackless_theme_count < 1) {
	return;
}

$stackless_theme_ids = stackless_theme_related_post_ids(get_the_ID(), $stackless_theme_count);
if (!$stackless_theme_ids) {
	return;
}

$stackless_theme_related = new WP_Query([
	'post_type' => 'post',
	'post__in' => $stackless_theme_ids,
	'orderby' => 'post__in',
	'posts_per_page' => count($stackless_theme_ids),
	'ignore_sticky_posts' => true,
	'no_found_rows' => true,
]);
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
