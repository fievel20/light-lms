# Déploiement sur le VPS Ionos

Principe : GitHub Actions construit l'application (dépendances PHP de production, assets Vite) après la CI, l'envoie par SSH sur le VPS et l'active **sans interruption** : chaque version a son dossier dans `releases/`, et un lien `current` pointe vers la version en ligne. Le VPS n'a besoin ni de Node ni de Composer.

```
/var/www/html/light-lms/
├── releases/20261023101500-a1b2c3d/   une version par déploiement (5 conservées)
├── shared/.env                        réglages de production (jamais dans Git)
├── shared/storage/                    journaux, sessions, cache, fichiers
└── current -> releases/…              servi par Apache et le worker
```

## Le serveur

Le VPS héberge **déjà d'autres sites** (Nextcloud, WordPress) avec **Apache**, **mod_php 8.3** et **MariaDB**. Light LMS s'y ajoute **sans rien changer pour eux** :

| Élément         | Autres sites                 | Light LMS                                  |
| --------------- | ---------------------------- | ------------------------------------------ |
| Serveur web     | Apache                       | Apache, hôte virtuel `lms.example.fr`         |
| PHP             | mod_php 8.3 (commande `php`) | PHP-FPM 8.4 (commande `php8.4`)            |
| Base de données | MariaDB                      | MariaDB, base et compte `light_lms` dédiés |
| Utilisateur     | `www-data`                   | `deploy` (déploiement, PHP-FPM et worker)  |

> ⚠ **Ne jamais installer `mysql-server` ni `nginx`** sur ce serveur : `mysql-server` désinstalle MariaDB, et Nginx entre en conflit avec Apache sur le port 80. Ne pas changer non plus la version par défaut de `php` (8.3), utilisée par les autres sites et le cron de Nextcloud.

Fichiers fournis dans le dépôt :

| Fichier                         | Rôle                                                    |
| ------------------------------- | ------------------------------------------------------- |
| `deploy/apache.conf`            | Hôte virtuel Apache (PHP-FPM 8.4 pour ce site seul)     |
| `deploy/supervisor.conf`        | Worker de file d'attente (envoi des invitations)        |
| `deploy/env.production.example` | Modèle du `.env` de production                          |
| `deploy/activate.sh`            | Active une version (liens, migrations, caches, worker)  |
| `.github/workflows/deploy.yml`  | Construit et déploie après chaque CI réussie sur `main` |

Ces fichiers ne sont pas sur le serveur : les ouvrir sur GitHub, copier leur contenu et le coller dans `sudo nano <destination>` (enregistrer avec `Ctrl+O`, quitter avec `Ctrl+X`).

## 1. Préparer le serveur (une seule fois)

Prérequis : un enregistrement DNS **A** `lms.example.fr` vers l'adresse IP du VPS, et les ports **80** et **443** ouverts dans le pare-feu Ionos (**Serveurs & Cloud → Réseau → Stratégies de pare-feu**) — c'est déjà le cas pour les autres sites.

### Utilisateur

```bash
sudo adduser --disabled-password --gecos "" deploy
sudo usermod -aG www-data deploy
```

### Paquets PHP 8.4 et Supervisor

```bash
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt install php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd supervisor
```

Lire la liste annoncée par `apt` avant de valider : elle ne doit **rien retirer**.

### PHP-FPM 8.4

Dans `/etc/php/8.4/fpm/pool.d/www.conf` : `user = deploy` (laisser `group = www-data`), `pm = ondemand`, `pm.max_children = 5`, puis :

```bash
sudo systemctl restart php8.4-fpm
```

PHP-FPM, le worker et les déploiements tournent ainsi tous sous `deploy` : les journaux et caches créés par l'un restent modifiables par les autres. Pour la même raison, lancer toute commande `artisan` en tant que `deploy` (`sudo -u deploy php8.4 artisan …`), jamais en `root`.

Autoriser `deploy` à recharger PHP-FPM 8.4 (et rien d'autre) : le script d'activation le fait à chaque version pour vider le cache OPcache.

```bash
echo 'deploy ALL=(root) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm' | sudo tee /etc/sudoers.d/light-lms
sudo chmod 440 /etc/sudoers.d/light-lms && sudo visudo -c
```

### Base de données

Avec le mot de passe `root` de MariaDB (remplacer `MOT_DE_PASSE_FORT`) :

```bash
sudo mysql -u root -p -e "CREATE DATABASE light_lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'light_lms'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_FORT';
GRANT ALL PRIVILEGES ON light_lms.* TO 'light_lms'@'localhost';"
```

### Dossiers et `.env`

```bash
sudo mkdir -p /var/www/html/light-lms/releases /var/www/html/light-lms/shared/storage/logs
sudo chown -R deploy:www-data /var/www/html/light-lms
sudo chmod -R 2775 /var/www/html/light-lms/shared
```

Copier `deploy/env.production.example` vers `/var/www/html/light-lms/shared/.env` et le compléter : mot de passe MariaDB (`DB_PASSWORD`), boîte d'envoi Ionos (`MAIL_USERNAME` et `MAIL_FROM_ADDRESS` identiques, `MAIL_PASSWORD`). Générer la clé de l'application :

```bash
php8.4 -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

et la coller dans `APP_KEY=`. Puis :

```bash
sudo chown deploy:www-data /var/www/html/light-lms/shared/.env
sudo chmod 640 /var/www/html/light-lms/shared/.env
```

### Apache et HTTPS

Copier `deploy/apache.conf` vers `/etc/apache2/sites-available/light-lms.conf`, puis :

```bash
sudo a2enmod proxy_fcgi setenvif rewrite headers expires
sudo a2ensite light-lms
sudo apache2ctl configtest && sudo systemctl reload apache2
sudo certbot --apache -d lms.example.fr
```

`configtest` doit répondre `Syntax OK`. **Ne pas lancer `a2enconf php8.4-fpm`** : cela ferait passer tous les sites en PHP 8.4.

Le dossier est sous `/var/www/html`, servi aussi par les autres sites : `apache.conf` le ferme à tous (sinon `shared/.env` serait téléchargeable) et ne rouvre que `current/public` pour `lms.example.fr`. Vérifier après le premier déploiement que `curl -I http://<adresse du VPS>/light-lms/shared/.env` répond `403` ou `404`.

### Worker de file d'attente

Copier `deploy/supervisor.conf` vers `/etc/supervisor/conf.d/light-lms.conf`, puis :

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

(Le worker démarrera correctement après le premier déploiement, quand `current` existera.)

## 2. Brancher GitHub (une seule fois)

Créer une paire de clés dédiée au déploiement (sur ton poste, appuyer deux fois sur Entrée pour ne pas mettre de phrase de passe) :

```bash
ssh-keygen -t ed25519 -C "deploy light-lms" -f deploy_key
```

- Ajouter le contenu de `deploy_key.pub` (une seule ligne `ssh-ed25519 …`) aux clés autorisées de `deploy` sur le VPS :

```bash
sudo mkdir -p /home/deploy/.ssh
sudo nano /home/deploy/.ssh/authorized_keys   # coller la ligne
sudo chown -R deploy:deploy /home/deploy/.ssh
sudo chmod 700 /home/deploy/.ssh && sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

- Vérifier depuis ton poste : `ssh -i deploy_key deploy@<adresse du VPS>` doit ouvrir une session sans mot de passe.
- Dans GitHub : **Settings → Environments → New environment** `production`, puis ses **secrets** :

| Secret               | Valeur                                           |
| -------------------- | ------------------------------------------------ |
| `DEPLOY_HOST`        | Adresse IP du VPS                                |
| `DEPLOY_USER`        | `deploy`                                         |
| `DEPLOY_SSH_KEY`     | Contenu complet de `deploy_key` (clé **privée**) |
| `DEPLOY_KNOWN_HOSTS` | Résultat de `ssh-keyscan -H <adresse du VPS>`    |
| `DEPLOY_PORT`        | Facultatif, si SSH n'écoute pas sur le port 22   |

- Dans **Settings → Secrets and variables → Actions → Variables** : créer `DEPLOY_ENABLED` = `true`.

Supprimer ensuite `deploy_key` et `deploy_key.pub` de ton poste (la clé n'est plus utile qu'à GitHub).

## 3. Premier déploiement

1. **Actions → deploy → Run workflow** (ou pousser sur `main` : le déploiement suit la CI).
2. Sur le VPS, créer le compte formateur :

```bash
cd /var/www/html/light-lms/current && sudo -u deploy php8.4 artisan lms:create-trainer
```

3. Vérifier le worker : `sudo supervisorctl status light-lms-worker` doit afficher `RUNNING`.
4. Ouvrir https://lms.example.fr, se connecter, créer une promotion et inviter un apprenant de test pour vérifier l'envoi réel de l'email.

Ensuite, **chaque push sur `main` dont la CI passe est déployé automatiquement.**

## 4. Exploitation

**Sauvegarde quotidienne** de la base (crontab de `deploy`, 14 jours conservés) :

```bash
mkdir -p ~/backups
crontab -e
# 30 2 * * * mariadb-dump --single-transaction light_lms | gzip > ~/backups/light_lms-$(date +\%F).sql.gz && find ~/backups -name '*.sql.gz' -mtime +14 -delete
```

(Renseigner l'utilisateur `light_lms` et son mot de passe dans `~/.my.cnf`, section `[client]`, `chmod 600`.)

| Besoin                          | Commande / emplacement                                                                                                                                                                      |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Journaux de l'application       | `/var/www/html/light-lms/shared/storage/logs/laravel-*.log`                                                                                                                                 |
| Journaux Apache du site         | `/var/log/apache2/light-lms-error.log`                                                                                                                                                      |
| Journal du worker               | `/var/www/html/light-lms/shared/storage/logs/worker.log`                                                                                                                                    |
| État du worker                  | `sudo supervisorctl status light-lms-worker`                                                                                                                                                |
| Emails en échec                 | `php8.4 artisan queue:failed`, puis `php8.4 artisan queue:retry all`                                                                                                                        |
| Revenir à la version précédente | `ln -sfn /var/www/html/light-lms/releases/<précédente> /var/www/html/light-lms/current && sudo systemctl reload php8.4-fpm && php8.4 /var/www/html/light-lms/current/artisan queue:restart` |
| Mode maintenance                | `php8.4 artisan down` / `php8.4 artisan up` (dans `current`)                                                                                                                                |
