# Installation Faty Style

## Hébergement classique

1. Copier tout le contenu du dépôt sur le serveur web.
2. Pointer le domaine vers le dossier contenant `index.html`.
3. Vérifier les droits de lecture des fichiers HTML, CSS, JS, JSON et images.
4. Ouvrir `index.html` depuis le domaine.
5. Tester le formulaire WhatsApp, le téléphone, l’email, Facebook et Instagram.
6. Tester sur mobile et desktop.

Aucun WordPress, aucune base MySQL et aucun build Node ne sont nécessaires.

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
}
```

Commandes utiles :

```bash
nginx -t
systemctl reload nginx
curl -I http://127.0.0.1:8082/
```
