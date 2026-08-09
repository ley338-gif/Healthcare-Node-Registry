---
title: ADR-0021 – Administrierbare Hersteller-Referenzdaten
description: Entscheidung zur kontrollierten Herstellerpflege bei unveränderter Systemspalte.
document_type: Architecture Decision Record
status: Accepted
version: 1.0
last_updated: 2026-08-09
---

# ADR-0021: Administrierbare Hersteller-Referenzdaten

## Status

Accepted

## Kontext

Hersteller wurden bisher als freier Text in `systems.vendor` gespeichert. Dadurch entstanden unterschiedliche Schreibweisen, und Administratoren konnten die Auswahlliste nicht zentral pflegen. Bestehende Installationen können bereits eigene Herstellerbezeichnungen enthalten, die bei einem Update nicht verloren gehen dürfen.

## Entscheidung

Hersteller werden in der eigenen Tabelle `manufacturers` mit öffentlicher Kennung, unveränderlichem Code, Anzeigename, Sortierung und Aktivstatus geführt. `systems.vendor` bleibt aus Kompatibilitätsgründen die nullable Referenzspalte und verweist per Fremdschlüssel auf `manufacturers.code`.

Die Migration übernimmt jeden vorhandenen nichtleeren Herstellerwert unverändert als Code und Anzeigename, bevor der Fremdschlüssel aktiviert wird. Dadurch bleiben bestehende Datensätze und Exporte fachlich lesbar. Neue Codes folgen dem bereits für Referenzdaten verwendeten stabilen Codeformat.

Neue Systemzuordnungen, Discovery-Übernahmen und CSV-Importe akzeptieren nur aktive Hersteller. Ein deaktivierter Hersteller darf an einem bestehenden System unverändert erhalten bleiben. Referenzwerte werden nicht physisch gelöscht.

CSV-Importe dürfen einen aktiven Hersteller entweder über den technischen Code oder den Anzeigenamen angeben. Intern wird immer der Code gespeichert; Ansichten und Exporte verwenden den Anzeigenamen.

## Konsequenzen

- Herstellerbezeichnungen sind zentral unter **Einstellungen > Referenzdaten** administrierbar.
- Umbenennungen ändern keine Systemreferenzen.
- Suche und Filter berücksichtigen Herstellerreferenzen.
- Direkte Datenbankimporte müssen vor einer Systemzuordnung zuerst den Hersteller anlegen.
- Produkte bleiben weiterhin Freitext, da ihre fachliche Abhängigkeit von Herstellern eine eigene Modellentscheidung erfordert.

## Abgrenzung

DICOM-Dienste, Dokumentkategorien, Discovery-Portprofile und Produkte sind nicht Bestandteil dieser Entscheidung.

## Referenzen

- [Referenzdaten für Systeme](../Features/system-reference-data.md)
- [CSV-Import](../Features/csv-registry-import.md)
- [ADR-0020: Administrierbare System-Referenzdaten](ADR-0020-system-reference-data.md)
- [Roadmap](../../ROADMAP.md)
