<?php
/**
 * Single posts and pages.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<?php stackless_theme_before_content(); ?>
<main id="main" class="site-main">
	<?php
	while (have_posts()) {
		the_post();
		get_template_part('template-parts/content', get_post_type() === 'post' ? 'post' : 'single');
		if (get_post_type() === 'post') {
			get_template_part('template-parts/related', 'posts');
		}
		if (comments_open() || get_comments_number()) {
			comments_template();
		}
	}
	?>
</main>
<?php
stackless_theme_after_content();
get_footer();
