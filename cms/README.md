# Faty Style CMS

Application d'administration du site Faty Style. Le CMS Laravel/Filament remplace l’administration JSON historique et publie le contenu public par releases atomiques.

## Socle

- Laravel 13
- Filament 5 / Livewire 4
- PostgreSQL 18
- Permissions granulaires et journal d'audit
- Authentification TOTP obligatoire avec codes de récupération chiffrés
- Modèle multilingue prêt pour les pages et les blocs
- Releases publiques atomiques et réversibles

## Fonctions disponibles

- édition des pages multilingues et de leurs blocs réordonnables ;
- workflow brouillon, validation, publication, masquage et archivage ;
- versions immuables et verrou optimiste contre les écrasements concurrents ;
- médiathèque privée avec empreinte SHA-256, dimensions, poids, texte alternatif et suivi des usages ;
- gestion de la navigation, des prestations, des univers de création et des réglages ;
- import transactionnel et réexécutable du contenu public historique.
- gestion sécurisée des utilisateurs et des cinq rôles ;
- demandes de contact avec assignation, statut et notes internes ;
- journal d’audit en lecture seule et redirections SEO ;
- tableau de bord synthétique et prévisualisation protégée ;
- releases JSON immuables, bascule atomique et rollback.
- workflow homogène des pages, prestations et univers, avec programmation et expiration automatiques ;
- contrôles de publication bloquants pour les éléments SEO et médias indispensables ;
- formulaire public interne avec double email, antispam, consentement, suivi, export et rétention RGPD ;
- sauvegardes PostgreSQL et médias, vérification d’intégrité, téléchargement et restauration réservée au super-administrateur ;
- exports JSON/CSV, alertes opérationnelles, rétention des versions, corbeilles et sauvegardes ;
- remplacement global d’un média tout en conservant ses utilisations.

## Démarrage local avec Docker

```bash
cp .env.example .env
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
docker compose up -d
```

Administration : `http://localhost:8083/admin`

Mailpit : `http://localhost:8026`

Les variables `ADMIN_BOOTSTRAP_*` permettent de créer le premier super-administrateur lors du seeding. Elles ne doivent jamais être commitées.

Le planificateur Laravel doit fonctionner chaque minute en production :

```cron
* * * * * cd /var/www/fatystyle-cms/current && php artisan schedule:run >> /dev/null 2>&1
```

## Tests

```bash
docker compose run --rm app composer test
docker compose run --rm app ./vendor/bin/pint --test
docker compose run --rm app composer audit
```

Les tests unitaires utilisent SQLite en mémoire pour leur rapidité. Une suite d'intégration PostgreSQL est également obligatoire avant chaque livraison.

La recette `v1.0.0` a été exécutée sur SQLite et PostgreSQL 18. Les résultats et les limites connues sont consignés dans `../docs/RAPPORT_LIVRAISON_v1.0.0.md`.

## Import initial du site public

La commande lit le JSON actuel et copie les originaux référencés vers le stockage privé. Elle ne modifie jamais le frontend existant.

```bash
php artisan cms:import-public-content ../data/content.json --dry-run
php artisan cms:import-public-content ../data/content.json
```

La clé Web3Forms historique est volontairement exclue de l’import. Les secrets des futurs formulaires seront fournis par l’environnement du CMS.

## Déploiement VPS

Le document d’exploitation est disponible dans `docs/ADMIN_ARCHITECTURE.md`. L’application doit être servie depuis `cms/public`, avec PHP-FPM, PostgreSQL, un stockage partagé persistant et une configuration Nginx distincte du site public.

Variables de publication indispensables en production :

```dotenv
CMS_PUBLIC_RELEASE_PATH=/var/www/fatystyle-content-releases
CMS_PUBLIC_CONTENT_LINK=/var/www/fatystyle/data/content.json
CMS_PUBLIC_MEDIA_LINK=/var/www/fatystyle/assets/images/cms
CMS_MEDIA_MAX_UPLOAD_MB=20
CMS_MEDIA_MAX_PIXELS=60000000
CMS_MEDIA_WEBP_QUALITY=82
CMS_CONTACT_RECIPIENT=fatystyle@hotmail.fr
CMS_CONTACT_RETENTION_MONTHS=36
CMS_BACKUP_RETENTION_DAYS=30
```

Le processus PHP doit pouvoir écrire dans le dossier des releases et remplacer atomiquement les liens du contenu et des médias publics. Une sauvegarde de la base et des liens actifs doit précéder toute opération de publication en production.

Après une migration ou une modification des réglages de qualité, les variantes peuvent être générées sans toucher aux originaux :

```bash
php artisan cms:generate-media-variants
php artisan cms:generate-media-variants --force
```

## Exploitation

```bash
php artisan cms:publish-scheduled
php artisan cms:backup --type=database
php artisan cms:backup --type=full
php artisan cms:purge-contact-data --dry-run
php artisan cms:prune --dry-run
```

Les binaires PostgreSQL `pg_dump` et `pg_restore` ainsi que l’extension PHP ZIP sont requis pour les sauvegardes. Une restauration crée d’abord une nouvelle sauvegarde complète de sécurité.

Les sauvegardes locales ne remplacent pas une copie chiffrée hors du VPS. Le fournisseur et la politique de rétention externe doivent être validés avant la production définitive.

## Principes de sécurité

- aucun secret dans Git ou JavaScript ;
- originaux médias stockés hors webroot ;
- permissions contrôlées côté serveur ;
- 2FA TOTP et récupération chiffrée ;
- sessions serveur chiffrées ;
- toutes les mutations journalisées ;
- l'administration de développement et de recette reste `noindex`.

## Publication publique

Le CMS générera des releases publiques immuables. La publication finale basculera un lien symbolique vers une release entièrement validée, ce qui garantit une mise en ligne atomique et un rollback immédiat.
