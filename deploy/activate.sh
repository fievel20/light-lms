#!/usr/bin/env bash
# Light LMS — activate an uploaded release on the server.
#
# Usage (run by the deploy workflow, or by hand):
#   bash /var/www/light-lms/releases/<release>/deploy/activate.sh <release>
#
# Layout:
#   /var/www/light-lms/
#   ├── releases/<release>/   one directory per deployment
#   ├── shared/.env           production settings (never in Git)
#   ├── shared/storage/       logs, sessions files, uploads…
#   └── current -> releases/<release>   what Nginx and the worker serve
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/light-lms}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
RELEASE="${1:?Usage: activate.sh <release>}"
RELEASE_DIR="$APP_DIR/releases/$RELEASE"

[ -d "$RELEASE_DIR" ] || { echo "Release introuvable : $RELEASE_DIR" >&2; exit 1; }
[ -f "$APP_DIR/shared/.env" ] || { echo "Fichier manquant : $APP_DIR/shared/.env" >&2; exit 1; }

echo "→ Liens vers les fichiers partagés"
mkdir -p "$APP_DIR/shared/storage"/{app/public,framework/{cache/data,sessions,views},logs}
rm -rf "$RELEASE_DIR/storage"
ln -sfn "$APP_DIR/shared/storage" "$RELEASE_DIR/storage"
ln -sfn "$APP_DIR/shared/.env" "$RELEASE_DIR/.env"

cd "$RELEASE_DIR"

echo "→ Migrations"
php artisan migrate --force

echo "→ Caches"
php artisan optimize

echo "→ Activation"
ln -sfn "$RELEASE_DIR" "$APP_DIR/current.tmp"
mv -Tf "$APP_DIR/current.tmp" "$APP_DIR/current"

echo "→ Redémarrage du worker (après sa tâche en cours)"
php artisan queue:restart

echo "→ Nettoyage des anciennes versions (on garde les $KEEP_RELEASES dernières)"
ls -1dt "$APP_DIR"/releases/*/ | tail -n +"$((KEEP_RELEASES + 1))" | xargs -r rm -rf

echo "✓ Version $RELEASE en ligne"
