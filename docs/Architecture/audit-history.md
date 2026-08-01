# Audit- und Historienarchitektur

## Gemeinsame Datenbasis

Fachliche Registry-Änderungen, Diagnosen und Dokumentationsänderungen werden append-only in `security_events` gespeichert. `App\Support\RegistryAudit` ist der einzige Schreibweg für diese Ereignisse. System- oder Organisationshistorien besitzen keine eigenen Tabellen.

`RegistryHistoryService` erzeugt berechtigungsgeprüfte Basisabfragen. `forContext()` begrenzt Ereignisse auf eine Registry-Entität und optional ihre Untereinheiten. `global()` stellt dieselbe Query-Grundlage für einen späteren Audit-Explorer bereit und erfordert `audit.view`.

`RegistryHistoryViewService` wendet auf beide Query-Arten dieselben Filter an, berechnet Kennzahlen, löst Benutzer auf und paginiert serverseitig. `RegistryAuditEntityResolver` übersetzt technische Subject-Klassen zentral in UI-Kontexte.

## Kontextauflösung

- Organisation: Organisation, Standorte, Abteilungen, Systeme, DICOM-Knoten und Verbindungen
- Standort: Standort, Abteilungen, Systeme, DICOM-Knoten und Verbindungen
- Abteilung: Abteilung, Systeme, DICOM-Knoten und Verbindungen
- System: System, DICOM-Knoten und Verbindungen
- DICOM-Knoten: Knoten und zugehörige Verbindungen
- DICOM-Verbindung: nur die Verbindung

Bei „Nur direkte Änderungen“ wird ausschließlich die ausgewählte Entität abgefragt. Die Standardauswahl schließt Untereinheiten ein.

## Berechtigungen und Datenschutz

Kontextabfragen verwenden die vorhandene `view`-Policy der Entität. Die globale Abfrage verlangt `audit.view`. Audit-Daten sind über die UI nicht veränderbar.

Dokumentations-Langtexte und strukturierte Daten werden im Audit nicht vollständig gespeichert. Stattdessen werden geänderte Feldnamen sowie Länge und SHA-256 abgelegt. Patientendaten dürfen nicht als Audit-Metadaten geschrieben werden.

## Vorbereitung des globalen Audit-Explorers

Eine `/audit`-Route ist noch nicht implementiert. Eine spätere Seite verwendet `RegistryHistoryService::global()`, `RegistryHistoryViewService::applyFilters()` beziehungsweise `present()`, `RegistryAuditEntityResolver` und `AuditHistoryPanel.vue`. Dadurch bleibt die globale Ansicht eine weitere Projektion derselben Datenbasis.

