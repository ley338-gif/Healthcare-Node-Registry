---
title: ADR-0011 – Discovery-Scan-Architektur
description: Technische Grundentscheidungen für die Netzwerk- und DICOM-Discovery.
document_type: ADR
chapter: ADR-0011
status: accepted
version: 1.0
last_updated: 2026-08-06
---

# ADR-0011: Discovery-Scan-Architektur

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-06
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: 0.4.0-dev (Discovery MVP)
- Ersetzt: -
- Ersetzt durch: -

## Kontext

Das Discovery-MVP muss unbekannte oder teilweise dokumentierte Netzwerke auf mögliche DICOM-Systeme prüfen: Host-Erreichbarkeit, begrenzte TCP-Portprüfung, begrenzte DICOM-C-ECHO-Tests. Die Anwendung läuft in einem nicht-privilegierten Container ohne Redis und ohne bisherige Queue-Nutzung. Der Auftrag verlangt explizit: keine unkontrollierte Shell-Ausführung, keine freien Nmap-Parameter, begrenzte Parallelität, begrenzte AE-Titel-Versuche, und dass ein einzelner fehlerhafter Host den gesamten Lauf nicht abbricht.

## Entscheidung

1. **Kein Nmap.** Portprüfung erfolgt über PHP-eigene, nicht-blockierende Sockets (`stream_socket_client` mit `STREAM_CLIENT_ASYNC_CONNECT` + `stream_select`, siehe `App\Services\Discovery\Probes\NativeTcpPortProbe`). Das vermeidet die gesamte Angriffsfläche von Shell-Argumenten/NSE-Skripten vollständig und liefert echte Parallelität innerhalb eines Batches.
2. **ICMP-Ping über das System-Binary**, gestartet über `Symfony\Component\Process\Process` mit fester Argumentliste (`App\Services\Discovery\Probes\NativeHostProbe`). Fällt Ping aus (fehlende `CAP_NET_RAW`), wird ein Host trotzdem als erreichbar gewertet, sobald ein konfigurierter Port antwortet - der Lauf bricht dadurch nicht ab.
3. **DICOM-C-ECHO über DCMTK `echoscu`**, ebenfalls über `Process` mit fester Argumentliste, nie über einen Shell-String. AE-Titel-Versuche sind pro Host und Port hart auf `config('discovery.max_ae_attempts_per_port')` begrenzt (`App\Services\Discovery\DicomEchoScanService`).
4. **Kein Redis.** Die Queue nutzt weiterhin den bereits konfigurierten `database`-Treiber; ein neuer, eigenständiger `worker`-Container führt `php artisan queue:work database --queue=discovery,default` aus. Redis wäre für die erwartete Last (ein bis wenige gleichzeitige Läufe) nicht erforderlich gewesen.
5. **Batch-Parallelität statt Multi-Prozess-Architektur.** `RunDiscoveryScanJob` verarbeitet IP-Adressen in Batches der Größe `max_parallel_hosts`; innerhalb eines Batches laufen Ping-Prozesse, Portscan-Sockets und Echo-Prozesse jeweils gebündelt parallel. Es wird kein `pcntl_fork()` und keine zusätzliche Prozessorchestrierung eingeführt.
6. **Ein Zielbereich pro Lauf.** Der Wizard erfasst genau einen CIDR- oder Start-/End-Bereich; `discovery_targets` als eigene Tabelle wurde bewusst nicht eingeführt, da der Wizard ohnehin nur einen Bereich pro Lauf erfasst (Schritt 1 „Zielbereich“, Singular).
7. **Keine automatischen Topologie-Verbindungen.** Die Übernahme eines Fundes erzeugt ausschließlich `System`/`DicomNode`; `DicomConnection`-Datensätze entstehen ausschließlich durch eine bewusste Benutzeraktion. Ein neues Feld `evidence_status` auf `dicom_connections` steuert nur die Darstellung (Linienstil), nicht die Existenz einer Verbindung.

## Alternativen

- **Nmap mit fester Parameterliste**: geprüft, verworfen. Selbst mit fester Parameterliste bleibt die Angriffsfläche (Basis-Image-Abhängigkeit, NSE-Skript-Pfad, OS-Erkennung) größer als bei reinen PHP-Sockets, ohne einen Mehrwert für die im MVP benötigte einfache Connect-Prüfung zu bieten.
- **Redis + Laravel Horizon**: geprüft, verworfen für das MVP. Erhöht die Infrastrukturkomplexität ohne im MVP-Lastprofil (wenige gleichzeitige Läufe) einen Vorteil gegenüber dem bereits vorhandenen `database`-Queue-Treiber zu bringen. Als klar dokumentierte Option für Version 2 vorgesehen, falls hohe Parallelität mehrerer gleichzeitiger Läufe benötigt wird.
- **`pcntl_fork()` für echte Multi-Prozess-Parallelität**: geprüft, verworfen. Deutlich höhere Komplexität und Fragilität (geteilte DB-Verbindungen, Signal-Handling) für einen im MVP nicht erforderlichen Parallelitätsgrad; nicht-blockierendes I/O innerhalb eines Batches erreicht das geforderte Verhalten mit deutlich weniger Risiko.

## Konsequenzen

### Positiv

- Minimale, gut überprüfbare Angriffsfläche für die Prozessausführung (zwei feste `Process`-Aufrufe, keine Shell-Strings).
- Keine neue Infrastrukturkomponente (Redis) nötig.
- Ein einzelner Host- oder Batch-Fehler beeinträchtigt nicht den restlichen Lauf (try/catch je Batch in `RunDiscoveryScanJob`).

### Negativ und Risiken

- Die Parallelität ist auf einen Batch begrenzt; sehr große, stark parallele Läufe sind langsamer als mit einer dedizierten Multi-Prozess- oder Redis-Queue-Architektur. Für die im MVP vorgesehene Nutzung (einzelne, gezielte Discovery-Läufe) ausreichend.
- ICMP-Ping funktioniert nur, wenn der Container über die nötigen Capabilities verfügt; dies ist dokumentiert und durch den Portscan-Fallback abgefedert, aber kein Ersatz für einen echten ICMP-Test.

## Verifikation

- `tests/Feature/DiscoveryScanJobTest.php` führt den vollständigen Job gegen gemockte Probes/Runner aus (kein echter Prozess, kein echtes Netzwerk) und prüft Batch-Abbruch bei `cancelling`.
- `composer quality` (Pint, Larastan, PHPUnit) und `npm run check` müssen grün sein.
- Manuelle Prüfung: `docker compose exec worker php artisan queue:work --once` verarbeitet einen Testlauf gegen einen erreichbaren, freigegebenen Bereich.

## Referenzen

- `docs/Features/dicom-discovery.md`
- `docs/Security/ThreatModel.md`
- `app/Jobs/RunDiscoveryScanJob.php`
- `app/Services/Discovery/`
