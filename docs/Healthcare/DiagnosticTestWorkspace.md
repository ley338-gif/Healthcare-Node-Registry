# Diagnostic Test Workspace

## Zweck und Abgrenzung

Der Test-Workspace unter `/tests` führt kontrollierte Netzwerk- und DICOM-Diagnosen gegen registrierte DICOM-Knoten aus. Er ist kein Monitoring-System, kein Portscanner und kein Ersatz für fachliche Abnahmetests eines PACS, RIS oder einer Modalität.

Alle Verbindungen und externen Prozesse werden im Laravel-Backend ausgeführt. Der Browser baut weder TCP- noch DICOM-Verbindungen auf. Als Netzwerkziele sind ausschließlich aktive, in der Registry gespeicherte DICOM-Knoten zulässig.

## Unterstützte Funktionen

| Test | Implementierung | Ergebnis |
|---|---|---|
| Netzwerk | PHP-Socket gegen den registrierten Knoten | DNS, TCP, Dauer und klassifizierter Fehler |
| C-ECHO | DCMTK `echoscu` und bestehender `DicomEchoService` | Association, Verification SOP Class, DIMSE-Status |
| Modality Worklist | DCMTK `findscu`, MWL Information Model | C-FIND samt Scheduled Procedure Step Sequence |
| PACS Query | DCMTK `findscu`, Study Root | C-FIND auf Studienebene; kein C-MOVE/C-GET |
| DICOM Storage | `img2dcm` und `storescu` | tatsächlicher C-STORE eines synthetischen Secondary Capture |
| Capability-Matrix | native A-ASSOCIATE-Negotiation | SOP-Class-/Transfer-Syntax-Kontexte; kein C-STORE |
| Dateianalyse | DCMTK `dcmdump` | UIDs, Transfer Syntax, Pixel Data, private Tags, bereinigter Dump |

Testprofile speichern wiederverwendbare Parameter. Testläufe erscheinen paginiert im Verlauf und können als JSON oder, für tabellarische Ergebnisse, als CSV exportiert werden.

## Architektur

Zentrale Typen unter `App\Services\Diagnostics`:

- `DiagnosticTestResult`: vollständiger Testlauf
- `DiagnosticTestStep`: einzelner Prüfschritt
- `DiagnosticTestStatus`: `success`, `warning`, `failed`, `timeout`, `cancelled`, `unsupported`
- `DiagnosticTarget`: Host, Port, AE Titles und öffentliche Knoten-/System-IDs
- `DiagnosticTestRecorder`: persistiert bereinigte Ergebnisse als `DiagnosticTestRun`

Protokollspezifische Services normalisieren ihre Resultate in diese Struktur. Externe Befehle sind durch Runner-Interfaces gekapselt. Persistiert werden Zeitpunkt, Benutzer, Knoten, System, Typ, Status, Dauer, Ziel, Schritte, Details, Warnungen und Fehler. Passwörter, Tokens, rohe Stacktraces und Patientenschlüssel werden nicht gespeichert.

## Endpunkte

Alle Endpunkte benötigen eine authentifizierte Sitzung.

| Methode | Pfad | Routenname |
|---|---|---|
| GET | `/tests` | `tests.index` |
| POST | `/tests/network/{dicomNode}` | `tests.network.run` |
| POST | `/dicom-nodes/{dicomNode}/verify` | `dicom-nodes.verify` |
| POST | `/tests/worklist/{dicomNode}` | `tests.worklist.run` |
| POST | `/tests/pacs-query/{dicomNode}` | `tests.pacs-query.run` |
| POST | `/tests/storage/{dicomNode}` | `tests.storage.run` |
| POST | `/tests/capabilities/{dicomNode}` | `tests.capabilities.run` |
| POST | `/tests/dicom-file-analysis` | `tests.dicom-file-analysis.run` |
| GET | `/tests/history/{run}/export/{json|csv}` | `tests.history.export` |
| POST/PUT | `/tests/profiles[...]` | `tests.profiles.*` |

Route Model Binding verwendet öffentliche UUIDs. Policies und Gates prüfen die Ressource serverseitig.

## Rollen und Berechtigungen

Das vorhandene RBAC-System bleibt maßgeblich.

| Funktion | Aktuelle Prüfung |
|---|---|
| Workspace und Verlauf | `registry.view` oder `registry.manage` über Policies |
| Netzwerk, C-ECHO, Worklist, PACS Query | `registry.manage` und Knoten-Policy |
| Testprofile verwalten | `registry.manage` |
| C-STORE und Capability-Matrix | `tests.run.storage` und Knoten-Policy |
| DICOM-Datei analysieren | `tests.analyze_file` |
| Ergebnisse exportieren | `tests.export` und Verlauf-Policy |

C-STORE besitzt bewusst ein strengeres Recht, da ein dauerhaftes Zielobjekt entstehen kann. Eine spätere Aufteilung von `registry.manage` in einzelne Testrechte ist in der Roadmap vermerkt; es existiert keine parallele Berechtigungsarchitektur.

## Sicherheitskonzept

- Ziele stammen ausschließlich aus aktiven, registrierten DICOM-Knoten.
- Host, Port und AE Titles werden serverseitig abgeleitet oder validiert.
- Archivierte Knoten und nicht konfigurierte Dienste werden abgewiesen.
- Socket- und Prozess-Timeouts begrenzen blockierende Aufrufe.
- Symfony Process erhält getrennte Argumente; keine Shell-Strings.
- Exitcodes, Association- und DIMSE-Fehler werden klassifiziert.
- Ausgaben werden begrenzt und interne Pfade maskiert.
- Testabschluss, Storage, Dateianalyse und Export werden auditiert.
- Patientenschlüssel werden vor Persistenz und nochmals vor Export maskiert.
- Uploads sind auf 20 MiB begrenzt, werden privat temporär verarbeitet, nie automatisch versendet und im `finally` gelöscht.

Eine konfigurierbare CIDR-Allowlist existiert derzeit nicht. Der Anwendungsschutz beruht auf registrierten Zielen und Autorisierung. Produktive Betreiber müssen Egress zusätzlich auf benötigte DICOM-Ziele begrenzen.

## Calling und Called AE Titles

Der Called AE Title bezeichnet die Ziel-AE und wird standardmäßig vom Knoten übernommen. Der Calling AE Title bezeichnet die Registry als SCU; vorgeschlagen wird `NODE_REGISTRY`. Beide sind maximal 16 Zeichen lang und werden serverseitig validiert.

Das Zielsystem muss den Calling AE Title für den gewünschten Dienst freigeben. Ein erreichbarer TCP-Port oder erfolgreicher C-ECHO beweist nicht, dass Worklist, Query oder Storage autorisiert sind.

## Timeouts

Aktuelle defensive Grenzen:

- Netzwerk: kurzer begrenzter Socket-Timeout
- `echoscu`/`findscu`: begrenzte Association-, DIMSE- und Gesamtprozesszeit
- Storage: 5 Sekunden Connect/ACSE, 15 Sekunden Socket/DIMSE, 25 Sekunden Gesamtprozess
- Dateianalyse: 20 Sekunden Gesamtprozess

Bei Prozess-Timeout wird der Prozess beendet und kontrolliert als Timeout gemeldet. Eine installationsweite Timeout-Konfiguration ist noch nicht vorhanden.

## Protokollverhalten

### C-ECHO

Prüft TCP, Association, Verification SOP Class und DIMSE-Antwort. Die bestehende Knotenverifikation bleibt kompatibel; zusätzlich entsteht ein Diagnoseverlaufseintrag.

### C-FIND

Worklist verwendet das MWL Information Model, PACS Query Study Root. Keine Treffer sind ein technischer Erfolg mit `resultCount = 0`. Antwortmengen sind begrenzt. C-MOVE und C-GET werden nicht ausgeführt. Patient Name, ID, Geburtsdatum und Accession Number werden im Verlauf maskiert.

### C-STORE

C-STORE benötigt ausdrückliche Bestätigung. Das Secondary-Capture-Objekt verwendet ausschließlich:

- Patient Name `DICOMNODE^TEST`
- Patient ID `TEST-[Zeitstempel]`
- Study Description `DICOM Connectivity Test`

Das Objekt kann im Zielsystem gespeichert, weitergeleitet oder archiviert werden. Aufräumen dort ist Betreiberaufgabe. Lokale BMP-, DICOM- und Verzeichnisartefakte werden immer gelöscht.

### Capability-Matrix

Die Matrix prüft 49 Kombinationen aus sieben Storage SOP Classes und sieben Transfer Syntaxes über Presentation Contexts. `accepted` beweist nur die Association-Aushandlung, keinen erfolgreichen C-STORE.

## DCMTK-Voraussetzungen

Das PHP-Containerimage installiert DCMTK. Benötigt werden `echoscu`, `findscu`, `img2dcm`, `storescu`, `dcmdump` sowie `dump2dcm` für Integrationstests. Getestet ist DCMTK 3.6.7. Nach Upgrades sind Parser- und Integrationsfälle erneut auszuführen.

## Firewall und Netzwerk

Der App-/PHP-Container benötigt ausgehenden TCP-Zugriff auf die registrierten DICOM-Ports. DNS muss gespeicherte Hostnamen im Container auflösen. Rückverbindungen sind für die implementierten SCU-Tests nicht nötig.

Empfohlen:

- Egress nur zu freigegebenen PACS-, RIS- und Modalitätsnetzen
- keine pauschale Freigabe kompletter Krankenhausnetze
- Containerhost-Quelladresse im Zielsystem berücksichtigen
- Firewall- und SCP-Logs bei Association-Rejections korrelieren
- `tls_enabled` ist Dokumentation; die aktuellen Diagnose-Runner bauen keine DICOM-TLS-Verbindung auf

## Bekannte Einschränkungen

- kein C-MOVE, C-GET, Storage Commitment oder MPPS-Test
- Storage nur als Secondary Capture; kein CT, MR, PDF oder SR
- Capability-Matrix ist Association-only
- keine zentrale Abbruchfunktion, Queue oder Parallelitätssteuerung
- keine CIDR-Allowlist oder zentrale Timeout-Konfiguration
- keine DICOM-TLS-Unterstützung in den Runnern
- keine Löschung bereits im Zielsystem gespeicherter Testobjekte
- kein PDF-Export, da keine projektweite PDF-Infrastruktur vorhanden ist

## Qualitätssicherung

```powershell
docker compose run --rm node npm run check
docker compose --profile test run --rm app-test composer quality
docker compose run --rm node npm run build
```

Feature-Tests decken Berechtigungen, Erfolg, Timeout, Protokollfehler, Maskierung, Persistenz, Audit, Export, Dateigrenzen und temporäre Bereinigung ab. Native Integrationstests prüfen DCMTK ohne externes PACS.

## Roadmap

- granulare Einzelrechte für ungefährliche Tests
- konfigurierbare Zielnetz-Allowlist, Timeouts und Parallelitätsgrenzen
- DICOM-TLS mit kontrolliertem Zertifikatsmanagement
- weitere synthetische Storage-Objekttypen
- C-MOVE/C-GET erst nach separatem Sicherheitsdesign
- Aufbewahrungs- und Löschregeln für Diagnoseverläufe
- PDF-Bericht, sobald eine projektweite PDF-Infrastruktur existiert
