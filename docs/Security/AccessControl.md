# Access Control

## Grundmodell

RBAC mit serverseitiger Durchsetzung, Default Deny und optionalen Ressourcen-Scopes.

## Rollenmatrix

| Fähigkeit | System Admin | Registry Admin | Editor | Document Manager | Auditor | External Support |
|---|---:|---:|---:|---:|---:|---:|
| Systeme lesen | ja | ja | ja | ja | ja | eingeschränkt |
| Systeme ändern | ja | ja | ja | nein | nein | nein |
| Endpoints/Connections ändern | ja | ja | ja | nein | nein | nein |
| Dokumente hochladen | ja | ja | optional | ja | nein | nein |
| Dokumente herunterladen | ja | ja | nach Recht | ja | nach Recht | eingeschränkt |
| Topologie anzeigen | ja | ja | ja | ja | ja | eingeschränkt |
| Export ausführen | ja | gesondert | nein | nein | gesondert | nein |
| DICOM Storage-Test | ja | gesondert | nein | nein | nein | nein |
| DICOM-Datei analysieren | ja | gesondert | nein | nein | nein | nein |
| Audit anzeigen | ja | ja | nein | nein | ja | nein |
| Benutzer verwalten | ja | nein | nein | nein | nein | nein |
| Rollen verwalten | ja | nein | nein | nein | nein | nein |
| Systemeinstellungen | ja | nein | nein | nein | nein | nein |

## Scopes

- installationsweit
- Organisation
- Standort
- optional Abteilung

Echte technische Tenant-Isolation ist nicht Bestandteil des MVP.

## Regeln

- keine Autorisierung nur im Frontend
- jede schreibende Aktion besitzt Policy-/Gate-Tests
- Rollenänderungen werden auditiert
- letzter aktiver Systemadministrator darf nicht unkontrolliert entfernt werden
- sensible Exporte und Downloads werden gesondert berechtigt
- datenverändernde DICOM-Tests verwenden strengere Rechte als reine Lese-/Verbindungstests
- External Support ist standardmäßig read-only, zeitlich begrenzbar und ohne Export
- regelmäßiger Access Review ist Organisationsprozess
