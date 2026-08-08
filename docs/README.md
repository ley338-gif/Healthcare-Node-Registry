---
title: Produktdokumentation
description: Einstieg und Statusübersicht der Dokumentation der Healthcare Node Registry.
document_type: Dokumentationsindex
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Produktdokumentation der Healthcare Node Registry

Diese Dokumentation beschreibt die Healthcare Node Registry (HNR) als proprietäres, kommerzielles On-Premises-Produkt. Sie dient als gemeinsame, versionierbare Informationsbasis für Anwender, Administratoren, Entwicklung, Betrieb und Architekturarbeit. Der private Quellcode und eine möglicherweise später veröffentlichte Produktdokumentation bleiben organisatorisch und rechtlich getrennt.

## Dokumentationsbereiche

| Bereich | Zweck | Status |
|---|---|---|
| [Produktbuch](product-book/README.md) | Vision, Zielgruppen, Produktkonzept, Datenmodell, Sicherheit, Betrieb, Produktlebenszyklus, Integration, Qualität und Governance | Kapitel 1 bis 10 im Entwurf |
| [Benutzerhandbuch](user-guide/README.md) | Aufgabenbezogene Bedienung der freigegebenen Funktionen | sieben Kapitel im Entwurf |
| [Administratorhandbuch](admin-guide/README.md) | Konfiguration, Betrieb, Sicherung und administrative Verfahren | fünf Kapitel im Entwurf |
| [Entwicklerhandbuch](developer-guide/README.md) | Entwicklungsumgebung, Tests und Beitragsprozess für das private Repository | vier Kapitel im Entwurf |
| [Architekturhandbuch](architecture/README.md) | Architekturübersichten, Entscheidungen und technische Leitplanken | bestehende Dokumente verfügbar |
| [DICOM-Referenz](dicom-reference/README.md) | DICOM-Begriffe, Rollen, Dienste und Diagnoseverhalten | konsolidierter Entwurf vorhanden |
| [API](api/README.md) | Dokumentation freigegebener Schnittstellen | Status und Freigaberegeln dokumentiert; keine öffentliche API |
| [Fehlerbehebung](troubleshooting/README.md) | Symptomorientierte Diagnose- und Lösungswege | Entwurf vorhanden |
| [Best Practices](best-practices/README.md) | Bewährte Vorgehensweisen für Dokumentation und Betrieb | Entwurf vorhanden |
| [FAQ](faq/README.md) | Kurze Antworten auf wiederkehrende Produktfragen | Entwurf vorhanden |
| [Glossar](glossary/README.md) | Verbindliche Terminologie der HNR | konsolidierter Entwurf vorhanden |
| [Versionshinweise](release-notes/README.md) | Einer Softwareversion zugeordnete Änderungen und Hinweise | Struktur und Vorlage vorhanden |
| [Medien](assets/README.md) | Freigegebene Abbildungen und weitere Dokumentationsmedien | vorgesehen |

## Aktuell vorhandene Dokumentation

Die gewachsene technische Dokumentation bleibt erhalten. Bis zu ihrer kontrollierten Überführung in die neue Struktur gelten insbesondere:

- [Dokumentations-Masterspezifikation](DOCUMENTATION_MASTER_SPECIFICATION.md)
- [Produktvision](product-book/01-product-vision.md), [Produktkonzept](product-book/02-produktkonzept-und-funktionsumfang.md), [Nutzungsszenarien](product-book/03-zielgruppen-und-nutzungsszenarien.md), [fachliches Datenmodell](product-book/04-fachliches-datenmodell.md), [Sicherheits- und Datenschutzkonzept](product-book/05-sicherheits-und-datenschutzkonzept.md), [Betriebs- und Bereitstellungsmodell](product-book/06-betriebs-und-bereitstellungsmodell.md), [Produktlebenszyklus und Roadmap](product-book/07-produktlebenszyklus-und-roadmap.md), [Integrations- und API-Strategie](product-book/08-integrations-und-api-strategie.md), [Qualitäts- und Compliance-Rahmen](product-book/09-qualitaets-und-compliance-rahmen.md) sowie [Produktgovernance und Verantwortlichkeiten](product-book/10-produktgovernance-und-verantwortlichkeiten.md)
- [Architekturüberblick](Architecture/Overview.md)
- [Modulübersicht](Architecture/Modules.md)
- [Architecture Decision Records](Decisions/README.md)
- [Organisationsstruktur](Domain/OrganizationStructure.md)
- [DICOM-Grundlagen](Healthcare/DICOM.md)
- [Diagnose-Workspace](Healthcare/DiagnosticTestWorkspace.md)
- [Authentifizierung](Security/Authentication.md) und [Zugriffskontrolle](Security/AccessControl.md)
- [Audit-Workspace](Features/audit-workspace.md)
- [Export der System- und Knotenübersicht](Features/system-overview-export.md)
- [Registry-Dokumentation](Features/registry-documentation.md)
- [Benutzerverwaltung](Features/UserManagement.md)
- [Vorlage für Versionshinweise](Releases/RELEASE_NOTES_TEMPLATE.md)

## Statusmodell

Jedes freigaberelevante Kapitel erhält einen nachvollziehbaren Dokumentstatus:

- `draft`: fachlich in Bearbeitung und nicht freigegeben
- `review`: zur fachlichen und technischen Prüfung vorgelegt
- `approved`: für die angegebene Dokument- und Softwareversion freigegeben
- `deprecated`: weiterhin nachvollziehbar, aber nicht mehr für den aktuellen Produktstand maßgeblich

Geplante Produktfunktionen werden ausdrücklich als geplant bezeichnet. Langfristige Zielbilder werden getrennt vom aktuellen und geplanten Funktionsumfang dargestellt.

## Erstellung und Freigabe

Kapitel werden einzeln erstellt, geprüft und freigegeben. Ein Kapitel gilt erst dann als freigegeben, wenn Inhalt, Terminologie, relative Links, Produktstatus und Versionsbezug fachlich sowie technisch geprüft wurden. Änderungen bleiben über die private Versionsverwaltung nachvollziehbar.

Der Freigabeprozess umfasst mindestens:

1. Erstellung oder Überarbeitung als `draft`.
2. Fachliche Prüfung durch eine für den Themenbereich verantwortliche Person.
3. Technische Prüfung gegen den tatsächlich verfügbaren Softwarestand.
4. Prüfung von Terminologie, Links und widerspruchsfreien Statusangaben.
5. Freigabe als `approved` mit eindeutigem Versionsbezug.

Diese Übersicht enthält bewusst keine Installationsanleitung. Betriebs- und Installationsverfahren werden im Administratorhandbuch gepflegt und separat freigegeben.
