<?php
/**
 * Comments.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

if (post_password_required()) {
	return;
}
?>
<section id="comments" class="comments-area">
	<?php if (have_comments()) : ?>
		<h2 class="comments-title">
			<?php
			$stackless_theme_count = (int) get_comments_number();
			/* translators: %s: number of comments */
			printf(esc_html(_n('%s comment', '%s comments', $stackless_theme_count, 'stackless-theme')), esc_html(number_format_i18n($stackless_theme_count)));
			?>
		</h2>
		<ol class="comment-list">
			<?php wp_list_comments(['style' => 'ol', 'short_ping' => true, 'avatar_size' => 40]); ?>
		</ol>
		<?php the_comments_navigation(); ?>
		<?php if (!comments_open()) : ?>
			<p class="no-comments"><?php esc_html_e('Comments are closed.', 'stackless-theme'); ?></p>
		<?php endif; ?>
	<?php endif; ?>

	<?php comment_form(['title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">', 'title_reply_after' => '</h2>']); ?>
</section>
