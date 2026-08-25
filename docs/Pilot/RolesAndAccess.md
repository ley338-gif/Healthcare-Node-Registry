# Minimales Pilot-Rollenmodell

## Rollen und Verantwortungen

| Pilotrolle | Anwendungskonto | Mindestumfang | Ausschlüsse |
|---|---|---|---|
| Systemadministrator | `system-administrator` | Konten, Rollen, Referenzdaten, technische Konfiguration; verpflichtende MFA | keine gemeinsame Nutzung, keine Alltagsdiagnostik ohne fachlichen Auftrag |
| Registry-Administrator | Registry-Admin-Rolle | Registry, Organisation, DICOM-Verbindungen und Dokumentation verwalten | keine Rollenverwaltung; Exporte und aktive Diagnosen nur mit Einzelrecht |
| PACS-Administrator | `PACS-Administrator` | freigegebene DICOM-Diagnosen und benötigte Registry-Leserechte | keine Benutzer-/Rollenverwaltung; verändernde Tests nur im Abnahmefenster |
| Auditor | Auditor-Rolle | Audit und freigegebene lesende Nachweise | keine Änderungen und keine aktiven Diagnosen |
| Nur Lesen | `Nur Lesen` | freigegebene Registry-Ansichten | keine Exporte, Änderungen oder Diagnosen |
| Break Glass | separates persönliches `system-administrator`-Konto | ausschließlich Wiederherstellung administrativer Handlungsfähigkeit | keine Routineverwendung; jede Nutzung erzeugt Incident-/Reviewpflicht |

Pro Person wird genau der kleinste notwendige Umfang vergeben. Gemeinsame Konten sind unzulässig. Kritische Kombinationen – insbesondere Rollenverwaltung plus fachliche Freigabe – werden organisatorisch getrennt, soweit die Pilotbesetzung dies erlaubt; andernfalls ist jede Nutzung nachträglich durch einen zweiten Verantwortlichen zu prüfen.

## Identitäts-Gates

- Privilegierte Konten richten vor Freigabe TOTP-MFA ein und testen genau einen Recovery-Vorgang.
- Recovery-Codes werden einmalig erzeugt, getrennt vom Passwort geschützt und nach Nutzung ersetzt.
- Das Break-Glass-Konto erhält ein zufälliges, getrennt verwahrtes Geheimnis und MFA; Zugriff, Nutzung und Rückgabe werden protokolliert.
- Offboarding deaktiviert das Konto unverzüglich, widerruft Sitzungen, entfernt Rollenzuordnungen und rotiert gemeinsam bekannte technische Secrets.
- Rollen- oder Aufgabenwechsel werden spätestens am selben Arbeitstag umgesetzt.
- Vor Pilotstart, monatlich und bei jedem Personalwechsel erfolgt ein Review von aktiven Konten, effektiven Rechten, MFA-Status und letzter Nutzung.
- Der letzte aktive Systemadministrator bleibt technisch geschützt; dies ersetzt nicht die namentliche Stellvertretung.

## Abnahmenachweis

Das Rechteprotokoll enthält Person, dienstliche Funktion, Anwendungskonto, Rollen, zusätzliche Einzelrechte, Scope, MFA-Status, Genehmiger, Gültigkeitsende und Reviewdatum. Recovery-Codes, Passwörter, TOTP-Secrets und Tokens gehören niemals in das Protokoll.

Technische Details: [Zugriffskontrolle](../Security/AccessControl.md), [Mehrfaktor-Authentisierung](../Security/MultiFactorAuthentication.md).
