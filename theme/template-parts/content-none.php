<?php
/**
 * Nothing found.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?>
<section class="empty-state">
	<span class="empty-state__icon"><?php stackless_theme_the_icon('search', 40); ?></span>
	<h2 class="empty-state__title"><?php esc_html_e('Nothing here yet', 'stackless-theme'); ?></h2>
	<p>
		<?php
		is_search()
			? esc_html_e('No results matched your search. Try different words.', 'stackless-theme')
			: esc_html_e('There is nothing to show here yet. A search might help.', 'stackless-theme');
		?>
	</p>
	<?php get_search_form(); ?>
</section>
