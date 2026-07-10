# Administration Faty Style

Une interface d’administration légère est disponible dans `admin/index.html`.

## Accès temporaire

- Identifiant : `admin`
- Mot de passe : `pwd123`

Cette authentification est temporaire et front-end. Elle convient pour une prévisualisation ou une administration simple sur un espace maîtrisé, mais elle doit être remplacée par une vraie authentification serveur si le site est exposé avec des besoins de sécurité sensibles.

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
