# ADR-0005: Organization Model

- **Status:** Proposed

## Entscheidung

Eine Installation kann mehrere Organisationen, Standorte und Abteilungen fachlich dokumentieren. Dies stellt keine echte technische Multi-Tenancy dar.

Zugriffe können über RBAC-Scopes eingeschränkt werden. Eine getrennte Datenbank pro Tenant, Tenant-Switching und SaaS-Abrechnung sind nicht Bestandteil des MVP.

## Folgen

- Organization bleibt fachliche Root-Entität
- globale Systemadministratoren bleiben möglich
- Datenbankmigrationen enthalten keine vorzeitige Tenant-Abstraktion
