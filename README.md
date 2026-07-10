# Faty Style

Site statique autonome pour Faty Style, atelier de couture, haute couture et retouches à Limay.

## Objectif

Livrer un site premium, clair, transférable et sans dépendance WordPress. Le site présente l’atelier, les prestations, les univers de créations, le savoir-faire et les moyens de contact.

## Structure

- `index.html` : accueil, à propos court, univers, aperçu maîtrisé des vraies créations, confiance.
- `presentation.html` : présentation détaillée, prestations et univers de créations.
- `savoir-faire.html` : méthode de travail artisanale.
- `contact.html` : coordonnées, réseaux sociaux et formulaire email.
- `message-envoye.html` : confirmation après envoi du formulaire.
- `assets/` : CSS, JS, images et polices locales.
- `assets/images/` : images structurées par usage et par univers, avec noms simples pour faciliter une future administration.
- `data/content.json` : structure de contenu prête pour la future administration.
- `admin/` : interface d’administration légère pour modifier `data/content.json`, avec sauvegarde PHP si l’hébergement le permet et export/import JSON en secours.
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

## Administration

L’interface `admin/index.html` permet de modifier les textes, coordonnées, prestations, catégories de créations, galeries, savoir-faire, contact, réseaux sociaux et SEO.

Accès temporaire :

- identifiant : `admin`
- mot de passe : `pwd123`

La sauvegarde réelle utilise `admin/save-content.php` et nécessite un hébergement compatible PHP avec droits d’écriture sur `data/content.json`. Si PHP n’est pas disponible, l’admin propose une sauvegarde locale navigateur et un export/import JSON.

Le bouton `Voir le site` ouvre une prévisualisation locale (`?preview=admin`) basée sur le contenu stocké dans le navigateur. Cette prévisualisation ne remplace pas la version publiée tant que `data/content.json` n’est pas exporté/importé ou sauvegardé côté serveur.
