---
title: Versionshinweise
description: Ablage- und Qualitätsregeln für freigegebene Release Notes.
document_type: Versionshinweise
chapter: Übersicht
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Versionshinweise

Für jede freigegebene Softwareversion wird eine eigene Datei `vMAJOR.MINOR.PATCH.md` angelegt. Entwicklungsstände mit `-dev` gelten nicht als Release.

Release Notes enthalten mindestens Zusammenfassung, neue und geänderte Funktionen, Fehlerbehebungen, Sicherheitsänderungen, Breaking Changes, Installation und Update, Migrationen, Backup-/Restore-Hinweis, bekannte Einschränkungen und Verifikationsnachweise.

Die [Release-Notes-Vorlage](../Releases/RELEASE_NOTES_TEMPLATE.md) ist die Grundlage. Ein unausgefülltes Feld ist kein Nachweis. Release Notes verwenden nur freigegebene Aussagen und unterscheiden Produktänderungen von internen Implementierungsdetails.

Das Changelog sammelt Änderungen während der Entwicklung. Vor Release werden Einträge konsolidiert, einer Version zugeordnet und auf Widersprüche zur Dokumentation geprüft.
