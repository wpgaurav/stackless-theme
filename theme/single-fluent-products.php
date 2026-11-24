<?php
/**
 * A single FluentCart product, laid out like a WooCommerce product or an EDD download: breadcrumb,
 * the gallery on the left, a sticky summary with price, options and the buy buttons on the right,
 * the description below and related products after it.
 *
 * FluentCart's own renderer prints the gallery, prices and buy section, so variations, quantity,
 * Buy Now, Add to Cart and add-ons such as wishlists keep working as FluentCart ships them.
 *
 * Parts (see inc/hooks.php), shared with the EDD download page: product_breadcrumbs, product,
 * product_media, product_summary, product_title, product_price, product_excerpt,
 * product_purchase, product_meta, product_content, product_related, product_sale_badge (only when
 * on sale). $context['store'] is
 * 'fluentcart'. Inside actions: stackless_theme_product_summary_start/_end.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<?php stackless_theme_before_content(); ?>
<main id="main" class="<?php echo stackless_theme_class('main', ['site-main', 'site-main--shop', 'container'], ['template' => 'single-fluent-products']); ?>">
	<?php
	while (have_posts()) {
		the_post();
		$stackless_theme_id = get_the_ID();
		$stackless_theme_renderer = stackless_theme_fluentcart_renderer($stackless_theme_id);
		$stackless_theme_terms = get_the_terms($stackless_theme_id, 'product-categories');
		$stackless_theme_category = is_array($stackless_theme_terms) ? $stackless_theme_terms[0] : null;
		$stackless_theme_shop = stackless_theme_fluentcart_page_id('shop');
		$stackless_theme_ctx = ['store' => 'fluentcart', 'post_id' => (int) $stackless_theme_id];
		?>
		<?php if (stackless_theme_option('stackless_store_breadcrumb') && stackless_theme_part_start('product_breadcrumbs', $stackless_theme_ctx)) : ?>
		<nav class="st-breadcrumb" aria-label="<?php echo esc_attr(stackless_theme_text('breadcrumb_label', __('Breadcrumb', 'stackless-theme'))); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(stackless_theme_text('home', __('Home', 'stackless-theme'))); ?></a>
			<?php if ($stackless_theme_shop > 0 && get_post_status($stackless_theme_shop) === 'publish') : ?>
				<span class="breadcrumb-sep" aria-hidden="true">/</span>
				<a href="<?php echo esc_url((string) get_permalink($stackless_theme_shop)); ?>"><?php echo esc_html(get_the_title($stackless_theme_shop)); ?></a>
			<?php endif; ?>
			<?php if ($stackless_theme_category) : ?>
				<span class="breadcrumb-sep" aria-hidden="true">/</span>
				<a href="<?php echo esc_url((string) get_term_link($stackless_theme_category)); ?>"><?php echo esc_html($stackless_theme_category->name); ?></a>
			<?php endif; ?>
			<span class="breadcrumb-sep" aria-hidden="true">/</span>
			<span aria-current="page"><?php the_title(); ?></span>
		</nav>
		<?php stackless_theme_part_end(); endif; ?>

		<?php if (stackless_theme_part_start('product', $stackless_theme_ctx)) : ?>
		<div class="fct-single-product-page st-fc-single" data-fluent-cart-single-product-page data-product-id="<?php echo esc_attr((string) $stackless_theme_id); ?>">
			<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('product', ['st-download', 'st-fc-product', 'has-media'], $stackless_theme_ctx)); ?>>
				<?php if (stackless_theme_part_start('product_media', $stackless_theme_ctx)) : ?>
				<div class="st-download__media st-fc-product__media">
					<?php
					stackless_theme_sale_badge($stackless_theme_ctx + ['placement' => 'single']);
					if ($stackless_theme_renderer) {
						$stackless_theme_renderer->renderGallery();
					} elseif (has_post_thumbnail()) {
						the_post_thumbnail('large', ['fetchpriority' => 'high']);
					}
					?>
				</div>
				<?php stackless_theme_part_end(); endif; ?>

				<?php if (stackless_theme_part_start('product_summary', $stackless_theme_ctx)) : ?>
				<div class="st-download__summary fct-product-summary">
					<?php do_action('stackless_theme_product_summary_start', $stackless_theme_ctx); ?>
					<?php
					if (stackless_theme_part_start('product_title', $stackless_theme_ctx)) {
						the_title('<h1 class="st-download__title">', '</h1>');
						stackless_theme_part_end();
					}
					?>
					<?php if ($stackless_theme_renderer && stackless_theme_part_start('product_price', $stackless_theme_ctx)) : ?>
						<div class="st-download__price"><?php $stackless_theme_renderer->renderPrices(); ?></div>
					<?php stackless_theme_part_end(); endif; ?>
					<?php if (has_excerpt() && stackless_theme_part_start('product_excerpt', $stackless_theme_ctx)) : ?>
						<div class="st-download__excerpt"><?php the_excerpt(); ?></div>
					<?php stackless_theme_part_end(); endif; ?>
					<?php if ($stackless_theme_renderer && stackless_theme_part_start('product_purchase', $stackless_theme_ctx)) : ?>
						<div class="st-download__purchase"><?php $stackless_theme_renderer->renderBuySection(); ?></div>
					<?php stackless_theme_part_end(); endif; ?>
					<?php
					$stackless_theme_cats = stackless_theme_option('stackless_store_categories') ? get_the_term_list($stackless_theme_id, 'product-categories', '', ', ') : '';
					if ($stackless_theme_cats && !is_wp_error($stackless_theme_cats) && stackless_theme_part_start('product_meta', $stackless_theme_ctx)) :
						?>
						<div class="st-download__meta"><span><?php echo esc_html(stackless_theme_text('product_category_label', __('Category:', 'stackless-theme'))); ?> <?php echo wp_kses_post($stackless_theme_cats); ?></span></div>
					<?php stackless_theme_part_end(); endif; ?>
					<?php do_action('stackless_theme_product_summary_end', $stackless_theme_ctx); ?>
				</div>
				<?php stackless_theme_part_end(); endif; ?>

				<?php if (stackless_theme_part_start('product_content', $stackless_theme_ctx)) : ?>
				<div class="st-download__content entry-content">
					<?php the_content(); ?>
				</div>
				<?php stackless_theme_part_end(); endif; ?>
			</article>
		</div>
		<?php stackless_theme_part_end(); endif; ?>

		<?php
		stackless_theme_part('product_related', static function () use ($stackless_theme_id): void {
			stackless_theme_fluentcart_related((int) $stackless_theme_id);
		}, $stackless_theme_ctx);
	}
	?>
</main>
<?php
stackless_theme_after_content();
get_footer();
