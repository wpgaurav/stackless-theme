<?php
/**
 * A single Easy Digital Downloads product: image and description on the left, a sticky summary with
 * price, price options and the purchase button on the right, related downloads below.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" class="site-main site-main--shop container">
	<?php
	while (have_posts()) {
		the_post();
		$stackless_theme_id = get_the_ID();
		$stackless_theme_categories = get_the_terms($stackless_theme_id, 'download_category');
		$stackless_theme_category = is_array($stackless_theme_categories) ? $stackless_theme_categories[0] : null;
		$stackless_theme_archive = get_post_type_archive_link('download');
		?>
		<nav class="st-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'stackless-theme'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'stackless-theme'); ?></a>
			<?php if ($stackless_theme_archive) : ?>
				<span class="breadcrumb-sep" aria-hidden="true">/</span>
				<a href="<?php echo esc_url($stackless_theme_archive); ?>"><?php echo esc_html(function_exists('edd_get_label_plural') ? edd_get_label_plural() : __('Downloads', 'stackless-theme')); ?></a>
			<?php endif; ?>
			<?php if ($stackless_theme_category) : ?>
				<span class="breadcrumb-sep" aria-hidden="true">/</span>
				<a href="<?php echo esc_url(get_term_link($stackless_theme_category)); ?>"><?php echo esc_html($stackless_theme_category->name); ?></a>
			<?php endif; ?>
			<span class="breadcrumb-sep" aria-hidden="true">/</span>
			<span aria-current="page"><?php the_title(); ?></span>
		</nav>

		<article id="post-<?php the_ID(); ?>" <?php post_class('st-download' . (has_post_thumbnail() ? ' has-media' : '')); ?>>
			<?php if (has_post_thumbnail() && !post_password_required()) : ?>
				<figure class="st-download__media"><?php the_post_thumbnail('large', ['fetchpriority' => 'high']); ?></figure>
			<?php endif; ?>

			<div class="st-download__summary">
				<?php the_title('<h1 class="st-download__title">', '</h1>'); ?>
				<p class="st-download__price"><?php echo stackless_theme_edd_price_html($stackless_theme_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?></p>
				<?php if (has_excerpt()) : ?>
					<div class="st-download__excerpt"><?php the_excerpt(); ?></div>
				<?php endif; ?>
				<div class="st-download__purchase">
					<?php echo stackless_theme_edd_purchase_form($stackless_theme_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD escapes its purchase form. ?>
				</div>
				<?php
				$stackless_theme_cats = get_the_term_list($stackless_theme_id, 'download_category', '', ', ');
				$stackless_theme_tags = get_the_term_list($stackless_theme_id, 'download_tag', '', ', ');
				?>
				<?php if ($stackless_theme_cats || $stackless_theme_tags) : ?>
					<div class="st-download__meta">
						<?php if ($stackless_theme_cats && !is_wp_error($stackless_theme_cats)) : ?>
							<span><?php esc_html_e('Category:', 'stackless-theme'); ?> <?php echo wp_kses_post($stackless_theme_cats); ?></span>
						<?php endif; ?>
						<?php if ($stackless_theme_tags && !is_wp_error($stackless_theme_tags)) : ?>
							<span><?php esc_html_e('Tags:', 'stackless-theme'); ?> <?php echo wp_kses_post($stackless_theme_tags); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="st-download__content entry-content">
				<?php the_content(); ?>
			</div>
		</article>

		<?php
		$stackless_theme_related = stackless_theme_edd_related_downloads($stackless_theme_id, function_exists('stackless_theme_related_count') ? stackless_theme_related_count('downloads') : 6);
		if ($stackless_theme_related) :
			?>
			<section class="st-related-downloads" aria-labelledby="st-related-downloads-title">
				<h2 id="st-related-downloads-title" class="st-related-downloads__title"><?php esc_html_e('You may also like', 'stackless-theme'); ?></h2>
				<div class="st-downloads">
					<?php
					global $post;
					foreach ($stackless_theme_related as $stackless_theme_related_id) {
						$post = get_post($stackless_theme_related_id); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- setup_postdata needs the global.
						setup_postdata($post);
						get_template_part('template-parts/content', 'download-card');
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
			<?php
		endif;

		if (comments_open() || get_comments_number()) {
			comments_template();
		}
	}
	?>
</main>
<?php
get_footer();
