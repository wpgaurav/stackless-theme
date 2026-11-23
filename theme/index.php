<?php
/**
 * Blog, archives and search results.
 *
 * Parts (see inc/hooks.php): archive_header, archive_title, archive_description, post_list,
 * pagination, blog_sidebar, empty_state; each card is the post_card part (content-summary.php).
 * $context has the archive type: home, search or archive.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<?php stackless_theme_before_content(); ?>
<?php $stackless_theme_ctx = ['template' => 'index', 'archive' => is_search() ? 'search' : (is_archive() ? 'archive' : 'home')]; ?>
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'container'], $stackless_theme_ctx); ?>">
	<?php if (is_home() && !is_front_page()) : ?>
		<?php if (stackless_theme_part_start('archive_header', $stackless_theme_ctx)) : ?>
		<header class="page-header">
			<h1 class="page-title"><?php single_post_title(); ?></h1>
		</header>
		<?php stackless_theme_part_end(); endif; ?>
	<?php elseif (is_search()) : ?>
		<?php if (stackless_theme_part_start('archive_header', $stackless_theme_ctx)) : ?>
		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query */
				printf(esc_html(stackless_theme_text('search_results_title', __('Results for "%s"', 'stackless-theme'), $stackless_theme_ctx)), esc_html(get_search_query()));
				?>
			</h1>
		</header>
		<?php stackless_theme_part_end(); endif; ?>
	<?php elseif (is_archive()) : ?>
		<?php if (stackless_theme_part_start('archive_header', $stackless_theme_ctx)) : ?>
		<header class="page-header">
			<?php
			if (stackless_theme_part_start('archive_title', $stackless_theme_ctx)) {
				the_archive_title('<h1 class="page-title">', '</h1>');
				stackless_theme_part_end();
			}
			?>
			<?php
			if (stackless_theme_option('stackless_archive_description') && stackless_theme_part_start('archive_description', $stackless_theme_ctx)) {
				the_archive_description('<div class="archive-description">', '</div>');
				stackless_theme_part_end();
			}
			?>
		</header>
		<?php stackless_theme_part_end(); endif; ?>
	<?php endif; ?>

	<?php $stackless_theme_sidebar = function_exists('stackless_theme_has_blog_sidebar') && stackless_theme_has_blog_sidebar(); ?>
	<?php if ($stackless_theme_sidebar) : ?>
		<div class="blog-layout">
		<div class="blog-layout__main">
	<?php endif; ?>

	<?php if (have_posts()) : ?>
		<?php if (stackless_theme_part_start('post_list', $stackless_theme_ctx)) : ?>
		<div class="<?php echo stackless_theme_class('post_list', explode(' ', stackless_theme_post_list_class()), $stackless_theme_ctx); ?>">
			<?php
			while (have_posts()) {
				the_post();
				stackless_theme_template_part('template-parts/content', 'summary');
			}
			?>
		</div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php stackless_theme_part('pagination', 'stackless_theme_pagination', $stackless_theme_ctx); ?>
	<?php else : ?>
		<?php stackless_theme_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>

	<?php if ($stackless_theme_sidebar) : ?>
		</div>
		<?php stackless_theme_part('blog_sidebar', static function (): void {
			get_sidebar('blog');
		}, $stackless_theme_ctx); ?>
		</div>
	<?php endif; ?>
</main>
<?php
stackless_theme_after_content();
get_footer();
