# Betriebliche Pilotabnahme

## Verbindliche Mindestziele

| Bereich | Pilot-Baseline | Nachweis | Owner |
|---|---|---|---|
| RPO | höchstens 24 Stunden | Zeitstempel des letzten konsistenten, verschlüsselten Backups | Operations |
| RTO | höchstens 8 Stunden | gemessene vollständige Wiederherstellung bis Doctor, Login und Datenstichprobe erfolgreich sind | Operations |
| Backupziel | getrenntes, verschlüsseltes Ziel mit eigenständiger Zugriffskontrolle; Schlüssel nicht im Backup | Ziel- und Berechtigungsreview | Operations / Security |
| Restore-Test | vor Pilotstart und danach mindestens quartalsweise sowie nach wesentlicher Backup-/Storage-Änderung | ausgefülltes Restore-Protokoll | Operations |
| Logrotation | Docker-Logs je Container standardmäßig `10m` × 5 Dateien; zentrale Logs gemäß lokaler Richtlinie | Compose-Konfiguration und Größenprüfung | Operations |
| Speicher | Warnung bei 80 %, kritisch bei 90 % für Datenbank-, App-Storage-, Docker- und Backup-Dateisystem | ausgelöster Testalarm | Operations |
| Zertifikate | Warnung 30 Tage, kritisch 14 Tage vor Ablauf für HTTPS- und DICOM-TLS-Zertifikate | Zertifikatsinventar und Testalarm | Security / Operations |
| Verfügbarkeit | Alarm bei `/up`-Fehler, unhealthy PostgreSQL/Worker/ClamAV oder wiederholtem Scheduler-/Queue-Fehler | ausgelöster und quittierter Testalarm | Operations |
| Backup | Alarm bei ausbleibendem oder fehlgeschlagenem Tagesbackup und bei Prüfsummenfehler | ausgelöster und quittierter Testalarm | Operations |
| Security | Alarm bei fehlgeschlagenem wöchentlichem Supply-Chain-Lauf, ClamAV-Signatur-/Healthfehler oder wiederholter privilegierter Anmeldungssperre | Ticket-/Alarmweg mit Empfänger und Eskalation | Security |

Abweichungen von RPO/RTO oder Schwellen sind vor Pilotstart als Risiko mit Ablaufdatum, kompensierender Maßnahme und Freigabe durch Pilot Owner, Operations und Security zu dokumentieren. Eine Abweichung darf kein fehlgeschlagenes P0-Gate verdecken.

## Abnahmeablauf

1. Zielhost, Docker-/Compose-Version, RC-SHA und Image-Digests festhalten.
2. HTTPS-Zertifikat, sichere Session-Konfiguration, Dateirechte und Secrets-Ablage prüfen.
3. Storage-Commitment-Port auf die freigegebene DICOM-Hostadresse binden; Host-Firewall nur für vereinbarte Quellziele öffnen.
4. DICOM-Egress auf die in der Registry freigegebenen Zieladressen und Ports beschränken.
5. Alle Container-Healthchecks, `/up`, `registry:doctor`, Worker-Heartbeat, Scheduler und ClamAV prüfen.
6. Backup von Datenbank und privatem Storage auf das getrennte Ziel erstellen und Prüfsummen validieren.
7. Restore in isolierter Umgebung durchführen; Dauer, Login, Registry-Beziehungen, Dokumentdateien und Prüfsummen protokollieren.
8. Logrotation und jede oben definierte Warn-/Kritisch-Meldung kontrolliert auslösen, Empfang und Eskalation dokumentieren.
9. Betriebs-, Security- und Pilot-Owner zeichnen das Protokoll ab.

## Betriebsrhythmus

- täglich: Backupstatus, Health- und Speicheralarme prüfen;
- wöchentlich: Supply-Chain-Lauf, ClamAV-Signaturen, fehlgeschlagene Jobs und Zertifikatsvorschau prüfen;
- monatlich: Benutzer-/Rollenänderungen, Kapazität und Backup-Restore-Trend prüfen;
- quartalsweise: vollständiger Restore-Test und Rechte-Review;
- ereignisbezogen: nach Release, Zertifikatsrotation, Storage-/Backupänderung oder Security Incident erneut prüfen.

Technische Verfahren: [Operations](../Deployment/Operations.md), [Backup und Restore](../Deployment/BackupRestore.md), [Releaseprozess](../Deployment/ReleaseProcess.md).
