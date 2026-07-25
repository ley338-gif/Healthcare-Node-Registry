# ADR-0010: Import and Export

- **Status:** Proposed

## Entscheidung

Der erste Import verwendet versionierte CSV-Schemata mit Dry Run, Validierungsbericht, Zeilennummern, Duplikatwarnungen und kontrollierter Transaktionsstrategie.

Exporte benötigen gesonderte Berechtigung, Scope-Prüfung, Audit und Schutz vor Spreadsheet Formula Injection.

## Nicht-Ziele

- ungeprüfter Vollimport
- beliebige Datenbankdumps
- automatische Änderungen ohne Vorschau
- Export unautorisierter oder ausgeblendeter Daten
