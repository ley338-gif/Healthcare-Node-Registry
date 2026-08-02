# Benutzerverwaltung

Die Benutzerverwaltung ist ein Unterbereich der bestehenden Einstellungen und baut auf den vorhandenen Modellen `User`, `Role` und `Permission`, den Laravel-Policies sowie der Registry-Audit-Infrastruktur auf.

## Funktionen

- Benutzer suchen sowie nach Status und Rolle filtern
- lokale Benutzer anlegen und bearbeiten
- Konten aktivieren und deaktivieren
- starke Initialpasswoerter und administrative Passwortwechsel
- vorhandene Sitzungen bei Deaktivierung oder Passwortwechsel widerrufen
- Rollen anlegen, bearbeiten und unbenutzte Rollen loeschen
- vorhandene Berechtigungen Rollen zuweisen
- Benutzer- und Rollenaktionen auditiert in der Ereignisgruppe Benutzer erfassen

## Berechtigungen

- `users.manage`: Benutzer anzeigen und verwalten
- `roles.manage`: Rollen verwalten und Benutzern Rollen zuweisen
- `settings.manage`: Zugriff auf den Einstellungsbereich ohne automatische Benutzer- oder Rollenrechte

Die Systemadministrator-Rolle darf nicht geaendert oder geloescht werden. Der letzte aktive Systemadministrator bleibt durch serverseitige Regeln erhalten.
