# Registry-Dokumentation und Dokumentenablage

Die Registry verbindet zwei getrennte, aber gemeinsam dargestellte Informationsarten:

- `registry_documentation` enthält editierbare Betriebsdokumentation für Organisationen, Standorte, Abteilungen und Systeme.
- `registry_documents` und `registry_document_versions` verwalten hochgeladene Dateien und deren unveränderliche Versionen für dieselben vier Registry-Kontexte.

Stammdaten wie Hersteller, Hostname oder Adresse verbleiben ausschließlich in den jeweiligen Registry-Modellen und werden im Dokumentationsbereich nur lesend angezeigt.

## Strukturierte Dokumentation

Ein Dokumentationseintrag besitzt Dokumentationstyp, Sektion, Titel, optionalen Inhalt, strukturierte JSON-Daten, Sichtbarkeit sowie Ersteller und letzten Bearbeiter. Pro Entität, Dokumentationstyp und Sektion existiert höchstens ein Eintrag.

Die Oberfläche verwendet die bestehenden Workspace-Cards und ein sektionenweises Slide-over. Leere Sektionen bleiben kompakt; gepflegte Inhalte und ihr Status werden hervorgehoben. Die Vollständigkeit wird ausschließlich aus explizit definierten Pflichtfeldern berechnet. Gibt es keine Pflichtfelder, zeigt die UI keinen erfundenen Prozentwert.

## Dokumente und Versionen

Ein Registry-Dokument enthält fachliche Metadaten wie Titel, Beschreibung, Kategorie, Sichtbarkeit, Gültigkeitszeitraum, Vertragsreferenz und Schlagwörter. Es gehört polymorph genau einer Organisation, einem Standort, einer Abteilung oder einem System.

Jeder Upload erzeugt eine separate Version mit fortlaufender Versionsnummer, Original- und Storage-Dateiname, erkanntem MIME-Typ, Erweiterung, Größe, SHA-256, Uploader, Uploadzeit, Änderungshinweis, Malware- und Freigabestatus. `current_version_id` verweist über einen zusammengesetzten Fremdschlüssel ausschließlich auf die zuletzt freigegebene Version desselben Dokuments. Eine neue Version verändert die fachlichen Dokumentmetadaten nicht, ersetzt die aktuelle Version erst nach Freigabe und löscht ältere Versionen oder Dateien nicht.

Dokumente werden archiviert statt physisch gelöscht. Archivierte Dokumente sind weder bearbeitbar noch herunterladbar und akzeptieren keine neuen Versionen. Auch archivierte Registry-Kontexte akzeptieren keine neuen Dokumente.

## Kategorien und Gültigkeit

Die zentrale Kategorie-Taxonomie umfasst Verträge, Lizenzen, Herstellerdokumentation, Handbücher, Installations- und Netzwerkunterlagen, Firewallfreigaben, Betriebs- und Notfallhandbücher, Backup-/Recovery-Dokumentation, SOPs, Zertifikate, Datenschutz, Informationssicherheit, ISO-Nachweise, Schnittstellendokumentation, Testprotokolle und Sonstiges.

Jede Kategorie ist einer konfigurierbaren Mindestaufbewahrung zugeordnet. Technische Unterlagen verwenden standardmäßig 1.095 Tage, Betriebs-/Notfall-/SOP-/Testunterlagen 2.555 Tage und Verträge, Lizenzen sowie Compliance-Nachweise 3.650 Tage. Die Werte werden über `REGISTRY_DOCUMENT_RETENTION_SHORT_DAYS`, `REGISTRY_DOCUMENT_RETENTION_STANDARD_DAYS` und `REGISTRY_DOCUMENT_RETENTION_LONG_DAYS` gesetzt. Beim Upload werden Frist und angewendete Dauer am Dokument gespeichert. Ein späterer Kategorienwechsel darf die bestehende Frist nicht verkürzen. Model-Löschungen vor Fristende werden serverseitig abgewiesen; Archivierung bleibt möglich.

Aus `valid_from`, `valid_until`, Archivstatus und dem konfigurierten Warnzeitraum wird ein Status abgeleitet: noch nicht gültig, gültig, läuft bald ab, abgelaufen oder archiviert. Der Warnzeitraum beträgt standardmäßig 60 Tage und kann über `REGISTRY_DOCUMENT_EXPIRY_WARNING_DAYS` angepasst werden.

## Suche und Filter

Suche, Filter und Pagination werden serverseitig ausgeführt. Durchsucht werden Titel, Beschreibung, Kategorie, Vertragsreferenz, Schlagwörter und Originaldateiname. Filter stehen für Kategorie, Dateityp, Dokumentstatus, Gültigkeit, Uploader, Uploadzeitraum und Malware-Status zur Verfügung. Dokumente fremder Registry-Kontexte werden nicht in die jeweilige Workspace-Liste gemischt.

PDF-Inhalte werden nicht automatisch extrahiert oder indexiert. Im Projekt existiert derzeit keine freigegebene OCR- oder Volltext-Extraktionsinfrastruktur.

## Upload und Dateiprüfung

Das Standardlimit beträgt 25 MiB (`REGISTRY_DOCUMENT_MAX_UPLOAD_KB=25600`). Serverseitig werden Erweiterung, durch PHP erkannter MIME-Typ und Dateisignatur gemeinsam geprüft.

| Erweiterung | Erlaubter Inhalt |
| --- | --- |
| `pdf` | PDF |
| `png` | PNG-Bild |
| `jpg`, `jpeg` | JPEG-Bild |
| `docx` | OOXML-Textdokument mit passender interner Struktur |
| `xlsx` | OOXML-Tabelle mit passender interner Struktur |
| `txt` | UTF-8-Text ohne Nullbytes |
| `zip` | ZIP-Datei; wird weder geöffnet noch entpackt |

HTML, SVG, ausführbare Dateien, Skripte und Office-Makroformate wie `docm` oder `xlsm` sind nicht freigegeben. Der Originalname wird bereinigt und nur als Metadatum gespeichert. Der tatsächliche Storage-Name ist eine serverseitig erzeugte UUID; der Pfad wird nicht aus Benutzereingaben gebildet.

SHA-256 dient als Integritätsmerkmal und zur Duplikaterkennung. Derselbe Inhalt kann nicht doppelt demselben Registry-Kontext beziehungsweise demselben Dokument als Version zugeordnet werden.

## Malware-Scan

`MalwareScanner` ist die austauschbare Scanner-Schnittstelle. Unterstützte Zustände sind `pending`, `clean`, `infected`, `failed` und `unavailable`. Nur `clean` darf heruntergeladen oder als PDF angezeigt werden; alle anderen Zustände werden serverseitig gesperrt.

Bei `REGISTRY_DOCUMENT_MALWARE_SCANNER_ENABLED=true` verwendet die Anwendung `ClamAvMalwareScanner` und überträgt Uploads per ClamD-`INSTREAM` an den internen Dienst `clamav:3310`. Port 3310 wird nicht auf dem Host veröffentlicht. Ist ClamAV deaktiviert, bindet die Anwendung `UnavailableMalwareScanner`; ist ClamD nicht erreichbar, liefert der Adapter ebenfalls `unavailable`. In beiden Fällen bleibt die Datei privat und gesperrt.

Der Scheduler führt stündlich `registry-documents:rescan --limit=250` aus. Das Kommando kann zusätzlich manuell ausgeführt werden und prüft `pending`, `failed` und `unavailable` erneut. Ein erfolgreicher Rescan setzt den Status auf `clean`; abrufbar wird die Version zusätzlich erst nach fachlicher Freigabe. `infected` bleibt terminal gesperrt. Ergebnisse werden als `document.scan_rescanned` ohne rohe Scanner-Ausgabe am Registry-Kontext auditiert.

## Freigabe und Vier-Augen-Prinzip

Neue Versionen beginnen als `draft`. Berechtigte Versionsverwalter reichen sie als `pending_review` ein. Eine zweite Person mit `documents.approve` kann die Version freigeben oder mit Begründung ablehnen. Ausschließlich Benutzer mit der technischen Rolle `system-administrator` dürfen als dokumentierter Override auch eine selbst hochgeladene Version freigeben. Dieser Override umgeht weder den Status `pending_review` noch die erforderliche saubere Malware-Prüfung und wird im Audit-Ereignis `document.approved` gekennzeichnet. Eigene Uploads abzulehnen bleibt auch für Systemadministratoren gesperrt. Die Regeln werden zusätzlich zur Policy unmittelbar im schreibenden Controller und innerhalb der gesperrten Datenbanktransaktion geprüft.

Nur `approved` und zugleich malwareseitig `clean` bewertete Versionen sind als Download oder PDF-Vorschau abrufbar. Bei einer neuen Version bleibt die bisherige freigegebene Version aktuell, bis die Nachfolgeversion genehmigt wurde. Der Dokument-Slideover zeigt Freigabestatus, Prüfer, Prüfnotiz und die verfügbaren Aktionen. Benutzer ohne Freigaberecht sehen ausdrücklich, dass die Version auf eine zweite Person wartet.

## Ablaufhinweise

Der Scheduler führt täglich um 07:00 Uhr `registry-documents:notify-expiry` aus. Berücksichtigt werden aktive, nicht archivierte Dokumente, deren `valid_until` innerhalb des mit `REGISTRY_DOCUMENT_EXPIRY_WARNING_DAYS` konfigurierten Zeitraums liegt, sowie bereits abgelaufene Dokumente. Aktive Benutzer mit `documents.view` erhalten pro Dokument und Fälligkeitsdatum genau einen persistenten In-App-Hinweis. Wird das Gültigkeitsdatum geändert, kann für die neue Frist erneut ein Hinweis entstehen.

Das Dashboard-Widget **Ablaufende Dokumente** zeigt berechtigten Benutzern die aktuellen Fristen und trennt abgelaufene von bald ablaufenden Dokumenten. Das Öffnen eines ungelesenen Hinweises markiert ihn als gelesen und führt zum Dokument. Es wird kein E-Mail-Versand erzwungen.

Das Kommando kann bei Bedarf manuell ausgeführt werden:

```bash
docker compose exec app php artisan registry-documents:notify-expiry
```

## Berechtigungen und Zugriff

Die Dokumentenfunktionen verwenden das bestehende RBAC und zusätzlich die Policy des zugeordneten Registry-Kontexts. Es gibt kein paralleles Rollenmodell.

- `documents.view`: Dokumentmetadaten und PDF-Vorschau
- `documents.upload`: neues Dokument hochladen
- `documents.update`: Metadaten bearbeiten
- `documents.archive`: archivieren und wiederherstellen
- `documents.download`: saubere Version herunterladen
- `documents.manage_versions`: neue Version anlegen
- `documents.approve`: fremde Versionen freigeben oder ablehnen; die Rolle `system-administrator` darf zusätzlich eigene Versionen freigeben

Die Datei liegt auf dem nicht öffentlich ausgelieferten Disk `registry_documents` unter `storage/app/private/registry-documents`. Downloads und Vorschauen erfolgen ausschließlich über autorisierte Controller-Endpunkte. Downloads verwenden `Content-Disposition: attachment` und `X-Content-Type-Options: nosniff`. Die PDF-Vorschau verwendet `inline`, private No-Store-Header, `SAMEORIGIN` und eine restriktive Content-Security-Policy.

## Audit und Historie

Dokumentaktionen verwenden `RegistryAudit` und dieselbe kryptografisch verkettete Ereignisquelle `security_events` wie die übrige Registry. Upload, neue Version, Einreichung, Freigabe, Ablehnung, Metadatenänderung, Archivierung, Wiederherstellung sowie fehlgeschlagene oder infizierte Scans werden am zugeordneten Registry-Kontext protokolliert und erscheinen in dessen bestehender Historie.

Dateiinhalte, interne Storage-Pfade und rohe Scanner-Ausgaben werden nicht in Audit-Metadaten übernommen. Downloads und Vorschauzugriffe erzeugen derzeit kein eigenes Audit-Ereignis.

## Bekannte Einschränkungen

- Es gibt keine automatische Quarantäne oder physische Löschung infizierter Dateien und keinen automatischen Löschlauf nach Ablauf der Mindestaufbewahrung.
- Vorschau ist ausschließlich für saubere PDF-Versionen verfügbar; Office-, Bild-, Text- und ZIP-Dateien werden nicht inline dargestellt.
- ZIP-Dateien werden nicht entpackt oder inhaltlich analysiert.
- `restricted` ist als Sichtbarkeit gespeichert; eine über die bestehenden Kontext- und Dokumentrechte hinausgehende Feld- oder Mandantentrennung ist nicht implementiert.
- Audit protokolliert fachliche Änderungen, derzeit aber keine einzelnen Dateiabrufe.

## Backup, Restore und Aufbewahrung

PostgreSQL und `storage/app/private/registry-documents` bilden gemeinsam den vollständigen Dokumentbestand. Backup und Restore müssen beide Teile aus demselben konsistenten Sicherungszeitpunkt umfassen. Ein Datenbank-Restore ohne die passenden Dateien erzeugt nicht abrufbare Versionen; ein Storage-Restore ohne die passenden Metadaten erzeugt verwaiste Dateien.

Nach einem Restore sind mindestens Versionsanzahl, `current_version_id`, Dateiexistenz, Größe, SHA-256, Freigabe- und Aufbewahrungsstatus stichprobenartig zu prüfen. Archivierte Dokumente und alte Versionen werden genauso wiederhergestellt wie aktive Daten. Ein späterer physischer Löschprozess muss Datenbank und privaten Storage konsistent behandeln und darf erst nach `retention_until` arbeiten.
