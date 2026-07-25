# Access Control

## Grundmodell

RBAC mit serverseitiger Durchsetzung. Rollen sind Ausgangspunkt; besonders kritische Rechte können granular vergeben werden.

## Vorgeschlagene Rollen

- System Administrator
- Registry Administrator
- Editor
- Auditor/Read Only
- Document Manager
- External Support Read Only

## Beispielberechtigungen

- assets.view/create/update/archive
- endpoints.view/create/update
- connections.view/create/update/delete
- documents.view/upload/download/delete
- topology.view/export
- audit.view/export
- users.manage
- roles.manage
- settings.manage
- imports.execute
- exports.execute

## Regeln

- Default Deny
- Least Privilege
- keine Autorisierung nur im Frontend
- Rollenänderungen auditieren
- eigenes Konto nicht unkontrolliert zum letzten Administrator degradieren
- sensible Exporte gesondert berechtigen
- regelmäßiger Access Review als Organisationsprozess
