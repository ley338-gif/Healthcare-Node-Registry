# ADR-0004: Identifiers

- **Status:** Proposed

## Entscheidung

Interne relationale Primärschlüssel verwenden PostgreSQL `bigint generated as identity`. Nach außen verwendete Objektkennungen verwenden UUID, vorzugsweise UUIDv7, sofern die gewählte Laravel-/PostgreSQL-Version dies sauber unterstützt.

## Begründung

Interne sequenzielle Schlüssel vereinfachen Joins und Betrieb. Öffentliche UUIDs vermeiden erratbare Ressourcen-URLs und erleichtern Import/Export.

## Regeln

- öffentliche IDs sind unique und unveränderlich
- interne IDs werden nicht in externen Exportformaten vorausgesetzt
- Migrationen und Seeds nutzen konsistente Strategien
