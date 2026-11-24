<?php
/**
 * Stackless theme bootstrap.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

define('STACKLESS_THEME_VERSION', '1.8.0-beta.1');
define('STACKLESS_THEME_DIR', get_template_directory());
define('STACKLESS_THEME_URI', get_template_directory_uri());

require STACKLESS_THEME_DIR . '/inc/icons.php';
require STACKLESS_THEME_DIR . '/inc/hooks.php';
require STACKLESS_THEME_DIR . '/inc/setup.php';
require STACKLESS_THEME_DIR . '/inc/template-tags.php';
require STACKLESS_THEME_DIR . '/inc/customizer.php';
require STACKLESS_THEME_DIR . '/inc/content-options.php';
require STACKLESS_THEME_DIR . '/inc/design.php';
require STACKLESS_THEME_DIR . '/inc/layout.php';
require STACKLESS_THEME_DIR . '/inc/page-blocks.php';
require STACKLESS_THEME_DIR . '/inc/blocks/blocks.php';

if (class_exists('WooCommerce')) {
    require STACKLESS_THEME_DIR . '/inc/woocommerce.php';
}

if (class_exists('Easy_Digital_Downloads')) {
    require STACKLESS_THEME_DIR . '/inc/edd.php';
}

if (defined('FLUENTCART_VERSION')) {
    require STACKLESS_THEME_DIR . '/inc/fluentcart.php';
}

if (class_exists('Easy_Digital_Downloads') || class_exists('WooCommerce')) {
    require STACKLESS_THEME_DIR . '/inc/store.php';
}

if (is_admin()) {
    require STACKLESS_THEME_DIR . '/inc/admin/tools.php';
    require STACKLESS_THEME_DIR . '/inc/admin/md-import.php';
}
