---
title: Synthetische Storage-SOP-Classes
status: Accepted
date: 2026-08-08
---

# ADR-0019: Synthetische Storage-SOP-Classes

## Kontext

Der kontrollierte C-STORE-Diagnosetest konnte ausschließlich ein Secondary-Capture-Objekt senden. Damit ließ sich zwar die grundsätzliche Storage-Verbindung prüfen, nicht aber die Annahme der in Kliniken besonders verbreiteten CT- und MR-Image-Storage-SOP-Classes.

Die Testobjekte dürfen keine echten Patientendaten enthalten, müssen serverseitig kontrolliert erzeugt werden und können im Zielsystem dauerhaft gespeichert oder weitergeleitet werden.

## Entscheidung

Der Storage-Test bietet einen geschlossenen, typisierten Katalog aus:

- Secondary Capture Image Storage
- CT Image Storage
- MR Image Storage

Die Auswahl wird serverseitig als Enum validiert. Ein freier SOP-Class-UID-Parameter wird nicht akzeptiert. `pydicom` erzeugt je Auswahl einen minimalen 1×1-Pixel-Datensatz mit synthetischen Kennungen, UIDs und SOP-Class-spezifischen Pflichtattributen. `storescu` überträgt anschließend die erzeugte Datei über den bestehenden, optional TLS-geschützten Diagnosepfad.

Ergebnis, Verlauf und Audit-Ereignis enthalten die tatsächlich gewählte SOP-Class-UID. Temporäre Dateien und Verzeichnisse werden unabhängig vom Testergebnis gelöscht.

Storage Commitment bleibt bewusst auf Secondary Capture beschränkt, weil dort nicht die Auswahl einer Storage-SOP-Class, sondern die korrelierte N-ACTION-/N-EVENT-REPORT-Kette geprüft wird.

## Folgen

- CT- und MR-Storage-Akzeptanz kann ohne echte klinische Daten kontrolliert getestet werden.
- Die ausdrückliche Bestätigung und das granulare Ausführungsrecht bleiben unverändert erforderlich.
- Ein technischer Erfolg beweist die Annahme des konkreten synthetischen Objekts, nicht die vollständige IOD-Konformität eines Hersteller-Workflows.
- Weitere SOP Classes benötigen eine bewusste Erweiterung des Enums, der Erzeugung und der Tests.

## Verifikation

- Feature-Tests prüfen Ergebnis- und Auditdaten für CT und MR.
- Der native Integrationstest erzeugt alle drei unterstützten Klassen und prüft die lokale Bereinigung.
- Request-Tests lehnen unbekannte SOP-Class-Werte vor der Ausführung ab.
