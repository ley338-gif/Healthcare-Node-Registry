# Healthcare Node Registry

Healthcare Node Registry (HNR) ist eine proprietäre On-Premises-Webanwendung zur strukturierten Verwaltung von Healthcare-IT-Systemen, DICOM-Knoten und deren Kommunikationsbeziehungen. Sie bündelt technische Stammdaten, Organisationszuordnung, Betriebsdokumentation, Dokumente, Diagnoseergebnisse und Auditinformationen in einer zentralen Registry.

Die Anwendung richtet sich an Healthcare-IT-Administration, PACS-/RIS-Verantwortliche, Systembetrieb, Informationssicherheit und technische Dienstleister. Sie verarbeitet Infrastruktur- und Konfigurationsdaten; sie ist kein PACS, kein Patientenakten-System und kein kontinuierliches Monitoring-System.

## Architektur und Entwicklungsstatus

HNR ist als modularer Monolith umgesetzt:

- Backend: PHP 8.4, Laravel 13, Inertia Laravel und sessionbasierte Authentifizierung
- Frontend: Vue 3, TypeScript, Inertia 3, Tailwind CSS 4 und Vite
- Datenbank: PostgreSQL 18
- Betrieb: Nginx 1.28 vor PHP-FPM, bereitgestellt mit Docker Compose
- DICOM-Werkzeuge: DCMTK im PHP-Container
- Speicherung: PostgreSQL für Fachdaten sowie ein privates Laravel-Dateisystem für Registry-Dokumente

Der aktuelle Stand ist eine aktiv entwickelte Anwendung. Die unten aufgeführten Funktionen sind implementiert und durch automatisierte Tests abgedeckt. Eine öffentliche REST-API, OpenAPI-/Swagger-Dokumentation, DICOM-TLS, MFA, LDAP/Active Directory und Redis sind derzeit nicht Bestandteil des Stacks. Seit dem Discovery-MVP gibt es einen `database`-gestützten Queue-Worker-Container für asynchrone Scan-Läufe (siehe unten).

## Features

- Dashboard mit Registry-Übersicht, letzten Änderungen, Diagnosekennzahlen und Aufgabenhinweisen
- Organisationsstruktur aus Organisationen, Standorten und Abteilungen
- Registry für Healthcare-IT-Systeme mit Zuordnung zur Organisationsstruktur und Archivierung
- Mehrere Netzwerkinterfaces je System mit primärer Schnittstelle und kompatiblen Alt-Feldern
- Gefilterter Excel-/PDF-Export der System- und DICOM-Knotenübersicht
- DICOM-Knoten mit AE Title, Host, Port, Rolle, Status und Verifikation
- DICOM-Verbindungen zwischen registrierten Knoten sowie grafische Netzwerkansicht
- Globale, berechtigungsgeprüfte Suche
- Strukturierte Betriebsdokumentation für Organisationen, Standorte, Abteilungen und Systeme
- Private Dokumentenablage mit Metadaten, Versionen, SHA-256-Prüfung, Duplikaterkennung, Archivierung und PDF-Vorschau
- Tägliche In-App-Erinnerungen und Dashboard-Übersicht für ablaufende Zertifikate, Lizenzen und Wartungsdokumente
- Datei-Allowlist und Signaturprüfung für PDF, PNG, JPEG, DOCX, XLSX, TXT und ZIP
- Sessionbasierter Login sowie Benutzer-, Rollen- und Berechtigungsverwaltung
- RBAC mit einer initialen Rolle `system-administrator` und serverseitigen Policies/Gates
- Audit-Arbeitsbereich mit Filtern, Detailansicht und CSV-Export
- Diagnose-Workspace mit Verlauf, Profilen und JSON-/CSV-Ergebnisexport
- Netzwerkprüfung und DICOM C-ECHO
- Modality-Worklist-C-FIND und PACS-Study-Root-C-FIND
- Kontrollierter C-STORE eines synthetischen Secondary-Capture-Objekts
- DICOM Capability-Matrix auf Basis der Association-Aushandlung
- DICOM-Dateianalyse mit `dcmdump`
- Öffentlicher, detailarmer Health-Endpunkt unter `/up`
- DICOM Discovery: geführter Wizard, asynchroner Netzwerk-Scan (Ping/Reverse-DNS, TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests), regelbasierte Klassifizierung mit Confidence-Score, Review-Queue mit Duplikaterkennung und Übernahme in die System-Registry (siehe `docs/Features/dicom-discovery.md`)
- Backup- und Restore-Skripte für PostgreSQL und privaten Dokumentenspeicher

Der Docker-Stack bindet ClamAV 1.5.3 über das interne ClamD-Netzwerk an. Uploads werden synchron gescannt; ein stündlicher Scheduler prüft offene Scans erneut. Ist der Scanner deaktiviert oder nicht erreichbar, werden Dateien mit `unavailable` privat gespeichert und Download sowie Vorschau bleiben fail-closed gesperrt.

## Verzeichnisstruktur

| Pfad | Inhalt |
| --- | --- |
| `app/` | Laravel-Controller, Requests, Models, Policies, Services, Konsolenbefehle und Modulbeschreibungen |
| `bootstrap/` | Laravel-Bootstrap und Providerregistrierung |
| `config/` | Anwendungs-, Datenbank-, Session-, Logging-, Diagnose- und Dokumentkonfiguration |
| `database/` | Migrationen, Factories und lokaler Development-Seeder |
| `docker/` | PHP-FPM-Image, Entrypoint, PHP-Konfiguration und Nginx-Konfiguration |
| `docs/` | Architektur-, Betriebs-, Sicherheits-, Entwickler-, Benutzer- und Fachdokumentation |
| `public/` | Web-Einstiegspunkt und generierter Frontend-Build |
| `resources/` | Vue-/TypeScript-Frontend, CSS und Blade-Shell |
| `routes/` | Web- und Konsolenrouten |
| `scripts/` | PowerShell-Skripte für Qualität, Tests, Backup und Restore |
| `specification/` | Produkt-, Scope-, UI- und Netzwerkreferenzen |
| `storage/` | Laufzeitdaten, Logs und private Dokumentablage; nicht versioniert |
| `tests/` | PHPUnit Feature- und Unit-Tests |

## Voraussetzungen

### Empfohlen: Docker

- Git
- Docker Engine mit Compose-Plugin (`docker compose`)
- PowerShell nur für die mitgelieferten `.ps1`-Skripte

PHP, Composer, PostgreSQL, Node.js und DCMTK müssen bei dieser Variante nicht auf dem Host installiert sein.

### Native Entwicklung ohne Docker

- PHP 8.4 mit mindestens den im Container aktivierten Erweiterungen `intl`, `opcache`, `pcntl`, `pdo_pgsql` und `zip`
- Composer 2
- PostgreSQL 18
- Node.js 24 und npm
- DCMTK für native DICOM-Diagnosen (`echoscu`, `findscu`, `img2dcm`, `storescu`, `dcmdump`)

Für einen reproduzierbaren Betrieb wird Docker empfohlen.

## Installation mit Docker

Die folgenden Befehle werden im Repository-Stamm ausgeführt.

1. Repository klonen und in das Verzeichnis wechseln:

   ```bash
   git clone <repository-url>
   cd Healthcare-Node-Registry
   ```

2. Lokale Konfiguration anlegen:

   ```bash
   cp .env.example .env
   ```

   Unter PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

   Vor einem produktionsnahen Start mindestens `DB_PASSWORD`, `POSTGRES_PASSWORD`, `APP_URL` und die Session-/TLS-Einstellungen prüfen. `DB_PASSWORD` und `POSTGRES_PASSWORD` müssen übereinstimmen.

3. PHP-Image bauen und Abhängigkeiten installieren:

   ```bash
   docker compose build app
   docker compose run --rm app composer install --no-interaction
   docker compose run --rm node npm ci
   ```

4. Laravel-Anwendungsschlüssel erzeugen:

   ```bash
   docker compose run --rm app php artisan key:generate
   ```

5. PostgreSQL starten und Migrationen ausführen:

   ```bash
   docker compose up -d db
   docker compose run --rm app php artisan migrate
   ```

6. Frontend für den Nginx-Betrieb bauen:

   ```bash
   docker compose run --rm node npm run build
   ```

7. Ersten Administrator interaktiv anlegen:

   ```bash
   docker compose run --rm app php artisan registry:create-admin
   ```

   Der Befehl fragt Name, E-Mail-Adresse und Passwort ab. Das Passwort muss mindestens 14 Zeichen sowie Groß- und Kleinbuchstaben, Zahlen und Sonderzeichen enthalten. Es wird weder ausgegeben noch protokolliert. Der Befehl verweigert ein zweites initiales Administratorkonto.

8. Anwendung, Worker, Scheduler und Malware-Scanner starten und prüfen:

   ```bash
   docker compose up -d app worker scheduler web clamav
   docker compose exec app php artisan registry:doctor
   docker compose ps
   ```

9. Im Browser öffnen:

   - Anwendung und Login: `http://localhost:8080`
   - Health-Endpunkt: `http://localhost:8080/up`

### Development-Seeder und erster Login

Für ausschließlich lokale Entwicklung kann statt der interaktiven Admin-Erstellung der Seeder verwendet werden:

```bash
docker compose run --rm app php artisan migrate --seed
```

Bei `APP_ENV=local` erzeugt er:

| Feld | Wert |
| --- | --- |
| Name | `Synthetic Development Admin` |
| E-Mail | `admin@example.test` |
| Passwort | `ChangeMe-Development-Only!` |
| Rolle | `system-administrator` |
| Login | `http://localhost:8080/login` |

Dieser Zugang ist synthetisch und darf nicht in produktiven oder gemeinsam erreichbaren Installationen verwendet werden. Das Passwort muss bei jeder längerlebigen Entwicklungsinstallation sofort geändert werden. In anderen Umgebungen legt der Seeder nur die Systemadministratorrolle und deren Berechtigungen an, aber keinen Benutzer.

### Discovery: Worker, DCMTK und ersten Testscan prüfen

1. Worker-Status prüfen:

   ```bash
   docker compose ps worker
   docker compose logs -f worker
   ```

   Der Worker verarbeitet die Queue `discovery` (`php artisan queue:work database --queue=discovery,default`).

2. DCMTK im Container prüfen:

   ```bash
   docker compose exec app which echoscu
   docker compose exec app which ping
   ```

3. Erlaubten Netzbereich prüfen: Unter Einstellungen > Discovery (`/settings/discovery`, Berechtigung `discovery.manage`) ist standardmäßig `192.168.0.0/16`, `172.16.0.0/12` und `10.0.0.0/8` freigegeben (Seed-Daten). Ohne mindestens einen aktiven Eintrag kann kein Lauf gestartet werden.

4. Testscan starten: unter „Discovery" → „Neuer Discovery-Lauf" einen kleinen, tatsächlich erreichbaren Bereich wählen (z. B. das eigene Docker-Subnetz oder ein einzelnes bekanntes Testgerät), Schritt 5 mit der Sicherheitsbestätigung abschließen. Der Fortschritt ist auf der Laufseite sichtbar; Ergebnisse erscheinen dort in der Review-Queue, sobald der `worker` sie verarbeitet hat.

## Docker-Stack

Es gibt genau eine Compose-Datei: `docker-compose.yml`.

| Dienst | Zweck | Host-Port | Profil | Restart Policy |
| --- | --- | --- | --- | --- |
| `web` | Nginx, statische Assets und Weiterleitung an PHP-FPM | `8080` | Standard | `unless-stopped` |
| `app` | Laravel auf PHP-FPM, Port 9000 nur im Compose-Netz | keiner | Standard | `unless-stopped` |
| `worker` | Queue-Worker (`php artisan queue:work database --queue=discovery,default`) für asynchrone Discovery-Scan-Läufe | keiner | Standard | `unless-stopped` |
| `scheduler` | Laravel-Scheduler für den stündlichen Malware-Rescan und die tägliche Dokument-Ablaufprüfung | keiner | Standard | `unless-stopped` |
| `clamav` | ClamAV 1.5.3 mit ClamD und FreshClam | keiner | Standard | `unless-stopped` |
| `db` | PostgreSQL 18.4 | keiner | Standard | `unless-stopped` |
| `node` | npm-/Vite-Werkzeugcontainer | keiner | `tools` | keine |
| `app-test` | isolierte Backend-Testausführung | keiner | `test` | keine |
| `db-test` | isolierte PostgreSQL-Testdatenbank | keiner | `test` | keine |

Nur Nginx wird auf dem Host veröffentlicht. PostgreSQL, PHP-FPM und ClamD sind nicht direkt vom Host erreichbar.

Netzwerke:

- `frontend`: Kommunikation zwischen `web` und `app`
- `backend`: internes Netz zwischen `app`, `worker`, `scheduler`, `db` und `clamav`
- `clamav_updates`: ausschließlich für Signaturaktualisierungen des ClamAV-Dienstes
- `test_backend`: internes Netz zwischen `app-test` und `db-test`

Persistente Volumes:

- `postgres_data`: produktive/lokale PostgreSQL-Daten
- `app_storage`: Laravel-Laufzeitdaten und private Registry-Dokumente
- `postgres_test_data`: Daten des isolierten Testprofils
- `app_test_storage`: Storage des isolierten Testprofils
- `clamav_data`: persistente ClamAV-Signaturdatenbank

`web` läuft mit schreibgeschütztem Root-Dateisystem und temporären Dateisystemen für Nginx-Cache und PID-Dateien. `app`, `worker`, `web` und `db` verwenden `no-new-privileges`. Der Entrypoint (`docker/php/entrypoint.sh`) startet `php-fpm` weiterhin als root (üblich, da FPM Worker-Prozesse selbst per Pool-Konfiguration auf `www-data` absenkt), führt alle anderen Kommandos - insbesondere den Queue-Worker - jedoch bewusst als `www-data` aus.

`worker` teilt sich `frontend`- und `backend`-Netz mit `app`, da Discovery-Scans Netzwerkverkehr zu Zielsystemen außerhalb des Compose-Netzes erzeugen müssen (`backend` ist `internal` und dafür nicht ausreichend).

Stoppen ohne Datenverlust:

```bash
docker compose down
```

`docker compose down -v` löscht die benannten Volumes und damit Datenbank- und Dokumentdaten. Dieser Befehl darf nur für bewusst verworfene Entwicklungsinstallationen verwendet werden.

## Standard-Ports

| Komponente | Port | Erreichbarkeit |
| --- | --- | --- |
| HNR über Nginx | `8080/tcp` | Host und Browser |
| PHP-FPM | `9000/tcp` | nur Compose-Netz `frontend` |
| PostgreSQL | `5432/tcp` | nur internes Compose-Netz `backend` |
| PostgreSQL Test | `5432/tcp` | nur internes Compose-Netz `test_backend` |
| Vite Development Server | `5173/tcp` standardmäßig | nur bei nativem `npm run dev`; im Compose-Dienst nicht veröffentlicht |

DICOM-Zielports werden pro registriertem Knoten gespeichert und sind keine eingehenden Ports der Anwendung.

## Konfiguration

`.env.example` enthält sichere Entwicklungsbeispiele. Echte `.env`-Dateien werden durch `.gitignore` ausgeschlossen.

### Zentrale Variablen

| Variable | Bedeutung | Beispiel/Standard |
| --- | --- | --- |
| `APP_NAME` | Anzeigename der Anwendung | `Healthcare Node Registry` |
| `APP_ENV` | Laravel-Umgebung; steuert unter anderem den Development-Seeder | `local` |
| `APP_KEY` | Laravel-Schlüssel für Verschlüsselung; mit `key:generate` erzeugen | kein Vorgabewert |
| `APP_DEBUG` | Laravel-Debugausgabe | `false` |
| `APP_URL` | Externe Basis-URL | `http://localhost:8080` |
| `APP_LOCALE` | In `.env.example` vorhanden, aktuell jedoch nicht ausgewertet; `de` ist in `config/app.php` fest gesetzt | `de` |
| `APP_FALLBACK_LOCALE` | In `.env.example` vorhanden, aktuell jedoch nicht ausgewertet; `en` ist in `config/app.php` fest gesetzt | `en` |
| `LOG_CHANNEL` | Laravel-Logkanal | `stderr` |
| `LOG_LEVEL` | Mindest-Loglevel | `info` |
| `DB_CONNECTION` | Datenbanktreiber | `pgsql` |
| `DB_HOST` | PostgreSQL-Host | `db` |
| `DB_PORT` | PostgreSQL-Port | `5432` |
| `DB_DATABASE` | Datenbankname | `healthcare_node_registry` |
| `DB_USERNAME` | Datenbankbenutzer | `registry` |
| `DB_PASSWORD` | Passwort der Laravel-Datenbankverbindung | Entwicklungswert in `.env.example`; produktiv ersetzen |
| `DB_SSLMODE` | PostgreSQL-SSL-Modus | `prefer` |
| `POSTGRES_DB` | Initiale Datenbank des Compose-Containers | muss zu `DB_DATABASE` passen |
| `POSTGRES_USER` | Initialer PostgreSQL-Benutzer | muss zu `DB_USERNAME` passen |
| `POSTGRES_PASSWORD` | Initiales PostgreSQL-Passwort | muss zu `DB_PASSWORD` passen |
| `SESSION_DRIVER` | Session-Speicher | `database` |
| `SESSION_LIFETIME` | Session-Laufzeit in Minuten | `120` |
| `SESSION_ENCRYPT` | In `.env.example` vorhanden, aktuell jedoch nicht ausgewertet; Sessions werden in `config/session.php` immer verschlüsselt | `true` |
| `SESSION_SECURE_COOKIE` | Cookie nur über HTTPS senden | lokal `false`, mit HTTPS `true` |
| `CACHE_STORE` | Laravel-Cache | `database` |
| `QUEUE_CONNECTION` | Laravel-Queue-Backend; verarbeitet vom `worker`-Dienst (`database`, keine Redis-Abhängigkeit) | `database` |
| `FILESYSTEM_DISK` | Standard-Dateisystem | `local` |
| `REGISTRY_DOCUMENT_EXPIRY_WARNING_DAYS` | Vorlauf für Ablaufwarnungen | `60` |
| `REGISTRY_DOCUMENT_MALWARE_SCANNER_ENABLED` | ClamAV-Adapter aktivieren; bei `false` wird sicher auf `UnavailableMalwareScanner` zurückgefallen | `true` |
| `REGISTRY_DOCUMENT_MALWARE_SCANNER_HOST` | ClamD-Hostname im internen Netz | `clamav` |
| `REGISTRY_DOCUMENT_MALWARE_SCANNER_PORT` | ClamD-TCP-Port | `3310` |
| `REGISTRY_DOCUMENT_MALWARE_SCANNER_CONNECT_TIMEOUT` | Verbindungs-Timeout in Sekunden | `2` |
| `REGISTRY_DOCUMENT_MALWARE_SCANNER_READ_TIMEOUT` | Scan-Timeout in Sekunden | `30` |
| `MAIL_MAILER` | Mail-Transport | `log` |
| `MAIL_FROM_ADDRESS` | Absenderadresse | `noreply@example.test` |
| `MAIL_FROM_NAME` | Absendername | `Healthcare Node Registry` |

Zusätzlich unterstützt die Implementierung bei Bedarf `DIAGNOSTIC_NETWORK_TIMEOUT` (1 bis 10 Sekunden, Standard 5), `REGISTRY_DOCUMENT_DISK` (Standard `registry_documents`) und `REGISTRY_DOCUMENT_MAX_UPLOAD_KB` (Standard 25 MiB). Nginx begrenzt Requests derzeit auf 10 MiB; ohne Anpassung von `docker/nginx/default.conf` ist daher das kleinere Nginx-Limit wirksam.

### Discovery-Variablen

| Variable | Bedeutung | Standard |
| --- | --- | --- |
| `DISCOVERY_MAX_RANGE_SIZE` | Maximale Anzahl IPv4-Adressen je Discovery-Lauf | `1024` |
| `DISCOVERY_LARGE_RANGE_WARNING_THRESHOLD` | Ab dieser Adressanzahl warnt der Wizard | `256` |
| `DISCOVERY_DEFAULT_CALLING_AE` | Standard-Calling-AE-Titel für Discovery-Scans | `HNR_DISCOVERY` |
| `DISCOVERY_MAX_PARALLEL_HOSTS` | Obergrenze für gleichzeitig verarbeitete Hosts je Lauf | `16` |
| `DISCOVERY_MAX_AE_ATTEMPTS_PER_PORT` | Maximale AE-Titel-Versuche je Host und Port | `5` |
| `DISCOVERY_PING_TIMEOUT` / `DISCOVERY_TCP_TIMEOUT` / `DISCOVERY_DICOM_ECHO_TIMEOUT` | Timeouts in Sekunden je Prüfungsart | `2` / `2` / `5` |
| `DISCOVERY_PORT_SCAN_BATCH_SIZE` | Interne Batchgröße für parallele Portprüfungen | `12` |

Erlaubte Scan-Netzbereiche sind keine Umgebungsvariable, sondern werden als Datensätze (`discovery_allowed_networks`) unter Einstellungen > Discovery verwaltet; die Seed-Daten legen initial die drei RFC1918-Bereiche an.

Laravel unterstützt darüber hinaus optionale Verbindungs-, Session-, Cache-, Queue- und Mailvariablen aus den Dateien unter `config/`. Für den bereitgestellten Compose-Stack genügen die Variablen aus `.env.example`.

## Native lokale Entwicklung

Nach Installation der nativen Voraussetzungen:

```bash
cp .env.example .env
composer install
npm ci
php artisan key:generate
php artisan migrate
php artisan registry:create-admin
npm run build
php artisan serve
```

Für eine native PostgreSQL-Instanz müssen insbesondere `DB_HOST`, Datenbankname, Benutzer und Passwort in `.env` angepasst werden; `DB_HOST=db` funktioniert nur im Compose-Netz.
Außerdem sollte `APP_URL` für den unten gezeigten Artisan-Server auf `http://127.0.0.1:8000` gesetzt werden.

Backend-Server: `http://127.0.0.1:8000`. Für Hot Reload in einem zweiten Terminal:

```bash
npm run dev
```

Der Vite-Development-Server verwendet standardmäßig Port 5173. Der Compose-Dienst `node` veröffentlicht diesen Port nicht und wird deshalb im dokumentierten Docker-Betrieb für Installations-, Build- und Prüfkommandos verwendet.

## Entwicklung und Qualitätssicherung

Backend-Kommandos im Docker-Stack:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:status
docker compose exec app php artisan registry:doctor
docker compose exec app php artisan registry:about
```

Frontend:

```bash
docker compose run --rm node npm run lint:check
docker compose run --rm node npm run format:check
docker compose run --rm node npm run types:check
docker compose run --rm node npm run test:unit
docker compose run --rm node npm run build
docker compose run --rm node npm run check
```

Isolierte Backend-Tests und Qualitätsprüfung:

```bash
docker compose --profile test run --rm app-test composer test
docker compose --profile test run --rm app-test composer lint:check
docker compose --profile test run --rm app-test composer analyse
docker compose --profile test run --rm app-test composer quality
```

Unter PowerShell stehen zusätzlich zur Verfügung:

```powershell
.\scripts\test.ps1
.\scripts\test.ps1 -Filter AuthenticationTest
.\scripts\quality.ps1
```

Backend-Tests sollen nicht gegen den normalen `app`-/`db`-Stack ausgeführt werden, sondern gegen das isolierte Testprofil.

## Datenbank, Migrationen und Seeds

Die Anwendung verwendet ausschließlich PostgreSQL. Migrationen verwalten Benutzer, Sessions, Cache, Jobs, persistente In-App-Benachrichtigungen, RBAC, Security Events, Organisationsstruktur, Systeme, DICOM-Knoten und -Verbindungen, Diagnoseverläufe und -profile sowie Registry-Dokumentation und Dokumentversionen.

Migrationen:

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:status
```

Der einzige Seeder ist `DatabaseSeeder`. Er stellt immer die Rolle `system-administrator` und deren Berechtigungen sicher. Nur in `APP_ENV=local` erzeugt er zusätzlich den oben dokumentierten synthetischen Entwicklungsadministrator.

```bash
docker compose exec app php artisan db:seed
```

### Backup

Bei laufenden Diensten `app` und `db`:

```powershell
.\scripts\backup.ps1
```

Optional kann mit `-OutputDirectory` ein anderes Ziel gewählt werden. Das Skript sichert PostgreSQL als Custom-Format-Dump, `storage/app/private` als TAR.GZ und erzeugt ein Manifest mit SHA-256-Prüfsummen. Es verschlüsselt das Backup nicht automatisch.

### Restore

Ein Restore ist destruktiv und erfordert die explizite Bestätigung:

```powershell
.\scripts\restore.ps1 -BackupDirectory .\backups\registry-YYYYMMDD-HHMMSS -ConfirmRestore
```

Das Skript prüft die im Manifest gespeicherten SHA-256-Werte, stoppt während des Restores den Webdienst, stellt Datenbank und privaten Storage wieder her, leert Laravel-Caches und führt anschließend `registry:doctor` aus.

## API

Es gibt derzeit keine freigegebene öffentliche REST-API, keine OpenAPI-Spezifikation und keine Swagger-Oberfläche. Die vorhandenen HTTP-Routen bilden die Inertia-Webanwendung ab und benötigen – mit Ausnahme von `/login` und `/up` – eine authentifizierte Sitzung. Diagnoseexporte sind als berechtigungsgeprüfte JSON-/CSV-Downloads verfügbar; der Auditexport unterstützt CSV.

## Sicherheit

- Produktive Passwörter und `APP_KEY` niemals aus `.env.example` übernehmen oder versionieren.
- Den Development-Administrator nicht produktiv verwenden; initiale Administratoren mit `registry:create-admin` anlegen.
- `APP_DEBUG=false` beibehalten.
- Für produktiven Betrieb HTTPS an einem vorgeschalteten Reverse Proxy terminieren, `APP_URL` auf die HTTPS-URL setzen und `SESSION_SECURE_COOKIE=true` verwenden. Der mitgelieferte Nginx-Dienst terminiert selbst kein TLS.
- PostgreSQL und PHP-FPM nicht zusätzlich auf Host-Ports veröffentlichen.
- Ausgehenden Netzwerkzugriff des App-Containers auf ausdrücklich freigegebene DICOM-Ziele begrenzen.
- DICOM-Diagnosen verwenden aktuell kein TLS; `tls_enabled` ist für diese Runner nur Registry-Metadatum.
- Diagnoseberechtigungen (`diagnostics.echo`, `.worklist`, `.query`, `.store`, `.move`, `.get`, `.mpps`, `.storage_commitment`, `.capability_matrix`) nach dem Minimalprinzip vergeben. Datenverändernde Tests verlangen zusätzlich eine ausdrückliche Bestätigung.
- ClamAV-Signaturupdates und den Zustand des `clamav`-Containers überwachen; ClamD-Port 3310 nicht außerhalb des internen Netzes veröffentlichen.
- Datenbank und `app_storage` gemeinsam, regelmäßig und verschlüsselt sichern; Restore-Tests durchführen.
- Benutzer, Rollen, Auditereignisse und Logs regelmäßig prüfen.
- Discovery-Scans ausschließlich gegen unter Einstellungen > Discovery freigegebene, tatsächlich autorisierte Netzbereiche starten (siehe `docs/Features/dicom-discovery.md`, `docs/Security/ThreatModel.md`).
- Keine Patientendaten, echten Zugangsdaten oder privaten Schlüssel in Tests, Logs oder Dokumentation speichern.

## Dokumentation

Die globale [DICOM-Verbindungsübersicht](docs/Features/global-dicom-connections.md) dokumentiert alle Kommunikationspfade zentral. Sie verwendet dieselben Verbindungsdatensätze wie die DICOM-Bereiche der Systemdetailseiten und unterstützt serverseitige Suche, Filterung, Sortierung und Pagination.

Der Einstieg in die Projektdokumentation befindet sich unter [`docs/README.md`](docs/README.md). Besonders relevant sind:

- [`docs/admin-guide/README.md`](docs/admin-guide/README.md) – Administration und Betrieb
- [`docs/developer-guide/README.md`](docs/developer-guide/README.md) – Entwicklung und Qualitätssicherung
- [`docs/Architecture/Overview.md`](docs/Architecture/Overview.md) – Architekturüberblick
- [`docs/Database/DataDictionary.md`](docs/Database/DataDictionary.md) – Datenmodell
- [`docs/Healthcare/DiagnosticTestWorkspace.md`](docs/Healthcare/DiagnosticTestWorkspace.md) – DICOM-Diagnosen und Einschränkungen
- [`docs/Security/AccessControl.md`](docs/Security/AccessControl.md) – Rollen und Autorisierung
- [`docs/Security/FileUploadSecurity.md`](docs/Security/FileUploadSecurity.md) – Dokumenten-Uploadschutz
- [`docs/Deployment/BackupRestore.md`](docs/Deployment/BackupRestore.md) – Backup und Restore
- [`docs/maintenance/repository-cleanup.md`](docs/maintenance/repository-cleanup.md) – letzter Repository-Cleanup
- [`docs/Features/dicom-discovery.md`](docs/Features/dicom-discovery.md) – DICOM Discovery: Workflow, Sicherheitsgrenzen, Klassifizierung
- [`docs/Decisions/ADR-0011-discovery-scanning.md`](docs/Decisions/ADR-0011-discovery-scanning.md) – Architekturentscheidungen der Scan-Engine
- [`docs/limitations.md`](docs/limitations.md) – bekannte Einschränkungen des Discovery-MVP
- [`docs/roadmap.md`](docs/roadmap.md) – dokumentierte, nicht umgesetzte Version-2-Funktionen

Dokumente mit Status `draft` sind Arbeitsstände. Bei Widersprüchen sind Code, Migrationen und Konfiguration des aktuellen Branches maßgeblich.

## Troubleshooting

### `docker compose` kann die Docker Engine nicht erreichen

Docker Desktop bzw. Docker Engine starten und mit `docker info` prüfen. Unter Windows muss Docker Desktop Zugriff auf das Projektlaufwerk haben.

### Port 8080 ist belegt

Den Host-Port links in `docker-compose.yml` ändern, zum Beispiel `8081:8080`, und `APP_URL` entsprechend anpassen. Danach `docker compose up -d web` erneut ausführen.

### PostgreSQL wird nicht gesund

```bash
docker compose ps
docker compose logs db
```

Prüfen, dass `POSTGRES_DB`, `POSTGRES_USER` und `POSTGRES_PASSWORD` zu `DB_DATABASE`, `DB_USERNAME` und `DB_PASSWORD` passen. Geänderte Initialwerte wirken nicht rückwirkend auf ein bereits initialisiertes `postgres_data`-Volume.

### Migrationen fehlen oder schlagen fehl

```bash
docker compose exec app php artisan migrate:status
docker compose exec app php artisan migrate
docker compose logs app
```

Produktive Daten nicht mit `migrate:fresh` löschen. Vor Migrationen ein geprüftes Backup erstellen.

### Login funktioniert nicht

Installation prüfen:

```bash
docker compose exec app php artisan registry:doctor
```

Fehlt der initiale Administrator, `registry:create-admin` ausführen. Existiert bereits ein Administrator, Passwörter über die berechtigte Benutzerverwaltung zurücksetzen; der Initialbefehl legt bewusst keinen zweiten Administrator an.

### Frontend ist leer oder das Manifest fehlt

```bash
docker compose run --rm node npm ci
docker compose run --rm node npm run build
docker compose exec app php artisan optimize:clear
```

Anschließend Browser-Cache leeren und `docker compose logs web app` prüfen.

### Healthcheck `/up` schlägt fehl

```bash
docker compose ps
docker compose logs web app db
docker compose exec app php artisan registry:doctor
```

`/up` bestätigt den Laravel-Health-Endpunkt; `registry:doctor` prüft zusätzlich Schlüssel, Datenbank, Migrationen, initialen Administrator, Verzeichnisrechte und Frontend-Manifest.

### DICOM-Test schlägt fehl

Host, Port, Called/Calling AE Title und Dienstkonfiguration des registrierten Knotens prüfen. Der App-Container benötigt DNS-Auflösung und ausgehenden TCP-Zugriff zum Ziel. Zielsystem-, Firewall- und Containerlogs gemeinsam auswerten. Details stehen im [Diagnose-Workspace](docs/Healthcare/DiagnosticTestWorkspace.md).

### Dokument kann nicht heruntergeladen oder angezeigt werden

Downloads und PDF-Vorschau erfordern den Malware-Scanstatus `clean`. `docker compose ps clamav` und `docker compose logs clamav` prüfen. Nach Wiederherstellung kann ein Rescan mit `docker compose exec app php artisan registry-documents:rescan` sofort angestoßen werden; andernfalls übernimmt ihn der stündliche Scheduler.

## Mitwirken

Der Beitragsprozess und die Qualitätsanforderungen stehen in [`CONTRIBUTING.md`](CONTRIBUTING.md). Änderungen benötigen Tests, aktualisierte Dokumentation, serverseitige Autorisierung und dürfen keine realen Gesundheitsdaten oder Secrets enthalten.

## Lizenz

Copyright © 2026. Alle Rechte vorbehalten. Das Repository ist derzeit für private Produktentwicklung vorgesehen; Details stehen in [`LICENSE.md`](LICENSE.md).
