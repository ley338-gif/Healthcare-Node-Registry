# Secure Development Lifecycle

## Phasen

1. Sicherheitsanforderungen
2. Threat Modeling
3. sichere Architektur
4. Implementierung mit Review
5. automatisierte Tests und Scans
6. Security-Verifikation
7. Release-Freigabe
8. Vulnerability Management
9. Lessons Learned

## Automatisierte Kontrollen

Implementiert:

- Secret Scan der vollständigen Git-Historie
- Dependency Scans für Composer- und produktive npm-Pakete
- statische Analyse
- Unit-/Feature-Tests und Coverage-Gates
- Container Scan der Release-App-, Web-, PostgreSQL- und ClamAV-Images auf behebbare hohe und kritische Befunde
- CycloneDX-SBOMs aller vier Runtime-Images des Release-Stacks

Verbindliche Schwellen, Nachweise und Ausnahmebedingungen beschreibt [Supply-Chain-Sicherheit](SupplyChain.md).

Noch geplant:

- automatisierte Lizenzprüfung
- signierte Build-Provenance und Artefaktsignaturen

## Manuelle Kontrollen

- Authz-Review
- Upload-Review
- Threat-Model-Review
- Update-/Rollback-Test
- Restore-Test
- Penetrationstest vor kommerziellem produktivem Einsatz
