---
title: ADR-0018 – DICOM-TLS für Diagnose-Runner
description: Zertifikatsprofile, gegenseitige Authentisierung und ausfallsicheres Transportverhalten für DICOM-Diagnosen.
document_type: ADR
chapter: ADR-0018
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0018: DICOM-TLS für Diagnose-Runner

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-08
- Verantwortliche Rolle: Produkt-, Architektur- und Sicherheitsverantwortung
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: –

## Kontext

`DicomNode` und `DicomConnection` enthielten bereits `tls_enabled`, die Diagnose-Runner verwendeten jedoch ausschließlich Klartext-TCP. Die Kennzeichnung konnte dadurch einen Sicherheitszustand suggerieren, den der ausgeführte Test nicht einhielt. Zertifikate und private Schlüssel dürfen weder in der Datenbank noch im Repository oder in Prozessargumenten als Geheimnis gespeichert werden.

## Entscheidung

Jeder TLS-fähige Knoten und jede TLS-fähige Verbindung referenziert ein benanntes Zertifikatsprofil. Die Kennung wird in `tls_profile` gespeichert; das Profil selbst liegt in einem vom Betreiber schreibgeschützt in den Container eingebundenen Verzeichnis. Ein Profil besteht aus `certificate.pem`, `private-key.pem` und `ca.pem`.

Vor jedem Diagnose-Lauf werden Profilkennung, Pfadgrenzen, Lesbarkeit, PEM-Struktur, Gültigkeitszeitraum und Übereinstimmung von Client-Zertifikat und Schlüssel geprüft. DCMTK erhält authentifiziertes TLS mit BCP-195-Profil und verpflichtender Peer-Zertifikatsprüfung. Die `pynetdicom`-Runner verwenden verifizierende Python-SSL-Kontexte; Storage Commitment schützt auch den Callback-Server mit gegenseitiger Zertifikatsprüfung. Die Capability Matrix verwendet einen PHP-TLS-Stream mit Client-Zertifikat und CA-Prüfung.

Ein aktiviertes TLS-Flag ohne gültiges Profil beendet den Lauf vor dem jeweiligen Netzwerk- oder DICOM-Prozess. Ein Rückfall auf Klartext ist ausgeschlossen. Unverschlüsselte private Schlüssel sind nur deshalb zugelassen, weil DCMTK ein Schlüsselpasswort ausschließlich als sichtbares Prozessargument oder interaktive Eingabe akzeptiert. Der Schlüssel muss daher über Container-Mount und Dateisystemrechte geschützt werden.

Verbindungen dürfen Port und TLS-Profil des Zielknotens für verbindungsbasierte C-MOVE-/C-GET-Läufe überschreiben. Andere knotengebundene Diagnosen verwenden die TLS-Konfiguration des Knotens.

## Alternativen

- Zertifikatsdateien direkt in der Datenbank wurden wegen Geheimnisschutz, Rotation und Backup-Risiken verworfen.
- Freie Dateipfade in Registry-Feldern wurden wegen Pfadmanipulation und Informationsabfluss verworfen.
- Schlüsselpasswörter als DCMTK-Argument wurden verworfen, weil sie in Prozesslisten sichtbar sein können.
- Automatischer Klartext-Fallback wurde verworfen, weil er die dokumentierte Transportanforderung unbemerkt verletzen würde.

## Konsequenzen

### Positiv

- TLS-Markierungen steuern jetzt tatsächlich den Transport aller Diagnose-Associations.
- Zertifikate können außerhalb von Image, Repository und Datenbank rotiert werden.
- Ungültige oder abgelaufene Profile führen zu einem klaren, sicheren Fehler.
- Alle Runner verwenden denselben Validierungs- und Profilvertrag.

### Negativ und Risiken

- Betreiber müssen Zertifikate, CA-Vertrauen, Dateirechte und Rotation selbst verwalten.
- DCMTK und die Python-Runner prüfen die Zertifikatskette, aber keine DNS-Namensbindung. Profile sollten deshalb eine dedizierte, eng begrenzte Vertrauens-CA verwenden.
- Der private Schlüssel liegt im Container unverschlüsselt, muss dort jedoch ausschließlich lesbar eingebunden sein.
- DICOM-TLS im Discovery-Scanner bleibt außerhalb dieser Entscheidung.

## Verifikation

Tests erzeugen ein synthetisches CA-/Client-Zertifikatsprofil und prüfen die daraus erzeugten DCMTK-, Python- und PHP-TLS-Konfigurationen. Für Echo, Worklist, PACS Query, Storage, C-MOVE, C-GET, MPPS, Storage Commitment und Capability Matrix belegt je ein Guard-Test, dass ein aktiviertes TLS-Flag mit ungültigem Profil vor dem jeweiligen Netzwerkprozess fehlschlägt. Request-Tests erzwingen Profilreferenzen bei TLS-fähigen Knoten und Verbindungen.

## Referenzen

- `docs/Healthcare/DicomTlsDiagnostics.md`
- `docs/Healthcare/DiagnosticTestWorkspace.md`
- `docker/dicom-tls/README.md`
- `ROADMAP.md`
