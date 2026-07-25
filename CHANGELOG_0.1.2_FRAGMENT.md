## [0.1.2] - Unreleased

### Added

- dedicated `app-test` service
- dedicated `db-test` PostgreSQL service
- isolated test network and volumes
- PowerShell helpers for safe tests and complete quality checks
- development and testing documentation

### Changed

- Composer test scripts no longer contain environment-variable workarounds
- PHPUnit database configuration comes from the isolated test container
- test safety guard validates effective Laravel environment and database

### Security

- PHPUnit cannot reach the development PostgreSQL service through Docker networking
- tests reject databases whose names do not end in `_test`
