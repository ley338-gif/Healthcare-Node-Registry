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

## Discovery

| Feld | Bedeutung | Regel |
|---|---|---|
| ip_range | CIDR- oder Start-/End-IP-Zielbereich eines Laufs | maximal `config('discovery.max_range_size')` Adressen, muss innerhalb eines aktiven `discovery_allowed_networks`-Eintrags liegen |
| confidence_score | Konfidenzstufe eines Fundes | `unknown, very_low, low, medium, high, very_high`, rein heuristisch |
| confidence_percentage | Numerischer Heuristikwert | 0–100, keine wissenschaftliche Genauigkeit |
| suggested_system_type | Vorgeschlagener Systemtyp | immer als Vorschlag gekennzeichnet, nie als Tatsache |
| discovery_classification_evidence.weight | Gewicht eines einzelnen Klassifizierungshinweises | positiver Ganzzahlwert, Summe ergibt confidence_percentage |
| discovered_hosts.status | Review-Status eines Fundes | `discovered, reviewing, confirmed, ignored` |
| discovery_runs.status | Lebenszyklus eines Laufs | `draft, pending, running, cancelling, completed, partially_failed, cancelled, failed` |
| dicom_discovery_results.association_successful / echo_successful | Ergebnis eines einzelnen C-ECHO-Versuchs | Association-Erfolg impliziert nicht zwingend C-ECHO-Erfolg |
| discovery_allowed_networks.cidr | Von einem Administrator freigegebener Scanbereich | Voraussetzung für jeden Discovery-Lauf, Standard: RFC1918 |
| dicom_connections.evidence_status | Nachweisstärke einer Topologie-Verbindung, unabhängig vom Betriebsstatus | `confirmed, technically_tested, suspected, manually_documented, failed_last_test`; nie automatisch auf `confirmed` gesetzt |

Große technische Rohausgaben (z. B. DCMTK-Konsolenausgabe) werden in `dicom_discovery_results.raw_response` als JSON mit begrenzter Länge gespeichert, nicht in einer separaten Volltextspalte.
