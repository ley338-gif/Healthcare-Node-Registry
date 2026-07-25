# 0.1.1 anwenden

```powershell
git checkout main
git pull origin main
git checkout -b hardening/foundation-0.1.1
```

Den Inhalt dieses Ordners in das Repository-Stammverzeichnis kopieren und vorhandene Dateien ersetzen.

```powershell
docker compose exec app composer dump-autoload
docker compose exec app php artisan migrate
docker compose exec app composer lint
docker compose exec app composer quality
docker compose run --rm node npm run check
docker compose exec app php artisan registry:create-admin
docker compose exec app php artisan registry:doctor
```

Danach:

```powershell
git status
git diff --check
git add .
git commit -m "feat: harden foundation for 0.1.1"
git push -u origin HEAD
```
