# Healthcare-End-to-End-Abnahme

## Sicherheitsgrenzen

Die Abnahme verwendet ausschließlich synthetische Identitäten, UIDs und Objekte. Aktive Netzwerk- oder DICOM-Tests dürfen nur gegen schriftlich freigegebene lokale Pilot-/Testziele erfolgen. Produktiv- und Patientendaten sind ausgeschlossen. C-STORE, MPPS, C-MOVE/C-GET und Storage Commitment benötigen zusätzlich ein vereinbartes Testfenster, zuständige Zielsystembetreuung und einen Cleanup-Nachweis.

## Zielmatrix

| Zielklasse | Mindestens benötigte Testinstanz | Zu erfassende Varianten |
|---|---|---|
| PACS/VNA | Query-/Storage-SCP in freigegebener Testumgebung | Hersteller/Version, Called/Calling AE, TLS, SOP Classes, Transfer Syntaxes, Timeout, Firewallpfad |
| RIS/MWL | MWL-SCP mit synthetischem Auftrag | Hersteller/Version, Scheduled Station AE, Modalität, Zeichensatz, Datums-/Zeitzone, Nulltreffer |
| Modalität oder Test-SCU/SCP | kontrollierter DICOM-Endpunkt | AE-Titel, IP/Port, C-ECHO, Storage-SOP-Klasse, TLS und Callback-Erreichbarkeit |

Herstellerunterschiede werden als separate Zeile ausgeführt; ein Erfolg mit einem Simulator ersetzt keinen vereinbarten Herstellernachweis.

## Verbindliche Szenarien

| ID | Ablauf und Akzeptanz | Nachweis | Verantwortlich | Status |
|---|---|---|---|---|
| HC-01 | freigegebenen kleinen CIDR-Bereich entdecken; Ziel, Ports und C-ECHO werden korrekt klassifiziert; keine Ziele außerhalb der Allowlist | Discovery-Lauf-ID, Netzfreigabe, Audit | Network / Healthcare Lead | offen |
| HC-02 | Fund prüfen, False Positive markieren beziehungsweise Kandidat freigeben; Reviewentscheidung ist nachvollziehbar | Review-Screenshot/Export und Audit-ID | Registry Admin | offen |
| HC-03 | freigegebenen Kandidaten in Registry übernehmen; Duplikate werden erkannt; System, Knoten und Verantwortlichkeit sind korrekt | öffentliche Registry-IDs und Audit-ID | Registry Admin | offen |
| HC-04 | DICOM-Verbindung anlegen/prüfen; Quell-/Zielrolle, AE-Titel, Host, Port und Evidence-Status stimmen | Verbindungs-ID und Audit-ID | PACS Admin | offen |
| HC-05 | C-ECHO über vereinbarten Klartext- oder TLS-Pfad erfolgreich; falscher AE-Titel, gesperrter Port und Timeout werden verständlich klassifiziert | Diagnose-Run-IDs je Positiv-/Negativfall | PACS Admin / Network | offen |
| HC-06 | MWL C-FIND liefert den synthetischen Auftrag; Nulltreffer ist technischer Erfolg; Patientenschlüssel werden im Verlauf maskiert | Diagnose-Run-ID und synthetische Auftrags-ID | RIS / PACS Admin | offen |
| HC-07 | PACS Study-Root C-FIND findet ausschließlich die synthetische Studie beziehungsweise liefert kontrolliert null Treffer | Diagnose-Run-ID und synthetische Study UID | PACS Admin | offen |
| HC-08 | Capability Matrix dokumentiert akzeptierte/abgelehnte SOP-Class-/Transfer-Syntax-Kontexte ohne Storage zu behaupten | Diagnose-Run-ID und Export | PACS Admin | offen |
| HC-09 | kontrolliertes C-STORE einer freigegebenen synthetischen SOP Class wird bestätigt und anschließend im Zielsystem gelöscht oder nachweislich quarantänisiert | Diagnose-Run-ID, Zielsystembeleg, Cleanup-ID | PACS Admin / Target Owner | offen |
| HC-10 | relevante Aktionen, Exporte, Fehler und Rechteverweigerungen erscheinen ohne Secrets/Patientendaten im Audit; unberechtigte Rolle bleibt gesperrt | Audit-Export und Rollenprotokoll | Auditor / Security | offen |
| HC-11 | falls Pilot-Scope: Storage Commitment empfängt N-EVENT-REPORT über begrenzte Callback-Adresse und Firewallregel; synthetisches Objekt wird bereinigt | Diagnose-Run-ID, Firewallbeleg, Cleanup-ID | PACS Admin / Network | offen |
| HC-12 | Vertreter von Registry Admin, PACS Admin, Auditor und Nur Lesen erledigen ihre Kernaufgaben; kein P0-UX-Fehler bleibt offen | signiertes Szenarioprotokoll | Product Owner | offen |

## Ausführungsprotokoll je Szenario

```text
Szenario-ID / Datum / Tester:
RC-Version und Git-SHA:
Zielklasse, Hersteller, Version:
Freigabe-/Change-ID und Testfenster:
Quell- und Ziel-AE (keine Secrets):
TLS-Profilkennung, Protokoll und Zertifikatsstatus:
Quell-/Zieladresse und Port (geschütztes Protokoll):
Timeout-/Firewallvariante:
synthetische Test-ID / UID:
erwartetes Ergebnis:
tatsächliches Ergebnis:
Diagnose-Run-/Audit-/Cleanup-ID:
Abweichung, Owner, Frist:
Status und Freigabe:
```

Rohdaten, Dumps und Screenshots werden vor Ablage auf Patientendaten, Secrets und unnötige Netzdetails geprüft. Lokale temporäre DICOM-Dateien müssen automatisch entfernt sein; Zielsystemobjekte werden durch den benannten Target Owner gemäß Testfenster bereinigt.
