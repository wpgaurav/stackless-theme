# Distribution builds

`theme/` is always the clean WordPress.org source. The updater is injected only into the direct-download build.

- `./build.sh` builds the clean ZIP in `build/`.
- `./build.sh --channel direct` builds the updater-enabled ZIP in `build/direct/`.
- `./build.sh --channel all` builds both and records their hashes in `build/manifest-all.json`.

The direct package adds an Update URI header, `inc/gt-theme-updater.php`, its bootstrap, and an update notice in readme.txt. It requires no account or license key. Its checks do not transmit the site URL or email. It honors WordPress's automatic-update preference and verifies each downloaded ZIP against the release checksum.

The shared client source is maintained in `~/Development/gt-theme-updates/client/gt-theme-updater.php`. This directory holds the vendored build copy, taken from Hassium on 2026-09-12 and identical to the shared source that day. Keep the copies synchronized when the updater changes.

Existing users must install the direct build once to receive the updater. The updater runs while an updater-enabled theme (or a child using it) is active, and can report other installed direct-build themes from the same collection. Clean WordPress.org builds do not load it.
