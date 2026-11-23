<?php
/**
 * Icons for Stackless blocks.
 *
 * Blocks store Marketers Delight's icon names (md-icon-bolt), so content moves between the two
 * themes unchanged. Marketers Delight draws them with an icon font; Stackless prints the matching
 * Tabler icon as inline SVG inside the same element. A name with no match prints no icon.
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

/**
 * Marketers Delight icon name (without the md-icon- prefix) => Tabler icon in assets/icons/.
 *
 * @return array<string, string>
 */
function stackless_theme_blocks_icon_map(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $map = [
        'angle-down' => 'chevron-down',
        'angle-left' => 'chevron-left',
        'angle-right' => 'chevron-right',
        'angle-up' => 'chevron-up',
        'bell' => 'bell',
        'bolt' => 'bolt',
        'book' => 'book',
        'cancel' => 'x',
        'cart' => 'shopping-cart',
        'chat' => 'message',
        'clock' => 'clock',
        'code' => 'code',
        'download' => 'download',
        'exclamation' => 'exclamation-mark',
        'eye' => 'eye',
        'facebook' => 'brand-facebook',
        'github' => 'brand-github',
        'hand' => 'hand-stop',
        'heart' => 'heart',
        'heart-empty' => 'heart',
        'instagram' => 'brand-instagram',
        'key' => 'key',
        'like' => 'thumb-up',
        'url' => 'link',
        'linkedin' => 'brand-linkedin',
        'loading' => 'loader-2',
        'mail-alt' => 'mail',
        'menu' => 'menu-2',
        'ok' => 'check',
        'pencil' => 'pencil',
        'phone' => 'phone',
        'pin' => 'pin',
        'pinterest' => 'brand-pinterest',
        'quote' => 'quote',
        'reply' => 'arrow-back-up',
        'rss' => 'rss',
        'search' => 'search',
        'share' => 'share',
        'tags' => 'tags',
        'telegram' => 'brand-telegram',
        'tiktok' => 'brand-tiktok',
        'trophy' => 'trophy',
        'twitter' => 'brand-x',
        'x' => 'brand-x',
        'reddit' => 'brand-reddit',
        'user' => 'user',
        'user-add' => 'user-plus',
        'volume-up' => 'volume',
        'whatsapp' => 'brand-whatsapp',
        'wordpress' => 'brand-wordpress',
        'youtube' => 'brand-youtube',
        'home' => 'home',
        'camera' => 'camera',
        'mic' => 'microphone',
        'book-new' => 'book-2',
        'books' => 'books',
        'files-empty' => 'files',
        'folder-open' => 'folder-open',
        'credit-card' => 'credit-card',
        'calculator' => 'calculator',
        'setting' => 'settings',
        'trophy-new' => 'trophy',
        'gift' => 'gift',
        'fire' => 'flame',
        'sun' => 'sun',
        'contrast' => 'contrast',
        'half-sun' => 'sun-moon',
        'star-empty' => 'star',
        'star-half' => 'star-half',
        'star-full' => 'star',
        'star' => 'star',
        'heart-new' => 'heart',
        'heart-broken' => 'heart-broken',
        'warning' => 'alert-triangle',
        'notification' => 'bell-ringing',
        'question' => 'help-circle',
        'info' => 'info-circle',
        'cancel-circle' => 'circle-x',
        'blocked' => 'ban',
        'arrow-up-left' => 'arrow-up-left',
        'arrow-up' => 'arrow-up',
        'arrow-up-right' => 'arrow-up-right',
        'arrow-right' => 'arrow-right',
        'arrow-down-right' => 'arrow-down-right',
        'arrow-down' => 'arrow-down',
        'arrow-down-left' => 'arrow-down-left',
        'arrow-left' => 'arrow-left',
        'checked' => 'checkbox',
        'unchecked' => 'square',
        'instagram-new' => 'brand-instagram',
        'paypal' => 'brand-paypal',
        'pdf' => 'file-type-pdf',
        'word' => 'file-type-doc',
        'excel' => 'file-type-xls',
        'chatgpt' => 'brand-openai',
        'perplexity' => 'sparkles',
        'claude' => 'sparkles',
        'gemini' => 'sparkles',
        'ellipsis-vertical' => 'dots-vertical',
        'ellipsis' => 'dots',
        'percent' => 'percentage',
        'dollar' => 'currency-dollar',
        'verified' => 'rosette-discount-check',
        'ad' => 'ad',
        'pro' => 'crown',
        'copy-solid' => 'copy',
        'copy' => 'copy',
        'clone' => 'copy',
        'mail' => 'mail',
        'cancel-circle-solid' => 'circle-x',
        'user-alt' => 'user-circle',
        'check-circle-solid' => 'circle-check',
        'check-circle' => 'circle-check',
        'gem' => 'diamond',
        'arrow-square-left' => 'square-arrow-left',
        'arrow-square-right' => 'square-arrow-right',
        'shield-check' => 'shield-check',
        'sign-in' => 'login',
        'comment' => 'message-circle',
        'rocket' => 'rocket',
        'badge' => 'award',
        'download-alt' => 'cloud-download',
        'tag' => 'tag',
        'folder' => 'folder',
        'amazon' => 'brand-amazon',
        'wikipedia' => 'brand-wikipedia',
        'plugin' => 'plug',
        'substack' => 'news',
        'responsive' => 'devices',
        'bookmark' => 'bookmark',
        'trash' => 'trash',
        'cloud' => 'cloud',
        'server' => 'server',
        'gauge' => 'gauge',
        'file' => 'file',
    ];
    /**
     * Filters the Marketers Delight icon name => Tabler icon map. A Tabler icon added here must
     * exist as an SVG file in the theme's assets/icons/ folder.
     */
    $map = (array) apply_filters('stackless_theme_blocks_icon_map', $map);
    return $map;
}

/**
 * The inline SVG for a stored icon value: md-icon-bolt, bolt, or a Tabler name that the theme
 * ships. Sized 1em so it follows the font size, as the icon font did. Empty when unknown.
 */
function stackless_theme_blocks_icon(string $value): string
{
    static $cache = [];
    $name = sanitize_key(preg_replace('/^md-icon-/', '', trim($value)));
    if ($name === '') {
        return '';
    }
    if (!isset($cache[$name])) {
        $map = stackless_theme_blocks_icon_map();
        $svg = stackless_theme_icon($map[$name] ?? $name, 24);
        $cache[$name] = $svg === '' ? '' : (string) preg_replace('/width="24" height="24"/', 'width="1em" height="1em"', $svg, 1);
    }
    return $cache[$name];
}

/**
 * Fills every empty element that carries an md-icon-* class with its SVG. The block templates
 * print the same empty <i> and <span> elements Marketers Delight does, so custom CSS aimed at
 * them keeps working.
 */
function stackless_theme_blocks_inline_icons(string $html): string
{
    if (strpos($html, 'md-icon-') === false) {
        return $html;
    }
    return (string) preg_replace_callback(
        '/<(i|span)\b([^>]*\bclass="[^"]*\bmd-icon-([a-z0-9-]+)[^"]*"[^>]*)><\/\1>/',
        static function (array $match): string {
            $svg = stackless_theme_blocks_icon($match[3]);
            return $svg === '' ? $match[0] : '<' . $match[1] . $match[2] . '>' . $svg . '</' . $match[1] . '>';
        },
        $html
    );
}

/**
 * The icon picker's options: label and stored value, in Marketers Delight's order of names.
 *
 * @return array<int, array{label: string, value: string}>
 */
function stackless_theme_blocks_icon_options(): array
{
    $options = [];
    foreach (array_keys(stackless_theme_blocks_icon_map()) as $name) {
        if (stackless_theme_blocks_icon($name) !== '') {
            $options[] = ['label' => ucfirst(str_replace('-', ' ', $name)), 'value' => 'md-icon-' . $name];
        }
    }
    return $options;
}
