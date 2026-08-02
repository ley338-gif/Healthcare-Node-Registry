# Repository cleanup

## Status

- Date: 2026-08-02
- Scope: complete tracked repository plus project-local files visible from the workspace
- Method: conservative reference, configuration, history, duplicate, artifact, and secret-indicator review

## Analyzed areas

- Laravel application code, routes, requests, models, policies, services, and providers
- Vue/Inertia frontend, Vite, ESLint, Prettier, TypeScript, and npm dependencies
- Composer dependencies, PHPUnit, PHPStan/Larastan, and Pint configuration
- Database factories, seeders, and all migrations
- Docker Compose, PHP-FPM image, Nginx configuration, and PowerShell scripts
- GitHub Actions, repository ignore rules, documentation, specifications, and release records
- Local ignored environment, dependency, cache, and build files

## Classification before changes

### Safe to remove

| Path | Category | Evidence |
| --- | --- | --- |
| `app/Http/Controllers/SystemController.php.bak` | obsolete backup | Not referenced by source, routes, tests, build, deployment, or documentation; Git history and direct comparison show it is an older, less complete copy of the active controller. |
| `phpstan-errors.txt` | generated local output | Captured terminal output from a PHPStan run; not consumed by scripts, CI, tests, or documentation. |

### Local and not for Git

- `.env`, `.phpunit.result.cache`, `bootstrap/cache/packages.php`, `bootstrap/cache/services.php`, `node_modules/`, `vendor/`, and `public/build/` are ignored and reproducible or machine-local.
- `.env` was not deleted because it is required by the current local installation and may contain credentials. Its values were not copied into documentation.
- Installed dependency directories and the current frontend build were retained because they are useful for validation and local development and are already excluded from version control.

### Potentially sensitive

- Local `.env` and named Docker volumes may contain credentials or application data. They were intentionally not inspected in detail, copied, or deleted.
- Private-address examples found in tests and UI placeholders are synthetic test ranges in context; no private keys or common token signatures were found in tracked project files by the performed indicator scan.

### Probably obsolete or manual review

- Root-level implementation reports, manifests, and changelog fragments reflect earlier delivery phases. They remain tracked because manifests reference the fragments and the reports can serve as release/provenance records.
- No database migration, route, controller, model, service, frontend component, dependency, backup/restore script, or module placeholder was removed without stronger evidence of safe removal.

### Still required

- All production application code, migrations, tests, Docker services, documentation, lock files, and declared dependencies were retained.
- Identical `.gitignore` placeholder files in runtime directories are intentional and keep required directory structure in Git.

## Changes performed

- Removed the obsolete controller backup and generated PHPStan output.
- Added `*.bak` and `phpstan-errors.txt` to `.gitignore` to prevent recurrence.
- Corrected indentation of the GitHub Actions `repository` job so its runner and steps belong to the job.
- Updated the changelog with the maintenance changes.

## Removed dependencies

None. Each direct frontend runtime dependency is imported by the application, frontend tooling dependencies are referenced by configuration/build scripts, and the reviewed backend packages support framework, process execution, development, analysis, formatting, or tests.

## Validation

- `npm ci --ignore-scripts`: passed after allowing registry access; 242 packages installed from the lock file.
- `npm run check`: passed (ESLint, Prettier check, Vue/TypeScript typecheck, 4 frontend unit tests, and Vite production build).
- `npm audit --audit-level=high --omit=dev`: passed with 0 production vulnerabilities. The installer separately reported one high-severity issue in the full dependency tree, therefore in development-only dependencies.
- `composer validate --strict --no-check-publish`: passed.
- `composer lint:check`: passed for 233 PHP files.
- `composer analyse`: passed for 147 analyzed PHP files with no errors.
- `composer test`: passed, 181 tests and 1,156 assertions.
- `docker compose config --quiet`: passed.
- `docker compose build app`: passed.
- `php artisan migrate:fresh --force` against the isolated `db-test` service: all 15 migrations passed.
- `php artisan registry:doctor --skip-assets`: application key, debug mode, database connection, migration state, and writable paths passed. The command returned failure only because a freshly migrated test database intentionally has no system administrator.
- `composer audit --locked --no-interaction`: could not query Packagist because DNS resolution from the Docker container was unavailable; this is an environment limitation, not a dependency finding.

## Known limitations and manual follow-up

- Secret-indicator scanning is heuristic and does not replace a dedicated history scan. Git history was not rewritten.
- Docker named volumes, uploads, audit records, backups, certificates, user files, and databases were deliberately left untouched.
- Historical manifests and implementation reports should only be consolidated after maintainers decide their retention policy.
- Repeat `composer audit --locked --no-interaction` in CI or another environment with Packagist access.
- Review and update the development-only npm dependency reported by `npm ci`; production dependency audit is clean.
- For a fully green operational doctor check, create an initial administrator in the intended installation rather than in the disposable test database.
