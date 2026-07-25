# ADR-0003: Access Control

- **Status:** Proposed

## Entscheidung

RBAC mit Default Deny. Berechtigungen werden serverseitig über Policies/Gates erzwungen. Optionales Scoping erfolgt nach Organisation, Standort und später Abteilung.

## Rollen

System Administrator, Registry Administrator, Editor, Document Manager, Auditor und External Support Read Only.

## Nicht-Ziele

Keine reine Frontend-Autorisierung und keine echte SaaS-Tenant-Isolation im MVP.

## Folgen

Jede schreibende Aktion benötigt positive und negative Policy-Tests. Exporte, Audit-Zugriff und Benutzerverwaltung sind gesonderte Rechte.
