# Clean Install und Restore validieren

Der folgende Ablauf beschreibt die Entwicklungsvalidierung. Eine Pilot- oder Release-Abnahme muss stattdessen die unveränderlichen Images und den Ablauf aus [`ReleaseProcess.md`](ReleaseProcess.md) verwenden; Composer- und npm-Abhängigkeiten werden dabei bereits während des Image-Builds installiert.

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
- Queue-Worker ist healthy und `registry:worker-health` erfolgreich
- Doctor-Command erfolgreich
- `/up` liefert HTTP 200
- Login und Logout funktionieren
- Daten bleiben nach Neustart erhalten
- kein Entwicklungskonto in Production
- keine Secrets in Logs oder Git
- CI erfolgreich

Commit-SHA, Docker-Versionen, Dauer, Ergebnis und Abweichungen dokumentieren.

## Validierungsprotokoll 2026-08-09

Der Ablauf wurde aus einer separaten, frischen Arbeitskopie von `main` auf Basis von Revision `fcb32eb` zusammen mit der in diesem Änderungssatz enthaltenen Backup-/Restore-Härtung ausgeführt. Das Compose-Projekt `hnr-clean-validation` verwendete eigene Netzwerke und Volumes sowie die abweichenden Hostports `18080` und `11114`; die bestehende Entwicklungsinstallation wurde nicht verwendet oder verändert.

| Prüfung | Ergebnis |
|---|---|
| Docker Engine / Compose | 29.6.2 / 5.3.1 |
| PHP-Image ohne Build-Cache | erfolgreich |
| Composer-Installation aus Lockfile | 111 Pakete, erfolgreich |
| npm-Installation aus Lockfile | 236 Pakete, 0 gemeldete Schwachstellen |
| Anwendungsschlüssel und frische Migration | erfolgreich, 26 Migrationen |
| Produktions-Frontend-Build | erfolgreich |
| Erster synthetischer Systemadministrator | erfolgreich; kein Development-Seeder in `production` |
| Vollständiger Stack | App, Worker, Scheduler, Web, PostgreSQL und ClamAV gestartet; PostgreSQL, Worker und ClamAV healthy |
| `registry:doctor` | alle Prüfungen erfolgreich |
| HTTP und Authentisierung | `/up` und `/login` HTTP 200; Login und Logout mit Session/XSRF erfolgreich |
| Persistenz nach Neustart | Datenbank- und Storage-Markierung blieben erhalten |
| Backup | PostgreSQL-Custom-Dump, Storage-Archiv mit `private/`, SHA-256-Manifest und Git-Revision erfolgreich |
| Negativer Restore-Test | ungültiges, aber prüfsummenkonsistentes Storage-Archiv vor dem Stoppen des Webdienstes abgelehnt; `/up` blieb HTTP 200 |
| Vollständiger Restore | Datenbank und privater Storage auf Sicherungsstand zurückgesetzt; Doctor, `/up` und Login danach erfolgreich |

Während der Validierung wurden zwei Fehler behoben: Native Docker-Fehler konnten von PowerShell 5.1 zuvor trotz `$ErrorActionPreference = "Stop"` übergangen werden, und der Container-Entrypoint zerlegte Kommandoargumente mit Leerzeichen. Zusätzlich wurde der einmalige Aufruf bei restriktiver Windows Execution Policy dokumentiert.

Die lokale Validierung ersetzt nicht die betriebliche Abnahme auf dem späteren Zielhost. Dort müssen insbesondere externe Erreichbarkeit, Reverse Proxy/HTTPS, Backup-Ziel, Verschlüsselung, Schlüsselverwaltung und Restore-Dauer erneut protokolliert werden.
