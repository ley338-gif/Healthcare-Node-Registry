# Control-Center-Dashboard anwenden

Branch: `feature/navigation-dashboard`

Inhalt in das Repository kopieren und vorhandene Dateien ersetzen.

```powershell
docker compose run --rm node npm run format
docker compose run --rm node npm run check
docker compose --profile test run --rm app-test composer quality
```

Danach:

```powershell
git add .
git commit -m "feat: redesign dashboard as registry control center"
git push
```
