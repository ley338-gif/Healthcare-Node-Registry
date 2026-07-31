# DICOM Domain Guidance

## Modellierungsgrundsätze

Ein Gerät, ein logisches System und eine DICOM Application Entity sind nicht zwangsläufig dasselbe.

Ein DICOM-Endpunkt kann enthalten:

- AE Title
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
