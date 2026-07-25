param([string]$OutputDirectory = ".\backups")

$ErrorActionPreference = "Stop"
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$target = Join-Path $OutputDirectory "registry-$timestamp"
New-Item -ItemType Directory -Force -Path $target | Out-Null

$dbContainer = (docker compose ps -q db).Trim()
$appContainer = (docker compose ps -q app).Trim()
if (-not $dbContainer -or -not $appContainer) { throw "App und DB müssen laufen." }

$dbFile = Join-Path $target "database.dump"
$storageFile = Join-Path $target "private-storage.tar.gz"

docker compose exec -T db sh -lc 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom --file=/tmp/registry.dump'
docker cp "${dbContainer}:/tmp/registry.dump" $dbFile
docker compose exec -T db rm -f /tmp/registry.dump

docker compose exec -T app sh -lc 'tar -C /var/www/html/storage/app -czf /tmp/private-storage.tar.gz private'
docker cp "${appContainer}:/tmp/private-storage.tar.gz" $storageFile
docker compose exec -T app rm -f /tmp/private-storage.tar.gz

$manifest = [ordered]@{
    created_at = (Get-Date).ToUniversalTime().ToString("o")
    database_sha256 = (Get-FileHash -Algorithm SHA256 $dbFile).Hash.ToLowerInvariant()
    private_storage_sha256 = (Get-FileHash -Algorithm SHA256 $storageFile).Hash.ToLowerInvariant()
    application_version = "0.1.1"
}
$manifest | ConvertTo-Json | Set-Content -Encoding utf8 (Join-Path $target "manifest.json")

Write-Host "Backup erstellt: $target"
Write-Host "Anschließend verschlüsseln und auf ein getrenntes Ziel übertragen."
