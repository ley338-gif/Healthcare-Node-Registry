# Operations

## Installationsvoraussetzungen

- unterstütztes Linux
- Docker Engine und Compose Plugin
- DNS und NTP
- TLS-Zertifikat
- ausreichend persistenter Speicher
- getrenntes Backupziel
- SMTP oder alternative Benachrichtigung optional

Für Pilot- und Release-Installationen ist der unveränderliche Build aus [`ReleaseProcess.md`](ReleaseProcess.md) verbindlich. Der Standard-Stack mit Quellcode-Bind-Mounts dient ausschließlich Entwicklung und lokaler Prüfung.

## Betriebsaufgaben

- Anwendung und Abhängigkeiten aktualisieren
- Backup überwachen
- Restore testen
- Speicherverbrauch überwachen
- Logs rotieren
- Zertifikate erneuern
- Benutzer und Rollen prüfen
- Schwachstellenmeldungen verfolgen
- Release Notes vor Update lesen

## HTTPS-Referenzbetrieb

Der lokale Standard-Stack bleibt über HTTP auf Port 8080 testbar. Für einen produktionsnahen HTTPS-Betrieb wird zusätzlich `docker-compose.https.yml` geladen. Die Überlagerung benötigt Docker Compose 2.24.4 oder neuer, ersetzt die Host-Portfreigabe des `web`-Dienstes, leitet Port 80 permanent auf HTTPS um und terminiert TLS auf Port 443.

Der Betreiber stellt in einem geschützten Host-Verzeichnis `tls.crt` mit vollständiger Server-Zertifikatskette und den zugehörigen privaten Schlüssel `tls.key` bereit. Die Dateien werden nur lesbar eingebunden und dürfen nicht im Repository liegen. In `.env` sind mindestens `APP_URL=https://<dns-name>`, `SESSION_SECURE_COOKIE=true` und `HTTPS_CERTIFICATE_HOST_DIRECTORY=<absoluter-host-pfad>` zu setzen. Die Überlagerung erzwingt das Secure-Flag zusätzlich im `app`-Container, sodass ein versehentlich verbliebener Entwicklungswert keine unsicheren Session-Cookies erzeugt.

```bash
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml config --quiet
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml up -d
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml exec app php artisan registry:doctor
```

Die Zertifikatskette, DNS-Namen, Dateirechte und Erneuerung müssen auf dem Zielhost geprüft werden. Fehlen Zertifikat oder Schlüssel, startet Nginx absichtlich nicht. Bei einer Zertifikatserneuerung den `web`-Dienst mit beiden Compose-Dateien neu erstellen. Ein vorgeschalteter Unternehmens-Reverse-Proxy kann weiterhin verwendet werden, muss aber mindestens gleichwertige TLS-, Header- und Cookie-Vorgaben umsetzen.

## Diagnose

Diagnosefunktionen dürfen keine Secrets oder Dokumentinhalte offenlegen. Support-Bundles benötigen explizite Auswahl, Redaction und Auditierung.

Der App-Container benötigt DCMTK sowie ausgehenden TCP-Zugriff auf die freigegebenen Ports registrierter DICOM-Knoten. Produktive Firewalls sollten Egress auf konkrete Zieladressen und Ports begrenzen. Calling AE `NODE_REGISTRY` muss in Zielsystemen autorisiert werden. C-STORE kann ein dauerhaftes synthetisches Objekt erzeugen und erfordert einen abgestimmten Bereinigungsprozess im Zielsystem. Details: [Diagnostic Test Workspace](../Healthcare/DiagnosticTestWorkspace.md).

Für DICOM-TLS wird das über `DIAGNOSTIC_TLS_PROFILE_HOST_DIRECTORY` konfigurierte Zertifikatsverzeichnis read-only in `app` und `worker` eingebunden. Zertifikatslayout, Dateirechte und Rotation sind unter [DICOM-TLS für Diagnose-Runner](../Healthcare/DicomTlsDiagnostics.md) beschrieben.

## Malware-Scanner

Der Compose-Dienst `clamav` stellt ClamD ausschließlich intern auf Port 3310 bereit und aktualisiert seine Signaturen über FreshClam. Der Healthcheck nutzt `/usr/local/bin/clamdcheck.sh`; Signaturen bleiben im Volume `clamav_data` erhalten. Der unverschlüsselte ClamD-Port darf nicht auf dem Host oder in fremden Netzen veröffentlicht werden.

Status prüfen:

```bash
docker compose ps clamav scheduler
docker compose logs clamav
docker compose exec app php artisan registry-documents:rescan --limit=250
docker compose exec app php artisan registry-documents:notify-expiry
```

Bei deaktiviertem oder nicht erreichbarem Scanner bleiben neue und offene Dateien mit einem nicht freigegebenen Scanstatus gesperrt. Der Scheduler wiederholt offene Scans stündlich.

## Discovery-Zeitpläne

Der `scheduler`-Container wertet jede Minute `php artisan discovery:run-schedules` aus. Fällige tägliche und wöchentliche Zeitpläne legen einen normalen Queue-Lauf an, den der `worker` verarbeitet. Die konfigurierte IANA-Zeitzone berücksichtigt Sommer-/Winterzeit; intern werden nächste und letzte Ausführung als UTC gespeichert. Vor jedem Lauf wird die aktuelle Netzfreigabe erneut geprüft. Bei einer Überschneidung mit einem aktiven Lauf wird fünf Minuten gewartet, bei entzogener Freigabe wird der Zeitplan deaktiviert.

```bash
docker compose ps scheduler worker
docker compose logs --tail=200 scheduler
docker compose exec app php artisan discovery:run-schedules
```

## Queue-Worker

Der `worker`-Container aktualisiert bei jedem Laravel-Queue-Loop die Datei `storage/framework/worker-heartbeat`. Der Docker-Healthcheck führt alle zehn Sekunden `php artisan registry:worker-health --quiet` aus und prüft die Queue-Datenbank sowie den Worker-Zustand. Im Leerlauf darf der Heartbeat höchstens 15 Sekunden alt sein. Während eines Jobs gilt die im Queue-Payload hinterlegte Job-Timeoutfrist zuzüglich 15 Sekunden Abschlussfrist; dadurch werden lange Discovery-Läufe nicht fälschlich als Ausfall gewertet, festgefahrene Jobs aber nach ihrer eigenen Obergrenze erkannt. Nach einer Startfrist von 30 Sekunden führen drei aufeinanderfolgende Fehler zum Status `unhealthy`.

```bash
docker compose ps worker db
docker compose exec worker php artisan registry:worker-health
docker compose logs --tail=200 worker
```

Der Heartbeat enthält nur einen Unix-Zeitstempel und keine Job-, Ziel- oder Patientendaten. Bei mehreren künftig skalierten Worker-Replikaten wäre ein je Replikat getrennter Heartbeat erforderlich; der aktuelle Referenz-Stack betreibt genau einen Worker.
