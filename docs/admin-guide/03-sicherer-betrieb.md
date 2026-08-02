---
title: Sicherer Betrieb
description: Härtung, Protokollierung und regelmäßige Betriebskontrollen.
document_type: Administratorhandbuch
chapter: 3
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Sicherer Betrieb

## Mindestmaßnahmen

- HNR nur in kontrollierten Netzen und über HTTPS bereitstellen;
- administrative Zugriffe beschränken;
- ausgehenden DICOM-Verkehr auf genehmigte Ziele und Ports begrenzen;
- Secrets und Schlüssel getrennt schützen und rotieren;
- Betriebssystem, Images und Abhängigkeiten kontrolliert aktualisieren;
- Audit- und Anwendungslogs überwachen, ohne sensible Inhalte unnötig zu vervielfältigen;
- private Dokumentablage nicht direkt per Webserver veröffentlichen;
- Scannerstatus und Verhalten bei nicht geprüften Uploads dokumentieren;
- Zeit, DNS, Speicherplatz und Jobverarbeitung überwachen.

Die Anwendung ist kein SIEM oder allgemeines Monitoring. Betreiber müssen Alarmierung, Incident-Prozess und Aufbewahrung in ihre vorhandenen Betriebsprozesse integrieren. Siehe [Bedrohungsmodell](../Security/ThreatModel.md), [Logging](../Security/Logging.md) und [Dateiupload-Sicherheit](../Security/FileUploadSecurity.md).
