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
- verpflichtende TOTP-MFA für privilegierte lokale Konten
- kein JWT für das reguläre Webfrontend
- kein Defaultpasswort im Repository

## Erstinstallation

Ein initiales Administratorkonto wird durch einen dokumentierten, einmaligen Setup-Prozess erzeugt. Zugangsdaten werden nicht fest eingebaut und nicht geloggt.

## Mehrfaktor-Authentisierung

Konten mit administrativen, schreibenden Registry-/Dokument-/Discovery- oder aktiven Diagnoseberechtigungen müssen beim ersten Passwort-Login eine TOTP-kompatible Authenticator-App verbinden. Das Passwort allein erzeugt bei bereits eingerichteter MFA keine authentifizierte Anwendungssitzung. Acht einmalige Wiederherstellungscodes, TOTP-Replay-Schutz, getrennte Rate Limits, Session-Widerruf bei Reset und vollständiges Audit sind implementiert. Das Betriebs- und Wiederherstellungsverfahren beschreibt [Mehrfaktor-Authentisierung](MultiFactorAuthentication.md).

## Spätere Erweiterungen

- OIDC
- LDAP/Active Directory
- SAML nur bei realem Bedarf

## Lokale Kontoverwaltung

Administratoren verwalten lokale Konten unter `Einstellungen > Benutzerverwaltung`. Neue und administrativ gesetzte Passwörter folgen derselben zentralen Passwortregel. Beim Deaktivieren eines Kontos, beim Setzen eines neuen Passworts, bei Rollen-/Berechtigungsänderungen oder beim MFA-Reset werden betroffene Datenbanksitzungen widerrufen. Erfolgreiche An- und Abmeldungen, MFA-Ereignisse sowie administrative Kontoänderungen werden auditiert.

Externe Provider werden über Adapter integriert. Lokale Break-Glass-Verfahren benötigen eine dokumentierte organisatorische Freigabe.
