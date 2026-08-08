---
title: DICOM-TLS für Diagnose-Runner
description: Bereitstellung, Zuordnung und Betrieb gegenseitig authentisierter Zertifikatsprofile.
document_type: Betriebskonzept
chapter: DICOM-Diagnostik
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# DICOM-TLS für Diagnose-Runner

Die Diagnose-Runner verwenden bei `tls_enabled=true` ausschließlich DICOM über TLS. Fehlt das referenzierte Profil oder ist ein Zertifikat ungültig, startet kein Netzwerkprozess; es gibt keinen automatischen Rückfall auf unverschlüsseltes DICOM.

## Unterstützte Diagnosen

- C-ECHO
- Modality Worklist C-FIND
- PACS Study Root C-FIND
- synthetisches C-STORE
- C-MOVE und C-GET
- Capability Matrix per Association Negotiation
- synthetisches MPPS
- synthetisches Storage Commitment einschließlich TLS-geschütztem Callback

Discovery verwendet einen getrennten Runner und unterstützt weiterhin kein DICOM-TLS.

## Zertifikatsprofil bereitstellen

Der Standardpfad im Container ist `/run/secrets/dicom-tls`. Das Host-Verzeichnis wird mit `DIAGNOSTIC_TLS_PROFILE_HOST_DIRECTORY` festgelegt und schreibgeschützt eingebunden. Eine Profilkennung wie `pacs-produktiv` entspricht folgendem Layout:

```text
<DIAGNOSTIC_TLS_PROFILE_HOST_DIRECTORY>/
└── pacs-produktiv/
    ├── certificate.pem
    ├── private-key.pem
    └── ca.pem
```

- `certificate.pem`: aktuell gültiges Client-Zertifikat für HNR
- `private-key.pem`: passender, unverschlüsselter privater Schlüssel
- `ca.pem`: vertrauenswürdige CA für das Zertifikat des DICOM-Gegenübers

Die Anwendung akzeptiert Profilkennungen mit höchstens 64 Zeichen aus Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich. Freie Dateipfade sind nicht zulässig. Zertifikate und Schlüssel dürfen nicht committed, in Registry-Notizen kopiert oder in Datenbank-Backups aufgenommen werden.

Der Schlüssel muss auf dem Host nur für den betreibenden Dienst lesbar sein. Der Container erhält ausschließlich einen Read-only-Mount. Ein unverschlüsselter Schlüssel ist erforderlich, weil ein DCMTK-Schlüsselpasswort sonst als Prozessargument sichtbar wäre.

## Docker-Konfiguration

In `.env`:

```dotenv
DIAGNOSTIC_TLS_PROFILE_DIRECTORY=/run/secrets/dicom-tls
DIAGNOSTIC_TLS_PROFILE_HOST_DIRECTORY=/srv/healthcare-node-registry/dicom-tls
```

Danach die betroffenen Container neu erstellen, damit der geänderte Mount wirksam wird:

```bash
docker compose up -d --force-recreate app worker
```

Eine Zertifikatsrotation innerhalb eines bereits eingebundenen Profilverzeichnisses wird beim nächsten Lauf eingelesen. Ein Image-Neubau ist nicht erforderlich.

## Registry-Zuordnung

Beim Bearbeiten eines DICOM-Knotens:

1. `TLS aktiviert` auswählen.
2. Unter `TLS-Profil` exakt die bereitgestellte Profilkennung eintragen.
3. Speichern und anschließend C-ECHO ausführen.

Für DICOM-Verbindungen kann ein eigenes TLS-Profil angegeben werden. Bei verbindungsbasiertem C-MOVE und C-GET überschreiben `Port` und TLS-Profil der Verbindung die Transportwerte des Zielknotens. Der Move-Destination-Knoten beschreibt den Empfänger des vom PACS ausgelösten Transfers und ist nicht die ausgehende TLS-Verbindung von HNR.

## Validierung und Fehlerverhalten

Vor jedem Lauf prüft HNR:

- Profilkennung und Pfadgrenzen
- Vorhandensein und Lesbarkeit aller drei Dateien
- gültige PEM-Zertifikate und einen lesbaren privaten Schlüssel
- aktuellen Gültigkeitszeitraum des Client-Zertifikats
- Übereinstimmung von Zertifikat und privatem Schlüssel
- CA-basierte Peer-Zertifikatsprüfung beim TLS-Handshake

Fehler werden als ungültige DICOM-TLS-Konfiguration ausgewiesen. HNR versucht anschließend keine Klartextverbindung. Die Zertifikatskette wird geprüft, jedoch keine DNS-Namensbindung; für jedes Betriebsumfeld sollte deshalb eine dedizierte, eng begrenzte Vertrauens-CA verwendet werden.

## Storage-Commitment-Callback

Storage Commitment öffnet zusätzlich den konfigurierten Callback-Port. Bei TLS muss das Zielsystem den Callback ebenfalls per TLS aufbauen, das HNR-Client-Zertifikat über die Profil-CA vertrauen und selbst ein von dieser CA akzeptiertes Zertifikat präsentieren. Portfreigabe und Zertifikatsvertrauen müssen vor dem Test abgestimmt sein.
