# Faty Style

Site statique autonome pour Faty Style, atelier de couture, haute couture et retouches à Limay.

## Objectif

Livrer un site premium, clair, transférable et sans dépendance WordPress. Le site présente l’atelier, les prestations, les univers de créations, le savoir-faire et les moyens de contact.

## Structure

- `index.html` : accueil, à propos court, univers, carousel de vraies créations, confiance.
- `presentation.html` : présentation détaillée, prestations et univers de créations.
- `savoir-faire.html` : méthode de travail artisanale.
- `contact.html` : coordonnées, réseaux sociaux et formulaire WhatsApp.
- `assets/` : CSS, JS, images et polices locales.
- `data/content.json` : structure de contenu prête pour la future administration.
- `admin/README_ADMIN_STANDBY.md` : admin prévue pour une phase suivante.
- `docs/README_INSTALLATION.md` : guide d’installation.

## Lancer localement

```bash
python3 -m http.server 8080
```

Puis ouvrir `http://127.0.0.1:8080/`.

## Déploiement

Le site fonctionne sur un hébergement Apache ou Nginx classique. Aucun build, aucune base de données et aucun WordPress ne sont requis.

## Réseaux officiels

- Facebook : https://www.facebook.com/fatystylefr/
- Instagram : https://www.instagram.com/fatystylefr/

## Future admin

Une future interface d’administration pourra modifier `data/content.json` et gérer les images rangées dans `assets/images/`.
