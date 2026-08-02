# MPPS-Diagnose

Die MPPS-Diagnose prüft die im DICOM-Knoten konfigurierte Modality Performed Procedure Step SOP Class mit einem echten N-CREATE und einem anschließenden N-SET auf `COMPLETED`.

## Sicherheit und Daten

- Der Benutzer muss die Autorisierung vor jedem Lauf ausdrücklich bestätigen.
- Der Knoten muss die Fähigkeit `MPPS` aktiviert haben.
- Der Test erzeugt ausschließlich synthetische Identifikatoren unter dem projektspezifischen UID-Präfix.
- Es werden keine Patientendaten abgefragt oder als Eingabe akzeptiert.
- Ausführung, Ergebnis und erzeugte MPPS-UID werden im Testverlauf und Audit protokolliert.

Der Test verändert das Zielsystem, weil dort eine synthetische MPPS-Instanz angelegt und abgeschlossen wird. Er darf daher nur gegen dafür freigegebene Systeme verwendet werden.

## Implementierung

Das App-Image installiert `pynetdicom==3.0.4` und `pydicom==3.0.1` mit festgelegten SHA-256-Hashes. Laravel übergibt Host, Port und AE Titles per JSON über die Standardeingabe an `scripts/dicom_mpps.py`; sensible Werte erscheinen dadurch nicht in der Prozessliste. Der Prozess besitzt ein Zeitlimit von 30 Sekunden.

