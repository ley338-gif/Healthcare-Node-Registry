# Implementation Report 0.1.2

## Problem

Backend tests were started in the development application container. Docker provided `APP_ENV=local` and the development database configuration. Tests using `RefreshDatabase` could therefore erase development data.

## Resolution

A separate test execution boundary was introduced:

- `app-test`
- `db-test`
- `test_backend`
- `app_test_storage`
- `postgres_test_data`

The test application uses only the hostname `db-test` and a database ending in `_test`.

## Verification

```powershell
docker compose --profile test run --rm app-test php artisan tinker --execute="dump(app()->environment(), config('database.connections.pgsql.host'), config('database.connections.pgsql.database'));"
```

Expected values:

```text
testing
db-test
healthcare_node_registry_test
```

Run quality gates:

```powershell
docker compose --profile test run --rm app-test composer quality
docker compose run --rm node npm run check
```

Afterward verify that the development administrator still exists.
