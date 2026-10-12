# Changelog

## Unreleased

## 0.9.0-rc.2 – 2026-10-12

- App-Release-Image verschlankt: Composer-Installation und Asset-Übernahme laufen in einer eigenen Build-Stufe, die Laufzeitstufe startet vom unveränderten PHP-Basisimage und enthält keine Compiler, Kernel-Header, Composer, git, curl-CLI, pip oder `pcov` mehr. HIGH/CRITICAL-Befunde ohne verfügbaren Fix sinken im App-Image von 233 auf 37 eindeutige CVEs, die Imagegröße von 1,2 GB auf 935 MB; ein neuer CI-Schritt sichert Laufzeitumfang und DICOM-Funktion des Images ab.

- XLSX-Import von AE-Titel-Kandidaten gehärtet: XML-Bestandteile werden nur noch als UTF-8 ohne abweichende Kodierungsdeklaration angenommen, sodass die bestehende DOCTYPE-Sperre nicht über UTF-16, UTF-7 oder andere Zeichenkodierungen umgangen werden kann; Regressionstests decken die abgelehnten Kodierungen und zulässige UTF-8-Varianten ab.

- SEC-01 präzisiert: maßgeblich ist der letzte abgeschlossene Lauf des Supply-Chain-Workflows auf `main` unabhängig vom Auslöser; er muss grün und höchstens sieben Tage alt sein, damit auch ein von GitHub deaktivierter Zeitplan auffällt.

## 0.9.0-rc.1 – 2026-10-12

Erster Release Candidate für den kontrollierten Pilot. Der Abschnitt fasst alle Änderungen seit `0.2.0-dev` zusammen.

### Release, Betrieb und Supply Chain

- Unveränderlichen Release-Build für den Pilotbetrieb ergänzt: Anwendungscode, produktive Composer-Abhängigkeiten und Frontend-Artefakte werden in getrennte App- und Webimages eingebaut; die Release-Compose-Überlagerung entfernt produktive Quellcode-Bind-Mounts und dokumentiert Installation, Upgrade und Rollback.
- Verbindliche Supply-Chain-Gates ergänzt: vollständiger Gitleaks-Historiencheck, Trivy-Scans der Release-Runtime-Images auf behebbare hohe und kritische Befunde sowie CycloneDX-SBOM-Artefakte; PHP-, Nginx- und `pydicom`-Laufzeiten auf gepinnte, gepatchte Stände angehoben und nicht benötigte Python-Installationswerkzeuge aus der Runtime entfernt.
- Seit dem 31.08.2026 fehlschlagende wöchentliche Supply-Chain-Prüfung wiederhergestellt: `league/commonmark` 2.10.3, `league/flysystem` 3.36.0, `laravel/framework` 13.35.0 sowie produktive npm-Pakete (`vue` 3.5.43) schließen die neu veröffentlichten Advisories; PostgreSQL auf 18.6 und ClamAV auf 1.5.4 mit neuen Digests angehoben. Composer- und npm-Audit laufen jetzt im Supply-Chain-Workflow und damit auch wöchentlich, die vier Trivy-Scans werden unabhängig ausgeführt und erst in einem abschließenden Gate bewertet, alle Actions sind SHA-gepinnt auf Node-24-Versionen und alle Runner auf `ubuntu-24.04` festgelegt.
- Verbindliche Pilot-Go-/No-Go-Matrix mit Betriebsbaseline (RPO/RTO, Restore, Logrotation, Speicher-/Zertifikats-/Health-Alarme), minimalem Rollenmodell und Healthcare-E2E-Testmatrix ergänzt; PostgreSQL-, ClamAV- und Tool-Images digest-gepinnt, Runtime-Scans/SBOMs auf alle vier Release-Images erweitert und der Storage-Commitment-Callback auf eine konfigurierbare Hostadresse begrenzbar gemacht.
- Optionales HTTPS-Referenz-Deployment für Docker Compose mit betreiberverwalteten Zertifikaten, HTTP-zu-HTTPS-Weiterleitung, TLS-1.2/1.3-Härtung, HSTS und CI-validierter Nginx-Konfiguration ergänzt; der lokale HTTP-Entwicklungsstart bleibt verfügbar.
- PowerShell-Backup und -Restore um verbindliche Docker-Exitcode-, Dump- und Archivprüfung, sichere Wiederherstellung über ein Staging-Verzeichnis sowie fail-closed Webdienstverhalten bei Restore-Fehlern gehärtet; privates Storage-Verzeichnis wird bereits beim Containerstart angelegt.
- Anwendungsspezifischen Queue-Worker-Healthcheck ergänzt: Queue-Loops aktualisieren einen inhaltsarmen Heartbeat, Docker prüft dessen Aktualität und die Datenbankverbindung, und Betrieb sowie Fehlerdiagnose sind dokumentiert.

### Sicherheit und Zugriffskontrolle

- Verpflichtende TOTP-MFA für lokale privilegierte Konten ergänzt: berechtigungsbasierte Erzwingung, lokale QR-Code-Einrichtung ohne externen Dienst, verschlüsselte Secrets, einmalige gehashte Recovery-Codes, Replay- und Rate-Limit-Schutz, Session-Widerruf bei Rollen-/Rechteänderungen, administrativer und konsolenbasierter Reset sowie vollständiges Audit.
- Einstellungen um eine berechtigungsgeprüfte Benutzer-, Rollen- und Berechtigungsverwaltung mit Suche, Status- und Rollenfiltern erweitert.
- Passwortvorgaben zentralisiert, Sitzungswiderruf bei Passwortwechsel und Deaktivierung sowie Schutz des letzten aktiven Systemadministrators ergänzt.
- Diagnoseausführung in neun einzeln vergebbare `diagnostics.*`-Berechtigungen aufgeteilt; bestehende Rollen werden migrationssicher übernommen und die Standardrollen `PACS-Administrator` sowie `Nur Lesen` ergänzt.

### Registry und Stammdaten

- Systemtypen und Systemstatus als fremdschlüsselgesicherte, auditierte Referenzdaten mit zentraler Verwaltungsoberfläche, Bestandsschutz für deaktivierte Werte sowie einheitlicher Validierung in Systempflege, CSV-Import und Discovery umgesetzt.
- Hersteller als fremdschlüsselgesicherte, auditierte Referenzdaten ergänzt; vorhandene Herstellertexte werden verlustfrei übernommen, neue Zuordnungen verwenden aktive Auswahllisten und CSV-Importe lösen Code oder Anzeigename kontrolliert auf.
- Systemkritikalität in Anlage, Bearbeitung, Detailansicht, Listenkennzeichnung, Filter und Export integriert; Änderungen werden über das bestehende Registry-Audit mit Vorher-/Nachher-Werten protokolliert.
- Optionale betriebliche Verantwortlichkeit von Systemen in Anlage, Bearbeitung, Detailansicht, Suche und XLSX-Export eingebunden; Änderungen werden mit Vorher-/Nachher-Wert im bestehenden Registry-Audit protokolliert.
- Mehrere Netzwerkinterfaces je System ergänzt: Bestandsmigration, Primärinterface-Synchronisierung, CRUD im Netzwerk-Reiter sowie Einbindung in Suche, Discovery-Duplikaterkennung und Systemexport.
- Gefilterten XLSX-/PDF-Export der System- und DICOM-Knotenübersicht ergänzt und den PDF-Tabellenkern mit der Firewall-/Portmatrix vereinheitlicht.
- Globale, berechtigungsgeprüfte Suche in der Kopfzeile für Registry-Struktur, Systeme, DICOM, Dokumente, Tests und Benutzer ergänzt.

### Dokumente

- Private Dokumentenablage für Organisationen, Standorte, Abteilungen und Systeme ergänzt: unveränderliche Dateiversionen mit aktueller Version, SHA-256-Prüfsumme und Duplikaterkennung, zentrale Dokumentkategorien, Gültigkeitsstatus, Suche, Filter und serverseitige Pagination.
- Uploads werden serverseitig über eine Datei-Allowlist mit MIME- und Signaturprüfung sowie konfigurierbarem Größenlimit geprüft; Download und PDF-Vorschau sind berechtigungsgeprüft und kommen ohne öffentliche Storage-URL aus.
- Malware-Prüfung für Registry-Dokumente ergänzt: Scanner-Schnittstelle mit fail-closed Zugriff auf nicht saubere Versionen und produktive ClamAV-Anbindung über internes ClamD-`INSTREAM`, persistente Signaturdaten, Healthcheck, stündlichen Rescan und Auditierung der Rescan-Ergebnisse.
- Versionsbezogenen Dokumentfreigabe-Workflow mit serverseitigem Vier-Augen-Prinzip, separatem Freigaberecht, Audit-Ereignissen, Veröffentlichung erst nach Freigabe sowie konfigurierbaren Mindestaufbewahrungsfristen je Kategorie ergänzt.
- Dokumentfreigabe um einen eng begrenzten, im Audit gekennzeichneten Systemadministrator-Override ergänzt: Die Rolle `system-administrator` darf eigene wartende und malwareseitig saubere Versionen freigeben; Selbstablehnung und alle übrigen Prüfungen bleiben gesperrt beziehungsweise verpflichtend.
- Dokumentfristen werden täglich geprüft, als persistente In-App-Hinweise an berechtigte Benutzer verteilt und im Dashboard-Widget „Ablaufende Dokumente“ zusammengefasst.

### Audit, Historie und Betriebsdokumentation

- Gemeinsame Registry-Historie für Systeme, Organisationen, Standorte und Abteilungen mit serverseitigen Audit-Filtern, Kennzahlen, Pagination und Detailansicht ergänzt.
- Strukturierte polymorphe Betriebsdokumentation mit kontextspezifischen Sektionen und einem nachvollziehbaren Dokumentationsstand auf Basis definierter Pflichtfelder ergänzt.
- Zentralen, read-only Audit-Arbeitsbereich mit Berechtigungsprüfung, serverseitiger Filterung und Entity-Auflösung, 50er-Paginierung, Slide-over-Details und CSV-Export ergänzt.
- Audit-Tabelle um kompakte Vorher-/Nachher-Werte, zentrale Ereignisgruppen und zustandsabhängige Deep Links in Registry-, DICOM-, Dokument- und Test-Workspaces erweitert.
- Audit-Ereignisse für Dokumentaktionen (Upload, Versionierung, Metadaten, Archivierung, Scanfehler), für Dokumentationsänderungen ohne vollständige Langtexte sowie für Benutzer-, Rollen-, Login- und Logout-Aktionen in der Ereignisgruppe Benutzer ergänzt.
- Audit-Trail kryptografisch über eine kanonische SHA-256-Hash-Chain mit gespeichertem Kettenkopf abgesichert; Verifikations- und kontrollierten Prune-Befehl, siebenjährig vorkonfigurierte betreiberabhängige Aufbewahrung, unveränderliche Audit-Modelle, Bestandsverkettung und Manipulations-/Löschungstests ergänzt.

### DICOM-Diagnose

- Diagnose-Workspace mit standardisierter Ergebnisarchitektur, persistentem Verlauf, wiederverwendbaren Testprofilen und berechtigungsgeprüftem Diagnosestatus auf dem Dashboard ergänzt; die bestehende C-ECHO-Verifikation ist in diese Architektur integriert.
- Echte Netzwerk-, C-ECHO-, Modality-Worklist- und PACS-C-FIND-Tests gegen registrierte Knoten sowie eine SOP-Class-/Transfer-Syntax-Capability-Matrix ohne C-STORE ergänzt.
- Kontrollierten synthetischen C-STORE mit Bestätigung, strengem Recht und Audit ergänzt und um auswählbare Secondary-Capture-, CT- und MR-Image-Storage-Objekte mit serverseitiger SOP-Class-Validierung, korrekten Auditdaten und rückstandsfreier lokaler Erzeugung erweitert.
- Autorisierten C-MOVE-Diagnosetest mit `movescu`, synthetischer serverseitiger Study-UID, Pflichtbestätigung, Testhistorie und Audit-Protokollierung ergänzt.
- Autorisierten C-GET-Diagnosetest mit `getscu`, synthetischer serverseitiger Study-UID, sicherer temporärer Dateibereinigung, Testhistorie und Audit-Protokollierung ergänzt.
- Autorisierten MPPS-Diagnosetest ergänzt: echtes N-CREATE mit anschließendem N-SET auf `COMPLETED` gegen Knoten mit aktivierter MPPS-Fähigkeit, ausschließlich synthetische Identifikatoren, Pflichtbestätigung, Testverlauf und Audit.
- Autorisierte Storage-Commitment-Diagnose ergänzt: synthetisches Secondary-Capture-Objekt per C-STORE, Anforderung per N-ACTION im Push Model und Erfolg erst mit korreliertem N-EVENT-REPORT über einen konfigurierbaren Callback-Port; eigenes Recht `diagnostics.storage_commitment`, Pflichtbestätigung, Testverlauf und Audit.
- DICOM-TLS für alle Diagnose-Runner ergänzt: benannte, schreibgeschützt eingebundene Zertifikatsprofile, gegenseitige Zertifikatsprüfung für DCMTK/Python/PHP, TLS-geschützter Storage-Commitment-Callback und fail-closed Verhalten ohne Klartext-Fallback.
- Modality-Worklist-Test benutzerfreundlich überarbeitet: Standardansicht mit Zeitraum-Presets (Heute/Morgen/Benutzerdefiniert) und automatisch aus dem registrierten Knoten übernommener Modalität, Patienten- und AE-Title-Parameter in einem standardmäßig eingeklappten Bereich „Erweiterte DICOM-Einstellungen“ mit Hilfetexten; verständliche Ergebnis- und Fehlerdarstellung mit klar gekennzeichneter „Mögliche Ursache“ oberhalb der weiterhin vorhandenen technischen Details. Zentrale, konfigurierbare Calling-AE-Titel-Einstellung (`DIAGNOSTIC_CALLING_AE_TITLE`) für alle Node-Diagnosen ergänzt; optionales `modality`-Feld für DICOM-Knoten hinzugefügt. Gleiches Namensprinzip (fachliche Bezeichnung primär, DICOM-Fachbegriff sekundär) auf C-ECHO, C-STORE, C-MOVE/C-GET und Storage Commitment angewendet.
- Serverseitige DICOM-Dateianalyse mit automatischer temporärer Bereinigung sowie bereinigter JSON- und CSV-Export von Diagnoseergebnissen ergänzt.
- Globale, serverseitig filter- und sortierbare Übersicht aller DICOM-Verbindungen unter `/connections` ergänzt; der DICOM-Verbindungsmanager bietet Details, Duplizieren mit Audit-Ereignis und Übergabe an den Test-Arbeitsbereich, jeweils mit Navigation und Berechtigungsprüfung.
- DICOM-Testaktionen in Verbindungsansichten werden nur bei passender granularer Diagnoseberechtigung angeboten; C-MOVE und C-GET werden nicht mehr irreführend in den C-FIND-Dialog des Test-Arbeitsbereichs geleitet.
- Bestehende Topologie um einen Nachweis-Status (`dicom_connections.evidence_status`: bestätigt/technisch getestet/vermutet/manuell dokumentiert/zuletzt fehlgeschlagen) und entsprechende Linienstile in der Netzwerkkarte erweitert.

### DICOM Discovery

- DICOM Discovery MVP ergänzt: geführter Wizard, asynchroner Netzwerk-Scan (Ping/Reverse-DNS, TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests) über einen neuen `worker`-Queue-Container, regelbasierte Klassifizierung mit Confidence-Score, Review-Queue mit Filtern und Detail-Drawer, Duplikaterkennung bei der Übernahme in die System-Registry, Administrator-verwaltete Freigabeliste erlaubter Netzbereiche und vollständige Audit-Protokollierung.
- Discovery-Wiederholungsversuche vollständig aktiviert: fehlgeschlagene Ping-, TCP-Port- und DICOM-C-ECHO-Prüfungen werden gemäß Wizard-Konfiguration begrenzt wiederholt, erfolgreiche Ziele ausgespart und die maximale Prüfungsanzahl vor dem Start transparent berechnet.
- Administrierbare Discovery-Portprofile ergänzt: aktive Profile stehen im Scan-Assistenten als Vorlagen bereit, das bisherige Portset wird als Standardprofil migriert und jeder Lauf speichert weiterhin eine unabhängige Portkopie; Änderungen werden auditiert.
- Tägliche und wöchentliche Discovery-Zeitpläne mit expliziter Zeitzone, pausierbarer Dashboard-Verwaltung, minütlicher Scheduler-Auswertung, Überlappungsschutz, erneuter Netzfreigabeprüfung vor jedem Lauf und vollständigem Audit ergänzt.
- Manuellen Vergleich vollständig abgeschlossener Discovery-Läufe desselben Zielbereichs ergänzt; neue, verschwundene, technisch veränderte und unveränderte Hosts werden anhand stabiler Scanmerkmale nachvollziehbar gegenübergestellt.
- Deduplizierte In-App-Benachrichtigungen für aktive Discovery-Benutzer ergänzt: vollständig erfolgreiche Zeitplanläufe werden mit ihrem vorherigen erfolgreichen Stand verglichen und melden neue oder verschwundene Hosts auf dem Dashboard; unvollständige und manuelle Läufe bleiben zur Vermeidung von Fehlalarmen ausgeschlossen.
- Kontrollierten CSV-/XLSX-Import von AE-Titel-Kandidaten im Discovery-Wizard ergänzt: versioniertes Schema, serverseitige Vorschau mit Zeilenfehlern und Duplikatwarnungen, Formel- und XML-Härtung sowie ausdrückliche Übernahme ohne automatischen Scanstart.
- Gefilterte, berechtigungsgeprüfte und auditierte Exporte der Discovery-Ergebnisse und Review-Queue ergänzt: CSV und JSON mit Tabellenkalkulationsschutz sowie PDF auf Basis der gemeinsamen Host-Abfrage und des vorhandenen mehrseitigen PDF-Tabellenkerns.

### Oberfläche und Navigation

- Gemeinsame Übersichtsseite und gruppierte Registry-Navigation ergänzt: Organisationen, Standorte und Abteilungen sind unter einem Navigationsbereich gebündelt, Systeme als nächstes zentrales Registry-Objekt hervorgehoben und geplante Module klar von verfügbaren Funktionen getrennt.
- Produktorientiertes Dashboard ohne erfundene Monitoringdaten mit den letzten Registry-Änderungen aus Security Events ergänzt.

### Wartung und Dokumentation

- Versehentlich eingecheckte Backup- und Static-Analysis-Artefakte entfernt, Ignore-Regeln für Backup-Dateien und lokale PHPStan-Ausgaben ergänzt und die Jobstruktur der Repository-Prüfung in GitHub Actions korrigiert.
- Product Book mit dem implementierten Stand von DICOM-Diagnosen und -TLS, ClamAV, Dokumentfreigabe und -aufbewahrung, Audit-Integrität sowie Systemverantwortlichkeit synchronisiert und verbleibende Grenzen präzisiert.

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
