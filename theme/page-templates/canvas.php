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
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'site-main--full', 'site-main--canvas'], ['template' => 'canvas']); ?>">
	<?php
	while (have_posts()) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('entry', ['entry', 'entry--full'], ['template' => 'canvas'])); ?>>
			<?php do_action('stackless_theme_before_entry_content', ['template' => 'canvas']); ?>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
			<?php do_action('stackless_theme_after_entry_content', ['template' => 'canvas']); ?>
		</article>
		<?php
	}
	?>
</main>
<?php
get_footer();
