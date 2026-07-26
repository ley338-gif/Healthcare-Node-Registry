@'
# Changelog

## [Unreleased]

### Planned

- System Registry mit Systemstammdaten, Zuordnungen, Suche, Detailansicht und Audit

## [0.2.0] - 2026-07-26

### Added

- Organisationsverwaltung
- Standortverwaltung
- Abteilungsverwaltung
- Suche, Filter und Archivierung für die Organisationsstruktur
- serverseitige Autorisierung und Audit Events
- gemeinsame Übersichtsseite für die Organisationsstruktur
- Control-Center-Dashboard ohne simulierte Betriebsdaten
- wiederverwendbare UI-Komponenten
- getrennte Entwicklungs- und Testinfrastruktur

### Changed

- Organisationen, Standorte und Abteilungen unter einem Navigationsbereich gebündelt
- Dashboard auf Systeme, Verbindungen, Status, Topologie und Änderungen ausgerichtet
- Systeme als nächstes zentrales Registry-Objekt hervorgehoben
- geplante Module klar von verfügbaren Funktionen getrennt

### Security

- Tests laufen gegen eine isolierte PostgreSQL-Testinstanz
- PHPUnit bricht bei einer unsicheren Umgebung oder Datenbank ab
'@ | Set-Content .\CHANGELOG.md -Encoding utf8