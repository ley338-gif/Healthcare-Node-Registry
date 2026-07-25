# Design System

## Zielbild

Ruhige, professionelle Administrationsoberfläche nach `specification/ui-reference.png`.

## Layout

- Sidebar: Primärnavigation
- Topbar: globale Suche, Kontext, Nutzeraktionen
- Main Canvas: Tabellen, Forms und Topologie
- optionaler Right Drawer: kontextbezogene Details
- mobile Darstellung: Navigation als Drawer, keine vollständige Topologie-Authoring-Pflicht

## Design Tokens

Vor Implementierung zentral definieren:

- Farben
- Typografie
- Spacing
- Radius
- Shadows
- Z-Index
- Motion
- Breakpoints

Keine freien Einzelwerte in Komponenten, wenn ein Token existiert.

## Statusdarstellung

Status wird nie ausschließlich durch Farbe vermittelt.

Beispiele:

- Aktiv
- Geplant
- Außer Betrieb
- Unbekannt
- Dokumentiert
- Nicht verifiziert
- Live geprüft, inklusive Zeitstempel

## Komponenten

- AppShell
- Sidebar
- Topbar
- PageHeader
- MetricCard
- DataTable
- FilterBar
- StatusBadge
- EmptyState
- ErrorState
- ConfirmDialog
- DetailDrawer
- AuditTimeline
- DocumentList
- TopologyCanvas
- TopologyNode
- ConnectionEdge

## Accessibility

- WCAG-orientierte Kontraste
- sichtbare Fokusindikatoren
- Tastaturnavigation
- semantische Labels
- sinnvolle Fehlertexte
- keine Hover-only-Funktion
- reduzierte Bewegung respektieren
