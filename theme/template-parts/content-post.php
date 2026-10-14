<?php
/**
 * A single blog post: category eyebrow, title, standfirst, byline, wide image, content, tags and author.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_categories = get_the_category();
$stackless_theme_author = (int) get_the_author_meta('ID');
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('entry entry--post'); ?>>
	<header class="entry-header post-header">
		<?php if ($stackless_theme_categories) : ?>
			<a class="post-eyebrow" href="<?php echo esc_url(get_category_link($stackless_theme_categories[0])); ?>"><?php echo esc_html($stackless_theme_categories[0]->name); ?></a>
		<?php endif; ?>
		<?php the_title('<h1 class="entry-title">', '</h1>'); ?>
		<?php if (has_excerpt()) : ?>
			<p class="post-standfirst"><?php echo esc_html(get_the_excerpt()); ?></p>
		<?php endif; ?>
		<div class="post-byline">
			<?php if ($stackless_theme_author) : ?>
				<?php echo get_avatar($stackless_theme_author, 40, '', '', ['class' => 'post-byline__avatar']); ?>
			<?php endif; ?>
			<div class="post-byline__text">
				<?php if ($stackless_theme_author) : ?>
					<a class="post-byline__author" href="<?php echo esc_url(get_author_posts_url($stackless_theme_author)); ?>"><?php the_author(); ?></a>
				<?php endif; ?>
				<span class="post-byline__meta">
					<time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date()); ?></time>
					<span aria-hidden="true">·</span>
					<?php echo esc_html(stackless_theme_reading_time()); ?>
				</span>
			</div>
		</div>
	</header>

	<?php if (has_post_thumbnail() && !post_password_required()) : ?>
		<figure class="entry-media alignwide"><?php the_post_thumbnail('large', ['fetchpriority' => 'high']); ?></figure>
	<?php endif; ?>

	<div class="entry-content">
		<?php
		the_content();
		wp_link_pages([
			'before' => '<nav class="page-links" aria-label="' . esc_attr__('Page', 'stackless-theme') . '">',
			'after' => '</nav>',
		]);
		?>
	</div>

	<footer class="entry-footer post-footer">
		<?php $stackless_theme_tags = get_the_tags(); ?>
		<?php if ($stackless_theme_tags) : ?>
			<div class="post-tags">
				<?php foreach ($stackless_theme_tags as $stackless_theme_tag) : ?>
					<a class="chip" href="<?php echo esc_url(get_tag_link($stackless_theme_tag)); ?>" rel="tag">#<?php echo esc_html($stackless_theme_tag->name); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ($stackless_theme_author && get_the_author()) : ?>
		<section class="author-box" aria-label="<?php esc_attr_e('About the author', 'stackless-theme'); ?>">
			<?php echo get_avatar($stackless_theme_author, 64, '', '', ['class' => 'author-box__avatar']); ?>
			<div>
				<p class="author-box__label"><?php esc_html_e('Written by', 'stackless-theme'); ?></p>
				<p class="author-box__name"><a href="<?php echo esc_url(get_author_posts_url($stackless_theme_author)); ?>"><?php the_author(); ?></a></p>
				<?php if (get_the_author_meta('description')) : ?>
					<p class="author-box__bio"><?php echo esc_html(get_the_author_meta('description')); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php endif; ?>
	</footer>

	<?php
	the_post_navigation([
		'prev_text' => '<span class="nav-subtitle">' . stackless_theme_icon('arrow-left', 14) . ' ' . esc_html__('Previous', 'stackless-theme') . '</span><span class="nav-title">%title</span>',
		'next_text' => '<span class="nav-subtitle">' . esc_html__('Next', 'stackless-theme') . ' ' . stackless_theme_icon('arrow-right', 14) . '</span><span class="nav-title">%title</span>',
	]);
	?>
</article>
