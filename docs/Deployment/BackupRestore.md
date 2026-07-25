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

Die Skripte prüfen SHA-256-Werte. Sie verschlüsseln das Backup nicht automatisch, da Schlüsselverwaltung und Zielsystem installationsspezifisch festgelegt werden müssen.

## Anforderungen

- getrenntes und verschlüsseltes Backup-Ziel
- Least Privilege
- definierte Aufbewahrung
- regelmäßige Restore-Tests
- Schlüssel nie zusammen mit dem Backup speichern
- Backups nie in Git committen

## Restore-Testprotokoll

Datum, Verantwortlicher, Version, Backup-ID, Dauer, Prüfsummen, Login-Test, Datenstichproben, Abweichungen, Korrekturmaßnahmen und Freigabe dokumentieren.

Ein Backup gilt erst nach erfolgreichem Restore-Test als belastbarer Nachweis.
