# Implementierungsbericht – Organisationsstruktur

## Scope

Organisationen, Standorte, Abteilungen, Suche, Bearbeitung, Archivierung, RBAC, Audit und Dashboard.

## Datenbank

Neue Tabellen: `organizations`, `sites`, `departments`.

## Sicherheit

Serverseitige Policies, keine Hard Deletes, Audit Events und ausschließlich synthetische Testdaten.

## Rollback

```bash
php artisan migrate:rollback --step=1
```
