<?php
/**
 * Downloads archive, download categories and download tags: a category bar and a grid of downloads.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();

$stackless_theme_terms = get_terms(['taxonomy' => 'download_category', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'name']);
$stackless_theme_current = is_tax('download_category') ? (int) get_queried_object_id() : 0;
$stackless_theme_all = get_post_type_archive_link('download');
?>
<main id="main" class="site-main site-main--shop container">
	<header class="page-header">
		<h1 class="page-title">
			<?php
			if (is_tax()) {
				single_term_title();
			} else {
				post_type_archive_title();
			}
			?>
		</h1>
		<?php the_archive_description('<div class="archive-description">', '</div>'); ?>
	</header>

	<?php if (!is_wp_error($stackless_theme_terms) && count($stackless_theme_terms) > 1) : ?>
		<nav class="st-download-filter" aria-label="<?php esc_attr_e('Download categories', 'stackless-theme'); ?>">
			<?php if ($stackless_theme_all) : ?>
				<a class="chip<?php echo $stackless_theme_current ? '' : ' is-current'; ?>" href="<?php echo esc_url($stackless_theme_all); ?>"<?php echo $stackless_theme_current ? '' : ' aria-current="page"'; ?>><?php esc_html_e('All', 'stackless-theme'); ?></a>
			<?php endif; ?>
			<?php foreach ($stackless_theme_terms as $stackless_theme_term) : ?>
				<?php $stackless_theme_is = $stackless_theme_current === (int) $stackless_theme_term->term_id; ?>
				<a class="chip<?php echo $stackless_theme_is ? ' is-current' : ''; ?>" href="<?php echo esc_url(get_term_link($stackless_theme_term)); ?>"<?php echo $stackless_theme_is ? ' aria-current="page"' : ''; ?>><?php echo esc_html($stackless_theme_term->name); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

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
