# Implementierungsbericht 0.1.0

## Nutzerwert

Das Paket schafft eine reproduzierbare technische Basis für die nachfolgenden Registry-Module und verhindert, dass Authentisierung, Layout, Datenbank, Container und CI mehrfach oder inkonsistent aufgebaut werden.

## Scope

- Framework-Grundgerüst
- modulare Monolith-Struktur
- lokale Session-Authentisierung
- RBAC-Basis
- Dashboard
- Health Endpoint
- Docker Compose
- CI und Qualitätswerkzeuge
- Dokumentationsaktualisierung

## Nicht-Scope

- Healthcare-Fachobjekte
- DICOM-/HL7-/FHIR-Kommunikation
- Uploads
- Discovery
- Monitoring
- SaaS-Mandantenfähigkeit

## Datenbankauswirkungen

Neue Tabellen: users, password_reset_tokens, sessions, roles, permissions, permission_role, role_user.

## Security

Default Deny über Auth-Middleware, serverseitige Rolle/Berechtigung, sichere Session-Konfiguration, keine Registrierungsroute, generische Login-Fehler, Rate Limiting und synthetische Seed-Daten.

## Tests

Featuretests für Health Endpoint, Login, unauthentisierten Dashboard-Zugriff und autorisierten Dashboard-Zugriff. Frontend-Prüfungen für TypeScript, ESLint und Build.

## Offene Risiken

- Abhängigkeiten müssen beim ersten Build online geladen und anschließend für Offline-Installationen gespiegelt werden.
- MFA, OIDC und LDAP sind noch nicht implementiert.
- Der Entwicklungs-Admin darf nicht produktiv verwendet werden.
