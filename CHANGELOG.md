# Changelog

## Unreleased

- Produktiven ClamAV-Malware-Scanner für Registry-Dokumente ergänzt: interne ClamD-`INSTREAM`-Anbindung, fail-closed Fallback, persistente Signaturdaten, Healthcheck, stündlicher Rescan und Auditierung der Rescan-Ergebnisse.
- Modality-Worklist-Test benutzerfreundlich überarbeitet: Standardansicht mit Zeitraum-Presets (Heute/Morgen/Benutzerdefiniert) und automatisch aus dem registrierten Knoten übernommener Modalität, Patienten- und AE-Title-Parameter in einem standardmäßig eingeklappten Bereich „Erweiterte DICOM-Einstellungen" mit Hilfetexten; verständliche Ergebnis- und Fehlerdarstellung mit klar gekennzeichneter „Mögliche Ursache" oberhalb der weiterhin vorhandenen technischen Details. Zentrale, konfigurierbare Calling-AE-Titel-Einstellung (`DIAGNOSTIC_CALLING_AE_TITLE`) für alle Node-Diagnosen ergänzt; optionales `modality`-Feld für DICOM-Knoten hinzugefügt. Gleiches Namensprinzip (fachliche Bezeichnung primär, DICOM-Fachbegriff sekundär) auf C-ECHO, C-STORE, C-MOVE/C-GET und Storage Commitment angewendet.
- DICOM Discovery MVP ergänzt: geführter Wizard, asynchroner Netzwerk-Scan (Ping/Reverse-DNS, TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests) über einen neuen `worker`-Queue-Container, regelbasierte Klassifizierung mit Confidence-Score, Review-Queue mit Filtern und Detail-Drawer, Duplikaterkennung bei der Übernahme in die System-Registry, Administrator-verwaltete Freigabeliste erlaubter Netzbereiche und vollständige Audit-Protokollierung.
- Bestehende Topologie um einen Nachweis-Status (`dicom_connections.evidence_status`: bestätigt/technisch getestet/vermutet/manuell dokumentiert/zuletzt fehlgeschlagen) und entsprechende Linienstile in der Netzwerkkarte erweitert.
- `systems`-Tabelle um optionale Felder `responsible` und `criticality` ergänzt (aktuell nur über die Discovery-Übernahme setzbar, siehe `docs/limitations.md`).
- Autorisierten C-MOVE-Diagnosetest mit `movescu`, synthetischer serverseitiger Study-UID, Pflichtbestätigung, Testhistorie und Audit-Protokollierung ergänzt.
- Autorisierten C-GET-Diagnosetest mit `getscu`, synthetischer serverseitiger Study-UID, sicherer temporärer Dateibereinigung, Testhistorie und Audit-Protokollierung ergänzt.
- Globale, serverseitig filter- und sortierbare Übersicht aller DICOM-Verbindungen unter `/connections` ergänzt.
- Bestehenden DICOM-Verbindungsmanager um Details, Duplizieren und Übergabe an den Test-Arbeitsbereich erweitert.
- Navigation, Berechtigungsprüfungen, Audit-Ereignis für Duplikate, Tests und Funktionsdokumentation ergänzt.

## Unreleased

### Maintenance

- Removed accidentally committed backup and static-analysis output artifacts.
- Added ignore rules for backup files and local PHPStan output.
- Corrected the GitHub Actions repository-check job structure.

- Globale, berechtigungsgepruefte Suche in der Kopfzeile fuer Registry-Struktur, Systeme, DICOM, Dokumente, Tests und Benutzer ergaenzt.
- Einstellungen um eine berechtigungsgepruefte Benutzer-, Rollen- und Berechtigungsverwaltung mit Suche, Status- und Rollenfiltern erweitert.
- Passwortvorgaben zentralisiert, Sitzungswiderruf bei Passwortwechsel und Deaktivierung sowie Schutz des letzten aktiven Systemadministrators ergaenzt.
- Benutzer-, Rollen-, Login- und Logout-Aktionen in die bestehende Audit-Infrastruktur und die Ereignisgruppe Benutzer integriert.

- Audit-Tabelle um kompakte Vorher-/Nachher-Werte, zentrale Ereignisgruppen und zustandsabhängige Deep Links in Registry-, DICOM-, Dokument- und Test-Workspaces erweitert.
- Zentralen, read-only Audit-Arbeitsbereich mit Berechtigungsprüfung, serverseitiger Filterung, 50er-Paginierung, Slide-over-Details und CSV-Export ergänzt.

## [Unreleased]

### Registry-Historie und Dokumentation

- gemeinsame Registry-Historie für Systeme, Organisationen, Standorte und Abteilungen
- serverseitige Audit-Filter, Kennzahlen, Pagination und Detailansicht
- strukturierte polymorphe Betriebsdokumentation mit kontextspezifischen Sektionen
- nachvollziehbarer Dokumentationsstand auf Basis definierter Pflichtfelder
- Audit-Ereignisse für Dokumentationsänderungen ohne vollständige Langtexte
- zentrale Audit-Filterung und Entity-Auflösung als Vorbereitung der globalen Audit-Seite

- private Dokumentenablage für Organisationen, Standorte, Abteilungen und Systeme
- unveränderliche Dateiversionen mit aktueller Version, SHA-256 und Duplikaterkennung
- zentrale Dokumentkategorien, Gültigkeitsstatus, Suche, Filter und serverseitige Pagination
- serverseitige Datei-Allowlist mit MIME- und Signaturprüfung sowie konfigurierbarem Größenlimit
- Malware-Scanner-Schnittstelle mit Fail-Closed-Zugriff für nicht saubere Versionen
- berechtigungsgeprüfter Download und abgesicherte PDF-Vorschau ohne öffentliche Storage-URL
- Audit-Ereignisse für Upload, Versionierung, Metadaten, Archivierung und Scanfehler

### Added

- Diagnose-Workspace mit standardisierter Ergebnisarchitektur und persistentem Verlauf
- echte Netzwerk-, C-ECHO-, Worklist- und PACS-C-FIND-Tests gegen registrierte Knoten
- kontrollierter synthetischer C-STORE mit Bestätigung, strengem Recht und Audit
- SOP-Class-/Transfer-Syntax-Capability-Matrix ohne C-STORE
- wiederverwendbare Testprofile und Dashboard-Diagnosestatus
- serverseitige DICOM-Dateianalyse mit automatischer temporärer Bereinigung
- bereinigter JSON- und CSV-Export von Diagnoseergebnissen
- gemeinsame Übersichtsseite für Organisationsstruktur
- gruppierte Registry-Navigation
- produktorientiertes Dashboard ohne erfundene Monitoringdaten
- letzte Registry-Änderungen aus Security Events

### Changed

- bestehende C-ECHO-Verifikation in die gemeinsame Diagnoseergebnis- und Verlaufsarchitektur integriert
- Dashboard um berechtigungsgeprüfte Diagnosekennzahlen ergänzt
- Organisationen, Standorte und Abteilungen unter einem Navigationsbereich gebündelt
- Systeme als nächstes zentrales Registry-Objekt hervorgehoben
- geplante Module klar von verfügbaren Funktionen getrennt
