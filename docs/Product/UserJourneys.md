# User Journeys

## Neues DICOM-System dokumentieren

1. Berechtigter Editor wählt Organisation, Standort und Abteilung.
2. Asset wird mit Typ, Hersteller, Modell, Version und Verantwortlichkeit angelegt.
3. Ein oder mehrere DICOM-Endpoints werden erfasst.
4. DICOM-Dienste und SCU-/SCP-Rollen werden pro Endpoint dokumentiert.
5. Verbindungen zu konkreten Zielendpoints werden angelegt.
6. Informationsquelle und Dokumentationsstatus werden erfasst.
7. Änderungen erzeugen Audit Events.
8. Topologie zeigt neue Beziehungen ohne erfundene Live-Messwerte.

## Auswirkung einer geplanten Änderung prüfen

1. Administrator sucht Asset oder AE Title.
2. Detailansicht zeigt Endpoints, Dienste und Verantwortliche.
3. Impact-Ansicht zeigt direkte ein- und ausgehende Abhängigkeiten.
4. Veraltete oder ungeprüfte Dokumentation wird eindeutig gekennzeichnet.
5. Daten können nur mit gesonderter Berechtigung exportiert werden.

## Technisches Dokument hinterlegen

1. Document Manager lädt eine erlaubte Datei hoch.
2. Datei wird außerhalb des Webroots in Quarantäne gespeichert.
3. MIME, Größe und Hash werden geprüft.
4. Optionaler Malware-Scanner liefert einen Status.
5. Erst freigegebene Dateien können heruntergeladen werden.
6. Upload und Download werden auditiert.
