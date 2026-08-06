# DICOM Discovery & Topologie-Vorschläge

Automatisierte, aber ausschließlich unterstützende Erkennung von DICOM-Systemen in einem begrenzten IPv4-Zielbereich. Kein Medizinprodukt, keine diagnostische oder therapeutische Funktion, keine Verarbeitung von Patientendaten. Jedes automatisch ermittelte Ergebnis ist ein Vorschlag und muss von einem Menschen bestätigt werden, bevor es in die Registry übernommen wird.

## Workflow

1. **Wizard** (`/discovery/runs/create`, Berechtigung `discovery.run`): Zielbereich (CIDR oder Start-/End-IP), Scanoptionen (Profil Vorsichtig/Standard/Schnell/Benutzerdefiniert), Portliste, AE-Titel-Kandidaten, Zusammenfassung mit verpflichtender Sicherheitsbestätigung.
2. **Scan-Job** (`App\Jobs\RunDiscoveryScanJob`, Queue `discovery`): Phase A Host-Erkennung (Ping + Reverse-DNS), Phase B TCP-Portprüfung gegen die konfigurierte Portliste, Phase C begrenzte DICOM-C-ECHO-Tests je offenem DICOM-Kandidatenport und AE-Titel-Kandidat.
3. **Fortschritt & Review** (`/discovery/runs/{id}`): Live-Fortschritt per Polling, Review-Queue mit Filtern (erreichbar, DICOM-Kandidat, erfolgreicher C-ECHO, Review-Status, Confidence, Systemtyp), Detail-Drawer mit Ports, DICOM-Testergebnissen und Klassifizierungs-Hinweisen.
4. **Übernahme** (`App\Services\Discovery\RegistryPromotionService`): Übernahme in ein neues oder bestehendes System inklusive DICOM-Endpunkt, mit Duplikaterkennung (IP, Hostname, AE-Titel, IP+Port, ähnlicher Name) und sichtbarer Herkunftsangabe (Discovery-Lauf, Zeitpunkt, ursprünglicher Confidence-Score).

## Sicherheitsgrenzen

- Scans sind auf von einem Administrator freigegebene Netzbereiche beschränkt (`discovery_allowed_networks`, verwaltet unter `/settings/discovery`, Berechtigung `discovery.manage`). Standardmäßig ausschließlich RFC1918-Bereiche.
- Zielbereich ist auf `config('discovery.max_range_size')` Adressen begrenzt (Standard 1024).
- Ping erfolgt über das System-Binary `ping` mit fester Argumentliste (kein Shell-String). TCP-Portprüfung erfolgt über nicht-blockierende PHP-Sockets, kein Nmap.
- DICOM-C-ECHO läuft über DCMTK `echoscu` mit fester Argumentliste; AE-Titel-Versuche sind über `config('discovery.max_ae_attempts_per_port')` je Host/Port hart begrenzt (Standard 5) - kein unbegrenztes Brute-Forcing.
- Ein Fehler bei einem einzelnen Host bricht den Lauf nicht ab; ein laufender Lauf kann kooperativ abgebrochen werden (`cancelling` → `cancelled`).
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

Siehe `docs/Database/DataDictionary.md` (Abschnitt „Discovery“) und `docs/Database/ERD.md` (Abschnitt „Discovery (implementiertes Schema)“) für die vollständige Tabellenübersicht. Kerntabellen: `discovery_runs`, `discovery_exclusions`, `discovery_ports` (Konfiguration), `discovery_ae_candidates`, `discovered_hosts`, `discovered_ports` (Ergebnis), `dicom_discovery_results`, `discovery_classification_evidence`, `discovery_allowed_networks`.

## Betrieb

- Scan-Jobs laufen über den `worker`-Container (`php artisan queue:work database --queue=discovery,default`), siehe `README.md`.
- DCMTK und `iputils-ping` sind im `app`/`worker`-Image installiert (`docker/php/Dockerfile`).
- Fehlt ICMP-Ping (z. B. fehlende `CAP_NET_RAW`), wird ein Host trotzdem als erreichbar gewertet, sobald mindestens ein konfigurierter Port antwortet - der Lauf bricht dadurch nicht ab.

## Nicht Bestandteil des MVP

Siehe `docs/limitations.md` und `docs/roadmap.md`.
