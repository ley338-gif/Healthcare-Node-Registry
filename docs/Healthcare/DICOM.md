# DICOM Domain Guidance

## Modellierungsgrundsätze

Ein Gerät, ein logisches System und eine DICOM Application Entity sind nicht zwangsläufig dasselbe.

Ein DICOM-Endpunkt kann enthalten:

- AE Title
- Modalität (optional, z. B. `DX`, `CT`, `MR`; dient der automatischen Vorbelegung im Worklist-Test, siehe [Diagnostic Test Workspace](DiagnosticTestWorkspace.md))
- Hostname und/oder IP
- Port
- lokale/entfernte Rolle
- unterstützte oder dokumentierte Dienste
- Called/Calling-Kontext
- Beschreibung und Quelle der Information
- Verifizierungsstatus und Datum

## Dienste

Mögliche fachliche Services:

- Verification (C-ECHO)
- Storage (C-STORE)
- Query/Retrieve
- Modality Worklist
- MPPS
- Storage Commitment
- Print
- IOCM-bezogene Workflows

SCU/SCP wird pro Dienst modelliert. Eine allgemeine Checkbox „SCU“ reicht nicht.

## Datenqualität

Jeder fachlich kritische Wert sollte eine Herkunft besitzen:

- manuell erfasst
- Herstellerdokument
- Installationsprotokoll
- Import
- später aktiv verifiziert

## Datenschutz

Die Registry speichert keine hochgeladenen DICOM-Dateien und benötigt keine echten Patientendaten. Der kontrollierte Storage-Test erzeugt kurzzeitig ein synthetisches Secondary-Capture-Objekt und kann dieses nach ausdrücklicher Bestätigung an ein Zielsystem senden. Dateianalysen und lokale Testartefakte werden nach Verarbeitung gelöscht. Details: [Diagnostic Test Workspace](DiagnosticTestWorkspace.md).

## Discovery-C-ECHO gegenüber verifizierten Knoten

Der Diagnose-Workspace verifiziert bekannte, bereits registrierte `DicomNode`-Einträge mit dem Calling-AE-Titel aus `config('diagnostics.default_calling_ae_title')` (Standard weiterhin `NODE_REGISTRY`, per `DIAGNOSTIC_CALLING_AE_TITLE` zentral konfigurierbar). Das DICOM-Discovery-Modul (siehe [dicom-discovery.md](../Features/dicom-discovery.md)) ist davon technisch getrennt: Es testet unbekannte Host/Port/AE-Kombinationen mit konfigurierbaren Calling- und Called-AE-Titel-Kandidaten und dem eigenständigen Standard-Calling-AE `HNR_DISCOVERY` (`config('discovery.default_calling_ae_title')`). Beide Pfade nutzen DCMTK `echoscu` mit fester Argumentliste, teilen sich aber bewusst keine Konfiguration und keinen Code, damit Discovery-Änderungen die produktive Node-Verifizierung nicht beeinflussen können.
