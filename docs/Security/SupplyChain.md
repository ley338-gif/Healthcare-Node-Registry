# Supply-Chain-Sicherheit

## Verbindliche CI-Nachweise

Der Workflow `.github/workflows/security.yml` läuft für Pull Requests, Änderungen auf `main`, manuelle Prüfungen und wöchentlich ohne Codeänderung. Fremde Actions sind auf unveränderliche Commit-SHAs festgelegt.

| Kontrolle | Umfang | Merge-Gate |
|---|---|---|
| Gitleaks | vollständige Git-Historie | jeder Fund blockiert |
| Composer Audit | `composer.lock` | veröffentlichte Security Advisories blockieren |
| npm Audit | produktive Pakete aus `package-lock.json` | hohe und kritische Befunde blockieren |
| Trivy | Release-App- und Webimage, OS- und Bibliothekspakete | behebbare hohe und kritische Befunde blockieren |
| CycloneDX SBOM | tatsächlich gebaute Release-App- und Webimages | Erzeugung beider 30 Tage aufbewahrter Artefakte ist verpflichtend |

Nicht behebbare Containerbefunde werden im automatischen Gate nicht als Fehler gewertet, müssen aber vor einem Pilot-Release anhand des vollständigen Scanreports risikobewertet werden. Ausnahmen oder Ignore-Regeln benötigen eine dokumentierte CVE-bezogene Begründung, Owner, Ablaufdatum und Security-Freigabe. Pauschale oder unbegrenzte Ausnahmen sind unzulässig.

## Basisimages und Laufzeitabhängigkeiten

Basisimages sind mit konkreter Version und Multi-Arch-Digest festgelegt. Der Build aktualisiert verfügbare Betriebssystempakete; neue Release-Artefakte werden nur akzeptiert, wenn das Trivy-Gate danach erfolgreich ist. Die DICOM-Python-Pakete werden mit Version und SHA-256-Hash installiert. `pip` und `setuptools` werden anschließend aus der Runtime-Umgebung entfernt, weil sie für den Betrieb nicht benötigt werden.

Die SBOM-Dateien gehören zum Release-Nachweis und dürfen keine Secrets enthalten. Bei einem Registry-Push werden zusätzlich Image-Repo-Digests, Scanlauf und SBOM-Artefakte gemeinsam mit Release-Version und Git-Commit dokumentiert.

## Regelmäßige Prüfung

Der wöchentliche Lauf erkennt neu veröffentlichte Schwachstellen auch ohne Repositoryänderung. Ein fehlgeschlagener Zeitplanlauf eröffnet keinen automatischen Produktionszugriff: Security bewertet den Befund, sperrt betroffene Release-Kandidaten und priorisiert einen Patch-Build. Für den Pilot gilt ein offener kritischer oder hoher, praktisch relevanter Befund als No-Go.
