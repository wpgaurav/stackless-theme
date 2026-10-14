<?php
/**
 * Homepage (Tour, the static front page) for demo.gatilab.com/stackless/. Built from core blocks
 * the Stackless theme styles, so the page also shows what the theme does with no custom CSS.
 * Idempotent: the detailed feature lists already on the page are kept verbatim and the sections
 * around them are regenerated. Run: wp --url=https://demo.gatilab.com/stackless/ eval-file demo-home.php
 */

defined('ABSPATH') || exit;

if (untrailingslashit(home_url()) !== 'https://demo.gatilab.com/stackless' || get_option('stylesheet') !== 'stackless-theme') {
    WP_CLI::error('Wrong target or theme not active.');
}
$tour = get_post((int) get_option('page_on_front'));
if (!$tour || $tour->post_name !== 'tour') {
    WP_CLI::error('Front page is not the Tour page.');
}

/* ------------------------------------------------------------------ Keep the detailed tour */
$content = $tour->post_content;
$marker = '<h2 class="wp-block-heading" id="tour-features">';
$anchoredStart = strpos($content, '<!-- wp:heading {"anchor":"tour-subscriptions"} -->');
if ($anchoredStart !== false) {
    $end = strpos($content, '<!-- wp:group {"align":"wide","className":"home-cta"');
    $details = substr($content, $anchoredStart, ($end === false ? strlen($content) : $end) - $anchoredStart);
} elseif (strpos($content, $marker) !== false) {
    // Later runs: the lists sit between the tour intro paragraph and the closing call to action.
    $start = strpos($content, '<!-- wp:heading -->', strpos($content, $marker));
    $end = strpos($content, '<!-- wp:group {"align":"wide","className":"home-cta"');
    $details = substr($content, $start, ($end === false ? strlen($content) : $end) - $start);
} else {
    $start = strpos($content, "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">Subscriptions</h2>");
    if ($start === false) {
        WP_CLI::error('Could not find the feature lists to keep.');
    }
    $details = substr($content, $start);
}
$details = trim($details);

// Multi-currency: show the products with exact price books right under the currency selector,
// so switching currency visibly reprices them.
// Keep exactly one currency product grid (an earlier run inserted it more than once).
$gridPattern = '/\n*<!-- wp:group \{"align":"wide","className":"currency-demo-products".*?<!-- \/wp:shortcode --><\/div><!-- \/wp:group -->/s';
if (preg_match_all($gridPattern, $details, $found) > 1) {
    $first = true;
    $details = preg_replace_callback($gridPattern, static function ($m) use (&$first) {
        if ($first) {
            $first = false;
            return $m[0];
        }
        return '';
    }, $details);
}
if (strpos($details, 'currency-demo-products') === false) {
    $ids = [];
    foreach (['meridian-wordpress-theme', 'formwise-pro', 'roastery-coffee-box', 'pattern-library-pass'] as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'product');
        if ($post) {
            $ids[] = (int) $post->ID;
        }
    }
    $selectorEnd = strpos($details, '<!-- /wp:group -->', (int) strpos($details, '[stackless_currency_selector]'));
    if ($ids && $selectorEnd !== false) {
        $grid = "\n\n" . '<!-- wp:group {"align":"wide","className":"currency-demo-products","layout":{"type":"default"}} --><div class="wp-block-group alignwide currency-demo-products">'
            . '<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} --><p class="has-muted-color has-text-color has-small-font-size">These four have exact prices set per currency. Switch above and watch them change; the rest of the shop converts at the store rate.</p><!-- /wp:paragraph -->'
            . '<!-- wp:shortcode -->[products ids="' . implode(',', $ids) . '" columns="4" orderby="post__in"]<!-- /wp:shortcode -->'
            . '</div><!-- /wp:group -->';
        $at = $selectorEnd + strlen('<!-- /wp:group -->');
        $details = substr($details, 0, $at) . $grid . substr($details, $at);
    }
}

// Section anchors for the jump links.
$sections = [
    'Subscriptions' => 'tour-subscriptions',
    'Checkout' => 'tour-checkout',
    'Multi-currency' => 'tour-currency',
    'Digital products, licenses and access' => 'tour-digital',
    'Catalog tools' => 'tour-catalog',
    'Store tools' => 'tour-store',
];
foreach ($sections as $title => $anchor) {
    $details = str_replace(
        "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$title}</h2>",
        "<!-- wp:heading {\"anchor\":\"{$anchor}\"} -->\n<h2 class=\"wp-block-heading\" id=\"{$anchor}\">{$title}</h2>",
        $details
    );
}
// Bold the plain-text lead-in of list items ("Order bumps: ...") for scanning.
$details = preg_replace('/<li>([A-Z][^<:]{2,40}):/', '<li><strong>$1</strong>:', $details);
// Shorter currency intro: the product grid under the selector names the price-book products.
$details = preg_replace('/<p>Pick a currency here, then browse\..*?<\/p>/s', '<p>Pick a currency, then browse. The four products below have exact prices per currency; everything else converts at the store rate with rounding.</p>', $details, 1);
$details = preg_replace('/<!-- wp:paragraph \{"textColor":"muted","fontSize":"small"\} --><p class="has-muted-color has-text-color has-small-font-size">These four have exact prices set per currency\..*?<!-- \/wp:paragraph -->/s', '', $details, 1);
// Demo limitations as a tinted notice.
if (strpos($details, 'tour-limits') === false) {
    $limitsAt = strpos($details, "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What you cannot try here</h2>");
    if ($limitsAt !== false) {
        $limits = trim(substr($details, $limitsAt));
        $limits = str_replace(["<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">", '</h2>'], ['<!-- wp:heading {"level":3} -->' . "\n" . '<h3 class="wp-block-heading">', '</h3>'], $limits);
        $limits = str_replace('<!-- /wp:heading -->', '<!-- /wp:heading -->', $limits);
        $details = substr($details, 0, $limitsAt)
            . '<!-- wp:group {"className":"tour-limits","style":{"border":{"radius":"12px","width":"1px"},"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"backgroundColor":"surface","borderColor":"line","layout":{"type":"default"}} -->'
            . '<div class="wp-block-group tour-limits has-border-color has-line-border-color has-surface-background-color has-background" style="border-width:1px;border-radius:12px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">'
            . $limits . '</div><!-- /wp:group -->';
    }
}

/* ------------------------------------------------------------------ Helpers */
$u = static function (string $path = ''): string {
    return esc_url(home_url('/' . ltrim($path, '/')));
};
$p = static function (string $slug) use ($u): string {
    $post = get_page_by_path($slug, OBJECT, 'product');
    return $post ? esc_url(get_permalink($post)) : $u('shop/');
};
$para = static function (string $html, string $attrs = '', string $class = ''): string {
    return '<!-- wp:paragraph' . ($attrs ? ' ' . $attrs : '') . ' --><p' . ($class ? ' class="' . $class . '"' : '') . '>' . $html . '</p><!-- /wp:paragraph -->';
};
$button = static function (string $label, string $href, string $style = ''): string {
    if ($style === 'outline') {
        return '<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="' . $href . '">' . $label . '</a></div><!-- /wp:button -->';
    }
    if ($style === 'ghost-white') {
        return '<!-- wp:button {"style":{"border":{"width":"1px","color":"#ffffff"},"color":{"background":"#ffffff00","text":"#ffffff"}}} --><div class="wp-block-button"><a class="wp-block-button__link has-text-color has-background has-border-color wp-element-button" href="' . $href . '" style="border-color:#ffffff;border-width:1px;color:#ffffff;background-color:#ffffff00">' . $label . '</a></div><!-- /wp:button -->';
    }
    if ($style === 'white') {
        return '<!-- wp:button {"backgroundColor":"white","textColor":"dark"} --><div class="wp-block-button"><a class="wp-block-button__link has-dark-color has-white-background-color has-text-color has-background wp-element-button" href="' . $href . '">' . $label . '</a></div><!-- /wp:button -->';
    }
    return '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . $href . '">' . $label . '</a></div><!-- /wp:button -->';
};
$buttons = static function (array $items, bool $center = false): string {
    return '<!-- wp:buttons' . ($center ? ' {"layout":{"type":"flex","justifyContent":"center"}}' : '') . ' --><div class="wp-block-buttons">' . implode('', $items) . '</div><!-- /wp:buttons -->';
};
$card = static function (string $title, string $text, array $links) use ($para): string {
    $linkHtml = implode(' · ', array_map(static function ($l) {
        return '<a href="' . $l[1] . '">' . esc_html($l[0]) . '</a>';
    }, $links));
    return '<!-- wp:group {"style":{"border":{"radius":"12px","width":"1px"},"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"backgroundColor":"white","borderColor":"line"} -->'
        . '<div class="wp-block-group has-border-color has-line-border-color has-white-background-color has-background" style="border-width:1px;border-radius:12px;padding-top:1.5rem;padding-right:1.5rem;padding-bottom:1.5rem;padding-left:1.5rem">'
        . '<!-- wp:heading {"level":3,"fontSize":"large","style":{"spacing":{"margin":{"bottom":"0.5rem"}}}} --><h3 class="wp-block-heading has-large-font-size" style="margin-bottom:0.5rem">' . esc_html($title) . '</h3><!-- /wp:heading -->'
        . $para($text, '{"textColor":"muted","style":{"spacing":{"margin":{"bottom":"0.75rem"}}}}', 'has-muted-color has-text-color" style="margin-bottom:0.75rem')
        . $para($linkHtml, '{"style":{"spacing":{"margin":{"bottom":"0"}}}}', '" style="margin-bottom:0')
        . '</div><!-- /wp:group -->';
};
$fact = static function (string $number, string $label): string {
    return '<!-- wp:group {"style":{"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"1.25rem","right":"1.25rem"}},"border":{"radius":"12px","width":"1px"}},"borderColor":"line","layout":{"type":"default"}} -->'
        . '<div class="wp-block-group has-border-color has-line-border-color" style="border-width:1px;border-radius:12px;padding-top:1rem;padding-right:1.25rem;padding-bottom:1rem;padding-left:1.25rem">'
        . '<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"0.25rem"}},"typography":{"lineHeight":"1.1","fontWeight":"700"}},"fontSize":"xx-large"} --><p class="has-xx-large-font-size" style="margin-bottom:0.25rem;font-weight:700;line-height:1.1">' . $number . '</p><!-- /wp:paragraph -->'
        . '<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"0"}}},"textColor":"muted","fontSize":"small"} --><p class="has-muted-color has-text-color has-small-font-size" style="margin-bottom:0">' . $label . '</p><!-- /wp:paragraph -->'
        . '</div><!-- /wp:group -->';
};

/* ------------------------------------------------------------------ Sections */
$hero = '<!-- wp:group {"align":"wide","style":{"border":{"radius":"16px"},"spacing":{"padding":{"top":"4rem","bottom":"4rem","left":"2rem","right":"2rem"}}},"backgroundColor":"primary-light","layout":{"type":"constrained","contentSize":"760px"}} -->'
    . '<div class="wp-block-group alignwide has-primary-light-background-color has-background" style="border-radius:16px;padding-top:4rem;padding-right:2rem;padding-bottom:4rem;padding-left:2rem">'
    . $para('<strong>Live demo store</strong> · Stackless for WooCommerce 1.0.7', '{"align":"center","fontSize":"small","style":{"spacing":{"margin":{"bottom":"0.5rem"}}}}', 'has-text-align-center has-small-font-size" style="margin-bottom:0.5rem')
    . '<!-- wp:heading {"textAlign":"center","level":1,"fontSize":"xx-large","style":{"spacing":{"margin":{"top":"0"}}}} --><h1 class="wp-block-heading has-text-align-center has-xx-large-font-size" style="margin-top:0">One plugin runs this whole store</h1><!-- /wp:heading -->'
    . $para('Subscriptions, multi-currency checkout, licenses, quotes and wishlists, all inside WooCommerce, in one fictional shop.', '{"align":"center","fontSize":"large"}', 'has-text-align-center has-large-font-size')
    . $buttons([
        $button('Browse the shop', $u('shop/')),
        $button('Take the feature tour', '#tour-features', 'outline'),
        $button('Get Stackless', esc_url('https://gauravtiwari.org/product/stackless-for-woocommerce/'), 'outline'),
    ], true)
    . $para('Safe to click: orders, payments and emails are switched off, so nothing is charged or sent.', '{"align":"center","textColor":"muted","fontSize":"small","style":{"spacing":{"margin":{"top":"1.25rem","bottom":"0"}}}}', 'has-text-align-center has-muted-color has-text-color has-small-font-size" style="margin-top:1.25rem;margin-bottom:0')
    . '</div><!-- /wp:group -->';

$facts = '<!-- wp:group {"align":"wide","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} --><div class="wp-block-group alignwide">'
    . $fact('30', 'modules in one plugin')
    . $fact('4', 'currencies at checkout')
    . $fact('5', 'checkout layouts')
    . $fact('21', 'demo products to try')
    . '</div><!-- /wp:group -->';

$bestsellers = '<!-- wp:group {"align":"wide","layout":{"type":"default"}} --><div class="wp-block-group alignwide">'
    . '<!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"0.5rem"}}}} --><h2 class="wp-block-heading" style="margin-bottom:0.5rem">Best sellers in the demo</h2><!-- /wp:heading -->'
    . $para('Sold counts, stock urgency, wishlist buttons and custom add-to-cart text all come from Stackless.', '{"textColor":"muted"}', 'has-muted-color has-text-color')
    . '<!-- wp:shortcode -->[products limit="4" columns="4" orderby="popularity"]<!-- /wp:shortcode -->'
    . '</div><!-- /wp:group -->';

$cards = [
    ['Subscriptions', 'Trials, signup fees, fixed-length plans and mixed carts with recurring totals.', [['Coffee box', $p('roastery-coffee-box')], ['Membership', $p('makers-club-membership')], ['3 payments', $p('wheel-throwing-masterclass')]]],
    ['Checkout', 'Order bumps, delivery slots, consent boxes, a trust block and the Warm Split layout.', [['Formwise Pro', $p('formwise-pro')], ['Stoneware Mug', $p('stoneware-mug')]]],
    ['Multi-currency', 'Switch to EUR, GBP or INR and the shop and cart reprice, with exact price books on some products.', [['Meridian theme', $p('meridian-wordpress-theme')], ['Pattern Library', $p('pattern-library-pass')]]],
    ['Licenses and access', 'License keys with site limits, lessons locked behind a purchase, and booked services.', [['Formwise Pro', $p('formwise-pro')], ['Video course', $p('launch-your-store-course')], ['Setup session', $p('store-setup-session')]]],
    ['Catalog tools', 'Sold counts, low-stock urgency, back-in-stock alerts, quantity rules and hidden wholesale prices.', [['Sketchbook', $p('hand-bound-sketchbook')], ['Candle', $p('beeswax-studio-candle')], ['Wholesale', $p('cafe-mug-case-wholesale')]]],
    ['Store tools', 'Wishlists without an account, quote requests and signed buy links.', [['Wishlist', $u('wishlist/')], ['Request a quote', $u('request-a-quote/')], ['Dinnerware set', $p('restaurant-dinnerware-set')]]],
];
$grid = '<!-- wp:group {"align":"wide","layout":{"type":"default"}} --><div class="wp-block-group alignwide">'
    . '<!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"0.5rem"}}}} --><h2 class="wp-block-heading" style="margin-bottom:0.5rem">What to try</h2><!-- /wp:heading -->'
    . $para('Each card points at products set up for that feature. The full list is further down.', '{"textColor":"muted"}', 'has-muted-color has-text-color')
    . '<!-- wp:group {"layout":{"type":"grid","minimumColumnWidth":"18rem"}} --><div class="wp-block-group">';
foreach ($cards as [$t, $x, $l]) {
    $grid .= $card($t, $x, $l);
}
$grid .= '</div><!-- /wp:group --></div><!-- /wp:group -->';

$theme = '<!-- wp:group {"align":"wide","style":{"border":{"radius":"16px"},"spacing":{"padding":{"top":"3rem","bottom":"3rem","left":"2.5rem","right":"2.5rem"}}},"backgroundColor":"dark","textColor":"white","layout":{"type":"default"}} -->'
    . '<div class="wp-block-group alignwide has-white-color has-dark-background-color has-text-color has-background" style="border-radius:16px;padding-top:3rem;padding-right:2.5rem;padding-bottom:3rem;padding-left:2.5rem">'
    . '<!-- wp:columns {"verticalAlignment":"center"} --><div class="wp-block-columns are-vertically-aligned-center">'
    . '<!-- wp:column {"verticalAlignment":"center","width":"60%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:60%">'
    . $para('Free theme', '{"fontSize":"small","textColor":"line"}', 'has-line-color has-text-color has-small-font-size')
    . '<!-- wp:heading {"textColor":"white"} --><h2 class="wp-block-heading has-white-color has-text-color">Styled by the Stackless theme</h2><!-- /wp:heading -->'
    . $para('Every page here, including checkout, the quote form and the account screens, uses the free Stackless WordPress theme. It is a minimal classic theme with full Block Editor support, built on Tabler\'s design foundation.', '{"textColor":"line"}', 'has-line-color has-text-color')
    . $buttons([
        $button('What the theme does', $u('theme/'), 'white'),
        $button('Get the theme free', esc_url('https://gauravtiwari.org/product/stackless-theme/')),
    ])
    . '</div><!-- /wp:column -->'
    . '<!-- wp:column {"verticalAlignment":"center","width":"40%"} --><div class="wp-block-column is-vertically-aligned-center" style="flex-basis:40%">'
    . '<!-- wp:list {"textColor":"white"} --><ul class="wp-block-list has-white-color has-text-color">'
    . '<!-- wp:list-item --><li>Full-screen search with live results</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>Sticky product summary</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>Every Stackless module styled</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>Cart and Checkout blocks matched to the theme</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>Editorial posts with author box</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>44px controls and stacked labels on every form</li><!-- /wp:list-item -->'
    . '<!-- wp:list-item --><li>Free automatic updates</li><!-- /wp:list-item -->'
    . '</ul><!-- /wp:list -->'
    . '</div><!-- /wp:column --></div><!-- /wp:columns -->'
    . '</div><!-- /wp:group -->';

$chips = implode(' ', array_map(static function ($title, $anchor) {
    return '<a class="chip" href="#' . $anchor . '">' . esc_html($title) . '</a>';
}, array_keys($sections), $sections));
$tourHead = '<!-- wp:heading {"anchor":"tour-features","style":{"spacing":{"margin":{"bottom":"0.5rem"}}}} --><h2 class="wp-block-heading" id="tour-features" style="margin-bottom:0.5rem">Every feature, with a product to try</h2><!-- /wp:heading -->'
    . $para('Follow any line below. Add things to the cart and go through checkout as far as the payment step.', '{"textColor":"muted"}', 'has-muted-color has-text-color')
    . $para($chips, '{"className":"tour-jump"}', 'tour-jump');

$cta = '<!-- wp:group {"align":"wide","className":"home-cta","style":{"border":{"radius":"16px"},"spacing":{"padding":{"top":"3.5rem","bottom":"3.5rem","left":"2rem","right":"2rem"}}},"backgroundColor":"primary","textColor":"white","layout":{"type":"constrained","contentSize":"640px"}} -->'
    . '<div class="wp-block-group alignwide home-cta has-white-color has-primary-background-color has-text-color has-background" style="border-radius:16px;padding-top:3.5rem;padding-right:2rem;padding-bottom:3.5rem;padding-left:2rem">'
    . '<!-- wp:heading {"textAlign":"center","textColor":"white"} --><h2 class="wp-block-heading has-text-align-center has-white-color has-text-color">Run your store on Stackless</h2><!-- /wp:heading -->'
    . $para('Everything on this site comes from one plugin and core WooCommerce. No stack of add-ons to keep in sync.', '{"align":"center","textColor":"white"}', 'has-text-align-center has-white-color has-text-color')
    . $buttons([
        $button('Get Stackless for WooCommerce', esc_url('https://gauravtiwari.org/product/stackless-for-woocommerce/'), 'white'),
        $button('Read the docs', esc_url('https://gauravtiwari.org/portal/course/stackless-for-woocommerce-training/lessons'), 'ghost-white'),
    ], true)
    . '</div><!-- /wp:group -->';

$new = implode("\n\n", [$hero, $facts, $bestsellers, $grid, $theme, $tourHead, $details, $cta]);
$saved = wp_update_post(wp_slash(['ID' => $tour->ID, 'post_content' => $new]), true);
if (is_wp_error($saved)) {
    WP_CLI::error($saved->get_error_message());
}
$check = get_post_field('post_content', $tour->ID);
echo wp_json_encode([
    'page' => $tour->ID,
    'details_kept' => strpos($check, $details) !== false,
    'blocks' => count(array_filter(parse_blocks($check), static function ($b) { return $b['blockName']; })),
    'bytes' => strlen($check),
]) . "\n";
