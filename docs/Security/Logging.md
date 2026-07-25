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

Audit-Events sind append-only. Löschung und Aufbewahrung erfolgen nur über einen dokumentierten, berechtigten Prozess.

## Zeit

Systeme benötigen zuverlässige Zeitsynchronisation. Anzeige erfolgt lokal, Speicherung in UTC.
