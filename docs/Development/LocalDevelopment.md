# Local Development

## Start application

```powershell
docker compose up -d app db web
```

## Install dependencies

```powershell
docker compose run --rm app composer install
docker compose run --rm node npm ci
```

## Database migration

```powershell
docker compose exec app php artisan migrate
```

## Initial administrator

```powershell
docker compose exec app php artisan registry:create-admin
```

## Installation check

```powershell
docker compose exec app php artisan registry:doctor
```

## Quality checks

```powershell
.\scripts\quality.ps1
```

Backend tests must never run in `app`.
