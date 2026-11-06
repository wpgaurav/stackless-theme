<?php
/**
 * Template Name: Full width (no title)
 * Template Post Type: post, page
 *
 * Header and footer with the content edge to edge: groups, covers and sections run full width,
 * text blocks keep the reading width, wide blocks the wide width.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
stackless_theme_before_content();
?>
<main id="main" class="site-main site-main--full">
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
stackless_theme_after_content();
get_footer();
