# Testing

## Required command

Backend quality checks must run in the isolated test container:

```powershell
docker compose --profile test run --rm app-test composer quality
```

Single test:

```powershell
.\scripts\test.ps1 -Filter HealthEndpointTest
```

Complete local checks:

```powershell
.\scripts\quality.ps1
```

## Coverage

The backend image includes PCOV, disabled by default. Run the full test suite with
coverage and enforce the repository thresholds with:

```powershell
docker compose --profile test run --rm app-test composer coverage:check
```

The generated Clover report is written to `coverage/clover.xml` and is ignored by
Git. `coverage-thresholds.json` protects the overall application coverage and the
security-sensitive policy, document, discovery, and CSV import groups against
regressions. GitHub Actions runs the same gate through `composer ci`.

## Commands that must not be used for backend tests

Do not run PHPUnit in the development container:

```text
docker compose exec app vendor/bin/phpunit
docker compose exec app composer test
docker compose exec app composer quality
```

The safety guard should reject such execution, but the approved workflow is the isolated `app-test` service.

## Reset test database

The test database contains no business data and may be removed at any time:

```powershell
docker compose --profile test down
docker volume rm healthcare-node-registry_postgres_test_data
```

The development volume `postgres_data` must not be removed during test maintenance.
