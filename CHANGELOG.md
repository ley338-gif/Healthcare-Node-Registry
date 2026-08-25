# Changelog

## Unreleased

- Verbindliche Supply-Chain-Gates ergänzt: vollständiger Gitleaks-Historiencheck, Trivy-Scans beider Release-Images auf behebbare hohe und kritische Befunde sowie CycloneDX-SBOM-Artefakte; PHP-, Nginx- und `pydicom`-Laufzeiten auf gepinnte, gepatchte Stände angehoben und nicht benötigte Python-Installationswerkzeuge aus der Runtime entfernt.

- Unveränderlichen Release-Build für den Pilotbetrieb ergänzt: Anwendungscode, produktive Composer-Abhängigkeiten und Frontend-Artefakte werden in getrennte App- und Webimages eingebaut; die Release-Compose-Überlagerung entfernt produktive Quellcode-Bind-Mounts und dokumentiert Installation, Upgrade und Rollback.

- Gefilterten, berechtigungsgeprüften und auditierten PDF-Export der Discovery-Ergebnisse und Review-Queue auf Basis der gemeinsamen Host-Abfrage und des vorhandenen mehrseitigen PDF-Tabellenkerns ergänzt.

- Kontrollierten CSV-/XLSX-Import von AE-Titel-Kandidaten im Discovery-Wizard ergänzt: versioniertes Schema, serverseitige Vorschau mit Zeilenfehlern und Duplikatwarnungen, Formel- und XML-Härtung sowie ausdrückliche Übernahme ohne automatischen Scanstart.

- Deduplizierte In-App-Benachrichtigungen für aktive Discovery-Benutzer ergänzt: vollständig erfolgreiche Zeitplanläufe werden mit ihrem vorherigen erfolgreichen Stand verglichen und melden neue oder verschwundene Hosts auf dem Dashboard; unvollständige und manuelle Läufe bleiben zur Vermeidung von Fehlalarmen ausgeschlossen.

- Dokumentfreigabe um einen eng begrenzten, im Audit gekennzeichneten Systemadministrator-Override ergänzt: Die Rolle `system-administrator` darf eigene wartende und malwareseitig saubere Versionen freigeben; Selbstablehnung und alle übrigen Prüfungen bleiben gesperrt beziehungsweise verpflichtend.

- Manuellen Vergleich vollständig abgeschlossener Discovery-Läufe desselben Zielbereichs ergänzt; neue, verschwundene, technisch veränderte und unveränderte Hosts werden anhand stabiler Scanmerkmale nachvollziehbar gegenübergestellt.

- Tägliche und wöchentliche Discovery-Zeitpläne mit expliziter Zeitzone, pausierbarer Dashboard-Verwaltung, minütlicher Scheduler-Auswertung, Überlappungsschutz, erneuter Netzfreigabeprüfung vor jedem Lauf und vollständigem Audit ergänzt.

- Gefilterten, berechtigungsgeprüften CSV- und JSON-Export der Discovery-Ergebnisse und Review-Queue mit Audit-Ereignis und Tabellenkalkulationsschutz ergänzt.

- Anwendungsspezifischen Queue-Worker-Healthcheck ergänzt: Queue-Loops aktualisieren einen inhaltsarmen Heartbeat, Docker prüft dessen Aktualität und die Datenbankverbindung, und Betrieb sowie Fehlerdiagnose sind dokumentiert.

- Discovery-Wiederholungsversuche vollständig aktiviert: fehlgeschlagene Ping-, TCP-Port- und DICOM-C-ECHO-Prüfungen werden gemäß Wizard-Konfiguration begrenzt wiederholt, erfolgreiche Ziele ausgespart und die maximale Prüfungsanzahl vor dem Start transparent berechnet.

- Administrierbare Discovery-Portprofile ergänzt: aktive Profile stehen im Scan-Assistenten als Vorlagen bereit, das bisherige Portset wird als Standardprofil migriert und jeder Lauf speichert weiterhin eine unabhängige Portkopie; Änderungen werden auditiert.

- Hersteller als fremdschlüsselgesicherte, auditierte Referenzdaten ergänzt; vorhandene Herstellertexte werden verlustfrei übernommen, neue Zuordnungen verwenden aktive Auswahllisten und CSV-Importe lösen Code oder Anzeigename kontrolliert auf.
- Systemtypen und Systemstatus als fremdschlüsselgesicherte, auditierte Referenzdaten mit zentraler Verwaltungsoberfläche, Bestandsschutz für deaktivierte Werte sowie einheitlicher Validierung in Systempflege, CSV-Import und Discovery umgesetzt.
- Systemkritikalität in Anlage, Bearbeitung, Detailansicht, Listenkennzeichnung, Filter und Export integriert; Änderungen werden über das bestehende Registry-Audit mit Vorher-/Nachher-Werten protokolliert.
- Verpflichtende TOTP-MFA für lokale privilegierte Konten ergänzt: berechtigungsbasierte Erzwingung, lokale QR-Code-Einrichtung ohne externen Dienst, verschlüsselte Secrets, einmalige gehashte Recovery-Codes, Replay- und Rate-Limit-Schutz, Session-Widerruf bei Rollen-/Rechteänderungen, administrativer und konsolenbasierter Reset sowie vollständiges Audit.
- Optionales HTTPS-Referenz-Deployment für Docker Compose mit betreiberverwalteten Zertifikaten, HTTP-zu-HTTPS-Weiterleitung, TLS-1.2/1.3-Härtung, HSTS und CI-validierter Nginx-Konfiguration ergänzt; der lokale HTTP-Entwicklungsstart bleibt verfügbar.
- PowerShell-Backup und -Restore um verbindliche Docker-Exitcode-, Dump- und Archivprüfung, sichere Wiederherstellung über ein Staging-Verzeichnis sowie fail-closed Webdienstverhalten bei Restore-Fehlern gehärtet; privates Storage-Verzeichnis wird bereits beim Containerstart angelegt.
- Product Book mit dem implementierten Stand von DICOM-Diagnosen und -TLS, ClamAV, Dokumentfreigabe und -aufbewahrung, Audit-Integrität sowie Systemverantwortlichkeit synchronisiert und verbleibende Grenzen präzisiert.
- Kontrollierten synthetischen C-STORE um auswählbare Secondary-Capture-, CT- und MR-Image-Storage-Objekte mit serverseitiger SOP-Class-Validierung, korrekten Auditdaten und rückstandsfreier lokaler Erzeugung erweitert.
- Optionale betriebliche Verantwortlichkeit von Systemen in Anlage, Bearbeitung, Detailansicht, Suche und XLSX-Export eingebunden; Änderungen werden mit Vorher-/Nachher-Wert im bestehenden Registry-Audit protokolliert.
- DICOM-TLS für alle Diagnose-Runner ergänzt: benannte, schreibgeschützt eingebundene Zertifikatsprofile, gegenseitige Zertifikatsprüfung für DCMTK/Python/PHP, TLS-geschützter Storage-Commitment-Callback und fail-closed Verhalten ohne Klartext-Fallback.
- Versionsbezogenen Dokumentfreigabe-Workflow mit serverseitigem Vier-Augen-Prinzip, separatem Freigaberecht, Audit-Ereignissen, Veröffentlichung erst nach Freigabe sowie konfigurierbaren Mindestaufbewahrungsfristen je Kategorie ergänzt.
- Audit-Trail kryptografisch über eine kanonische SHA-256-Hash-Chain mit gespeichertem Kettenkopf abgesichert; Verifikations- und kontrollierten Prune-Befehl, siebenjährig vorkonfigurierte betreiberabhängige Aufbewahrung, unveränderliche Audit-Modelle, Bestandsverkettung und Manipulations-/Löschungstests ergänzt.
- DICOM-Testaktionen in Verbindungsansichten werden nur bei passender granularer Diagnoseberechtigung angeboten; C-MOVE und C-GET werden nicht mehr irreführend in den C-FIND-Dialog des Test-Arbeitsbereichs geleitet.

- Dokumentfristen werden täglich geprüft, als persistente In-App-Hinweise an berechtigte Benutzer verteilt und im Dashboard-Widget „Ablaufende Dokumente“ zusammengefasst.
- Mehrere Netzwerkinterfaces je System ergänzt: Bestandsmigration, Primärinterface-Synchronisierung, CRUD im Netzwerk-Reiter sowie Einbindung in Suche, Discovery-Duplikaterkennung und Systemexport.
- Gefilterten XLSX-/PDF-Export der System- und DICOM-Knotenübersicht ergänzt und den PDF-Tabellenkern mit der Firewall-/Portmatrix vereinheitlicht.
- Diagnoseausführung in neun einzeln vergebbare `diagnostics.*`-Berechtigungen aufgeteilt; bestehende Rollen werden migrationssicher übernommen und die Standardrollen `PACS-Administrator` sowie `Nur Lesen` ergänzt.
- Produktiven ClamAV-Malware-Scanner für Registry-Dokumente ergänzt: interne ClamD-`INSTREAM`-Anbindung, fail-closed Fallback, persistente Signaturdaten, Healthcheck, stündlicher Rescan und Auditierung der Rescan-Ergebnisse.
- Modality-Worklist-Test benutzerfreundlich überarbeitet: Standardansicht mit Zeitraum-Presets (Heute/Morgen/Benutzerdefiniert) und automatisch aus dem registrierten Knoten übernommener Modalität, Patienten- und AE-Title-Parameter in einem standardmäßig eingeklappten Bereich „Erweiterte DICOM-Einstellungen" mit Hilfetexten; verständliche Ergebnis- und Fehlerdarstellung mit klar gekennzeichneter „Mögliche Ursache" oberhalb der weiterhin vorhandenen technischen Details. Zentrale, konfigurierbare Calling-AE-Titel-Einstellung (`DIAGNOSTIC_CALLING_AE_TITLE`) für alle Node-Diagnosen ergänzt; optionales `modality`-Feld für DICOM-Knoten hinzugefügt. Gleiches Namensprinzip (fachliche Bezeichnung primär, DICOM-Fachbegriff sekundär) auf C-ECHO, C-STORE, C-MOVE/C-GET und Storage Commitment angewendet.
- DICOM Discovery MVP ergänzt: geführter Wizard, asynchroner Netzwerk-Scan (Ping/Reverse-DNS, TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests) über einen neuen `worker`-Queue-Container, regelbasierte Klassifizierung mit Confidence-Score, Review-Queue mit Filtern und Detail-Drawer, Duplikaterkennung bei der Übernahme in die System-Registry, Administrator-verwaltete Freigabeliste erlaubter Netzbereiche und vollständige Audit-Protokollierung.
- Bestehende Topologie um einen Nachweis-Status (`dicom_connections.evidence_status`: bestätigt/technisch getestet/vermutet/manuell dokumentiert/zuletzt fehlgeschlagen) und entsprechende Linienstile in der Netzwerkkarte erweitert.
- `systems`-Tabelle um optionale Felder `responsible` und `criticality` ergänzt; `responsible` ist inzwischen auch in der allgemeinen Systempflege verfügbar, `criticality` weiterhin nur über die Discovery-Übernahme (siehe `docs/limitations.md`).
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

## Historische Entwicklungsmeilensteine

### 0.2.0-dev – Organisationsstruktur und UI-Grundlage

- Organisationen, Standorte und Abteilungen mit Suche, Bearbeitung, Archivierung, RBAC, Audit und Dashboard-Integration umgesetzt.
- Gemeinsamen Einstieg und gruppierte Navigation für die Organisationsstruktur ergänzt; die fachliche Hierarchie folgt ADR‑0005.
- Monolithisches Anwendungslayout in getrennte Sidebar-/Header-Komponenten und wiederverwendbare Page-, Card-, Status- und Statistikkomponenten zerlegt.
- Dashboard auf tatsächlich vorhandene Registry-Daten begrenzt; Monitoring- und Verbindungswerte werden erst mit realen Datenquellen dargestellt.

### 0.1.2 – Isolierte Entwicklungs- und Testinfrastruktur

- Eigene Compose-Dienste `app-test` und `db-test` mit isoliertem Netzwerk und getrennten Volumes eingeführt.
- Test-Sicherheitsguard auf effektive Laravel-Testumgebung und Datenbanknamen mit Suffix `_test` erweitert.
- PowerShell-Helfer und Entwicklungsdokumentation für sichere Einzeltests und vollständige Quality-Gates ergänzt.
- PHPUnit kann die PostgreSQL-Entwicklungsdatenbank durch die Docker-Netztrennung nicht erreichen.

### 0.1.1 – Foundation Hardening

- Initialen Administrator als transaktionalen, einmaligen Konsolenprozess mit Rollenvergabe und Security Event abgesichert.
- Installationsprüfung über `registry:doctor` sowie Featuretests für Administratorkonto, Rollenvergabe und Zweitkonto-Sperre ergänzt.
- Clean-Install-Validierung auf einer separaten Zielumgebung und vollständiger Restore-Test blieben als manuelle Nachweise offen.

### 0.1.0 – Application Foundation (2026-07-25)

- Reproduzierbare Laravel-/Inertia-Grundlage als modularer Monolith mit lokaler Sessionauthentisierung, RBAC, Dashboard, Health-Endpunkt, Docker Compose und CI geschaffen.
- Datenbankbasis für Benutzer, Sessions, Rollen und Berechtigungen eingeführt; Default-Deny, generische Loginfehler und Rate Limits als Sicherheitsgrundlage umgesetzt.
- Abhängigkeiten werden kontrolliert installiert und über Composer-/npm-Lockfiles versioniert; Offline-Installationen benötigen ein internes Artefakt- und Image-Mirroring.
- Die Foundation enthält ausschließlich synthetische Entwicklungsdaten und keine realen Patientendaten.
