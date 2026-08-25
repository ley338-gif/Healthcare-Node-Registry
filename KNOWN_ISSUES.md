# Known Issues

## Unreleased

- Die bestehenden Application-/Documentation-Workflows verwenden teilweise noch Actions auf Node-20-Basis, die GitHub derzeit unter Node 24 ausführt und mit einer Deprecation-Warnung versieht. Der neue Supply-Chain-Workflow verwendet bereits SHA-gepinnte Node-24-Actions; die übrige CI-Aktualisierung ist als nicht pilot-blockierende Wartung offen.

- Der Audit-Trail besitzt eine interne SHA-256-Hash-Chain und kontrollierte Aufbewahrung;
  eine externe signierte Verankerung gegen Angreifer mit vollständigem Datenbank-Schreibzugriff
  ist noch nicht umgesetzt.
- Dokumentfreigabe und kategoriebasierte Mindestaufbewahrung sind umgesetzt; ein automatisierter physischer Löschlauf nach Fristende ist bewusst nicht vorhanden.
- Historische Entitätsnamen sind nicht als vollständige Snapshots verfügbar.
- Systemtypen, Systemstatus und Hersteller sind zentral administrierbar; DICOM-Dienste, Dokumentkategorien und Portprofile besitzen noch keine gemeinsame Referenzdatenverwaltung. Produkte bleiben bewusst Freitext.

## 0.1.1

- OIDC und LDAP fehlen noch; MFA ist aktuell auf lokale TOTP-Codes beschränkt und unterstützt noch keine phishing-resistenten Passkeys/WebAuthn-Faktoren.
- Backup-Verschlüsselung und Schlüsselverwaltung sind installationsspezifisch.
