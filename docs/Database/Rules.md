# Database Rules

## PostgreSQL

PostgreSQL ist die primäre Datenbank. Produktive Versionen werden explizit unterstützt und getestet.

## Regeln

- Fremdschlüssel und Constraints bevorzugen.
- `jsonb` nur für protokollspezifische Erweiterungen, nicht als Ersatz für ein Datenmodell.
- IP-Adressen als `inet`, sofern ORM und Portabilität dies sauber unterstützen.
- eindeutige Constraints werden fachlich und mandantenbezogen definiert.
- Migrationen sind vorwärtsgerichtet; veröffentlichte Migrationen werden nicht verändert.
- destruktive Änderungen benötigen Backup-, Migrations- und Rollbackplan.
- Seeds enthalten ausschließlich synthetische Daten.
- Zeitstempel in UTC.
- Indizes werden anhand realer Abfragen und Messungen gesetzt.
- Auditdaten werden nicht durch normale CRUD-Funktionen überschrieben.

## Migrationsreview

Jede Migration prüft:

- Locking und Laufzeit
- Default-Werte
- Nullability
- bestehende Daten
- Indexaufbau
- Rollbackfähigkeit
- Backupbedarf
