# Déploiement sur le VPS Ionos

## Le protocole de déploiement continu

**Ce qui est sur `main` est en ligne.** Il n'y a rien à faire sur le serveur pour livrer une nouvelle version :

```
branche de travail ──fusion──▶ main ──▶ tests (CI) ──▶ deploy ──▶ vérification ──▶ ✓ en ligne
                                          │ échec        │ échec      │ le site ne répond pas
                                          ▼              ▼            ▼
                                     rien ne part   l'ancienne    retour automatique
                                                    version reste à la version précédente
```

1. **Travailler sur une branche** (`feature/…`), jamais directement sur `main`.
2. **Fusionner dans `main` et pousser** : c'est la mise en production.
3. GitHub lance les **tests**. S'ils échouent, rien n'est déployé.
4. S'ils passent, **deploy** construit la version, l'envoie sur le VPS et l'active **sans interruption** (migrations comprises).
5. **deploy** vérifie que l'adresse du site suivie de `/up` répond. Sinon, il **remet automatiquement la version précédente** en ligne.
6. En cas d'échec, GitHub t'envoie un email. Le détail est dans l'onglet **Actions** du dépôt.

Deux règles pour que ça reste sûr :

- **Migrations compatibles** : la version précédente doit fonctionner avec la nouvelle base (ajouter une colonne, oui ; en supprimer une, dans un déploiement ultérieur). Les retours en arrière ne défont pas les migrations.
- **Ne rien modifier à la main dans `releases/`** : chaque déploiement repart de GitHub.

Relancer un déploiement sans changer le code : **Actions → deploy → Run workflow**. Revenir en arrière à la main : voir [Exploitation](#5-exploitation).

### Comment c'est rangé sur le serveur

```
/var/www/html/light-lms/
├── releases/20261112101500-a1b2c3d/   une version par déploiement (5 conservées)
├── shared/.env                        réglages de production (jamais dans Git)
├── shared/storage/                    journaux, sessions, cache
└── current -> releases/…              la version en ligne
```

Le VPS héberge **d'autres sites** (Nextcloud, WordPress : Apache, mod_php 8.3, MariaDB). Light LMS s'y ajoute sans rien changer pour eux : son propre site Apache, son propre PHP-FPM 8.4 (commande `php8.4`), sa propre base `light_lms`, son propre utilisateur `deploy`. Le dossier est fermé aux autres sites, seul `current/public` est servi, pour son domaine.

> ⚠ Sur ce serveur : **ne jamais installer `mysql-server`** (il désinstalle MariaDB) **ni `nginx`** (conflit avec Apache), et ne pas changer la version par défaut de `php` (8.3).

| Fichier                         | Rôle                                                          |
| ------------------------------- | ------------------------------------------------------------- |
| `deploy/server-setup.sh`        | Prépare le serveur une fois (ou le remet à zéro)              |
| `.github/workflows/deploy.yml`  | Construit, déploie, vérifie, revient en arrière si besoin     |
| `deploy/activate.sh`            | Active une version sur le serveur (liens, migrations, caches) |
| `deploy/rollback.sh`            | Remet la version précédente en ligne                          |
| `deploy/apache.conf`            | Site Apache du domaine choisi                                 |
| `deploy/supervisor.conf`        | Worker d'envoi des emails                                     |
| `deploy/env.production.example` | Modèle du `.env` de production                                |

## 1. Avant de commencer

- **Domaine** : choisir l'adresse du site (ex. `lms.example.fr`) et créer, dans l'espace Ionos (Domaines → DNS), un enregistrement **A** vers l'adresse IP du VPS. Vérifier depuis ton poste : `nslookup <domaine>` doit renvoyer cette adresse.
- **Pare-feu Ionos** : ports 80 et 443 ouverts (déjà le cas pour tes autres sites).
- **Boîte d'envoi** : une adresse Ionos (ex. `noreply@example.fr`) et son mot de passe.
- **MariaDB** : `sudo mysql -e "SELECT 1"` doit répondre sans demander de mot de passe. Sinon, voir l'[annexe](#annexe--mot-de-passe-root-mariadb).

## 2. Préparer le serveur

### Clé de déploiement (sur ton poste)

```bash
ssh-keygen -t ed25519 -C "deploy light-lms" -f deploy_key
```

Appuyer deux fois sur Entrée (pas de phrase de passe). Deux fichiers sont créés : `deploy_key` (privée, pour GitHub) et `deploy_key.pub` (publique, pour le serveur). Afficher la clé publique pour la copier : `cat deploy_key.pub`.

### Script d'installation (sur le VPS)

```bash
cd ~
curl -fsSL https://raw.githubusercontent.com/fievel20/light-lms/main/deploy/server-setup.sh -o light-lms-setup.sh
sudo bash light-lms-setup.sh
```

Il demande le domaine, la clé publique, l'adresse et le mot de passe de la boîte d'envoi, puis l'accord pour le certificat HTTPS. Il installe PHP 8.4 et Supervisor (**sans jamais retirer de paquet**), crée l'utilisateur `deploy`, les dossiers, la base et son mot de passe, le `.env`, le site Apache et le worker. Il termine en affichant les valeurs à donner à GitHub.

Le script peut être **relancé sans risque** : ce qui existe déjà est conservé.

**Repartir de zéro** (après une installation ratée) : `sudo bash light-lms-setup.sh reset` retire tout ce que le script a installé, en demandant confirmation pour la base, les fichiers et l'utilisateur `deploy`. Les paquets, MariaDB et les autres sites ne sont pas touchés.

Vérifier depuis ton poste que la clé fonctionne : `ssh -i deploy_key deploy@<adresse du VPS>` doit ouvrir une session sans mot de passe (`exit` pour quitter).

## 3. Brancher GitHub

Dans le dépôt : **Settings → Environments → New environment** `production`, puis **Add environment secret** :

| Secret               | Valeur                                                                 |
| -------------------- | ---------------------------------------------------------------------- |
| `DEPLOY_HOST`        | L'adresse IP affichée par le script                                    |
| `DEPLOY_KNOWN_HOSTS` | Les lignes affichées par le script sous « DEPLOY_KNOWN_HOSTS »         |
| `DEPLOY_SSH_KEY`     | Tout le contenu de `deploy_key`, de `-----BEGIN` à `-----END…` compris |
| `DEPLOY_PORT`        | Facultatif, seulement si SSH n'écoute pas sur le port 22               |

Puis **Settings → Secrets and variables → Actions → Variables → New repository variable**, deux fois :

- `SITE_URL` = l'adresse du site, avec `https://` (ex. `https://lms.example.fr`) ;
- `DEPLOY_ENABLED` = `true`.

Supprimer ensuite `deploy_key` et `deploy_key.pub` de ton poste.

## 4. Premier déploiement

1. **Actions → deploy → Run workflow** (branche `main`). Compter 3 minutes ; toutes les étapes doivent être vertes.
2. Sur le VPS, créer le compte formateur :

    ```bash
    cd /var/www/html/light-lms/current && sudo -u deploy php8.4 artisan lms:create-trainer
    ```

3. Vérifier le worker : `sudo supervisorctl status light-lms-worker` doit afficher `RUNNING`.
4. Vérifier que les réglages sont protégés : `curl -I http://<adresse du VPS>/light-lms/shared/.env` doit répondre `403` (ou `404`), jamais `200`.
5. Ouvrir le site, se connecter, créer une promotion et s'inviter avec une autre adresse pour tester l'email.

C'est en place : désormais, **chaque fusion dans `main` dont les tests passent est déployée automatiquement.**

## 5. Exploitation

Les commandes `artisan` se lancent dans `/var/www/html/light-lms/current`, en tant que `deploy` et avec `php8.4` : `sudo -u deploy php8.4 artisan …`.

| Besoin                       | Commande                                                                 |
| ---------------------------- | ------------------------------------------------------------------------ |
| Version en ligne             | `readlink /var/www/html/light-lms/current`                               |
| Revenir à la version d'avant | `sudo -u deploy bash /var/www/html/light-lms/current/deploy/rollback.sh` |
| Journal de l'application     | `tail -50 /var/www/html/light-lms/shared/storage/logs/laravel-*.log`     |
| Journal Apache du site       | `sudo tail -50 /var/log/apache2/light-lms-error.log`                     |
| Worker                       | `sudo supervisorctl status light-lms-worker` (ou `restart`)              |
| Emails en échec              | `artisan queue:failed`, puis `artisan queue:retry all`                   |
| Mode maintenance             | `artisan down` / `artisan up`                                            |

**Sauvegarde quotidienne** de la base et des images des séquences (14 jours conservés). Le mot de passe `light_lms` est dans `shared/.env` (`DB_PASSWORD`) :

```bash
sudo -u deploy bash -c 'mkdir -p ~/backups && printf "[client]\nuser=light_lms\npassword=%s\n" "$(grep ^DB_PASSWORD= /var/www/html/light-lms/shared/.env | cut -d= -f2-)" > ~/.my.cnf && chmod 600 ~/.my.cnf'
sudo -u deploy crontab -e
```

et ajouter les deux lignes :

```
30 2 * * * mariadb-dump --single-transaction light_lms | gzip > ~/backups/light_lms-$(date +\%F).sql.gz && find ~/backups -name '*.sql.gz' -mtime +14 -delete
45 2 * * * tar -czf ~/backups/images-$(date +\%F).tar.gz -C /var/www/html/light-lms/shared/storage/app private && find ~/backups -name 'images-*.tar.gz' -mtime +14 -delete
```

Les images des séquences sont dans `shared/storage/app/private/sequences` (et les icônes dans `private/icons`, sauvegardées par la même commande) : hors du dossier public, elles ne passent que par l'application (personnes connectées) et survivent aux déploiements.

### Mise à jour du serveur pour les images (phase 2)

Les images des séquences pèsent jusqu'à 5 Mo, alors qu'un serveur installé avant la phase 2 refuse les envois de plus de 2 Mo. **Une fois**, sur le VPS, retélécharger le script et le relancer : il relève les limites (Apache et PHP-FPM) et conserve tout le reste (`.env`, base, certificat).

```bash
cd ~
curl -fsSL https://raw.githubusercontent.com/fievel20/light-lms/main/deploy/server-setup.sh -o light-lms-setup.sh
sudo bash light-lms-setup.sh
```

Vérifier ensuite : `grep LimitRequestBody /etc/apache2/sites-available/light-lms*.conf` doit afficher `8388608`.

## Annexe : mot de passe root MariaDB

Si `sudo mysql` demande un mot de passe oublié, le réinitialiser (Nextcloud et WordPress sont coupés environ deux minutes, aucune donnée n'est touchée) :

```bash
sudo systemctl stop mariadb
sudo systemctl set-environment MYSQLD_OPTS="--skip-grant-tables --skip-networking"
sudo systemctl start mariadb
sudo mysql -e "FLUSH PRIVILEGES; ALTER USER 'root'@'localhost' IDENTIFIED VIA unix_socket OR mysql_native_password USING PASSWORD('NOUVEAU_MOT_DE_PASSE');"
sudo systemctl unset-environment MYSQLD_OPTS
sudo systemctl restart mariadb
```

Ensuite, `sudo mysql` fonctionne sans mot de passe, et le nouveau mot de passe reste valable pour les autres usages.
