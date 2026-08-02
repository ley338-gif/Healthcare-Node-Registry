---
title: Tests und Qualitätsprüfungen
description: Risikobasierte Teststrategie und verbindliche Qualitätsbefehle.
document_type: Entwicklerhandbuch
chapter: 3
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Tests und Qualitätsprüfungen

Feature-Tests prüfen HTTP-Verhalten, Policies, Validierung, Datenintegrität und Audit. Unit-Tests eignen sich für isolierte Wertobjekte und Parser. Vue- und TypeScript-Prüfungen sichern Komponentenlogik und Typen.

Kritische Negativfälle umfassen fehlende Berechtigungen, archivierte Objekte, manipulierte IDs, ungültige Ziele, Timeouts, sensible Daten und fehlerhafte Uploads.

Vor Abschluss sind auszuführen:

```powershell
docker compose run --rm node npm run check
docker compose --profile test run --rm app-test composer quality
docker compose run --rm node npm run build
```

Fehler werden behoben und nicht durch Abschalten von Regeln umgangen. Weitere Vorgaben enthält [Testing](../Development/Testing.md).
