<?php
/**
 * A single post or page.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

// The Checkout block (and Stackless Checkout Builder) brings its own heading.
$stackless_theme_show_title = !(function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) && !is_front_page();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('entry'); ?>>
	<?php if ($stackless_theme_show_title) : ?>
		<header class="entry-header">
			<?php if (get_post_type() === 'post') : ?>
				<div class="entry-meta"><?php stackless_theme_posted_on(); ?></div>
			<?php endif; ?>
			<?php the_title('<h1 class="entry-title">', '</h1>'); ?>
		</header>
	<?php endif; ?>

	<?php if (has_post_thumbnail() && !post_password_required() && get_post_type() === 'post') : ?>
		<figure class="entry-media alignwide"><?php the_post_thumbnail('large'); ?></figure>
	<?php endif; ?>

	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages([
			'before' => '<nav class="page-links" aria-label="' . esc_attr__('Page', 'stackless-theme') . '">',
			'after' => '</nav>',
		]);
		?>
	</div>

	<?php if (get_post_type() === 'post') : ?>
		<footer class="entry-footer"><?php stackless_theme_entry_footer(); ?></footer>
		<?php
		the_post_navigation([
			'prev_text' => '<span class="nav-subtitle">' . esc_html__('Previous', 'stackless-theme') . '</span><span class="nav-title">%title</span>',
			'next_text' => '<span class="nav-subtitle">' . esc_html__('Next', 'stackless-theme') . '</span><span class="nav-title">%title</span>',
		]);
		?>
	<?php endif; ?>
</article>
