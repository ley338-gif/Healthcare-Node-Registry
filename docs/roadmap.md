---
title: Roadmap – Discovery Version 2
description: Dokumentierte, aber bewusst nicht in das MVP aufgenommene Funktionen.
document_type: Roadmap
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-11
---

# Roadmap: Mögliche Version-2-Funktionen für Discovery

Diese Funktionen sind bewusst **nicht** Bestandteil des DICOM Discovery & Topology MVP. Sie werden hier dokumentiert, um Erwartungen zu steuern, aber nicht ungefragt implementiert.

- PDF-Export der Discovery-Ergebnisse und der Review-Queue (gefilterte CSV- und JSON-Exporte sind implementiert)
- Worklist-C-FIND-, Query/Retrieve-, Storage-Commitment- und MPPS-Tests im Discovery-Kontext (bestehen bereits für verifizierte Registry-Knoten im Test-Arbeitsbereich, nicht aber als Discovery-Scan-Phase)
- DICOM-TLS als Discovery-Prüfoption
- SNMP-Abfragen als zusätzliche Erkennungsquelle
- Passive Netzwerk-Sensorik/Port-Mirroring/Network-TAP
- Herstellerspezifische Klassifizierungsprofile
- Import von Conformance Statements zur Anreicherung der Klassifizierung
- Monitoring und Alarmierung auf Basis wiederholter Discovery-Läufe
- Vier-Augen-Freigabe-Workflow für die Übernahme in die Registry
- Erweiterte Organisationsstruktur-Zuordnung direkt im Discovery-Wizard
- Versionierte Topologie mit Änderungshistorie
- Automatische Änderungsberichte zwischen zwei Discovery-Läufen
- Echte Multi-Prozess- oder Redis-gestützte Parallelität für sehr große, zeitkritische Scans (siehe ADR-0011)

Siehe `docs/limitations.md` für Einschränkungen des aktuellen MVP.
