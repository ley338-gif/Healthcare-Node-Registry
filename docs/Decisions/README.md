---
title: Architecture Decision Records
description: Index und Statusmodell der Architekturentscheidungen.
document_type: ADR-Index
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-11
---

# Architecture Decision Records

## Zweck

ADRs dokumentieren langfristig bindende oder schwer umkehrbare technische Entscheidungen. Ein dokumentierter Vorschlag ist noch keine akzeptierte Entscheidung.

## Status

- `Proposed`: vorgeschlagen und noch nicht verbindlich
- `Accepted`: entschieden und für den genannten Stand verbindlich
- `Superseded`: durch ein benanntes ADR ersetzt
- `Rejected`: geprüft und verworfen

## Index

- [ADR-0000: Vorlage](ADR-0000-template.md)
- [ADR-0001: Technologieversionen](ADR-0001-technology-versions.md)
- [ADR-0002: Authentisierung](ADR-0002-authentication.md)
- [ADR-0003: Zugriffskontrolle](ADR-0003-access-control.md)
- [ADR-0004: Kennungen](ADR-0004-identifiers.md)
- [ADR-0005: Organisationsmodell](ADR-0005-organization-model.md)
- [ADR-0006: Endpoint- und DICOM-Modell](ADR-0006-endpoint-and-dicom-model.md)
- [ADR-0007: Topologiebibliothek](ADR-0007-topology-library.md)
- [ADR-0008: Dokumentspeicher](ADR-0008-document-storage.md)
- [ADR-0009: Audit und Logging](ADR-0009-audit-and-logging.md)
- [ADR-0010: Import und Export](ADR-0010-import-export.md)
- [ADR-0011: Discovery-Scan-Architektur](ADR-0011-discovery-scanning.md)
- [ADR-0012: Fundament des modularen Monolithen](ADR-0012-modular-monolith-foundation.md)
- [ADR-0013: Isolierte Testausführung](ADR-0013-isolated-test-execution.md)
- [ADR-0014: Wiederverwendbare Anwendungsshell](ADR-0014-reusable-application-shell.md)
- [ADR-0015: Audit-Integrität und Aufbewahrung](ADR-0015-audit-integrity-and-retention.md)
- [ADR-0016: Dokumentfreigabe und Aufbewahrung](ADR-0016-document-approval-and-retention.md)
- [ADR-0017: Betriebliche Systemverantwortlichkeit](ADR-0017-system-responsibility.md)
- [ADR-0018: DICOM-TLS für Diagnose-Runner](ADR-0018-dicom-tls-diagnostics.md)
- [ADR-0019: Synthetische Storage-SOP-Classes](ADR-0019-synthetic-storage-sop-classes.md)
- [ADR-0020: Administrierbare System-Referenzdaten](ADR-0020-system-reference-data.md)
- [ADR-0021: Administrierbare Hersteller-Referenzdaten](ADR-0021-manufacturer-reference-data.md)
- [ADR-0022: Vergleich von Discovery-Scanständen](ADR-0022-discovery-run-comparison.md)
- [ADR-0023: Benachrichtigungen über Discovery-Änderungen](ADR-0023-discovery-change-notifications.md)
- [ADR-0024: Import von Discovery-AE-Titel-Kandidaten](ADR-0024-discovery-ae-candidate-import.md)
- [ADR-0025: PDF-Export der Discovery-Ergebnisse](ADR-0025-discovery-pdf-export.md)
- [ADR-0026: Rollenabhängige DICOM-Netzwerkendpunkte](ADR-0026-role-dependent-dicom-endpoints.md)

## Offener Konsolidierungsbedarf

ADR-0002 bis ADR-0010 tragen überwiegend noch den Status `Proposed`, obwohl Teile davon implementiert sein können. Der Status wird nicht allein aufgrund des Codes geändert; erforderlich sind technische Prüfung, Entscheidungsverantwortung und eindeutiger Versionsbezug. Sprach- und Metadatenvereinheitlichung erfolgt bei dieser fachlichen Review.
