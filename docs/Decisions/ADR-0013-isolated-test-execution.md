---
title: ADR-0013 – Physisch isolierte Backend-Testausführung
description: Trennung von Entwicklungs- und Testdatenbank durch eigene Compose-Dienste, Netze und Laufzeitprüfungen.
document_type: ADR
chapter: ADR-0013
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0013: Physisch isolierte Backend-Testausführung

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-07-26
- Verantwortliche Rolle: Entwicklung/DevOps
- Betroffene Produktversion: 0.1.2
- Ersetzt: Testausführung im Entwicklungscontainer
- Ersetzt durch: -

## Kontext

Backendtests wurden ursprünglich im laufenden Entwicklungscontainer gestartet. Dadurch übernahm PHPUnit effektiv `APP_ENV=local` und die Entwicklungsdatenbank. Tests mit `RefreshDatabase` konnten deshalb lokale Entwicklungsdaten löschen. Eine abweichende Datenbankbezeichnung allein verhindert weder eine fehlerhafte Containerkonfiguration noch die Netzwerkerreichbarkeit der Entwicklungsdatenbank.

## Entscheidung

Backendtests laufen ausschließlich über das Compose-Profil `test` mit einer eigenen Ausführungsgrenze:

- `app-test` als Testanwendung mit `APP_ENV=testing`,
- `db-test` als separate PostgreSQL-Instanz,
- `test_backend` als isoliertes Netzwerk,
- `app_test_storage` und `postgres_test_data` als getrennte Volumes.

`app-test` ist nicht mit dem Entwicklungsnetz verbunden und verwendet ausschließlich den Hostnamen `db-test`. Zusätzlich verweigert der Test-Basisklasse jede Ausführung außerhalb der Laravel-Umgebung `testing` oder gegen eine Datenbank, deren Name nicht auf `_test` endet.

## Alternativen

- **Nur `DB_DATABASE` vor dem Test überschreiben:** verworfen, weil der Entwicklungscontainer und dessen Netzwerkzugriff bestehen bleiben.
- **SQLite/In-Memory:** verworfen, weil PostgreSQL-spezifische Constraints und Verhalten Teil der zu prüfenden Anwendung sind.
- **Tests im Entwicklungscontainer mit Warnhinweis:** verworfen, weil eine dokumentarische Warnung keinen technischen Schutz vor Datenverlust bietet.

## Konsequenzen

### Positiv

- PHPUnit kann den Entwicklungsdienst `db` nicht über Docker-DNS erreichen.
- Testdaten und Laufzeitspeicher sind vollständig von lokalen Entwicklungsdaten getrennt.
- Fehlkonfigurierte Testaufrufe werden vor einer Datenbankmigration abgebrochen.

### Negativ und Risiken

- Backendtests benötigen einen zusätzlichen PostgreSQL-Container und damit mehr lokale Ressourcen.
- Direkte Aufrufe von PHPUnit im Entwicklungscontainer sind absichtlich nicht unterstützt.
- Testvolumes müssen bei Schema- oder Zustandsproblemen separat bereinigt werden.

## Verifikation

Der verbindliche Quality-Aufruf lautet:

```powershell
docker compose --profile test run --rm app-test composer quality
```

`tests/TestCase.php` prüft Umgebung und Datenbanknamen. `docker compose config --quiet` validiert die Dienst- und Netzwerkdefinitionen.

## Referenzen

- `docker-compose.yml`
- `tests/TestCase.php`
- `docs/Development/DockerArchitecture.md`
- `docs/Development/Testing.md`
- `scripts/test.ps1`
- `scripts/quality.ps1`
