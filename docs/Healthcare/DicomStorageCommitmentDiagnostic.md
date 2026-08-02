# Storage-Commitment-Diagnose

Die Diagnose speichert zunächst ein synthetisches Secondary-Capture-Objekt per C-STORE. Anschließend fordert sie für genau diese SOP Instance mit dem Storage Commitment Push Model per N-ACTION die sichere Verwahrung an. Erfolg liegt erst vor, wenn ein zugehöriger N-EVENT-REPORT das Objekt bestätigt.

## Callback-Konfiguration

Storage Commitment ist asynchron. Der App-Container lauscht während des Tests auf Port `11113`. Das Zielsystem muss den verwendeten Calling AE Title – standardmäßig `NODE_REGISTRY` – auf die erreichbare IP des Docker-Hosts und diesen Port zurückrouten können. Docker veröffentlicht den Port standardmäßig als `11113`; der Host-Port ist mit `DIAGNOSTIC_STORAGE_COMMITMENT_HOST_PORT` änderbar.

```env
DIAGNOSTIC_STORAGE_COMMITMENT_CALLBACK_PORT=11113
DIAGNOSTIC_STORAGE_COMMITMENT_HOST_PORT=11113
DIAGNOSTIC_STORAGE_COMMITMENT_EVENT_TIMEOUT=30
```

Wenn das Ziel den N-EVENT-REPORT auf derselben Association sendet, wird dieser ebenfalls verarbeitet. Nur ein Storage-Commitment-Test kann gleichzeitig laufen, damit Calling AE Title und Callback-Port eindeutig bleiben.

## Sicherheit

- Ausführung nur mit `tests.run.storage`, Knoten-Policy und aktivierter Capability `supports_storage_commitment`.
- Explizite Bestätigung vor jedem Lauf.
- Ausschließlich synthetisches Secondary Capture ohne echte Patientendaten.
- Transaction UID und SOP Instance UID werden serverseitig erzeugt.
- Audit-Eintrag und persistierter Diagnoseverlauf für jeden abgeschlossenen Lauf.
- Das Zielobjekt kann dauerhaft im Zielsystem verbleiben; die Bereinigung ist Betreiberaufgabe.

DCMTK enthält im verwendeten Image kein vollständiges CLI für N-ACTION plus asynchronen N-EVENT-REPORT. Die Umsetzung nutzt daher die bereits gepinnte und lizenzgeprüfte `pynetdicom`-Laufzeit.
