# Healthcare Node Registry

> Arbeitstitel für eine On-Premise-Webanwendung zur zentralen Dokumentation und Visualisierung medizinischer IT-Systeme und ihrer Kommunikationsbeziehungen.

## Status

**Projektphase:** Sprint 0 – Produkt- und Engineering-Grundlage  
**Dokumentationsversion:** 0.1.0  
**Geplanter Produktstatus:** Pre-Alpha

Dieses Repository enthält zunächst die verbindliche Produktspezifikation, Architekturgrundlagen, Entwicklungsregeln, Compliance-Vorbereitung und den Projektplan. Anwendungsquellcode wird erst nach Freigabe der Architektur und des MVP-Scopes ergänzt.

## Produktziel

Die Anwendung stellt eine zentrale, nachvollziehbare und durchsuchbare Registry für Healthcare-IT bereit. Sie dokumentiert insbesondere:

- DICOM-Nodes und DICOM Application Entities
- PACS-, RIS-, KIS- und PVS-Systeme
- Modalitäten, Archive, Viewer und KI-Systeme
- HL7-, FHIR-, REST-, LDAP-, Datenbank- und Datei-Endpunkte
- Kommunikationsbeziehungen, Abhängigkeiten und Ports
- Standorte, Abteilungen, Verantwortliche und Dokumente
- Änderungen und Audit-Ereignisse

Der Schwerpunkt der ersten Produktversion liegt auf **Dokumentation, Topologie, Suche und Auditierbarkeit**. Das Produkt ist in Version 1.0 weder PACS noch RIS, Interface Engine, Netzwerkscanner oder vollwertiges Monitoring-System.

## Betriebsmodell

- On-Premise und Private/Local Cloud
- vollständig im Kundennetz betreibbar
- browserbasierter Zugriff
- keine verpflichtende Internetverbindung
- keine verpflichtende Telemetrie
- Docker-Compose als primärer Installationsweg
- PostgreSQL als Datenbank
- Reverse Proxy und TLS durch Kundeninfrastruktur oder mitgelieferte Referenzkonfiguration

## Geplanter Technologie-Stack

- Backend: Laravel
- Frontend: Vue 3, TypeScript, Inertia.js
- UI: Tailwind CSS und eine kontrollierte Komponentenbibliothek
- Topologie: Vue Flow oder eine nach ADR freigegebene Alternative
- Datenbank: PostgreSQL
- Queue/Cache: Redis, erst wenn fachlich erforderlich
- Deployment: Docker Compose
- CI: GitHub Actions

Versionen werden vor Implementierungsbeginn in einem ADR und in einer Abhängigkeitsrichtlinie festgelegt. Es werden keine unkontrollierten `latest`-Tags verwendet.

## Verbindliche Referenzen

- [Netzwerkarchitektur](specification/network-architecture-reference.png)
- [GUI-Referenz](specification/ui-reference.png)
- [Produktvision](specification/ProductVision.md)
- [MVP-Scope](specification/Scope.md)
- [AI Engineering Manual](AI_ENGINEERING_MANUAL.md)
- [Roadmap](ROADMAP.md)

Die Referenzbilder geben die visuelle und konzeptionelle Richtung vor. Sie sind keine pixelgenaue oder technisch vollständige Implementierungsspezifikation.

## Compliance-Hinweis

Das Projekt wird auf eine spätere Einbettung in ein Informationssicherheits- und Qualitätsmanagementsystem vorbereitet. Die Dokumentation orientiert sich unter anderem an ISO/IEC 27001:2022, ISO 9001:2015 einschließlich Amendment 1:2024, Datenschutzgrundsätzen und OWASP ASVS.

**Das Repository, die Software und die Dokumentation stellen keine Zertifizierung dar.** Eine Zertifizierung bezieht sich auf ein wirksam betriebenes Managementsystem einer Organisation, nicht allein auf Quellcode oder ein Produkt.

## Erste Schritte

1. `AI_ENGINEERING_MANUAL.md` lesen.
2. Scope und offene Entscheidungen in `ROADMAP.md` prüfen.
3. Architekturentscheidungen als ADR dokumentieren.
4. Sprint 0 abschließen.
5. Erst anschließend das Laravel-/Vue-Projekt initialisieren.

## Repository-Prinzip

Eine Änderung gilt erst als abgeschlossen, wenn Code, Tests und betroffene Dokumentation konsistent sind. Roadmap, Changelog und Architekturunterlagen werden mit jeder Version überprüft.

## Lizenz

Bis zur endgültigen Lizenzentscheidung gilt [LICENSE.md](LICENSE.md).
