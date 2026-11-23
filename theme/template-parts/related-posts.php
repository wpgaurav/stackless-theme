<?php
/**
 * Related items below a single (the related_posts part, in singular.php). Filters:
 * stackless_theme_related_posts_query, stackless_theme_related_posts_class. Related items below a single: "More from the journal" for posts (same category first, topped
 * up with recent posts), or a custom post type's own items when it passes $args. The heading and
 * counts are set in Customize > Stackless Theme.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_args = isset($args) && is_array($args) ? $args : [];
$stackless_theme_type = (string) ($stackless_theme_args['post_type'] ?? 'post');
$stackless_theme_count = (int) ($stackless_theme_args['count'] ?? (function_exists('stackless_theme_related_count') ? stackless_theme_related_count('posts') : 6));
if ($stackless_theme_count < 1) {
	return;
}
$stackless_theme_title = (string) ($stackless_theme_args['title'] ?? '');
if ($stackless_theme_title === '') {
	$stackless_theme_title = trim((string) stackless_theme_option('stackless_related_title')) ?: stackless_theme_text('related_posts_title', __('More from the journal', 'stackless-theme'));
}

$stackless_theme_ids = stackless_theme_related_post_ids(get_the_ID(), $stackless_theme_count, $stackless_theme_type);
if (!$stackless_theme_ids) {
	return;
}

$stackless_theme_related = new WP_Query((array) apply_filters('stackless_theme_related_posts_query', [
	'post_type' => $stackless_theme_type,
	'post__in' => $stackless_theme_ids,
	'orderby' => 'post__in',
	'posts_per_page' => count($stackless_theme_ids),
	'ignore_sticky_posts' => true,
	'no_found_rows' => true,
], $stackless_theme_type));
?>
<section class="<?php echo stackless_theme_class('related_posts', ['related-posts', 'container'], ['post_type' => $stackless_theme_type]); ?>" aria-labelledby="related-posts-title">
	<h2 id="related-posts-title" class="related-posts__title"><?php echo esc_html($stackless_theme_title); ?></h2>
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
