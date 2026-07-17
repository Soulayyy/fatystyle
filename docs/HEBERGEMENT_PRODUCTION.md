# Remise technique à l'hébergeur — Faty Style

Ce document est la procédure de référence pour déployer le dépôt `Soulayyy/fatystyle` en production. Il ne contient volontairement aucun secret.

## 1. Composition du dépôt

Le projet est un monorepo composé de deux applications indépendantes :

1. **Site public statique** à la racine du dépôt : fichiers HTML, CSS, JavaScript, polices, images et `data/content.json`. Il n'a besoin ni de PHP ni de base de données pour servir les visiteurs.
2. **Administration** dans `cms/` : Laravel 13, Filament 5 et Livewire 4. Elle utilise PostgreSQL, conserve les médias originaux hors du webroot et publie des releases statiques vers le site public.

Le formulaire public envoie ses requêtes à `/cms-api/contact`. Le reverse proxy doit transmettre uniquement cette route vers `POST /api/contact` du CMS.

## 2. Prérequis de production

- Linux 64 bits ;
- Nginx recommandé ;
- PHP **8.3 minimum** (PHP 8.5 utilisé par l'image Docker du dépôt) avec FPM ;
- extensions PHP : `bcmath`, `ctype`, `curl`, `dom/xml`, `exif`, `fileinfo`, `gd` avec JPEG/WebP, `intl`, `mbstring`, `openssl`, `pdo_pgsql`, `tokenizer` et `zip` ;
- Composer 2 ;
- PostgreSQL 18 recommandé ;
- `pg_dump` et `pg_restore` de version compatible avec le serveur PostgreSQL ;
- cron, ou un scheduler équivalent, exécuté chaque minute ;
- SMTP transactionnel autorisant l'adresse d'expédition du domaine ;
- HTTPS pour le site et l'administration ;
- liens symboliques et renommage atomique autorisés sur le volume public ;
- sauvegarde chiffrée externe au serveur.

Node.js n'est pas requis pour servir le site public. Il n'est utile que si l'hébergeur reconstruit les ressources frontend du CMS.

## 3. Architecture d'hébergement attendue

Exemple de chemins, adaptables à la plateforme :

```text
/var/www/fatystyle/                         dépôt ou release applicative
/var/www/fatystyle/cms/public/              webroot exclusif de l'administration
/var/www/fatystyle/cms/.env                 secrets, jamais dans Git
/var/www/fatystyle/cms/storage/             stockage Laravel persistant
/var/www/fatystyle-content-releases/        releases générées par le CMS, persistant
/var/www/fatystyle/data/content.json        contenu public actif
/var/www/fatystyle/assets/images/cms/       médias CMS publics actifs
PostgreSQL                                  données persistantes séparées
```

Les processus PHP-FPM et Nginx doivent pouvoir lire `cms/public`. L'utilisateur PHP-FPM doit pouvoir écrire dans :

- `cms/storage/` ;
- `cms/bootstrap/cache/` ;
- `/var/www/fatystyle-content-releases/` ;
- les dossiers parents de `data/content.json` et `assets/images/cms/` afin de remplacer leurs liens symboliques atomiquement.

Le volume contenant les releases publiques et les médias doit être partagé entre le CMS et le site public. Sur une plateforme à conteneurs éphémères, monter explicitement ces chemins sur un volume persistant partagé.

## 4. Domaines et webroots

- domaine principal, par exemple `https://www.domaine-client.fr` : webroot à la racine publique du site statique ;
- administration, par exemple `https://admin.domaine-client.fr` : webroot **strictement** limité à `cms/public` ;
- PostgreSQL : non exposé à Internet ;
- l'administration reste toujours `noindex, nofollow`.

Ne jamais utiliser la racine `cms/` comme webroot et ne jamais rendre `.env`, `storage`, les sauvegardes, `.git`, `docs/` ou `deploy/` accessibles par HTTP.

Un exemple Nginx adaptable est fourni dans `deploy/nginx/fatystyle-production.conf.example`.

## 5. Variables d'environnement

Copier `cms/.env.production.example` vers un fichier secret `cms/.env`, puis remplacer toutes les valeurs `CHANGE_ME`. Les valeurs indispensables sont :

- URL finale du CMS et URL du site public ;
- clé Laravel `APP_KEY` générée sur le serveur ;
- accès PostgreSQL avec mot de passe fort ;
- paramètres SMTP ;
- destinataire `CMS_CONTACT_RECIPIENT=fatystyle@hotmail.fr` ;
- chemins absolus de publication partagée ;
- identité et mot de passe initial du super-administrateur.

Le mot de passe initial doit contenir au moins 14 caractères. Les trois variables `ADMIN_BOOTSTRAP_*` doivent être supprimées de l'environnement juste après la création du compte.

## 6. Première installation

Depuis la copie de travail du dépôt :

```bash
cd /var/www/fatystyle/cms
cp .env.production.example .env
# Renseigner les secrets dans .env, hors Git.

composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan cms:import-public-content ../data/content.json --dry-run
php artisan cms:import-public-content ../data/content.json
php artisan storage:link
php artisan optimize
```

L'import initial doit être exécuté une seule fois sur une base vide. Le `--dry-run` doit réussir avant l'import réel.

Ensuite :

1. retirer `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL` et `ADMIN_BOOTSTRAP_PASSWORD` de l'environnement ;
2. exécuter `php artisan config:cache` ;
3. donner les droits d'écriture à l'utilisateur PHP-FPM sans utiliser de permissions `777` ;
4. installer la tâche `deploy/cron/fatystyle-cms` en adaptant le chemin et la version de PHP ;
5. charger la configuration Nginx, vérifier avec `nginx -t`, puis recharger Nginx et PHP-FPM ;
6. se connecter au CMS et activer le TOTP obligatoire avec conservation sécurisée des codes de récupération.

## 7. Déploiements suivants

Avant chaque déploiement, sauvegarder PostgreSQL et les médias. Puis :

```bash
cd /var/www/fatystyle/cms
php artisan down
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

Idéalement, déployer le code dans une release datée, relier `.env` et `storage` depuis un répertoire partagé, puis basculer un lien `current` atomiquement. Conserver au moins la release applicative précédente pour rollback.

Ne jamais remplacer la base PostgreSQL, `cms/storage`, les releases de contenu ou les médias persistants lors d'un simple `git pull` ou redéploiement.

## 8. Tâches automatiques obligatoires

Le scheduler Laravel doit s'exécuter chaque minute :

```cron
* * * * * www-data cd /var/www/fatystyle/cms && /usr/bin/php artisan schedule:run --no-interaction >> /var/log/fatystyle-cms-scheduler.log 2>&1
```

Il gère la publication programmée, l'expiration des contenus, les sauvegardes, la rétention RGPD et le nettoyage. Aucun worker de queue permanent n'est nécessaire dans la version livrée : l'envoi SMTP du formulaire est synchrone.

## 9. Sauvegardes indispensables

À sauvegarder hors du serveur :

- base PostgreSQL ;
- `cms/storage/app/private/` et, de préférence, tout `cms/storage/` utile ;
- `/var/www/fatystyle-content-releases/` ;
- fichier `.env` dans un coffre de secrets ;
- configuration Nginx et certificats selon la politique de la plateforme.

Commandes applicatives disponibles :

```bash
php artisan cms:backup --type=database
php artisan cms:backup --type=full
```

Prévoir une sauvegarde PostgreSQL quotidienne, une sauvegarde complète hebdomadaire, une copie chiffrée externe et un test périodique de restauration. Une sauvegarde stockée uniquement sur le même VPS n'est pas suffisante.

## 10. Contrôles après installation

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan cms:publish-scheduled
curl -fsS https://admin.domaine-client.fr/up
curl -I https://www.domaine-client.fr/
curl -I https://www.domaine-client.fr/data/content.json
```

Vérifier ensuite manuellement :

- connexion à `/admin` et enrôlement 2FA ;
- chargement des six catégories et de leurs photos ;
- ajout d'un média test puis publication ;
- apparition atomique du nouveau contenu sur le site public ;
- envoi du formulaire, réception sur `fatystyle@hotmail.fr` et email de confirmation au visiteur ;
- absence d'accès HTTP à `.env`, `.git`, `cms/storage` et aux sauvegardes ;
- certificats TLS, redirection HTTP vers HTTPS et cookies `Secure` ;
- présence du `noindex` sur l'administration, mais pas sur le site public en production.

## 11. Informations à obtenir du client avant mise en production

- domaine et sous-domaine d'administration définitifs ;
- accès DNS ;
- identifiants SMTP ou compte transactionnel, adresse d'expédition validée et enregistrements SPF/DKIM/DMARC ;
- adresse du premier super-administrateur ;
- politique de sauvegarde externe et durée de rétention ;
- fenêtre de maintenance et personne à contacter en cas d'incident.

## 12. Points bloquants à signaler

Le déploiement ne doit pas être déclaré terminé si la plateforme ne permet pas l'un des éléments suivants :

- PostgreSQL persistant ;
- volume média persistant et partagé avec le site public ;
- création et remplacement atomique de liens symboliques ;
- cron chaque minute ;
- SMTP sortant ;
- HTTPS ;
- sauvegarde hors serveur.

En cas d'architecture serverless ou de services totalement isolés sans volume partagé, il faut adapter le mécanisme de publication vers un stockage objet/CDN avant la mise en production.
