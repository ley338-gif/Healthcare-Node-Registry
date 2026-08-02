---
title: Architekturhandbuch
description: Technische Struktur und Entscheidungsgrundlagen der Healthcare Node Registry.
document_type: Architekturhandbuch
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Architekturhandbuch

## Zweck

Das Architekturhandbuch beschreibt Systemgrenzen, Module, Datenhaltung, Deployment und verbindliche Entscheidungen. Es richtet sich an Entwicklung, Betrieb, Sicherheit und technische Reviews.

## Inhalte

- [Architekturüberblick](Overview.md)
- [Kontextdiagramm](ContextDiagram.md)
- [Module](Modules.md)
- [Abhängigkeitsregeln](DependencyRules.md)
- [Deployment](Deployment.md)
- [Dokumentspeicher](registry-document-storage.md)
- [Audit-Historie](audit-history.md)
- [Architecture Decision Records](../Decisions/README.md)
- [Datenmodell und Regeln](../Database/ERD.md)

Die HNR ist eine modulare Laravel-Anwendung mit Inertia/Vue-Frontend, PostgreSQL und privater Dokumentablage. Diagnoseadapter kapseln externe DICOM-Werkzeuge. Interne Routen und Datenstrukturen sind kein freigegebener öffentlicher API-Vertrag.

Architekturdokumente bleiben im Status `draft`, bis sie gegen den aktuellen Code geprüft, versioniert und freigegeben wurden.
