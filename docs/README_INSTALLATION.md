# Installation Faty Style

## Hébergement classique

1. Copier le site public dans une release dédiée puis basculer atomiquement le lien `/var/www/fatystyle`.
2. Pointer le domaine vers le dossier contenant `index.html`.
3. Vérifier les droits de lecture des fichiers HTML, CSS, JS, JSON et images.
4. Ouvrir `index.html` depuis le domaine.
5. Tester le formulaire email, le téléphone, WhatsApp, Facebook et Instagram.
6. Vérifier les balises SEO, les données structurées et l’image Open Graph.
7. Tester sur mobile et desktop.

Aucun WordPress, aucune base MySQL et aucun build Node ne sont nécessaires pour le site public. L’administration utilise séparément PHP-FPM et PostgreSQL.

## Administration

L’ancienne interface statique a été retirée. Le CMS Laravel/Filament se trouve dans `cms/` et requiert PHP, PostgreSQL, un processus planifié et un stockage persistant. Suivre `cms/README.md` pour l’installation.

## Exemple Nginx sur port dédié

```nginx
server {
    listen 8082;
    server_name _;

    root /var/www/fatystyle;
    index index.html;

    # Uniquement sur la recette par IP. Retirer après validation du domaine final.
    add_header X-Robots-Tag "noindex, nofollow, noarchive" always;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff|woff2|ttf|json)$ {
        expires off;
        try_files $uri =404;
    }

    location = /cms-api/contact {
        proxy_pass http://127.0.0.1:8083/api/contact;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

Les configurations réellement utilisées pour la recette sont versionnées dans `deploy/nginx/`. La configuration de production définitive devra ajouter HTTPS, HSTS après validation, cookies `Secure`, le domaine final et une politique de cache de production.

Commandes utiles :

```bash
nginx -t
systemctl reload nginx
curl -I http://localhost:8082/
```
