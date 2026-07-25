# Healthcare Node Registry

> On-Premise-Webanwendung zur zentralen Dokumentation und Visualisierung medizinischer IT-Systeme, Endpunkte und Kommunikationsbeziehungen.

## Status

- **Projektphase:** Sprint 0 – Foundation
- **Foundation-Version:** 0.0.1
- **Geplanter Produktstatus:** Pre-Alpha
- **Ausführbare Anwendung:** noch nicht vorhanden
- **Nächster geplanter Produktstand:** 0.1.0 – Technisches Grundgerüst

Dieses Repository enthält die verbindliche Produktspezifikation, Architekturgrundlagen, Entwicklungsregeln, Security- und Compliance-Vorbereitung. Die Laravel-/Vue-Anwendung wird erst nach formeller Freigabe der blockierenden Sprint-0-Entscheidungen initialisiert.

## Produktziel

Die Anwendung stellt eine zentrale, nachvollziehbare und durchsuchbare Registry für Healthcare-IT bereit. Sie dokumentiert insbesondere:

- DICOM-Nodes und DICOM Application Entities
- PACS-, RIS-, KIS- und PVS-Systeme
- Modalitäten, Archive, Viewer und KI-Systeme
- HL7-, FHIR-, REST-, LDAP-, Datenbank-, Datei- und S3-Endpunkte
- Kommunikationsbeziehungen, Abhängigkeiten, Dienste und Ports
- Standorte, Abteilungen, Teams und Verantwortlichkeiten
- technische Dokumente und Conformance Statements
- Änderungen und Audit-Ereignisse

Der Schwerpunkt der Version 1.0 liegt auf **Dokumentation, Topologie, Suche und Auditierbarkeit**. Das Produkt ist kein PACS, RIS, KIS, Monitoring-System, Netzwerkscanner oder Interface Engine.

## Betriebsmodell

- On-Premise-first
- vollständig im Kundennetz betreibbar
- ohne verpflichtende Internetverbindung
- keine verpflichtende Telemetrie
- browserbasierte Bedienung
- Docker Compose als primärer Installationsweg
- PostgreSQL als primäre Datenbank
- Reverse Proxy und TLS durch Kundeninfrastruktur oder Referenzkonfiguration
- Private/Local Cloud zulässig, solange der Kunde Daten und Dienste kontrolliert

## Geplanter Technologie-Stack

Die konkreten Versionen werden in `docs/Decisions/ADR-0001-technology-versions.md` festgelegt.

- Backend: Laravel
- Frontend: Vue 3, TypeScript, Inertia.js
- Styling: Tailwind CSS
- Datenbank: PostgreSQL
- Topologie: Vue Flow, vorbehaltlich ADR-Freigabe
- Deployment: Docker Compose
- CI/CD: GitHub Actions

Keine unkontrollierten `latest`-Tags.

## Verbindliche Referenzen

- [Produktvision](specification/ProductVision.md)
- [Scope](specification/Scope.md)
- [Requirements](specification/Requirements.md)
- [Netzwerkarchitektur](specification/NetworkArchitecture.md)
- [UI-Referenz](specification/UIReference.md)
- [Healthcare-Glossar](docs/Healthcare/Glossary.md)
- [Initiales ERD](docs/Database/ERD.md)
- [Security Threat Model](docs/Security/ThreatModel.md)
- [ADR-Index](docs/Decisions/README.md)
- [Roadmap](ROADMAP.md)

Die Referenzbilder bleiben verbindliche konzeptionelle beziehungsweise visuelle Leitlinien, sind aber keine pixelgenauen Implementierungsspezifikationen.

## Compliance-Hinweis

Das Repository unterstützt eine spätere Einbettung in ein ISMS und QMS. Es orientiert sich an ISO/IEC 27001, ISO 9001, Datenschutzgrundsätzen und OWASP ASVS.

**Das Repository und die Software stellen keine Zertifizierung und keine automatische vollständige Normkonformität dar.**

## Freigaberegel vor der Laravel-Initialisierung

Die Initialisierung darf beginnen, wenn:

1. alle blockierenden ADRs akzeptiert sind,
2. das Rollen- und Berechtigungsmodell freigegeben ist,
3. das Datenmodell implementierbar ist,
4. offene hohe oder kritische Risiken behandelt oder formell akzeptiert sind,
5. User Stories und Akzeptanzkriterien für 0.1.0 vorliegen,
6. die Lizenzentscheidung getroffen ist.
