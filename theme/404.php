<?php
/**
 * Not found.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<main id="main" class="site-main container">
	<section class="empty-state">
		<span class="empty-state__icon"><?php stackless_theme_the_icon('alert-circle', 40); ?></span>
		<p class="empty-state__eyebrow">404</p>
		<h1 class="empty-state__title"><?php esc_html_e('This page could not be found', 'stackless-theme'); ?></h1>
		<p><?php esc_html_e('The link may be old or mistyped. Try a search, or head back to the home page.', 'stackless-theme'); ?></p>
		<?php get_search_form(); ?>
		<p><a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php stackless_theme_the_icon('arrow-left', 18); ?> <?php esc_html_e('Back to home', 'stackless-theme'); ?></a></p>
	</section>
</main>
<?php
get_footer();
