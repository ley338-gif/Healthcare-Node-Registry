# Testing Strategy

## Testpyramide

- Unit Tests für fachliche Regeln
- Feature-/Integrationstests für HTTP, Datenbank und Policies
- Komponenten-/Frontendtests für kritische Interaktionen
- wenige End-to-End-Tests für Kernabläufe
- Security-, Migrations- und Restore-Tests
- manuelle UX- und Accessibility-Reviews

## Vorgesehene Werkzeuge

Die konkreten Versionen werden in ADR-0001 festgelegt.

- PHPUnit oder Pest für PHP
- Laravel Feature Tests
- Vue Test Utils und Vitest
- Playwright für ausgewählte E2E-Flows
- axe-core für automatisierte Accessibility-Prüfungen
- Composer Audit und npm Audit
- Container- und Dependency-Scanning in CI

## Kritische Testfälle

- jede schreibende Aktion mit positiver und negativer Rechteprüfung
- Organisations-/Standort-Scopes
- Duplikat- und Validierungsregeln für Endpoints
- DICOM-Dienste und SCU-/SCP-Rollen
- Connection-Integrität
- Audit-Erzeugung und Redaction
- Uploadprüfung und Quarantäne
- Exportberechtigung und CSV-Injection-Schutz
- Migration und Rollbackbewertung
- Backup und Restore
- Loading-, Empty-, Error- und Permission-Denied-States

## Testdaten

Ausschließlich synthetische Daten. Keine Produktionskopien ohne formelle, geprüfte Anonymisierung.

## Merge-Gates ab 0.1.0

- Backend-Tests erfolgreich
- Frontend-Tests erfolgreich
- Linting und statische Analyse erfolgreich
- Dokumentationscheck erfolgreich
- keine kritischen Dependency-/Container-Funde
- Migrationen geprüft
