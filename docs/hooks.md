# Stackless theme hooks

Every visible part of a Stackless page can be added to, hidden, replaced, re-classed or re-worded from a plugin, without copying a template. Site layouts (Stackless Pro) are built on these hooks, plus the theme's option defaults and Page Blocks Builder regions.

The API lives in `theme/inc/hooks.php`. With nothing hooked in, the output is exactly what the templates print.

## The pattern

Every part follows one naming pattern. For a part named `post_title`:

| Hook | Type | Arguments | Use it to |
|---|---|---|---|
| `stackless_theme_show_post_title` | filter | `bool $show, array $context` | Hide the part (return `false`). |
| `stackless_theme_show_part` | filter | `bool $show, string $part, array $context` | Hide any part by name. |
| `stackless_theme_pre_post_title` | filter | `null, array $context` | Replace the part: return an HTML string. |
| `stackless_theme_before_post_title` | action | `array $context` | Print before the part. |
| `stackless_theme_post_title_html` | filter | `string $html, array $context` | Rewrite the part's rendered markup. |
| `stackless_theme_part_html` | filter | `string $html, string $part, array $context` | Rewrite any part's markup. |
| `stackless_theme_after_post_title` | action | `array $context` | Print after the part. |
| `stackless_theme_post_title_class` | filter | `array $classes, array $context` | Change the classes, where the part has a class list. |
| `stackless_theme_class` | filter | `array $classes, string $part, array $context` | Change any part's classes. |

Notes:

- **Before and after actions** print outside the captured markup, so the `_html` filters never see what they add.
- **A hidden part** fires none of its hooks.
- **A replaced part** still fires its before and after actions.

Container parts also fire `stackless_theme_{part}_start` and `stackless_theme_{part}_end` *inside* the element. Use those to add an item at either end of a row, such as a button at the start of the header actions.

### Helpers for your own markup

```php
stackless_theme_part('my_part', function (array $context) { echo '...'; }, $context);

if (stackless_theme_part_start('my_part', $context)) {
    echo '...';
    stackless_theme_part_end();
}

stackless_theme_class('my_part', ['a', 'b'], $context);  // escaped class attribute value
stackless_theme_text('my_key', __('Default', 'my-plugin'), $context);  // filterable text
```

## Parts

### Header and footer

| Part | Where | Classes | Inside actions |
|---|---|---|---|
| `header` | The site header element | yes (`header`, `header_inner`) | `header_start`, `header_end` |
| `header_brand` | Logo or site title | yes | |
| `header_nav` | Primary menu | yes | |
| `header_actions` | Search, account, cart and menu buttons | yes | `header_actions_start`, `header_actions_end` |
| `header_search_button` | Search button | | |
| `header_account` | Account link (`$context['url']`) | | |
| `header_cart` | Cart link | | |
| `header_menu_toggle` | Mobile menu button | | |
| `search_overlay` | Full-screen search | yes | |
| `search_form` | Overlay search field | | |
| `search_categories` | Overlay "Popular categories" | | |
| `search_best_sellers` | Overlay "Best sellers" | | |
| `footer` | The site footer element | yes | `footer_start`, `footer_end` |
| `footer_widgets` | Footer widget columns | yes | |
| `footer_bottom` | Copyright row | yes | `footer_bottom_start`, `footer_bottom_end` |
| `footer_info` | Copyright and theme credit line | | |
| `footer_menu` | Footer menu | | |

Page Blocks Builder's Header and Footer regions replace the theme's header and footer when they have blocks. Filter `stackless_theme_region_mode` to `before` or `after` to keep both.

#### Header layout

`apply_filters('stackless_theme_header_layout', array $layout)` decides where each header part sits. `$layout` has three groups, each a list of part slugs:

```php
// The default: the same markup as the fixed header before 1.8.0, byte for byte.
[
    'start'  => ['branding', 'navigation'],
    'center' => [],
    'end'    => ['currency', 'search', 'account', 'cart', 'menu_toggle'],
]
```

| Slug | Part | Prints when |
|---|---|---|
| `branding` | `header_brand` | Always |
| `navigation` | `header_nav` (`#site-navigation`, the menu the toggle opens) | Always |
| `currency` | The header currency switcher | Stackless for WooCommerce's currency selector is on |
| `search` | `header_search_button` | Header and menu > Search button is on |
| `account` | `header_account` | Store > Account button is on and there is an account URL |
| `cart` | `header_cart` | Store > Cart button is on and a store supplies a cart link |
| `menu_toggle` | `header_menu_toggle` | Always (shown below 1080px) |

Rules:

- **Unknown slugs** are skipped, and so is anything that is not a string.
- **A slug appears once.** Placed twice, it keeps its first place.
- **A slug left out** does not print. Keep `menu_toggle` somewhere if `navigation` is placed, or phones lose the menu.
- **`start` parts** print straight into `.site-header__inner`, as they always have.
- **`end` parts** print inside the `header_actions` part (`.site-header__actions`), between `header_actions_start` and `header_actions_end`. The row is skipped when `end` is empty and nothing is hooked to those actions, unless `center` has parts.
- **A non-empty `center`** adds `site-header--has-center` to the header and wraps the groups in `.site-header__start` and `.site-header__center`. The bar becomes a three-column grid (`1fr auto 1fr`), so the center group sits in the middle of the page.

```php
// A centered logo with the menu on the left.
add_filter('stackless_theme_header_layout', function (array $layout): array {
    return [
        'start'  => ['navigation'],
        'center' => ['branding'],
        'end'    => ['search', 'account', 'cart', 'menu_toggle'],
    ];
});
```

`stackless_theme_header_layout()` returns the cleaned layout and `stackless_theme_header_part_slugs()` the known slugs. Below 1080px the menu opens under the header wherever `navigation` sits.

### Single posts

`$context` holds:
- `post_id`
- `category_position`, `byline_position`
- `header_style`
- `layout`

| Part | Where | Classes | Inside actions |
|---|---|---|---|
| `post_hero` | Full-width featured image above post and sidebar | yes | |
| `single_layout` | Post and sidebar wrapper (sidebar layouts) | yes | |
| `single_sidebar` | The sidebar column | yes | `single_sidebar_start`, `single_sidebar_end` |
| `post` | The article | yes | |
| `post_breadcrumbs` | Breadcrumb trail | | |
| `post_media` | Featured image in the article | yes | |
| `post_header` | Title block | yes | `post_header_start`, `post_header_end` |
| `post_category` | Category label above the title | | |
| `post_byline` | Author, date, reading time | yes | |
| `post_title` | The title | | |
| `post_standfirst` | Excerpt under the title | | |
| `post_content` | The content | | |
| `post_footer` | Tags and author box | yes | `post_footer_start`, `post_footer_end` |
| `post_tags` | Category and tag chips | | |
| `post_author_box` | Author box | | |
| `post_navigation` | Previous and next links | | |
| `related_posts` | Related posts | yes | |
| `comments` | Comments | yes | |

The `post_footer` part prints even when it has no tags or author box, as long as something is hooked to `post_footer_start` or `post_footer_end`.

### Pages and custom post types

`$context` holds `post_id`, `post_type` and `custom_post_type`.

| Part | Where | Classes |
|---|---|---|
| `entry` | The article | yes |
| `entry_header` | Title block | |
| `entry_meta` | Date and author | |
| `entry_title` | The title | |
| `entry_media` | Featured image | |
| `entry_content` | The content | |
| `entry_footer` | Categories and tags (posts) | |
| `entry_navigation` | Previous and next links | |

The Full width and Canvas templates also fire `stackless_theme_before_entry_content` and `stackless_theme_after_entry_content`.

#### Layout choices on pages and custom post types

The editor's Layout panel offers the post layout (centered, sidebar on the right, sidebar on the left) and header style on pages and public custom post types too. Store products (`product`, `download`, `fluent-products`) are left out, since they use their own templates.

- **Without a choice** a page or custom post type item looks as it always did: centered, no sidebar, its usual title. The Customizer's Single posts options never reach them.
- **With a sidebar** it uses the `single_layout` and `single_sidebar` parts, like a post, with `template-parts/content-single.php` inside. Related posts stay on posts only.
- **A header style** adds `entry-header--center`, `entry-header--left`, `entry-header--image-first` or `entry-header--image-full` to its `.entry-header`. "Featured image above the title" shows the image even on types that hide it. "Full-width featured image" prints the `post_hero` part.
- **A page template that owns its layout** (`stackless_theme_layout_owning_templates`) ignores both choices.

| Filter | Use it to |
|---|---|
| `stackless_theme_layout_post_types` | The post types the Layout panel shows on (every public type but attachments). The layout and header style choices apply to these types, minus the store product types. |
| `stackless_theme_layout_choices` | The choices themselves. Each has a `types` list you can narrow or widen. |

`stackless_theme_single_has_sidebar()`, `stackless_theme_single_layout()` and `stackless_theme_entry_header_style()` report the result for the item being viewed.

### Blog, archives and search

`$context['archive']` is `home`, `search` or `archive`.

| Part | Where | Classes | Inside actions |
|---|---|---|---|
| `archive_header` | Page title block | | |
| `archive_title` | The title | | |
| `archive_description` | Term description | | |
| `post_list` | The list or grid of cards | yes | |
| `post_card` | One card (`$context['product']` for store results) | yes | |
| `card_media` | Card image | | |
| `card_meta` | Card date and author | | |
| `card_title` | Card title | | |
| `card_price` | Product price (store results) | | |
| `card_excerpt` | Card excerpt | | |
| `card_more` | Read more / View product | | |
| `pagination` | Page numbers | | |
| `blog_sidebar` | The sidebar | yes | `blog_sidebar_start`, `blog_sidebar_end` |
| `empty_state` | Nothing found | yes | `empty_state_start`, `empty_state_end` |
| `search_form_inline` | `get_search_form()` | yes | |
| `not_found` | The 404 message | | |

### Store

The FluentCart product page and the EDD download page share these parts. `$context['store']` is `fluentcart` or `edd`. WooCommerce keeps its own `woocommerce_*` hooks.

| Part | Where | Classes | Inside actions |
|---|---|---|---|
| `product_breadcrumbs` | Breadcrumb trail | | |
| `product` | The product article | yes | |
| `product_media` | Gallery or image | | |
| `product_summary` | Buy column | | `product_summary_start`, `product_summary_end` |
| `product_title` | Title | | |
| `product_price` | Price | | |
| `product_excerpt` | Short description | | |
| `product_purchase` | Options and buy buttons | | |
| `product_meta` | Category line | | |
| `product_content` | Description | | |
| `product_related` | Related products | | |
| `product_grid` | EDD archive grid | yes | |
| `product_card` | EDD card | yes | |
| `product_sale_badge` | `<span class="stackless-sale-badge">Sale</span>` over the image of a FluentCart or EDD card (`$context['placement']` is `card`) or product page (`single`; above the title when an EDD download has no image). Prints, and fires its hooks, only when the product is on sale. | yes | |
| `store_toolbar` | Promo line, search, sorting, category chips (WooCommerce, EDD) | yes | |

Whether a product is on sale comes from the `stackless_theme_product_on_sale` filter (`bool $on_sale, int $post_id, string $store`), through `stackless_theme_product_on_sale($post_id, $store)`:

- **FluentCart:** the theme answers at priority 5: on sale when any variation's compare-at price is above its price. A filter at the default priority can overrule it.
- **EDD:** EDD has no sale price, so the default is `false` and no badge prints until a filter returns `true`, for example from a discount or a custom field.
- **WooCommerce:** keeps its own `.onsale` badge and `woocommerce_sale_flash` filter.

```php
// Show the badge on EDD downloads that carry a "_on_sale" meta value.
add_filter('stackless_theme_product_on_sale', function (bool $on_sale, int $post_id, string $store): bool {
    return $store === 'edd' ? (bool) get_post_meta($post_id, '_on_sale', true) : $on_sale;
}, 10, 3);
```

The FluentCart card badge prints from FluentCart's `fluent_cart/product/group/before_image_block` action, on the cards FluentCart draws with its own renderer (the product shortcodes, product lists, related products). Cards built from FluentCart's blocks (render source `gutenberg`, such as the default Shop page template) are skipped, because those templates carry FluentCart's own Sale badge block, which the site owner places or removes. FluentCart versions without render sources get the badge on every renderer card.

### Blocks

Each Stackless block renders as a part named `block_` plus the block's name with underscores, so the pattern above applies to it: `stackless_theme_show_block_callout`, `stackless_theme_pre_block_faq`, `stackless_theme_block_quote_html`, and so on. `$context` holds `block` (the name after the slash), `attributes` and `content` (the rendered inner blocks). The `_html` filters receive the finished markup, with icons and block-support classes in place.

| Part | Block |
|---|---|
| `block_arrow` | `stackless/arrow` |
| `block_book_card` | `stackless/book-card` |
| `block_callout` | `stackless/callout` |
| `block_content_upgrade` | `stackless/content-upgrade` |
| `block_faq`, `block_faq_item` | `stackless/faq` and its items |
| `block_github_card` | `stackless/github-card` |
| `block_key_takeaway` | `stackless/key-takeaway` |
| `block_npm_card` | `stackless/npm-card` |
| `block_plugin_card` | `stackless/plugin-card` |
| `block_podcast_card` | `stackless/podcast-card` |
| `block_pull_quote` | `stackless/pull-quote` |
| `block_query_loop` | `stackless/query-loop` |
| `block_quote` | `stackless/quote` |
| `block_related_content` | `stackless/related-content` |
| `block_section` | `stackless/section` |
| `block_share_notice` | `stackless/share-notice` |
| `block_side_promotion`, `block_side_promotion_item` | `stackless/side-promotion` and its panels |
| `block_spoiler` | `stackless/spoiler` |
| `block_steps`, `block_step` | `stackless/steps` and its steps |
| `block_theme_card` | `stackless/theme-card` |
| `block_table_of_contents` | `stackless/table-of-contents` (the list is filled once the post has rendered, so filter that with `stackless_theme_blocks_toc_markup`) |
| `block_youtube_card` | `stackless/youtube-card` |

Block filters:

| Filter | Use it to |
|---|---|
| `stackless_theme_blocks` | Add, remove or change block definitions before they register. |
| `stackless_theme_blocks_supports` | Change a block's supports (`$supports, $slug`). |
| `stackless_theme_blocks_icon_map` | Map a stored icon name to a Tabler icon in `assets/icons/`. |
| `stackless_theme_blocks_palette` | The colors offered by the blocks' color-class pickers. |
| `stackless_theme_blocks_editor_data` | The data handed to the editor scripts (colors, icons, card icons, popups). |
| `stackless_theme_blocks_popup_trigger` | Return the attributes that make a Callout or Content Upgrade button open a popup (`'', $popup, $slug`). Empty by default: the `popup` attribute is stored and ignored. |
| `stackless_theme_blocks_toc_defaults`, `stackless_theme_blocks_toc_markup` | The table of contents defaults, and its inner markup (`$html, $headings, $args`). |
| `stackless_theme_blocks_query_loop_max_posts` | The most posts one Query Loop may render (50). |
| `stackless_theme_blocks_related_image_size` | The image size for Related Content thumbnails. |
| `stackless_theme_blocks_refresh_cards` | Whether the Plugin, Theme, GitHub and npm cards refresh their stats (`true, $type`). Return false and they print the saved values and make no outside requests. |
| `stackless_theme_blocks_card_schema` | A card's structured data (`$data, $slug`). Return an empty array to print none. |
| `stackless_theme_blocks_core_styles`, `stackless_theme_blocks_core_legacy_styles_enabled` | The core block style variations, and whether the older ones show in the style picker. |

### Page wrapper

The `main` class filter is `stackless_theme_main_class`. Its `$context['template']` is `singular`, `index`, `404`, `full-width`, `canvas`, `single-download`, `single-fluent-products`, `archive-download` or `woocommerce`.

## Interface text

Filter `stackless_theme_text_{key}` (or `stackless_theme_text` with the key as the third argument) to change a label. Return plain text; the theme escapes it.

| Area | Keys |
|---|---|
| Header and menus | `menu`, `search`, `my_account`, `sign_in`, `primary_menu_label`, `footer_menu_label` |
| Search | `search_store_title`, `search_for`, `search_placeholder`, `search_results_title`, `popular_categories`, `best_sellers` |
| Posts | `previous`, `next`, `read_more`, `filed_under`, `written_by`, `about_author`, `related_posts_title`, `page_links_label`, `blog_sidebar_label` |
| Empty states | `empty_title`, `empty_text`, `empty_search_text`, `browse_all_products`, `not_found_title`, `not_found_text`, `back_home` |
| Store | `sale_badge`, `view_product`, `choose_option`, `choose_options`, `related_products_title`, `product_category_label`, `product_tags_label`, `breadcrumb_label`, `home` |
| Footer | `theme_credit` (keep the `%s`; it becomes the theme link) |

## Options, presets and design

A site layout ships its defaults through filters. A site owner's saved Customizer values always win over them.

| Filter | What it controls |
|---|---|
| `stackless_theme_option_defaults` | Defaults for every Stackless option: layouts, header style, archive grid, store toggles, mobile menu, and the rest. |
| `theme_mod_{name}` (WordPress core) | Any saved setting, including colors (`stackless_color_*`), widths (`stackless_width_*`), fonts (`stackless_font_body`, `stackless_font_heading`) and footer text. |
| `stackless_theme_option` / `stackless_theme_option_{key}` | The resolved option value at render time. |
| `stackless_theme_cpt_option_defaults` / `stackless_theme_cpt_option` | Custom post type options. |
| `stackless_theme_layout_choices` | The per-post Layout panel selects (posts, pages and custom post types; see above). |
| `stackless_theme_design_colors`, `stackless_theme_design_widths`, `stackless_theme_font_stacks`, `stackless_theme_bundled_fonts` | The design registries (add a color, a width or a font). |
| `stackless_theme_design_vars` / `stackless_theme_design_css` | The CSS custom properties and the printed design CSS. In the Customizer preview the color and font properties (`stackless_theme_design_live_properties()`) print in their own `stackless-theme-live-design` style and are redrawn in the browser as those settings change, so a filter on them shows after the next preview reload. |
| `stackless_theme_footer_text` | The footer line. |

```php
// A site layout's defaults: a grid blog, posts with a right sidebar and a full-width image,
// Inter body text with Anton headings in capitals.
add_filter('stackless_theme_option_defaults', function (array $defaults): array {
    return array_merge($defaults, [
        'stackless_archive_layout' => 'grid-3',
        'stackless_single_layout' => 'sidebar-right',
        'stackless_single_header' => 'image-full',
        'stackless_mobile_menu' => 'drawer',
    ]);
});
add_filter('theme_mod_stackless_font_body', fn ($value) => $value ?: 'font-inter');
add_filter('theme_mod_stackless_font_heading', fn ($value) => $value ?: 'font-anton');
add_filter('theme_mod_stackless_heading_case', fn ($value) => $value ?: 'uppercase');
```

## Customizer

The theme's options live in one panel, `STACKLESS_THEME_PANEL` (`stackless_theme_panel`). Once the theme has registered its own sections and controls, it fires:

```php
do_action('stackless_theme_customize_sections', WP_Customize_Manager $wp_customize, string $panel_id);
```

It runs on `customize_register` at priority 50, after the theme's own callbacks (10, 12, 15 and 20), so every theme section and setting exists by then. It also runs when the theme's settings import builds its own Customizer manager, so settings added here are sanitized on import too.

Section priorities in the panel:

| Priority | Section | ID |
|---|---|---|
| 10 | Colors | `stackless_theme_colors` |
| 20 | Typography | `stackless_theme_typography` |
| 30 | Layout | `stackless_theme_layout` |
| 40 | Header and menu | `stackless_theme_header` |
| 50 | Blog and archives | `stackless_theme` |
| 60 | Single posts | `stackless_theme_single` |
| 65 | Custom post types | `stackless_theme_cpt` |
| 70 | Store (only with a store plugin) | `stackless_theme_store` |
| 75 | Shop toolbar: WooCommerce | `stackless_theme_store_woo` |
| 76 | Shop toolbar: EDD | `stackless_theme_store_edd` |
| 90 (70 without a store) | Footer | `stackless_theme_footer` |
| 100 (80 without a store) | Custom code | `stackless_theme_code` |

Use a priority below 10 to sort before Colors, or one between two of these to sit between them.

```php
add_action('stackless_theme_customize_sections', function (WP_Customize_Manager $wp_customize, string $panel_id): void {
    $wp_customize->add_section('my_styles', [
        'title' => __('Styles', 'my-plugin'),
        'panel' => $panel_id,
        'priority' => 5,
    ]);
}, 10, 2);
```

While `STACKLESS_PRO_VERSION` is not defined, the panel's description ends with one line linking to Stackless Theme Pro. Defining the constant removes it.

## Templates

| Filter | Use it to |
|---|---|
| `stackless_theme_template_part_file` | Serve a template part from a plugin. Arguments: `string $file, string $slug, ?string $name, array $args`. Return a readable path. Every part the theme loads goes through it: `template-parts/site-header`, `template-parts/site-footer`, `template-parts/header-search`, `template-parts/content-post`, `template-parts/content-single`, `template-parts/content-summary`, `template-parts/content-none`, `template-parts/related-posts`, `template-parts/content-download-card`. |
| `stackless_theme_layout_owning_templates` | Page templates that bring their own layout (the single post options step aside). |
| `stackless_theme_custom_post_types` | Which custom post types get Customizer options. |

## Queries, arguments and sizes

| Filter | Arguments passed on |
|---|---|
| `stackless_theme_primary_menu_args`, `stackless_theme_footer_menu_args` | `wp_nav_menu()` |
| `stackless_theme_comment_list_args`, `stackless_theme_comment_form_args` | `wp_list_comments()`, `comment_form()` |
| `stackless_theme_post_navigation_args` | `the_post_navigation()` (posts, pages, custom post types) |
| `stackless_theme_related_posts_query` | The related items `WP_Query` |
| `stackless_theme_post_byline_meta` | Byline items: `date`, `reading_time`, `category` |
| `stackless_theme_post_media_size`, `stackless_theme_post_hero_size`, `stackless_theme_entry_media_size`, `stackless_theme_card_media_size` | Image sizes |
| `stackless_theme_search_terms`, `stackless_theme_search_popular` | What the search overlay's Discover shows |
| `stackless_theme_search_product` | A store product in search results (image and price) |
| `stackless_theme_breadcrumb_providers` | The SEO plugins tried for breadcrumbs |

## Page Blocks Builder regions

These are theme positions for Page Blocks Builder blocks. Each renders where the theme calls it, and only when it has published blocks.

| Region | Where |
|---|---|
| `header`, `footer` | Replace the site header or footer. |
| `hero` | After the header. |
| `before_content`, `after_content` | Around the main content of every template. |
| `sidebar` | Blog sidebar, and post sidebars. |
| `single_sidebar` | Top of the post sidebar (sidebar layouts only). |
| `404` | Replaces the not found message. |
| `shop_top`, `store_top` | Above the WooCommerce shop grid, or the EDD download grid. |

Every part above also works on pages that use Page Blocks Builder's templates, wherever the theme's markup prints. Posts and products on a PBB template get the `has-page-blocks-template` body class.

## Focus trap for panels

`assets/js/focus.js` is registered (not enqueued) as the `stackless-theme-focus` script, deferred in the footer. List the handle as a dependency and `window.StacklessFocus` is there when your script runs. No jQuery. The theme's FluentCart cart drawer and mobile filters use it.

| Call | What it does |
|---|---|
| `StacklessFocus.trap(container, { onEscape, returnFocus })` | Moves focus to `container` (it gets `tabindex="-1"` if it has none), keeps Tab and Shift+Tab inside it, and calls `onEscape(event)` on Escape. `returnFocus` is where focus goes back on release; it defaults to the element that had focus before. Returns `release`. Trapping a container that is already trapped returns its existing `release` and moves nothing. |
| `release(fallback)` | Ends the trap and returns focus to `returnFocus`, or to `fallback` when that element is gone or hidden. If the visitor already moved focus elsewhere on the page, focus stays put. Calling it twice does nothing. |
| `StacklessFocus.focusables(root)` | The visible, enabled elements in `root` that Tab reaches, in document order. |

Traps stack: only the newest one handles Tab and Escape, and releasing it hands them back to the one under it, so a popup can open over the cart drawer.

```php
wp_enqueue_script('my-popup', $url, ['stackless-theme-focus'], $version, ['in_footer' => true, 'strategy' => 'defer']);
```

```js
var release = window.StacklessFocus.trap(popup, { onEscape: closePopup, returnFocus: openButton });
function closePopup() {
	popup.hidden = true;
	release();
}
```

## Dark colors

`base.css` holds a second set of the color properties under `html.stackless-dark`. The theme never adds that class and has no toggle: a plugin or script sets it on `<html>` (before first paint, to avoid a flash) and removes it for light. It also sets `color-scheme: dark`, so form controls and scrollbars follow.

The theme declares `add_theme_support('stackless-dark-mode')`. Check `current_theme_supports('stackless-dark-mode')` before offering a dark mode: a theme version without it has no dark tokens, and the class would do nothing.

| Property | Light | Dark |
|---|---|---|
| `--st-bg`, `--st-surface`, `--st-surface-muted` | `#fafafa`, `#ffffff`, `#f6f6f6` | `#121212`, `#1b1b1b`, `#232323` |
| `--st-text`, `--st-heading`, `--st-muted` | `#262626`, `#171717`, `#666666` | `#e0e0e0`, `#f5f5f5`, `#a3a3a3` |
| `--st-border`, `--st-border-strong` | `#e5e5e5`, `#d4d4d4` | `#2e2e2e`, `#3d3d3d` |
| `--st-gray-50` to `--st-gray-900` | `#fafafa` to `#171717` | turned over: `#1f1f1f` to `#f5f5f5` |
| `--st-primary` | `#066fd1` or the Customizer color | unchanged: buttons keep the primary fill and white label |
| `--st-link`, `--st-link-hover` | unset (rules fall back to `--st-primary`, `--st-primary-hover`) | primary mixed with white, 45/55 and 30/70 |
| `--st-primary-light`, `--st-primary-text` | tint and shade of primary | primary at 22% over the dark surface, primary mixed 40/60 with white |
| `--st-{success,warning,danger,info}-light` / `-text` | pale tints, dark text | dark tints, light text (7.5:1 or more) |
| `--st-shadow-sm`, `--st-shadow`, `--st-shadow-lg`, `--st-shadow-input`, `--st-ring` | soft gray | black shadows; the ring is drawn from `--st-link` |
| `--st-code-bg`, `--st-code-text` | unset (`--st-gray-900`, `--st-gray-100`) | `#0b0b0b`, `#e0e0e0` |
| `--st-inverse-text` | unset (`#fff`) | `#121212`, the label on fills drawn in the top grays (Buy now, the store notice) |

Contrast with the default colors: text on the background 14.2:1, text on surfaces 13.1:1, muted text on the background 7.4:1, links on the background 9.6:1. Because `--st-link` keeps at least 55% white, links stay above 4.5:1 on every dark surface whatever the primary is (pure black as primary still gives 4.6:1 on the lightest dark surface).

The Customizer colors print on `:root`. `html.stackless-dark` is more specific, so in dark the neutral colors (background, surfaces, text, headings) are always the dark set; a custom primary carries into dark as the button fill and, mixed with white, as the link color. The block palette colors that name a role (`body`, `muted`, `line`, `surface`, `primary-light`) follow the dark set; `primary`, `dark`, `white` and the status colors are explicit picks and stay as chosen. To change a dark color, filter `stackless_theme_design_css` and append an `html.stackless-dark{...}` rule.

New CSS should take text colors from `var(--st-link, var(--st-primary))` rather than `--st-primary`, and labels on gray-900 fills from `var(--st-inverse-text, #fff)`.
