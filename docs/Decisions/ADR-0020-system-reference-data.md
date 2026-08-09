---
title: ADR-0020 – Administrierbare Referenzdaten für Systemtypen und Systemstatus
description: Entscheidung für stabile technische Codes mit zentral gepflegten Anzeigenamen und Aktivstatus.
document_type: ADR
chapter: ADR-0020
status: accepted
version: 1.0
last_updated: 2026-08-09
---

# ADR-0020: Administrierbare Referenzdaten für Systemtypen und Systemstatus

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-09
- Verantwortliche Rolle: Architektur/Registry Administration
- Betroffene Produktversion: Unreleased
- Ersetzt: fest im System-Controller hinterlegte Auswahllisten
- Ersetzt durch: -

## Kontext

Systemtyp und betrieblicher Systemstatus waren als freie Zeichenketten gespeichert, während die Oberfläche nur fest im Controller hinterlegte Werte anbot. Dadurch konnten Import und Discovery technisch andere Werte schreiben als die reguläre Systempflege. Bezeichnungen waren nicht installationsbezogen anpassbar und dieselben Übersetzungen wurden in mehreren Vue-Seiten wiederholt.

Bestehende Systeme und Auditdaten referenzieren die technischen Zeichenketten. Eine nachträgliche Änderung dieser Codes würde Filter, Exporte, Klassifizierungsregeln und historische Nachweise brechen.

## Entscheidung

Systemtypen und Systemstatus werden in zwei eigenen Referenztabellen geführt. Jeder Wert besitzt:

- einen nach Anlage unveränderlichen technischen Code;
- einen administrierbaren Anzeigenamen und eine Sortierreihenfolge;
- einen Aktivstatus für neue Zuordnungen;
- bei lebenszykluskritischen Statuswerten einen technischen Schutz vor Deaktivierung.

Die bestehenden Spalten systems.system_type und systems.status bleiben als stabile Codes erhalten und werden per Fremdschlüssel abgesichert. Die Migration übernimmt unbekannte Bestandswerte vor dem Aufbau der Fremdschlüssel. Deaktivierte Werte bleiben an bestehenden Systemen gültig und sichtbar, sind für neue Systeme, CSV-Importe und Discovery-Übernahmen jedoch nicht mehr auswählbar.

Die Verwaltung benötigt settings.manage; Änderungen werden in der Audit-Hash-Chain unter der Ereignisgruppe Administration protokolliert. Technische Codes können nach der Anlage weder über Oberfläche noch Request geändert werden.

Dieser erste Referenzdatenumfang enthält ausschließlich Systemtypen und Systemstatus. Hersteller wurden anschließend separat in [ADR-0021](ADR-0021-manufacturer-reference-data.md) entschieden; DICOM-Dienste, Dokumentkategorien und Portprofile bleiben spätere Entscheidungen.

## Alternativen

- **Freie Zeichenketten beibehalten:** verworfen, weil Datenqualität und konsistente Auswahlwerte nicht durchsetzbar wären.
- **Technische Codes bei Umbenennung ändern:** verworfen, weil dies bestehende Zuordnungen und historische Referenzen destabilisiert.
- **Eine polymorphe Universaltabelle für sämtliche Referenzdaten:** verworfen, weil Fremdschlüssel und domänenspezifische Schutzregeln dadurch schwächer und schwerer nachvollziehbar würden.
- **Deaktivierung nur bei unbenutzten Werten erlauben:** verworfen, weil eine kontrollierte Ablösung alter Werte möglich sein soll, ohne Bestandsdaten umzuschreiben.

## Konsequenzen

### Positiv

- Einheitliche Validierung in manueller Pflege, Import und Discovery.
- Installationsbezogen anpassbare Anzeigenamen ohne Änderung stabiler Codes.
- Datenbankseitige referenzielle Integrität.
- Bestandsschutz für deaktivierte Werte.
- Keine physische Löschung historisch verwendeter Referenzwerte.

### Negativ und Risiken

- Klassifizierungsregeln verwenden weiterhin technische Codes und müssen bei neuen fachlichen Typen bewusst erweitert werden.
- Individuelle Statuswerte erhalten die neutrale Standarddarstellung, solange keine eigene Farbsemantik definiert ist.
- Weitere Referenzdatenarten benötigen eigene Scope- und Integritätsentscheidungen.

## Verifikation

- Feature-Tests prüfen Berechtigung, Defaults, Anlage, Aktualisierung, Audit und geschützte Statuswerte.
- Tests belegen, dass deaktivierte Typen nicht neu zugeordnet, aber an bestehenden Systemen beibehalten werden können.
- System-, Discovery-, Import-, Export- und Einstellungsprüfungen laufen im isolierten Testprofil.
- Frontend-Typprüfung und Produktionsbuild prüfen die Verwaltungs- und Auswahloberflächen.

## Referenzen

- [Referenzdaten für Systeme](../Features/system-reference-data.md)
- [Fachliches Datenmodell](../product-book/04-fachliches-datenmodell.md)
- [Zugriffskontrolle](../Security/AccessControl.md)
- [Roadmap](../../ROADMAP.md)
