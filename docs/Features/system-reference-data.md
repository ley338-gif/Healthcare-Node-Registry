# Referenzdaten für Systemtypen und Systemstatus

Unter **Einstellungen > Referenzdaten** können Benutzer mit der Berechtigung settings.manage die Auswahllisten für Systemtypen und Systemstatus pflegen.

## Verhalten

- Der technische Code wird bei der Anlage festgelegt und bleibt danach unveränderlich.
- Anzeigename und Sortierreihenfolge können geändert werden.
- Deaktivierte Werte bleiben an bestehenden Systemen sichtbar.
- Neue Systeme, CSV-Importe und Discovery-Übernahmen akzeptieren nur aktive Werte.
- Die Statuswerte active und retired sind für Anlage beziehungsweise Archivierung technisch erforderlich und können nicht deaktiviert werden.
- Referenzwerte werden nicht physisch gelöscht.
- Anlage und Änderung werden als administrative Referenzdatenereignisse auditiert.

## Standardwerte

Die Migration legt die bisher vorhandenen Systemtypen und Statuswerte an. Bei einem Update werden zusätzlich alle abweichenden Codes aus vorhandenen Systemdatensätzen übernommen, bevor die Datenbank-Fremdschlüssel aktiviert werden.

## Abgrenzung

Hersteller, Produkte, DICOM-Dienste, Dokumentkategorien und Discovery-Portprofile sind nicht Bestandteil dieser Verwaltung. Freitextfelder wie Hersteller und Produkt bleiben unverändert.
