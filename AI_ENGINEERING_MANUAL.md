# AI Engineering Manual

## 1. Zweck

Dieses Dokument ist die verbindliche Arbeitsanweisung für Menschen und KI-Systeme, die an der Healthcare Node Registry arbeiten. Es soll verhindern, dass isolierte Features, uneinheitliche Architektur, veraltete Dokumentation oder unkontrollierte technische Schulden entstehen.

Die KI arbeitet nicht als reiner Codegenerator, sondern gleichzeitig als:

- Senior Lead Developer und Software Architect
- Senior Laravel-, Vue- und Web-Entwickler
- PostgreSQL Database Engineer
- DevOps- und Container-Engineer
- Security Engineer
- QA- und Test-Engineer
- Technical Writer
- PACS-Administrator
- RIS-/KIS-Schnittstellenspezialist
- DICOM-, HL7- und FHIR-Fachexperte
- Product Owner mit Fokus auf realistische Healthcare-IT-Prozesse

Fachwissen ist kritisch anzuwenden. Vermutungen über Herstellerprodukte, DICOM-Verhalten oder regulatorische Anforderungen müssen als Annahmen markiert und vor einer produktiven Umsetzung verifiziert werden.

## 2. Produktauftrag

Entwickelt wird ein eigenständiges On-Premise-Produkt zur Dokumentation und Visualisierung medizinischer Systeme, Endpunkte und Kommunikationsbeziehungen.

Die Version 1.0 umfasst:

1. System- und Node-Registry
2. Endpunkte und Protokollkonfigurationen
3. Verbindungen und Abhängigkeiten
4. interaktive Topologie
5. Dokumentenanhänge
6. Suche, Filter und Tags
7. Benutzer, Rollen und Berechtigungen
8. Audit- und Änderungshistorie
9. Import/Export in kontrolliertem Umfang
10. sichere On-Premise-Bereitstellung

Monitoring, Discovery, aktive DICOM-Kommunikation und automatische Conformance-Analyse sind keine stillschweigenden Bestandteile des MVP.

## 3. Nicht verhandelbare Leitlinien

- On-Premise-first und offline betreibbar
- Security by Design und Privacy by Design
- Least Privilege und sichere Standardwerte
- nachvollziehbare Änderungen
- keine produktiven Patientendaten als notwendige Produktfunktion
- kein PHI/PII-Logging
- keine unkontrollierte Telemetrie
- migrationsfähiges Datenmodell
- dokumentierte Architekturentscheidungen
- automatisierbare Tests
- reproduzierbare Builds
- versionierte Abhängigkeiten und Container-Images
- Barrierearmut und Bedienbarkeit für Administratoren
- Dokumentation ist Bestandteil des Lieferumfangs

## 4. Verbindlicher Arbeitsablauf

### 4.1 Vor jeder Änderung

Die ausführende Person oder KI muss:

1. den fachlichen Zweck formulieren,
2. bestehende Dokumentation und verwandte Module prüfen,
3. Scope und Akzeptanzkriterien festlegen,
4. Sicherheits- und Datenschutzfolgen prüfen,
5. Datenbank- und Migrationsfolgen prüfen,
6. API- und UI-Auswirkungen prüfen,
7. Rückwärtskompatibilität prüfen,
8. Teststrategie festlegen,
9. notwendige Dokumentationsänderungen benennen,
10. Risiken und offene Annahmen dokumentieren.

Bei unklaren Anforderungen darf nicht durch erfundene Fachlogik fortgefahren werden.

### 4.2 Während der Umsetzung

- Änderungen bleiben klein, überprüfbar und thematisch fokussiert.
- Geschäftslogik gehört nicht in Controller oder UI-Komponenten.
- Datenbankmigrationen werden nicht nachträglich umgeschrieben, sobald sie veröffentlicht wurden.
- Keine Secrets, Passwörter, Zertifikate oder reale Gesundheitsdaten im Repository.
- Keine neue Abhängigkeit ohne Nutzen-, Wartungs- und Sicherheitsprüfung.
- Keine neue Architekturabstraktion ohne konkreten Bedarf.
- Fehler werden strukturiert behandelt und ohne vertrauliche Details ausgegeben.
- Audit-Events dürfen nicht mit technischen Debug-Logs verwechselt werden.

### 4.3 Nach jeder Änderung

Mindestens prüfen und bei Bedarf aktualisieren:

- `CHANGELOG.md`
- `ROADMAP.md`
- betroffene Dateien in `docs/`
- API-/Datenbankspezifikation
- Tests
- ADR bei wesentlichen Entscheidungen
- `KNOWN_ISSUES.md`
- Release Notes bei einer Version

### 4.4 Definition of Done

Eine Aufgabe ist nur erledigt, wenn:

- Akzeptanzkriterien erfüllt sind,
- automatisierte Tests vorhanden und erfolgreich sind,
- statische Analyse und Linting erfolgreich sind,
- Security-Auswirkungen bewertet sind,
- Berechtigungen getestet sind,
- Migration und Rollback bewertet sind,
- UI-Zustände für Laden, Leerstand und Fehler vorhanden sind,
- Logs keine sensiblen Daten enthalten,
- Dokumentation und Changelog aktualisiert sind,
- Review abgeschlossen ist,
- keine unaufgelösten kritischen oder hohen Risiken bestehen.

## 5. Architekturregeln

- Modularer Monolith für die erste Produktgeneration.
- Klare Domänengrenzen ohne verteilte Microservices.
- Backend ist fachliche Quelle der Wahrheit.
- Frontend erhält nur autorisierte und benötigte Daten.
- Services, Actions oder Use Cases kapseln Geschäftsprozesse.
- Policies/Gates erzwingen Autorisierung serverseitig.
- Validierung erfolgt serverseitig; Clientvalidierung dient nur der UX.
- PostgreSQL-Constraints sichern fachlich kritische Invarianten.
- Externe Integrationen werden über Adapter gekapselt.
- Hintergrundjobs müssen idempotent und beobachtbar sein.
- Ereignisse werden nur eingeführt, wenn Entkopplung einen realen Vorteil bietet.

Geplante Domänen:

- Identity & Access
- Organizations/Sites
- Assets/Systems
- Endpoints
- Connections
- Topology
- Documents
- Taxonomy
- Audit
- Import/Export
- Administration

## 6. Datenmodellregeln

- Primärschlüssel werden konsistent gewählt und dokumentiert.
- Menschenlesbare stabile IDs können zusätzlich verwendet werden.
- IP-Adressen werden mit PostgreSQL-geeigneten Typen oder validierten Strukturen gespeichert.
- Ports werden auf 1–65535 begrenzt.
- AE Titles werden fachlich validiert und normalisiert, ohne unzulässige Annahmen über Groß-/Kleinschreibung.
- Ein physisches oder logisches System kann mehrere Endpunkte besitzen.
- Ein DICOM-Endpunkt besteht nicht nur aus einer IP-Adresse.
- Verbindungen referenzieren konkrete Quell- und Zielendpunkte.
- Freitext ersetzt keine strukturierte Information, wo Suche und Auswertung erforderlich sind.
- Soft Deletes werden nur eingesetzt, wenn Wiederherstellung oder Referenzintegrität sie rechtfertigen.
- Auditdaten werden append-only konzipiert.
- Zeitstempel werden in UTC gespeichert und in der UI lokal dargestellt.

## 7. Healthcare-Fachregeln

- DICOM Node, Application Entity, Gerät und System sind fachlich zu unterscheiden.
- SCU/SCP-Rollen werden pro Service modelliert.
- Services wie C-STORE, C-FIND/MWL, C-MOVE, MPPS und Storage Commitment werden nicht als austauschbare Checkboxen ohne Beziehungskontext behandelt.
- Transfer Syntaxes und SOP Classes werden erst modelliert, wenn der Anwendungsfall dies verlangt.
- HL7-Endpunkte benötigen Richtung, Transport, Host, Port, Zeichensatz und fachlichen Nachrichtenkontext.
- FHIR-Basis-URLs, Authentisierungsverfahren und Capability-Informationen werden getrennt modelliert.
- Patientendaten sind für den Registry-Kern nicht erforderlich und sollen nicht gespeichert werden.
- Screenshots und Testdaten dürfen keine echten Patientendaten enthalten.

## 8. Frontend- und UX-Regeln

Die Datei `specification/ui-reference.png` ist die visuelle Leitlinie.

- Desktop-first für administrative Nutzung, dennoch responsive.
- dauerhafte linke Navigation, klare Kopfzeile und große Arbeitsfläche
- Statusfarben immer zusätzlich mit Text oder Symbol
- konsistente Cards, Tabellen, Badges, Dialoge und Detail-Drawer
- Topologie ist ein Arbeitswerkzeug, kein dekoratives Diagramm
- Filterzustände sind sichtbar und zurücksetzbar
- gefährliche Aktionen benötigen Bestätigung und Berechtigungsprüfung
- Tastaturbedienung, sichtbare Fokuszustände und semantisches HTML
- keine unnötigen Animationen
- keine erfundenen Monitoringdaten im produktiven UI
- Design-Tokens statt verstreuter Einzelwerte

Jede Ansicht berücksichtigt:

- Loading State
- Empty State
- Error State
- Permission Denied State
- Success Feedback
- lange Texte und große Datenmengen
- deutsche und später englische Texte

## 9. Security Engineering

Ziel ist eine belastbare Grundlage, keine behauptete Zertifizierung.

Mindestens:

- serverseitiges RBAC
- sichere Sessions und Cookies
- CSRF-Schutz
- Output Encoding
- Content Security Policy
- Rate Limiting
- sichere Passwortspeicherung
- MFA-fähige Architektur
- Schutz vor Enumeration
- Auditierung administrativer Aktionen
- sichere Datei-Uploads
- Malware-Scan-Schnittstelle vorbereiten
- MIME-, Größen- und Dateitypprüfung
- Secrets außerhalb des Repositories
- Dependency- und Container-Scanning
- SBOM pro Release
- signierte oder prüfbare Release-Artefakte
- dokumentiertes Vulnerability- und Incident-Verfahren
- Backup-, Restore- und Update-Konzept

OWASP ASVS wird als technische Verifikationsgrundlage genutzt. Die konkrete Zielstufe wird vor Beta festgelegt.

## 10. ISO-27001-Ausrichtung

Die Software unterstützt organisatorische Sicherheitsmaßnahmen, ersetzt aber kein ISMS.

Das Projekt führt mindestens:

- Asset- und Informationsklassifizierung
- Risikoregister
- Zugriffs- und Rollenmatrix
- Lieferanten-/Abhängigkeitsbewertung
- Änderungsmanagement
- Secure Development Lifecycle
- Schwachstellenmanagement
- Incident- und Meldeprozess
- Backup- und Restore-Nachweise
- Logging- und Aufbewahrungskonzept
- Release- und Freigabenachweise
- regelmäßige interne Reviewpunkte
- Maßnahmenverfolgung

ISO-Klauseltexte werden nicht kopiert. Verwendet werden eigene Arbeitsnachweise und eine kontrollierte Zuordnung.

## 11. ISO-9001-Ausrichtung

Qualitätsmanagement wird durch kontrollierte Prozesse vorbereitet:

- Anforderungen besitzen Quelle, Verantwortlichkeit und Akzeptanzkriterien.
- Änderungen sind über Issue, Branch, Pull Request, Test und Release nachvollziehbar.
- Fehler und Abweichungen werden dokumentiert.
- Korrekturmaßnahmen werden auf Wirksamkeit geprüft.
- Qualitätsziele erhalten messbare Kriterien.
- Kundenfeedback fließt kontrolliert in die Roadmap.
- Dokumente besitzen Eigentümer, Status, Version und Reviewdatum.
- Releases benötigen definierte Freigabekriterien.
- Wissen, Lessons Learned und abgelehnte Entscheidungen werden erhalten.
- Risiken und Chancen werden regelmäßig bewertet.

## 12. Git- und GitHub-Regeln

Empfohlenes Branching:

- `main`: jederzeit releasefähig
- kurze Feature-/Fix-Branches
- Pull Request für jede nicht triviale Änderung
- keine direkten produktiven Änderungen auf `main`

Commit-Stil:

- `feat:`
- `fix:`
- `docs:`
- `test:`
- `refactor:`
- `security:`
- `build:`
- `chore:`

Pull Requests enthalten:

- Problem und Ziel
- Scope
- Screenshots bei UI-Änderungen
- Datenbankauswirkungen
- Sicherheitsauswirkungen
- Tests
- Dokumentationsänderungen
- Rollback-/Migrationshinweise
- verknüpftes Issue

## 13. Versionierung und Releases

Semantic Versioning:

- Major: inkompatible Änderung
- Minor: rückwärtskompatible Funktion
- Patch: rückwärtskompatibler Fix

Vor 1.0 können Minor-Versionen stärker brechen; jede Abweichung muss dokumentiert werden.

Jedes Release benötigt:

- Changelog
- Release Notes
- Migrationshinweise
- bekannte Einschränkungen
- geprüfte Installations-/Updateanleitung
- SBOM
- Backup-/Restore-Hinweis
- dokumentierte Freigabe

## 14. Dokumentationsstandard

Jedes Dokument enthält, soweit sinnvoll:

- Zweck
- Geltungsbereich
- Eigentümer/Rolle
- Status
- Version
- letzte Prüfung
- nächste Prüfung
- referenzierte Entscheidungen
- offene Punkte

Dokumente dürfen nicht lediglich Absichtserklärungen enthalten. Sie müssen den tatsächlichen Projektstand abbilden.

## 15. Verhalten einer KI im Repository

Die KI muss:

- zuerst lesen, dann planen, dann ändern,
- keine vorhandene Funktion blind ersetzen,
- keine Dateien löschen, ohne Abhängigkeiten zu prüfen,
- Annahmen ausdrücklich kennzeichnen,
- bei widersprüchlichen Anforderungen stoppen,
- kleine, nachvollziehbare Patches bevorzugen,
- Tests und Dokumentation im selben Arbeitspaket behandeln,
- keine Zertifizierungs- oder Compliance-Erfüllung behaupten,
- keine Herstellerkompatibilität ohne Nachweis behaupten,
- keine reale Healthcare-Infrastruktur scannen oder verändern,
- bei sicherheitskritischen Änderungen einen manuellen Review verlangen.

Vor jeder Implementierung liefert die KI einen knappen Plan mit:

1. Ziel
2. betroffene Dateien
3. Datenmodell-/API-Auswirkungen
4. Sicherheitsauswirkungen
5. Tests
6. Dokumentation
7. Risiken

## 16. Verbotene Abkürzungen

Nicht zulässig:

- Security oder RBAC nur im Frontend
- unverschlüsselte Standardzugänge
- Standardpasswörter
- `latest`-Container-Tags im produktiven Beispiel
- ungeprüfte Datei-Uploads
- produktive Debug-Modi
- irreversible Migrationen ohne Plan
- Logging kompletter Request-Bodies
- Speicherung von Patientendaten „für spätere Nutzung“
- künstliche Monitoringwerte
- nicht dokumentierte externe SaaS-Abhängigkeiten
- Copy-and-paste von ISO-Normtexten
- Behauptung „ISO-konform“ ohne belastbaren Scope und Nachweis

## 17. Review-Rhythmus

- bei jedem Pull Request: technische und dokumentarische Konsistenz
- monatlich in aktiver Entwicklung: Roadmap, Risiken, Technical Debt
- vor jedem Release: Security-, Qualitäts- und Dokumentationsreview
- mindestens jährlich: Grundsatzreview von Architektur, Bedrohungsmodell und Compliance-Mapping
