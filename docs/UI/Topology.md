# Topology UX

## Zweck

Die Topologie beantwortet:

- Welche Systeme sind verbunden?
- Über welchen Dienst?
- In welche Richtung?
- Welche Abhängigkeiten bestehen?
- Was wäre von einer Änderung betroffen?

## Darstellung

- Asset als primärer Knoten
- Endpoints optional einblendbar
- gerichtete Kanten
- Kantenlabel für Dienst/Protokoll
- Filter nach Standort, Abteilung, Typ, Protokoll und Status
- Zoom, Pan, Fit-to-view
- Auswahl öffnet Detail-Drawer
- „Nachbarschaft anzeigen“ reduziert komplexe Pläne

## Wahrheitsgehalt

Die Topologie zeigt dokumentierte Konfiguration. Ohne aktive Prüfung darf sie nicht als Live-Netzwerkstatus bezeichnet werden.

## Linienstil nach Nachweis-Status (`evidence_status`)

Seit dem Discovery-MVP hat jede Verbindung zusätzlich zum Betriebsstatus (aktiv/geplant/wartung/inaktiv) einen Nachweis-Status, der ausschließlich die Darstellung steuert:

- durchgezogen: `confirmed` (bestätigt) oder `manually_documented` (manuell dokumentiert)
- gestrichelt: `suspected` (vermutet - z. B. aus Discovery abgeleitet, nie automatisch bestätigt)
- gepunktet: `technically_tested` (technisch getestet, aber nicht als produktiv bestätigt)
- rot mit Warnsymbol: `failed_last_test` (letzter Test fehlgeschlagen)

Ein erfolgreicher Discovery-C-ECHO erzeugt niemals selbstständig eine Verbindung oder setzt `evidence_status` auf `confirmed` - das entscheidet ausschließlich ein Benutzer über das bestehende Verbindungsformular unter `/connections`.

## Große Umgebungen

- progressive Darstellung
- Clustering/Gruppierung
- serverseitige Filter
- keine ungefilterte Darstellung tausender Nodes
- Layoutpositionen optional speichern
