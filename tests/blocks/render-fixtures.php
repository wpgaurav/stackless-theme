<?php
/**
 * Render test for the Slider and Carousel blocks: parses each fixture in tests/blocks/fixtures/,
 * renders it with do_blocks() and checks the markup the front end and screen readers rely on.
 *
 * Usage (on a site running the theme):
 *   studio wp --path <site> eval-file tests/blocks/render-fixtures.php
 *   wp eval-file tests/blocks/render-fixtures.php
 *
 * @package Stackless_Theme
 */

defined('ABSPATH') || exit;

$stb_failures = 0;
$stb_check = static function (bool $ok, string $message) use (&$stb_failures): void {
    echo ($ok ? 'PASS' : 'FAIL') . '  ' . $message . "\n";
    if (!$ok) {
        ++$stb_failures;
    }
};
$stb_fixture = static function (string $name): string {
    return (string) file_get_contents(__DIR__ . '/fixtures/' . $name); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local fixture.
};
$stb_count = static function (string $html, string $needle): int {
    return substr_count($html, $needle);
};

foreach (['slider', 'slide', 'carousel'] as $stb_name) {
    $stb_check(WP_Block_Type_Registry::get_instance()->is_registered('stackless/' . $stb_name), "stackless/$stb_name is registered");
}

/* Slider */
$stb_html = do_blocks($stb_fixture('slider.html'));
$stb_check($stb_count($stb_html, 'aria-roledescription="carousel"') === 1, 'slider: one carousel region');
$stb_check((bool) preg_match('/<section [^>]*aria-label="New season"/', $stb_html), 'slider: labelled from the label attribute');
$stb_check($stb_count($stb_html, 'aria-roledescription="slide"') === 3, 'slider: three slides');
foreach (['1 of 3', '2 of 3', '3 of 3'] as $stb_label) {
    $stb_check(strpos($stb_html, 'aria-label="' . $stb_label . '"') !== false, "slider: a slide labelled \"$stb_label\"");
}
$stb_check($stb_count($stb_html, 'loading="eager"') === 1 && $stb_count($stb_html, 'loading="lazy"') === 2, 'slider: first photo eager, the rest lazy');
$stb_check(strpos($stb_html, 'data-autoplay="5"') !== false, 'slider: autoplay interval passed to the script');
$stb_check(strpos($stb_html, 'class="stb-scroller-pause') !== false, 'slider: pause button printed when autoplay is on');
$stb_check($stb_count($stb_html, 'stb-scroller-arrow') === 2 && $stb_count($stb_html, '<svg') >= 2, 'slider: two arrows with their icons');
$stb_check(strpos($stb_html, 'is-v-center is-h-left') !== false && strpos($stb_html, 'is-v-top is-h-right') !== false, 'slider: content positions');
$stb_check(strpos($stb_html, 'object-position:50% 30%') !== false, 'slider: focal point');
$stb_check(strpos($stb_html, 'alignfull') !== false, 'slider: full width by default');

/* Carousel of blocks */
$stb_html = do_blocks($stb_fixture('carousel.html'));
$stb_check($stb_count($stb_html, 'class="stb-carousel-item"') === 5, 'carousel: each block wrapped as an item');
$stb_check(strpos($stb_html, 'aria-label="5 of 5"') !== false, 'carousel: items labelled "x of n"');
$stb_check(strpos($stb_html, '--stb-per-view-phone:1.2') !== false && strpos($stb_html, '--stb-per-view:3') !== false, 'carousel: items per view as custom properties');
$stb_check(strpos($stb_html, 'class="stb-scroller-dots"') !== false && strpos($stb_html, 'stb-scroller-pause') === false, 'carousel: dots, and no pause button without autoplay');

/* Carousel around a single Query Loop */
$stb_html = do_blocks($stb_fixture('carousel-query.html'));
$stb_check((bool) preg_match('/<ul [^>]*class="[^"]*wp-block-post-template[^"]*stb-scroller-track/', $stb_html), 'carousel + Query Loop: the post list is the row');
$stb_check(strpos($stb_html, 'stb-carousel-item') === false && strpos($stb_html, 'is-list') !== false, 'carousel + Query Loop: no item wrappers');

/* Carousel around a Product Collection */
if (WP_Block_Type_Registry::get_instance()->is_registered('woocommerce/product-collection')) {
    $stb_html = do_blocks($stb_fixture('carousel-products.html'));
    $stb_check((bool) preg_match('/<ul [^>]*class="[^"]*wc-block-product-template[^"]*stb-scroller-track/', $stb_html), 'carousel + Product Collection: the product list is the row');
    $stb_check(strpos($stb_html, 'stb-scroller-progress-bar') !== false, 'carousel + Product Collection: progress bar');
} else {
    echo "SKIP  carousel + Product Collection: WooCommerce is not active\n";
}

/* Nesting: a Carousel inside a Carousel item counts its own items. */
$stb_inner = '<!-- wp:stackless/carousel {"label":"Inner"} --><!-- wp:paragraph --><p>a</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>b</p><!-- /wp:paragraph --><!-- /wp:stackless/carousel -->';
$stb_html = do_blocks('<!-- wp:stackless/carousel {"label":"Outer"} -->' . $stb_inner . '<!-- wp:paragraph --><p>c</p><!-- /wp:paragraph --><!-- wp:paragraph --><p>d</p><!-- /wp:paragraph --><!-- /wp:stackless/carousel -->');
$stb_check($stb_count($stb_html, 'aria-label="1 of 3"') === 1 && $stb_count($stb_html, 'aria-label="3 of 3"') === 1 && $stb_count($stb_html, 'aria-label="2 of 2"') === 1, 'nested carousels number their own items');

/* Two sliders on one page: only the first photo of the first gets fetchpriority. */
$stb_html = do_blocks($stb_fixture('slider.html') . $stb_fixture('slider.html'));
$stb_check($stb_count($stb_html, 'fetchpriority="high"') <= 1, 'two sliders: at most one high-priority photo');
$stb_check($stb_count($stb_html, 'aria-label="1 of 3"') === 2, 'two sliders: each numbers its slides from 1');

/* An empty Slider or Carousel prints nothing. */
$stb_check(trim(do_blocks('<!-- wp:stackless/slider /-->')) === '' && trim(do_blocks('<!-- wp:stackless/carousel /-->')) === '', 'empty blocks print nothing');

echo $stb_failures ? "\n$stb_failures failed\n" : "\nAll passed\n";
