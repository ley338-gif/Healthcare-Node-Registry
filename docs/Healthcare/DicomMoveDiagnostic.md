# C-MOVE-Diagnose

Die Healthcare Node Registry kann einen dokumentierten C-MOVE-Verbindungspfad kontrolliert testen. Der Test wird in der globalen Verbindungsübersicht bei aktiven Verbindungen mit dem Dienst `move` gestartet.

## Sicherheitsgrenzen

- Zielknoten und Move-Destination stammen ausschließlich aus der bestehenden `DicomConnection`.
- Eine C-MOVE-Verbindung benötigt `destination_dicom_node_id`.
- Benutzer können weder Patientenkennungen noch Study-/Series-UIDs eingeben.
- Verwendet wird ausschließlich `DIAGNOSTIC_MOVE_TEST_STUDY_UID`. Der Standardwert `1.2.826.0.1.3680043.10.987.999.1` liegt im projektspezifischen synthetischen UID-Bereich.
- Die Ausführung erfordert die ausdrückliche Bestätigung „Ich bestätige, dass dies ein autorisierter Test ist“.
- Bestätigung, Benutzer, Verbindung, Test-ID, Status, Zielknoten, Move-Destination und Test-Study-UID werden im vorhandenen Audit-System protokolliert.

Auch eine synthetische UID kann einen Transfer auslösen, falls unter dieser UID bewusst ein Testdatensatz im Query/Retrieve-SCP vorbereitet wurde. Deshalb darf C-MOVE nur mit freigegebenen Testdaten und einer korrekt konfigurierten Move-Destination ausgeführt werden.

## Technische Ausführung

Der Runner verwendet das im App-Container vorhandene DCMTK-Werkzeug `movescu` mit Study-Root-Abfragemodell, `QueryRetrieveLevel=STUDY`, der konfigurierten Test-UID sowie Calling-, Called- und Move-Destination-AE-Title. Ergebnisse werden als `DiagnosticTestRun` mit `test_type = dicom_move` gespeichert.

Endpunkt: `POST /tests/move/{dicomConnection}`
