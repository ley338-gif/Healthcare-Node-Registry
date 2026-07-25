# Clean Install Validation 0.1.1

```bash
cp .env.example .env
docker compose build --no-cache
docker compose run --rm app composer install --no-interaction
docker compose run --rm node npm ci
docker compose run --rm app php artisan key:generate
docker compose up -d db
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan registry:create-admin
docker compose run --rm node npm run build
docker compose up -d
docker compose exec app php artisan registry:doctor
```

## Akzeptanzkriterien

- Container laufen stabil
- Datenbank ist healthy
- Doctor-Command erfolgreich
- `/up` liefert HTTP 200
- Login und Logout funktionieren
- Daten bleiben nach Neustart erhalten
- kein Entwicklungskonto in Production
- keine Secrets in Logs oder Git
- CI erfolgreich

Commit-SHA, Docker-Versionen, Dauer, Ergebnis und Abweichungen dokumentieren.
