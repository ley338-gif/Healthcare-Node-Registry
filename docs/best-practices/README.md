---
title: Best Practices
description: Bewährte Regeln für Datenpflege und sicheren Betrieb.
document_type: Best Practices
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Best Practices

## Datenpflege

- Verwenden Sie eindeutige Namen und eine belegbare Informationsquelle.
- Pflegen Sie Organisation, System und DICOM-AE als getrennte fachliche Objekte.
- Archivieren Sie veraltete Objekte statt historische Bedeutung zu überschreiben.
- Prüfen Sie AE Titles, Hosts, Ports und Dienste nach Änderungen am Zielsystem.
- Vermeiden Sie Secrets, Patientendaten und unstrukturierte Kopien in Freitextfeldern.

## Berechtigungen

- Verwenden Sie persönliche Konten und minimale Rollen.
- Trennen Sie Anzeige, Verwaltung, Export und schreibende Diagnose soweit vorgesehen.
- Prüfen Sie privilegierte Konten regelmäßig und sperren Sie verwaiste Zugänge.

## Diagnose

- Testen Sie nur registrierte und freigegebene Ziele.
- Beginnen Sie mit Netzwerk und C-ECHO, bevor Sie fachlich stärkere Tests ausführen.
- Begrenzen Sie C-FIND-Suchen und dokumentieren Sie Testzeitpunkt und Zweck.
- Stimmen Sie C-STORE und die Bereinigung synthetischer Zielobjekte ab.

## Betrieb

- Begrenzen Sie Netzwerkzugriffe und verwenden Sie HTTPS.
- Sichern Sie Datenbank und Dokumentablage konsistent.
- Testen Sie Restore und Update vor produktiver Freigabe.
- Behandeln Sie Audit- und Exportdateien als vertrauliche Infrastrukturinformationen.
