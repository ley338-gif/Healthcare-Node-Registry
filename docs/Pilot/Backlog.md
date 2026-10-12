# Pilot-Backlog

## P0 – pilot-blockierend

| Paket | Abhängigkeit | Aufwand | Risiko | Abnahmekriterium | Stand |
|---|---|---:|---|---|---|
| P0-01 unveränderlicher Release und Rollback | grüner Anwendungs-Baseline-Build | mittel | hoch | App/Web ohne Quellcode-Bind-Mounts; Version/SHA; Install-, Upgrade- und Rollbackverfahren | umgesetzt |
| P0-02 Supply-Chain-Gates | P0-01-Images | mittel | hoch | History-Secret-Scan; vier Runtime-Scans ohne behebbare HIGH/CRITICAL; vier SBOMs; unveränderliche Digests | umgesetzt; Zeitplanlauf-Regression vom 31.08.–12.10.2026 behoben, gültig nur bei grünem letzten Zeitplanlauf |
| P0-03 Betriebs-, Rollen- und Go/No-Go-Modell | P0-01 und P0-02 | klein-mittel | hoch | RPO/RTO, Restore, Monitoring, Alarmwege, Rollen/MFA/Break Glass sowie Entscheidungsmatrix dokumentiert | umgesetzt; formale Freigabe offen |
| P0-04 Zielhost- und Healthcare-E2E-Abnahme | freigegebener Zielhost, PACS, RIS/MWL, Modalität/Test-SCU und benannte Owner | groß / extern | hoch | alle offenen P0-Zeilen der [Entscheidungsmatrix](README.md) mit synthetischen Daten erfüllt | extern blockiert |

P0-04 darf erst ausgeführt werden, wenn Ziele und Testfenster schriftlich freigegeben sind. Das Repository enthält keine solche Freigabe; deshalb werden keine aktiven Netzwerk- oder DICOM-Tests gestartet.

## P1 – pilot-relevant, nicht allein blockierend

- fehlgeschlagene wöchentliche Supply-Chain-Läufe aktiv an Security melden, statt sie nur in GitHub Actions anzuzeigen;
- Linting-Entwicklungswerkzeuge auf Major-Versionen ohne bekannte Advisories anheben;
- Image-Signierung und überprüfbare Build-Provenance ergänzen;
- organisationsspezifische zentrale Log-/Alert-Integration automatisieren;
- phishing-resistente MFA-Option neben TOTP bewerten;
- Bus-Faktor durch zweite technisch eingewiesene Person und Wiederanlaufübung reduzieren.

P1 wird zum P0, wenn lokale Security-, Datenschutz- oder Betriebsverantwortliche dies aufgrund der Zielumgebung verlangen.

## P2 – spätere Produktentwicklung

- zusätzliche Discovery-Protokolle, passives Monitoring oder SNMP;
- weitere DICOM-SOP-Klassen und nicht für den Pilot vereinbarte Integrationen;
- funktionale Erweiterungen ohne direkten Bezug zu einem fehlgeschlagenen Pilot-Gate.

Bis zur Erfüllung von P0-04 werden keine neuen Produktfunktionen aus P2 begonnen.
