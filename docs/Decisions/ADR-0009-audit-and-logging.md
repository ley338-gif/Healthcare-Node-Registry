# ADR-0009: Audit and Logging

- **Status:** Proposed

## Entscheidung

Drei getrennte Kategorien:

1. fachliche Audit Events
2. Security Events
3. technische Logs

Audit Events sind append-only. Technische Logs enthalten keine vollständigen Request Bodies, Tokens, Passwörter oder Patientendaten.

## Offene Betriebsparameter

- Aufbewahrungsdauer
- Logrotation
- Export
- Integritätsschutz
- zentrale Weiterleitung

Diese Parameter werden vor dem Pilot verbindlich festgelegt.
