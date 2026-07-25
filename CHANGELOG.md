# Changelog

## [Unreleased]

## [0.1.1] - 2026-07-25

### Added

- sicherer `registry:create-admin`-Befehl
- `registry:doctor`
- Security-Event-Tabelle
- zentraler RBAC-Bootstrapper
- Backup-/Restore-Skripte mit SHA-256
- Clean-Install-Anleitung
- Tests für Setup und Installationsprüfung

### Changed

- Seeder und produktiver Setup-Prozess getrennt
- CI um Composer-Validierung, Audits, `.env`-Schutz und Compose-Prüfung erweitert
- Roadmap und Known Issues synchronisiert

### Security

- Passwort nur verdeckt abgefragt
- keine Passwortoption in der Shell
- zweites Initialkonto wird verweigert
- Restore verlangt explizite Bestätigung
