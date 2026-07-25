# Docker Architecture

## Development

```text
web -> app -> db
```

- `app` loads `.env`
- `db` stores `healthcare_node_registry`
- `app_storage` contains development runtime data
- `postgres_data` contains development database data

## Testing

```text
app-test -> db-test
```

- isolated Docker network
- isolated PostgreSQL instance
- isolated storage volume
- database name must end in `_test`
- no access to the development database service
- PHPUnit safety guard rejects any non-test environment or database

## Security boundary

`app-test` is attached only to `test_backend`. It cannot resolve or connect to the development service `db` through Docker DNS.

This is stronger than only changing `DB_DATABASE`, because the test process uses a completely separate PostgreSQL container.
