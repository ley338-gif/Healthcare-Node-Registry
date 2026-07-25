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

Verantwortliche, Netzwerksegmente, Systeme, DICOM-/HL7-Endpunkte und Mandantenfähigkeit folgen in späteren Sprints.
