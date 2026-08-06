---
title: Architecture Decision Records
description: Index und Statusmodell der Architekturentscheidungen.
document_type: ADR-Index
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
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

## Offener Konsolidierungsbedarf

ADR-0002 bis ADR-0010 tragen überwiegend noch den Status `Proposed`, obwohl Teile davon implementiert sein können. Der Status wird nicht allein aufgrund des Codes geändert; erforderlich sind technische Prüfung, Entscheidungsverantwortung und eindeutiger Versionsbezug. Sprach- und Metadatenvereinheitlichung erfolgt bei dieser fachlichen Review.
