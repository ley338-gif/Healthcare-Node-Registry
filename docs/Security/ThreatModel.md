# Threat Model

## Scope

Webanwendung, PostgreSQL, Dokumentenspeicher, Browserzugriff, Backup und spätere optionale Integrationsworker.

## Schützenswerte Werte

- System- und Netzwerkdokumentation
- IP-Adressen, Hostnamen, Ports und AE Titles
- Benutzerkonten und Rollen
- Audit-Logs
- technische Dokumente und Conformance Statements
- Konfiguration und Secrets
- Backup-Artefakte

Auch ohne Patientendaten sind diese Informationen sicherheitskritisch.

## Hauptbedrohungen

| Bedrohung | Beispiel | Gegenmaßnahmen |
|---|---|---|
| Unbefugter Zugriff | gestohlenes Konto | MFA-Fähigkeit, sichere Sessions, RBAC |
| Rechteausweitung | normaler Nutzer ändert Rollen | serverseitige Policies, Audit |
| Datenmanipulation | Verbindung unbemerkt geändert | Audit, Freigabeprozesse, Backups |
| Informationsabfluss | Export oder Dokumentdownload | Least Privilege, Download-Audit |
| Schad Datei | kompromittierter Upload | Typ-/Größenprüfung, Quarantäne, Scan |
| Supply Chain | verwundbare Abhängigkeit | Lockfiles, Scans, SBOM |
| Ransomware | Daten und Backups verschlüsselt | getrennte Backups, Restore-Tests |
| Log-Leakage | Secrets in Fehlerlogs | Redaction, strukturierte Logs |
| CSRF/XSS | manipulierte Adminaktion | Frameworkschutz, CSP, Encoding |
| Fehlkonfiguration | Debug/Defaultpasswort | sichere Defaults, Startup Checks |

## Review

Das Bedrohungsmodell wird vor Beta und bei neuen Vertrauensgrenzen aktualisiert.
