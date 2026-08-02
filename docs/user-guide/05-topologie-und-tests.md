---
title: Topologie und Tests
description: DICOM-Topologie lesen und kontrollierte Diagnosen ausführen.
document_type: Benutzerhandbuch
chapter: 5
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Topologie und Tests

## Topologie

Die Topologie visualisiert registrierte Systeme, DICOM-Knoten und Beziehungen. Wählen Sie Elemente für Details. Die Ansicht zeigt den dokumentierten Sollzustand und ist kein Live-Monitoring.

## Tests

1. Öffnen Sie **Tests** und wählen Sie einen aktiven registrierten Knoten.
2. Prüfen Sie Zielhost, Port, Called AE und freigegebenen Dienst.
3. Wählen Sie Netzwerk, C-ECHO, Capability-Matrix, Worklist, PACS Query oder Storage entsprechend Ihrer Berechtigung.
4. Begrenzen Sie Suchparameter und bestätigen Sie schreibende Storage-Tests ausdrücklich.
5. Bewerten Sie Ergebnis, Einzelschritte, Dauer und Fehlermeldung im Verlauf.

Ein erfolgreicher TCP-Test beweist keine DICOM-Funktion. Eine akzeptierte Capability-Verhandlung beweist keinen C-STORE. Keine C-FIND-Treffer können technisch erfolgreich sein.

C-STORE erzeugt ein synthetisches Testobjekt, das im Zielsystem verbleiben kann. Seine Bereinigung ist Betreiberaufgabe. Vollständige Grenzen beschreibt der [Diagnose-Workspace](../Healthcare/DiagnosticTestWorkspace.md).
