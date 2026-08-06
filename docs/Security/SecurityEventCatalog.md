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
| Diagnosetest abgeschlossen | Actor, Test-ID, Knoten, System, Testtyp, Status | Audit |
| DICOM Storage abgeschlossen | Actor, Test-ID, Knoten, SOP Class, SOP Instance UID, Status | Audit |
| DICOM-Datei analysiert | Actor, Dateigröße, Ergebnis; kein Dateiname und keine Patientendaten | Audit |
| Diagnoseergebnis exportiert | Actor, Test-ID, Format | Audit |
| Dokument hoch-/heruntergeladen | Actor, Dokument-ID, Ergebnis | Audit |
| Upload abgewiesen | Actor, Grundcode, Dateigröße, MIME | Security Event |
| Konfiguration geändert | Actor, Schlüssel, bereinigtes vorher/nachher | Audit |
| Backup/Restore ausgelöst | Actor/System, Ergebnis, Referenz | Audit |
| Rate Limit ausgelöst | Route-Klasse, Quell-IP, Zeitpunkt | Security Log |
| Discovery-Lauf gestartet/abgeschlossen (`discovery.run.started`/`.completed`) | Actor, Lauf-ID, Zielbereich, Anzahl IPs, Ergebnisstatus | Audit |
| Discovery-Lauf abgebrochen (`discovery.run.cancelled`) | Actor, Lauf-ID, verarbeitete/gesamte IPs | Audit |
| Discovery-Fund bestätigt/ignoriert (`discovery.finding.confirmed`/`.ignored`) | Actor, Host-ID, IP-Adresse, Confidence-Score | Audit |
| Discovery-System übernommen (`discovery.system.promoted`) | Actor, System-ID, Discovery-Lauf-ID, Quell-IP | Audit |
| Freigegebener Netzbereich geändert (`discovery.allowed_network.*`) | Actor, CIDR, aktiv/inaktiv | Audit |

Keine Passwörter, Tokens, Cookies, vollständigen Request Bodies oder Patientendaten protokollieren.
