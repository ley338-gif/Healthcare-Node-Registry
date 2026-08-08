# Known Issues

## Unreleased

- Der Audit-Trail besitzt eine interne SHA-256-Hash-Chain und kontrollierte Aufbewahrung;
  eine externe signierte Verankerung gegen Angreifer mit vollständigem Datenbank-Schreibzugriff
  ist noch nicht umgesetzt.
- Dokumentfreigabe und kategoriebasierte Mindestaufbewahrung sind umgesetzt; ein automatisierter physischer Löschlauf nach Fristende ist bewusst nicht vorhanden.
- Historische Entitätsnamen sind nicht als vollständige Snapshots verfügbar.
- Der synthetische Storage-Test deckt derzeit nur die implementierte Secondary-Capture-SOP-Klasse ab; weitere synthetische Storage-SOP-Klassen fehlen.

## 0.1.1

- Clean Install und Restore müssen noch auf einer separaten Zielumgebung protokolliert werden.
- MFA, OIDC und LDAP fehlen noch.
- Backup-Verschlüsselung und Schlüsselverwaltung sind installationsspezifisch.
