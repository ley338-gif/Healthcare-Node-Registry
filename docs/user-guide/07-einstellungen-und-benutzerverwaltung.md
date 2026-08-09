---
title: Einstellungen und Benutzerverwaltung
description: Benutzer, Rollen und Berechtigungen verwalten.
document_type: Benutzerhandbuch
chapter: 7
status: draft
version: 0.1
last_updated: 2026-08-02
---

# Einstellungen und Benutzerverwaltung

## Zweck

Die Benutzerverwaltung ist ein Unterbereich von **Einstellungen** und verwendet das bestehende Rollen- und Berechtigungssystem.

## Referenzdaten

Benutzer mit `settings.manage` pflegen unter **Einstellungen > Referenzdaten** Systemtypen, Systemstatus und Hersteller. Der technische Code ist nach der Anlage unveränderlich; Anzeigename, Reihenfolge und Aktivstatus können angepasst werden. Deaktivierte Werte bleiben an bestehenden Systemen sichtbar, stehen für neue Zuordnungen jedoch nicht mehr zur Verfügung. Die für Anlage und Archivierung benötigten Statuswerte sind geschützt.

Eine frische Installation enthält keine vorgegebenen Hersteller. Legen Sie zuerst die tatsächlich verwendeten Hersteller an. Danach stehen aktive Werte in Systemanlage, Systembearbeitung und Discovery-Übernahme zur Auswahl. Bereits vorhandene Herstellertexte werden bei einem Update automatisch und ohne Umbenennung übernommen.

## Benutzer

Berechtigte Administratoren können lokale Benutzer anlegen, Namen und E-Mail-Adresse ändern, Konten aktivieren oder sperren, Rollen zuweisen und Passwörter zurücksetzen. Verwenden Sie personenbezogene Angaben sparsam und weisen Sie nur erforderliche Rollen zu.

## Rollen

Rollen bündeln bestehende Berechtigungen. Prüfen Sie vor Änderungen alle betroffenen Benutzer. Systemgeschützte Rollen oder Rollen mit noch bestehenden Zuordnungen können Einschränkungen beim Ändern oder Löschen besitzen.

## Sicheres Vorgehen

- Verwenden Sie persönliche Konten statt geteilter Zugangsdaten.
- Vergeben Sie minimale Rechte.
- Prüfen Sie Rollen regelmäßig.
- Sperren Sie nicht mehr benötigte Konten zeitnah.
- Übermitteln Sie zurückgesetzte Passwörter über einen kontrollierten Kanal.
- Bewahren Sie mindestens einen geprüften administrativen Wiederherstellungszugang auf.

Ein vollständiger organisatorischer Access-Review bleibt Betreiberaufgabe. Details enthält die [Benutzerverwaltung](../Features/UserManagement.md).
