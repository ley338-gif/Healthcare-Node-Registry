# Known Issues

## Unreleased

- Ein Pilot-Go bleibt blockiert, bis Zielhostinstallation, Restore/RPO/RTO, Alarmwege, namentliches Rollen-/MFA-Review, Healthcare-E2E mit synthetischen Daten, UX-Abnahme und formale Dokumentfreigabe in einer autorisierten Pilotumgebung nachgewiesen sind. Die technische Go/No-Go-Matrix ist vorhanden, ersetzt diese externen Nachweise aber ausdrücklich nicht.

- Die reinen Entwicklungswerkzeuge für das Linting besitzen bekannte Advisories (`braces`/`micromatch`/`fast-glob` über `@vue/eslint-config-typescript`, `postcss-selector-parser` über `eslint-plugin-vue`). Sie sind weder in den Release-Images noch in ausgelieferten Frontend-Artefakten enthalten; die Behebung erfordert Major-Upgrades der ESLint-Konfiguration und ist als nicht pilot-blockierende Wartung offen.

- Der Audit-Trail besitzt eine interne SHA-256-Hash-Chain und kontrollierte Aufbewahrung;
  eine externe signierte Verankerung gegen Angreifer mit vollständigem Datenbank-Schreibzugriff
  ist noch nicht umgesetzt.
- Dokumentfreigabe und kategoriebasierte Mindestaufbewahrung sind umgesetzt; ein automatisierter physischer Löschlauf nach Fristende ist bewusst nicht vorhanden.
- Historische Entitätsnamen sind nicht als vollständige Snapshots verfügbar.
- Systemtypen, Systemstatus und Hersteller sind zentral administrierbar; DICOM-Dienste, Dokumentkategorien und Portprofile besitzen noch keine gemeinsame Referenzdatenverwaltung. Produkte bleiben bewusst Freitext.

## 0.1.1

- OIDC und LDAP fehlen noch; MFA ist aktuell auf lokale TOTP-Codes beschränkt und unterstützt noch keine phishing-resistenten Passkeys/WebAuthn-Faktoren.
- Backup-Verschlüsselung und Schlüsselverwaltung sind installationsspezifisch.
