#!/usr/bin/env bash
# Light LMS — put the previous release back online.
#
# Usage (run by the deploy workflow when the site does not answer, or by hand):
#   bash /var/www/html/light-lms/current/deploy/rollback.sh [release]
#
# Without argument, goes back to the release just before the current one.
# Database migrations are not reverted: they must stay compatible with the
# previous release (add columns, do not drop them in the same deployment).
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/html/light-lms}"
PHP="${PHP_BIN:-php8.4}"
CURRENT="$(basename "$(readlink -f "$APP_DIR/current")")"

if [ $# -ge 1 ]; then
    TARGET="$1"
else
    # Release names start with their UTC date: alphabetical order is chronological.
    TARGET="$(find "$APP_DIR/releases" -mindepth 1 -maxdepth 1 -type d -printf '%f\n' | sort | awk -v current="$CURRENT" '$0 == current { print previous; exit } { previous = $0 }')"
fi

[ -n "$TARGET" ] || { echo "Aucune version précédente à remettre en ligne." >&2; exit 1; }
[ -d "$APP_DIR/releases/$TARGET" ] || { echo "Version introuvable : $TARGET" >&2; exit 1; }

echo "→ Retour de $CURRENT à $TARGET"
ln -sfn "$APP_DIR/releases/$TARGET" "$APP_DIR/current.tmp"
mv -Tf "$APP_DIR/current.tmp" "$APP_DIR/current"

sudo -n /usr/bin/systemctl reload php8.4-fpm || echo "⚠ Rechargement impossible : voir /etc/sudoers.d/light-lms" >&2
"$PHP" "$APP_DIR/current/artisan" queue:restart

echo "✓ Version $TARGET en ligne"
