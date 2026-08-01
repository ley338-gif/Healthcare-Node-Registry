# Strukturierte Registry-Dokumentation

`registry_documentation` speichert aktuelle Betriebsdokumentation polymorph für Organisationen, Standorte, Abteilungen und Systeme. Stammdaten wie Hersteller, Hostname oder Adresse bleiben in ihren Registry-Modellen und werden in der Dokumentationsoberfläche nur read-only angezeigt.

## Struktur

Jeder Eintrag besitzt Dokumentationstyp, Sektion, Titel, optionalen Inhalt, strukturierte JSON-Daten, Sichtbarkeit sowie Ersteller und letzten Bearbeiter. Pro Entität, Dokumentationstyp und Sektion existiert höchstens ein Eintrag.

Systeme bieten zehn Betriebssektionen. Organisationen, Standorte und Abteilungen besitzen eigene fachlich passende Sektionen. Alle verwenden `DocumentationPanel.vue` mit Cards, sektionenweisem Slide-over und einem nachvollziehbaren Fortschritt aus explizit definierten Pflichtfeldern.

## Audit und Berechtigungen

Lesen und Bearbeiten verwenden die bestehenden Registry-Policies. Es wurde kein paralleles Rollensystem eingeführt. Jede Anlage oder Änderung erzeugt `documentation.updated` über `RegistryAudit` am dokumentierten Registry-Kontext.

Das Audit enthält keine vollständigen Langtexte oder JSON-Inhalte. Für diese Felder werden Länge und SHA-256 protokolliert. Damit bleibt die Änderung nachweisbar, ohne sensible Betriebsinformationen zu duplizieren.

## Bekannte Einschränkungen

- keine Datei-Uploads oder Anhänge
- keine Freigabe- oder Versionsworkflows
- keine Exporte
- `restricted` ist vorbereitet; feinere Sichtbarkeitsrechte folgen erst bei einem abgestimmten Berechtigungskonzept

