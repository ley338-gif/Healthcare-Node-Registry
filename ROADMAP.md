# Roadmap

## Leitprinzip

Die Roadmap beschreibt geplante Ergebnisse. Änderungen werden begründet, versioniert und im Changelog dokumentiert.

## 0.0.1 – Foundation-Baseline

- [x] Produktvision
- [x] MVP-Scope
- [x] Referenzbilder
- [x] AI Engineering Manual
- [x] initiale Architektur- und Datenbankdokumentation
- [x] ISO-27001-/ISO-9001-Vorbereitung
- [x] GitHub-Templates

## Sprint 0 – Foundation-Freigabe

**Ziel:** Implementierbare Produkt-, Architektur-, Security- und Qualitätsgrundlage.

### Blockierende Entscheidungen

- [ ] ADR-0001 Technologieversionen akzeptieren
- [ ] ADR-0002 Authentisierung akzeptieren
- [ ] ADR-0003 Zugriffskontrolle akzeptieren
- [ ] ADR-0004 Identifikatoren akzeptieren
- [ ] ADR-0005 Organisationsmodell akzeptieren
- [ ] ADR-0006 Endpoint- und DICOM-Modell akzeptieren
- [ ] ADR-0007 Topologie-Bibliothek akzeptieren
- [ ] ADR-0008 Dokumentenspeicher akzeptieren
- [ ] ADR-0009 Audit und Logging akzeptieren
- [ ] ADR-0010 Import/Export akzeptieren
- [ ] Threat-Model-Review durchführen
- [ ] priorisierte User Stories für 0.1.0 freigeben
- [ ] Lizenzmodell festlegen

### Exit-Kriterien

- keine widersprüchlichen Versionsangaben
- keine offenen Architekturblocker für die Projektinitialisierung
- Rollen- und Berechtigungsmatrix freigegeben
- Datenmodell implementierbar und fachlich geprüft
- Security-Risiken bewertet
- keine ungeklärten kritischen oder hohen Risiken
- Teststrategie und Definition of Done freigegeben
- Backlog für 0.1.0 vorhanden

## 0.1.0 – Technisches Grundgerüst

- Laravel-/Vue-/TypeScript-Projekt
- PostgreSQL
- Docker-Compose-Entwicklungsumgebung
- CI-Grundpipeline
- Basislayout gemäß UI-Referenz
- lokale sessionbasierte Authentisierung
- Rollen- und Berechtigungsgrundlage
- Health Endpoint ohne interne Details
- strukturierte technische Logs
- erste Installationsanleitung

## 0.2.0 – Registry Core

- Organisationen, Standorte und Abteilungen
- Systeme und Assets
- Systemtypen und Lifecycle-Status
- Hersteller, Modell und Version
- Tags und Notizen
- Listen-, Detail-, Erstell- und Bearbeitungsansichten
- Suche und Filter
- Audit für CRUD-Aktionen

## 0.3.0 – Endpoints & DICOM

- mehrere Endpunkte je System
- protokollspezifische Endpoint-Details
- DICOM AE Title, Host/IP und Port
- DICOM-Dienste und SCU-/SCP-Rollen
- Duplikatwarnungen
- kontrollierter CSV-Import
- Export

## 0.4.0 – Connections & Topology

- gerichtete Verbindungen zwischen Endpunkten
- Dienst, Zweck und Dokumentationsstatus
- Topologieansicht
- Filter
- Detail-Drawer
- Impact-Ansicht
- exportfähige Darstellung

## 0.5.0 – Documents & Knowledge

- sichere Dokumentenanhänge
- Kategorien und Versionen
- SHA-256-Prüfsumme
- Berechtigungen
- Download-Audit
- Malware-Scan-Adapter
- Conformance Statements

## 0.6.0 – Operational Readiness

- Backup und Restore
- Update- und Migrationsprozess
- Security Header
- Dependency- und Container-Scanning
- SBOM
- Performance-Baseline
- Accessibility-Review
- Pilotinstallationsleitfaden

## 0.9.0 – Pilot

- Pilotbetrieb ausschließlich mit synthetischen oder formal bereinigten Daten
- Feedbackprozess
- Usability-Tests
- Korrekturmaßnahmen
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
- DICOM-Conformance-Parser
- erweiterte HL7-/FHIR-Module
- Fristen- und Zertifikatsmanagement
- externe API
- Systemhaus- oder echte Mandantenfunktionen
