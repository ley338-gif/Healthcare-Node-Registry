# ADR-0002: Authentication

- **Status:** Proposed

## Entscheidung

Version 0.1.0 verwendet lokale, sessionbasierte Laravel-Authentisierung. Das reguläre Webfrontend verwendet keine JWT-Architektur.

OIDC und LDAP/Active Directory werden später über Adapter ergänzt. MFA wird architektonisch vorbereitet, aber nicht ohne freigegebenen Recovery- und Betriebsprozess eingeführt.

## Begründung

Sessionauthentisierung reduziert Komplexität, passt zu Inertia.js und ermöglicht sichere serverseitige Autorisierung.

## Folgen

- sichere Cookie- und Session-Konfiguration
- CSRF-Schutz
- Rate Limits
- Setup-Prozess für erstes Administratorkonto
- Audit für sicherheitsrelevante Kontoereignisse
