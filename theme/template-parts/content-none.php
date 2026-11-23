<?php
/**
 * Nothing found. A search with no results also points at the store: Browse all products and the
 * product categories that have something in them. Part: empty_state; inside actions
 * stackless_theme_empty_state_start/_end.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_shop = is_search() && stackless_theme_option('stackless_store_search_links') ? stackless_theme_shop_url() : '';
$stackless_theme_terms = $stackless_theme_shop !== '' ? stackless_theme_store_terms_with_products(6) : [];
?>
<?php if (stackless_theme_part_start('empty_state', ['search' => is_search()])) : ?>
<section class="<?php echo stackless_theme_class('empty_state', ['empty-state']); ?>">
	<?php do_action('stackless_theme_empty_state_start'); ?>
	<span class="empty-state__icon"><?php stackless_theme_the_icon('search', 40); ?></span>
	<h2 class="empty-state__title"><?php echo esc_html(stackless_theme_text('empty_title', __('Nothing here yet', 'stackless-theme'))); ?></h2>
	<p>
		<?php
		is_search()
			? print esc_html(stackless_theme_text('empty_search_text', __('No results matched your search. Try different words.', 'stackless-theme')))
			: print esc_html(stackless_theme_text('empty_text', __('There is nothing to show here yet. A search might help.', 'stackless-theme')));
		?>
	</p>
	<?php get_search_form(); ?>
	<?php if ($stackless_theme_shop !== '') : ?>
		<div class="empty-state__browse">
			<a class="button" href="<?php echo esc_url($stackless_theme_shop); ?>"><?php echo esc_html(stackless_theme_text('browse_all_products', __('Browse all products', 'stackless-theme'))); ?></a>
			<?php if ($stackless_theme_terms) : ?>
				<nav class="empty-state__links" aria-label="<?php esc_attr_e('Product categories', 'stackless-theme'); ?>">
					<?php foreach ($stackless_theme_terms as $stackless_theme_term) : ?>
						<a href="<?php echo esc_url((string) get_term_link($stackless_theme_term)); ?>"><?php echo esc_html($stackless_theme_term->name); ?></a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php do_action('stackless_theme_empty_state_end'); ?>
</section>
<?php stackless_theme_part_end(); endif; ?>
