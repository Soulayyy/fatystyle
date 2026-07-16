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
- les médias originaux sont privés, dédupliqués par SHA-256 et ne peuvent pas être supprimés lorsqu’ils sont utilisés ;
- l’import historique est transactionnel, simulable et n’importe aucun secret du formulaire public ;
- les transitions éditoriales sont contrôlées côté serveur et capturées dans des versions immuables.

## Administration disponible

Le panneau couvre désormais les pages, traductions, blocs, médias, menus, prestations, univers de création et réglages généraux. Les permissions de chaque ressource reprennent les matrices de rôles du socle. Les contenus JSON complexes restent éditables sans perte en attendant les formulaires spécialisés par type de bloc.

## Compatibilité multilingue

Les pages et blocs séparent dès le départ les données structurelles de leurs traductions. Seul le français est affiché dans le premier périmètre, mais l’ajout d’une langue ne nécessitera pas de refonte du schéma.

## Environnements

- **Développement** : Docker Compose, PostgreSQL et Mailpit locaux.
- **Recette** : base et secrets séparés, accès protégé, `noindex`.
- **Production** : déploiement automatisé, sauvegardes externes, HTTPS et supervision.

Le site public existant n’est modifié qu’après validation de la recette et test du plan de retour arrière.
