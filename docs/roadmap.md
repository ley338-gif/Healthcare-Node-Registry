---
title: Roadmap – Discovery Version 2
description: Dokumentierte, aber bewusst nicht in das MVP aufgenommene Funktionen.
document_type: Roadmap
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-25
---

# Roadmap: Mögliche Version-2-Funktionen für Discovery

## Pilot-Readiness

- [x] unveränderlicher Release-Build ohne produktive Quellcode-Bind-Mounts sowie dokumentierter Installations-, Upgrade- und Rollback-Prozess
- [ ] automatisierte Supply-Chain-Nachweise einschließlich Secret Scan, Container Scan und SBOM
- [ ] überprüfbare Pilot-Go-/No-Go-Matrix mit Release-, Security-, Betriebs- und Healthcare-E2E-Nachweisen

Die offenen Punkte sind pilot-blockierend und werden vor weiteren Discovery-Produktfunktionen bearbeitet.

Diese Funktionen sind bewusst **nicht** Bestandteil des DICOM Discovery & Topology MVP. Sie werden hier dokumentiert, um Erwartungen zu steuern, aber nicht ungefragt implementiert.

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
