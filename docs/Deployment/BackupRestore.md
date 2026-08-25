# Backup and Restore

## Umfang

- PostgreSQL
- privater Dokumentenspeicher `storage/app/private`
- Konfiguration ohne offengelegte Secrets
- Schlüsselmaterial nach getrenntem Verfahren
- Versions- und Prüfsummenmanifest

## PowerShell

```powershell
.\scripts\backup.ps1
.\scripts\restore.ps1 -BackupDirectory .\backups\registry-YYYYMMDD-HHMMSS -ConfirmRestore
```

Blockiert eine restriktive Windows Execution Policy lokale Skripte, können die versionierten Repository-Skripte einmalig ohne dauerhafte Richtlinienänderung ausgeführt werden:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\backup.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\restore.ps1 -BackupDirectory .\backups\registry-YYYYMMDD-HHMMSS -ConfirmRestore
```

Die Skripte prüfen native Docker-Exitcodes, Datenbank- und Archivformat sowie SHA-256-Werte. Ein Restore validiert beide Sicherungen vor dem Stoppen des Webdienstes. Schlägt ein Restore nach diesem Punkt fehl, bleibt der Webdienst zum Schutz vor einem inkonsistenten Zustand gestoppt. Die Skripte verschlüsseln das Backup nicht automatisch, da Schlüsselverwaltung und Zielsystem installationsspezifisch festgelegt werden müssen.

## Anforderungen

- getrenntes und verschlüsseltes Backup-Ziel
- Least Privilege
- definierte Aufbewahrung
- regelmäßige Restore-Tests
- Schlüssel nie zusammen mit dem Backup speichern
- Backups nie in Git committen

Für den Pilot gelten mindestens RPO 24 Stunden und RTO 8 Stunden. Das bedeutet ein tägliches konsistentes Backup auf ein getrenntes, verschlüsseltes Ziel und einen gemessenen Restore vor Pilotstart. Der vollständige Restore-Test wird mindestens quartalsweise und nach wesentlichen Änderungen an Datenbank, Storage oder Sicherungsverfahren wiederholt. Details und Alarmgrenzen: [Betriebliche Pilotabnahme](../Pilot/OperationalAcceptance.md).

- PostgreSQL und `storage/app/private/registry-documents` aus demselben konsistenten Sicherungszeitpunkt wiederherstellen
- alte Versionen und archivierte Dokumente vollständig in Sicherung und Restore einbeziehen
- nach Restore Dateiexistenz, Größe und SHA-256 gegen `registry_document_versions` prüfen
- verwaiste Datenbankreferenzen oder Storage-Dateien melden und nicht automatisch löschen

Die Anwendung archiviert Registry-Dokumente fachlich, löscht aber keine Datei physisch. Bis eine verbindliche Aufbewahrungsrichtlinie und ein getesteter Bereinigungsprozess existieren, müssen alle Dokumentversionen gesichert und wiederhergestellt werden.

## Restore-Testprotokoll

Datum, Verantwortlicher, Version, Backup-ID, Dauer, Prüfsummen, Login-Test, Datenstichproben, Abweichungen, Korrekturmaßnahmen und Freigabe dokumentieren.

Ein Backup gilt erst nach erfolgreichem Restore-Test als belastbarer Nachweis.
