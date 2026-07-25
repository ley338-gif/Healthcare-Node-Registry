# Roadmap

## Leitprinzip

Die Roadmap beschreibt geplante Ergebnisse, keine unveränderliche Zusage. Änderungen werden begründet, versioniert und im Changelog dokumentiert.

## Sprint 0 – Foundation

**Ziel:** Freigegebene Produkt-, Architektur-, Sicherheits- und Qualitätsgrundlage.

- [x] Produktvision
- [x] MVP-Scope
- [x] Referenzbilder integrieren
- [x] AI Engineering Manual
- [x] ISO-27001-/ISO-9001-Vorbereitung dokumentieren
- [x] initiales Datenmodell
- [x] GitHub-Templates
- [ ] ADR: konkrete Framework-Versionen
- [ ] ADR: Authentisierungsstrategie
- [ ] ADR: Topologie-Bibliothek
- [ ] ADR: Dokumentenspeicher
- [ ] Bedrohungsmodell-Workshop
- [ ] priorisierte User Stories und Akzeptanzkriterien
- [ ] Lizenzmodell festlegen

**Exit-Kriterien:** Keine offenen Blocker für Projektinitialisierung; Verantwortlichkeiten und MVP-Abgrenzung freigegeben.

## 0.1.0 – Technisches Grundgerüst

- Laravel-/Vue-/TypeScript-Projekt
- PostgreSQL
- Docker-Compose-Entwicklungsumgebung
- CI-Grundpipeline
- Basislayout gemäß UI-Referenz
- Authentisierung
- Rollen- und Berechtigungsgrundlage
- Health Endpoint ohne interne Details
- strukturierte Logs
- erste Installationsanleitung

## 0.2.0 – Registry Core

- Organisation, Standort und Abteilung
- Systeme/Assets
- Systemtypen und Status
- Hersteller, Modell und Version
- Tags und benutzerdefinierte Notizen
- Listen-, Detail-, Erstell- und Bearbeitungsansichten
- Suche und Filter
- Audit-Events für CRUD-Aktionen

## 0.3.0 – Endpoints & DICOM

- mehrere Endpunkte pro System
- DICOM AE Title, Host/IP und Port
- SCU-/SCP-Rollen je Dienst
- Validierung und Duplikatwarnungen
- DICOM-fachliche Detailansicht
- kontrollierter CSV-Import
- Export

## 0.4.0 – Connections & Topology

- gerichtete Verbindungen zwischen Endpunkten
- Protokoll, Dienst, Zweck und Status
- Topologieansicht
- Filter nach Standort, Protokoll und Systemtyp
- Detail-Drawer
- Impact-Ansicht „Was hängt davon ab?“
- druck-/exportfähige Darstellung

## 0.5.0 – Documents & Knowledge

- sichere Dokumentenanhänge
- Kategorien
- Version und Gültigkeit
- SHA-256-Prüfsumme
- Berechtigungen
- Download-Audit
- optionaler Malware-Scan-Adapter
- Conformance Statements verknüpfen

## 0.6.0 – Operational Readiness

- Backup-/Restore-Prozess
- Update- und Migrationsprozess
- Admin- und Security-Dokumentation
- Security-Header
- Dependency-/Container-Scanning
- SBOM
- Basis-Performance-Tests
- Accessibility-Review
- Pilotinstallationsleitfaden

## 0.9.0 – Pilot

- Pilotbetrieb mit synthetischen oder bereinigten Daten
- Feedbackprozess
- Usability-Tests
- Korrekturmaßnahmen
- Datenimportverbesserungen
- vollständige Betriebsdokumentation
- Release Candidate

## 1.0.0 – Erste produktive Version

- freigegebener MVP
- dokumentierter Supportumfang
- reproduzierbares Release
- Installations-, Update-, Backup- und Restore-Anleitung
- Security- und Qualitätsnachweise
- keine bekannten kritischen oder hohen Sicherheitslücken

## Nach 1.0 – nur nach Kundenvalidierung

- aktive C-ECHO-/TCP-Prüfungen
- segmentbasierter Monitoring-Agent
- automatische Discovery
- DICOM Conformance Parser
- HL7-/FHIR-Spezialmodule
- Vertrags-, Wartungs- und Zertifikatsfristen
- externe API
- Mandanten-/Systemhausfunktionen

Diese Punkte sind bewusst nicht Teil des MVP.
