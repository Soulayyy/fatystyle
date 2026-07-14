# Faty Style

Site statique autonome pour Faty Style, atelier de couture, haute couture et retouches à Limay.

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
- `admin/` : base technique de future administration, non mise en avant sur le site public.
- `docs/README_INSTALLATION.md` : guide d’installation.

## Lancer localement

```bash
python3 -m http.server 8080
```

Puis ouvrir `http://localhost:8080/`.

## Déploiement

Le site fonctionne sur un hébergement Apache ou Nginx classique. Aucun build, aucune base de données et aucun WordPress ne sont requis.

## Réseaux officiels

- Facebook : https://www.facebook.com/fatystylefr/
- Instagram : https://www.instagram.com/fatystylefr/

## Administration

L’interface `admin/index.html` sert de base de travail pour la future administration : textes, coordonnées, prestations, catégories de créations, galeries, savoir-faire, contact, réseaux sociaux et SEO.

L'accès doit être configuré côté serveur avec `FATYSTYLE_ADMIN_USER` et un `FATYSTYLE_ADMIN_TOKEN` long et aléatoire. Aucun secret d'administration n'est stocké dans le dépôt.

La sauvegarde réelle utilise `admin/save-content.php` et nécessite un hébergement compatible PHP avec droits d’écriture sur `data/content.json`. Si PHP n’est pas disponible, l’admin propose une sauvegarde locale navigateur et un export/import JSON.

Le bouton `Voir le site` ouvre une prévisualisation locale (`?preview=admin`) basée sur le contenu stocké dans le navigateur. Cette prévisualisation ne remplace pas la version publiée tant que `data/content.json` n’est pas exporté/importé ou sauvegardé côté serveur.
