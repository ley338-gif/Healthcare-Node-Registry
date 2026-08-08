---
title: ADR-0017 – Betriebliche Systemverantwortlichkeit
description: Sichtbare, bearbeitbare und auditierte Verantwortlichkeit auf bestehenden Systemdatensätzen.
document_type: ADR
chapter: ADR-0017
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0017: Betriebliche Systemverantwortlichkeit

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-08
- Verantwortliche Rolle: Produkt- und Architekturverantwortung
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: –

## Kontext

Das Datenmodell enthält bereits das optionale Freitextfeld `systems.responsible`, das bei der Übernahme eines Discovery-Funds gesetzt werden kann. In der allgemeinen Systempflege war der Wert jedoch weder sichtbar noch bearbeitbar. Für ein weitergehendes Modell aus Personen, Teams, Kontakten, Gültigkeitszeiträumen oder Verantwortungsarten gibt es im Repository noch keinen abgestimmten fachlichen Vertrag.

## Entscheidung

Die bestehende betriebliche Verantwortlichkeit wird in Anlage, Bearbeitung und Detailansicht eines Systems eingebunden. Das Feld bleibt optionaler Freitext mit maximal 160 Zeichen. Berechtigungen folgen unverändert den bestehenden `create`- und `update`-Policies für Systeme.

Änderungen laufen durch den vorhandenen System-Update-Pfad. Das Ereignis `registry.system.updated` enthält den Wert dadurch in seinen `before`- und `after`-Metadaten. Die Systemsuche und der XLSX-Export berücksichtigen die Verantwortlichkeit ebenfalls.

Es werden keine neuen Kontakt-, Rollen- oder Zuordnungstabellen eingeführt. Strukturierte Verantwortlichkeiten für Organisationen, Standorte oder Abteilungen bleiben außerhalb dieser Entscheidung, bis dafür Anforderungen einschließlich Kardinalität, Historisierung und Datenschutz festgelegt sind.

## Alternativen

- Ein neues strukturiertes Verantwortungsmodell wurde verworfen, weil dafür weder ein implementierter Domänenvertrag noch eindeutige Anforderungen existieren.
- Eine ausschließlich über Discovery pflegbare Verantwortlichkeit wurde verworfen, weil manuell angelegte Systeme sonst nicht vollständig gepflegt werden können.
- Ein separates Berechtigungsrecht wurde verworfen, weil die Verantwortlichkeit Teil der vorhandenen Systemstammdaten ist und dem bestehenden System-Änderungsrecht folgt.

## Konsequenzen

### Positiv

- Die bereits gespeicherte Verantwortlichkeit ist im normalen Arbeitsablauf sichtbar und pflegbar.
- Änderungen sind ohne parallelen Auditmechanismus nachvollziehbar.
- Die Umsetzung erweitert das Domänenmodell nicht über den belegten Funktionsumfang hinaus.

### Negativ und Risiken

- Freitext kann nicht gegen ein Benutzer- oder Teamverzeichnis validiert werden.
- Umbenennungen und organisatorische Wechsel werden nur als Auditänderung, nicht als eigenständige zeitlich gültige Zuordnung modelliert.
- Das separate Feld `systems.criticality` bleibt in der allgemeinen Systempflege vorerst nicht angebunden.

## Verifikation

Feature-Tests prüfen Anlage und Änderung der Verantwortlichkeit sowie die Vorher-/Nachher-Werte im Audit. Frontend-Typprüfung und Build sichern die Bindung in Anlage-, Bearbeitungs- und Detailkomponenten ab. Der Exporttest prüft die Ausgabe im XLSX-Bericht.

## Referenzen

- `app/Models/System.php`
- `app/Http/Controllers/SystemController.php`
- `docs/limitations.md`
- `ROADMAP.md`
