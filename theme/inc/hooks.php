<?php
/**
 * The theme's extension API. Every visible part of a Stackless page goes through these helpers,
 * so a plugin (Stackless Pro's site layouts, for one) can add to, hide, replace, re-class or
 * re-word any part without copying a template. See docs/hooks.md for the list of parts.
 *
 * For a part named "post_title":
 *
 *   stackless_theme_show_post_title   filter (bool, $context)        false hides the part
 *   stackless_theme_show_part         filter (bool, $part, $context) the same, for every part
 *   stackless_theme_pre_post_title    filter (null, $context)        return HTML to replace the part
 *   stackless_theme_before_post_title action ($context)              prints before the part
 *   stackless_theme_post_title_html   filter ($html, $context)       the part's rendered markup
 *   stackless_theme_part_html         filter ($html, $part, $context)
 *   stackless_theme_after_post_title  action ($context)              prints after the part
 *   stackless_theme_post_title_class  filter ($classes, $context)    where the part has classes
 *
 * Before and after actions run outside the captured markup, so what they print is never passed
 * through the _html filters. A hidden part fires nothing.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Opens a part. Returns true when the template should print the part's own markup, then close it
 * with stackless_theme_part_end(). Returns false when the part is hidden or was replaced, in
 * which case the template skips the markup and does not call stackless_theme_part_end().
 *
 *   <?php if (stackless_theme_part_start('post_title', $context)) : ?>
 *       <h1 class="entry-title">...</h1>
 *   <?php stackless_theme_part_end(); endif; ?>
 */
function stackless_theme_part_start(string $part, array $context = []): bool
{
    if (!apply_filters("stackless_theme_show_{$part}", true, $context) || !apply_filters('stackless_theme_show_part', true, $part, $context)) {
        return false;
    }
    $replacement = apply_filters("stackless_theme_pre_{$part}", null, $context);
    do_action("stackless_theme_before_{$part}", $context);
    if (is_string($replacement)) {
        echo $replacement; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup supplied by a filter.
        do_action("stackless_theme_after_{$part}", $context);
        return false;
    }
    $GLOBALS['stackless_theme_open_parts'][] = [$part, $context];
    ob_start();
    return true;
}

/** Closes the part opened last: filters its markup, prints it and fires the after action. */
function stackless_theme_part_end(): void
{
    if (empty($GLOBALS['stackless_theme_open_parts'])) {
        return;
    }
    [$part, $context] = array_pop($GLOBALS['stackless_theme_open_parts']);
    $html = (string) ob_get_clean();
    $html = (string) apply_filters("stackless_theme_{$part}_html", $html, $context);
    echo apply_filters('stackless_theme_part_html', $html, $part, $context); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the template's escaped markup, or a filter's.
    do_action("stackless_theme_after_{$part}", $context);
}

/** Renders a part from a callback: the same hooks as start/end, for parts built in PHP. */
function stackless_theme_part(string $part, callable $render, array $context = []): void
{
    if (stackless_theme_part_start($part, $context)) {
        $render($context);
        stackless_theme_part_end();
    }
}

/** Whether a part will show, for templates that wrap it in markup of their own. */
function stackless_theme_shows_part(string $part, array $context = []): bool
{
    return (bool) apply_filters("stackless_theme_show_{$part}", true, $context) && (bool) apply_filters('stackless_theme_show_part', true, $part, $context);
}

/**
 * A part's class list, filtered by stackless_theme_{part}_class and stackless_theme_class, and
 * escaped for a class attribute.
 */
function stackless_theme_class(string $part, array $classes, array $context = []): string
{
    $classes = (array) apply_filters("stackless_theme_{$part}_class", $classes, $context);
    $classes = (array) apply_filters('stackless_theme_class', $classes, $part, $context);
    return esc_attr(implode(' ', array_unique(array_filter(array_map('strval', $classes)))));
}

/** The same class list as an array, for post_class() and body_class(). */
function stackless_theme_class_list(string $part, array $classes, array $context = []): array
{
    $classes = (array) apply_filters("stackless_theme_{$part}_class", $classes, $context);
    return array_values(array_unique(array_filter(array_map('strval', (array) apply_filters('stackless_theme_class', $classes, $part, $context)))));
}

/** A piece of interface text: filtered by stackless_theme_text_{key} and stackless_theme_text. Not escaped. */
function stackless_theme_text(string $key, string $text, array $context = []): string
{
    $text = (string) apply_filters("stackless_theme_text_{$key}", $text, $context);
    return (string) apply_filters('stackless_theme_text', $text, $key, $context);
}

/**
 * Loads a template part. A plugin can serve its own file with stackless_theme_template_part_file
 * (return a path), else the child theme's or the theme's file loads as usual.
 */
function stackless_theme_template_part(string $slug, ?string $name = null, array $args = []): void
{
    $file = (string) apply_filters('stackless_theme_template_part_file', '', $slug, $name, $args);
    if ($file !== '' && is_readable($file)) {
        load_template($file, false, $args);
        return;
    }
    get_template_part($slug, $name, $args);
}
