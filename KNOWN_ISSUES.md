# Known Issues

## Unreleased

- Der Audit-Trail besitzt eine interne SHA-256-Hash-Chain und kontrollierte Aufbewahrung;
  eine externe signierte Verankerung gegen Angreifer mit vollständigem Datenbank-Schreibzugriff
  ist noch nicht umgesetzt.
- Dokumentfreigabe und kategoriebasierte Mindestaufbewahrung sind umgesetzt; ein automatisierter physischer Löschlauf nach Fristende ist bewusst nicht vorhanden.
- Historische Entitätsnamen sind nicht als vollständige Snapshots verfügbar.

## 0.1.1

- MFA, OIDC und LDAP fehlen noch.
- Backup-Verschlüsselung und Schlüsselverwaltung sind installationsspezifisch.
