#!/usr/bin/env bash
# Build an updater-free WordPress.org ZIP by default. --channel all also builds direct updates.
set -euo pipefail
THEME_BUILD_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec python3 "$THEME_BUILD_ROOT/distribution/build.py" --root "$THEME_BUILD_ROOT" "$@"
