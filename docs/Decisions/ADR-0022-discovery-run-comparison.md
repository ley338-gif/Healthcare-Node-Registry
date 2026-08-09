---
title: ADR-0022 – Vergleich von Discovery-Scanständen
description: Fachliche und technische Regeln für den reproduzierbaren Vergleich abgeschlossener Discovery-Läufe.
document_type: ADR
chapter: ADR-0022
status: accepted
version: 1.0
last_updated: 2026-08-10
---

# ADR-0022: Vergleich von Discovery-Scanständen

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-10
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: 0.4.0-dev
- Ersetzt: -
- Ersetzt durch: -

## Kontext

Wiederholte Discovery-Läufe erzeugen jeweils einen unveränderlichen technischen Scanstand. Administratoren müssen erkennen können, welche Hosts seit einem früheren Lauf neu hinzugekommen, verschwunden oder technisch verändert sind. Ein Vergleich unterschiedlicher Zielbereiche oder noch laufender beziehungsweise abgebrochener Scans würde jedoch irreführende Differenzen erzeugen.

## Entscheidung

1. Verglichen werden ausschließlich zwei unterschiedliche, vollständig abgeschlossene Läufe mit identischem `ip_range`.
2. Die IP-Adresse bildet die technische Hostidentität innerhalb des verglichenen Zielbereichs. Ein Host wird als `new`, `missing`, `changed` oder `unchanged` klassifiziert.
3. Als technische Änderungen gelten Hostname, Reverse DNS, Erreichbarkeit, offene Ports, erfolgreiche DICOM-C-ECHO-Endpunkte, vorgeschlagener Systemtyp sowie Confidence-Stufe und -Wert.
4. Ping-Latenz, Antwortzeiten, Review-Status und Registry-Zuordnung werden nicht verglichen. Diese Werte sind flüchtig beziehungsweise entstehen durch menschliche Bearbeitung und beschreiben keine stabile technische Änderung.
5. Der Vergleich wird bei Bedarf aus den bestehenden Scanständen berechnet und nicht als zweiter Datenbestand gespeichert. Die maximale Zielbereichsgröße begrenzt die Ergebnismenge bereits auf einen für die Webansicht geeigneten Umfang.
6. Die Ansicht verwendet dieselbe serverseitige `discovery.view`-Policy wie die beiden Ausgangsläufe. Ein allein im Frontend ausgeblendeter Schutz ist nicht zulässig.

## Alternativen

- **Persistierte Diff-Tabelle:** verworfen, weil sie redundante Daten, Invalidierungsregeln und zusätzlichen Aufbewahrungsbedarf erzeugt, ohne für die begrenzte Laufgröße einen Nutzen zu bieten.
- **Abgleich über Hostname oder AE-Titel:** verworfen, weil beide Werte fehlen oder sich ändern können und AE-Titel nicht zuverlässig automatisch ermittelt werden können.
- **Vergleich beliebiger Zielbereiche und Laufstatus:** verworfen, weil fehlende Hosts dann nicht von ungeprüften Adressen unterschieden werden könnten.

## Konsequenzen

### Positiv

- Reproduzierbarer, erklärbarer Vergleich ohne neues Persistenzschema.
- Keine Fehlalarme durch Latenzschwankungen oder nachträgliche Review-Aktionen.
- Bestehende Discovery-Berechtigungen und Sicherheitsgrenzen bleiben unverändert.

### Negativ und Risiken

- Geänderte IP-Adressen erscheinen als verschwundener und neuer Host, weil es ohne verlässliche Gerätekennung keine belastbare automatische Zusammenführung gibt.
- Abweichende Scanoptionen können technische Differenzen beeinflussen; die Ansicht stellt deshalb die beiden konkreten Läufe und den gemeinsamen Zielbereich sichtbar gegenüber.
- Der Vergleich ist eine manuell aufgerufene Momentaufnahme und noch kein automatischer Änderungsbericht oder Alarm.

## Verifikation

- Feature-Tests decken neue, verschwundene, veränderte und unveränderte Hosts ab.
- Serverseitige Tests lehnen fehlende Berechtigungen, nicht abgeschlossene Läufe und unterschiedliche Zielbereiche ab.
- `composer ci` und `npm run check` müssen erfolgreich sein.

## Referenzen

- [DICOM Discovery](../Features/dicom-discovery.md)
- [Discovery-Roadmap](../roadmap.md)
- `app/Services/Discovery/DiscoveryRunComparisonService.php`
- `tests/Feature/DiscoveryRunComparisonTest.php`
