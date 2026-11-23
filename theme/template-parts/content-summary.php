<?php
/**
 * A post in a list. A store product (in search results) shows its image, price and View product
 * instead of an excerpt and Read more.
 *
 * Parts (see inc/hooks.php): post_card, card_media, card_meta, card_title, card_price,
 * card_excerpt, card_more. $context has the post ID, post type and whether it is a product.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_product = stackless_theme_search_product(get_post());
$stackless_theme_ctx = ['post_id' => (int) get_the_ID(), 'post_type' => (string) get_post_type(), 'product' => (bool) $stackless_theme_product];

if (!stackless_theme_part_start('post_card', $stackless_theme_ctx)) {
	return;
}

if ($stackless_theme_product) :
	$stackless_theme_classes = ['post-card', 'post-card--product'];
	if ($stackless_theme_product['image'] !== '') {
		$stackless_theme_classes[] = 'has-media';
	}
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('post_card', $stackless_theme_classes, $stackless_theme_ctx)); ?>>
		<?php if ($stackless_theme_product['image'] !== '' && stackless_theme_part_start('card_media', $stackless_theme_ctx)) : ?>
			<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php echo $stackless_theme_product['image']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core image markup from the store's filter. ?>
			</a>
		<?php stackless_theme_part_end(); endif; ?>
		<div class="post-card__body">
			<?php
			if (stackless_theme_part_start('card_title', $stackless_theme_ctx)) {
				the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
				stackless_theme_part_end();
			}
			?>
			<?php if ($stackless_theme_product['price'] !== '' && stackless_theme_part_start('card_price', $stackless_theme_ctx)) : ?>
				<div class="post-card__price"><?php echo $stackless_theme_product['price']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the store's own escaped price markup. ?></div>
			<?php stackless_theme_part_end(); endif; ?>
			<?php if (stackless_theme_part_start('card_more', $stackless_theme_ctx)) : ?>
			<a class="button is-secondary post-card__action" href="<?php the_permalink(); ?>">
				<?php echo esc_html(stackless_theme_text('view_product', __('View product', 'stackless-theme'), $stackless_theme_ctx)); ?><span class="screen-reader-text"> <?php the_title(); ?></span>
			</a>
			<?php stackless_theme_part_end(); endif; ?>
		</div>
	</article>
	<?php
	stackless_theme_part_end();
	return;
endif;
?>
<?php
// Customize > Stackless Theme > Blog and archives.
$stackless_theme_card_image = stackless_theme_option('stackless_archive_image') && has_post_thumbnail() && !post_password_required();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('post_card', $stackless_theme_card_image ? ['post-card'] : ['post-card', 'is-text-only'], $stackless_theme_ctx)); ?>>
	<?php if ($stackless_theme_card_image && stackless_theme_part_start('card_media', $stackless_theme_ctx)) : ?>
		<a class="post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail((string) apply_filters('stackless_theme_card_media_size', 'medium_large', $stackless_theme_ctx), ['loading' => 'lazy']); ?>
		</a>
	<?php stackless_theme_part_end(); endif; ?>
	<div class="post-card__body">
		<?php if (get_post_type() === 'post' && stackless_theme_option('stackless_archive_meta') && stackless_theme_part_start('card_meta', $stackless_theme_ctx)) : ?>
			<div class="entry-meta"><?php stackless_theme_posted_on(); ?></div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php
		if (stackless_theme_part_start('card_title', $stackless_theme_ctx)) {
			the_title('<h2 class="entry-title"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">', '</a></h2>');
			stackless_theme_part_end();
		}
		?>
		<?php if (stackless_theme_option('stackless_archive_excerpt') && stackless_theme_part_start('card_excerpt', $stackless_theme_ctx)) : ?>
			<div class="entry-summary"><?php the_excerpt(); ?></div>
		<?php stackless_theme_part_end(); endif; ?>
		<?php if (stackless_theme_option('stackless_archive_read_more') && stackless_theme_part_start('card_more', $stackless_theme_ctx)) : ?>
			<a class="more-link" href="<?php the_permalink(); ?>">
				<?php echo esc_html(stackless_theme_text('read_more', __('Read more', 'stackless-theme'), $stackless_theme_ctx)); ?><span class="screen-reader-text"> <?php the_title(); ?></span>
				<?php stackless_theme_the_icon('arrow-right', 16); ?>
			</a>
		<?php stackless_theme_part_end(); endif; ?>
	</div>
</article>
<?php stackless_theme_part_end(); ?>
