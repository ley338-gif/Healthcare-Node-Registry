---
title: Architektur- und Implementierungsregeln
description: Leitplanken für Backend, Frontend, Berechtigungen und Audit.
document_type: Entwicklerhandbuch
chapter: 2
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Architektur- und Implementierungsregeln

Neue Funktionen erweitern vorhandene Models, Controller, Form Requests, Services, Policies, Auditmechanismen und Vue-Muster. Fachlogik gehört nicht parallel in Controller und Komponenten.

## Verbindliche Regeln

- Autorisierung serverseitig und standardmäßig restriktiv durchsetzen.
- Form Requests für schreibende Eingaben verwenden.
- öffentliche UUIDs statt interner IDs an extern sichtbaren Grenzen nutzen.
- Zustands- und Referenzwerte zentral als Enum oder Konfiguration führen.
- sensible Änderungen und Aktionen über die bestehende Audit-Infrastruktur erfassen.
- Inertia-Seiten schlank halten und wiederverwendbare UI-Komponenten nutzen.
- keine Secrets, Patientendaten oder unbereinigten Stacktraces persistieren.
- Migrationen vorwärtsgerichtet, getestet und mit Updatefolgen dokumentieren.

Langfristig bindende Abweichungen benötigen ein ADR. Siehe [Architekturhandbuch](../Architecture/README.md) und [Abhängigkeitsregeln](../Architecture/DependencyRules.md).
