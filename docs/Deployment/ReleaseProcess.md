# Release-, Installations-, Upgrade- und Rollback-Prozess

## Release-Kandidat

Der erste Pilot-Kandidat ist als `0.9.0-rc.1` vorgesehen. Ein freigegebenes Artefakt wird durch drei gemeinsam dokumentierte Werte identifiziert:

- Git-Commit (`HNR_REVISION`),
- fachliche Version (`HNR_VERSION`),
- unveränderlicher Image-Tag (`HNR_IMAGE_TAG`).

Für einen belastbaren Nachweis muss `HNR_IMAGE_TAG` eindeutig sein; `latest` ist nicht zulässig. Nach dem Build sind die Image-Digests im Freigabeprotokoll festzuhalten.

## Release-Build

Die Release-Überlagerung baut Anwendungscode, Composer-Abhängigkeiten und Frontend-Artefakte in die Images ein. Anders als der Entwicklungs-Stack bindet sie keinen Quellcode vom Host ein.

```bash
export HNR_VERSION=0.9.0-rc.1
export HNR_IMAGE_TAG=0.9.0-rc.1
export HNR_REVISION="$(git rev-parse HEAD)"
docker compose -f docker-compose.yml -f docker-compose.release.yml build --pull
docker image inspect "healthcare-node-registry-app:${HNR_IMAGE_TAG}" --format '{{.Id}}'
docker image inspect "healthcare-node-registry-web:${HNR_IMAGE_TAG}" --format '{{.Id}}'
docker image inspect "healthcare-node-registry-postgres:${HNR_IMAGE_TAG}" --format '{{.Id}}'
docker image inspect "clamav/clamav:1.5.3@sha256:d06c1d6a451d616e1dd79b42f44c8c8c291bba9cf4e75ebc4d0e43c1c6dd87bb" --format '{{.Id}}'
```

Das Datenbankimage entfernt den Root-Startpfad des offiziellen Basisimages und läuft fest als Benutzer `postgres`. Nach dem Push der drei gebauten Images in eine interne Registry ist zusätzlich der dort ausgewiesene Repo-Digest zu protokollieren und beim Zielabruf zu verifizieren; der gepinnte ClamAV-Digest wird ebenfalls festgehalten. Auf PowerShell werden die drei Werte über `$env:HNR_VERSION`, `$env:HNR_IMAGE_TAG` und `$env:HNR_REVISION` gesetzt. Vor einer Freigabe müssen beide Compose-Varianten validiert werden:

```bash
docker compose -f docker-compose.yml -f docker-compose.release.yml config --quiet
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml config --quiet
```

## Erstinstallation

1. Zielhost, DNS, NTP, Firewall, Backup-Ziel und TLS-Zertifikate freigeben.
2. `.env` aus `.env.example` erstellen und alle Entwicklungswerte ersetzen.
3. Eindeutigen Image-Tag und freigegebenen Commit setzen.
4. Release-Images bauen oder aus der freigegebenen internen Registry anhand ihres Digests beziehen.
5. Zunächst nur Datenbank und ClamAV starten.
6. Migrationen einmalig über das Release-Image ausführen.
7. Initialen Administrator kontrolliert anlegen.
8. Gesamten Stack starten und die Abnahmeprüfungen protokollieren.

```bash
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml up -d db clamav
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml run --rm app php artisan migrate --force
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml run --rm app php artisan registry:create-admin
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml up -d
docker compose -f docker-compose.yml -f docker-compose.release.yml -f docker-compose.https.yml exec app php artisan registry:doctor
```

## Upgrade

1. Release Notes, Migrationen und bekannte Einschränkungen prüfen.
2. Gemeinsam konsistentes Backup von Datenbank und privatem Storage erstellen und dessen Prüfsummen sichern.
3. Wiederherstellbarkeit des verwendeten Backup-Verfahrens nachweisen.
4. Neue Images unter einem neuen, eindeutigen Tag bereitstellen; vorhandene Tags nicht überschreiben.
5. Wartungsfenster beginnen und schreibende Zugriffe stoppen.
6. Migrationen mit dem neuen App-Image ausführen.
7. Dienste mit dem neuen Image-Tag neu erstellen.
8. Doctor, Health-Endpunkt, Login, Registry, Dokumentzugriff, Audit, Worker und pilotrelevante DICOM-Funktionen prüfen.

## Rollback

Ein Rollback ist nur zulässig, wenn Migrationen und Datenformat zur vorherigen Version kompatibel sind. Andernfalls wird der vor dem Upgrade erzeugte, verifizierte Sicherungsstand in einer isolierten Umgebung wiederhergestellt.

1. Fehlerbild, Zeitpunkt und betroffene Version dokumentieren.
2. Schreibende Zugriffe stoppen.
3. Bei kompatibler Datenbank den vorherigen unveränderlichen Image-Tag setzen und Dienste neu erstellen.
4. Bei inkompatibler Migration Datenbank und privaten Storage aus demselben Sicherungsstand mit `scripts/restore.ps1` wiederherstellen.
5. `registry:doctor`, `/up`, Login, Dokumentzugriff, Audit-Kettenprüfung und Worker-Status prüfen.
6. Rollback-Ergebnis, Dauer und verbleibende Abweichungen freigeben lassen.

Produktive Migrationen werden nie automatisiert zurückgerollt. Vor jedem Pilot-Upgrade ist anhand der konkreten Migrationen festzulegen, welcher der beiden Wege zulässig ist.

## Freigabenachweis

Der Nachweis enthält mindestens Version, Commit, Image-Tags und -Digests, Build-Zeitpunkt, Build-Umgebung, Compose-Version, Testergebnisse, SBOM-/Scan-Verweise, Backup-ID, Restore-Nachweis, Migrationsergebnis, Abnahmeergebnis, verantwortliche Rollen und bekannte Risiken.
