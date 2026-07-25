# Implementierungsbericht 0.1.1

## Datenbank

Neue Tabelle `security_events`.

## Sicherheit

Passwörter werden nicht geloggt oder als CLI-Argument akzeptiert. Benutzer, Rolle und Security Event werden in einer Transaktion angelegt.

## Tests

- erstes Administratorkonto
- Rollenvergabe
- Security Event
- Verweigerung eines zweiten Initialkontos
- Doctor-Command

## Manuell offen

- Clean Install auf separater Zielumgebung
- vollständiger Restore-Test
