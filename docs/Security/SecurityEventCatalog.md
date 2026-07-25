# Security Event Catalog

| Event | Mindestdaten | Audit/Log |
|---|---|---|
| Login erfolgreich | Actor, Zeitpunkt, Quell-IP, Session-Korrelation | Security Event |
| Login fehlgeschlagen | eingegebener Identifier nur bereinigt, Zeitpunkt, Quell-IP | Security Log |
| Logout | Actor, Zeitpunkt | Security Event |
| Passwort geändert/zurückgesetzt | Actor, Zielkonto, Zeitpunkt | Audit |
| Rolle/Berechtigung geändert | Actor, Zielkonto, vorher/nachher | Audit |
| Benutzer gesperrt/deaktiviert | Actor, Zielkonto, Grundcode | Audit |
| Export gestartet/abgeschlossen | Actor, Scope, Datensatzanzahl, Ergebnis | Audit |
| Dokument hoch-/heruntergeladen | Actor, Dokument-ID, Ergebnis | Audit |
| Upload abgewiesen | Actor, Grundcode, Dateigröße, MIME | Security Event |
| Konfiguration geändert | Actor, Schlüssel, bereinigtes vorher/nachher | Audit |
| Backup/Restore ausgelöst | Actor/System, Ergebnis, Referenz | Audit |
| Rate Limit ausgelöst | Route-Klasse, Quell-IP, Zeitpunkt | Security Log |

Keine Passwörter, Tokens, Cookies, vollständigen Request Bodies oder Patientendaten protokollieren.
