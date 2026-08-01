# Strukturierte Registry-Dokumentation

`registry_documentation` speichert aktuelle Betriebsdokumentation polymorph für Organisationen, Standorte, Abteilungen und Systeme. Stammdaten wie Hersteller, Hostname oder Adresse bleiben in ihren Registry-Modellen und werden in der Dokumentationsoberfläche nur read-only angezeigt.

## Struktur

Jeder Eintrag besitzt Dokumentationstyp, Sektion, Titel, optionalen Inhalt, strukturierte JSON-Daten, Sichtbarkeit sowie Ersteller und letzten Bearbeiter. Pro Entität, Dokumentationstyp und Sektion existiert höchstens ein Eintrag.

Systeme bieten zehn Betriebssektionen. Organisationen, Standorte und Abteilungen besitzen eigene fachlich passende Sektionen. Alle verwenden `DocumentationPanel.vue` mit Cards, sektionenweisem Slide-over und einem nachvollziehbaren Fortschritt aus explizit definierten Pflichtfeldern.

## Audit und Berechtigungen

Lesen und Bearbeiten verwenden die bestehenden Registry-Policies. Es wurde kein paralleles Rollensystem eingeführt. Jede Anlage oder Änderung erzeugt `documentation.updated` über `RegistryAudit` am dokumentierten Registry-Kontext.

Das Audit enthält keine vollständigen Langtexte oder JSON-Inhalte. Für diese Felder werden Länge und SHA-256 protokolliert. Damit bleibt die Änderung nachweisbar, ohne sensible Betriebsinformationen zu duplizieren.

## Bekannte Einschränkungen

- keine Datei-Uploads oder Anhänge
- keine Freigabe- oder Versionsworkflows
- keine Exporte
- `restricted` ist vorbereitet; feinere Sichtbarkeitsrechte folgen erst bei einem abgestimmten Berechtigungskonzept

## Bestandsanalyse für die Dokumentenablage

Die vorhandene strukturierte Dokumentation bleibt die Quelle für editierbare
Betriebsinformationen. Eine künftige Dateiablage ergänzt sie, ersetzt sie aber
nicht: Das fachliche Dokument und seine unveränderlichen Dateiversionen benötigen
eigene Modelle mit einer polymorphen Zuordnung zu denselben vier Registry-Entitäten.
Stammdaten bleiben weiterhin ausschließlich in Organisation, Standort, Abteilung
und System gespeichert.

### Vorhandene Datenstruktur und Abläufe

| Bereich | Heutiger Stand | Konsequenz |
| --- | --- | --- |
| Dokumentation | `RegistryDocumentation` mit UUID, polymorphem Kontext, Typ, Sektion, Freitext, JSON-Feldern, Sichtbarkeit und Bearbeitern | Bestehende Datensätze und Endpunkte bleiben erhalten. Binärdateien gehören nicht in dieses Modell. |
| Eindeutigkeit | Eine Sektion je Kontext und Dokumentationstyp | Geeignet für den aktuellen Bearbeitungsstand, nicht für Dateiversionen. |
| Vollständigkeit | Das Frontend zählt explizit als `required` definierte Felder; Boolean-Werte gelten als gepflegt | Berechnung ist nachvollziehbar. Ohne definierte Pflichtfelder darf kein scheinpräziser Prozentwert entstehen. |
| Autorisierung | Lesen und Schreiben delegieren an die bestehenden `view`- und `update`-Policies des Registry-Kontexts (`registry.view`/`registry.manage`) | Dokumentrechte müssen in die vorhandene RBAC-Konvention aufgenommen und zusätzlich serverseitig gegen den Kontext geprüft werden. |
| Audit | Anlage und Änderung schreiben `documentation.updated` über `RegistryAudit`; Langtext und JSON erscheinen nur als Länge und SHA-256 | Dateiaktionen verwenden dieselbe `security_events`-Quelle. Dateiinhalte und sensible Metadaten dürfen nicht ins Audit gelangen. |
| Historie | `RegistryHistoryService` sammelt Ereignisse eines Kontexts und seiner Nachfolger; `RegistryHistoryViewService` filtert und paginiert | Dokumentereignisse werden am zugeordneten Registry-Kontext protokolliert und erscheinen dadurch ohne zweite Historienarchitektur. |
| Storage | Der Standard-Disk `local` liegt unter `storage/app/private`; `public` existiert separat. Docker persistiert `storage` in einem Volume | Dokumentdateien verwenden ausschließlich einen privaten Disk. Keine Storage-URL und kein Symlink auf den Public-Disk. |
| Bestehender Upload | Die DICOM-Dateianalyse kopiert einen Upload in ein Verzeichnis mit Modus `0700`, analysiert ihn und entfernt ihn im `finally`-Block | Das Muster für temporäre Verarbeitung und garantierte Bereinigung ist nutzbar, aber keine dauerhafte Dokumentablage. |

Es gibt aktuell weder einen allgemeinen Datei-Download oder eine Vorschau noch
eine persistente Upload-Funktion, Dateiversionierung, Duplikaterkennung oder
Quarantäne. Eine Malware-Integration ist nicht vorhanden. Die DICOM-Analyse
begrenzt nur die Größe; sie ist keine MIME-, Magic-Byte- oder Malware-Prüfung für
Registry-Dokumente.

### Wiederverwendbare Komponenten

- `DocumentationPanel.vue` und die zentralen Sektionsdefinitionen für Systeme,
  Organisationen, Standorte und Abteilungen
- die vorhandenen System- und Organisationsstruktur-Workspaces mit Tabs und
  deren Datenübergabe
- bestehende Slide-over-Gestaltung sowie `StatusBadge.vue`, `EmptyState.vue` und
  die vorhandene Pagination
- Registry-Policies, Rollen, Berechtigungen und die etablierten Test-Helper
- `RegistryAudit`, `RegistryHistoryService`, `RegistryHistoryViewService` und
  `AuditHistoryPanel.vue`
- privater Laravel-Storage und die persistenten Docker-Storage-Volumes

### Erforderliche Erweiterungen

Die Dateiablage benötigt ein fachliches, polymorph zugeordnetes Dokumentmodell
und ein separates, append-only orientiertes Versionsmodell. Die aktuelle Version
muss eindeutig referenziert werden; alte Versionen bleiben erhalten. Zusätzlich
sind zentrale Kategorien, eigene Dokumentberechtigungen innerhalb des bestehenden
RBAC, ein Upload-/Prüfservice, eine Malware-Scanner-Schnittstelle sowie
autorisierte Download- und Preview-Endpunkte erforderlich. Hash, Dateigröße,
erkannter MIME-Typ, Scanstatus und sicher erzeugter Storage-Pfad werden pro Version
gespeichert. Archivierung ersetzt eine normale physische Löschung.

Die UI wird aus dem bestehenden Dokumentationsbereich heraus erweitert. Sie
verwendet dieselben Workspace-, Card-, Tabellen-, Badge- und Slide-over-Muster;
es entsteht weder eine zweite Dokumentationsoberfläche noch ein paralleles Audit.

### Vor jedem dauerhaften Upload zu schließende Sicherheitslücken

1. Erlaubte Endungen, serverseitig erkannte MIME-Typen und Dateisignaturen müssen
   gemeinsam validiert werden; Endung oder Browser-MIME allein reichen nicht.
2. Ein konfigurierbares Größenlimit, zufällige Storage-Namen, normalisierte
   Anzeigenamen und strikt vom Server erzeugte Pfade verhindern Ressourcenmissbrauch
   und Pfadmanipulation.
3. Dateien werden nur privat gespeichert. Download und Vorschau brauchen für
   jede Anfrage Autorisierung, sichere `Content-Type`-/`Content-Disposition`-Header
   und `X-Content-Type-Options: nosniff`.
4. Ein Malware-Scanner-Vertrag mit den Zuständen `pending`, `clean`, `infected`,
   `failed` und `unavailable` ist vor Freigabe erforderlich. Nicht saubere Dateien
   bleiben gesperrt; technische Scannerdetails werden nicht roh angezeigt.
5. SVG bleibt bis zu einer sicheren Bereinigung gesperrt. ZIP wird nie automatisch
   entpackt; ausführbare Dateien und Office-Makroformate bleiben verboten.
6. SHA-256 und eine definierte Duplikatregel verhindern unbemerkte Doppeluploads.
   Datenbank- und Storage-Schreibvorgänge benötigen ein konsistentes Fehler- und
   Aufräumverhalten.
7. Upload, Versionswechsel, Scanfehler, Infektionsfund und Archivierung werden
   über `RegistryAudit` am Registry-Kontext protokolliert, ohne Dateiinhalte,
   Zugangsdaten, interne Pfade oder ungefilterte Scanner-Ausgaben zu speichern.
8. Das persistente private Storage-Volume muss in Backup, Restore, Aufbewahrung
   und Desaster-Recovery gemeinsam mit den Versionsmetadaten berücksichtigt werden.

Bis diese Punkte implementiert und getestet sind, ist die Dokumentenablage nur
geplant. Die derzeitige Anwendung stellt keine dauerhafte Upload-, Download- oder
Vorschaufunktion für Registry-Dokumente bereit.
