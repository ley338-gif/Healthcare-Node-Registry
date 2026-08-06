---
title: Fehlerbehebung
description: Symptomorientierte Diagnose für häufige HNR-Probleme.
document_type: Fehlerbehebung
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Fehlerbehebung

## Anmeldung nicht möglich

Prüfen Sie URL, Kontoaktivierung, Anmeldedaten, Uhrzeit und Erreichbarkeit der Anwendung. Administratoren prüfen Benutzerstatus und serverseitige Logs, ohne Passwörter zu protokollieren.

## Suchergebnis fehlt

Prüfen Sie Schreibweise, einen eindeutigen Suchbegriff, Objektstatus und Berechtigung. Suche zeigt keine unautorisierten Ressourcen.

## DICOM-Ziel nicht erreichbar

Prüfen Sie den gespeicherten Host und Port, DNS im App-Container, Firewall-Egress, Zielprozess und Routing. Ein Browserzugriff auf den Host ist kein Nachweis für Containererreichbarkeit.

## Association wird abgelehnt

Prüfen Sie Called und Calling AE, IP-Freigabe am SCP, angebotene SOP Class und Transfer Syntax sowie Zielprotokolle. Ein offener TCP-Port genügt nicht.

## C-FIND erfolgreich, aber ohne Treffer

Keine Treffer sind technisch möglich. Prüfen Sie Zeitraum, Suchschlüssel, Informationsmodell, Berechtigung des Calling AE und Datenbestand im Zielsystem.

## Dokument lässt sich nicht öffnen

Prüfen Sie Downloadberechtigung, Archivstatus, Scanstatus, vorhandene Version und private Storage-Verfügbarkeit.

## Nach einem Update treten Fehler auf

Stoppen Sie weitere Änderungen, sichern Sie Logs, prüfen Sie Migrationen und Release Notes und verwenden Sie nur den vorab festgelegten Rollbackweg. Stellen Sie Datenbank und Dateien konsistent wieder her.

Weitere Betriebsprüfungen bietet das Konsolenkommando `php artisan hnr:doctor`; konkrete Ausführung richtet sich nach der lokalen Containerumgebung.

## Discovery-Lauf bleibt bei "geplant"/"läuft" hängen

Prüfen Sie, ob der `worker`-Container läuft (`docker compose ps worker`) und Jobs aus der Queue `discovery` verarbeitet (`docker compose logs worker`). Ohne laufenden Worker werden Discovery-Läufe angelegt, aber nie verarbeitet.

## Discovery-Wizard lässt keinen Start zu ("keine erlaubten Netzbereiche")

Ein Administrator muss unter Einstellungen > Discovery mindestens einen aktiven Netzbereich freigeben, bevor Läufe gestartet werden können. Standardmäßig sind ausschließlich private (RFC1918) Bereiche vorgesehen.

## Discovery findet keine erreichbaren Hosts, obwohl Geräte online sind

Prüfen Sie, ob `ping` im `app`/`worker`-Container über `CAP_NET_RAW` verfügt (`docker compose exec worker ping -c1 127.0.0.1`). Fehlt die Berechtigung, wird jeder Host als per ICMP nicht erreichbar gewertet; ist zusätzlich "auch nicht antwortende Hosts auf Ports prüfen" deaktiviert, werden keine Hosts gespeichert. Aktivieren Sie diese Option oder korrigieren Sie die Container-Capabilities.

## DICOM-C-ECHO in Discovery schlägt für alle Kandidaten fehl

Prüfen Sie, ob `echoscu` im Container vorhanden ist (`docker compose exec worker which echoscu`), ob der Zielport tatsächlich ein DICOM-Dienst ist (ein offener Port beweist das nicht) und ob der getestete Called-AE-Titel-Kandidat mit dem am Zielsystem konfigurierten Wert übereinstimmt.
