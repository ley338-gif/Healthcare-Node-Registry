---
title: ADR-0015 – Audit-Integrität und Aufbewahrung
description: Verkettete SHA-256-Nachweise und kontrollierte Aufbewahrung für Security Events.
document_type: ADR
chapter: ADR-0015
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0015: Audit-Integrität und Aufbewahrung

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-08
- Verantwortliche Rolle: Systembetrieb und Informationssicherheit
- Betroffene Produktversion: Unreleased
- Ersetzt: offene Betriebsparameter in ADR-0009
- Ersetzt durch:

## Kontext

Die gemeinsame Tabelle `security_events` war fachlich append-only, besaß aber keinen
technischen Integritätsnachweis und keine verbindliche Aufbewahrungssteuerung. Eine
nachträgliche Änderung oder Löschung war deshalb nicht automatisiert erkennbar.
ISO/IEC 27001 gibt für diese Registry keine feste Aufbewahrungsdauer vor. Nach der
DSGVO-Dokumentation bestimmt der Betreiber Zweck, Rechtsgrundlage und Frist.

## Entscheidung

Jeder Audit-Eintrag enthält einen kanonisch berechneten SHA-256-Hash über Ereignis-ID,
Typ, Akteur, Subjekt, redigierte Metadaten, UTC-Zeitpunkt, unveränderliches
Aufbewahrungsende und den Hash des Vorgängers. Ein gesperrter Singleton-Kettenzustand
serialisiert Schreibvorgänge und speichert den erwarteten Kettenkopf. Dadurch wird
auch eine Löschung am Kettenende erkannt, ohne bei jedem Schreibvorgang die Historie
zu lesen.

`registry:audit-verify` verifiziert die Kette unter einem gemeinsamen Datenbank-Lock
und meldet die erste nachweisbare Fundstelle. Eloquent verhindert Updates und
Löschungen von `SecurityEvent`; neue Schreibpfade verwenden ausschließlich
`RegistryAudit` und den zentralen `AuditChainWriter`.

Die Standardaufbewahrung beträgt sieben Jahre (`AUDIT_RETENTION_DAYS=2555`) und muss
vom Betreiber geprüft werden. Das pro Eintrag gespeicherte Aufbewahrungsende kann
durch eine spätere Konfigurationsverkürzung nicht unterschritten werden; eine
Verlängerung der globalen Frist gilt hingegen auch für vorhandene Events.

Der täglich geplante Befehl `registry:audit-prune` entfernt nur einen zusammenhängenden,
abgelaufenen Präfix. Der Hash des letzten entfernten Eintrags bleibt als Checkpoint
erhalten und bildet den Vorgängeranker für die verbleibende Kette und neue Events.

## Alternativen

- Nur Datenbankberechtigungen verhindern keine unbemerkte Änderung durch hochprivilegierte
  Zugänge und liefern keinen Verifikationsnachweis.
- Eine digitale Signatur oder externe, unveränderliche Verankerung bietet stärkeren
  Schutz, benötigt aber Schlüsselverwaltung oder einen zusätzlichen Betriebsdienst.
- Einzelhashes ohne Verkettung erkennen keine gelöschten Datensätze.
- Vollständige Aufbewahrung ohne Ablauf widerspricht Datenminimierung und erzeugt eine
  nicht begrenzte Datenhaltung.

## Konsequenzen

### Positiv

- Änderungen, Einfügungen, Umordnungen sowie Löschungen in der Mitte und am Ende sind
  mit einem linearen Prüflauf erkennbar.
- Schreiben benötigt nur SHA-256 sowie einen gesperrten Zustandsdatensatz und keinen
  Scan der Historie.
- Aufbewahrung ist konfigurierbar, rückwirkend nicht verkürzbar und automatisiert.
- Bereits vorhandene Events werden bei der Migration deterministisch verkettet.

### Negativ und Risiken

- Audit-Schreibvorgänge werden kurz über einen gemeinsamen Kettenkopf serialisiert.
- Nach erlaubtem Ablauf sind die entfernten Inhalte nicht mehr verifizierbar; nur ihr
  letzter Hash bleibt als Kontinuitätsanker erhalten.
- Ein Angreifer mit Schreibzugriff auf Events und Kettenzustand kann die komplette
  Kette neu berechnen. Für Schutz gegen dieses Szenario ist eine externe signierte
  Verankerung erforderlich.
- Backups müssen Events und `audit_chain_state` konsistent gemeinsam sichern.

## Verifikation

- `php artisan registry:audit-verify`
- `tests/Feature/AuditChainIntegrityTest.php` prüft reguläre Verkettung, Manipulation,
  Mittel-/Tail-Löschung, Modellschutz und Aufbewahrungsablauf.
- `tests/Feature/CreateInitialAdminCommandTest.php` schützt den zuvor direkten
  Security-Event-Schreibpfad.

## Referenzen

- [ADR-0009: Audit and Logging](ADR-0009-audit-and-logging.md)
- [Logging und Audit](../Security/Logging.md)
- [Datenschutz und DSGVO](../Compliance/DSGVO.md)
- [Registry-Historie](../Features/registry-history.md)
- [Roadmap](../../ROADMAP.md)
