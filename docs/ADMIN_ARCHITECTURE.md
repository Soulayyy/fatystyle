# Architecture de l’administration Faty Style

## Objectif

Le CMS remplace l’administration JSON historique par une application Laravel structurée. Le site public actuel reste indépendant jusqu’à validation complète de la migration.

## Flux de publication cible

1. Un utilisateur modifie un brouillon dans Filament.
2. Les validations métier, SEO, médias et permissions sont exécutées côté serveur.
3. Une version immuable du contenu est enregistrée.
4. Une release publique complète est générée dans un nouveau répertoire.
5. Les contrôles de cohérence et d’intégrité sont exécutés.
6. Un lien symbolique est basculé atomiquement vers la nouvelle release.
7. La release précédente reste disponible pour un rollback immédiat.

La base PostgreSQL et l’administration ne sont donc jamais sollicitées par un visiteur du site public.

## Domaines applicatifs

- **Identity** : utilisateurs, rôles, permissions, sessions et 2FA.
- **Content** : pages, traductions, blocs, statuts et versions.
- **Publishing** : validations, prévisualisations, programmation, releases et rollback.
- **Media** : originaux privés, variantes, métadonnées, usages et corbeille.
- **Contact** : formulaires, demandes, pièces jointes, consentements et emails.
- **SEO** : métadonnées, indexation, redirections, sitemap et données structurées.
- **Operations** : audit, exports, sauvegardes, alertes et supervision.

## Invariants déjà posés

- les contenus utilisent des ULID stables ;
- les slugs sont uniques par langue ;
- les blocs sont limités à des types validés ;
- les secrets TOTP et codes de récupération sont chiffrés ;
- aucun utilisateur inactif ou sans rôle ne peut ouvrir le panneau ;
- les suppressions métier utilisent une corbeille ;
- chaque page possède un verrou de version pour prévenir les écrasements concurrents ;
- les releases possèdent une empreinte et peuvent référencer la release restaurée.
- les médias originaux sont privés, contrôlés par leur signature réelle, dédupliqués par SHA-256 et ne peuvent pas être supprimés lorsqu’ils sont utilisés ;
- les usages des médias sont indexés automatiquement pour les prestations, couvertures, galeries et images Open Graph, avec vérification directe de secours ;
- les originaux restent privés ; les formats publics sont des variantes WebP responsives de 320 à 1 920 pixels, régénérables sans perte ;
- l’import historique est transactionnel, simulable et n’importe aucun secret du formulaire public ;
- les transitions éditoriales sont contrôlées côté serveur et capturées dans des versions immuables.

## Administration disponible

Le panneau couvre désormais les pages, traductions, blocs, médias, menus, prestations, univers de création et réglages généraux. Les permissions de chaque ressource reprennent les matrices de rôles du socle. Les contenus JSON complexes restent éditables sans perte en attendant les formulaires spécialisés par type de bloc.

Il couvre également les utilisateurs, rôles, demandes de contact, redirections, journal d’audit, prévisualisations et releases publiques. Le tableau de bord expose uniquement les compteurs autorisés à l’utilisateur connecté.

## Publication et retour arrière

Chaque publication reconstruit un `content.json` complet et copie les médias gérés par le CMS dans un répertoire numéroté et immuable. Une empreinte SHA-256 est enregistrée en base et dans le manifeste. Lorsque `CMS_PUBLIC_CONTENT_LINK` et `CMS_PUBLIC_MEDIA_LINK` sont configurés, les liens publics du contenu et des médias sont remplacés par renommage atomique de liens symboliques préparés dans leurs répertoires respectifs. Une restauration crée une nouvelle entrée de release pointant vers l’artefact historique choisi ; l’historique n’est jamais réécrit.

## Déploiement courant

- site public : Nginx sur le port `8082` ;
- administration : Nginx et PHP-FPM sur un port distinct ;
- code : releases horodatées avec un lien `current` ;
- données persistantes : `.env`, stockage Laravel et PostgreSQL séparés du code ;
- administration et prévisualisations : en-tête `X-Robots-Tag: noindex, nofollow` ;
- sessions : serveur, chiffrées, cookies HTTP-only et SameSite strict.

## Compatibilité multilingue

Les pages et blocs séparent dès le départ les données structurelles de leurs traductions. Seul le français est affiché dans le premier périmètre, mais l’ajout d’une langue ne nécessitera pas de refonte du schéma.

## Environnements

- **Développement** : Docker Compose, PostgreSQL et Mailpit locaux.
- **Recette** : base et secrets séparés, accès protégé, `noindex`.
- **Production** : déploiement automatisé, sauvegardes externes, HTTPS et supervision.

Le site public existant n’est modifié qu’après validation de la recette et test du plan de retour arrière.
