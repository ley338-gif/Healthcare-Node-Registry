---
title: ADR-0014 – Wiederverwendbare administrative Anwendungsshell
description: Aufteilung des Inertia-Layouts in gemeinsame Navigation, Kopfzeile und UI-Grundkomponenten.
document_type: ADR
chapter: ADR-0014
status: accepted
version: 1.0
last_updated: 2026-08-08
---

# ADR-0014: Wiederverwendbare administrative Anwendungsshell

- Entscheidungsstatus: Accepted
- Entscheidungsdatum: 2026-07-27
- Verantwortliche Rolle: Frontend/UX
- Betroffene Produktversion: 0.2.0-dev
- Ersetzt: Monolithisches Anwendungslayout
- Ersetzt durch: -

## Kontext

Registry-, Diagnose-, Dokumentations- und Administrationsseiten benötigen dieselbe primäre Navigation, Kopfzeile, Inhaltsstruktur und Statusdarstellung. Ein monolithisches Layout koppelt diese Belange an einzelne Seiten, erschwert konsistente Berechtigungszustände und führt zu duplizierten visuellen Mustern. Gleichzeitig darf das Dashboard keine erfundenen Monitoring- oder Onlinewerte anzeigen, solange keine entsprechende Datenquelle implementiert ist.

## Entscheidung

Die Inertia-/Vue-Oberfläche verwendet eine gemeinsame Anwendungsshell mit getrennten Komponenten für Sidebar und Header. Wiederkehrende Darstellungen werden als kleine UI-Grundkomponenten gekapselt, insbesondere Page Header, Content Cards, Status Badges und Statistik-Karten.

Die Navigation gruppiert fachlich zusammengehörige Arbeitsbereiche und darf geplante Funktionen nur eindeutig deaktiviert oder als nicht verfügbar darstellen. Dashboard-Kennzahlen werden ausschließlich aus tatsächlich vorhandenen Registry- und Diagnosewerten gebildet.

## Alternativen

- **Ein vollständiges Layout pro Seite:** verworfen wegen duplizierter Navigation, uneinheitlicher Zustände und höherem Pflegeaufwand.
- **Eine einzige große Layoutkomponente:** verworfen, weil Header, Sidebar und Inhaltsmuster dadurch schwer isoliert test- und änderbar wären.
- **Vorbereitete Navigation mit simulierten Kennzahlen:** verworfen, weil sie einen nicht implementierten Betriebszustand suggeriert.

## Konsequenzen

### Positiv

- Konsistente Navigation und Statusdarstellung über alle Arbeitsbereiche.
- Kleine Komponenten können unabhängig weiterentwickelt und wiederverwendet werden.
- Neue Seiten übernehmen etablierte Fokus-, Empty-State- und Permission-Muster.
- Das Dashboard bleibt fachlich nachvollziehbar und zeigt keine künstlichen Monitoringdaten.

### Negativ und Risiken

- Änderungen an Shell-Komponenten wirken auf viele Seiten und benötigen Frontend-Gesamtprüfungen.
- Zu allgemeine UI-Komponenten können fachliche Unterschiede verschleiern; Abstraktionen bleiben deshalb klein und konkret.

## Verifikation

- `npm run check` prüft Linting, Formatierung, Typen, Unit-Tests und Produktionsbuild.
- Navigationseinträge und Dashboarddaten werden durch Featuretests und serverseitige Berechtigungen abgesichert.
- Nicht implementierte Module dürfen keine aktiven Links oder erfundenen Statuswerte erhalten.

## Referenzen

- `docs/UX/UIFoundation.md`
- `docs/UI/DesignSystem.md`
- `resources/js/Layouts/AppLayout.vue`
- `resources/js/Components/layout/`
- `resources/js/Components/ui/`
- `AI_ENGINEERING_MANUAL.md`
