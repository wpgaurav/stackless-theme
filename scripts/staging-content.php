<?php
/**
 * Seeds theme review content on the Stackless staging site. Idempotent: re-running updates in place.
 * Run: wp eval-file staging-content.php
 *
 * Creates a Style guide page (every semantic element, form control and core block), three Journal
 * posts, Primary and Footer menus, and footer widgets. Front page settings are left alone.
 */

defined('ABSPATH') || exit;

function stt_upsert(string $type, string $slug, string $title, string $content, array $extra = []): int
{
    $existing = get_page_by_path($slug, OBJECT, $type);
    $data = array_merge(['post_type' => $type, 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content, 'post_status' => 'publish'], $extra);
    if ($existing) {
        $data['ID'] = $existing->ID;
        $id = wp_update_post(wp_slash($data), true);
    } else {
        $id = wp_insert_post(wp_slash($data), true);
    }
    if (is_wp_error($id)) {
        WP_CLI::error($slug . ': ' . $id->get_error_message());
    }
    WP_CLI::log('  ' . $type . ' ' . $slug . ' #' . $id);
    return (int) $id;
}

$shop = (int) wc_get_page_id('shop');
$img = '';
$attachments = get_posts(['post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 3, 'fields' => 'ids']);
if ($attachments) {
    $img = (string) wp_get_attachment_image_url($attachments[0], 'large');
}

/* ------------------------------------------------------------------ Style guide */
$imageBlock = $img
    ? '<!-- wp:image {"aspectRatio":"16/9","scale":"cover","sizeSlug":"large","align":"wide"} --><figure class="wp-block-image alignwide size-large"><img src="' . esc_url($img) . '" alt="Product photo" style="aspect-ratio:16/9;object-fit:cover"/><figcaption class="wp-element-caption">A wide image with a caption.</figcaption></figure><!-- /wp:image -->'
    : '';

$guide = <<<HTML
<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">This page shows every element the Stackless theme styles: text, lists, tables, code, media, forms and the core blocks. Use it to check a change in one place.</p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Headings and text</h2>
<!-- /wp:heading -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Heading level 3</h3>
<!-- /wp:heading -->

<!-- wp:heading {"level":4} -->
<h4 class="wp-block-heading">Heading level 4</h4>
<!-- /wp:heading -->

<!-- wp:heading {"level":5} -->
<h5 class="wp-block-heading">Heading level 5</h5>
<!-- /wp:heading -->

<!-- wp:heading {"level":6} -->
<h6 class="wp-block-heading">Heading level 6</h6>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Body text with <a href="#">a link</a>, <strong>strong</strong>, <em>emphasis</em>, <mark>marked text</mark>, <code>inline code</code>, <kbd>Cmd</kbd> + <kbd>K</kbd>, <abbr title="HyperText Markup Language">HTML</abbr>, <del>deleted</del> and <ins>inserted</ins> text, H<sub>2</sub>O, E = mc<sup>2</sup>, <small>small print</small> and <samp>sample output</samp>.</p>
<!-- /wp:paragraph -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>One plugin instead of a stack of add-ons, and a theme that stays out of its way.</p>
<!-- /wp:paragraph --><cite>Fernleaf Supply Co.</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>Fast pages sell more than clever ones.</p><cite>Shop notes</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Lists</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item --><li>Unordered item</li><!-- /wp:list-item --><!-- wp:list-item --><li>Item with a nested list<!-- wp:list --><ul class="wp-block-list"><!-- wp:list-item --><li>Nested item</li><!-- /wp:list-item --></ul><!-- /wp:list --></li><!-- /wp:list-item --><!-- wp:list-item --><li>Third item</li><!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list"><!-- wp:list-item --><li>Add products</li><!-- /wp:list-item --><!-- wp:list-item --><li>Turn on the modules you need</li><!-- /wp:list-item --><!-- wp:list-item --><li>Launch</li><!-- /wp:list-item --></ol>
<!-- /wp:list -->

<!-- wp:html -->
<dl><dt>SKU</dt><dd>FL-MUG-STONE</dd><dt>Ships in</dt><dd>2 to 3 business days</dd></dl>
<address>Fernleaf Supply Co.<br>12 Kiln Lane, Portland, OR 97205</address>
<!-- /wp:html -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Table</h2>
<!-- /wp:heading -->

<!-- wp:table {"className":"is-style-stripes"} -->
<figure class="wp-block-table is-style-stripes"><table><thead><tr><th>Plan</th><th>Price</th><th>Sites</th></tr></thead><tbody><tr><td>Starter</td><td>\$49</td><td>1</td></tr><tr><td>Studio</td><td>\$99</td><td>5</td></tr><tr><td>Agency</td><td>\$199</td><td>Unlimited</td></tr></tbody><tfoot><tr><td>All plans</td><td>Yearly</td><td>Updates included</td></tr></tfoot></table><figcaption class="wp-element-caption">A striped table with header and footer.</figcaption></figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Code</h2>
<!-- /wp:heading -->

<!-- wp:code -->
<pre class="wp-block-code"><code>add_filter('stackless_runtime_price_surcharge', function (\$surcharge, \$product) {
    return \$surcharge;
}, 10, 2);</code></pre>
<!-- /wp:code -->

<!-- wp:preformatted -->
<pre class="wp-block-preformatted">Preformatted text keeps   its spacing.</pre>
<!-- /wp:preformatted -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Media and layout</h2>
<!-- /wp:heading -->

{$imageBlock}

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Column one</h3>
<!-- /wp:heading --><!-- wp:paragraph -->
<p>Columns stack on small screens.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Column two</h3>
<!-- /wp:heading --><!-- wp:paragraph -->
<p>Each column keeps the theme's type scale.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","backgroundColor":"primary-light","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide has-primary-light-background-color has-background"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">A wide group with a background</h3>
<!-- /wp:heading --><!-- wp:paragraph -->
<p>Groups use the palette from theme.json and the theme's large radius.</p>
<!-- /wp:paragraph --><!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Primary button</a></div>
<!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Outline button</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:details -->
<details class="wp-block-details"><summary>Details block: what ships with Stackless?</summary><!-- wp:paragraph {"placeholder":"Type / to add a hidden block"} -->
<p>Subscriptions, payments, invoices, quotes, licensing, multi-currency and store tools.</p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:file -->
<div class="wp-block-file"><a href="#">stackless-price-sheet.pdf</a><a href="#" class="wp-block-file__button wp-element-button" download>Download</a></div>
<!-- /wp:file -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Forms</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<form class="style-guide-form" onsubmit="return false">
<fieldset style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));column-gap:1.25rem">
<legend>Contact details</legend>
<p><label for="sg-name">Name</label><input id="sg-name" type="text" placeholder="Jordan Park"></p>
<p><label for="sg-email">Email</label><input id="sg-email" type="email" placeholder="you@example.com"></p>
<p><label for="sg-phone">Phone</label><input id="sg-phone" type="tel" placeholder="+1 555 0100"></p>
<p><label for="sg-url">Website</label><input id="sg-url" type="url" placeholder="https://"></p>
<p><label for="sg-password">Password</label><input id="sg-password" type="password" value="hunter22"></p>
<p><label for="sg-number">Quantity</label><input id="sg-number" type="number" value="3" min="1"></p>
<p><label for="sg-date">Delivery date</label><input id="sg-date" type="date"></p>
<p><label for="sg-time">Time</label><input id="sg-time" type="time"></p>
<p><label for="sg-select">Country</label><select id="sg-select"><option>United States</option><option>India</option><option>United Kingdom</option></select></p>
<p><label for="sg-disabled">Disabled field</label><input id="sg-disabled" type="text" value="Not editable" disabled></p>
<p><label for="sg-multi">Interests</label><select id="sg-multi" multiple size="4"><option selected>Ceramics</option><option>Paper goods</option><option>Courses</option><option>Wholesale</option></select></p>
<p><label for="sg-message">Message</label><textarea id="sg-message" placeholder="How can we help?"></textarea></p>
<p><label for="sg-range">Budget <output id="sg-range-out" for="sg-range">60%</output></label><input id="sg-range" type="range" value="60" oninput="document.getElementById('sg-range-out').value=this.value+'%'"></p>
<p><label for="sg-color">Accent color</label><input id="sg-color" type="color" value="#066fd1"></p>
<p><label for="sg-progress">Order progress <output for="sg-progress">64%</output></label><progress id="sg-progress" value="64" max="100">64%</progress></p>
<p><label for="sg-meter">Stock level <output for="sg-meter">30%</output></label><meter id="sg-meter" value="0.3" low="0.25" high="0.75" optimum="1">30%</meter></p>
<p><label for="sg-file">Attachment</label><input id="sg-file" type="file"></p>
<p><label><input type="checkbox" checked> Email me order updates</label><label><input type="checkbox"> Join the newsletter</label></p>
<p><label><input type="radio" name="sg-ship" checked> Standard shipping</label><label><input type="radio" name="sg-ship"> Express shipping</label></p>
<p style="grid-column:1/-1"><button type="submit">Send message</button><input type="reset" value="Reset"><button type="button" class="button is-secondary">Secondary</button><button type="button" disabled>Disabled</button></p>
</fieldset>
</form>
<!-- /wp:html -->

<!-- wp:search {"label":"Search","buttonText":"Search"} /-->

<!-- wp:heading -->
<h2 class="wp-block-heading">Store blocks and shortcodes</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[products limit="3" columns="3" orderby="popularity"]
<!-- /wp:shortcode -->
HTML;

$guideId = stt_upsert('page', 'style-guide', 'Style guide', $guide);

/* ------------------------------------------------------------------ Journal */
// The blog index stays on the home page; the menu links there.
$journalUrl = home_url('/');

// Demo author for Journal posts (a byline needs a display name, not a login).
$author = get_user_by('login', 'fernleaf-editor');
$authorId = $author ? (int) $author->ID : (int) wp_insert_user([
    'user_login' => 'fernleaf-editor',
    'user_pass' => wp_generate_password(32),
    'user_email' => 'editor@fernleaf.example',
    'role' => 'author',
]);
wp_update_user([
    'ID' => $authorId,
    'first_name' => 'Maya',
    'last_name' => 'Chen',
    'display_name' => 'Maya Chen',
    'nickname' => 'Maya Chen',
    'description' => 'Maya runs the studio side of Fernleaf Supply Co. and writes about packing, pricing and the tools behind a small online shop.',
]);

$cat = term_exists('Shop notes', 'category') ?: wp_insert_term('Shop notes', 'category');
$catId = (int) (is_array($cat) ? $cat['term_id'] : $cat);

$posts = [
    'how-we-pack-stoneware' => ['How we pack stoneware so it arrives in one piece', "<!-- wp:paragraph -->\n<p>Every mug ships in a molded pulp cradle inside a double-wall box. Here is what we learned after 2,000 orders.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">What failed first</h2>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Bubble wrap alone let handles snap on long routes. Pulp cradles cut breakage to under one in two hundred.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item --><li>Double-wall box</li><!-- /wp:list-item --><!-- wp:list-item --><li>Pulp cradle per mug</li><!-- /wp:list-item --><!-- wp:list-item --><li>Paper fill, no plastic</li><!-- /wp:list-item --></ul>\n<!-- /wp:list -->"],
    'subscriptions-for-coffee' => ['Running a coffee subscription without a second plugin', "<!-- wp:paragraph -->\n<p>The Roastery Coffee Box renews monthly through Stackless Subscriptions, with Stripe saving the card at checkout.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><!-- wp:paragraph -->\n<p>Customers can skip, pause or switch roasts from My Account.</p>\n<!-- /wp:paragraph --></blockquote>\n<!-- /wp:quote -->"],
    'wholesale-pricing' => ['Hiding wholesale prices until a trade account signs in', "<!-- wp:paragraph -->\n<p>Trade buyers see case pricing after signing in. Everyone else sees a sign-in prompt and a quote form.</p>\n<!-- /wp:paragraph -->"],
];
$i = 0;
$excerpts = [
    'how-we-pack-stoneware' => 'Molded pulp, double-wall boxes and no plastic: the packing changes that cut breakage after 2,000 orders.',
    'subscriptions-for-coffee' => 'How the Roastery Coffee Box renews every month with Stackless Subscriptions and Stripe, and what customers can change themselves.',
    'wholesale-pricing' => 'Trade buyers see case pricing after signing in; everyone else gets a sign-in prompt and a quote form.',
];
$tags = [
    'how-we-pack-stoneware' => ['shipping', 'packaging'],
    'subscriptions-for-coffee' => ['subscriptions', 'stripe', 'coffee'],
    'wholesale-pricing' => ['wholesale', 'pricing'],
];
foreach ($posts as $slug => [$title, $body]) {
    $id = stt_upsert('post', $slug, $title, $body, ['post_author' => $authorId, 'post_category' => [$catId], 'post_date' => gmdate('Y-m-d H:i:s', time() - (++$i) * DAY_IN_SECONDS)]);
    if (!empty($attachments[$i - 1])) {
        set_post_thumbnail($id, $attachments[$i - 1]);
    }
    wp_update_post(['ID' => $id, 'post_excerpt' => $excerpts[$slug]]);
    wp_set_post_tags($id, $tags[$slug]);
}

/* ------------------------------------------------------------------ Menus */
function stt_menu(string $name, string $location, array $items): void
{
    $menu = wp_get_nav_menu_object($name);
    $menuId = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    foreach (wp_get_nav_menu_items($menuId) ?: [] as $old) {
        wp_delete_post($old->ID, true);
    }
    $parents = [];
    foreach ($items as $key => $item) {
        $args = ['menu-item-title' => $item[0], 'menu-item-status' => 'publish'];
        if (is_int($item[1])) {
            $args += ['menu-item-object-id' => $item[1], 'menu-item-object' => get_post_type($item[1]), 'menu-item-type' => 'post_type'];
        } else {
            $args += ['menu-item-url' => $item[1], 'menu-item-type' => 'custom'];
        }
        if (!empty($item[2])) {
            $args['menu-item-parent-id'] = $parents[$item[2]];
        }
        $parents[$key] = (int) wp_update_nav_menu_item($menuId, 0, $args);
    }
    $locations = (array) get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $menuId;
    set_theme_mod('nav_menu_locations', $locations);
    WP_CLI::log('  menu ' . $name . ' → ' . $location);
}

$page = static function (string $slug): int {
    $p = get_page_by_path($slug);
    return $p ? (int) $p->ID : 0;
};

$cat_link = static function (string $slug): string {
    $link = get_term_link($slug, 'product_cat');
    return is_wp_error($link) ? home_url('/shop/') : $link;
};

stt_menu('Stackless Primary', 'primary', [
    'shop' => ['Shop', $shop],
    'plugins' => ['Themes and plugins', $cat_link('themes-plugins'), 'shop'],
    'goods' => ['Studio goods', $cat_link('studio-goods'), 'shop'],
    'courses' => ['Courses and services', $cat_link('courses-services'), 'shop'],
    'journal' => ['Journal', $journalUrl],
    'quote' => ['Request a quote', $page('request-a-quote')],
    'library' => ['Members', $page('makers-club-library')],
    'guide' => ['Style guide', $guideId],
]);
stt_menu('Stackless Footer', 'footer', [
    'privacy' => ['Privacy', $page('privacy-policy')],
    'refunds' => ['Refunds', $page('refund_returns')],
    'wishlist' => ['Wishlist', $page('wishlist')],
]);

/* ------------------------------------------------------------------ Footer widgets */
$blocks = (array) get_option('widget_block', []);
$footer = [];
$widgets = [
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Fernleaf Supply Co.</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ceramics, paper goods and courses for small makers, shipped from Portland.</p><!-- /wp:paragraph -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Shop</h2><!-- /wp:heading --><!-- wp:list --><ul class="wp-block-list"><li><a href="' . esc_url(get_permalink($shop)) . '">All products</a></li><li><a href="' . esc_url(wc_get_cart_url()) . '">Cart</a></li><li><a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">My account</a></li></ul><!-- /wp:list -->',
    '<!-- wp:heading {"level":2} --><h2 class="wp-block-heading">Journal</h2><!-- /wp:heading --><!-- wp:latest-posts {"postsToShow":3,"displayPostDate":true} /-->',
];
foreach ($blocks as $k => $v) {
    if (is_array($v) && !empty($v['stt'])) {
        unset($blocks[$k]);
    }
}
$next = max(array_filter(array_keys($blocks), 'is_int') ?: [0]) + 1;
foreach ($widgets as $content) {
    $blocks[$next] = ['content' => $content, 'stt' => 1];
    $footer[] = 'block-' . $next;
    $next++;
}
update_option('widget_block', $blocks);
$sidebars = (array) get_option('sidebars_widgets', []);
$sidebars['footer'] = $footer;
update_option('sidebars_widgets', $sidebars);
WP_CLI::log('  footer widgets: ' . count($footer));

WP_CLI::success('Theme review content ready: ' . get_permalink($guideId));
