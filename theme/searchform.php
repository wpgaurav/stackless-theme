<?php
/**
 * Search form.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stackless_theme_search_id = wp_unique_id('search-');
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr($stackless_theme_search_id); ?>"><?php esc_html_e('Search for:', 'stackless-theme'); ?></label>
	<span class="search-form__icon"><?php stackless_theme_the_icon('search', 18); ?></span>
	<input type="search" id="<?php echo esc_attr($stackless_theme_search_id); ?>" class="search-field" placeholder="<?php esc_attr_e('Search', 'stackless-theme'); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
	<button type="submit" class="search-submit"><?php esc_html_e('Search', 'stackless-theme'); ?></button>
</form>
