<?php
/**
 * A single page or custom post type item.
 *
 * Parts (see inc/hooks.php): entry, entry_header, entry_meta, entry_title, entry_media,
 * entry_content, entry_footer, entry_navigation. $context has the post ID, the post type and
 * whether it is a custom post type single.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

// The Checkout block (and Stackless Checkout Builder) brings its own heading.
$stackless_theme_show_title = !(function_exists('is_checkout') && is_checkout() && !is_wc_endpoint_url()) && !is_front_page()
	&& (!is_singular() || (int) get_the_ID() !== (int) get_queried_object_id() || stackless_theme_shows('title'));

// Custom post types show their date, image and post links as set in Customize > Stackless Theme > Custom post types.
$stackless_theme_type = (string) get_post_type();
$stackless_theme_cpt = (int) get_the_ID() === (int) get_queried_object_id() && stackless_theme_is_custom_single();
$stackless_theme_show_meta = $stackless_theme_type === 'post' || ($stackless_theme_cpt && stackless_theme_cpt_option($stackless_theme_type, 'meta'));
$stackless_theme_show_image = $stackless_theme_type === 'post' || ($stackless_theme_cpt && stackless_theme_cpt_option($stackless_theme_type, 'image'));
$stackless_theme_show_nav = $stackless_theme_type === 'post' || ($stackless_theme_cpt && stackless_theme_cpt_option($stackless_theme_type, 'nav'));
$stackless_theme_ctx = ['post_id' => (int) get_the_ID(), 'post_type' => $stackless_theme_type, 'custom_post_type' => $stackless_theme_cpt];
?>
<?php if (stackless_theme_part_start('entry', $stackless_theme_ctx)) : ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('entry', ['entry'], $stackless_theme_ctx)); ?>>
	<?php
	if ($stackless_theme_cpt) {
		stackless_theme_maybe_breadcrumbs();
	}
	?>
	<?php if ($stackless_theme_show_title && stackless_theme_part_start('entry_header', $stackless_theme_ctx)) : ?>
		<header class="entry-header">
			<?php if ($stackless_theme_show_meta && stackless_theme_part_start('entry_meta', $stackless_theme_ctx)) : ?>
				<div class="entry-meta"><?php stackless_theme_posted_on(); ?></div>
			<?php stackless_theme_part_end(); endif; ?>
			<?php
			if (stackless_theme_part_start('entry_title', $stackless_theme_ctx)) {
				the_title('<h1 class="entry-title">', '</h1>');
				stackless_theme_part_end();
			}
			?>
		</header>
	<?php stackless_theme_part_end(); endif; ?>

	<?php if (has_post_thumbnail() && !post_password_required() && $stackless_theme_show_image && stackless_theme_part_start('entry_media', $stackless_theme_ctx)) : ?>
		<figure class="entry-media alignwide"><?php the_post_thumbnail((string) apply_filters('stackless_theme_entry_media_size', 'large', $stackless_theme_ctx)); ?></figure>
	<?php stackless_theme_part_end(); endif; ?>

	<?php if (stackless_theme_part_start('entry_content', $stackless_theme_ctx)) : ?>
	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages([
			'before' => '<nav class="page-links" aria-label="' . esc_attr(stackless_theme_text('page_links_label', __('Page', 'stackless-theme'))) . '">',
			'after' => '</nav>',
		]);
		?>
	</div>
	<?php stackless_theme_part_end(); endif; ?>

	<?php if ($stackless_theme_type === 'post' && stackless_theme_part_start('entry_footer', $stackless_theme_ctx)) : ?>
		<footer class="entry-footer"><?php stackless_theme_entry_footer(); ?></footer>
	<?php stackless_theme_part_end(); endif; ?>
	<?php if ($stackless_theme_show_nav && stackless_theme_part_start('entry_navigation', $stackless_theme_ctx)) : ?>
		<?php
		the_post_navigation((array) apply_filters('stackless_theme_post_navigation_args', [
			'prev_text' => '<span class="nav-subtitle">' . esc_html(stackless_theme_text('previous', __('Previous', 'stackless-theme'))) . '</span><span class="nav-title">%title</span>',
			'next_text' => '<span class="nav-subtitle">' . esc_html(stackless_theme_text('next', __('Next', 'stackless-theme'))) . '</span><span class="nav-title">%title</span>',
		], $stackless_theme_ctx));
		?>
	<?php stackless_theme_part_end(); endif; ?>
</article>
<?php stackless_theme_part_end(); endif; ?>
