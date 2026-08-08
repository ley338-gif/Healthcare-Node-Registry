# Organisationsstruktur

```text
Organisation
└── Standort
    └── Abteilung
```

## Regeln

- Datensätze werden archiviert, nicht hart gelöscht.
- Ein Elternelement kann erst archiviert werden, wenn seine aktiven Kinder archiviert sind.
- Öffentliche URLs verwenden UUIDv7-basierte `public_id`-Werte.
- Erstellen, Ändern und Archivieren erzeugt Security Events.
- Namen sind innerhalb des jeweiligen Elternkontexts eindeutig.

## Nicht enthalten

Strukturierte Verantwortungszuweisungen für Organisationen, Standorte und Abteilungen, Netzwerksegmente, DICOM-/HL7-Endpunkte und Mandantenfähigkeit folgen in späteren Sprints. Für Systeme ist eine optionale betriebliche Verantwortlichkeit als Freitext vorhanden; Systeme selbst werden in der System Registry verwaltet.
