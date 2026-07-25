# ADR-0001: Technology Versions

- **Status:** Accepted for 0.1.0
- **Decision date:** 2026-07-25

## Entscheidung

| Komponente | Festlegung |
|---|---|
| PHP | 8.4.x |
| Laravel | 13.x |
| Inertia Laravel/Vue | 3.x |
| Vue | 3.5.x |
| TypeScript | 5.8.x |
| Tailwind CSS | 4.1.x |
| Vite | 8.x |
| Node.js | 24 LTS |
| PostgreSQL | 18.x |
| Nginx | 1.28.x |
| Docker Compose | Compose Specification / Plugin v2 |

Container verwenden konkrete Minor-Tags. Composer- und npm-Lockfiles werden beim ersten kontrollierten Installationslauf erzeugt und committed.

## Begründung

Die Versionen besitzen aktiven Support und bilden die aktuelle stabile Laravel-/Vue-Toolchain. Node 24 ist LTS. PostgreSQL 18 besitzt einen langen Supportzeitraum.

## Konsequenzen

- keine `latest`-Tags
- Minor-/Patch-Updates durch Dependency- und Security-Review
- Major-Upgrades benötigen eigenes ADR oder Aktualisierung dieses ADR
- Offline-Installationen benötigen ein internes Artefakt-/Image-Mirroring
