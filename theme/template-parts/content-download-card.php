<?php
/**
 * A download in a grid: image, title, price and one action. Downloads with price options link to
 * their page, where the options are; the rest add to the cart in place.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_id = get_the_ID();
$stackless_theme_variable = function_exists('edd_has_variable_prices') && edd_has_variable_prices($stackless_theme_id);
?>
<article id="download-<?php the_ID(); ?>" <?php post_class('st-download-card'); ?>>
	<a class="st-download-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if (has_post_thumbnail()) : ?>
			<?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => '']); ?>
		<?php else : ?>
			<span class="st-download-card__placeholder"><?php stackless_theme_the_icon('shopping-bag', 32); ?></span>
		<?php endif; ?>
	</a>
	<div class="st-download-card__body">
		<?php the_title('<h2 class="st-download-card__title"><a href="' . esc_url(get_permalink()) . '">', '</a></h2>'); ?>
		<p class="st-download-card__price"><?php echo stackless_theme_edd_price_html($stackless_theme_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper. ?></p>
	</div>
	<div class="st-download-card__action">
		<?php if ($stackless_theme_variable) : ?>
			<a class="button st-download-card__choose" href="<?php the_permalink(); ?>">
				<?php esc_html_e('Choose an option', 'stackless-theme'); ?><span class="screen-reader-text">: <?php the_title(); ?></span>
			</a>
		<?php else : ?>
			<?php echo stackless_theme_edd_purchase_form($stackless_theme_id, ['price' => false]); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- EDD escapes its purchase form. ?>
		<?php endif; ?>
	</div>
</article>
