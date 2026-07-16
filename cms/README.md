# Faty Style CMS

Application d'administration du site Faty Style. Ce dossier remplace progressivement l'administration JSON historique sans modifier le site public avant la recette finale.

## Socle

- Laravel 13
- Filament 5 / Livewire 4
- PostgreSQL 18
- Permissions granulaires et journal d'audit
- Authentification TOTP obligatoire avec codes de récupération chiffrés
- Modèle multilingue prêt pour les pages et les blocs
- Releases publiques atomiques et réversibles

## Fonctions disponibles dans le deuxième jalon

- édition des pages multilingues et de leurs blocs réordonnables ;
- workflow brouillon, validation, publication, masquage et archivage ;
- versions immuables et verrou optimiste contre les écrasements concurrents ;
- médiathèque privée avec empreinte SHA-256, dimensions, poids, texte alternatif et suivi des usages ;
- gestion de la navigation, des prestations, des univers de création et des réglages ;
- import transactionnel et réexécutable du contenu public historique.

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

## Tests

```bash
docker compose run --rm app composer test
docker compose run --rm app ./vendor/bin/pint --test
docker compose run --rm app composer audit
```

Les tests unitaires utilisent SQLite en mémoire pour leur rapidité. Une suite d'intégration PostgreSQL est également obligatoire avant chaque livraison.

## Import initial du site public

La commande lit le JSON actuel et copie les originaux référencés vers le stockage privé. Elle ne modifie jamais le frontend existant.

```bash
php artisan cms:import-public-content ../data/content.json --dry-run
php artisan cms:import-public-content ../data/content.json
```

La clé Web3Forms historique est volontairement exclue de l’import. Les secrets des futurs formulaires seront fournis par l’environnement du CMS.

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
