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
| `store_toolbar` | Promo line, search, sorting, category chips (WooCommerce, EDD) | yes | |

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
| Store | `view_product`, `choose_option`, `choose_options`, `related_products_title`, `product_category_label`, `product_tags_label`, `breadcrumb_label`, `home` |
| Footer | `theme_credit` (keep the `%s`; it becomes the theme link) |

## Options, presets and design

A site layout ships its defaults through filters. A site owner's saved Customizer values always win over them.

| Filter | What it controls |
|---|---|
| `stackless_theme_option_defaults` | Defaults for every Stackless option: layouts, header style, archive grid, store toggles, mobile menu, and the rest. |
| `theme_mod_{name}` (WordPress core) | Any saved setting, including colors (`stackless_color_*`), widths (`stackless_width_*`), fonts (`stackless_font_body`, `stackless_font_heading`) and footer text. |
| `stackless_theme_option` / `stackless_theme_option_{key}` | The resolved option value at render time. |
| `stackless_theme_cpt_option_defaults` / `stackless_theme_cpt_option` | Custom post type options. |
| `stackless_theme_layout_choices` | The per-post Layout panel selects. |
| `stackless_theme_design_colors`, `stackless_theme_design_widths`, `stackless_theme_font_stacks`, `stackless_theme_bundled_fonts` | The design registries (add a color, a width or a font). |
| `stackless_theme_design_vars` / `stackless_theme_design_css` | The CSS custom properties and the printed design CSS. |
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
