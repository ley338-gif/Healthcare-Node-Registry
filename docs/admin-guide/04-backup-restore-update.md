---
title: Backup, Restore und Update
description: Datensicherung, Wiederherstellung und kontrollierte Aktualisierung.
document_type: Administratorhandbuch
chapter: 4
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Backup, Restore und Update

Eine vollständige Sicherung umfasst Datenbank, private Dokumentablage und erforderliche Konfiguration beziehungsweise Secrets nach dem lokalen Wiederanlaufkonzept. Schützen Sie Sicherungen mindestens so streng wie das Produktivsystem.

## Restore-Prüfung

1. isolierte Zielumgebung vorbereiten;
2. Datenbank und Dateien aus demselben Sicherungszeitpunkt wiederherstellen;
3. Anwendung mit passender Softwareversion starten;
4. Anmeldung, Datenstichproben, Dokumentzugriff und zentrale Beziehungen prüfen;
5. Dauer, Abweichungen und Freigabe protokollieren.

## Update

Lesen Sie Release Notes und Migrationen, erstellen Sie eine geprüfte Sicherung, legen Sie Wartungsfenster und Rollbackweg fest, aktualisieren Sie Artefakte und führen Sie Migrationen nur kontrolliert aus. Prüfen Sie anschließend Healthcheck, Anmeldung, Registry, Dokumentzugriff, Audit und relevante DICOM-Funktionen.

Verbindliche Details: [Backup und Restore](../Deployment/BackupRestore.md).
