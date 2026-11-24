<?php
/**
 * The sidebar header layout (Header and menu > Header layout > Sidebar): its stylesheet, the
 * rail width, the focus trap for the phone drawer, and a tiny script right after <body> that
 * restores the visitor's collapsed or expanded rail before the page paints. Nothing here runs
 * unless the rail is in use (stackless_theme_header_has_rail()).
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', 'stackless_theme_rail_assets', 21);
function stackless_theme_rail_assets(): void
{
    if (!stackless_theme_header_has_rail()) {
        return;
    }
    stackless_theme_style('stackless-theme-header-sidebar', 'assets/css/header-sidebar.css', ['stackless-theme-layout']);
    $width = stackless_theme_sanitize_rail_width(stackless_theme_option('stackless_header_rail_width'));
    if ($width !== 280) {
        wp_add_inline_style('stackless-theme-header-sidebar', ':root{--st-rail:' . $width . 'px}');
    }
    // The phone drawer traps focus with window.StacklessFocus.
    wp_enqueue_script('stackless-theme-focus');
}

/**
 * Adds the js class at once (so the drawer CSS applies before theme.js runs), then the
 * rail-collapsed body class when the visitor collapsed the rail, or when collapsed is the default
 * and they never chose. Without JavaScript the rail stays expanded.
 */
add_action('wp_body_open', 'stackless_theme_rail_state_script', 1);
function stackless_theme_rail_state_script(): void
{
    if (!stackless_theme_header_has_rail()) {
        return;
    }
    $collapse = stackless_theme_sanitize_rail_collapse(stackless_theme_option('stackless_header_rail_collapse'));
    $default = $collapse !== 'off' && stackless_theme_sanitize_rail_default(stackless_theme_option('stackless_header_rail_default')) === 'collapsed' ? 'collapsed' : 'expanded';
    $script = "document.documentElement.classList.add('js');";
    if ($collapse !== 'off') {
        $script .= "(function(){var s=null;try{s=localStorage.getItem('stacklessRail')}catch(e){}if((s||" . wp_json_encode($default) . ")==='collapsed'){document.body.classList.add('rail-collapsed')}})();";
    }
    wp_print_inline_script_tag($script, ['id' => 'stackless-theme-rail-state']);
}
