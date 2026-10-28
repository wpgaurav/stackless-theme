<?php
/**
 * Downloads archive, download categories and download tags: a category bar and a grid of downloads.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();

$stackless_theme_current = is_tax('download_category') ? (int) get_queried_object_id() : 0;
?>
<main id="main" class="site-main site-main--shop container">
	<header class="page-header">
		<h1 class="page-title">
			<?php
			if (is_search()) {
				/* translators: %s: search query */
				printf(esc_html__('Results for "%s"', 'stackless-theme'), esc_html(get_search_query()));
			} elseif (is_tax()) {
				single_term_title();
			} else {
				post_type_archive_title();
			}
			?>
		</h1>
		<?php the_archive_description('<div class="archive-description">', '</div>'); ?>
	</header>

	<?php
	if (function_exists('stackless_theme_store_toolbar')) {
		stackless_theme_store_toolbar('edd', $stackless_theme_current);
	}
	?>

	<?php if (have_posts()) : ?>
		<div class="st-downloads">
			<?php
			while (have_posts()) {
				the_post();
				get_template_part('template-parts/content', 'download-card');
			}
			?>
		</div>
		<?php stackless_theme_pagination(); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
