---
title: Entwicklungsumgebung
description: Lokalen Docker-basierten Entwicklungsstand einrichten.
document_type: Entwicklerhandbuch
chapter: 1
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Entwicklungsumgebung

Die HNR verwendet Laravel, PHP, PostgreSQL, Inertia, Vue 3 und TypeScript in einem Docker-Compose-Workflow. Verwenden Sie die im Repository festgelegten Versionen und Lockfiles.

Vor Änderungen sind Branch, Arbeitsbaum, Umgebungsdatei, Containerstatus und Datenbankzustand zu prüfen. Secrets und lokale Zugangsdaten dürfen nicht committed werden. Initialisierung, Start und gebräuchliche Befehle beschreibt [Lokale Entwicklung](../Development/LocalDevelopment.md); Containerrollen erläutert die [Docker-Architektur](../Development/DockerArchitecture.md).

Änderungen an Abhängigkeiten, Images oder Laufzeitversionen benötigen Kompatibilitätsprüfung und gegebenenfalls ein ADR.
