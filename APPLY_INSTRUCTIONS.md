# 0.1.2 Development Infrastructure anwenden

Dieses Overlay trennt Entwicklungs- und Testdatenbank vollständig.

## Vorher

Arbeitsstand sichern:

```powershell
git status
```

Das Overlay in das Repository-Stammverzeichnis kopieren und vorhandene Dateien ersetzen.

## Neue Containerstruktur

```text
app       -> Entwicklungsanwendung
db        -> healthcare_node_registry

app-test  -> isolierte Testanwendung
db-test   -> healthcare_node_registry_test
```

## Container neu erstellen

```powershell
docker compose down
docker compose --profile test build app app-test
docker compose up -d app web db
```

## Sicheren Testlauf starten

```powershell
docker compose --profile test run --rm app-test composer quality
```

Frontend:

```powershell
docker compose run --rm node npm run check
```

## Entwicklungsdatenbank prüfen

```powershell
docker compose exec app php artisan tinker
```

```php
config('database.connections.pgsql.database');
App\Models\User::count();
```

Erwartet:

```text
healthcare_node_registry
```

## Administrator anlegen

Nur im Entwicklungscontainer:

```powershell
docker compose exec app php artisan registry:create-admin
docker compose exec app php artisan registry:doctor
```

## Commit

```powershell
git status
git diff --check
git add .
git commit -m "fix: isolate development and test infrastructure"
git push
```
