# Known Issues

## 0.1.0

- Composer- und npm-Lockfiles können in dieser Übergabeumgebung ohne externen Paketdownload nicht erzeugt werden. Sie müssen beim ersten kontrollierten Installationslauf erstellt und committed werden.
- Ein produktiver Setup-Wizard für das erste Administratorkonto fehlt noch.
- MFA, OIDC und LDAP sind noch nicht implementiert.
- PostgreSQL-Backup und Restore sind dokumentarisch vorbereitet, aber noch nicht in einer Zielumgebung getestet.
- Das Dashboard enthält bewusst keine erfundenen Live-Monitoringwerte.
- Die reservierten Modulverzeichnisse enthalten noch keine Fachimplementierung.
