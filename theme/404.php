<?php
/**
 * Not found. Part: not_found (inside the main element; the Page Blocks 404 region replaces it).
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'container'], ['template' => '404']); ?>">
	<?php if (!stackless_theme_region('404')) : ?>
	<?php if (stackless_theme_part_start('not_found')) : ?>
	<section class="empty-state">
		<span class="empty-state__icon"><?php stackless_theme_the_icon('alert-circle', 40); ?></span>
		<p class="empty-state__eyebrow">404</p>
		<h1 class="empty-state__title"><?php echo esc_html(stackless_theme_text('not_found_title', __('This page could not be found', 'stackless-theme'))); ?></h1>
		<p><?php echo esc_html(stackless_theme_text('not_found_text', __('The link may be old or mistyped. Try a search, or head back to the home page.', 'stackless-theme'))); ?></p>
		<?php get_search_form(); ?>
		<p><a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php stackless_theme_the_icon('arrow-left', 18); ?> <?php echo esc_html(stackless_theme_text('back_home', __('Back to home', 'stackless-theme'))); ?></a></p>
	</section>
	<?php stackless_theme_part_end(); endif; ?>
	<?php endif; ?>
</main>
<?php
get_footer();
