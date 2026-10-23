# Déploiement sur le VPS Ionos

Principe : GitHub Actions construit l'application (dépendances PHP de production, assets Vite) après la CI, l'envoie par SSH sur le VPS et l'active **sans interruption** : chaque version a son dossier dans `releases/`, et un lien `current` pointe vers la version en ligne. Le VPS n'a besoin ni de Node ni de Composer.

```
/var/www/light-lms/
├── releases/20261023101500-a1b2c3d/   une version par déploiement (5 conservées)
├── shared/.env                        réglages de production (jamais dans Git)
├── shared/storage/                    journaux, sessions, cache, fichiers
└── current -> releases/…              servi par Nginx et le worker
```

Fichiers fournis dans le dépôt :

| Fichier                         | Rôle                                                    |
| ------------------------------- | ------------------------------------------------------- |
| `deploy/nginx.conf`             | Hôte virtuel Nginx                                      |
| `deploy/supervisor.conf`        | Worker de file d'attente (envoi des invitations)        |
| `deploy/env.production.example` | Modèle du `.env` de production                          |
| `deploy/activate.sh`            | Active une version (liens, migrations, caches, worker)  |
| `.github/workflows/deploy.yml`  | Construit et déploie après chaque CI réussie sur `main` |

## 1. Préparer le serveur (une seule fois)

Prérequis : un VPS Ionos sous **Ubuntu 24.04**, un nom de domaine dont l'enregistrement **A** pointe vers l'adresse IP du VPS, un accès SSH administrateur.

### Utilisateur et pare-feu

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy
sudo ufw allow OpenSSH && sudo ufw allow 'Nginx Full' && sudo ufw enable
```

### Paquets

```bash
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt install -y nginx mysql-server supervisor certbot python3-certbot-nginx \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd
```

Offre de base (peu de mémoire) : ajouter un fichier d'échange de 1 Go et alléger PHP-FPM et MySQL.

```bash
sudo fallocate -l 1G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

- `/etc/php/8.4/fpm/pool.d/www.conf` : `pm = ondemand`, `pm.max_children = 5`
- `/etc/mysql/mysql.conf.d/mysqld.cnf` : `innodb_buffer_pool_size = 128M`

### Base de données

```bash
sudo mysql -e "CREATE DATABASE light_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'light_lms'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON light_lms.* TO 'light_lms'@'localhost';"
```

### Dossiers et `.env`

```bash
sudo mkdir -p /var/www/light-lms/{releases,shared/storage}
sudo chown -R deploy:www-data /var/www/light-lms
sudo chmod -R 2775 /var/www/light-lms/shared
```

Copier `deploy/env.production.example` vers `/var/www/light-lms/shared/.env` et le compléter : domaine (`APP_URL`), mot de passe MySQL, boîte d'envoi Ionos (`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Générer la clé de l'application :

```bash
php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

et la coller dans `APP_KEY=`. Puis : `chmod 640 /var/www/light-lms/shared/.env`.

### Nginx et HTTPS

Copier `deploy/nginx.conf` vers `/etc/nginx/sites-available/light-lms` en remplaçant `lms.example.fr` par le domaine, puis :

```bash
sudo ln -s /etc/nginx/sites-available/light-lms /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d lms.example.fr
```

### Worker de file d'attente

```bash
sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/light-lms.conf
sudo supervisorctl reread && sudo supervisorctl update
```

(Le worker démarrera correctement après le premier déploiement, quand `current` existera.)

## 2. Brancher GitHub (une seule fois)

Créer une paire de clés dédiée au déploiement (sur ton poste) :

```bash
ssh-keygen -t ed25519 -C "deploy light-lms" -f deploy_key -N ""
```

- Ajouter le contenu de `deploy_key.pub` à `/home/deploy/.ssh/authorized_keys` sur le VPS.
- Dans GitHub : **Settings → Environments → New environment** `production`, puis ses **secrets** :

| Secret               | Valeur                                         |
| -------------------- | ---------------------------------------------- |
| `DEPLOY_HOST`        | Adresse IP ou nom du VPS                       |
| `DEPLOY_USER`        | `deploy`                                       |
| `DEPLOY_SSH_KEY`     | Contenu de `deploy_key` (clé **privée**)       |
| `DEPLOY_KNOWN_HOSTS` | Résultat de `ssh-keyscan -H <adresse du VPS>`  |
| `DEPLOY_PORT`        | Facultatif, si SSH n'écoute pas sur le port 22 |

- Dans **Settings → Secrets and variables → Actions → Variables** : créer `DEPLOY_ENABLED` = `true`.

Supprimer ensuite `deploy_key` de ton poste (la clé n'est plus utile qu'à GitHub).

## 3. Premier déploiement

1. **Actions → deploy → Run workflow** (ou pousser sur `main` : le déploiement suit la CI).
2. Sur le VPS, créer le compte formateur :

```bash
cd /var/www/light-lms/current && php artisan lms:create-trainer
```

3. Ouvrir le site, se connecter, créer une promotion et inviter un apprenant de test pour vérifier l'envoi réel de l'email.

Ensuite, **chaque push sur `main` dont la CI passe est déployé automatiquement.**

## 4. Exploitation

**Sauvegarde quotidienne** de la base (crontab de `deploy`, 14 jours conservés) :

```bash
mkdir -p ~/backups
crontab -e
# 30 2 * * * mysqldump --single-transaction light_lms | gzip > ~/backups/light_lms-$(date +\%F).sql.gz && find ~/backups -name '*.sql.gz' -mtime +14 -delete
```

(Renseigner l'utilisateur et le mot de passe MySQL dans `~/.my.cnf`, `chmod 600`.)

| Besoin                          | Commande / emplacement                                                                                                                |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| Journaux de l'application       | `/var/www/light-lms/shared/storage/logs/laravel-*.log`                                                                                |
| Journal du worker               | `/var/www/light-lms/shared/storage/logs/worker.log`                                                                                   |
| État du worker                  | `sudo supervisorctl status light-lms-worker`                                                                                          |
| Emails en échec                 | `php artisan queue:failed`, puis `php artisan queue:retry all`                                                                        |
| Revenir à la version précédente | `ln -sfn /var/www/light-lms/releases/<précédente> /var/www/light-lms/current && php /var/www/light-lms/current/artisan queue:restart` |
| Mode maintenance                | `php artisan down` / `php artisan up` (dans `current`)                                                                                |
