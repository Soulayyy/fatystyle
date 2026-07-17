# Installation Faty Style

## Hébergement classique

1. Copier tout le contenu du dépôt sur le serveur web.
2. Pointer le domaine vers le dossier contenant `index.html`.
3. Vérifier les droits de lecture des fichiers HTML, CSS, JS, JSON et images.
4. Ouvrir `index.html` depuis le domaine.
5. Tester le formulaire email, le téléphone, WhatsApp, Facebook et Instagram.
6. Vérifier les balises SEO, les données structurées et l’image Open Graph.
7. Tester sur mobile et desktop.

Aucun WordPress, aucune base MySQL et aucun build Node ne sont nécessaires.

## Administration

L’ancienne interface statique a été retirée. Le CMS Laravel/Filament se trouve dans `cms/` et requiert PHP, PostgreSQL, un processus planifié et un stockage persistant. Suivre `cms/README.md` pour l’installation.

## Exemple Nginx sur port dédié

```nginx
server {
    listen 8082;
    server_name _;

    root /var/www/fatystyle;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff|woff2|ttf|json)$ {
        expires 30d;
        add_header Cache-Control "public";
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

Commandes utiles :

```bash
nginx -t
systemctl reload nginx
curl -I http://localhost:8082/
```
