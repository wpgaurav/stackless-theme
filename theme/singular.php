<?php
/**
 * Single posts and pages. A blog post can sit beside a sidebar and open with a full-width featured
 * image (Customize > Stackless Theme > Single posts, or the post's own Layout panel); related
 * posts then run full width below. Pages and custom post types get the same sidebar and image
 * when their own Layout panel picks them.
 *
 * Parts (see inc/hooks.php): post_hero, single_layout, single_sidebar, related_posts, comments,
 * plus the post and entry parts in template-parts/content-post.php and content-single.php.
 * Actions: stackless_theme_after_singular (after a single's content, before comments).
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
$stackless_theme_sidebar = function_exists('stackless_theme_single_has_sidebar') && stackless_theme_single_has_sidebar();
$stackless_theme_ctx = ['template' => 'singular', 'post_type' => (string) get_post_type(get_queried_object_id()), 'sidebar' => $stackless_theme_sidebar];
?>
<?php stackless_theme_before_content(); ?>
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main'], $stackless_theme_ctx); ?>">
	<?php
	if (function_exists('stackless_theme_single_hero')) {
		stackless_theme_single_hero();
	}
	while (have_posts()) {
		the_post();
		if ($stackless_theme_sidebar && stackless_theme_part_start('single_layout', $stackless_theme_ctx)) {
			?>
			<div class="<?php echo stackless_theme_class('single_layout', ['single-layout', 'container', 'single-layout--' . stackless_theme_single_layout()], $stackless_theme_ctx); ?>">
				<div class="single-layout__main">
					<?php
					stackless_theme_template_part('template-parts/content', get_post_type() === 'post' ? 'post' : 'single');
					if (comments_open() || get_comments_number()) {
						comments_template();
					}
					?>
				</div>
				<?php
				if (stackless_theme_part_start('single_sidebar', $stackless_theme_ctx)) {
					get_sidebar('single');
					stackless_theme_part_end();
				}
				?>
			</div>
			<?php
			stackless_theme_part_end();
			if (get_post_type() === 'post') {
				stackless_theme_part('related_posts', static function (): void {
					stackless_theme_template_part('template-parts/related', 'posts');
				}, $stackless_theme_ctx);
			}
			/** Fires after a single's content, before comments. Custom post type related items hook in here. */
			do_action('stackless_theme_after_singular');
			continue;
		}

		stackless_theme_template_part('template-parts/content', get_post_type() === 'post' ? 'post' : 'single');
		if (get_post_type() === 'post') {
			stackless_theme_part('related_posts', static function (): void {
				stackless_theme_template_part('template-parts/related', 'posts');
			}, $stackless_theme_ctx);
		}
		/** Fires after a single's content, before comments. Custom post type related items hook in here. */
		do_action('stackless_theme_after_singular');
		if (comments_open() || get_comments_number()) {
			comments_template();
		}
	}
	?>
</main>
<?php
stackless_theme_after_content();
get_footer();
