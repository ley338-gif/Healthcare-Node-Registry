---
title: ADR-0016 – Dokumentfreigabe und Aufbewahrung
description: Versionsbezogenes Vier-Augen-Prinzip und kategoriebasierte Mindestaufbewahrung.
document_type: ADR
chapter: ADR-0016
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0016: Dokumentfreigabe und Aufbewahrung

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-08
- Verantwortliche Rolle: Produkt- und Sicherheitsverantwortung
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: —

## Kontext

Dateiversionen waren technisch unveränderlich und malwaregeprüft, konnten aber ohne fachliche Freigabe abgerufen werden. Außerdem fehlte eine je Dokumentkategorie verbindlich gespeicherte Mindestaufbewahrung.

## Entscheidung

Der Freigabestatus liegt auf `RegistryDocumentVersion`, weil jede neue Datei separat geprüft werden muss. Der Zustandsweg lautet `draft` → `pending_review` → `approved` oder `rejected`. Nur eine vom Uploader verschiedene Person mit `documents.approve` darf einen wartenden Upload freigeben oder ablehnen. Die Anwendung erzwingt dies in Policy, Controller und gesperrter Transaktion. Eine Freigabe veröffentlicht die Version als `current_version_id`; Download und Vorschau verlangen zusätzlich einen sauberen Malware-Status.

Dokumentkategorien werden den konfigurierbaren Stufen kurz, standard und lang zugeordnet. Die beim Upload berechnete Frist wird gespeichert, durch Kategorienwechsel nie verkürzt und blockiert vorzeitige Model-Löschungen. Archivierung ist keine Löschung und bleibt zulässig. Ein automatisierter Löschlauf nach Fristende ist nicht Teil dieser Entscheidung.

Freigabe, Ablehnung und Prüfanfrage werden über `RegistryAudit` in die kryptografische Audit-Kette geschrieben. Freitext-Prüfnotizen werden nicht in Audit-Metadaten kopiert.

## Alternativen

- Ein Freigabestatus am Dokument wurde verworfen, weil er neue ungeprüfte Versionen implizit als freigegeben behandeln könnte.
- Eine reine UI-Sperre wurde verworfen, weil direkte HTTP-Anfragen und Administrator-Policy-Ausnahmen das Vier-Augen-Prinzip umgehen könnten.
- Sofortige physische Löschung nach Fristende wurde mangels abgestimmtem Storage-/Datenbank-Lösch- und Restore-Verfahren nicht eingeführt.

## Konsequenzen

### Positiv

- Jede veröffentlichte Version besitzt einen namentlich und zeitlich nachvollziehbaren zweiten Prüfer.
- Bestehende freigegebene Versionen bleiben während der Prüfung einer Folgeversion verfügbar.
- Mindestfristen sind installationsabhängig konfigurierbar und technisch gegen vorzeitige Löschung geschützt.

### Negativ und Risiken

- Erstuploads sind bis Malwareprüfung und Vier-Augen-Freigabe nicht abrufbar.
- Betreiber müssen Aufbewahrungswerte anhand ihrer Rechts- und Compliance-Vorgaben festlegen; die Defaults sind keine Rechtsberatung.
- Nach Fristende existiert weiterhin kein automatischer Löschprozess.

## Verifikation

Feature-Tests prüfen das Selbstfreigabeverbot auch für Systemadministratoren, die Freigabe durch eine zweite Person, Ablehnung mit Begründung, Audit-Verkettung, Download-Sperre sowie konfigurierbare Kategorien und Löschschutz.

## Referenzen

- `docs/Features/registry-documentation.md`
- `docs/Architecture/registry-document-storage.md`
- `docs/Security/FileUploadSecurity.md`
- `ROADMAP.md`
