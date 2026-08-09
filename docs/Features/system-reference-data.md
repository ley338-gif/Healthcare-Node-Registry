# Referenzdaten für Systemtypen, Systemstatus und Hersteller

Unter **Einstellungen > Referenzdaten** können Benutzer mit der Berechtigung `settings.manage` die Auswahllisten für Systemtypen, Systemstatus und Hersteller pflegen.

## Verhalten

- Der technische Code wird bei der Anlage festgelegt und bleibt danach unveränderlich.
- Anzeigename und Sortierreihenfolge können geändert werden.
- Deaktivierte Werte bleiben an bestehenden Systemen sichtbar.
- Neue Systeme, CSV-Importe und Discovery-Übernahmen akzeptieren nur aktive Werte.
- Die Statuswerte active und retired sind für Anlage beziehungsweise Archivierung technisch erforderlich und können nicht deaktiviert werden.
- Referenzwerte werden nicht physisch gelöscht.
- Anlage und Änderung werden als administrative Referenzdatenereignisse auditiert.

## Standardwerte

Die Systemreferenz-Migration legt die bisher vorhandenen Systemtypen und Statuswerte an. Bei einem Update werden zusätzlich alle abweichenden Codes aus vorhandenen Systemdatensätzen übernommen, bevor die Datenbank-Fremdschlüssel aktiviert werden.

Für Hersteller werden keine erfundenen Standardwerte angelegt. Bei einem Update wird jeder vorhandene nichtleere Herstellertext unverändert als technischer Code und Anzeigename übernommen. Eine frische Installation beginnt daher mit einer leeren Herstellerliste; Administratoren legen die benötigten Werte vor der ersten Zuordnung an.

Der Systemexport und die Oberflächen zeigen den Anzeigenamen. Im CSV-Import kann beim Feld `vendor` entweder der aktive Code oder der Anzeigename verwendet werden.

## Abgrenzung

Produkte, DICOM-Dienste und Dokumentkategorien sind nicht Bestandteil dieser Verwaltung. Discovery-Portprofile werden separat unter den Discovery-Einstellungen gepflegt. Produkt und Modell bleiben Freitextfelder.
