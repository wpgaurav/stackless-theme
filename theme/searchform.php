<?php
/**
 * Search form (get_search_form()). Part: search_form_inline.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_search_id = wp_unique_id('search-');
?>
<?php if (!stackless_theme_part_start('search_form_inline')) { return; } ?>
<form role="search" method="get" class="<?php echo stackless_theme_class('search_form_inline', ['search-form']); ?>" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr($stackless_theme_search_id); ?>"><?php echo esc_html(stackless_theme_text('search_for', __('Search for:', 'stackless-theme'))); ?></label>
	<span class="search-form__icon"><?php stackless_theme_the_icon('search', 18); ?></span>
	<input type="search" id="<?php echo esc_attr($stackless_theme_search_id); ?>" class="search-field" placeholder="<?php echo esc_attr(stackless_theme_text('search_placeholder', __('Search', 'stackless-theme'))); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
	<button type="submit" class="search-submit"><?php echo esc_html(stackless_theme_text('search', __('Search', 'stackless-theme'))); ?></button>
</form>
<?php stackless_theme_part_end(); ?>
