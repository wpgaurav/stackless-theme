<?php
/**
 * A single FluentCart product, laid out like a WooCommerce product or an EDD download: breadcrumb,
 * the gallery on the left, a sticky summary with price, options and the buy buttons on the right,
 * the description below and related products after it.
 *
 * FluentCart's own renderer prints the gallery, prices and buy section, so variations, quantity,
 * Buy Now, Add to Cart and add-ons such as wishlists keep working as FluentCart ships them.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

get_header();
?>
<?php stackless_theme_before_content(); ?>
<main id="main" class="site-main site-main--shop container">
	<?php
	while (have_posts()) {
		the_post();
		$stackless_theme_id = get_the_ID();
		$stackless_theme_renderer = stackless_theme_fluentcart_renderer($stackless_theme_id);
		$stackless_theme_terms = get_the_terms($stackless_theme_id, 'product-categories');
		$stackless_theme_category = is_array($stackless_theme_terms) ? $stackless_theme_terms[0] : null;
		$stackless_theme_shop = stackless_theme_fluentcart_page_id('shop');
		?>
		<nav class="st-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'stackless-theme'); ?>">
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'stackless-theme'); ?></a>
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

		<div class="fct-single-product-page st-fc-single" data-fluent-cart-single-product-page data-product-id="<?php echo esc_attr((string) $stackless_theme_id); ?>">
			<article id="post-<?php the_ID(); ?>" <?php post_class('st-download st-fc-product has-media'); ?>>
				<div class="st-download__media st-fc-product__media">
					<?php
					if ($stackless_theme_renderer) {
						$stackless_theme_renderer->renderGallery();
					} elseif (has_post_thumbnail()) {
						the_post_thumbnail('large', ['fetchpriority' => 'high']);
					}
					?>
				</div>

				<div class="st-download__summary fct-product-summary">
					<?php the_title('<h1 class="st-download__title">', '</h1>'); ?>
					<?php if ($stackless_theme_renderer) : ?>
						<div class="st-download__price"><?php $stackless_theme_renderer->renderPrices(); ?></div>
					<?php endif; ?>
					<?php if (has_excerpt()) : ?>
						<div class="st-download__excerpt"><?php the_excerpt(); ?></div>
					<?php endif; ?>
					<?php if ($stackless_theme_renderer) : ?>
						<div class="st-download__purchase"><?php $stackless_theme_renderer->renderBuySection(); ?></div>
					<?php endif; ?>
					<?php
					$stackless_theme_cats = get_the_term_list($stackless_theme_id, 'product-categories', '', ', ');
					if ($stackless_theme_cats && !is_wp_error($stackless_theme_cats)) :
						?>
						<div class="st-download__meta"><span><?php esc_html_e('Category:', 'stackless-theme'); ?> <?php echo wp_kses_post($stackless_theme_cats); ?></span></div>
					<?php endif; ?>
				</div>

				<div class="st-download__content entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		</div>

		<?php
		stackless_theme_fluentcart_related($stackless_theme_id);
	}
	?>
</main>
<?php
stackless_theme_after_content();
get_footer();
