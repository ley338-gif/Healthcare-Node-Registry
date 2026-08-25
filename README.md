# Healthcare Node Registry

Healthcare Node Registry (HNR) ist eine proprietäre On-Premises-Webanwendung zur strukturierten Verwaltung von Healthcare-IT-Systemen, DICOM-Knoten und deren Kommunikationsbeziehungen. Sie bündelt technische Stammdaten, Organisationszuordnung, Betriebsdokumentation, Dokumente, Diagnoseergebnisse und Auditinformationen in einer zentralen Registry.

Die Anwendung richtet sich an Healthcare-IT-Administration, PACS-/RIS-Verantwortliche, Systembetrieb, Informationssicherheit und technische Dienstleister. Sie verarbeitet Infrastruktur- und Konfigurationsdaten; sie ist kein PACS, kein Patientenakten-System und kein kontinuierliches Monitoring-System.

## Architektur und Entwicklungsstatus

HNR ist als modularer Monolith umgesetzt:

- Backend: PHP 8.4, Laravel 13, Inertia Laravel und sessionbasierte Authentifizierung
- Frontend: Vue 3, TypeScript, Inertia 3, Tailwind CSS 4 und Vite
- Datenbank: PostgreSQL 18
- Betrieb: Nginx 1.28 vor PHP-FPM, bereitgestellt mit Docker Compose
- DICOM-Werkzeuge: DCMTK sowie eine fest gepinnte Python-Laufzeit mit `pynetdicom` und `pydicom` im PHP-Container
- Speicherung: PostgreSQL für Fachdaten sowie ein privates Laravel-Dateisystem für Registry-Dokumente

Der aktuelle Stand ist eine aktiv entwickelte Anwendung. Die unten aufgeführten Funktionen sind implementiert und durch automatisierte Tests abgedeckt. Für asynchrone Discovery-Scan-Läufe gibt es einen `database`-gestützten Queue-Worker-Container.

## Features

- Dashboard mit Registry-Übersicht, letzten Änderungen, Diagnosekennzahlen und Aufgabenhinweisen
- Organisationsstruktur aus Organisationen, Standorten und Abteilungen
- Registry für Healthcare-IT-Systeme mit Zuordnung zur Organisationsstruktur und Archivierung
- Zentral administrierbare Systemtypen, Systemstatus und Hersteller mit stabilen Codes, Aktivstatus und Bestandsschutz
- Mehrere Netzwerkinterfaces je System mit primärer Schnittstelle und kompatiblen Alt-Feldern
- Gefilterter Excel-/PDF-Export der System- und DICOM-Knotenübersicht
- CSV-Import für Systeme und DICOM-Knoten mit Vorschau, Validierung und Duplikaterkennung
- DICOM-Knoten mit AE Title, Rolle, Status und Verifikation; Host und Port sind für reine SCU-Nodes optional
- DICOM-Verbindungen zwischen registrierten Knoten sowie grafische Netzwerkansicht
- Globale DICOM-Verbindungsübersicht und Firewall-/Portmatrix mit CSV-/PDF-Export
- Globale, berechtigungsgeprüfte Suche
- Strukturierte Betriebsdokumentation für Organisationen, Standorte, Abteilungen und Systeme
- Private Dokumentenablage mit Metadaten, Versionen, SHA-256-Prüfung, Duplikaterkennung, Archivierung und PDF-Vorschau
- Tägliche In-App-Erinnerungen und Dashboard-Übersicht für ablaufende Zertifikate, Lizenzen und Wartungsdokumente
- Datei-Allowlist und Signaturprüfung für PDF, PNG, JPEG, DOCX, XLSX, TXT und ZIP
- Sessionbasierter Login sowie Benutzer-, Rollen- und Berechtigungsverwaltung
- Verpflichtende TOTP-MFA für privilegierte lokale Konten mit einmaligen Wiederherstellungscodes, Replay-Schutz und administrativem beziehungsweise konsolenbasiertem Reset
- RBAC mit einer initialen Rolle `system-administrator` und serverseitigen Policies/Gates
- Audit-Arbeitsbereich mit Filtern, Detailansicht und CSV-Export
- Diagnose-Workspace mit Verlauf, Profilen und JSON-/CSV-Ergebnisexport
- Netzwerkprüfung und DICOM C-ECHO
- Modality-Worklist-C-FIND und PACS-Study-Root-C-FIND
- Kontrollierter C-STORE auswählbarer synthetischer Secondary-Capture-, CT- und MR-Objekte
- Autorisierte C-MOVE- und C-GET-Tests mit serverseitig konfigurierten synthetischen Study-UIDs
- Autorisierter MPPS-Test mit synthetischem N-CREATE und anschließendem N-SET auf `COMPLETED`
- Autorisiertes Storage Commitment mit synthetischem C-STORE, N-ACTION und korreliertem N-EVENT-REPORT
- DICOM Capability-Matrix auf Basis der Association-Aushandlung
- DICOM-Dateianalyse mit `dcmdump`
- Öffentlicher, detailarmer Health-Endpunkt unter `/up`
- DICOM Discovery: geführter Wizard, sofortige sowie täglich oder wöchentlich geplante asynchrone Netzwerk-Scans (Ping/Reverse-DNS, TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests), regelbasierte Klassifizierung mit Confidence-Score, Review-Queue mit Export, Vergleich abgeschlossener Scanstände, In-App-Hinweise bei neuen oder verschwundenen Hosts, Duplikaterkennung und Übernahme in die System-Registry (siehe `docs/Features/dicom-discovery.md`)
- Backup- und Restore-Skripte für PostgreSQL und privaten Dokumentenspeicher

Der Docker-Stack bindet ClamAV 1.5.3 über das interne ClamD-Netzwerk an. Uploads werden synchron gescannt; ein stündlicher Scheduler prüft offene Scans erneut. Ist der Scanner deaktiviert oder nicht erreichbar, werden Dateien mit `unavailable` privat gespeichert und Download sowie Vorschau bleiben fail-closed gesperrt.

## Nicht enthalten

Der aktuelle Stand enthält keine öffentliche REST-API, keine OpenAPI-/Swagger-Spezifikation, kein DICOM-TLS im Discovery-Scanner, keine DICOMweb- oder HL7/MLLP-Schnittstelle, keine OIDC- oder LDAP-/Active-Directory-Anbindung und kein Redis. Registrierte Diagnoseziele unterstützen dagegen DICOM-TLS über betreiberseitig bereitgestellte Zertifikatsprofile. Lokale privilegierte Konten verwenden TOTP-MFA; WebAuthn/Passkeys, SMS- und E-Mail-Codes sind nicht implementiert. Die Anwendung ist außerdem kein PACS, kein Patientenakten-System und kein kontinuierliches Monitoring- oder Alarmierungssystem.

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
- Docker Engine mit Compose-Plugin (`docker compose`); für die HTTPS-Überlagerung mindestens Compose 2.24.4 wegen `!override`
- PowerShell nur für die mitgelieferten `.ps1`-Skripte

PHP, Composer, PostgreSQL, Node.js und DCMTK müssen bei dieser Variante nicht auf dem Host installiert sein.

### Native Entwicklung ohne Docker

- PHP 8.4 mit mindestens den im Container aktivierten Erweiterungen `intl`, `opcache`, `pcntl`, `pdo_pgsql` und `zip`
- Composer 2
- PostgreSQL 18
- Node.js 24 und npm
- DCMTK für native DICOM-Diagnosen (`echoscu`, `findscu`, `movescu`, `getscu`, `storescu`, `dcmdump`, `dump2dcm`)
- Python 3 mit `venv`, `pynetdicom==3.0.4` und `pydicom==3.0.2` unter `/opt/registry-dicom` für MPPS und Storage Commitment

Für einen reproduzierbaren Betrieb wird Docker empfohlen.

## Installation mit Docker

Der folgende Standardablauf ist für lokale Entwicklung und Validierung vorgesehen und bindet den Arbeitsbaum in die Container ein. Für Pilot- und Release-Installationen ist der [unveränderliche Release-, Installations-, Upgrade- und Rollback-Prozess](docs/Deployment/ReleaseProcess.md) mit `docker-compose.release.yml` verbindlich.

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

   Beim ersten Login eines privilegierten Kontos führt die Anwendung verpflichtend durch die TOTP-Einrichtung. Den Einrichtungsschlüssel in einer kompatiblen Authenticator-App erfassen, den sechsstelligen Code bestätigen und die acht nur einmal angezeigten Wiederherstellungscodes getrennt vom Gerät sicher speichern.

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
   docker compose exec worker php artisan registry:worker-health
   docker compose logs -f worker
   ```

   Der Worker verarbeitet die Queue `discovery` (`php artisan queue:work database --queue=discovery,default`). Sein anwendungsspezifischer Docker-Healthcheck prüft einen bei jedem Queue-Loop aktualisierten Heartbeat sowie die Datenbankverbindung. Ein leerer Queue-Zustand ist gesund; ein veralteter oder ungültiger Heartbeat setzt den Container auf `unhealthy`.

2. DCMTK im Container prüfen:

   ```bash
   docker compose exec app which echoscu
   docker compose exec app which ping
   ```

3. Erlaubten Netzbereich prüfen: Unter Einstellungen > Discovery (`/settings/discovery`, Berechtigung `discovery.manage`) ist standardmäßig `192.168.0.0/16`, `172.16.0.0/12` und `10.0.0.0/8` freigegeben (Seed-Daten). Ohne mindestens einen aktiven Eintrag kann kein Lauf gestartet werden.

4. Testscan starten: unter „Discovery" → „Neuer Discovery-Lauf" einen kleinen, tatsächlich erreichbaren Bereich wählen (z. B. das eigene Docker-Subnetz oder ein einzelnes bekanntes Testgerät), Schritt 5 mit der Sicherheitsbestätigung abschließen. Dort kann der Lauf sofort gestartet oder täglich beziehungsweise wöchentlich geplant werden. Zeitpläne erscheinen auf dem Discovery-Dashboard; der Fortschritt ausgeführter Läufe ist auf der Laufseite sichtbar.

## Docker-Stack

`docker-compose.yml` definiert den lokalen Entwicklungs- und Validierungsstack. `docker-compose.release.yml` ersetzt die Quellcode-Bind-Mounts durch unveränderliche App- und Webimages. `docker-compose.https.yml` ergänzt beide Varianten optional um HTTPS; ohne diese Datei bleibt der lokale HTTP-Start auf Port 8080 unverändert.

| Dienst | Zweck | Host-Port | Profil | Restart Policy |
| --- | --- | --- | --- | --- |
| `web` | Nginx, statische Assets und Weiterleitung an PHP-FPM | `8080` | Standard | `unless-stopped` |
| `app` | Laravel auf PHP-FPM, Port 9000 nur im Compose-Netz | keiner | Standard | `unless-stopped` |
| `worker` | Queue-Worker (`php artisan queue:work database --queue=discovery,default`) für asynchrone Discovery-Scan-Läufe; Heartbeat- und Datenbank-Healthcheck | keiner | Standard | `unless-stopped` |
| `scheduler` | Laravel-Scheduler für Discovery-Zeitpläne, Malware-Rescans, Dokument-Ablaufprüfung und Audit-Aufbewahrung | keiner | Standard | `unless-stopped` |
| `clamav` | ClamAV 1.5.3 mit ClamD und FreshClam | keiner | Standard | `unless-stopped` |
| `db` | PostgreSQL 18.4 | keiner | Standard | `unless-stopped` |
| `node` | npm-/Vite-Werkzeugcontainer | keiner | `tools` | keine |
| `app-test` | isolierte Backend-Testausführung | keiner | `test` | keine |
| `db-test` | isolierte PostgreSQL-Testdatenbank | keiner | `test` | keine |

Nur Nginx wird auf dem Host veröffentlicht. PostgreSQL, PHP-FPM und ClamD sind nicht direkt vom Host erreichbar.

### HTTPS-Referenzbetrieb

Für eine über TLS erreichbare Installation stellt der Betreiber ein Serverzertifikat und den zugehörigen privaten Schlüssel bereit. Standardmäßig werden folgende nicht versionierte Dateien erwartet:

```text
docker/https/tls.crt
docker/https/tls.key
```

`tls.crt` muss das Serverzertifikat einschließlich erforderlicher Zwischenzertifikate enthalten. Für den produktiven Betrieb sollte `HTTPS_CERTIFICATE_HOST_DIRECTORY` auf ein geschütztes Verzeichnis außerhalb des Repositorys zeigen. Anschließend in `.env` mindestens diese Werte setzen:

```dotenv
APP_URL=https://registry.example.org
SESSION_SECURE_COOKIE=true
HTTPS_CERTIFICATE_HOST_DIRECTORY=/srv/healthcare-node-registry/https
HTTPS_HTTP_PORT=80
HTTPS_PORT=443
```

Den Stack mit Basisdatei und HTTPS-Überlagerung bauen, prüfen und starten:

```bash
docker compose -f docker-compose.yml -f docker-compose.https.yml config --quiet
docker compose -f docker-compose.yml -f docker-compose.https.yml up --build -d
docker compose -f docker-compose.yml -f docker-compose.https.yml exec app php artisan registry:doctor
```

Nginx veröffentlicht dann HTTP auf Port 80 ausschließlich als permanente Weiterleitung und die Anwendung per HTTPS auf Port 443. TLS 1.2 und 1.3, HSTS und die bereits im Standardbetrieb verwendeten Sicherheitsheader sind aktiviert. Die Überlagerung erzwingt für den `app`-Dienst zusätzlich `SESSION_SECURE_COOKIE=true`; der Eintrag in `.env` hält die beabsichtigte Betriebskonfiguration für Administratoren und Konsolenbefehle sichtbar. Fehlen `tls.crt` oder `tls.key`, startet `web` absichtlich nicht. Zertifikatsausstellung, Vertrauenskette, Dateirechte, Erneuerung und privater Schlüssel bleiben Betreiberverantwortung.

Für einen lokalen Test mit einem selbstsignierten Zertifikat können die Host-Ports geändert werden:

```dotenv
APP_URL=https://localhost:8443
SESSION_SECURE_COOKIE=true
HTTPS_HTTP_PORT=8081
HTTPS_PORT=8443
```

Die HTTP-Weiterleitung zielt auf den produktiven Standardport 443. Bei einem abweichenden lokalen HTTPS-Port deshalb direkt `https://localhost:8443` öffnen. Der Browser warnt bei selbstsignierten Zertifikaten erwartungsgemäß. Zur Rückkehr zum unverschlüsselten, ausschließlich lokalen Entwicklungsbetrieb `APP_URL=http://localhost:8080` und `SESSION_SECURE_COOKIE=false` setzen und wieder nur `docker compose up -d` verwenden.

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
| HNR HTTPS-Überlagerung | `80/tcp` → HTTPS, `443/tcp` → Anwendung | Host und Browser; nur mit `docker-compose.https.yml` |
| Storage-Commitment-Callback | `11113/tcp` standardmäßig | während eines Storage-Commitment-Tests vom DICOM-Ziel erreichbar; Host-Port konfigurierbar |
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
| `APP_TIMEZONE` | Standardzeitzone der Anwendung und neuer Discovery-Zeitpläne; IANA-Bezeichner | `Europe/Berlin` in `.env.example` |
| `HTTPS_HTTP_PORT` | Host-Port für die HTTP-zu-HTTPS-Weiterleitung der optionalen HTTPS-Überlagerung | `80` |
| `HTTPS_PORT` | Host-Port für TLS der optionalen HTTPS-Überlagerung | `443` |
| `HTTPS_CERTIFICATE_HOST_DIRECTORY` | Read-only eingebundenes Host-Verzeichnis mit `tls.crt` und `tls.key` | `./docker/https` |
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
| `DIAGNOSTIC_CALLING_AE_TITLE` | Standardmäßiger Calling AE Title für DICOM-Diagnosen | `NODE_REGISTRY` |
| `DIAGNOSTIC_PYTHON_BINARY` | Python-Interpreter der isolierten DICOM-Laufzeit | `/opt/registry-dicom/bin/python` |
| `DIAGNOSTIC_MOVE_TEST_STUDY_UID` | Ausschließlich serverseitig konfigurierte Test-Study-UID für C-MOVE | synthetische UID aus `.env.example` |
| `DIAGNOSTIC_GET_TEST_STUDY_UID` | Ausschließlich serverseitig konfigurierte Test-Study-UID für C-GET | synthetische UID aus `.env.example` |
| `DIAGNOSTIC_STORAGE_COMMITMENT_CALLBACK_PORT` | Interner Callback-Port für N-EVENT-REPORT | `11113` |
| `DIAGNOSTIC_STORAGE_COMMITMENT_HOST_PORT` | Auf dem Docker-Host veröffentlichter Callback-Port | `11113` |
| `DIAGNOSTIC_STORAGE_COMMITMENT_BIND_ADDRESS` | Host-Adresse für den Callback-Port; im Pilot auf die freigegebene DICOM-Schnittstelle begrenzen | `0.0.0.0` |
| `DIAGNOSTIC_STORAGE_COMMITMENT_EVENT_TIMEOUT` | Wartezeit auf den N-EVENT-REPORT in Sekunden | `30` |
| `DOCKER_LOG_MAX_SIZE` | Maximale Größe einer Docker-Logdatei vor Rotation | `10m` |
| `DOCKER_LOG_MAX_FILES` | Anzahl aufbewahrter Docker-Logdateien je Container | `5` |
| `REGISTRY_DOCUMENT_EXPIRY_WARNING_DAYS` | Vorlauf für Ablaufwarnungen | `60` |
| `REGISTRY_DOCUMENT_RETENTION_SHORT_DAYS` | Mindestaufbewahrung für technische Standardunterlagen | `1095` |
| `REGISTRY_DOCUMENT_RETENTION_STANDARD_DAYS` | Mindestaufbewahrung für Betriebs-, Notfall-, SOP- und Testunterlagen | `2555` |
| `REGISTRY_DOCUMENT_RETENTION_LONG_DAYS` | Mindestaufbewahrung für Verträge, Lizenzen und Compliance-Nachweise | `3650` |
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
python3 -m venv /opt/registry-dicom
/opt/registry-dicom/bin/pip install --require-hashes -r docker/php/requirements-dicom.txt
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

Das Skript prüft die im Manifest gespeicherten SHA-256-Werte sowie Datenbank- und Archivformat, stoppt erst danach den Webdienst, stellt Datenbank und privaten Storage wieder her, leert Laravel-Caches und führt anschließend `registry:doctor` aus. Bei restriktiver Windows Execution Policy können die versionierten Skripte einmalig mit `powershell.exe -NoProfile -ExecutionPolicy Bypass -File ...` gestartet werden. Schlägt der Restore nach dem Stoppen fehl, bleibt der Webdienst zum Schutz vor einem inkonsistenten Zustand gestoppt.

## API

Es gibt derzeit keine freigegebene öffentliche REST-API, keine OpenAPI-Spezifikation und keine Swagger-Oberfläche. Die vorhandenen HTTP-Routen bilden die Inertia-Webanwendung ab und benötigen – mit Ausnahme von `/login`, der nach gültigem Passwort kurzzeitig erreichbaren MFA-Challenge und `/up` – eine authentifizierte Sitzung. Privilegierte Sitzungen werden erst nach bestätigtem zweiten Faktor für die Anwendung freigeschaltet. Diagnoseexporte sind als berechtigungsgeprüfte JSON-/CSV-Downloads verfügbar; der Auditexport unterstützt CSV.

## Sicherheit

- Produktive Passwörter und `APP_KEY` niemals aus `.env.example` übernehmen oder versionieren.
- Den Development-Administrator nicht produktiv verwenden; initiale Administratoren mit `registry:create-admin` anlegen.
- `APP_DEBUG=false` beibehalten.
- Wiederherstellungscodes privilegierter Konten getrennt vom Authenticator-Gerät sichern; MFA-Reset nur nach dokumentierter Identitätsprüfung durchführen. Details: [`docs/Security/MultiFactorAuthentication.md`](docs/Security/MultiFactorAuthentication.md).
- Für produktiven Betrieb die mitgelieferte HTTPS-Überlagerung oder einen gleichwertig gehärteten vorgeschalteten Reverse Proxy verwenden, `APP_URL` auf die HTTPS-URL setzen und `SESSION_SECURE_COOKIE=true` verwenden.
- PostgreSQL und PHP-FPM nicht zusätzlich auf Host-Ports veröffentlichen.
- Ausgehenden Netzwerkzugriff des App-Containers auf ausdrücklich freigegebene DICOM-Ziele begrenzen.
- Für registrierte DICOM-Diagnoseziele bei Bedarf DICOM-TLS mit einem gültigen Zertifikatsprofil aktivieren; Profilfehler brechen ohne Klartext-Fallback ab. Der Discovery-Scanner unterstützt noch kein DICOM-TLS.
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
- [`docs/Security/MultiFactorAuthentication.md`](docs/Security/MultiFactorAuthentication.md) – TOTP-MFA, Recovery und Break Glass
- [`docs/Security/FileUploadSecurity.md`](docs/Security/FileUploadSecurity.md) – Dokumenten-Uploadschutz
- [`docs/Deployment/BackupRestore.md`](docs/Deployment/BackupRestore.md) – Backup und Restore
- [`docs/Deployment/ReleaseProcess.md`](docs/Deployment/ReleaseProcess.md) – unveränderlicher Release-Build, Installation, Upgrade und Rollback
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

### Worker ist `unhealthy` oder Discovery-Läufe bleiben geplant

```bash
docker compose ps worker db
docker compose exec worker php artisan registry:worker-health
docker compose logs --tail=200 worker db
```

Der Worker schreibt bei jedem Queue-Loop einen Heartbeat und prüft im Healthcheck zusätzlich seine Datenbankverbindung. Im Leerlauf darf der Heartbeat höchstens 15 Sekunden alt sein. Während eines Jobs bleibt der Worker bis zum individuellen Job-Timeout zuzüglich 15 Sekunden Abschlussfrist gesund; danach gilt auch ein beschäftigter Worker als festgefahren. Ist PostgreSQL nicht erreichbar, wird der Container ebenfalls `unhealthy`. Nach Behebung der Ursache den Worker mit `docker compose restart worker` neu starten. Das Storage-Volume oder die Datenbank dürfen dafür nicht entfernt werden.

### DICOM-Test schlägt fehl

Host, Port, Called/Calling AE Title und Dienstkonfiguration des registrierten Knotens prüfen. Der App-Container benötigt DNS-Auflösung und ausgehenden TCP-Zugriff zum Ziel. Zielsystem-, Firewall- und Containerlogs gemeinsam auswerten. Details stehen im [Diagnose-Workspace](docs/Healthcare/DiagnosticTestWorkspace.md).

### Dokument kann nicht heruntergeladen oder angezeigt werden

Downloads und PDF-Vorschau erfordern den Malware-Scanstatus `clean`. `docker compose ps clamav` und `docker compose logs clamav` prüfen. Nach Wiederherstellung kann ein Rescan mit `docker compose exec app php artisan registry-documents:rescan` sofort angestoßen werden; andernfalls übernimmt ihn der stündliche Scheduler.

## Mitwirken

Der Beitragsprozess und die Qualitätsanforderungen stehen in [`CONTRIBUTING.md`](CONTRIBUTING.md). Änderungen benötigen Tests, aktualisierte Dokumentation, serverseitige Autorisierung und dürfen keine realen Gesundheitsdaten oder Secrets enthalten.

## Lizenz

Copyright © 2026. Alle Rechte vorbehalten. Das Repository ist derzeit für private Produktentwicklung vorgesehen; Details stehen in [`LICENSE.md`](LICENSE.md).
