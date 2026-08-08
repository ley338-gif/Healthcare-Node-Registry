---
title: ADR-0012 – Modularer Monolith als Anwendungsarchitektur
description: Entscheidung für eine gemeinsam deployte Laravel-/Inertia-Anwendung mit klaren Domänengrenzen.
document_type: ADR
chapter: ADR-0012
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0012: Modularer Monolith als Anwendungsarchitektur

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-07-25
- Verantwortliche Rolle: Architektur/Backend
- Betroffene Produktversion: 0.1.0
- Ersetzt: -
- Ersetzt durch: -

## Kontext

Die Registry benötigt Authentisierung, RBAC, fachliche Registry-Module, Audit, Import/Export und eine administrative Weboberfläche. Diese Fähigkeiten teilen Datenmodell, Transaktionsgrenzen und Bereitstellung. Eine frühzeitige Verteilung auf eigenständige Dienste würde zusätzliche Netzwerk-, Konsistenz- und Betriebsgrenzen schaffen, bevor ein belastbarer Skalierungsbedarf besteht.

## Entscheidung

Die erste Produktgeneration wird als modularer Monolith umgesetzt: Laravel bildet Backend und fachliche Quelle der Wahrheit, Inertia/Vue die administrative Oberfläche, PostgreSQL die relationale Datenhaltung. Fachliche Grenzen werden in Models, Services, Policies, Controllern und dokumentierten Domänen gewahrt, aber gemeinsam gebaut und deployt.

Prozessgrenzen werden nur für konkrete Betriebsaufgaben ergänzt, etwa Queue-Worker oder Scheduler. Sie greifen auf dieselbe Anwendung und Datenbank zu und bilden keine eigenständigen Microservices.

## Alternativen

- **Microservices pro Domäne:** verworfen, da die zusätzliche Betriebs- und Konsistenzkomplexität für den aktuellen Umfang keinen belegten Nutzen bietet.
- **Unstrukturierter Monolith:** verworfen, weil fehlende Domänengrenzen Tests, Verantwortlichkeiten und spätere Extraktion erschweren würden.
- **Reines API-Backend mit getrenntem SPA-Deployment:** verworfen, da Inertia und serverseitige Sessionauthentisierung den benötigten On-Premises-Betrieb mit weniger Komplexität abdecken.

## Konsequenzen

### Positiv

- Ein reproduzierbarer Build- und Deploymentpfad.
- Transaktionale Konsistenz über Registry-, Audit- und Administrationsfunktionen.
- Serverseitige Authentisierung und Autorisierung ohne parallele Client-Sicherheitslogik.
- Fachmodule können innerhalb einer Anwendung iterativ ergänzt werden.

### Negativ und Risiken

- Fehler oder Ressourcenengpässe können mehrere Domänen derselben Anwendung betreffen.
- Domänengrenzen müssen in Code und Reviews aktiv erhalten werden.
- Eine spätere Auslagerung erfordert explizite Schnittstellen und ein eigenes ADR.

## Verifikation

- Architektur- und Modulübersichten beschreiben dieselben Domänengrenzen.
- Geschäftslogik liegt nicht in Vue-Komponenten; Policies/Gates bleiben serverseitig.
- Der Compose- und CI-Build erzeugt eine gemeinsam deployte Anwendung mit separaten Betriebsprozessen nur bei nachgewiesenem Bedarf.

## Referenzen

- `docs/Architecture/Overview.md`
- `docs/Architecture/Modules.md`
- `docs/Decisions/ADR-0002-authentication.md`
- `docs/Decisions/ADR-0003-access-control.md`
- `AI_ENGINEERING_MANUAL.md`
