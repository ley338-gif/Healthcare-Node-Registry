@'
# Roadmap

## 0.1.1 – Foundation Hardening

Abgeschlossen.

## 0.2.0 – Registry Core

Abgeschlossen.

- [x] Organisationen
- [x] Standorte
- [x] Abteilungen
- [x] Suche und Filter
- [x] Archivierung
- [x] Autorisierung
- [x] Audit Events
- [x] Dashboard-Integration
- [x] Tests
- [x] getrennte Entwicklungs- und Testinfrastruktur
- [x] Control-Center-Navigation und UI-Grundlage

Bewusst nicht Bestandteil von 0.2.0:

- Verantwortlichkeiten
- webbasierte Benutzerverwaltung

## 0.3.0 – System Registry

Ziel: Administratoren können ihre technische Systemlandschaft vollständig erfassen und verwalten.

- [ ] Systeme anlegen, bearbeiten und archivieren
- [ ] Organisation, Standort und Abteilung zuordnen
- [ ] Systemtyp und Betriebsstatus pflegen
- [ ] Hostname, FQDN und IP-Adresse erfassen
- [ ] Hersteller, Modell und Version dokumentieren
- [ ] Betriebssystem, Seriennummer und Inventarnummer pflegen
- [ ] Suche und Filter
- [ ] Detailansicht
- [ ] Autorisierung und Audit Events
- [ ] Dashboard-Kennzahlen aus echten Systemdaten
- [ ] Tests

## Spätere Releases

### 0.4.0 – Verbindungen und Topologie

Kommunikationsbeziehungen zwischen Systemen und erste Topologieansicht.

### 0.5.0 – DICOM Services

AE Titles, Ports, Rollen und DICOM-Dienste auf Basis der System Registry.

### 0.6.0 – Monitoring

Technische und fachliche Erreichbarkeitsprüfungen.
'@ | Set-Content .\ROADMAP.md -Encoding utf8