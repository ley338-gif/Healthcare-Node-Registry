# Registry-Historie

Die Tabs „Historie“ in Systemen und Organisationsstruktur verwenden dieselbe Audit-Abfrage und dieselbe Vue-Komponente.

## Funktionen

- Kennzahlen für Gesamt, heute, 7 und 30 Tage
- Filter für Zeitraum, Ereignistyp, Benutzer, Status und Suchtext
- serverseitige Pagination
- direkte oder untergeordnete Ereignisse in der Organisationsstruktur
- Detail-Slide-over mit ausschließlich geänderten Vorher-/Nachher-Feldern
- einklappbare technische Metadaten
- zentrale menschenlesbare Bezeichnung betroffener Entitäten

Die Systemhistorie umfasst auch DICOM-Knoten, Verbindungen, Diagnoseereignisse und Dokumentationsänderungen. Organisations-, Standort- und Abteilungshistorien übernehmen diese Ereignisse entsprechend ihrer Hierarchie.

## Bekannte Einschränkungen

- Ereignisse können nur Informationen darstellen, die beim Schreiben als sichere Metadaten erfasst wurden.
- Historische Entitätsnamen werden derzeit nicht als vollständiger Snapshot gespeichert.
- Export, Aufbewahrungssteuerung und Integritätsnachweis folgen mit dem globalen Audit-Modul.

