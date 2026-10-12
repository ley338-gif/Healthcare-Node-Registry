# Supply-Chain-Sicherheit

## Verbindliche CI-Nachweise

Der Workflow `.github/workflows/security.yml` läuft für Pull Requests, Änderungen auf `main`, manuelle Prüfungen und wöchentlich ohne Codeänderung. In allen Workflows sind fremde Actions auf unveränderliche Commit-SHAs mit Node-24-Runtime und die Runner auf `ubuntu-24.04` festgelegt, damit ein Wechsel des `ubuntu-latest`-Labels die Nachweise nicht unbemerkt verändert.

| Kontrolle | Umfang | Merge-Gate |
|---|---|---|
| Gitleaks | vollständige Git-Historie | jeder Fund blockiert |
| Composer Audit | `composer.lock` | veröffentlichte Security Advisories blockieren |
| npm Audit | produktive Pakete aus `package-lock.json` | hohe und kritische Befunde blockieren |
| Trivy | Release-App-, Web-, PostgreSQL- und ClamAV-Image, OS- und Bibliothekspakete | behebbare hohe und kritische Befunde blockieren |
| CycloneDX SBOM | alle vier im Release-Stack eingesetzten Images | Erzeugung der vier 30 Tage aufbewahrten Artefakte ist verpflichtend |

Composer- und npm-Audit laufen auf Basis der Lockfiles in einem eigenen Job und damit auch im wöchentlichen Lauf. Die vier Trivy-Scans laufen unabhängig voneinander; ein abschließender Gate-Schritt fasst ihre Ergebnisse in der Job-Zusammenfassung zusammen und lässt den Job fehlschlagen, sobald ein Image nicht bestanden hat. Ein Befund in einem Image verdeckt dadurch keine Befunde in den übrigen Images.

Nicht behebbare Containerbefunde werden im automatischen Gate nicht als Fehler gewertet, müssen aber vor einem Pilot-Release anhand des vollständigen Scanreports risikobewertet werden. Ausnahmen oder Ignore-Regeln benötigen eine dokumentierte CVE-bezogene Begründung, Owner, Ablaufdatum und Security-Freigabe. Pauschale oder unbegrenzte Ausnahmen sind unzulässig.

## Basisimages und Laufzeitabhängigkeiten

Basis- und externe Runtime-Images sind mit konkreter Version und Multi-Arch-Digest festgelegt. Der Build aktualisiert verfügbare Betriebssystempakete; neue Release-Artefakte werden nur akzeptiert, wenn das Trivy-Gate danach erfolgreich ist. Die DICOM-Python-Pakete werden mit Version und SHA-256-Hash installiert. `pip` und `setuptools` werden anschließend aus der Runtime-Umgebung entfernt, weil sie für den Betrieb nicht benötigt werden.

Das PostgreSQL-Release-Image leitet sich vom digest-gepinnten offiziellen Image ab, entfernt jedoch das nur für einen Root-Start benötigte `gosu`-Hilfsprogramm und läuft fest als Benutzer `postgres`. Damit gehört ein privilegierter Containerstart nicht zum Pilotpfad und unnötiger Go-Runtime-Code nicht zum Datenbankimage. Der Start mit einem frischen benannten Volume ist Teil der Containerprüfung.

Die SBOM-Dateien gehören zum Release-Nachweis und dürfen keine Secrets enthalten. Bei einem Registry-Push werden zusätzlich Image-Repo-Digests, Scanlauf und SBOM-Artefakte gemeinsam mit Release-Version und Git-Commit dokumentiert.

## Regelmäßige Prüfung

Der wöchentliche Lauf erkennt neu veröffentlichte Schwachstellen auch ohne Repositoryänderung. Ein fehlgeschlagener Zeitplanlauf eröffnet keinen automatischen Produktionszugriff: Security bewertet den Befund, sperrt betroffene Release-Kandidaten und priorisiert einen Patch-Build. Für den Pilot gilt ein offener kritischer oder hoher, praktisch relevanter Befund als No-Go.

SEC-01 der [Pilot-Entscheidungsmatrix](../Pilot/README.md) gilt nur als erfüllt, solange der jeweils letzte abgeschlossene Lauf des Supply-Chain-Workflows auf `main` grün und höchstens sieben Tage alt ist. Der Auslöser (Push, Zeitplan oder manueller Start) ist dabei unerheblich, weil jeder Lauf dieselben Prüfungen mit aktuellen Advisory-Daten ausführt. Die Altersgrenze stellt sicher, dass der wöchentliche Zeitplan tatsächlich läuft: GitHub deaktiviert Zeitpläne in öffentlichen Repositorys nach 60 Tagen ohne Repositoryaktivität, ein ausbleibender Lauf würde sonst nicht auffallen. Zwischen dem 31.08. und dem 12.10.2026 schlug der Zeitplanlauf wegen neu veröffentlichter Advisories zu `league/commonmark` sowie veralteter PostgreSQL- und ClamAV-Basisimages wiederholt fehl, ohne dass dies bemerkt wurde. Basis-Image-Digests werden deshalb bei einem roten Zeitplanlauf auf den aktuellen Patchstand derselben Hauptversion angehoben, statt Ausnahmen einzutragen.
