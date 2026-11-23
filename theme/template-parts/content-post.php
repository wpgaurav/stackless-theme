<?php
/**
 * A single blog post: breadcrumbs, category, title, standfirst, byline, featured image, content,
 * tags and author box. What shows, and where the category and byline sit, follows Customize >
 * Stackless Theme > Single posts, and the post's own Layout choices.
 *
 * Parts (see inc/hooks.php): post, post_breadcrumbs, post_media, post_header, post_category,
 * post_byline, post_title, post_standfirst, post_content, post_footer, post_tags,
 * post_author_box, post_navigation. Each gets $context with the post ID and placements.
 * Inside actions: stackless_theme_post_header_start/_end, stackless_theme_post_footer_start/_end.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_category_at = stackless_theme_single_category_position();
$stackless_theme_byline_at = stackless_theme_single_byline_position();
$stackless_theme_header = stackless_theme_single_header();
$stackless_theme_ctx = [
	'post_id' => (int) get_the_ID(),
	'category_position' => $stackless_theme_category_at,
	'byline_position' => $stackless_theme_byline_at,
	'header_style' => $stackless_theme_header,
	'layout' => stackless_theme_single_layout(),
];
$stackless_theme_categories = $stackless_theme_category_at !== 'hidden' ? get_the_category() : [];
$stackless_theme_category = $stackless_theme_categories ? $stackless_theme_categories[0] : null;
$stackless_theme_author = stackless_theme_option('stackless_single_author') ? (int) get_the_author_meta('ID') : 0;
$stackless_theme_date = stackless_theme_single_date();
$stackless_theme_reading = stackless_theme_option('stackless_single_reading_time') ? stackless_theme_reading_time() : '';
$stackless_theme_image = stackless_theme_option('stackless_single_image');
$stackless_theme_left = $stackless_theme_header === 'left' || stackless_theme_single_has_sidebar();

// The byline: author, then date, reading time and (when placed there) the category, separated by dots.
$stackless_theme_meta = (array) apply_filters('stackless_theme_post_byline_meta', array_filter([
	'date' => $stackless_theme_date,
	'reading_time' => $stackless_theme_reading !== '' ? esc_html($stackless_theme_reading) : '',
	'category' => $stackless_theme_category && $stackless_theme_category_at === 'byline'
		? '<a class="post-byline__category" href="' . esc_url(get_category_link($stackless_theme_category)) . '">' . esc_html($stackless_theme_category->name) . '</a>'
		: '',
]), $stackless_theme_ctx);
$stackless_theme_byline = '';
if ($stackless_theme_author || $stackless_theme_meta) {
	ob_start();
	if (stackless_theme_part_start('post_byline', $stackless_theme_ctx)) {
		?>
	<div class="<?php echo stackless_theme_class('post_byline', ['post-byline', 'post-byline--' . $stackless_theme_byline_at], $stackless_theme_ctx); ?>">
		<?php if ($stackless_theme_author) : ?>
			<?php echo get_avatar($stackless_theme_author, 40, '', '', ['class' => 'post-byline__avatar']); ?>
		<?php endif; ?>
		<div class="post-byline__text">
			<?php if ($stackless_theme_author) : ?>
				<a class="post-byline__author" href="<?php echo esc_url(get_author_posts_url($stackless_theme_author)); ?>"><?php the_author(); ?></a>
			<?php endif; ?>
			<?php if ($stackless_theme_meta) : ?>
				<span class="post-byline__meta">
					<?php echo implode(' <span aria-hidden="true">·</span> ', $stackless_theme_meta); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part escaped above. ?>
				</span>
			<?php endif; ?>
		</div>
	</div>
		<?php
		stackless_theme_part_end();
	}
	$stackless_theme_byline = (string) ob_get_clean();
}

// The featured image: in the article, unless the header style puts it full width above the post (singular.php prints it).
$stackless_theme_figure = '';
if ($stackless_theme_image !== 'hidden' && $stackless_theme_header !== 'image-full' && has_post_thumbnail() && !post_password_required()) {
	ob_start();
	if (stackless_theme_part_start('post_media', $stackless_theme_ctx)) {
		echo '<figure class="' . stackless_theme_class('post_media', ['entry-media', $stackless_theme_image === 'wide' ? 'alignwide' : ''], $stackless_theme_ctx) . '">'
			. get_the_post_thumbnail(null, (string) apply_filters('stackless_theme_post_media_size', 'large', $stackless_theme_ctx), ['fetchpriority' => 'high']) . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core thumbnail markup and escaped classes.
		stackless_theme_part_end();
	}
	$stackless_theme_figure = (string) ob_get_clean();
}
?>
<?php if (stackless_theme_part_start('post', $stackless_theme_ctx)) : ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(stackless_theme_class_list('post', ['entry', 'entry--post'], $stackless_theme_ctx)); ?>>
	<?php stackless_theme_maybe_breadcrumbs(); ?>

	<?php
	if ($stackless_theme_header === 'image-first') {
		echo $stackless_theme_figure; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
	}
	?>

	<?php if (stackless_theme_part_start('post_header', $stackless_theme_ctx)) : ?>
	<header class="<?php echo stackless_theme_class('post_header', ['entry-header', 'post-header', $stackless_theme_left ? 'post-header--left' : ''], $stackless_theme_ctx); ?>">
		<?php do_action('stackless_theme_post_header_start', $stackless_theme_ctx); ?>
		<?php if ($stackless_theme_category && $stackless_theme_category_at === 'above' && stackless_theme_part_start('post_category', $stackless_theme_ctx)) : ?>
			<a class="post-eyebrow" href="<?php echo esc_url(get_category_link($stackless_theme_category)); ?>"><?php echo esc_html($stackless_theme_category->name); ?></a>
		<?php stackless_theme_part_end(); endif; ?>
		<?php
		if ($stackless_theme_byline_at === 'above') {
			echo $stackless_theme_byline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where built.
		}
		?>
		<?php
		if (stackless_theme_part_start('post_title', $stackless_theme_ctx)) {
			the_title('<h1 class="entry-title">', '</h1>');
			stackless_theme_part_end();
		}
		?>
		<?php if (has_excerpt() && stackless_theme_option('stackless_single_standfirst') && stackless_theme_part_start('post_standfirst', $stackless_theme_ctx)) : ?>
			<p class="post-standfirst"><?php echo esc_html(get_the_excerpt()); ?></p>
		<?php stackless_theme_part_end(); endif; ?>
		<?php
		if ($stackless_theme_byline_at === 'below') {
			echo $stackless_theme_byline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where built.
		}
		?>
		<?php do_action('stackless_theme_post_header_end', $stackless_theme_ctx); ?>
	</header>
	<?php stackless_theme_part_end(); endif; ?>

	<?php
	if ($stackless_theme_header !== 'image-first') {
		echo $stackless_theme_figure; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above.
	}
	?>

	<?php if (stackless_theme_part_start('post_content', $stackless_theme_ctx)) : ?>
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

	<?php
	// get_the_tags() is false for a post with no tags, and an error object when the lookup fails.
	$stackless_theme_tags = stackless_theme_option('stackless_single_tags') ? get_the_tags() : [];
	$stackless_theme_tags = is_array($stackless_theme_tags) ? $stackless_theme_tags : [];
	$stackless_theme_box = stackless_theme_option('stackless_single_author_box') && get_the_author_meta('ID') && get_the_author();
	$stackless_theme_category_below = $stackless_theme_category && $stackless_theme_category_at === 'below';
	$stackless_theme_byline_end = $stackless_theme_byline !== '' && $stackless_theme_byline_at === 'end';
	?>
	<?php if (($stackless_theme_tags || $stackless_theme_box || $stackless_theme_category_below || $stackless_theme_byline_end || has_action('stackless_theme_post_footer_start') || has_action('stackless_theme_post_footer_end')) && stackless_theme_part_start('post_footer', $stackless_theme_ctx)) : ?>
		<footer class="<?php echo stackless_theme_class('post_footer', ['entry-footer', 'post-footer'], $stackless_theme_ctx); ?>">
			<?php do_action('stackless_theme_post_footer_start', $stackless_theme_ctx); ?>
			<?php
			if ($stackless_theme_byline_end) {
				echo $stackless_theme_byline; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped where built.
			}
			?>
			<?php if (($stackless_theme_tags || $stackless_theme_category_below) && stackless_theme_part_start('post_tags', $stackless_theme_ctx)) : ?>
				<div class="post-tags">
					<?php if ($stackless_theme_category_below) : ?>
						<span class="post-tags__label"><?php echo esc_html(stackless_theme_text('filed_under', __('Filed under', 'stackless-theme'), $stackless_theme_ctx)); ?></span>
						<a class="chip chip--category" href="<?php echo esc_url(get_category_link($stackless_theme_category)); ?>"><?php echo esc_html($stackless_theme_category->name); ?></a>
					<?php endif; ?>
					<?php foreach ($stackless_theme_tags as $stackless_theme_tag) : ?>
						<a class="chip" href="<?php echo esc_url(get_tag_link($stackless_theme_tag)); ?>" rel="tag">#<?php echo esc_html($stackless_theme_tag->name); ?></a>
					<?php endforeach; ?>
				</div>
			<?php stackless_theme_part_end(); endif; ?>

			<?php if ($stackless_theme_box && stackless_theme_part_start('post_author_box', $stackless_theme_ctx)) : ?>
				<?php $stackless_theme_box_author = (int) get_the_author_meta('ID'); ?>
				<section class="author-box" aria-label="<?php echo esc_attr(stackless_theme_text('about_author', __('About the author', 'stackless-theme'), $stackless_theme_ctx)); ?>">
					<?php echo get_avatar($stackless_theme_box_author, 64, '', '', ['class' => 'author-box__avatar']); ?>
					<div>
						<p class="author-box__label"><?php echo esc_html(stackless_theme_text('written_by', __('Written by', 'stackless-theme'), $stackless_theme_ctx)); ?></p>
						<p class="author-box__name"><a href="<?php echo esc_url(get_author_posts_url($stackless_theme_box_author)); ?>"><?php the_author(); ?></a></p>
						<?php if (get_the_author_meta('description')) : ?>
							<p class="author-box__bio"><?php echo esc_html(get_the_author_meta('description')); ?></p>
						<?php endif; ?>
					</div>
				</section>
			<?php stackless_theme_part_end(); endif; ?>
			<?php do_action('stackless_theme_post_footer_end', $stackless_theme_ctx); ?>
		</footer>
	<?php stackless_theme_part_end(); endif; ?>

	<?php
	if (stackless_theme_option('stackless_single_post_nav') && stackless_theme_part_start('post_navigation', $stackless_theme_ctx)) {
		the_post_navigation((array) apply_filters('stackless_theme_post_navigation_args', [
			'prev_text' => '<span class="nav-subtitle">' . stackless_theme_icon('arrow-left', 14) . ' ' . esc_html(stackless_theme_text('previous', __('Previous', 'stackless-theme'))) . '</span><span class="nav-title">%title</span>',
			'next_text' => '<span class="nav-subtitle">' . esc_html(stackless_theme_text('next', __('Next', 'stackless-theme'))) . ' ' . stackless_theme_icon('arrow-right', 14) . '</span><span class="nav-title">%title</span>',
		], $stackless_theme_ctx));
		stackless_theme_part_end();
	}
	?>
</article>
<?php stackless_theme_part_end(); endif; ?>
