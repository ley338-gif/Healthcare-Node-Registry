# ADR-0001: Technology Versions

- **Status:** Proposed
- **Decision date:** offen

## Kontext

Reproduzierbare Builds und On-Premise-Betrieb erfordern festgelegte, unterstützte Versionen.

## Entscheidung

Vor Initialisierung werden konkrete Versionen für PHP, Laravel, Node.js, Vue, TypeScript, Inertia.js, Tailwind CSS, PostgreSQL, Docker Compose und unterstützte Browser festgelegt. Container-Images verwenden feste Major-/Minor-Tags oder Digests, niemals unkontrolliert `latest`.

## Kriterien

- aktiver Security-Support
- Laravel-Kompatibilität
- LTS-Präferenz bei Laufzeit und Datenbank
- offline installierbare Artefakte
- verfügbare SBOM- und Scan-Unterstützung

## Konsequenzen

Die Versionsmatrix wird in README und Betriebsdokumentation referenziert. Updates benötigen Changelog, Risiko- und Migrationsbewertung.
