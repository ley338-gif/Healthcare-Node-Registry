# Globale DICOM-Verbindungsübersicht

Die Seite **Kommunikation → Verbindungen** unter `/connections` zeigt dieselben DICOM-Verbindungen, die auch in den jeweiligen Systemdetails erscheinen. Grundlage ist ausschließlich die bestehende Tabelle `dicom_connections`; es gibt keine separate Datenhaltung für die globale Ansicht.

## Funktionsumfang

- Serverseitig paginierte Liste mit Quell- und Zielsystem, Knoten, effektiven AE Titles, Dienst, Zieladresse, Zielport, Status und letztem Verifikationsstatus des Zielknotens
- Volltextsuche über Verbindungsname, Systeme, Knoten, AE Titles, Host und Port
- Filter nach Quell- und Zielsystem, Dienst, Standort, Abteilung, Status, Zielport und AE Title
- Sortierung nach Name, Quell- und Zielsystem, Dienst, Zielport, Status und letzter Prüfung
- Anlegen und Bearbeiten über den auch in der Systemdetailansicht verwendeten Verbindungsmanager
- Detail-Drawer, Duplizieren und Archivieren
- Übergabe von Zielknoten, Dienst und AE Titles an den vorhandenen Test-Arbeitsbereich

AE Titles und der Zielport können an der Verbindung überschrieben werden. Sind keine Overrides gesetzt, werden die Werte des referenzierten Quell- beziehungsweise Zielknotens verwendet. Der angezeigte Konnektivitätsstatus ist derzeit der letzte Verifikationsstatus des Zielknotens; das Datenmodell führt keinen separaten Teststatus pro Verbindung.

## Berechtigungen und Audit

`registry.view` oder `registry.manage` erlaubt das Anzeigen. Schreibende Aktionen benötigen `registry.manage` und werden serverseitig über `DicomConnectionPolicy` geprüft. Erstellen, Ändern, Duplizieren und Archivieren erzeugen Ereignisse im vorhandenen Security-/Audit-Log.

## HTTP-Endpunkte

- `GET /connections` – globale Liste, Filter, Sortierung und Pagination
- `POST /dicom-connections` – anlegen
- `PUT /dicom-connections/{public_id}` – bearbeiten
- `POST /dicom-connections/{public_id}/duplicate` – duplizieren
- `POST /dicom-connections/{public_id}/archive` – archivieren
- `GET /tests?node=…&service=…&calling_ae_title=…&called_ae_title=…` – vorhandenen Test-Arbeitsbereich vorbefüllen
