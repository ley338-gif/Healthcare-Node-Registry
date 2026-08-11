---
title: ADR-0023 – Benachrichtigungen über Discovery-Änderungen
description: Regeln für belastbare In-App-Hinweise bei neuen oder verschwundenen Hosts in wiederkehrenden Discovery-Läufen.
document_type: ADR
chapter: ADR-0023
status: accepted
version: 1.0
last_updated: 2026-08-11
---

# ADR-0023: Benachrichtigungen über Discovery-Änderungen

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-11
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: 0.4.0-dev
- Ersetzt: -
- Ersetzt durch: -

## Kontext

Wiederkehrende Discovery-Läufe und der manuelle Scanstandvergleich sind implementiert. Administratoren sollen relevante Bestandsänderungen erkennen, ohne jeden Zeitplan regelmäßig öffnen zu müssen. Ein fehlgeschlagener, abgebrochener oder nur teilweise erfolgreicher Scan darf jedoch keine irreführende Meldung über angeblich verschwundene Systeme erzeugen.

Discovery-Ergebnisse sind zunächst technische Hostfunde und noch keine bestätigten Registry-Systeme. Benachrichtigungen müssen diese Unsicherheit erhalten, aktuelle Berechtigungen beachten und bei wiederholter Verarbeitung desselben Laufes idempotent bleiben.

## Entscheidung

1. Hinweise werden ausschließlich nach einem vollständig erfolgreichen Lauf mit `discovery_schedule_id` erzeugt. Manuelle, erste, abgebrochene, fehlgeschlagene und teilweise fehlgeschlagene Läufe erzeugen keine Änderungsmeldung.
2. Ausgangsstand ist der zeitlich unmittelbar vorherige vollständig erfolgreiche Lauf desselben Zeitplans und desselben Zielbereichs.
3. Eine Meldung entsteht nur, wenn der bestehende Scanvergleich mindestens einen neuen oder verschwundenen Host feststellt. Rein veränderte technische Merkmale bleiben in der manuellen Vergleichsansicht sichtbar, lösen aber noch keinen Alarm aus.
4. Empfänger sind alle aktiven Benutzer mit einer effektiven Berechtigung `discovery.view`, `discovery.run` oder `discovery.manage`. Beim Öffnen wird die Berechtigung erneut serverseitig geprüft.
5. Die bestehende Laravel-Datenbankbenachrichtigung wird wiederverwendet. Pro Empfänger und aktuellem Lauf wird höchstens ein Hinweis gespeichert; ein erneuter Job- oder Serviceaufruf erzeugt kein Duplikat.
6. Die Meldung enthält nur Laufreferenzen, Anzahlen und höchstens zehn beispielhafte IP-Adressen je Änderungstyp. Sie verlinkt auf den vollständigen Scanvergleich. E-Mail-Versand ist nicht Bestandteil dieser Ausbaustufe.
7. Fehler beim Erzeugen von Benachrichtigungen werden gemeldet, ändern aber einen technisch erfolgreich abgeschlossenen Discovery-Lauf nicht nachträglich in einen Fehlerstatus.

## Alternativen

- **Hinweise nach jedem manuellen Lauf:** verworfen, weil ein belastbarer automatischer Ausgangsstand und eine klare betriebliche Erwartung fehlen.
- **Meldungen auch bei teilweise fehlgeschlagenen Läufen:** verworfen, da fehlende Hosts dann Scanfehler statt reale Änderungen darstellen können.
- **Nur Zeitplan-Eigentümer benachrichtigen:** verworfen, weil Betriebsverantwortung über Rollen und nicht dauerhaft über die Person abgebildet wird, die einen Zeitplan angelegt hat.
- **E-Mail als Pflichtkanal:** verworfen, da das Referenzsetup keinen produktiven Mailversand voraussetzt und In-App-Hinweise bereits persistent verfügbar sind.

## Konsequenzen

### Positiv

- Relevante Änderungen werden ohne zusätzlichen Dienst sichtbar.
- Fehlalarme durch unvollständige Scans werden fail-closed vermieden.
- Rechteänderungen wirken sofort auf Empfängerauswahl und Öffnen bestehender Meldungen.
- Der bestehende Vergleichsdienst bleibt die einzige fachliche Diff-Implementierung.

### Negativ und Risiken

- Benutzer sehen Hinweise erst nach dem Anmelden; es gibt noch keinen externen Alarmkanal.
- Ein Benutzer mit Discovery-Recht erhält Hinweise für alle Zeitpläne, da organisatorische Scopes noch nicht implementiert sind.
- Änderungen ohne neue oder verschwundene IP-Adresse lösen keine Meldung aus und müssen weiterhin im Vergleich geprüft werden.

## Verifikation

- Feature-Tests prüfen Empfängerrechte, inaktive Konten, Deduplizierung und Ausschluss ungeeigneter Läufe.
- Dashboard und Weiterleitung werden inklusive Markierung als gelesen getestet.
- Ein Rechteentzug vor dem Öffnen führt zu `403` und lässt die Meldung ungelesen.
- `composer ci` und `npm run check` müssen erfolgreich sein.

## Referenzen

- [ADR-0022: Vergleich von Discovery-Scanständen](ADR-0022-discovery-run-comparison.md)
- [DICOM Discovery](../Features/dicom-discovery.md)
- [Discovery-Roadmap](../roadmap.md)
- `app/Services/Discovery/DiscoveryChangeNotificationService.php`
- `tests/Feature/DiscoveryChangeNotificationTest.php`
