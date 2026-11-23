<?php
/**
 * Downloads archive, download categories and download tags: a category bar and a grid of downloads.
 *
 * Parts (see inc/hooks.php): archive_header, store_toolbar, product_grid, product_card (each
 * card, content-download-card.php), pagination. $context['store'] is 'edd'.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();

$stackless_theme_current = is_tax('download_category') ? (int) get_queried_object_id() : 0;
?>
<?php stackless_theme_before_content(); ?>
<?php $stackless_theme_ctx = ['store' => 'edd', 'template' => 'archive-download', 'term' => $stackless_theme_current]; ?>
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'site-main--shop', 'container'], $stackless_theme_ctx); ?>">
	<?php if (stackless_theme_part_start('archive_header', $stackless_theme_ctx)) : ?>
	<header class="page-header">
		<h1 class="page-title">
			<?php
			if (is_search()) {
				/* translators: %s: search query */
				printf(esc_html(stackless_theme_text('search_results_title', __('Results for "%s"', 'stackless-theme'), $stackless_theme_ctx)), esc_html(get_search_query()));
			} elseif (is_tax()) {
				single_term_title();
			} else {
				post_type_archive_title();
			}
			?>
		</h1>
		<?php the_archive_description('<div class="archive-description">', '</div>'); ?>
	</header>
	<?php stackless_theme_part_end(); endif; ?>

	<?php
	stackless_theme_region('store_top');
	if (function_exists('stackless_theme_store_toolbar')) {
		stackless_theme_store_toolbar('edd', $stackless_theme_current);
	}
	?>

	<?php if (have_posts()) : ?>
		<?php if (stackless_theme_part_start('product_grid', $stackless_theme_ctx)) : ?>
		<div class="<?php echo stackless_theme_class('product_grid', ['st-downloads'], $stackless_theme_ctx); ?>">
			<?php
			while (have_posts()) {
				the_post();
				stackless_theme_template_part('template-parts/content', 'download-card');
			}
			?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php stackless_theme_part('pagination', 'stackless_theme_pagination', $stackless_theme_ctx); ?>
	<?php else : ?>
		<?php stackless_theme_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>
</main>
<?php
stackless_theme_after_content();
get_footer();
