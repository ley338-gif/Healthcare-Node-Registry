# Datenwörterbuch

## Zentrale Felder

| Feld | Bedeutung | Regel |
|---|---|---|
| public_id | stabile, nicht sequenziell erratbare Objektkennung | unique, unveränderlich |
| documentation_state | Qualität/Freigabe der Dokumentation | kontrollierte Taxonomie |
| operational_intent | dokumentierte Soll-Nutzung | kein Live-Status |
| information_source | Herkunft der Information | Pflicht bei fachlich kritischen Daten |
| verified_at | letzter fachlicher Prüfzeitpunkt | UTC |
| lifecycle_status | Lebenszyklus eines Assets | geplant, produktiv, außer Betrieb, archiviert |
| scan_status | Ergebnis der Upload-Prüfung | pending, clean, rejected, unavailable |
| correlation_id | verbindet mehrere Audit Events eines Use Cases | UUID |
| storage_key | interner zufälliger Ablageschlüssel | keine Benutzereingabe |
| sha256 | Integritätsnachweis eines Dokuments | 64 Hex-Zeichen |

## Personenbezogene Kontaktdaten

Kontaktdaten werden minimiert. Teams, Funktionspostfächer und Herstellerkontakte sind privaten Kontaktdaten vorzuziehen.
