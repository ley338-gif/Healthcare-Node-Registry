# Modulabhängigkeitsregeln

## Grundsatz

Der modulare Monolith besitzt klare Domänengrenzen. Controller und Vue-Komponenten enthalten keine zentrale Geschäftslogik.

## Erlaubte Abhängigkeiten

- UI/HTTP darf Application Use Cases aufrufen.
- Application Use Cases dürfen Domänenlogik und freigegebene Ports verwenden.
- Infrastruktur implementiert Ports und Adapter.
- Topology liest freigegebene Read Models aus Assets, Endpoints und Connections.
- Audit empfängt bewusst definierte Audit-Daten, keine vollständigen Request Bodies.
- ImportExport verwendet öffentliche Application Services der Zielmodule.

## Verbotene Abhängigkeiten

- Vue-Komponenten greifen nicht direkt auf Datenbank- oder Autorisierungslogik zu.
- Controller ändern keine Models ohne Use Case oder Action.
- Documents kennt keine DICOM-Fachlogik.
- Topology führt keine zweite fachliche Datenhaltung ein.
- Module lesen nicht unkontrolliert interne Tabellen anderer Module.
- Externe Integrationen werden nicht direkt in Controller eingebaut.

## Gemeinsame Bausteine

Nur stabile, fachlich neutrale Bausteine dürfen in Shared/Common liegen, zum Beispiel:

- Identifikatoren
- Clock
- Pagination
- Result/Error-Typen
- technische Basisklassen

Shared darf kein Sammelplatz für ungeklärte Domänenlogik werden.
