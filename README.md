# Healthcare Node Registry 0.1.0

Technisches Grundgerüst der On-Premise-Registry für Healthcare-IT-Systeme und Kommunikationsbeziehungen.

## Enthalten

- Laravel 13 / PHP 8.4
- Vue 3 / TypeScript / Inertia 3
- Tailwind CSS 4
- PostgreSQL 18
- Docker Compose
- Nginx + PHP-FPM
- sessionbasierte Anmeldung
- native RBAC-Grundlage
- modularer Monolith
- Health Endpoint
- strukturierte Logs
- GitHub Actions für Backend, Frontend und Container
- Dashboard-Basislayout gemäß Enterprise-Admin-Richtung

## Noch nicht enthalten

- produktive Registry-Fachmodule
- DICOM- oder HL7-Kommunikation
- Monitoring und Discovery
- Dokumenten-Uploads
- vollständige Benutzerverwaltung
- MFA oder externe Verzeichnisanbindung

## Lokaler Start

Voraussetzungen: Docker Engine und Docker Compose Plugin.

```bash
cp .env.example .env
docker compose build
docker compose run --rm app composer install --no-interaction
docker compose run --rm node npm ci
docker compose run --rm app php artisan key:generate
docker compose up -d db
docker compose run --rm app php artisan migrate --seed
docker compose run --rm node npm run build
docker compose up -d
```

Anwendung: `http://localhost:8080`

Synthetischer Entwicklungszugang:

- E-Mail: `admin@example.test`
- Passwort: `ChangeMe-Development-Only!`

Der Seeder ist nur für lokale Entwicklung gedacht. Produktive Installationen müssen ein eigenes initiales Administratorkonto über einen kontrollierten Setup-Prozess anlegen.

## Entwicklung

```bash
docker compose up -d db app web
docker compose run --rm node npm run dev -- --host 0.0.0.0
```

## Tests

```bash
docker compose run --rm app composer test
docker compose run --rm node npm run check
```

## Modulstruktur

```text
app/Modules/
├── Identity/
├── Organizations/
├── Assets/
├── Endpoints/
├── Dicom/
├── Connections/
├── Topology/
├── Documents/
├── Taxonomy/
├── Audit/
├── ImportExport/
└── Administration/
```

Jedes Modul erhält bei fachlicher Implementierung eigene Application-, Domain-, Infrastructure- und Presentation-Bereiche. Das Grundgerüst führt noch keine unnötigen Abstraktionen ein.

## Sicherheit

- keine echten Patientendaten in Seeds, Tests oder Logs
- Debug standardmäßig deaktiviert
- Session-Cookies HttpOnly und SameSite=Lax
- serverseitige Autorisierung
- keine verpflichtende Telemetrie
- keine externen CDN-Abhängigkeiten
- Datenbank nicht auf Host-Port veröffentlicht
