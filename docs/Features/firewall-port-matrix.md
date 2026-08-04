# Firewall- und Portmatrix

Der Read-only-Report unter `/reports/firewall-matrix` aggregiert alle aktiven DICOM-Verbindungen. Er zeigt den Kommunikationspfad von Quelle zu Ziel, den effektiv verwendeten Zielport, den dokumentierten DICOM-Dienst, AE Titles und den TLS-Status.

## Filter

Die Ansicht und beide Exporte unterstützen dieselben Filter:

- Organisation
- Standort
- Abteilung
- System – als Quelle oder Ziel
- DICOM-Dienst

Organisationsfilter treffen eine Verbindung, wenn der Quell- oder Zielkontext dem Filter entspricht. Der Zielport ist `port_override`, sofern an der Verbindung gesetzt, andernfalls der Port des Zielknotens.

## Exporte

- CSV: UTF-8 mit BOM, geeignet für Tabellenkalkulationen und technische Weiterverarbeitung
- PDF: paginierte A4-Querformat-Tabelle für Freigabe- und Betriebsdokumentation

Die Exporte verwenden exakt die aktuell gesetzten Filter. Es wurde keine zusätzliche PDF-Abhängigkeit eingeführt; der begrenzte Tabellenbericht wird durch einen projektspezifischen PDF-Exporter erzeugt.

## Berechtigung

Ansicht und Export verwenden dieselbe `viewAny`-Policy wie die bestehende DICOM-Verbindungsübersicht. Es entsteht kein paralleler Berechtigungsmechanismus.
