<?php
/**
 * Template Name: Canvas (no header or footer)
 * Template Post Type: post, page
 *
 * Only the content, edge to edge, for landing pages that bring their own header and footer.
 * Scripts and styles still load through wp_head() and wp_footer().
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" class="site-main site-main--full site-main--canvas">
	<?php
	while (have_posts()) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class('entry entry--full'); ?>>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	}
	?>
</main>
<?php
get_footer();
