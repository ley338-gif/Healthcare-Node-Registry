# Requirements

## Funktionale Muss-Anforderungen

| ID | Anforderung | Akzeptanzkriterien |
|---|---|---|
| FR-001 | Systeme anlegen, ändern, anzeigen und kontrolliert archivieren | serverseitige Validierung; Berechtigungsprüfung; Archivierung statt unkontrollierter Löschung; Audit-Event |
| FR-002 | Mehrere Endpunkte je System verwalten | mindestens DICOM, HL7, FHIR, REST, LDAP, DB und Datei fachlich unterscheidbar; Duplikatwarnung |
| FR-003 | DICOM AE und Dienste dokumentieren | AE Title, Host/IP, Port, Dienst, Quell-/Zielrolle, Informationsquelle und Verantwortlichkeit strukturiert |
| FR-004 | gerichtete Verbindungen zwischen Endpunkten abbilden | Quelle und Ziel sind konkrete Endpoints; Selbstverbindungen werden bewusst validiert; Richtung sichtbar |
| FR-005 | Beziehungen als interaktive Topologie visualisieren | Filter, Auswahl, Detailansicht, Richtung, Dienst, Tastaturzugriff und Empty/Error State |
| FR-006 | Suche nach relevanten Feldern | Name, AE Title, IP, Hostname, Hersteller und Standort; Berechtigungsfilter serverseitig; keine unautorisierten Treffer |
| FR-007 | Rollen und Berechtigungen serverseitig durchsetzen | Default Deny; Policy-/Gate-Tests; keine ausschließlich clientseitige Autorisierung |
| FR-008 | Änderungen an kritischen Objekten auditieren | Actor, Aktion, Zielobjekt, Zeitpunkt, Korrelation und bereinigte Vorher-/Nachherdaten |
| FR-009 | Dokumente sicher an Systeme anhängen | Allowlist, MIME-Prüfung, Größenlimit, zufälliger Storage Key, Hash, Quarantäne-/Scanstatus, Download-Audit |
| FR-010 | kontrollierten Export bereitstellen | getrennte Berechtigung; Audit; Formel-Injection-Schutz; definierte Schemas; keine unautorisierten Daten |
| FR-011 | Installation, Update, Backup und Restore dokumentieren | reproduzierbare Schritte, Rückrollstrategie, Restore-Test und bekannte Einschränkungen |
| FR-012 | Verantwortlichkeiten dokumentieren | Teams oder Kontakte können Systemen, Standorten und Diensten mit Rolle zugeordnet werden |
| FR-013 | Informationsquelle und Prüfstatus dokumentieren | Quelle, Prüfdatum, prüfende Person und Dokumentationsstatus sind nachvollziehbar |

## Nichtfunktionale Anforderungen

| ID | Anforderung | Ziel |
|---|---|---|
| NFR-001 | Offline-Betrieb | keine verpflichtende externe Cloud oder Telemetrie |
| NFR-002 | PostgreSQL | primäre relationale Datenbank, Constraints für Invarianten |
| NFR-003 | reproduzierbare Bereitstellung | versionierte Images, Lockfiles, Docker Compose |
| NFR-004 | keine notwendigen Patientendaten | Produktkern funktioniert ohne PHI; Tests und Seeds synthetisch |
| NFR-005 | sichere Defaults | Debug aus, Default Deny, sichere Cookies, CSP und Rate Limits |
| NFR-006 | nachvollziehbare Releases | SemVer, Changelog, ADRs und Migrationsbewertung |
| NFR-007 | barrierearme Admin-Oberfläche | Tastaturbedienung, Fokuszustände, semantisches HTML, Status nicht nur farblich |
| NFR-008 | Betriebsdokumentation | Rollen-, Backup-, Restore-, Logging- und Updatekonzept |
| NFR-009 | ISO-orientierte Nachweise | keine Zertifizierungsbehauptung; nachvollziehbare technische und prozessuale Evidenz |
| NFR-010 | Security-Verifikation | ASVS-orientierte Prüfung, Dependency- und Container-Scanning |
| NFR-011 | Performance-Baseline | Listen und Suche werden mit definierten Testdaten gemessen; Grenzwerte vor Pilot festlegen |
| NFR-012 | Datenminimierung | nur für den Registry-Zweck erforderliche Daten speichern |
| NFR-013 | Portabilität | dokumentierte Unterstützung definierter Linux-/Container-Plattformen |

## Traceability

Die Zuordnung von Anforderungen zu Modulen, Tests, Risiken und Releases steht in `docs/Product/RequirementsTraceability.md`.
