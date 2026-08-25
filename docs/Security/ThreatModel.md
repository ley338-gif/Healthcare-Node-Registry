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
7. Anwendung/Worker zu freigegebenen Discovery-Netzen und DICOM-Zielsystemen
8. DICOM-Zielsystem zurück zum Storage-Commitment-Callback
9. spätere Verzeichnis- oder Netzwerkadapter

## Zentrale Bedrohungen

| Bedrohung | Beispiel | Primäre Maßnahmen | Status |
|---|---|---|---|
| unbefugter Zugriff | gestohlenes Passwort | verpflichtende TOTP-MFA für privilegierte Konten, sichere Sessions, Recovery-Codes und RBAC | für lokale privilegierte Konten implementiert; Phishing-resistente Faktoren offen |
| Rechteausweitung | Editor ändert Rollen | serverseitige Policies, Default Deny, Audit | Konzept vorhanden |
| Datenmanipulation | Verbindung unbemerkt geändert | Constraints, Audit, Reviews, Backups | Konzept vorhanden |
| Informationsabfluss | Export/Download | gesonderte Rechte, Audit, Scope-Prüfung | Konzept vorhanden |
| schädliche Datei | kompromittierter Upload | Allowlist, Quarantäne, Scanner, Fail Closed | Konzept vorhanden |
| Supply Chain | verwundbare Abhängigkeit oder kompromittiertes Build-Artefakt | Lockfiles, History-Secret-Scan, Dependency- und Release-Image-Scans, CycloneDX-SBOM, SHA-gepinnte CI-Actions und Freigaben | automatisierte CI-Gates implementiert; Signierung/Provenance offen |
| Ransomware | Daten und Backups verschlüsselt | getrennte Backups, Restore-Tests | Betriebsentscheidung offen |
| Log Leakage | Secrets in Logs | Redaction, strukturierte Logs, Event Catalog | Konzept vorhanden |
| CSRF/XSS | manipulierte Adminaktion | Frameworkschutz, CSP, Encoding | offen bis Implementierung |
| Fehlkonfiguration | Debug/Defaultpasswort | sichere Defaults, Setup-Checks | offen bis Implementierung |
| CSV Injection | Export wird in Office geöffnet | Formel-Injection-Schutz | Konzept vorhanden |
| Enumeration | erratbare IDs oder Nutzer | public IDs, generische Fehlermeldungen | ADR offen |
| Backup-Diebstahl | ungeschütztes Backup | Verschlüsselung, Zugriffskontrolle | Betriebsentscheidung offen |
| Scan fremder/nicht autorisierter Netze | Discovery-Lauf gegen fremdes Netz gestartet | Freigabeliste `discovery_allowed_networks` (Default nur RFC1918), Pflichtbestätigung im Wizard, Audit | Implementiert |
| Command Injection über Scan-Parameter | manipulierter Host/Port/AE-Titel erreicht eine Shell | ausschließlich Argumentlisten via `Symfony\Process`, keine Shell-Strings, serverseitige Eingabevalidierung (IPv4, Port 1–65535, AE-Titel-Regex) | Implementiert |
| Ressourcenerschöpfung durch großen Scan | sehr großer Zielbereich blockiert Worker | `discovery.max_range_size`, `max_parallel_hosts`, harte Prozess-Timeouts, kooperativer Abbruch | Implementiert |
| AE-Titel-Brute-Forcing | unbegrenzte Called-AE-Versuche gegen ein Zielsystem | `discovery.max_ae_attempts_per_port` begrenzt Versuche je Host/Port | Implementiert |
| aktive Diagnose verändert Zielsystem | C-STORE, MPPS oder Storage Commitment hinterlässt synthetische Objekte | separate Einzelrechte, Pflichtbestätigung, synthetische Daten, Testfenster, Audit und Cleanup-Owner | technisch implementiert; Zielsystem-Cleanup ist Pilot-Gate |
| DICOM-Fehlrouting oder unzulässiger Egress | registrierter, aber falsch zugeordneter Knoten wird kontaktiert | aktive Registry-Ziele, serverseitige Policies, AE-/Portvalidierung, Host-Firewall und zielgenaue Egress-Regeln | technische Kontrollen vorhanden; Zielhostabnahme offen |
| Klartext-/Zertifikatsdowngrade | fehlerhaftes TLS-Profil führt zu unbemerktem Klartext | TLS fail-closed ohne Klartext-Wiederholung, read-only Zertifikatsprofile, Ablaufüberwachung | implementiert; Zertifikatsalarm ist Pilot-Gate |
| ungeschützter Callback-Port | fremdes System erreicht den Storage-Commitment-Listener | konfigurierbare Bind-Adresse, Quell-Firewall, kurzer ereignisbezogener Listener und separates Recht | Implementiert; Firewallnachweis offen |

## Review

- vor 0.1.0: Architektur- und Trust-Boundary-Review
- vor 0.5.0: Upload-/Dokumentenreview
- vor 0.9.0: vollständiger Pilot-Security-Review
- bei neuen Vertrauensgrenzen oder externen Integrationen
