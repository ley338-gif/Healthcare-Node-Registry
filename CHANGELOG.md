# Changelog

## Unreleased

- Zentralen, read-only Audit-Arbeitsbereich mit Berechtigungsprüfung, serverseitiger Filterung, 50er-Paginierung, Slide-over-Details und CSV-Export ergänzt.

## [Unreleased]

### Registry-Historie und Dokumentation

- gemeinsame Registry-Historie für Systeme, Organisationen, Standorte und Abteilungen
- serverseitige Audit-Filter, Kennzahlen, Pagination und Detailansicht
- strukturierte polymorphe Betriebsdokumentation mit kontextspezifischen Sektionen
- nachvollziehbarer Dokumentationsstand auf Basis definierter Pflichtfelder
- Audit-Ereignisse für Dokumentationsänderungen ohne vollständige Langtexte
- zentrale Audit-Filterung und Entity-Auflösung als Vorbereitung der globalen Audit-Seite

- private Dokumentenablage für Organisationen, Standorte, Abteilungen und Systeme
- unveränderliche Dateiversionen mit aktueller Version, SHA-256 und Duplikaterkennung
- zentrale Dokumentkategorien, Gültigkeitsstatus, Suche, Filter und serverseitige Pagination
- serverseitige Datei-Allowlist mit MIME- und Signaturprüfung sowie konfigurierbarem Größenlimit
- Malware-Scanner-Schnittstelle mit Fail-Closed-Zugriff für nicht saubere Versionen
- berechtigungsgeprüfter Download und abgesicherte PDF-Vorschau ohne öffentliche Storage-URL
- Audit-Ereignisse für Upload, Versionierung, Metadaten, Archivierung und Scanfehler

### Added

- Diagnose-Workspace mit standardisierter Ergebnisarchitektur und persistentem Verlauf
- echte Netzwerk-, C-ECHO-, Worklist- und PACS-C-FIND-Tests gegen registrierte Knoten
- kontrollierter synthetischer C-STORE mit Bestätigung, strengem Recht und Audit
- SOP-Class-/Transfer-Syntax-Capability-Matrix ohne C-STORE
- wiederverwendbare Testprofile und Dashboard-Diagnosestatus
- serverseitige DICOM-Dateianalyse mit automatischer temporärer Bereinigung
- bereinigter JSON- und CSV-Export von Diagnoseergebnissen
- gemeinsame Übersichtsseite für Organisationsstruktur
- gruppierte Registry-Navigation
- produktorientiertes Dashboard ohne erfundene Monitoringdaten
- letzte Registry-Änderungen aus Security Events

### Changed

- bestehende C-ECHO-Verifikation in die gemeinsame Diagnoseergebnis- und Verlaufsarchitektur integriert
- Dashboard um berechtigungsgeprüfte Diagnosekennzahlen ergänzt
- Organisationen, Standorte und Abteilungen unter einem Navigationsbereich gebündelt
- Systeme als nächstes zentrales Registry-Objekt hervorgehoben
- geplante Module klar von verfügbaren Funktionen getrennt
