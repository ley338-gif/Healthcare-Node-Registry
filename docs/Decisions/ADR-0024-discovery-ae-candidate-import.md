---
title: ADR-0024 – Import von Discovery-AE-Titel-Kandidaten
description: Kontrollierte CSV- und XLSX-Vorschau für AE-Titel im Discovery-Wizard.
document_type: ADR
chapter: ADR-0024
status: accepted
version: 1.0
last_updated: 2026-08-11
---

# ADR-0024: Import von Discovery-AE-Titel-Kandidaten

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-08-11
- Verantwortliche Rolle: Backend/Architektur
- Betroffene Produktversion: Unreleased
- Ersetzt: keine
- Ersetzt durch: —

## Kontext

Discovery-Läufe akzeptieren eine begrenzte Liste von Calling- und Called-AE-Titel-Kandidaten. Größere, bereits außerhalb der Registry gepflegte Kandidatenlisten mussten bisher einzeln in den Wizard übertragen werden. Gleichzeitig darf ein Import weder ungeprüft einen Scan starten noch die harte Begrenzung der AE-Versuche umgehen. ADR-0011 legt außerdem weiterhin genau einen Zielbereich pro Discovery-Lauf fest.

## Entscheidung

Der Discovery-Wizard erhält eine serverseitige Vorschau für CSV- und XLSX-Dateien mit `schema_version=1`. `ae_title` ist verpflichtend, `role` ist optional und verwendet standardmäßig `called`. Die Vorschau normalisiert AE-Titel, meldet Fehler mit Zeilennummern, entfernt Duplikate nachvollziehbar und markiert übernommene Werte mit der bestehenden Quelle `imported`. Erst eine ausdrückliche Benutzeraktion übernimmt fehlerfreie Kandidaten in das noch nicht gespeicherte Wizard-Formular; ein Scan wird dadurch nicht gestartet.

Dateien sind auf 2 MiB und 30 Datenzeilen begrenzt. XLSX wird ohne Formelauswertung und ohne externe Bibliothek aus dem ersten Arbeitsblatt gelesen. Externe XML-Entitäten, unzulässige Beziehungspfade, übergroße XML-Bestandteile und Formeln werden abgewiesen. Die bestehende serverseitige Laufvalidierung bleibt für die endgültige Konfiguration maßgeblich.

Zielbereiche werden nicht importiert. Mehrere Bereiche würden der Entscheidung „ein Zielbereich pro Lauf“ aus ADR-0011 widersprechen und benötigen vor einer Umsetzung ein eigenes Batch-/Autorisierungsdesign.

## Alternativen

- Ein rein clientseitiger Parser wurde verworfen, weil Dateigrenzen und Schema dann nicht zentral und testbar durchgesetzt würden.
- Eine direkte Erzeugung mehrerer Läufe aus einer Datei wurde verworfen, weil Sicherheitsbestätigung, Netzfreigabe und Fehlerbehandlung pro Zielbereich neu entworfen werden müssten.
- Eine zusätzliche Spreadsheet-Bibliothek wurde für das kleine feste Schema verworfen, um Abhängigkeiten und Formel-/Formatierungsumfang nicht unnötig zu vergrößern.

## Konsequenzen

### Positiv

- Bestehende AE-Titel-Listen können kontrolliert und mit Zeilenfeedback übernommen werden.
- CSV und echte XLSX-Dateien werden unterstützt, ohne Makros oder Formeln auszuführen.
- Der Import verändert keine Datenbank und startet keinen Netzwerkverkehr.

### Negativ und Risiken

- Nur das erste XLSX-Arbeitsblatt und ein bewusst kleines, versioniertes Schema werden gelesen.
- Zielbereichs- und Mehrfachlaufimporte bleiben außerhalb des Funktionsumfangs.
- Anwender müssen die Vorschau ausdrücklich übernehmen und den normalen Wizard vollständig abschließen.

## Verifikation

Feature-Tests prüfen CSV mit deutschem Semikolon-Trennzeichen, XLSX, Duplikate, zeilenbezogene Validierungsfehler, Formelabwehr, Dateiendungen, Berechtigungen und das Ausbleiben eines automatisch erzeugten Discovery-Laufs.

## Referenzen

- `docs/Decisions/ADR-0011-discovery-scanning.md`
- `docs/Decisions/ADR-0010-import-export.md`
- `docs/Features/dicom-discovery.md`
- `app/Services/Discovery/DiscoveryAeCandidateImportService.php`
