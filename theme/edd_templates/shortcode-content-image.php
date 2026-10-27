<?php
/**
 * [downloads] grid image. EDD prints the 150px thumbnail; the theme's grid cards are wider, so this
 * prints the medium_large size instead (CSS cannot sharpen a 150px image). The title link that
 * follows carries the name, so this link is hidden from screen readers.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

if (has_post_thumbnail(get_the_ID())) : ?>
	<div class="edd_download_image">
		<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php echo get_the_post_thumbnail(get_the_ID(), 'medium_large', ['loading' => 'lazy', 'alt' => '']); ?>
		</a>
	</div>
<?php endif; ?>
