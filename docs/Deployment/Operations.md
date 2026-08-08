# Operations

## Installationsvoraussetzungen

- unterstütztes Linux
- Docker Engine und Compose Plugin
- DNS und NTP
- TLS-Zertifikat
- ausreichend persistenter Speicher
- getrenntes Backupziel
- SMTP oder alternative Benachrichtigung optional

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

## Diagnose

Diagnosefunktionen dürfen keine Secrets oder Dokumentinhalte offenlegen. Support-Bundles benötigen explizite Auswahl, Redaction und Auditierung.

Der App-Container benötigt DCMTK sowie ausgehenden TCP-Zugriff auf die freigegebenen Ports registrierter DICOM-Knoten. Produktive Firewalls sollten Egress auf konkrete Zieladressen und Ports begrenzen. Calling AE `NODE_REGISTRY` muss in Zielsystemen autorisiert werden. C-STORE kann ein dauerhaftes synthetisches Objekt erzeugen und erfordert einen abgestimmten Bereinigungsprozess im Zielsystem. Details: [Diagnostic Test Workspace](../Healthcare/DiagnosticTestWorkspace.md).

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
