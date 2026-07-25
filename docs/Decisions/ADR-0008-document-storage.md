# ADR-0008: Document Storage

- **Status:** Proposed

## Entscheidung

Standard ist ein lokales, nicht öffentlich erreichbares Docker Volume. S3-kompatibler On-Premise-Speicher kann später als Adapter ergänzt werden.

Metadaten liegen in PostgreSQL; Binärdaten nicht. Dateien erhalten zufällige Storage Keys und SHA-256. Uploads durchlaufen Quarantäne und definierte Scanstatus.

## Folgen

Backup und Restore müssen Datenbank und Dokumentenspeicher konsistent behandeln. Direkte öffentliche Storage-URLs sind verboten.
