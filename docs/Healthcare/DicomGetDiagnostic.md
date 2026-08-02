# C-GET-Diagnose

Die globale Verbindungsübersicht kann dokumentierte C-GET-Pfade kontrolliert testen. Zielknoten und effektive AE Titles stammen aus der vorhandenen `DicomConnection`.

Benutzer können keine Patientenkennung oder UID eingeben. Der Runner verwendet ausschließlich `DIAGNOSTIC_GET_TEST_STUDY_UID` (Standard: `1.2.826.0.1.3680043.10.987.999.2`). Vor der Ausführung ist eine ausdrückliche Bestätigung als autorisierter Test erforderlich und wird mit Test-ID, Benutzer, Verbindung, Ziel, UID und Ergebnis im Audit protokolliert.

`getscu` speichert empfangene Testobjekte in einem zugriffsgeschützten temporären Verzeichnis. Sämtliche Dateien und das Verzeichnis werden auch bei Timeout oder Fehler im `finally`-Pfad gelöscht. Das Ergebnis wird als `DiagnosticTestRun` mit `test_type = dicom_get` gespeichert.

Endpunkt: `POST /tests/get/{dicomConnection}`
