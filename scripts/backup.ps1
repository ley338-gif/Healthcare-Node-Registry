param(
    [string]$OutputDirectory = ".\backups",
    [string]$ApplicationVersion = "0.3.0-dev"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Assert-NativeCommand([string]$Action) {
    if ($LASTEXITCODE -ne 0) {
        throw "$Action fehlgeschlagen (Exitcode $LASTEXITCODE)."
    }
}

$dbContainerOutput = docker compose ps -q db
Assert-NativeCommand "Datenbank-Container ermitteln"
$appContainerOutput = docker compose ps -q app
Assert-NativeCommand "Anwendungs-Container ermitteln"
$dbContainer = ($dbContainerOutput | Out-String).Trim()
$appContainer = ($appContainerOutput | Out-String).Trim()
if (-not $dbContainer -or -not $appContainer) { throw "App und DB müssen laufen." }

$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"
$target = Join-Path $OutputDirectory "registry-$timestamp"
New-Item -ItemType Directory -Force -Path $target | Out-Null

$dbFile = Join-Path $target "database.dump"
$storageFile = Join-Path $target "private-storage.tar.gz"

docker compose exec -T db sh -lc 'pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --format=custom --file=/tmp/registry.dump'
Assert-NativeCommand "Datenbank sichern"
docker cp "${dbContainer}:/tmp/registry.dump" $dbFile
Assert-NativeCommand "Datenbanksicherung kopieren"
docker compose exec -T db rm -f /tmp/registry.dump
Assert-NativeCommand "Temporäre Datenbanksicherung entfernen"

docker compose exec -T app sh -lc 'install -d -o www-data -g www-data -m 0750 /var/www/html/storage/app/private && tar -C /var/www/html/storage/app -czf /tmp/private-storage.tar.gz private && tar -tzf /tmp/private-storage.tar.gz > /tmp/private-storage.list && grep -Eq "^private/?$" /tmp/private-storage.list'
Assert-NativeCommand "Privaten Dokumentenspeicher sichern"
docker cp "${appContainer}:/tmp/private-storage.tar.gz" $storageFile
Assert-NativeCommand "Storage-Sicherung kopieren"
docker compose exec -T app rm -f /tmp/private-storage.tar.gz /tmp/private-storage.list
Assert-NativeCommand "Temporäre Storage-Sicherung entfernen"

if ((Get-Item $dbFile).Length -le 0 -or (Get-Item $storageFile).Length -le 0) {
    throw "Backup-Dateien sind leer."
}

$applicationRevision = "unknown"
$revisionOutput = git rev-parse --short HEAD 2>$null
if ($LASTEXITCODE -eq 0) {
    $applicationRevision = ($revisionOutput | Out-String).Trim()
}

$manifest = [ordered]@{
    created_at = (Get-Date).ToUniversalTime().ToString("o")
    database_sha256 = (Get-FileHash -Algorithm SHA256 $dbFile).Hash.ToLowerInvariant()
    private_storage_sha256 = (Get-FileHash -Algorithm SHA256 $storageFile).Hash.ToLowerInvariant()
    application_version = $ApplicationVersion
    application_revision = $applicationRevision
}
$manifest | ConvertTo-Json | Set-Content -Encoding utf8 (Join-Path $target "manifest.json")

Write-Host "Backup erstellt: $target"
Write-Host "Anschließend verschlüsseln und auf ein getrenntes Ziel übertragen."
