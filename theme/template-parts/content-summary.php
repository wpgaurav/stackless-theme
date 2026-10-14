<?php
/**
 * A post in a list.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('post-card'); ?>>
	<?php if (has_post_thumbnail() && !post_password_required()) : ?>
		<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail('medium_large', ['loading' => 'lazy']); ?>
		</a>
	<?php endif; ?>
	<div class="post-card__body">
		<?php if (get_post_type() === 'post') : ?>
			<div class="entry-meta"><?php stackless_theme_posted_on(); ?></div>
		<?php endif; ?>
		<?php the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>'); ?>
		<div class="entry-summary"><?php the_excerpt(); ?></div>
		<a class="more-link" href="<?php the_permalink(); ?>">
			<?php esc_html_e('Read more', 'stackless-theme'); ?><span class="screen-reader-text"> <?php the_title(); ?></span>
			<?php stackless_theme_the_icon('arrow-right', 16); ?>
		</a>
	</div>
</article>
