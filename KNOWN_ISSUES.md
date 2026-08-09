# Known Issues

## Unreleased

- Der Audit-Trail besitzt eine interne SHA-256-Hash-Chain und kontrollierte Aufbewahrung;
  eine externe signierte Verankerung gegen Angreifer mit vollständigem Datenbank-Schreibzugriff
  ist noch nicht umgesetzt.
- Dokumentfreigabe und kategoriebasierte Mindestaufbewahrung sind umgesetzt; ein automatisierter physischer Löschlauf nach Fristende ist bewusst nicht vorhanden.
- Historische Entitätsnamen sind nicht als vollständige Snapshots verfügbar.
- Systemtypen und Systemstatus sind zentral administrierbar; Hersteller, DICOM-Dienste, Dokumentkategorien und Portprofile besitzen noch keine gemeinsame Referenzdatenverwaltung.

## 0.1.1

- OIDC und LDAP fehlen noch; MFA ist aktuell auf lokale TOTP-Codes beschränkt und unterstützt noch keine phishing-resistenten Passkeys/WebAuthn-Faktoren.
- Backup-Verschlüsselung und Schlüsselverwaltung sind installationsspezifisch.
