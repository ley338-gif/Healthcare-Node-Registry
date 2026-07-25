# Scope

## In Scope für Version 1.0

### Registry

- Organisation, Standort und Abteilung
- logische und physische Systeme
- Hersteller, Modell, Version, Status
- Verantwortliche als Rollen-/Kontakthinweis
- Tags und Notizen

### Endpoints

- Hostname, IP-Adresse, Port
- Protokoll und Zweck
- DICOM AE Title
- DICOM-Dienste und SCU/SCP-Rollen
- optionale HL7-, FHIR-, REST-, LDAP-, DB- und File-Endpunkte

### Connections

- gerichtete Beziehung
- Quell- und Zielendpunkt
- Dienst, Protokoll, Richtung, Zweck
- fachlicher/technischer Status
- Notizen und Änderungsverlauf

### Topologie

- interaktiver Graph
- Filter
- System- und Verbindungsdetails
- Auswirkungs-/Nachbarschaftsansicht
- Export oder druckfähige Ansicht

### Dokumente

- sichere Anhänge
- Kategorien
- Version/Gültigkeit
- Prüfsumme
- Berechtigungen und Download-Audit

### Plattform

- Authentisierung
- RBAC
- Audit
- Suche/Filter
- Import/Export
- On-Premise-Docker-Betrieb
- Backup/Restore und Updates

## Out of Scope für Version 1.0

- Verarbeitung medizinischer Bilddaten
- Speicherung von Patientenstammdaten
- PACS-/RIS-Funktionalität
- DICOM-Router oder Interface Engine
- automatische Netzwerkscans
- aktive Discovery
- permanentes Monitoring
- Remote-Konfiguration von Modalitäten
- automatische Firewalländerungen
- Cloud-Pflicht
- KI-Auswertung von Kundendaten
- behauptete ISO-Zertifizierung

## Scope-Änderungen

Jede Scope-Erweiterung benötigt:

- dokumentierten Nutzerbedarf
- Aufwand und Risiko
- Sicherheits- und Datenschutzbewertung
- Auswirkungen auf Roadmap und Architektur
- Freigabe
