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
