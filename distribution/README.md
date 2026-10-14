# Distribution builds

`theme/` is always the clean WordPress.org source. The license client is injected only into the direct-download build.

- `./build.sh` builds the clean ZIP in `build/`.
- `./build.sh --channel direct` builds the licensed ZIP in `build/direct/`.
- `./build.sh --channel all` builds both and records their hashes in `build/manifest-all.json`.

The direct package adds `Update URI: https://gauravtiwari.org/product/stackless-theme/#license`, `inc/gt-license.php` (from `distribution/gt-license.php`), its bootstrap, and an update notice in readme.txt. The `#license` fragment keeps the anonymous free-theme updater from other Gatilab themes from claiming Stackless.

Since 1.1.0, updates come from FluentCart licensing on product 1183774 (variation 150, free, lifetime, unlimited activations). Customers get a key with the free checkout and activate it under Appearance > Stackless License. The key gates updates only. License requests send the key, theme version and site URL to `https://gauravtiwari.org/?fluent-cart=...`.

Stackless left the anonymous `gt-theme-updates` channel on 2026-10-14 (`_gt_public_theme_release.enabled=false`). Releases go to the FluentCart product: upload the licensed ZIP as a download, then set `license_settings.version` and `global_update_file`.
