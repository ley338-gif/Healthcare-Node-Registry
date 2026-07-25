# Threat Model

## Scope

Webanwendung, Reverse Proxy, PostgreSQL, Dokumentenspeicher, Browserzugriff, Import/Export, Backup und spätere optionale Integrationsadapter.

## Schützenswerte Werte

- System- und Netzwerkdokumentation
- IP-Adressen, Hostnamen, Ports und AE Titles
- Benutzerkonten, Rollen und Sessions
- Audit Events
- technische Dokumente und Conformance Statements
- Konfiguration und Secrets
- Export- und Backup-Artefakte

Auch ohne Patientendaten sind diese Informationen sicherheitskritisch.

## Trust Boundaries

1. Browser zu Reverse Proxy
2. Reverse Proxy zu Anwendung
3. Anwendung zu Datenbank
4. Anwendung zu Dokumentenspeicher
5. Anwendung zu Malware-Scanner
6. Anwendung/Storage zu Backup
7. spätere Verzeichnis- oder Netzwerkadapter

## Zentrale Bedrohungen

| Bedrohung | Beispiel | Primäre Maßnahmen | Status |
|---|---|---|---|
| unbefugter Zugriff | gestohlenes Konto | MFA-fähig, sichere Sessions, RBAC | offen bis Implementierung |
| Rechteausweitung | Editor ändert Rollen | serverseitige Policies, Default Deny, Audit | Konzept vorhanden |
| Datenmanipulation | Verbindung unbemerkt geändert | Constraints, Audit, Reviews, Backups | Konzept vorhanden |
| Informationsabfluss | Export/Download | gesonderte Rechte, Audit, Scope-Prüfung | Konzept vorhanden |
| schädliche Datei | kompromittierter Upload | Allowlist, Quarantäne, Scanner, Fail Closed | Konzept vorhanden |
| Supply Chain | verwundbare Abhängigkeit | Lockfiles, Scans, SBOM, Freigaben | offen bis CI |
| Ransomware | Daten und Backups verschlüsselt | getrennte Backups, Restore-Tests | Betriebsentscheidung offen |
| Log Leakage | Secrets in Logs | Redaction, strukturierte Logs, Event Catalog | Konzept vorhanden |
| CSRF/XSS | manipulierte Adminaktion | Frameworkschutz, CSP, Encoding | offen bis Implementierung |
| Fehlkonfiguration | Debug/Defaultpasswort | sichere Defaults, Setup-Checks | offen bis Implementierung |
| CSV Injection | Export wird in Office geöffnet | Formel-Injection-Schutz | Konzept vorhanden |
| Enumeration | erratbare IDs oder Nutzer | public IDs, generische Fehlermeldungen | ADR offen |
| Backup-Diebstahl | ungeschütztes Backup | Verschlüsselung, Zugriffskontrolle | Betriebsentscheidung offen |

## Review

- vor 0.1.0: Architektur- und Trust-Boundary-Review
- vor 0.5.0: Upload-/Dokumentenreview
- vor 0.9.0: vollständiger Pilot-Security-Review
- bei neuen Vertrauensgrenzen oder externen Integrationen
