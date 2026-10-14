<?php
/**
 * Full-screen search: a large field, live product results, popular categories and best sellers.
 * Live results come from the WooCommerce Store API; without JavaScript the form submits normally.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_products = class_exists('WooCommerce');
$stackless_theme_terms = $stackless_theme_products
	? get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true, 'parent' => 0, 'orderby' => 'count', 'order' => 'DESC', 'number' => 6, 'exclude' => [(int) get_option('default_product_cat')]])
	: [];
$stackless_theme_popular = $stackless_theme_products
	? wc_get_products(['status' => 'publish', 'visibility' => 'catalog', 'limit' => 4, 'orderby' => 'popularity', 'order' => 'DESC'])
	: [];
?>
<div id="site-search" class="search-overlay" role="dialog" aria-modal="true" aria-labelledby="search-overlay-title" hidden data-st-search>
	<div class="search-overlay__inner container">
		<div class="search-overlay__top">
			<p id="search-overlay-title" class="search-overlay__title"><?php $stackless_theme_products ? esc_html_e('Search the store', 'stackless-theme') : esc_html_e('Search', 'stackless-theme'); ?></p>
			<button type="button" class="search-overlay__close" data-st-close>
				<?php stackless_theme_the_icon('x', 22); ?>
				<span class="screen-reader-text"><?php esc_html_e('Close search', 'stackless-theme'); ?></span>
			</button>
		</div>

		<form role="search" method="get" class="header-search" action="<?php echo esc_url(home_url('/')); ?>">
			<label class="screen-reader-text" for="header-search-field"><?php $stackless_theme_products ? esc_html_e('Search products', 'stackless-theme') : esc_html_e('Search', 'stackless-theme'); ?></label>
			<span class="header-search__icon"><?php stackless_theme_the_icon('search', 24); ?></span>
			<input type="search" id="header-search-field" class="header-search__field" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php echo esc_attr($stackless_theme_products ? __('What are you looking for?', 'stackless-theme') : __('Search…', 'stackless-theme')); ?>" autocomplete="off" aria-controls="search-overlay-results">
			<?php if ($stackless_theme_products) : ?>
				<input type="hidden" name="post_type" value="product">
			<?php endif; ?>
			<button type="submit" class="header-search__submit"><?php esc_html_e('Search', 'stackless-theme'); ?></button>
		</form>

		<section id="search-overlay-results" class="search-results" aria-live="polite" hidden>
			<div class="search-results__head">
				<h2 class="search-overlay__heading" data-st-results-title><?php esc_html_e('Products', 'stackless-theme'); ?></h2>
				<a class="search-results__all" href="#" data-st-results-all><?php esc_html_e('View all results', 'stackless-theme'); ?> <?php stackless_theme_the_icon('arrow-right', 16); ?></a>
			</div>
			<ul class="search-results__list" data-st-results-list></ul>
		</section>

		<div class="search-overlay__discover" data-st-discover>
			<?php if (!is_wp_error($stackless_theme_terms) && $stackless_theme_terms) : ?>
				<section class="search-overlay__section">
					<h2 class="search-overlay__heading"><?php esc_html_e('Popular categories', 'stackless-theme'); ?></h2>
					<div class="search-overlay__chips">
						<?php foreach ($stackless_theme_terms as $stackless_theme_term) : ?>
							<a class="chip" href="<?php echo esc_url(get_term_link($stackless_theme_term)); ?>"><?php echo esc_html($stackless_theme_term->name); ?></a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ($stackless_theme_popular) : ?>
				<section class="search-overlay__section">
					<h2 class="search-overlay__heading"><?php esc_html_e('Best sellers', 'stackless-theme'); ?></h2>
					<ul class="search-results__list search-results__list--grid">
						<?php foreach ($stackless_theme_popular as $stackless_theme_product) : ?>
							<li>
								<a class="search-result" href="<?php echo esc_url($stackless_theme_product->get_permalink()); ?>">
									<span class="search-result__image"><?php echo wp_kses_post($stackless_theme_product->get_image('woocommerce_thumbnail', ['loading' => 'lazy', 'alt' => ''])); ?></span>
									<span class="search-result__text">
										<span class="search-result__name"><?php echo esc_html($stackless_theme_product->get_name()); ?></span>
										<span class="search-result__price"><?php echo function_exists('stackless_theme_price_without_links') ? stackless_theme_price_without_links($stackless_theme_product->get_price_html()) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses-filtered in the helper. ?></span>
									</span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		</div>
	</div>
</div>
