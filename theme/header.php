<?php
/**
 * Site header.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e('Skip to content', 'stackless-theme'); ?></a>

<?php
if (stackless_theme_shows('header')) {
	stackless_theme_chrome('header', static function (): void {
		get_template_part('template-parts/site', 'header');
	});
}
stackless_theme_region('hero');
?>
