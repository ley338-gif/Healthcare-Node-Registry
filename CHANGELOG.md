# Changelog

## [Unreleased]

## [0.1.0] - 2026-07-25

### Added

- Laravel-13-/Vue-3-/Inertia-3-Grundgerüst
- modularer Monolith mit reservierten Domänengrenzen
- PostgreSQL-18- und Docker-Compose-Umgebung
- lokale sessionbasierte Anmeldung
- native Rollen- und Berechtigungsgrundlage
- Dashboard-Basislayout
- Health Endpoint
- strukturierte JSON-Logs auf stderr
- Backend-, Frontend- und Container-CI
- Featuretests für Health, Login und Dashboard
- akzeptierter Technologie-ADR

### Security

- Debug standardmäßig deaktiviert
- verschlüsselte Datenbanksessions
- Rate Limiting für Anmeldungen
- keine öffentliche Registrierung
- sichere Nginx-Header
- getrennte Frontend- und interne Backend-Netze
- Datenbank ohne veröffentlichten Host-Port
