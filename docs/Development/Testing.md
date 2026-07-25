# Testing Strategy

## Testpyramide

- Unit Tests für fachliche Regeln
- Feature/Integration Tests für HTTP, Datenbank und Policies
- Komponenten-/Frontendtests für kritische Interaktionen
- wenige End-to-End-Tests für Kernabläufe
- Security- und Migrationstests
- manuelle UX- und Accessibility-Reviews

## Kritische Testfälle

- Rechteprüfung jeder schreibenden Aktion
- Mandanten-/Standortgrenzen, falls eingeführt
- Duplikat- und Validierungsregeln für Endpoints
- Connection-Integrität
- Audit-Event-Erzeugung
- Uploadprüfung
- Exportberechtigung
- Migrationen
- Backup/Restore
- Fehler- und Empty States

## Testdaten

Ausschließlich synthetisch. Keine Produktionskopien ohne formelle, geprüfte Anonymisierung.
