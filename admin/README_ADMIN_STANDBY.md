# Administration Faty Style

Une interface d’administration légère est disponible dans `admin/index.html`.

## Accès

L'administration n'embarque aucun identifiant ni secret dans le code. Le serveur PHP doit fournir :

- `FATYSTYLE_ADMIN_USER` : identifiant administrateur, `admin` par défaut ;
- `FATYSTYLE_ADMIN_TOKEN` : secret long et aléatoire, obligatoire.

Sans `FATYSTYLE_ADMIN_TOKEN`, l'authentification, la sauvegarde et l'import d'images sont refusés.

## Contenus administrables

- textes du site ;
- coordonnées ;
- hero ;
- prestations ;
- catégories de créations ;
- photos et galerie ;
- contact ;
- réseaux sociaux Facebook et Instagram.
- SEO local : titres, descriptions, mots-clés, Open Graph et données structurées.

## Sauvegarde

L’admin charge et modifie `data/content.json`.

Deux modes sont prévus :

- sauvegarde réelle via `admin/save-content.php` si l’hébergement exécute PHP et autorise l’écriture du fichier JSON ;
- export/import JSON et sauvegarde locale navigateur si l’hébergement est statique ou si PHP n’est pas disponible.

Les images peuvent être importées via `admin/upload-image.php` si PHP est disponible. Les uploads acceptés sont limités aux formats `jpg`, `jpeg`, `png`, `webp` et `avif`, avec un maximum de 5 Mo.

## Limites volontaires

La structure fine du design, les grilles CSS, les comportements responsive et le JavaScript métier ne sont pas modifiables depuis l’admin. C’est volontaire afin de préserver un rendu premium stable.

Le site public ne contient pas de bouton Admin visible. L’accès se fait par URL directe.
