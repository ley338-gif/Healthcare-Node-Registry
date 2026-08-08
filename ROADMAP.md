# Roadmap

- [x] Zentraler Audit-Arbeitsbereich mit globaler Suche, Filtern, Detailansicht und CSV-Export

## Registry-Historie und Dokumentation

- [x] gemeinsame kontextbezogene Audit-Abfrage
- [x] Systemhistorie und Historie der Organisationsstruktur
- [x] strukturierte Dokumentation für Systeme, Organisationen, Standorte und Abteilungen
- [x] Audit-Ereignisse für Dokumentationsänderungen ohne rohe Langtexte
- [x] wiederverwendbare Filter-, Detail- und Dokumentationskomponenten
- [x] private Registry-Dokumentenablage mit Kategorien, Versionen und Integritätsprüfsummen
- [x] serverseitige Datei-Allowlist, Größenlimit und Malware-Scanner-Schnittstelle
- [x] berechtigungsgeprüfte Downloads und PDF-Vorschau
- [x] Dokumentfilter, Gültigkeitsstatus und serverseitige Pagination
- [x] Dokumentaktionen in der gemeinsamen Registry-Historie
- [x] globale Audit-Seite auf der gemeinsamen Query-Grundlage
- [x] gefilterter, gestreamter Audit-Export als CSV
- [ ] verbindliche Audit-Aufbewahrung und kryptografischer Integritätsnachweis
- [x] produktiver ClamAV-Scanner mit fail-closed Fallback und Rescan-Workflow
- [ ] Dokumentfreigabe, Vier-Augen-Prinzip und verbindliche Aufbewahrungsregeln

## 0.1.1 – Foundation Hardening

Abgeschlossen.

## 0.2.0 – Registry Core

- [x] Organisationen
- [x] Standorte
- [x] Abteilungen
- [x] Suche und Filter
- [x] Archivierung
- [x] Autorisierung
- [x] Audit Events
- [x] Dashboard-Integration
- [x] Tests
- [ ] Verantwortlichkeiten
- [x] webbasierte Benutzer- und Rollenverwaltung unter Einstellungen

## Nächster Sprint

DICOM Nodes, Diagnose-Workspace und zugehörige Stammdaten sind umgesetzt.

## Diagnose – nächste Ausbaustufe

- [x] Netzwerk und C-ECHO
- [x] Modality Worklist C-FIND
- [x] PACS Study Root C-FIND
- [x] kontrollierter Secondary-Capture-C-STORE
- [x] Capability-Matrix per Association Negotiation
- [x] Testprofile, Verlauf, Export und Dashboard
- [x] serverseitige DICOM-Dateianalyse
- [ ] granulare Einzelrechte für Netzwerk, Echo, Worklist und PACS Query
- [ ] konfigurierbare CIDR-Allowlist, Timeouts und Parallelitätsgrenzen
- [ ] DICOM-TLS für Diagnose-Runner
- [ ] weitere synthetische Storage-SOP-Classes
- [ ] C-MOVE/C-GET nach separatem Sicherheitsdesign
