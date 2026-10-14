<?php
/**
 * Blog, archives and search results.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" class="site-main container">
	<?php if (is_home() && !is_front_page()) : ?>
		<header class="page-header">
			<h1 class="page-title"><?php single_post_title(); ?></h1>
		</header>
	<?php elseif (is_search()) : ?>
		<header class="page-header">
			<h1 class="page-title">
				<?php
				/* translators: %s: search query */
				printf(esc_html__('Results for "%s"', 'stackless-theme'), esc_html(get_search_query()));
				?>
			</h1>
		</header>
	<?php elseif (is_archive()) : ?>
		<header class="page-header">
			<?php the_archive_title('<h1 class="page-title">', '</h1>'); ?>
			<?php the_archive_description('<div class="archive-description">', '</div>'); ?>
		</header>
	<?php endif; ?>

	<?php $stackless_theme_sidebar = function_exists('stackless_theme_has_blog_sidebar') && stackless_theme_has_blog_sidebar(); ?>
	<?php if ($stackless_theme_sidebar) : ?>
		<div class="blog-layout">
		<div class="blog-layout__main">
	<?php endif; ?>

	<?php if (have_posts()) : ?>
		<div class="post-list">
			<?php
			while (have_posts()) {
				the_post();
				get_template_part('template-parts/content', 'summary');
			}
			?>
		</div>
		<?php stackless_theme_pagination(); ?>
	<?php else : ?>
		<?php get_template_part('template-parts/content', 'none'); ?>
	<?php endif; ?>

	<?php if ($stackless_theme_sidebar) : ?>
		</div>
		<?php get_sidebar('blog'); ?>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
