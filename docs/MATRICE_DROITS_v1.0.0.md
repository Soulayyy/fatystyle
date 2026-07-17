# Matrice des droits — v1.0.0

Les permissions sont appliquées côté ressource et, pour les téléchargements ou exports, une seconde fois sur la route et dans le contrôleur.

| Rôle | Lecture | Création / modification | Validation / publication | Exports | Utilisateurs | Sauvegarde / restauration |
|---|---|---|---|---|---|---|
| Super-administrateur | complète | complète | oui | oui | oui | création, téléchargement et restauration |
| Administrateur de contenu | contenu | contenu | oui | oui selon module | non | lecture et export uniquement |
| Éditeur | contenu autorisé | oui | non | non | non | non |
| Validateur | contenu autorisé | validation | oui | selon permission de lecture/export | non | non |
| Lecteur / auditeur | lecture seule | non | non | oui | lecture seulement si explicitement accordée | export uniquement si explicitement accordé |

Invariants vérifiés automatiquement : compte inactif refusé, compte sans rôle refusé, éditeur sans publication, lecteur sans modification, administrateur de contenu sans gestion utilisateur ni restauration, 2FA obligatoire, récupération TOTP configurée et URLs d’export non contournables.
