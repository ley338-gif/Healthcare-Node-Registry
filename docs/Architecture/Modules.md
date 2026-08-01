# Modules

## Identity & Access

Benutzer, Rollen, Berechtigungen, Sessions, MFA-Fähigkeit und spätere Verzeichnisanbindung.

## Organizations & Sites

Organisationen, Standorte, Gebäude, Abteilungen, Teams und Verantwortlichkeiten. Organisationen sind im MVP fachliche Objekte, keine technischen SaaS-Tenants.

## Assets

Physische oder logische Systeme wie Modalität, PACS, RIS, Archiv, Viewer oder KI-System.

## Endpoints

Konkrete Kommunikationsendpunkte. Generische Basisdaten werden durch protokollspezifische Detailmodelle ergänzt.

## DICOM

DICOM Application Entities, Dienste, Rollen, Called/Calling AE und fachliche Validierung.

Der Diagnosebereich führt kontrollierte Netzwerk-, C-ECHO-, C-FIND-, C-STORE- und Association-Tests ausschließlich gegen registrierte Endpunkte aus. Gemeinsame Ergebnis-DTOs, persistierte Testläufe und Runner-Interfaces liegen unter `App\Services\Diagnostics`. Details: [Diagnostic Test Workspace](../Healthcare/DiagnosticTestWorkspace.md).

## Connections

Gerichtete Beziehungen zwischen konkreten Endpoints mit Dienst, Zweck, Informationsquelle, Dokumentationsstatus und Soll-Nutzung.

## Topology

Abfrage und Visualisierung von Assets, Endpoints und Connections. Keine zweite fachliche Datenhaltung und kein Monitoring im MVP.

## Documents

Dokumentenmetadaten, Version, Kategorie, Hash, Storage-Referenz, Quarantäne-/Scanstatus und Berechtigungen.

Die implementierte Ablage, ihre Sicherheitsgrenzen sowie Backup- und Restore-Anforderungen sind in [Architektur der Registry-Dokumentenablage](registry-document-storage.md) beschrieben.

## Taxonomy

Kontrollierte System-, Endpoint-, Dienst-, Status- und Dokumenttypen.

## Audit

Append-only-Nachweise relevanter fachlicher und administrativer Aktionen.

## Import/Export

Validierte, nachvollziehbare und berechtigungsgeprüfte Datenübernahme und Ausgabe.

## Administration

Systemeinstellungen, Aufbewahrung, Diagnose und Betriebsinformationen ohne Secrets oder Patientendaten.
