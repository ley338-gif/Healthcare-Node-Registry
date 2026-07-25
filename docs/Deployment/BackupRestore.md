# Backup and Restore

## Backupumfang

- PostgreSQL
- Dokumentenspeicher
- Konfiguration ohne offen gelegte Secrets
- Schlüsselmaterial nach gesondertem Verfahren
- Versionsinformation

## Anforderungen

- verschlüsselte Übertragung und Speicherung
- getrenntes Ziel
- definierte Aufbewahrung
- automatisierte Fehlererkennung
- dokumentierter Restore
- regelmäßige Restore-Tests
- Konsistenz zwischen Datenbank und Dokumentenspeicher

## Restore-Testprotokoll

- Datum
- verwendete Version
- Backup-ID
- Zielumgebung
- Dauer
- Integritätsprüfung
- Stichproben
- Abweichungen
- Freigabe

Ein vorhandenes Backup gilt erst nach erfolgreichem Restore-Test als belastbarer Nachweis.
