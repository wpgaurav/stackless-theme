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
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'site-main--full'], ['template' => 'full-width']); ?>">
	<?php
	while (have_posts()) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('entry', ['entry', 'entry--full'], ['template' => 'full-width'])); ?>>
			<?php do_action('stackless_theme_before_entry_content', ['template' => 'full-width']); ?>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
			<?php do_action('stackless_theme_after_entry_content', ['template' => 'full-width']); ?>
		</article>
		<?php
	}
	?>
</main>
<?php
stackless_theme_after_content();
get_footer();
