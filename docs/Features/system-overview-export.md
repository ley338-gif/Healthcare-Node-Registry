# Export der System- und Knotenübersicht

Die Systemübersicht unter `/systems` kann über die Aktionen **Excel** und **PDF** exportiert werden. Beide Exporte verwenden die aktuell gesetzten Filter für Suche, Systemtyp, Status, Organisation, Standort und Abteilung. Die Auswahl eines einzelnen Systems beeinflusst den Export nicht.

## Inhalt

Der Bericht enthält alle passenden, nicht archivierten Systeme. Für jeden aktiven DICOM-Knoten entsteht eine eigene Zeile; Systeme ohne DICOM-Knoten bleiben als Zeile mit leeren DICOM-Feldern enthalten.

- Organisationskontext und Systemstammdaten
- Hostname, FQDN und IP-Adresse
- Hersteller-, Produkt-, Versions- und Betriebssystemangaben
- Inventar- und Seriennummer
- DICOM-Knoten, AE Title, Modalität, Host, Port, Rolle, Status und TLS-Metadatum

## Formate

- XLSX: vollständige Spaltenauswahl, fixierte Kopfzeile und Autofilter; als echte Office-Open-XML-Arbeitsmappe erzeugt
- PDF: kompakte, paginierte A4-Querformatübersicht für Betriebsdokumentation

XLSX und PDF werden ohne zusätzliche Composer-Abhängigkeit erzeugt. Der PDF-Bericht verwendet denselben generischen Tabellenexporter wie die Firewall- und Portmatrix.

## Berechtigung und Sicherheit

Der Export verwendet die bestehende `viewAny`-Policy für Systeme. Nicht berechtigte Benutzer erhalten keinen Export. Antworten werden als Download mit festem Dateinamen, korrektem MIME-Typ und `X-Content-Type-Options: nosniff` ausgeliefert.
