---
title: ADR-0025 – PDF-Export der Discovery-Ergebnisse
description: Gefilterter, auditierter PDF-Bericht auf Basis der gemeinsamen Discovery-Abfrage.
document_type: ADR
chapter: ADR-0025
status: accepted
version: 1.0
last_updated: 2026-08-11
---

# ADR-0025: PDF-Export der Discovery-Ergebnisse

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-11
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: —

## Kontext

Discovery-Ergebnisse konnten bereits gefiltert als CSV und JSON exportiert werden. Für technische Abstimmungen und Freigabeunterlagen wird zusätzlich ein menschenlesbarer, unveränderlich ausgelieferter PDF-Bericht der aktuell gefilterten Review-Queue benötigt. Ein separater Abfragepfad würde das Risiko erzeugen, dass Oberfläche und Bericht unterschiedliche Ergebnisse enthalten.

## Entscheidung

Der PDF-Export verwendet dieselbe `DiscoveryHostQuery`, dieselben Filter, dieselbe `discovery.view`-Autorisierung und dasselbe Audit-Ereignis wie CSV und JSON. Die Darstellung nutzt den vorhandenen `TabularPdfExporter`, der bereits für Systemübersicht und Firewallmatrix eingesetzt wird. Der Bericht enthält Laufname, Zielbereich, Anzahl aktiver Filter sowie IP-Adresse, Hostname, Erreichbarkeit, Review-Status, Confidence, vorgeschlagenen Systemtyp, offene Ports und erfolgreiche C-ECHO-Endpunkte.

Der maximal zulässige Discovery-Zielbereich begrenzt einen Lauf standardmäßig auf 1.024 Adressen. Deshalb darf die gefilterte Ergebnismenge für die PDF-Erzeugung gesammelt werden; CSV und JSON bleiben für maschinelle beziehungsweise größere Weiterverarbeitung gestreamt.

## Alternativen

- Eine zweite PDF-Bibliothek wurde verworfen, weil der vorhandene Tabellenkern die benötigte, textbasierte und mehrseitige Ausgabe bereits abdeckt.
- Ein clientseitiger Browserdruck wurde verworfen, weil Filterumfang, Autorisierung, Auditierung und reproduzierbare Dateiausgabe serverseitig gewährleistet werden müssen.
- Ein ungefilterter Gesamtbericht wurde verworfen, weil Export und sichtbare Review-Queue dieselbe Auswahl abbilden sollen.

## Konsequenzen

### Positiv

- Der PDF-Bericht entspricht exakt der serverseitig gefilterten Ergebnisliste.
- Bestehende Berechtigungs- und Auditregeln werden ohne Parallelstruktur wiederverwendet.
- Der Bericht ist mehrseitig und enthält keine aktiven Inhalte.

### Negativ und Risiken

- Lange Werte werden im kompakten Tabellenlayout gekürzt; CSV und JSON bleiben die vollständigen Datenformate.
- Die PDF-Erzeugung sammelt die gefilterten Hosts im Speicher, ist jedoch durch die Discovery-Laufgrenze beschränkt.

## Verifikation

Feature-Tests prüfen PDF-Signatur, Content-Type, Filterwirkung, sichtbare DICOM-Endpunkte, Berechtigung und Audit-Metadaten. Die vollständige Backend- und Frontend-CI bleibt verbindlich.

## Referenzen

- `docs/Decisions/ADR-0010-import-export.md`
- `docs/Features/dicom-discovery.md`
- `app/Services/Discovery/DiscoveryHostQuery.php`
- `app/Services/Reports/DiscoveryResultPdfExporter.php`
