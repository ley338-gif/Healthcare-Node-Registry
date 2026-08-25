# Supplier and Dependency Management

## Geltungsbereich

- Frameworks und Bibliotheken
- Container-Basisimages
- Build- und CI-Aktionen
- Malware-Scan-Engines
- optionale externe Dienste
- Hosting-/Supportlieferanten

## Bewertungskriterien

- Wartungsstatus
- Sicherheitsverlauf
- Lizenz
- Releasefrequenz
- Community/Hersteller
- Exit-/Austauschmöglichkeit
- Offline-Fähigkeit
- Datenübertragung
- Supportdauer

## Nachweise

- Lockfiles
- CycloneDX-SBOMs der gebauten Release-App- und Webimages
- Composer-, npm- und Trivy-Scans als verbindliche CI-Gates
- vollständiger Secret-History-Scan
- dokumentierte ADRs für wesentliche Abhängigkeiten
- regelmäßige Reviewtermine

Die konkrete Automatisierung, Schwellen und Ausnahmebedingungen beschreibt [Supply-Chain-Sicherheit](../Security/SupplyChain.md).
