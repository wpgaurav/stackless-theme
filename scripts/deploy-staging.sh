#!/usr/bin/env bash
# Sync the theme to a staging site over SSH. Usage: scripts/deploy-staging.sh [--activate]
# Needs STACKLESS_THEME_STAGING_SSH (user@host) and STACKLESS_THEME_STAGING_PATH (WordPress root)
# in the environment or ~/.env; optional STACKLESS_THEME_STAGING_KEY (identity file).
set -euo pipefail
cd "$(dirname "$0")/.."
read_env() { [[ -n "${!1:-}" ]] && { printf '%s' "${!1}"; return; }; [[ -f "$HOME/.env" ]] && grep -m1 "^$1=" "$HOME/.env" | cut -d= -f2- | sed -e 's/^["'"'"']//' -e 's/["'"'"']$//'; }
HOST=$(read_env STACKLESS_THEME_STAGING_SSH); WPROOT=$(read_env STACKLESS_THEME_STAGING_PATH); KEY=$(read_env STACKLESS_THEME_STAGING_KEY)
[[ -n "$HOST" && -n "$WPROOT" ]] || { echo "Set STACKLESS_THEME_STAGING_SSH and STACKLESS_THEME_STAGING_PATH" >&2; exit 1; }
SSH_OPTS=(-o BatchMode=yes -o ConnectTimeout=15); [[ -n "$KEY" ]] && SSH_OPTS+=(-i "$KEY" -o IdentitiesOnly=yes)
rsync -az --delete --exclude '.DS_Store' -e "ssh ${SSH_OPTS[*]}" theme/ "$HOST:$WPROOT/wp-content/themes/stackless-theme/"
if [[ "${1:-}" == "--activate" ]]; then
  ssh "${SSH_OPTS[@]}" "$HOST" "cd $WPROOT && wp theme activate stackless-theme"
fi
echo "Deployed $(grep -m1 "^Version:" theme/style.css | awk '{print $NF}')"
