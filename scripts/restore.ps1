param(
    [Parameter(Mandatory = $true)][string]$BackupDirectory,
    [switch]$ConfirmRestore
)

$ErrorActionPreference = "Stop"
if (-not $ConfirmRestore) { throw "Destruktiver Restore: -ConfirmRestore ist erforderlich." }

$dbFile = Join-Path $BackupDirectory "database.dump"
$storageFile = Join-Path $BackupDirectory "private-storage.tar.gz"
$manifestFile = Join-Path $BackupDirectory "manifest.json"

foreach ($file in @($dbFile, $storageFile, $manifestFile)) {
    if (-not (Test-Path $file)) { throw "Backup-Datei fehlt: $file" }
}

$manifest = Get-Content $manifestFile -Raw | ConvertFrom-Json
if ((Get-FileHash $dbFile -Algorithm SHA256).Hash.ToLowerInvariant() -ne $manifest.database_sha256) {
    throw "Datenbank-Prüfsumme stimmt nicht."
}
if ((Get-FileHash $storageFile -Algorithm SHA256).Hash.ToLowerInvariant() -ne $manifest.private_storage_sha256) {
    throw "Storage-Prüfsumme stimmt nicht."
}

$dbContainer = (docker compose ps -q db).Trim()
$appContainer = (docker compose ps -q app).Trim()
if (-not $dbContainer -or -not $appContainer) { throw "App und DB müssen laufen." }

docker compose stop web | Out-Null
docker cp $dbFile "${dbContainer}:/tmp/registry.dump"
docker compose exec -T db sh -lc 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner --no-privileges /tmp/registry.dump'
docker compose exec -T db rm -f /tmp/registry.dump

docker cp $storageFile "${appContainer}:/tmp/private-storage.tar.gz"
docker compose exec -T app sh -lc 'rm -rf /var/www/html/storage/app/private && tar -C /var/www/html/storage/app -xzf /tmp/private-storage.tar.gz'
docker compose exec -T app rm -f /tmp/private-storage.tar.gz

docker compose exec -T app php artisan optimize:clear
docker compose start web | Out-Null
docker compose exec -T app php artisan registry:doctor
Write-Host "Restore abgeschlossen. Funktions- und Stichprobentest dokumentieren."
