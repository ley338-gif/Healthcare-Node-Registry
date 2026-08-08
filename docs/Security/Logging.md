# Logging and Audit

## Technische Logs

Zweck: Betrieb und Fehleranalyse.

Nicht loggen:

- Passwörter, Tokens, Sessionwerte
- vollständige Request Bodies
- hochgeladene Dokumentinhalte
- Patientendaten
- Secrets oder Connection Strings

## Audit-Log

Zweck: Nachvollziehbarkeit fachlich und sicherheitsrelevanter Aktionen.

Mindestens:

- Nutzer/technischer Akteur
- Aktion
- betroffenes Objekt
- Zeitpunkt
- Ergebnis
- relevante Vorher-/Nachher-Werte, redigiert
- Quell-IP, soweit zulässig und erforderlich
- Korrelations-ID

## Integrität

Audit-Events sind append-only und über kanonische SHA-256-Hashes mit ihrem Vorgänger
verkettet. Der gespeicherte Kettenkopf erkennt zusätzlich die Löschung abschließender
Einträge. Betreiber prüfen die Kette regelmäßig und nach Restore-Vorgängen:

```bash
php artisan registry:audit-verify
```

Die Standardaufbewahrung beträgt sieben Jahre und ist über
`AUDIT_RETENTION_DAYS` an die verbindliche Vorgabe des Betreibers anzupassen.
`registry:audit-prune` entfernt ausschließlich abgelaufene älteste Einträge und
bewahrt deren letzten Hash als Checkpoint. Der Scheduler führt diesen Prozess täglich
aus. Eine nachträgliche Verkürzung der Konfiguration verkürzt bereits persistierte
Einzelfristen nicht.

Die interne Hash-Chain ist kein Ersatz für eine externe signierte Verankerung gegen
Angreifer mit vollständigem Schreibzugriff auf Daten und Kettenzustand. Details und
Betriebsgrenzen beschreibt ADR-0015.

## Zeit

Systeme benötigen zuverlässige Zeitsynchronisation. Anzeige erfolgt lokal, Speicherung in UTC.
