#!/usr/bin/env bash
# Write a .min.css / .min.js beside every theme stylesheet and script. Usage: scripts/minify.sh [--check]
# --check exits non-zero when a committed minified file is missing or stale.
set -euo pipefail
cd "$(dirname "$0")/../theme"
ESBUILD=(npx -y esbuild@0.25.10)
CHECK=${1:-}
stale=0
while IFS= read -r src; do
  out="${src%.*}.min.${src##*.}"
  fresh=$("${ESBUILD[@]}" "$src" --minify --log-level=warning --legal-comments=none)
  if [[ "$CHECK" == "--check" ]]; then
    [[ -f "$out" && "$(cat "$out")" == "$fresh" ]] || { echo "Stale: $out" >&2; stale=1; }
  else
    printf '%s\n' "$fresh" > "$out"
    printf '%-32s %6s -> %6s bytes\n' "$src" "$(wc -c < "$src" | tr -d ' ')" "$(wc -c < "$out" | tr -d ' ')"
  fi
done < <(find assets/css assets/js -type f \( -name '*.css' -o -name '*.js' \) ! -name '*.min.*' | sort)
exit $stale
