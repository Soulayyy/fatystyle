# Dépendances et licences — v1.0.0

Les versions exactes et les licences transitives sont verrouillées dans `cms/composer.lock`. L’audit du 17 juillet 2026 ne signale aucune vulnérabilité connue.

Principales dépendances serveur : Laravel 13 (MIT), Filament 5 (MIT), Livewire 4 (MIT), Spatie Permission 8 (MIT), Spatie Activitylog 5 (MIT) et PHPUnit 12 pour les tests (BSD-3-Clause).

Outils frontend du CMS : Vite 8 (MIT), Tailwind CSS 4 (MIT), plugin Laravel Vite 3 (MIT) et Concurrently 9 (MIT). Le site public ne charge aucun composant Filament.

Services externes actuellement utilisés ou référencés : Google Fonts, Google Maps/Avis, Facebook, Instagram, WhatsApp et FormSubmit. Leur maintien en production doit être validé au titre de la confidentialité et des conditions de service.
