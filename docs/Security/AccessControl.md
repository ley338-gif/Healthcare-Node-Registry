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

## Verwaltung

Die vorhandenen Rollen und Berechtigungen werden im Bereich `Einstellungen > Benutzerverwaltung` gepflegt. Benutzer mit `users.manage` koennen Konten anlegen, bearbeiten, deaktivieren und Passwoerter setzen. Die Zuweisung und Pflege von Rollen erfordert zusaetzlich `roles.manage`.

Die Rolle `system-administrator` ist technisch geschuetzt. Das eigene Konto kann nicht deaktiviert werden; ausserdem verhindert die Anwendung, dass der letzte aktive Systemadministrator deaktiviert oder aus dieser Rolle entfernt wird. Alle Aenderungen verwenden die vorhandenen Policies und die zentrale Audit-Infrastruktur.

Diagnosen werden einzeln mit `diagnostics.echo`, `diagnostics.worklist`, `diagnostics.query`, `diagnostics.store`, `diagnostics.move`, `diagnostics.get`, `diagnostics.mpps`, `diagnostics.storage_commitment` und `diagnostics.capability_matrix` freigegeben. Die Standardrolle `PACS-Administrator` enthaelt alle Diagnoserechte; `Nur Lesen` enthaelt keines davon.

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
