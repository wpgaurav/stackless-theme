# Stackless theme

A minimal classic WordPress theme for WooCommerce stores running [Stackless for WooCommerce](https://gauravtiwari.org/product/stackless-for-woocommerce/). The installable theme is in `theme/` (folder name `stackless-theme` once packaged).

- Classic PHP templates, full Block Editor support (`theme.json` settings, editor styles, wide/full alignment).
- Design tokens from Tabler (MIT); icons from Tabler Icons 3.47.0 (MIT), bundled as SVG files in `assets/icons/`.
- PHP 7.4+, WordPress 6.6+, WooCommerce 9.9+.

## Layout

```
theme/
  style.css, functions.php, theme.json, readme.txt, screenshot.png
  header.php, footer.php, index.php, singular.php, 404.php, comments.php, searchform.php
  template-parts/      post summary, single, empty state
  inc/                 setup, icons, template tags, WooCommerce integration
  assets/css/          base (tokens, elements, forms, blocks), layout, woocommerce, stackless, editor
  assets/js/theme.js   mobile menu and header search toggles
  assets/icons/        Tabler Icons used by the theme
```

CSS is plain, unbuilt CSS with `--st-*` custom properties on `:root`. WooCommerce and Stackless layers load only when those plugins are active. Asset URLs carry the file's modification time, so edits bust caches without purging anything.

## Scripts

- `./build.sh` builds the clean ZIP in `build/`; `./build.sh --channel all` also builds the direct-download ZIP with the license client in `build/direct/` (see `distribution/README.md`).
- `scripts/deploy-staging.sh [--activate]` syncs `theme/` to a staging site (reads `STACKLESS_THEME_STAGING_*` from the environment or `~/.env`).
- `scripts/staging-content.php` seeds review content (style guide, journal posts, menus, footer widgets); run it with `wp eval-file`.

Releases go through FluentCart licensing on product 1183774, not the anonymous `gt-theme-updates` pipeline (see `distribution/README.md`).
