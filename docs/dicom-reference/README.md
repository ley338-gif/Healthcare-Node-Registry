---
title: DICOM-Referenz
description: DICOM-Begriffe, Dienste und Diagnosegrenzen der HNR.
document_type: DICOM-Referenz
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# DICOM-Referenz

## Zweck

Diese Referenz erläutert die in der HNR verwendeten DICOM-Begriffe. Sie ersetzt weder den DICOM-Standard noch die Konformitätserklärungen der beteiligten Systeme.

## Application Entity und Knoten

Eine DICOM Application Entity (AE) ist ein logischer Kommunikationspartner. In der HNR wird sie als DICOM-Knoten mit AE Title, Host, Port, Rolle, Status, Systemzuordnung und Diensten dokumentiert. Gerät, System und AE sind nicht zwingend identisch.

## Rollen und Dienste

SCU initiiert einen Dienst, SCP stellt ihn bereit. Die Rolle gilt pro Dienst. Dokumentiert werden insbesondere Verification, Storage, Query/Retrieve, Modality Worklist, MPPS, Storage Commitment und weitere kontrollierte Dienste. Die aktuell ausführbaren Diagnosen sind enger begrenzt.

## Diagnoseübersicht

| Diagnose | Aussage | Keine Aussage über |
|---|---|---|
| Netzwerk | DNS und TCP-Erreichbarkeit | DICOM-Autorisierung |
| C-ECHO | Association und Verification | andere SOP Classes |
| Capability-Matrix | akzeptierte Presentation Contexts | erfolgreichen C-STORE |
| Worklist C-FIND | technische MWL-Abfrage | klinische Vollständigkeit |
| Study-Root C-FIND | technische Studienabfrage | Retrieve-Fähigkeit |
| C-STORE | Übertragung eines synthetischen Objekts | allgemeine Speicherabnahme |
| Dateianalyse | technische Datei- und Tagstruktur | diagnostische Richtigkeit |

## Sicherheitsgrenzen

Die HNR ist kein PACS, DICOM-Router oder dauerhafter DICOM-Datenspeicher. C-MOVE, C-GET, DICOM-TLS und weitere Dienste sind nicht als aktuell verfügbar dokumentiert. Tests verwenden registrierte Ziele; Patientendaten sind nicht erforderlich.

Vertiefung: [DICOM-Grundlagen](../Healthcare/DICOM.md), [Diagnose-Workspace](../Healthcare/DiagnosticTestWorkspace.md) und [Glossar](../glossary/README.md).
