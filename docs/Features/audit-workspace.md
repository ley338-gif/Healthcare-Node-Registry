# Zentraler Audit-Arbeitsbereich

`Administration → Audit` zeigt die bestehende, append-only Historie aus `security_events` als read-only Arbeitsbereich. Die Seite ist ausschließlich mit `audit.view` sichtbar und erreichbar.

Die Ansicht bietet kompakte Kennzahlen, serverseitige Filter und Sortierung, 50 Einträge pro Seite, responsive Tabellenansichten und ein Detail-Slide-over mit Vorher-/Nachher-Werten. CSV-Exporte übernehmen die aktuelle Filterauswahl und werden speicherschonend gestreamt. Es gibt keine zweite Audit-Datenhaltung.

Strukturierte Einzeländerungen werden bereits kompakt in der Tabelle gezeigt; bei mehreren Feldern bleibt die Tabelle bewusst reduziert und das Slide-over zeigt die vollständige Gegenüberstellung. Das zentrale `AuditEventGroup`-Enum ordnet jedes Ereignis genau einer auswertbaren Gruppe zu. Verfügbare, aktive Registry-Objekte können direkt im jeweiligen Workspace geöffnet werden; für archivierte, gelöschte oder nicht mehr vorhandene Ziele bleibt die Navigation deaktiviert.
