#!/usr/bin/env bash
# Light LMS — prepare the shared VPS, once. Safe to run again: what is already
# in place is kept. It never removes a package and never touches the other
# sites (Apache + mod_php 8.3 + MariaDB stay as they are).
#
#   curl -fsSL https://raw.githubusercontent.com/fievel20/light-lms/main/deploy/server-setup.sh -o light-lms-setup.sh
#   sudo bash light-lms-setup.sh          # install
#   sudo bash light-lms-setup.sh reset    # remove what this script installed
set -Eeuo pipefail
# Functions are not named "install" or "reset": that would shadow the commands.
# Never stop silently: name the command that failed.
trap 'fail "Arrêt imprévu ligne $LINENO : $BASH_COMMAND"' ERR

DOMAIN="${DOMAIN:-}"
APP_DIR="${APP_DIR:-/var/www/html/light-lms}"
RAW="https://raw.githubusercontent.com/fievel20/light-lms/${REF:-main}/deploy"
ENV_FILE="$APP_DIR/shared/.env"
POOL=/etc/php/8.4/fpm/pool.d/light-lms.conf
SITE=/etc/apache2/sites-available/light-lms.conf
WORKER=/etc/supervisor/conf.d/light-lms.conf
SUDOERS=/etc/sudoers.d/light-lms

step() { printf '\n\033[1m→ %s\033[0m\n' "$*"; }
info() { printf '  %s\n' "$*"; }
fail() { printf '\033[31m✗ %s\033[0m\n' "$*" >&2; exit 1; }
# Prompts read the terminal itself (< /dev/tty): text pasted ahead of time is
# never taken as an answer, nor run as a command once the script ends.
confirm() { local answer; read -r -p "  $1 [o/N] " answer < /dev/tty; [[ $answer =~ ^[oO]$ ]]; }

# Replaces KEY=… in the .env file (values go through the environment, so no escaping issue).
set_env() {
    KEY="$1" VALUE="$2" awk 'BEGIN { k = ENVIRON["KEY"]; v = ENVIRON["VALUE"] }
        index($0, k "=") == 1 { print k "=" v; next } { print }' "$ENV_FILE" > "$ENV_FILE.tmp"
    mv "$ENV_FILE.tmp" "$ENV_FILE"
}

preflight() {
    [ "$(id -u)" -eq 0 ] || fail "Lance ce script avec sudo."
    systemctl is-active --quiet apache2 || fail "Apache ne tourne pas : ce script attend le serveur Apache existant."
    systemctl is-active --quiet mariadb || fail "MariaDB ne tourne pas."
    mysql -e 'SELECT 1' > /dev/null 2>&1 \
        || fail "« sudo mysql » demande un mot de passe : voir l'annexe « Mot de passe root MariaDB » du guide."
    [ -n "$DOMAIN" ] || read -r -p "  Domaine du site (ex. lms.example.fr) : " DOMAIN < /dev/tty
    [[ $DOMAIN =~ ^[a-z0-9.-]+\.[a-z]+$ ]] || fail "Domaine invalide : « $DOMAIN »."
}

setup_server() {
    preflight

    step "Paquets PHP 8.4 et Supervisor (aucun paquet ne sera retiré)"
    if ! apt-cache policy php8.4-fpm | grep -q 'Candidate: [0-9]'; then
        add-apt-repository -y ppa:ondrej/php
    fi
    apt-get update -q
    # --no-remove: apt stops instead of removing anything (MariaDB, mod_php…).
    apt-get install -y -q --no-remove --no-install-recommends \
        php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
        php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd php8.4-opcache supervisor python3-certbot-apache

    if [ -x /usr/bin/php8.3 ] && [ "$(readlink -f /etc/alternatives/php)" != /usr/bin/php8.3 ]; then
        update-alternatives --set php /usr/bin/php8.3
        info "Commande « php » remise sur 8.3 (autres sites, cron Nextcloud)."
    fi

    step "Utilisateur deploy"
    id deploy > /dev/null 2>&1 || adduser --disabled-password --gecos "" deploy
    usermod -aG www-data deploy
    install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
    touch /home/deploy/.ssh/authorized_keys
    chown deploy:deploy /home/deploy/.ssh/authorized_keys
    chmod 600 /home/deploy/.ssh/authorized_keys
    if grep -q 'deploy light-lms' /home/deploy/.ssh/authorized_keys; then
        info "Clé de déploiement déjà autorisée."
    else
        while true; do
            read -r -p "  Colle la clé publique (une seule ligne, contenu de deploy_key.pub), ou Entrée pour plus tard : " key < /dev/tty
            if [ -z "$key" ]; then
                info "À ajouter plus tard dans /home/deploy/.ssh/authorized_keys (ou relance le script)."
                break
            elif [[ $key == ssh-* ]]; then
                echo "$key" >> /home/deploy/.ssh/authorized_keys
                info "Clé ajoutée."
                break
            fi
            info "Ce n'est pas une clé publique : elle commence par « ssh-ed25519 ». Réessaie."
        done
    fi

    step "Dossiers $APP_DIR"
    mkdir -p "$APP_DIR/releases" "$APP_DIR/shared/storage/logs"
    chown -R deploy:www-data "$APP_DIR"
    find "$APP_DIR/shared" -type d -exec chmod 2775 {} +

    step "PHP-FPM 8.4 : pool dédié, sous l'utilisateur deploy"
    [ -e /etc/apache2/conf-enabled/php8.4-fpm.conf ] \
        && fail "La configuration globale php8.4-fpm est activée dans Apache (tous les sites passeraient en 8.4) : « sudo a2disconf php8.4-fpm » puis relance."
    cat > "$POOL" <<'POOL'
[light-lms]
user = deploy
group = www-data
listen = /run/php/light-lms.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
pm = ondemand
pm.max_children = 5
pm.process_idle_timeout = 30s
pm.max_requests = 500
POOL
    # PHP-FPM 8.4 serves Light LMS only: the default pool would just use memory.
    [ -f /etc/php/8.4/fpm/pool.d/www.conf ] && mv /etc/php/8.4/fpm/pool.d/www.conf /etc/php/8.4/fpm/pool.d/www.conf.disabled
    php-fpm8.4 -t
    systemctl restart php8.4-fpm

    local rule
    rule="$(mktemp)"
    echo 'deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm' > "$rule"
    visudo -cqf "$rule" && install -m 440 "$rule" "$SUDOERS"
    rm -f "$rule"

    step "Base de données light_lms"
    local db_password
    if [ -f "$ENV_FILE" ]; then
        db_password="$(grep '^DB_PASSWORD=' "$ENV_FILE" | cut -d= -f2- || true)"
    fi
    [ -n "${db_password:-}" ] || db_password="$(openssl rand -hex 24)"
    mysql <<SQL
CREATE DATABASE IF NOT EXISTS light_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'light_lms'@'localhost' IDENTIFIED BY '$db_password';
ALTER USER 'light_lms'@'localhost' IDENTIFIED BY '$db_password';
GRANT ALL PRIVILEGES ON light_lms.* TO 'light_lms'@'localhost';
SQL

    step "Réglages de production ($ENV_FILE)"
    if [ -f "$ENV_FILE" ]; then
        info "Déjà présent, conservé."
    else
        curl -fsSL "$RAW/env.production.example" -o "$ENV_FILE"
        set_env APP_KEY "base64:$(openssl rand -base64 32)"
        set_env APP_URL "https://$DOMAIN"
        set_env DB_PASSWORD "$db_password"
        local mail mail_password
        read -r -p "  Adresse de la boîte d'envoi Ionos (ex. noreply@example.fr) : " mail < /dev/tty
        read -r -s -p "  Mot de passe de cette boîte (rien ne s'affiche) : " mail_password < /dev/tty
        echo
        [[ $mail_password != *"'"* ]] || fail "Le mot de passe contient une apostrophe : renseigne MAIL_PASSWORD à la main dans $ENV_FILE."
        set_env MAIL_USERNAME "$mail"
        set_env MAIL_FROM_ADDRESS "$mail"
        set_env MAIL_PASSWORD "'$mail_password'"
        info "Créé."
    fi
    chown deploy:www-data "$ENV_FILE"
    chmod 640 "$ENV_FILE"

    step "Apache : site $DOMAIN"
    if [ -f "$SITE" ]; then
        info "Déjà présent, conservé (certbot y a peut-être ajouté la redirection HTTPS)."
    else
        curl -fsSL "$RAW/apache.conf" | sed "s#lms\.example\.fr#$DOMAIN#g; s#/var/www/html/light-lms#$APP_DIR#g" > "$SITE"
    fi
    a2enmod -q proxy_fcgi setenvif rewrite headers expires
    a2ensite -q light-lms
    apache2ctl configtest
    systemctl reload apache2

    if [ -d "/etc/letsencrypt/live/$DOMAIN" ]; then
        info "Certificat HTTPS déjà en place."
    elif confirm "Demander le certificat HTTPS de $DOMAIN maintenant ? (le DNS doit déjà pointer vers ce serveur)"; then
        certbot --apache --redirect -d "$DOMAIN"
    else
        info "Plus tard : sudo certbot --apache --redirect -d $DOMAIN"
    fi

    step "Worker d'envoi des emails"
    curl -fsSL "$RAW/supervisor.conf" | sed "s#/var/www/html/light-lms#$APP_DIR#g" > "$WORKER"
    supervisorctl reread > /dev/null
    supervisorctl update
    info "Il démarrera après le premier déploiement."

    step "Terminé. Pour GitHub (Settings → Environments → production) :"
    local ip
    ip="$(hostname -I | awk '{ print $1 }')"
    info "DEPLOY_HOST        = $ip"
    info "DEPLOY_KNOWN_HOSTS = les lignes ci-dessous"
    ssh-keyscan -H "$ip" 2> /dev/null
}

reset_server() {
    preflight
    case "$APP_DIR" in */light-lms) ;; *) fail "APP_DIR inattendu : $APP_DIR" ;; esac

    step "Remise à zéro de Light LMS (les autres sites ne sont pas touchés)"
    confirm "Continuer ?" || exit 0

    rm -f "$WORKER"
    supervisorctl reread > /dev/null && supervisorctl update

    a2dissite -q light-lms light-lms-le-ssl 2> /dev/null || true
    rm -f "$SITE" /etc/apache2/sites-available/light-lms-le-ssl.conf
    systemctl reload apache2
    if [ -d "/etc/letsencrypt/live/$DOMAIN" ] && confirm "Supprimer le certificat HTTPS de $DOMAIN ?"; then
        certbot delete --cert-name "$DOMAIN"
    fi

    rm -f "$POOL" "$SUDOERS"
    if [ -f /etc/php/8.4/fpm/pool.d/www.conf.disabled ]; then
        mv /etc/php/8.4/fpm/pool.d/www.conf.disabled /etc/php/8.4/fpm/pool.d/www.conf
    fi
    if [ -f /etc/php/8.4/fpm/pool.d/www.conf ]; then
        systemctl restart php8.4-fpm
    fi

    if confirm "Supprimer la base light_lms et toutes ses données (définitif) ?"; then
        mysql -e "DROP DATABASE IF EXISTS light_lms; DROP USER IF EXISTS 'light_lms'@'localhost';"
    fi
    if [ -d "$APP_DIR" ] && confirm "Supprimer $APP_DIR (versions, .env, journaux) ?"; then
        rm -rf "$APP_DIR"
    fi
    if id deploy > /dev/null 2>&1 && confirm "Supprimer l'utilisateur deploy et sa clé SSH ?"; then
        deluser --remove-home deploy
    fi

    step "Remise à zéro terminée. Pour réinstaller : sudo bash $0"
}

case "${1:-install}" in
    install) setup_server ;;
    reset) reset_server ;;
    *) fail "Usage : sudo bash $0 [install|reset]" ;;
esac
