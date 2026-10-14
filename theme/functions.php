<?php
/**
 * Stackless theme bootstrap.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

define('STACKLESS_THEME_VERSION', '1.1.2');
define('STACKLESS_THEME_DIR', get_template_directory());
define('STACKLESS_THEME_URI', get_template_directory_uri());

require STACKLESS_THEME_DIR . '/inc/icons.php';
require STACKLESS_THEME_DIR . '/inc/setup.php';
require STACKLESS_THEME_DIR . '/inc/template-tags.php';

if (class_exists('WooCommerce')) {
    require STACKLESS_THEME_DIR . '/inc/woocommerce.php';
}
