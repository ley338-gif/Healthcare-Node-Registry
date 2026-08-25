# Pilot-Readiness und Go/No-Go

Diese Seite ist die verbindliche P0-Entscheidungsmatrix für `0.9.0-rc.1`. Ein Pilot-Go ist nur zulässig, wenn alle P0-Kriterien den Status **erfüllt** besitzen. Eine dokumentierte Planung, ein lokal bestandener Test oder eine Risikoakzeptanz ersetzt keinen ausdrücklich geforderten Nachweis auf dem Pilot-Zielsystem.

Arbeitspakete, Abhängigkeiten, Aufwand und Abnahmekriterien sind im [Pilot-Backlog](Backlog.md) geführt.

## Statusregeln

- **erfüllt**: Kriterium und verlinkter Nachweis sind aktuell, überprüfbar und freigegeben.
- **offen**: Nachweis oder Freigabe fehlt; bei P0 ist dies ein No-Go.
- **nicht erfüllt**: Prüfung ist fehlgeschlagen; bei P0 ist dies ein No-Go.
- **nicht anwendbar**: nur mit dokumentierter Begründung und Freigabe durch Pilot Owner und Security zulässig.

## Entscheidungsmatrix

| ID | Kriterium und Akzeptanz | Nachweis | Ergebnis | Verantwortlich | Status |
|---|---|---|---|---|---|
| REL-01 | App- und Webimage sind unveränderlich gebaut; Version und Git-SHA sind als Labels enthalten; keine produktiven Quellcode-Bind-Mounts | [Releaseprozess](../Deployment/ReleaseProcess.md), grüner CI-Job `container` | lokal und CI nachgewiesen | Release Owner | erfüllt |
| REL-02 | Installation, Upgrade und Rollback sind für denselben RC dokumentiert | [Releaseprozess](../Deployment/ReleaseProcess.md) | Verfahren vorhanden | Release Owner | erfüllt |
| REL-03 | finaler RC-SHA, Image-Digests, vier SBOMs und alle CI-Läufe sind im Freigabeprotokoll festgehalten | Freigabeprotokoll nach Vorlage unten | erst nach letztem Pilot-P0-Merge möglich | Release Owner | offen |
| INS-01 | saubere Installation des unveränderten RC auf dem vorgesehenen Zielhost besteht Doctor, Health, Login, Worker und Persistenztest | [Clean-Install-Validierung](../Deployment/CleanInstallValidation.md) | ältere Entwicklungsvalidierung vorhanden; Zielhostnachweis fehlt | Operations | offen |
| SEC-01 | vollständige Git-Historie ist secret-frei; alle vier Runtime-Images haben keine behebbaren hohen/kritischen Befunde; vier SBOMs sind erzeugt | [Supply-Chain-Sicherheit](../Security/SupplyChain.md), CI-Artefakte | automatisierte Gates vorhanden | Security | erfüllt |
| SEC-02 | HTTPS, Secrets, DICOM-Egress, Callback-Bindung, Firewall und Zertifikatsketten sind auf dem Zielhost geprüft | [Betriebsabnahme](OperationalAcceptance.md) | installationsabhängig | Security / Operations | offen |
| IAM-01 | namentliche Rolleninhaber, MFA, Recovery, Break-Glass, Offboarding und Rechte-Review sind protokolliert | [Pilot-Rollenmodell](RolesAndAccess.md) | technisches Modell vorhanden; organisatorische Besetzung fehlt | Identity Owner | offen |
| OPS-01 | RPO/RTO, getrenntes Backupziel, Restore-Dauer und Datenintegrität sind bestätigt | [Betriebsabnahme](OperationalAcceptance.md), Restore-Protokoll | Zielhostnachweis fehlt | Operations | offen |
| OPS-02 | Logrotation, Speicher-, Zertifikats-, Backup-, Health- und Security-Alarmwege wurden ausgelöst und quittiert | [Betriebsabnahme](OperationalAcceptance.md) | Zielintegration fehlt | Operations / Security | offen |
| HC-01 | PACS-, RIS/MWL- und Modalitäts-/Test-SCU-Matrix wurde ausschließlich mit synthetischen Daten vollständig bestanden | [Healthcare-E2E-Abnahme](HealthcareAcceptance.md) | keine freigegebenen lokalen Zielsysteme im Repository | Healthcare Lead | offen |
| UX-01 | Vertreter der Pilotrollen bestehen die kritischen Aufgaben ohne P0-Usability-Fehler | Protokoll der Szenarien aus der Healthcare-E2E-Abnahme | Benutzerabnahme fehlt | Product Owner | offen |
| DOC-01 | Administrator-, Benutzer-, Sicherheits-, Betriebs- und Release-Dokumentation ist auf den RC geprüft und freigegeben | Dokumentenfreigabe mit Version und SHA | technische Inhalte vorhanden; formale Freigabe fehlt | Documentation Owner | offen |

## Aktuelle Empfehlung

**No-Go.** Die automatisierbaren Release- und Supply-Chain-Gates sind erfüllt. Zielhostinstallation, betrieblicher Restore und Alarmweg, namentliche Rollenfreigabe, Healthcare-E2E- und Benutzerabnahme sowie die finale Dokumentfreigabe benötigen eine ausdrücklich freigegebene Pilotumgebung und verantwortliche Personen. Diese externen Nachweise dürfen nicht simuliert oder durch lokale Annahmen ersetzt werden.

## Freigabeprotokoll

```text
Version / RC:
Git-SHA:
App-Image-Digest:
Web-Image-Digest:
PostgreSQL-Image-Digest:
ClamAV-Image-Digest:
CI-Läufe und SBOM-Artefakte:
Pilot-Zielhost / Umgebung:
Matrixstand:
Offene Abweichungen:
Pilot Owner, Datum, Entscheidung:
Security, Datum, Entscheidung:
Operations, Datum, Entscheidung:
Healthcare Lead, Datum, Entscheidung:
```

Die ausgefüllte Fassung wird als kontrolliertes, zugriffsgeschütztes Pilotartefakt abgelegt. Sie enthält keine Kennwörter, privaten Schlüssel, Tokens, Patientendaten oder ungekürzten sensiblen Netzpläne.
