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
