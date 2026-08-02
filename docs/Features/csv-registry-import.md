# CSV-Import für Systeme und DICOM-Knoten

Unter `/systems/import` können berechtigte Registry-Administratoren Stammdaten importieren. Jeder Import besteht aus zwei getrennten Schritten: Vorschau und bestätigte Übernahme. Die Vorschau schreibt keine Registry-Daten und zeigt Fehler je CSV-Zeile an.

## Allgemeine Grenzen

- UTF-8-CSV mit Kopfzeile und Komma als Trennzeichen
- maximal 2 MiB und 1.000 Datenzeilen
- leere optionale Felder bleiben `null`
- Duplikate innerhalb der Datei und gegenüber der Datenbank werden nicht importiert
- der bestätigte Lauf erzeugt `registry.csv_import.completed` mit Anzahl importierter, übersprungener und fehlerhafter Zeilen

## Systeme

Erforderliche Kopfzeile in dieser Reihenfolge:

```csv
organization_name,site_name,department_name,name,system_type,status,hostname,fqdn,ip_address,vendor,product,model,version,operating_system,operating_system_version,serial_number,inventory_number,description,notes
```

Organisationen sowie optional Standorte und Abteilungen müssen bereits existieren. Ein System gilt als Duplikat, wenn derselbe Name innerhalb derselben Organisation bereits vorhanden ist.

## DICOM-Knoten

```csv
organization_name,system_name,name,ae_title,host,port,role,status,tls_enabled,supports_echo,supports_store,supports_query,supports_retrieve,supports_storage_commitment,supports_mpps,supports_worklist,description,notes
```

Das referenzierte System muss bereits in der angegebenen Organisation existieren. Für boolesche Spalten sind `true`/`false`, `1`/`0`, `yes`/`no` und `ja`/`nein` zulässig. Die Kombination aus System, AE Title, Host und Port muss eindeutig sein.

Die Feldvalidierung verwendet dieselben Regeln wie die manuellen Formulare aus `StoreSystemRequest` und `StoreDicomNodeRequest`.
