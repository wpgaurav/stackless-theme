=== Stackless ===
Contributors: gauravtiwari
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.9.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A minimal classic theme for blogs, sites and stores. A store plugin is optional: WooCommerce, Easy Digital Downloads and FluentCart are styled when active.

== Description ==

Stackless is a quiet, fast classic theme. It styles every semantic HTML element and every form control, and works on its own for a blog or a business site with no plugin. When a store plugin is active it also styles WooCommerce's classic templates and its Cart and Checkout blocks, Easy Digital Downloads' downloads, checkout, receipts and account pages, and every Stackless for WooCommerce and Stackless for EDD module (wishlist, sold count, quotes, licenses, subscriptions, saved carts, order bumps and more).

* Classic PHP templates with full Block Editor support: theme.json palette, type scale, spacing, wide and full alignment, editor styles.
* System font stack by default and no jQuery. Ten open-source fonts are included and load only when you pick them.
* Header with logo, two-level primary menu and full-screen search; with a store active, an account link and live cart count.
* With WooCommerce or Easy Digital Downloads, the search shows live product results, popular categories and best sellers.
* Single product pages with a sticky summary column; editorial single posts with author box, related posts, optional sidebar layouts and per-post layout choices.
* Footer widget area and footer menu. Optional "Shop" widget area above the product grid.
* One Customizer panel for colors, typography, widths, the header and mobile menu, blog and archives, single posts, custom post types, store elements, the footer line and header and footer code.
* Full width and Canvas templates, and per-page options to hide the title, header or footer or lay the header over the first section.
* Page Blocks Builder regions (header, hero, before and after content, sidebar, footer, 404, shop and store) and an edge-to-edge builder template.
* Settings export and import with backups.

== Installation ==

1. Appearance → Themes → Add New → Upload Theme, choose stackless-theme.zip, then Activate.
2. Appearance → Menus: assign menus to "Primary menu" and "Footer menu". Without a primary menu the header lists a few top-level pages, and the shop when a store plugin is active.
3. Appearance → Customize → Site Identity: add a logo.

== Copyright ==

Stackless theme, Copyright 2026 Gaurav Tiwari. Distributed under the terms of the GNU GPL v2 or later.

Design tokens follow Tabler (https://tabler.io), Copyright (c) 2018-2026 codecalm.net, MIT License.
Icons: Tabler Icons 3.47.0 (https://tabler.io/icons), Copyright (c) 2020-2026 Paweł Kuna, MIT License.

Fonts in assets/fonts, each under the SIL Open Font License 1.1 (OFL.txt beside each font), subset to Latin and converted to WOFF2:
Inter, Copyright 2016 The Inter Project Authors (https://github.com/rsms/inter).
Geist and Geist Serif, Copyright 2024-2026 The Geist Project Authors (https://github.com/vercel/geist-font).
Allo, Copyright 2026 Josh Puckett (https://joshpuckett.me), portions Copyright 2016 The Inter Project Authors.
Intern, Copyright 2016 The Inter Project Authors, Copyright 2026 Luke.
Newsreader, Copyright 2020 The Newsreader Project Authors (https://github.com/productiontype/Newsreader).
Anton, Copyright 2020 The Anton Project Authors (https://github.com/googlefonts/AntonFont).
Bebas Neue, Copyright 2019 The Bebas Neue Project Authors (https://github.com/dharmatype/Bebas-Neue).
Oswald, Copyright 2016 The Oswald Project Authors (https://github.com/googlefonts/OswaldFont).
Archivo Black, Copyright 2017 The Archivo Black Project Authors (https://github.com/Omnibus-Type/ArchivoBlack).

== Changelog ==

= 1.9.3 =
* New: a Performance section under Customize > Stackless Theme. Six options, all off until ticked: leave out the emoji script, leave out extra head links (RSD, shortlink, REST link, version tag), stop other sites showing your posts as embed cards, a shorter body class list (classes the theme and store plugins use stay), Dashicons only for signed-in users, and leave out jQuery Migrate. None of them touch wp-admin.
* Changed: the layout and Easy Digital Downloads stylesheets load in parts, so each page gets only the styles for what it shows. Pages load 12 to 59 KB less CSS, most on EDD pages.
* Changed: Appearance > Stackless tools has a new look in the style of the WordPress 7 admin: cards, tabs between the Stackless screens, a backups list and a summary of the Performance options.
* Fixed: a Stackless block such as a table of contents or a plugin card counted as store content, so WooCommerce's styles (about 60 KB) loaded on ordinary posts.

= 1.9.2 =
* New: Site Identity has "Show the site title next to the logo", for a logo that is a mark or icon rather than the full name.
* Fixed: the Logo width setting was ignored, and an SVG logo saved with only a viewBox disappeared from the header.
* Fixed: an SVG logo drawn with currentColor showed black and vanished on a dark header. It now prints inline and takes the header's text color, in dark mode too.
* Fixed: a menu item given the CSS class "button" showed its text in the same color as the button. It now shows as a call-to-action button, in the mobile menu too.
* Fixed: an author photo that is not square showed as an oval in the byline, the author box and comments. Avatars are now cropped round.

= 1.9.1 =
* Changed: a Step's title is an h3 instead of an h4, so steps under a section heading no longer skip a heading level. It looks the same.

= 1.9.0 =
* New: a Linked tile style for the Cover block. The cover's link opens from anywhere on it, for photo category tiles and similar cards, and the photo zooms a little on hover (not with reduced motion).
* Changed: the Query Loop styles are named Cards, Magazine and List.

= 1.8.1 =
* Fixed: a long word in a large heading wrapped onto the next line only after widening the page on phones.
* Fixed: Carousel arrows could sit off the page edge beside a post sidebar or on narrower screens; they now sit outside the items only from 1280px and never beside a sidebar.
* Fixed: a Carousel whose items all fit showed dots, a progress bar and a pause button that did nothing.
* Fixed: Play on an autoplaying Slider or Carousel waited until the pointer left the button.
* Fixed: a Carousel list with its own anchor lost the link between it and its arrows for screen readers.
* Fixed: in the icons-only sidebar header, screen readers could not read the menu labels.
* Fixed: the Header right menu was printed twice with the same ids in the split layout.
* Fixed: a Slider opening a page did not count as a hero for the see-through header unless its alignment was saved.
* Fixed: Side Promotion panels did not line up with the text column and could widen the page.

= 1.8.0 =
* New: Stackless blocks. Callout, Content Upgrade, FAQ, Steps, Key Takeaway, Pull Quote, Quote, Related Content, Share Notice, Spoiler, Section, Query Loop, Side Promotion, Arrow and Table of Contents are in the editor under Stackless. Each block loads its own small stylesheet only on pages that use it, and only the FAQ, Spoiler, Table of Contents, Slider and Carousel have a script.
* New: card blocks. Plugin Card, Theme Card, GitHub Card, npm Package Card, Book Card, YouTube Card and Podcast Card fill themselves in from a slug, a repository, a package name, an ISBN, a video link or a feed. Each card saves its data in the post, so a page view waits on no outside service, and none of them loads a script. Plugin and theme images are copied into the media library. The YouTube card links to the video and loads no player.
* New: the Plugin, Theme, GitHub and npm cards keep their ratings, installs, stars and versions up to date. The numbers are fetched in the background at most once a day and are never waited for on a page view.
* New: style variations for core blocks: paragraphs, headings, lists, buttons, images, tables, groups, covers, videos, separators and the query loop. A variation's stylesheet loads only on pages that use it.
* Removed: the one-off settings importer for sites moving from another theme. The theme's own settings export, import and backups are unchanged.
* New: icons chosen in a block are drawn as Tabler icons, and each block can be hidden, replaced or filtered through the theme's hooks.
* New: colors, fonts and heading letter case in Customize > Stackless Theme now update the preview as you change them, without reloading it. A bundled font still loads only once it is picked.
* New: plugins can rearrange the header. A filter places the logo, menu, search, account, cart and menu button at the start, center or end of the bar, so a centered logo or a centered menu needs no template copy. The default header is unchanged.
* New: pages and custom post types can sit beside a sidebar and pick a header style in the editor's Layout panel, like posts. Without a choice they look as before.
* New: add-ons can add their own sections to the Stackless Theme panel in the Customizer, in a set order among the theme's.
* New: the Stackless Theme panel in the Customizer mentions Stackless Theme Pro in one line, which goes away once Pro is active.
* Fixed: a post with no tags showed an empty tag after it.
* Fixed: on store pages where a plugin loads its own .container styles (FluentCart does on some pages), the header and footer could be narrower than the page at some screen widths.
* New: a shared focus trap for drawers, dialogs and popups (the stackless-theme-focus script, window.StacklessFocus). The FluentCart cart drawer and mobile filters now use it and behave as before.
* New: a dark set of the theme's colors, used when the page's html element has the stackless-dark class. The theme never adds the class itself, and declares theme support for stackless-dark-mode so a plugin can tell the colors are there. Links stay at 4.5:1 or more in dark whatever the primary color, and the store layers' leftover light colors (overlays, the zoom button, loading spinners, the Buy now button and the store notice) now follow the theme's colors.
* New: a Sale badge on FluentCart product cards and product pages when a product's compare-at price is above its price, drawn like WooCommerce's. EDD has no sale price, so its cards and download pages show the badge only when the stackless_theme_product_on_sale filter says so.
* Changed: the Callout block's Tip, Info, Warning, Success and Error presets take their colors from the theme instead of fixed light colors in the markup, so they follow the site's colors and stay readable in dark. They look the same as before on a light page.
* Changed: a store plugin is optional. The theme was checked with no store active (front page, blog, posts, pages, search, 404 and the Customizer), and the description and Customizer panel no longer assume a store. A post with no author no longer shows an empty byline.
* New: Header layout in Customize > Stackless Theme > Header and menu. Centered puts the logo in the middle with the menu on the left. Split puts the primary menu on the left of a centered logo and a new Header right menu on its right. On phones and tablets both menus open from the Menu button. Default looks as before.
* New: a Sidebar header layout for app-style sites. A fixed side rail holds the logo, a search field, the primary menu, a Sidebar list menu and an account menu, with a slim top bar for search and cart. Visitors can collapse it to icons or hide it, and the choice is remembered in their browser. Below 1080px the rail is a drawer. A page can switch back to the top header from its Layout panel. The stylesheet loads only with this layout.
* New: menu items can have an icon, picked from the theme's icons in Appearance > Menus. It shows before the label in any menu.
* New: Header over the first section, for the whole site. Choose the front page, pages that open with a Cover block or hero, or every page that opens with a full-width section, and light or dark header text. Pages that open with a title keep the solid header, and a page's own Layout choice still works.
* New: Hide the header while scrolling down. It comes back on any scroll up, and never hides while the menu or search is open or keyboard focus is in it.
* New: the --st-header-height CSS property holds the header's real height, for blocks that have to clear it.
* New: Slider block. Full-width photo slides with a heading, text and buttons laid over each, a focal point and an overlay per slide, and small, medium, large or screen-height sizes. Arrows, dots and looping are options, and autoplay (off by default) adds a pause button, stops while the pointer or keyboard is on the slider, and never runs for visitors who ask for reduced motion. Under a header laid over the first section, the slides leave room for the header and the header switches to light text.
* New: Carousel block. A sideways row of any blocks (cards, columns, images, product and post blocks), with items in view set for desktop, tablet and phone, a gap, arrows, dots or a progress bar, and snapping. With a single Query Loop, Product Collection, product grid, Columns or Gallery inside, it scrolls that block's own list.
* New: the Slider and Carousel move with CSS scroll snap and share one small script for the arrows, dots, arrow keys and autoplay, loaded only on pages that use them. Without the script they are plain horizontal scrollers. Both are labelled for screen readers ("2 of 5"), follow the site's colors and dark colors, keep their space while loading, and lazy-load every photo but the first slide's.
* Fixed: with the header over the first section, a dropdown menu could show white text on a white panel, and menu hover backgrounds were too light to see.
* Fixed: a Cover block at the top of a page with the header over it started 32px down, leaving a strip of page background under the header.
* Changed: the "Built with Stackless" footer link points to wpstackless.com.
* New: the --st-header-offset CSS property moves the header over the first section down, so a bar printed above the header (Pro's announcement bar, for one) stays visible.
* Fixed: a full-width Group with its own content or wide size kept the theme's reading width instead.
* Fixed: on phones, pages jumped about 78px on first load while the menu switched from its no-JavaScript state. The page is now marked as scripted before it draws.
* New: more Typography settings: heading sizes (four scales), heading weight, body text size, line height and letter spacing, and heading line height and letter spacing. Each previews live and stays at the theme default until you set it.

= 1.7.0 =
* New: one Stackless Theme panel in the Customizer, with sections for colors, typography, layout, header and menu, blog and archives, single posts, custom post types, store, footer and custom code. Store options appear only for the store plugin that is active. Saved settings carry over.
* New: single post layouts. A post can sit beside a sidebar that stays in view, open with a centered or left-aligned title, the featured image above the title, or a full-width featured image, and show breadcrumbs from Yoast SEO, Rank Math, SEOPress, All in One SEO, The SEO Framework, Slim SEO or Breadcrumb NavXT. The category and byline can sit above the title, below it or after the post, and each post can pick its own layout in the editor's Layout panel.
* New: blog and archive options. List or two- and three-column grids, switches for images, dates, excerpts and Read more, excerpt length, and options for each custom post type's single pages.
* New: ten fonts are included: Inter, Geist, Intern, Allo, Newsreader and Geist Serif for text, and Anton, Bebas Neue, Oswald and Archivo Black for headings. Only the fonts you pick are loaded, and headings can be set in capitals.
* New: the mobile menu can open as a full-screen drawer, the header can scroll away with the page, and the header search, cart and account buttons, product breadcrumbs and category lines can be switched off.
* New: products in search results show their image and price, and a search with no results offers the shop and its categories.
* New: every part of the theme can be hidden, replaced or added to through hooks, option defaults can be filtered, and template parts can be served from a plugin.
* Improved: Page Blocks Builder. Products and posts on a Page Blocks template get that template edge to edge, and a new region sits at the top of the post sidebar.
* Improved: FluentCart stores. On phones the product name and price come before the gallery, the shop grid fills the row on tablets, list view rows are compact, and the price filter's fields look and work like fields.
* Fixed: buttons with a filled background lost their label on hover.
* Fixed: keyboard use of the mobile menu, FluentCart's cart drawer, mobile filters and checkout order summary. Focus moves into an open panel, stays there, and returns when it closes.

= 1.6.1 =
* New: FluentCart product pages use the same layout as WooCommerce products and EDD downloads: a breadcrumb, the gallery beside a summary that stays in view, the price, options and one buy row with quantity, Buy Now and Add to Cart, the category, then the description and related products.

= 1.6.0 =
* New: FluentCart support. The shop, product, cart, checkout, receipt, account and cart drawer use the theme's colors, with 4px corners on buttons, fields and standard product cards, and flat primary buttons replace the dark gradient ones. FluentCart's optional card layouts keep their own shapes. Colors chosen in FluentCart's settings still win.
* New: FluentCart store pages use the wide container, so titles line up with the store, products sit in two columns and reviews share their edge.
* New: header cart and account links for FluentCart stores.
* Fixed: license notices in the licensed download are tinted boxes without a thick left border.

= 1.5.0 =
* New: design settings under Appearance > Customize > Stackless: Design. Primary, text, heading, background and card colors, the reading and wide widths, body and heading fonts (system stacks or your own), logo width and an optional fade between pages. Only changed values print, as a few custom properties, and the editor and theme.json palette follow them.
* New: a footer text field with {year} and {site}, and fields for code in the head and before the closing body tag (administrators who may post unfiltered HTML only), under Stackless: Footer and code.
* New: Full width (no title) and Canvas (no header or footer) templates for pages, posts and other public post types (not WooCommerce products or EDD downloads, which keep their store layouts). Full-width blocks, covers, background groups and raw HTML sections run edge to edge; text and other blocks keep the reading width with space around them.
* New: a Layout panel in the editor to hide the title, the site header or the site footer, or to lay the header over the first section until the page scrolls. Over a dark cover the header switches to light text (filter stackless_theme_header_on_dark for other heroes). Products and other classic-editor screens get the same options in a side box.
* New: Page Blocks Builder support. Blocks placed in the header or footer regions replace the theme's own (filter stackless_theme_region_mode to keep both); the hero, before content, after content, sidebar and 404 regions print in place, and two theme regions sit above the WooCommerce shop grid and the download grid. The builder template runs edge to edge and the builder preview uses the theme's type and colors.
* New: Appearance > Stackless tools. Export the theme settings as JSON, import them on another site, and restore one of the last three backups taken before each import.
* Improved: the WooCommerce stylesheet (46 KB) loads only on store screens, pages with WooCommerce or Stackless blocks and shortcodes, and pages with WooCommerce widgets, instead of on every page. Blocks and shortcodes it cannot see in advance still pull it in.
* Improved: the Stackless module layer loads on store screens or when a Stackless module queues its own styles, and the EDD header-cart script only where the header cart prints.
* Improved: related posts are cached per post and refresh when any post is published, edited or unpublished.
* Fixed: headings inside a group or column with its own text color take that color, and full-width groups, columns and covers with a background run square to the screen edges.
* Changed: the site header and footer markup moved to template-parts/site-header.php and template-parts/site-footer.php. Child themes that copied header.php or footer.php keep working; override the new parts to change the markup.

= 1.4.2 =
* Fixed: below 920px the WooCommerce checkout's order summary no longer sits in a card. WooCommerce lays it out flush there, so the card showed an empty strip above the title, text against its edge and a blank band under the totals on phones and in narrow embedded checkouts.
* Fixed: the store notice stays a bar at the bottom of the screen for logged-in users. With the admin bar showing it stretched over the whole page.

= 1.4.1 =
* Changed: one look across WooCommerce, Easy Digital Downloads and the rest of the theme. Corners are 4px on controls and cards and 2px on badges, chips and tags.
* Changed: WooCommerce notices are tinted boxes with a thin border in their color (blue for information, green for success, red for errors) instead of a white box with a colored stripe.
* Changed: small buttons and links that act as controls (notice and account table buttons, pagination, the cart's remove button, the currency switcher) are at least 40px tall, so they are easier to tap.

= 1.4.0 =
* New: Easy Digital Downloads support. Download grids and archives, single downloads with a sticky summary, the checkout (shortcode and block), receipts, order and download history, profile, login and register forms, and the Stackless for EDD account page all match the WooCommerce design. The EDD styles load only on EDD pages.
* New: a store toolbar on the downloads store page and archives with search, sorting, category chips, a currency switcher, quick links and a promo line, set under Appearance > Customize > Store (Easy Digital Downloads). The section shows only while EDD is active.
* New: the header search suggests downloads as you type, with thumbnail, category and price.
* Improved: account tables turn into cards on phones, Free Price Text labels show on free downloads, and downloads with options show "Choose an option" in grids.

= 1.3.1 =
* Fixed: the order received page. The order summary strip keeps its dividers clear of the text and wraps cleanly; the order details table has one line per row with totals aligned right; Additional information and Checkout consents show as matching cards; and every receipt section has even spacing.

= 1.3.0 =
* New: theme options under Appearance > Customize > Stackless to set how many related posts and related products show (0 to 12; 0 hides the section).
* Changed: 6 related posts and 6 related products show by default, in two rows of three. Related posts come from the same category first and are topped up with recent posts.

= 1.2.3 =
* Fix: My Account pages show the navigation beside the account content again. WooCommerce's clearfix pseudo-elements had become grid items, which pushed the menu into the content column and squeezed the content underneath.

= 1.2.2 =
* New: the currency switcher also sits at the bottom of the mobile menu on screens under 600px, where the header switcher is hidden.

= 1.2.1 =
* New: a compact currency switcher in the header and footer when Stackless for WooCommerce's Storefront Currency Selector is on and at least two currencies are enabled. The header shows the currency code (hidden on screens under 600px to keep the header buttons in one row); the footer shows the code and symbol. Hide either with the stackless_theme_currency_switcher filter.

= 1.2.0 =
* New: a Blog sidebar beside the post list on the blog, post archives and search results. Add widgets under Appearance > Widgets > Blog; until then it shows search, recent posts and categories. It stacks below the posts on screens under 960px. Turn it off with the stackless_theme_blog_sidebar filter.

= 1.1.2 =
* Fixed: in the Cart block's totals card, the coupon, subtotal, shipping and total rows line up with the card title instead of touching its border.

= 1.1.1 =
* Improved: the theme loads minified stylesheets and script (about 18% smaller in total, 42% for the script). Define SCRIPT_DEBUG to load the readable sources.

= 1.1.0 =
* New: the download from GauravTiwari.org now receives updates with a free lifetime license key, activated under Appearance > Stackless License. Unlimited websites per key.

= 1.0.2 =
* Fixed: on small phones the header fits the logo and all four header buttons without clipping the menu button.

= 1.0.1 =
* Fixed: blocks with a border color no longer turn their text light gray.
* Fixed: the WooCommerce store notice sits at the bottom of the screen instead of covering the header.

= 1.0.0 =
* Initial release: classic templates with Block Editor support, Tabler-based design tokens and icons, full styling for WooCommerce (classic templates and Cart/Checkout blocks) and every Stackless for WooCommerce module.
