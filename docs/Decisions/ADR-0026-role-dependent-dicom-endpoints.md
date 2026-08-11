---
title: ADR-0026 – Rollenabhängige DICOM-Netzwerkendpunkte
description: Host und Port sind nur für empfangende DICOM Application Entities verpflichtend.
document_type: ADR
chapter: ADR-0026
status: accepted
version: 1.0
last_updated: 2026-08-11
---

# ADR-0026: Rollenabhängige DICOM-Netzwerkendpunkte

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-11
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: —

## Kontext

Eine DICOM Application Entity wird durch ihren AE Title identifiziert. Eine reine SCU initiiert Associations und muss nicht zwingend einen eingehenden DICOM-Listener anbieten. Das bisherige Registry-Schema verlangte dennoch für jede Node Host und Port und bildete dadurch insbesondere Modalitäten ohne SCP-Funktion falsch ab.

## Entscheidung

Der AE Title bleibt für jede DICOM-Node verpflichtend. Für Nodes mit der Rolle `scu` sind Host/IP und Port optional; eine IP-Adresse darf auch ohne Listener-Port zur Dokumentation hinterlegt werden. Für `scp` und `both` bleiben Host/IP und Port verpflichtend.

Ausführbare Diagnosen werden ausschließlich gegen Nodes mit vollständigem Netzwerkendpunkt angeboten. Eine DICOM-Verbindung darf eine Node ohne vollständigen Endpunkt als Quelle verwenden. Der Zielknoten benötigt einen Host und entweder seinen eigenen Port oder einen Port-Override der Verbindung. Eine C-MOVE-Destination benötigt ebenfalls einen vollständigen Endpunkt.

## Konsequenzen

### Positiv

- Reine SCU-Modalitäten lassen sich fachlich korrekt über AE Title und optional IP dokumentieren.
- Fehlende Listener-Daten werden nicht durch erfundene Standardports ersetzt.
- Diagnose- und Verbindungsziele bleiben technisch eindeutig und ausführbar.

### Negativ und Risiken

- Nicht adressierbare Nodes stehen im Diagnose-Workspace nicht zur Auswahl.
- Bestehende Integrationen müssen `host` und `port` als nullable behandeln.
- Die Eindeutigkeit bleibt fachlich die Kombination aus System, AE Title und den gegebenenfalls vorhandenen Endpunktdaten.

## Verifikation

Feature-Tests prüfen SCU-Nodes ohne Endpunkt, die weiterhin verpflichtenden SCP-Endpunkte sowie die Verwendung nicht adressierbarer Nodes als Verbindungsquelle und -ziel.

## Referenzen

- `docs/Decisions/ADR-0006-endpoint-and-dicom-model.md`
- `docs/user-guide/03-systeme-und-dicom.md`
- `docs/Features/csv-registry-import.md`
