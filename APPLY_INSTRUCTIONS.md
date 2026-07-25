# UI Foundation anwenden

Branch: `feature/navigation-dashboard`

Inhalt in das Repository-Stammverzeichnis kopieren.

```powershell
docker compose run --rm node npm run format
docker compose run --rm node npm run check
docker compose --profile test run --rm app-test composer quality
```

Commit:

```powershell
git add .
git commit -m "feat: establish reusable registry UI foundation"
git push
```
