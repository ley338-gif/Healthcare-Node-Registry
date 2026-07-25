# Authentication

## MVP-Entscheidung

Für 0.1.0 wird lokale, sessionbasierte Laravel-Authentisierung vorgesehen.

## Anforderungen

- serverseitige Sessions
- sichere, HttpOnly- und SameSite-Cookies
- Secure-Flag bei HTTPS
- CSRF-Schutz
- Rate Limiting
- Schutz vor Benutzer-Enumeration
- moderne Passwort-Hashing-Defaults
- kontrollierter Passwort-Reset
- Audit von Login, Logout, Sperrung und Rollenänderung
- kein JWT für das reguläre Webfrontend
- kein Defaultpasswort im Repository

## Erstinstallation

Ein initiales Administratorkonto wird durch einen dokumentierten, einmaligen Setup-Prozess erzeugt. Zugangsdaten werden nicht fest eingebaut und nicht geloggt.

## Spätere Erweiterungen

- OIDC
- LDAP/Active Directory
- SAML nur bei realem Bedarf
- MFA

Externe Provider werden über Adapter integriert. Lokale Break-Glass-Verfahren benötigen eine dokumentierte organisatorische Freigabe.
