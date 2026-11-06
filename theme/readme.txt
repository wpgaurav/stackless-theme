=== Stackless ===
Contributors: gauravtiwari
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A minimal classic theme for WooCommerce and Easy Digital Downloads stores running Stackless.

== Description ==

Stackless is a quiet, fast classic theme. It styles every semantic HTML element, every form control, WooCommerce's classic templates and its Cart and Checkout blocks, Easy Digital Downloads' downloads, checkout, receipts and account pages, and every Stackless for WooCommerce and Stackless for EDD module (wishlist, sold count, quotes, licenses, subscriptions, saved carts, order bumps and more).

* Classic PHP templates with full Block Editor support: theme.json palette, type scale, spacing, wide and full alignment, editor styles.
* System font stack, no web fonts, no jQuery, one small script for the mobile menu and header search.
* Header with logo, two-level primary menu, account link and live cart count.
* Full-screen product search with live results, popular categories and best sellers.
* Single product pages with a sticky summary column; editorial single posts with author box and related posts.
* Footer widget area and footer menu. Optional "Shop" widget area above the product grid.
* Design settings for colors, reading and wide widths, fonts and logo width, plus a footer line and header and footer code.
* Full width and Canvas templates, and per-page options to hide the title, header or footer or lay the header over the first section.
* Page Blocks Builder regions (header, hero, before and after content, sidebar, footer, 404, shop and store) and an edge-to-edge builder template.
* Settings export and import with backups, and an importer for sites moving from Marketers Delight.

== Installation ==

1. Appearance → Themes → Add New → Upload Theme, choose stackless-theme.zip, then Activate.
2. Appearance → Menus: assign menus to "Primary menu" and "Footer menu". Without a primary menu the header lists the shop and a few top-level pages.
3. Appearance → Customize → Site Identity: add a logo.

== Copyright ==

Stackless theme, Copyright 2026 Gaurav Tiwari. Distributed under the terms of the GNU GPL v2 or later.

Design tokens follow Tabler (https://tabler.io), Copyright (c) 2018-2026 codecalm.net, MIT License.
Icons: Tabler Icons 3.47.0 (https://tabler.io/icons), Copyright (c) 2020-2026 Paweł Kuna, MIT License.

== Changelog ==

= 1.5.0 =
* New: design settings under Appearance > Customize > Stackless: Design. Primary, text, heading, background and card colors, the reading and wide widths, body and heading fonts (system stacks or your own), logo width and an optional fade between pages. Only changed values print, as a few custom properties, and the editor and theme.json palette follow them.
* New: a footer text field with {year} and {site}, and fields for code in the head and before the closing body tag (administrators who may post unfiltered HTML only), under Stackless: Footer and code.
* New: Full width (no title) and Canvas (no header or footer) templates for pages, posts and other public post types (not WooCommerce products or EDD downloads, which keep their store layouts). Full-width blocks, covers, background groups and raw HTML sections run edge to edge; text and other blocks keep the reading width with space around them.
* New: a Layout panel in the editor to hide the title, the site header or the site footer, or to lay the header over the first section until the page scrolls. Over a dark cover the header switches to light text (filter stackless_theme_header_on_dark for other heroes). Products and other classic-editor screens get the same options in a side box.
* New: Page Blocks Builder support. Blocks placed in the header or footer regions replace the theme's own (filter stackless_theme_region_mode to keep both); the hero, before content, after content, sidebar and 404 regions print in place, and two theme regions sit above the WooCommerce shop grid and the download grid. The builder template runs edge to edge and the builder preview uses the theme's type and colors.
* New: Appearance > Stackless tools. Export the theme settings as JSON, import them on another site, and restore one of the last three backups taken before each import.
* New: Marketers Delight importer (also under Tools > Import). It previews, then moves the logo, colors, widths, fonts, header and footer scripts, menu locations, widgets, the footer copyright, MD page templates (PBB Template, Builder, Full Blank, Premium Builder) and per-page header, footer, headline and transparent-header settings. It lists what Stackless cannot take over: MD page blocks (with a link to Page Blocks Builder's own importer), MD shortcodes and blocks in content, dropin post types and MD-only features. MD's data is never changed.
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
