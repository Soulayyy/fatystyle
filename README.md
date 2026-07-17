# Faty Style

Site statique autonome pour Faty Style, atelier de couture, haute couture et retouches à Limay.

Version de livraison : `v1.0.0`. Le site public et l’administration sont déployés séparément afin que la consultation du site ne dépende jamais de PostgreSQL ou de Filament.

## Objectif

Livrer un site premium, clair, transférable et sans dépendance WordPress. Le site présente l’atelier, les prestations, les univers de créations, le savoir-faire et les moyens de contact.

## Structure

- `index.html` : accueil, à propos court, label Maître Artisan, aperçu maîtrisé des vraies créations, méthode, avis Google et confiance.
- `presentation.html` : présentation de l’atelier et prestations principales.
- `savoir-faire.html` : page Création, univers de créations et galeries organisées.
- `pro.html` : page dédiée aux projets professionnels, prototypes et petites séries.
- `contact.html` : coordonnées, réseaux sociaux et formulaire email.
- `message-envoye.html` : confirmation après envoi du formulaire.
- `assets/` : CSS, JS, images et polices locales.
- `assets/images/` : images structurées par usage et par univers, avec noms simples pour faciliter une future administration.
- `data/content.json` : structure de contenu prête pour la future administration.
- `cms/` : administration Laravel/Filament complète, séparée du site public.
- `docs/README_INSTALLATION.md` : guide d’installation rapide.
- `docs/HEBERGEMENT_PRODUCTION.md` : remise complète à l’hébergeur et procédure de production.

## Lancer localement

```bash
python3 -m http.server 8080
```

Puis ouvrir `http://localhost:8080/`.

## Déploiement

Le site fonctionne sur un hébergement Apache ou Nginx classique. Aucun build, aucune base de données et aucun WordPress ne sont requis.

## Réseaux officiels

- Facebook : https://www.facebook.com/fatystylefr/
- Instagram : https://www.instagram.com/atelier_fatystyle/

## Administration

Le CMS dans `cms/` gère les pages, médias, prestations, créations, contacts, utilisateurs, publication, sauvegardes, exports et audit. L’accès est protégé par rôles et double authentification.

Les publications sont générées dans des releases immuables et basculées atomiquement vers le site public. L’ancienne administration statique et ses points d’écriture PHP ont été retirés.

Voir `docs/HEBERGEMENT_PRODUCTION.md`, `cms/README.md`, `docs/ADMIN_ARCHITECTURE.md` et `docs/RAPPORT_LIVRAISON_v1.0.0.md` pour l’installation, l’exploitation et les réserves avant passage sur le domaine définitif.
