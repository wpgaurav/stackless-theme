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
| `header_nav_right` | Header right menu (split layout) | yes | |
| `header_rail` | The sidebar layout's rail (`#site-rail`) | yes | `header_rail_start`, `header_rail_end` |
| `header_search_field` | Search field in the rail | yes | |
| `header_rail_list` | Sidebar list menu (`$context['title']`) | yes | |
| `header_rail_toggle` | Rail collapse button | | |
| `header_account_menu` | Avatar and account menu, or Sign in (`$context['url']`) | yes | |
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

`apply_filters('stackless_theme_header_layout', array $layout)` decides where each header part sits. `$layout` has the top header's three groups, `start`, `center` and `end`, and the sidebar layout's five, `rail_top`, `rail_nav`, `rail_list`, `rail_bottom` and `bar`. Each is a list of part slugs:

```php
// The default: the same markup as the fixed header before 1.8.0, byte for byte.
[
    'start'  => ['branding', 'navigation'],
    'center' => [],
    'end'    => ['currency', 'search', 'account', 'cart', 'menu_toggle'],
    'rail_top' => [], 'rail_nav' => [], 'rail_list' => [], 'rail_bottom' => [], 'bar' => [],
]
```

The value passed in is the preset that Header and menu > Header layout (`stackless_header_layout`) stands for, from `stackless_theme_header_layout_preset($preset)`. A filter still wins over it. A filter that returns only `start`, `center` and `end` gets empty rail groups, so filters written before the sidebar layout keep working.

| Preset | `start` | `center` | `end` | Rail groups and `bar` |
|---|---|---|---|---|
| `default` | branding, navigation | | currency, search, account, cart, menu_toggle | empty |
| `centered` | navigation | branding | currency, search, account, cart, menu_toggle | empty |
| `split` | navigation | branding | navigation_right, currency, search, account, cart, menu_toggle | empty |
| `sidebar` | | | | `rail_top`: branding, rail_toggle. `rail_nav`: search_field, navigation. `rail_list`: rail_list. `rail_bottom`: account_menu. `bar`: currency, search, cart |

| Slug | Part | Prints when |
|---|---|---|
| `branding` | `header_brand` | Always |
| `navigation` | `header_nav` (`#site-navigation`, the menu the toggle opens) | Always |
| `currency` | The header currency switcher | Stackless for WooCommerce's currency selector is on |
| `search` | `header_search_button` | Header and menu > Search button is on |
| `account` | `header_account` | Store > Account button is on and there is an account URL |
| `cart` | `header_cart` | Store > Cart button is on and a store supplies a cart link |
| `menu_toggle` | `header_menu_toggle` | Always (shown below 1080px) |
| `navigation_right` | `header_nav_right` (`#site-navigation-right`) | A menu is assigned to Header right menu (`header-right`) |
| `search_field` | `header_search_field` | Header and menu > Search field in the sidebar is on |
| `rail_list` | `header_rail_list` | A menu is assigned to Sidebar list (`sidebar-list`) |
| `rail_toggle` | `header_rail_toggle` | The header is the sidebar layout |
| `account_menu` | `header_account_menu` | Always: avatar and menu when signed in, Sign in otherwise |

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

`stackless_theme_header_layout()` returns the cleaned layout, with all eight groups. `stackless_theme_header_part_slugs()` returns the known slugs and `stackless_theme_header_layout_slots()` the groups. Below 1080px the menu opens under the header wherever `navigation` sits.

**Split.** When `navigation_right` is placed and the Header right menu has a menu, the header gets `site-header--split`. That menu prints twice:

- as `#site-navigation-right` beside the logo, from 1080px
- inside `#site-navigation` as `ul.site-nav__merged` below 1080px, so the Menu button opens both menus

Its `wp_nav_menu()` arguments pass through `stackless_theme_header_right_menu_args`.

**Sidebar.** The header is the rail and top bar when a rail group (`rail_top`, `rail_nav`, `rail_list`, `rail_bottom`) has parts. All of these must also hold:

- the site header shows
- no Page Blocks header region replaces it
- the page did not tick "Top header instead of the sidebar"

`stackless_theme_header_has_rail()` reports it. Then:

- **Markup.** `<header class="site-header site-header--sidebar">` holds the top bar (`.site-header__inner.site-bar`, with a Menu button, a compact brand for phones, and the `bar` parts in `.site-header__actions`). It also holds `#site-rail` (the `header_rail` part), with `.site-rail__top`, `.site-rail__body` (`rail_nav`, then `rail_list`, scrolling on its own) and `.site-rail__bottom`.
- **Assets.** `assets/css/header-sidebar.css` (handle `stackless-theme-header-sidebar`) and the `stackless-theme-focus` script load only in this layout. A small inline script after `<body>` restores the visitor's collapsed state before paint.
- **Body classes.** `has-rail`, `rail-collapse-icons`, `rail-collapse-hidden` or `rail-collapse-off`, and `rail-collapsed` (added by script). Also `has-bar-autohide` when hide-on-scroll is on.
- **Desktop.** From 1080px the rail is fixed at `--st-rail` (280px, or the width setting) and the body is padded by `--st-rail-current`, so full-width blocks span the content area only. Collapsed to icons it is `--st-rail-icons` (72px). Labels (`.menu-item-label`) are hidden and show as a tooltip on hover and keyboard focus. Items without an icon show their first letter.
- **Phones and tablets.** Below 1080px the rail is a drawer. The bar's Menu button opens it (`html.rail-open`) and `StacklessFocus` traps focus. Escape, the close button or a click outside closes it, and focus goes back to Menu.
- **State.** Both toggles (`[data-st-rail-toggle]`) carry `aria-controls="site-rail"` and `aria-expanded`. The visitor's choice is kept in `localStorage` under `stacklessRail`. Without JavaScript the rail stays expanded, and on phones it sits in the page.
- **Header over the first section** is off in this layout, and Keep the header in view does not apply. Hide-on-scroll moves only the top bar.
- **Menu arguments.** The primary menu gets `'stackless_rail' => true` in its `wp_nav_menu()` arguments. The list and account menus pass through `stackless_theme_rail_list_menu_args` and `stackless_theme_account_menu_args`. Without an Account menu, signed-in visitors get My account (when there is an account URL) and Log out.

#### Header options

All are theme mods in Customize > Stackless Theme > Header and menu. Each has a default in `stackless_theme_core_option_defaults()`, so a site layout can ship its own through `stackless_theme_option_defaults`. Each has a sanitizer, so settings imports and `stackless_theme_sanitize_mods()` accept them. At their defaults the front end prints the same HTML as before these options existed.

| Setting | Values (default first) | What it does |
|---|---|---|
| `stackless_header_layout` | `default`, `centered`, `split`, `sidebar` | The preset passed to `stackless_theme_header_layout`. |
| `stackless_header_transparent` | `off`, `front`, `hero`, `all` | Lays the header over the first section. `front`: the front page. `hero`: views that open with a full-width Cover block (title hidden), a post's full-width featured image, or a Page Blocks hero region. `all`: those, plus any other first block set to full width. A view that opens with a title stays solid. |
| `stackless_header_transparent_text` | `light`, `dark` | Text over the first section while the header is clear: body class `has-header-on-dark` or `has-header-on-light`. The preview updates without reloading. |
| `stackless_header_sticky` | `true`, `false` | Unchanged. Not used by the sidebar layout. |
| `stackless_header_hide_on_scroll` | `false`, `true` | Body class `has-header-autohide` (with sticky on), or `has-bar-autohide` in the sidebar layout. theme.js sets `html.is-header-hidden` on the way down and clears it on the way up, near the top, and when focus enters the header. CSS never hides the header while `nav-open`, `search-open`, `rail-open` or focus is inside it. It hides and shows without sliding under reduced motion. |
| `stackless_header_rail_width` | `280`, 240 to 320 | Rail width in pixels (`--st-rail`). |
| `stackless_header_rail_collapse` | `icons`, `hidden`, `off` | What the rail's collapse button does from 1080px. |
| `stackless_header_rail_default` | `expanded`, `collapsed` | The rail on a first visit, before the visitor chooses. |
| `stackless_header_rail_search` | `true`, `false` | The `search_field` part. |
| `stackless_header_rail_list_title` | `''`, any text | Heading over the Sidebar list menu, and its label. |

How the clear header is decided:

- **A page's own choice wins.** "Header over the first section" in the page's Layout panel (`_stackless_transparent_header`) turns it on, with its automatic text color.
- **Without a page choice**, `stackless_theme_header_transparent_auto()` applies the site option.
- **Where the view starts.** `stackless_theme_opens_with_hero($mode)` decides whether the view opens with a full-width section. Filter it with `stackless_theme_opens_with_hero` (`bool $opens, string $mode`).
- **Last word.** `stackless_theme_layout_flag` (flag `transparent_header`) still has it.

Theme support: the theme declares `add_theme_support('stackless-header-options')`. Check `current_theme_supports('stackless-header-options')` before adding header layout, clear header or hide-on-scroll options of your own (Stackless Theme Pro defers to these).

Menu locations: `primary`, `header-right` (split layout), `sidebar-list` (sidebar layout's list), `account` (sidebar layout, signed-in visitors) and `footer`.

The page Layout panel has "Top header instead of the sidebar" (meta `_stackless_top_header`, layout flag `top_header`). It shows only while Header layout is Sidebar.

#### Menu item icons

Appearance > Menus gives every menu item an Icon field. Its choices are the theme's bundled Tabler icons in `assets/icons/`, filterable with `stackless_theme_menu_icons` (a list of icon names). The icon is saved as `_stackless_menu_icon` on the item and prints before the label in any menu, through `nav_menu_item_title`:

- the label is wrapped in `<span class="menu-item-label">`
- the `<li>` gets `menu-item-has-icon`

The Customizer's menu editor does not show the field. Use Appearance > Menus. In the sidebar layout's rail every label is wrapped, and a top-level item without an icon gets a `.menu-item-initial` letter. A list item with the CSS class `has-dot` shows a small dot.

#### Header height token

`--st-header-height` is a public CSS custom property on `:root`: the site header's height, for anything that has to clear it, such as a slider under a clear header, a sticky offset or `scroll-margin-top`.

`--st-header-offset` (default `0px`) moves a header that sits over the first section down from the top of the window, for a bar printed above the header such as an announcement bar. Set it on `:root` to the bar's visible height and back to `0px` once the bar scrolls away or closes. The admin bar is added on top of it, so leave it out.

- **CSS value.** `calc(4rem + 1px)` for the top header, and `calc(3.5rem + 1px)` for the sidebar layout's top bar.
- **When it changes.** theme.js watches the header with a `ResizeObserver`. When the real height differs from the CSS value (a menu wrapping to two rows, an announcement bar printed into the header), it writes the measured height inline on `<html>`. It removes it again once they agree.
- **No header.** Without JavaScript, or on a page with no site header, the CSS value applies.

```css
.my-slider { padding-top: var(--st-header-height, 4rem); }
```

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
| `block_carousel` | `stackless/carousel` |
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
| `block_slider`, `block_slide` | `stackless/slider` and its slides |
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
| `stackless_theme_blocks_slider_heights` | The Slider's height presets (`small`, `medium`, `large` => CSS length). |
| `stackless_theme_blocks_carousel_gaps` | The Carousel's gap presets (`none`, `small`, `medium`, `large` => CSS length). |
| `stackless_theme_blocks_carousel_lists` | The class names of the lists a Carousel scrolls when it holds a single block. |

#### Slider and Carousel

Both blocks save only their attributes and inner blocks; PHP prints the markup. The row is a CSS scroll-snap scroller, so it scrolls sideways without JavaScript. One shared script (`assets/js/blocks/scroller.js`, handle `stackless-block-scroller`) adds the arrows, dots or progress bar, the arrow keys, Home and End, and autoplay. The shared stylesheet `assets/css/blocks/scroller.css` (handle `stackless-block-scroller`) loads with either block's own stylesheet, only on pages that use one. Block definitions can share files the same way: `'script' => 'scroller'` names a shared script and `'style_deps' => ['scroller']` a shared stylesheet.

`stackless/slider` (supports anchor, class, align wide and full, top and bottom margin) holds only `stackless/slide` blocks.

| Attribute | Type | Default | Meaning |
|---|---|---|---|
| `label` | string | `''` | The region's accessible name. Empty reads "Slideshow". |
| `height` | string | `large` | `small` (24rem), `medium` (32rem), `large` (40rem) or `viewport`. Slides grow when their content needs more room. |
| `viewportHeight` | number | `100` | With `viewport`: the share of the screen height, 30 to 100. |
| `autoplay` | boolean | `false` | Moves on by itself and adds a pause button. Pauses on hover, on focus, off screen and in a hidden tab; never runs with reduced motion. |
| `interval` | number | `6` | Seconds per slide, 2 to 30. |
| `arrows`, `dots` | boolean | `true` | The previous and next buttons, and the dots. |
| `loop` | boolean | `true` | The arrows wrap from the last slide to the first. |
| `align` | string | `full` | Block alignment. |

`stackless/slide` (supports anchor, class, text and background color with gradients, padding; parent `stackless/slider`) holds any blocks, laid over its photo.

| Attribute | Type | Default | Meaning |
|---|---|---|---|
| `mediaId` | number | `0` | The photo's attachment ID. Printed with `wp_get_attachment_image()`, its alt text and `srcset`. |
| `mediaUrl` | string | `''` | The photo's URL, used when there is no attachment ID and by the editor preview. |
| `focalPoint` | object | `{"x":0.5,"y":0.5}` | The part of the photo kept in view (`object-position`). |
| `dimRatio` | number | `40` | The black overlay's opacity, 0 to 100. |
| `contentPosition` | string | `center center` | Vertical then horizontal: `top`, `center` or `bottom`, and `left`, `center` or `right`. |

A slide with a photo uses white text (it redefines `--st-heading`, `--st-text`, `--st-muted` and `--st-link` inside the slide); one without uses the page's surface and text colors. The first slide's photo is `loading="eager"` (with `fetchpriority="high"` in the first Slider on the page); the rest are lazy. A Slider that opens a page whose header sits over the first section leaves room for the header (`--st-header-height`, 4rem when unset) and adds `has-header-on-dark` through the `stackless_theme_header_on_dark` filter.

`stackless/carousel` (supports anchor, class, align wide and full, top and bottom margin) holds any blocks. Each becomes an item wrapped in `<div class="stb-carousel-item" role="group" aria-roledescription="slide" aria-label="2 of 5">`.

| Attribute | Type | Default | Meaning |
|---|---|---|---|
| `label` | string | `''` | The region's accessible name. Empty reads "Carousel". |
| `perView`, `perViewTablet`, `perViewPhone` | number | `3`, `2`, `1` | Items in view on desktop, under 1024px and under 600px, 1 to 8. A fraction (1.2) lets the next item peek in. |
| `gap` | string | `medium` | `none`, `small`, `medium` or `large`. |
| `arrows` | boolean | `true` | Previous and next buttons; they move a page at a time. |
| `pagination` | string | `dots` | `dots`, `progress` or `none`. |
| `snap` | boolean | `true` | Scrolling settles with an item at the edge. |
| `loop` | boolean | `false` | The arrows wrap from the end to the start. |
| `autoplay`, `interval` | boolean, number | `false`, `6` | As on the Slider. |
| `listClass` | string | `''` | With a single inner block: the class of the list to scroll, when it is not one of the known lists. |

**A single Query Loop or product block.** When the Carousel holds exactly one block, it looks inside it for a known list and makes that list the row: core Query Loop (`wp-block-post-template`), WooCommerce Product Collection (`wc-block-product-template`), the older WooCommerce product grids (`wc-block-grid__products`, `ul.products`), Easy Digital Downloads (`edd-blocks__downloads`, `edd_downloads_list`), FluentCart's Products block (`fct-products-container`), the Stackless Query Loop (`loop-cards`, `loop-teasers`, `loop-blocks`), Columns and Gallery. The list gets `stb-scroller-track` and `data-stb-track`, its items keep their own markup, and the block's title, pagination and filters stay outside the row. Set `listClass` (Advanced panel) for any other list, or add a class with `stackless_theme_blocks_carousel_lists`. A block whose script redraws its list (a filtered or paged FluentCart shop) drops the row class when it redraws, so use the Carousel around a fixed selection.

Markup the script reads: the root `[data-stb-scroller]` with `data-loop`, `data-autoplay` (seconds, `0` for off) and `data-label-*` interface text; the row `[data-stb-track]`; `.stb-scroller-prev`, `.stb-scroller-next`, `.stb-scroller-dots`, `.stb-scroller-progress-bar`, `.stb-scroller-pause` and the live `.stb-scroller-status`. The root gets `is-ready` once the script runs and `is-playing` while autoplay runs.

### Page wrapper

The `main` class filter is `stackless_theme_main_class`. Its `$context['template']` is `singular`, `index`, `404`, `full-width`, `canvas`, `single-download`, `single-fluent-products`, `archive-download` or `woocommerce`.

## Interface text

Filter `stackless_theme_text_{key}` (or `stackless_theme_text` with the key as the third argument) to change a label. Return plain text; the theme escapes it.

| Area | Keys |
|---|---|
| Header and menus | `menu`, `search`, `my_account`, `sign_in`, `log_out`, `primary_menu_label`, `header_right_menu_label`, `rail_list_label`, `sidebar`, `footer_menu_label` |
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
