---
title: Topologie und Tests
description: DICOM-Topologie lesen und kontrollierte Diagnosen ausführen.
document_type: Benutzerhandbuch
chapter: 5
status: draft
version: 0.2
last_updated: 2026-08-08
---

# Topologie und Tests

## Topologie

Die Topologie visualisiert registrierte Systeme, DICOM-Knoten und Beziehungen. Wählen Sie Elemente für Details. Die Ansicht zeigt den dokumentierten Sollzustand und ist kein Live-Monitoring.

## Tests

1. Öffnen Sie **Tests** und wählen Sie einen aktiven registrierten Knoten.
2. Prüfen Sie Zielhost, Port, Called AE und freigegebenen Dienst – diese Angaben sind bereits hinterlegt und müssen nicht erneut eingegeben werden.
3. Wählen Sie „Erreichbarkeit testen" (C-ECHO), „Worklist abfragen" (Modality Worklist), „Testbild senden" (C-STORE), „Abruf testen" (C-MOVE/C-GET), „Speicherbestätigung prüfen" (Storage Commitment), Capability-Matrix oder PACS Query entsprechend Ihrer Berechtigung. Der DICOM-Fachbegriff steht jeweils klein daneben oder in den technischen Details.
4. Begrenzen Sie Suchparameter und bestätigen Sie schreibende Storage-Tests ausdrücklich.
5. Bewerten Sie Ergebnis, Einzelschritte, Dauer und Fehlermeldung im Verlauf.

Ein erfolgreicher TCP-Test beweist keine DICOM-Funktion. Eine akzeptierte Capability-Verhandlung beweist keinen C-STORE. Keine C-FIND-Treffer können technisch erfolgreich sein.

C-STORE erzeugt ein synthetisches Testobjekt, das im Zielsystem verbleiben kann. Seine Bereinigung ist Betreiberaufgabe. Vollständige Grenzen beschreibt der [Diagnose-Workspace](../Healthcare/DiagnosticTestWorkspace.md).

## Worklist testen: Standardansicht

Der Worklist-Test beantwortet die Frage „Bekommt dieses Gerät seine Worklist?", ohne PACS-Fachwissen vorauszusetzen:

- **Zeitraum**: Wählen Sie „Heute" (voreingestellt), „Morgen" oder „Benutzerdefiniert" mit eigenem Von-/Bis-Datum.
- **Modalität**: Ist für den gewählten Knoten bereits eine Modalität hinterlegt (z. B. `DX`), wird sie automatisch angezeigt und verwendet – „DX – vom System übernommen". Ist keine hinterlegt, können Sie optional eine eintragen.
- **Patientenfilter**: Patientenname, Patienten-ID und Accession Number sind optional und schränken die Ergebnisse weiter ein.

Klicken Sie auf **Worklist abfragen**. Das Ergebnis zeigt zuerst verständlich, ob die Abfrage erfolgreich war, wie viele Einträge gefunden wurden und wie lange die Antwort gedauert hat – erst darunter folgt die technische Trefferliste.

### Calling AE Title und Called AE Title

Diese beiden Werte werden häufig verwechselt, bedeuten aber unterschiedliche Dinge:

- **Called AE Title**: Der AE Title des Worklist-Servers, der abgefragt wird. Wird immer automatisch aus dem registrierten Knoten übernommen.
- **Calling AE Title**: Der AE Title, mit dem sich HNR selbst beim Worklist-Server meldet. Er wird mit einem sinnvollen Standardwert vorbelegt, kann aber bei Bedarf angepasst werden.

Beide Werte dürfen identisch sein – das ist in DICOM ausdrücklich zulässig.

### Erweiterte DICOM-Einstellungen

Wer die technischen Parameter direkt sehen oder ändern möchte, öffnet den Abschnitt „Erweiterte DICOM-Einstellungen" im Dialog. Dort stehen Calling AE Title, Called AE Title, Station AE und – falls eine Knoten-Modalität hinterlegt ist – eine Möglichkeit, sie nur für diese eine Abfrage zu überschreiben. Kurze Hilfetexte erklären jedes Feld. Dieser Bereich ist standardmäßig eingeklappt und für die meisten Abfragen nicht nötig.

### Typische Fehlerbilder

- **Verbindung abgelehnt**: Der Worklist-Server läuft möglicherweise nicht auf dem hinterlegten Host/Port.
- **DICOM-Verbindung abgelehnt**: Der Worklist-Server hat die Association abgelehnt – möglicherweise wird der verwendete Calling AE Title dort nicht akzeptiert.
- **Zeitüberschreitung**: Der Worklist-Server antwortet nicht rechtzeitig oder ist über das Netzwerk nicht erreichbar.

In allen Fällen zeigt HNR zuerst eine verständliche Einordnung und, sofern zutreffend, einen ausdrücklich als Vermutung gekennzeichneten Hinweis auf eine mögliche Ursache. Die vollständige technische Meldung von DCMTK bleibt über „Technische Details anzeigen" abrufbar.
