# DICOM Discovery & Topologie-Vorschläge

Automatisierte, aber ausschließlich unterstützende Erkennung von DICOM-Systemen in einem begrenzten IPv4-Zielbereich. Kein Medizinprodukt, keine diagnostische oder therapeutische Funktion, keine Verarbeitung von Patientendaten. Jedes automatisch ermittelte Ergebnis ist ein Vorschlag und muss von einem Menschen bestätigt werden, bevor es in die Registry übernommen wird.

## Workflow

1. **Wizard** (`/discovery/runs/create`, Berechtigung `discovery.run`): Zielbereich (CIDR oder Start-/End-IP), Scanoptionen (Profil Vorsichtig/Standard/Schnell/Benutzerdefiniert), Portliste, AE-Titel-Kandidaten, Zusammenfassung mit verpflichtender Sicherheitsbestätigung. AE-Titel-Kandidaten können zusätzlich über eine kontrollierte CSV-/XLSX-Vorschau in das Formular übernommen werden. Die geprüfte Konfiguration kann einmalig sofort gestartet oder als täglicher beziehungsweise wöchentlicher Zeitplan mit IANA-Zeitzone gespeichert werden.
2. **Scan-Job** (`App\Jobs\RunDiscoveryScanJob`, Queue `discovery`): Phase A Host-Erkennung (Ping + Reverse-DNS), Phase B TCP-Portprüfung gegen die konfigurierte Portliste, Phase C begrenzte DICOM-C-ECHO-Tests je offenem DICOM-Kandidatenport und AE-Titel-Kandidat.

Die im Wizard konfigurierten Wiederholungsversuche (0–3) gelten für fehlgeschlagene Ping-, TCP-Port- und DICOM-C-ECHO-Prüfungen. Erfolgreiche Ziele werden nicht erneut geprüft. Damit entstehen pro technischer Prüfung höchstens vier Ausführungen; die Begrenzung der AE-Titel-Kandidaten je Host und Port bleibt davon unberührt.

### Importformat für AE-Titel-Kandidaten

CSV-Dateien müssen UTF-8-kodiert sein und dürfen Komma oder Semikolon als Trennzeichen verwenden. XLSX-Dateien verwenden das erste Arbeitsblatt; Formeln werden nicht ausgewertet und führen zur Ablehnung. Beide Formate besitzen dieselben Spalten und höchstens 30 Datenzeilen:

| Spalte | Pflicht | Inhalt |
| --- | --- | --- |
| `schema_version` | ja | aktuell immer `1` |
| `ae_title` | ja | 1–16 Zeichen, Buchstaben, Ziffern, Leerzeichen, `_` oder `-` |
| `role` | nein | `called` (Standard) oder `calling` |

Beispiel:

```csv
schema_version,ae_title,role
1,PACS_PROD,called
1,RIS_TEST,called
1,HNR_DISCOVERY,calling
```

Der Import zeigt zunächst Fehler und Duplikatwarnungen mit Zeilenbezug. Erst **Gültige Kandidaten übernehmen** überträgt die Werte in das lokale Wizard-Formular. Es wird weder ein Discovery-Lauf gespeichert noch Netzwerkverkehr gestartet. Die Gesamtliste bleibt auf 30 Einträge und die späteren Echo-Versuche bleiben auf `DISCOVERY_MAX_AE_ATTEMPTS_PER_PORT` begrenzt.
3. **Fortschritt & Review** (`/discovery/runs/{id}`): Live-Fortschritt per Polling, Review-Queue mit Filtern (erreichbar, DICOM-Kandidat, erfolgreicher C-ECHO, Review-Status, Confidence, Systemtyp), Detail-Drawer mit Ports, DICOM-Testergebnissen und Klassifizierungs-Hinweisen. Die aktuelle, gefilterte Ergebnismenge kann als UTF-8-CSV, JSON oder mehrseitiger PDF-Bericht exportiert werden; alle Formate verwenden dieselbe serverseitige Filterabfrage, erfordern `discovery.view` und werden auditiert. CSV und JSON enthalten die vollständigen maschinenlesbaren Werte; lange Tabellenwerte können im kompakten PDF gekürzt dargestellt sein.
   Abgeschlossene Läufe desselben Zielbereichs können zusätzlich miteinander verglichen werden. Die Ansicht unterscheidet neue, verschwundene, veränderte und unveränderte Hosts. Latenzwerte und manuelle Review-Aktionen werden dabei bewusst nicht als technische Änderung gewertet.
   Nach einem vollständig erfolgreichen Zeitplanlauf vergleicht die Anwendung den Stand automatisch mit dem vorherigen erfolgreichen Lauf desselben Zeitplans. Aktive Benutzer mit Discovery-Recht erhalten bei neuen oder verschwundenen Hosts einen deduplizierten In-App-Hinweis auf dem Dashboard mit direktem Link zum Vergleich. Manuelle, erste, teilweise fehlgeschlagene oder abgebrochene Läufe lösen keine Meldung aus; ein externer Benachrichtigungskanal ist nicht vorhanden.
4. **Übernahme** (`App\Services\Discovery\RegistryPromotionService`): Übernahme in ein neues oder bestehendes System inklusive DICOM-Endpunkt, mit Duplikaterkennung (IP, Hostname, AE-Titel, IP+Port, ähnlicher Name) und sichtbarer Herkunftsangabe (Discovery-Lauf, Zeitpunkt, ursprünglicher Confidence-Score).

## Sicherheitsgrenzen

- Wiederverwendbare Portprofile werden unter `/settings/discovery` mit der Berechtigung `discovery.manage` verwaltet. Der Wizard bietet nur aktive Profile an. Beim Laufstart wird die ausgewählte und gegebenenfalls angepasste Portliste als eigene Laufkonfiguration gespeichert; spätere Profiländerungen verändern bestehende Läufe nicht.

- Scans sind auf von einem Administrator freigegebene Netzbereiche beschränkt (`discovery_allowed_networks`, verwaltet unter `/settings/discovery`, Berechtigung `discovery.manage`). Standardmäßig ausschließlich RFC1918-Bereiche.
- Zielbereich ist auf `config('discovery.max_range_size')` Adressen begrenzt (Standard 1024).
- Ping erfolgt über das System-Binary `ping` mit fester Argumentliste (kein Shell-String). TCP-Portprüfung erfolgt über nicht-blockierende PHP-Sockets, kein Nmap.
- DICOM-C-ECHO läuft über DCMTK `echoscu` mit fester Argumentliste; AE-Titel-Versuche sind über `config('discovery.max_ae_attempts_per_port')` je Host/Port hart begrenzt (Standard 5) - kein unbegrenztes Brute-Forcing.
- Ein Fehler bei einem einzelnen Host bricht den Lauf nicht ab; ein laufender Lauf kann kooperativ abgebrochen werden (`cancelling` → `cancelled`).
- Der Laravel-Scheduler prüft fällige Discovery-Zeitpläne minütlich. Vor jeder Ausführung wird die aktuelle Netzfreigabe erneut validiert. Ein überlappender aktiver Lauf verschiebt die Ausführung um fünf Minuten; eine entzogene Freigabe deaktiviert den Zeitplan fail-closed. Freie Cron-Ausdrücke sind nicht vorgesehen.
- Jede sicherheitsrelevante Aktion (Laufstart, Abbruch, Freigabe/Bestätigung, Übernahme, Änderung freigegebener Netzbereiche) wird als Audit-Ereignis protokolliert (`App\Services\Discovery\DiscoveryAuditService`, siehe `docs/Security/SecurityEventCatalog.md`).

## Klassifizierung (Abschnitt 12)

Rein regelbasiert, keine externe KI (`App\Services\Discovery\Classification\ClassificationService`). Jeder Hinweis hat einen Namen, eine für sich lesbare Begründung und ein Gewicht; die Summe ergibt einen Prozentwert (gekappt bei 100) und eine von sechs Konfidenzstufen (unbekannt … sehr hoch). Der Wert ist ausdrücklich als Heuristik gekennzeichnet, nie als Tatsache.

Wichtige, in der Oberfläche wiederholt sichtbare Einschränkungen:

- Ein offener Port beweist keinen DICOM-Dienst.
- Ein erfolgreicher C-ECHO beweist nur die Erreichbarkeit dieses Endpunkts, keine produktive Verbindung zwischen zwei Systemen.
- AE-Titel können nicht zuverlässig automatisch ausgelesen werden - es werden nur konfigurierte Kandidaten getestet.

## Topologie-Integration

Die Übernahme erzeugt ausschließlich `System`- und `DicomNode`-Datensätze, niemals automatisch eine `DicomConnection`. Übernommene Systeme erscheinen dadurch automatisch in der bestehenden Topologie (`/network`), ohne dass eine zweite Topologie-Implementierung nötig ist. Verbindungen zwischen Systemen müssen weiterhin manuell über `/connections` angelegt werden. Der dort ergänzte Nachweis-Status (`DicomConnection::$evidence_status`: bestätigt, technisch getestet, vermutet, manuell dokumentiert, zuletzt fehlgeschlagen) steuert den Linienstil in `DicomNetworkMap.vue` (siehe `docs/UI/Topology.md`).

## Datenmodell

`discovery_port_profiles` enthält die administrierbaren Vorlagen. `discovery_ports` bleibt die unveränderliche, laufbezogene Kopie der tatsächlich verwendeten Konfiguration. `discovery_schedules` speichert Intervall, Ortszeit, Zeitzone und eine kontrollierte Konfigurationskopie; erzeugte Läufe verweisen über `discovery_runs.discovery_schedule_id` auf ihren Ursprung.

Siehe `docs/Database/DataDictionary.md` (Abschnitt „Discovery“) und `docs/Database/ERD.md` (Abschnitt „Discovery (implementiertes Schema)“) für die vollständige Tabellenübersicht. Kerntabellen: `discovery_schedules`, `discovery_runs`, `discovery_exclusions`, `discovery_ports` (Konfiguration), `discovery_ae_candidates`, `discovered_hosts`, `discovered_ports` (Ergebnis), `dicom_discovery_results`, `discovery_classification_evidence`, `discovery_allowed_networks`.

## Betrieb

- Scan-Jobs laufen über den `worker`-Container (`php artisan queue:work database --queue=discovery,default`), siehe `README.md`.
- Fällige Zeitpläne werden vom `scheduler`-Container über `php artisan discovery:run-schedules` ausgewertet. Für die lokale Zeitauslegung gilt die je Zeitplan gespeicherte Zeitzone; `APP_TIMEZONE` ist die Vorgabe im Wizard.
- DCMTK und `iputils-ping` sind im `app`/`worker`-Image installiert (`docker/php/Dockerfile`).
- Fehlt ICMP-Ping (z. B. fehlende `CAP_NET_RAW`), wird ein Host trotzdem als erreichbar gewertet, sobald mindestens ein konfigurierter Port antwortet - der Lauf bricht dadurch nicht ab.

## Nicht Bestandteil des MVP

Siehe `docs/limitations.md` und `docs/roadmap.md`.
