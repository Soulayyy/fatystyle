# Rapport de livraison Faty Style — v1.0.0

Date de recette : 17 juillet 2026  
Périmètre : site public statique, CMS Laravel/Filament, PostgreSQL, publication, formulaire et exploitation VPS.

## 1. Résumé exécutif

Le site public est stable sur la recette et l’administration couvre le périmètre livré. Le contenu et le design validés n’ont pas été modifiés pendant cette passe. Les migrations, permissions, imports, médias, publications, sauvegardes et routes sensibles ont été testés sur un environnement isolé puis contrôlés sur le VPS.

Décision : **livrable avec réserves pour la recette actuelle**. Le passage en production sur le domaine définitif reste conditionné aux décisions listées plus bas, en particulier le SMTP, HTTPS, la politique de confidentialité et la sauvegarde hors VPS.

## 2. Tests exécutés

| Contrôle | Résultat |
|---|---|
| Suite Laravel sur SQLite | 33 tests, 162 assertions, succès |
| Suite Laravel sur PostgreSQL 18 | 33 tests, 162 assertions, succès |
| Migrations PostgreSQL depuis une base vide | 13 migrations, succès |
| Laravel Pint | 140 fichiers, succès |
| Composer audit sur le lock | aucune vulnérabilité connue |
| Audit HTML/SEO/liens/ressources | 6 pages, aucune anomalie |
| Chrome mobile, tablette et desktop | 18 parcours, aucun débordement, image cassée ou erreur console |
| Galerie Enfants | 20 photos ; navigation 1/20 vers 2/20 et fermeture Échap validées |
| Import historique `--dry-run` | 5 pages, 7 blocs, 6 catégories, 348 références média |
| Import historique réel rejoué deux fois | idempotent ; 6 catégories, 339 associations photo, 344 médias dédupliqués |
| Variantes médias | 399 médias, 1 663 variantes, aucun média sans variante |
| Régénération forcée | une image régénérée avec succès sans perte de l’original |
| Sauvegarde PostgreSQL | créée et contrôlée par SHA-256 |
| Sauvegarde complète | 231 435 960 octets, créée et contrôlée par SHA-256 |
| Restauration PostgreSQL isolée | 32 tables et 399 médias retrouvés |
| Planificateur | cron actif chaque minute ; commandes programmées sans erreur |
| Formulaire via `/cms-api/contact` | POST 200 JSON, demande enregistrée puis donnée de test supprimée |
| GET sur le formulaire | refusé ; route CMS directe limitée à POST |
| Secrets Git | aucun secret réel ou `.env` de production suivi |

Le build Docker Compose et les tests physiques Safari/iOS n’ont pas été exécutés sur ce VPS, Docker et ces plateformes n’y étant pas disponibles. Le fonctionnement applicatif équivalent a été validé nativement sous PHP 8.5, PostgreSQL 18, Nginx et Chrome.

## 3. Anomalies corrigées

| ID | Gravité | Zone | Anomalie et risque | Correction et validation |
|---|---|---|---|---|
| FS-001 | critique | Publication | Les releases créées par `root` étaient en `0750`, rendant JSON et galerie illisibles par Nginx. | Dossiers publics forcés en `0755`, test sous umask restrictif et galerie 339 photos validée. |
| FS-002 | critique | Authentification | Le seeder bootstrap pouvait réinitialiser mot de passe, état et rôle d’un compte existant. | Création uniquement au premier passage ; test prouvant la conservation du mot de passe et du rôle choisis. |
| FS-003 | majeur | Permissions | L’administrateur de contenu détenait une permission de suppression de sauvegarde non nécessaire. | Permission retirée ; matrice des cinq rôles testée. |
| FS-004 | majeur | Exports | Les routes sensibles dépendaient uniquement d’un contrôle dans le contrôleur. | Ajout d’un second contrôle `can:` sur prévisualisation, exports et téléchargement de sauvegarde ; contournement direct testé. |
| FS-005 | moyen | CMS | La racine du CMS exposait encore la page Laravel de démonstration. | Redirection vers la connexion Filament et suppression du template obsolète. |
| FS-006 | majeur | Médias | Nginx limitait les uploads à 13 Mo alors que le CMS en autorise 20. | Limite portée à 21 Mo dans les configurations CMS versionnées. |
| FS-007 | majeur | Recette | La recette par IP n’imposait pas explicitement `noindex` et la compression des ressources était incomplète. | Configuration Nginx de recette versionnée : `X-Robots-Tag`, headers de sécurité, gzip et cache désactivé. |
| FS-008 | faible | SEO | Dates du sitemap antérieures à la livraison. | Dates techniques actualisées sans changer les URLs. |
| FS-009 | moyen | Documentation | Les procédures et l’état réel du déploiement n’étaient pas consolidés. | Rapport, matrice, inventaire et procédure d’exploitation ajoutés ; README actualisés. |

## 4. Risques restants

### Critique

Aucun défaut applicatif critique reproduit sur la recette après corrections.

### Majeur

- La recette utilise encore FormSubmit. Les données de contact quittent donc l’infrastructure Faty Style après leur enregistrement dans le CMS.
- L’administration est exposée en HTTP sur une IP. Les cookies `Secure` ne peuvent pas être activés avant HTTPS.
- Les sauvegardes restent sur le VPS principal ; une panne totale du serveur pourrait affecter application et sauvegardes.
- Aucune politique de confidentialité validée n’est actuellement publiée.

### Moyen

- Les polices publiques sont encore chargées depuis Google Fonts.
- Le domaine final et les redirections HTTP vers HTTPS ne sont pas actifs sur cette recette.
- Le build Docker Compose n’a pas été rejoué faute de moteur Docker sur l’hôte de recette ; la configuration a été relue et le socle réel testé nativement.
- Les parcours lecteur d’écran complets et les tests sur appareils Safari/iOS physiques restent à effectuer lors de la recette client.

### Faible

- Le nom historique `savoir-faire.html` reste utilisé pour la page « Créations » afin de ne casser aucun lien. Toute évolution devra passer par une redirection 301 validée.

## 5. Décisions client nécessaires

1. Fournir et valider un SMTP afin de passer à `CMS_CONTACT_DELIVERY=mail` et supprimer FormSubmit du HTML public.
2. Confirmer le domaine final et autoriser HTTPS, HSTS et `SESSION_SECURE_COOKIE=true`.
3. Valider le texte de politique de confidentialité, les sous-traitants et la durée de conservation des contacts (36 mois actuellement configurés).
4. Choisir si les polices Google doivent être auto-hébergées.
5. Choisir un stockage externe chiffré pour les sauvegardes et sa rétention.
6. Confirmer les éventuels autres services tiers avant production.

## 6. Déploiement

- Site public de recette : `http://178.105.180.143:8082/`
- Administration de recette : `http://178.105.180.143:8083/admin`
- Version : `v1.0.0`
- Commit : commit final portant le tag `v1.0.0`
- Migrations : aucune migration en attente après déploiement
- Sauvegarde de sécurité : base et médias créés avant la release finale
- Rollback applicatif : rebascule atomique du lien `/var/www/fatystyle-cms/current` vers la release précédente
- Rollback contenu : action « Restaurer » sur une release publiée, réservée aux rôles autorisés

## 7. Documentation livrée

- README racine et README CMS ;
- installation et architecture d’administration ;
- cahier des charges administration fourni par le client ;
- chiffrage administration fourni par le client ;
- matrice des droits ;
- procédures d’exploitation, sauvegarde, restauration et rollback ;
- inventaire de livraison ;
- présent rapport de tests, sécurité et recette.

## 8. Preuves

Les preuves reproductibles sont constituées par la suite de tests, le tag Git, les checksums des sauvegardes, les journaux du planificateur et les captures placées dans `docs/evidence/`. Les données techniques de test du formulaire ont été supprimées après validation.
