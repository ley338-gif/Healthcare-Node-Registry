param(
    [Parameter(Mandatory = $true)][string]$BackupDirectory,
    [switch]$ConfirmRestore
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"
if (-not $ConfirmRestore) { throw "Destruktiver Restore: -ConfirmRestore ist erforderlich." }

function Assert-NativeCommand([string]$Action) {
    if ($LASTEXITCODE -ne 0) {
        throw "$Action fehlgeschlagen (Exitcode $LASTEXITCODE)."
    }
}

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

$dbContainerOutput = docker compose ps -q db
Assert-NativeCommand "Datenbank-Container ermitteln"
$appContainerOutput = docker compose ps -q app
Assert-NativeCommand "Anwendungs-Container ermitteln"
$dbContainer = ($dbContainerOutput | Out-String).Trim()
$appContainer = ($appContainerOutput | Out-String).Trim()
if (-not $dbContainer -or -not $appContainer) { throw "App und DB müssen laufen." }

docker cp $dbFile "${dbContainer}:/tmp/registry.dump"
Assert-NativeCommand "Datenbanksicherung in Container kopieren"
docker compose exec -T db sh -lc 'pg_restore --list /tmp/registry.dump > /dev/null'
Assert-NativeCommand "Datenbanksicherung validieren"

docker cp $storageFile "${appContainer}:/tmp/private-storage.tar.gz"
Assert-NativeCommand "Storage-Sicherung in Container kopieren"
docker compose exec -T app sh -lc 'rm -f /tmp/private-storage.list && tar -tzf /tmp/private-storage.tar.gz > /tmp/private-storage.list && grep -Fx private/ /tmp/private-storage.list > /dev/null'
Assert-NativeCommand "Storage-Sicherung validieren"

$webStopped = $false
try {
    docker compose stop web | Out-Null
    Assert-NativeCommand "Webdienst stoppen"
    $webStopped = $true

    docker compose exec -T db sh -lc 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner --no-privileges /tmp/registry.dump'
    Assert-NativeCommand "Datenbank wiederherstellen"
    docker compose exec -T db rm -f /tmp/registry.dump
    Assert-NativeCommand "Temporäre Datenbanksicherung entfernen"

    docker compose exec -T app sh -lc 'rm -rf /var/www/html/storage/app/.private-restore && install -d -o www-data -g www-data -m 0750 /var/www/html/storage/app/.private-restore && tar --no-same-owner -C /var/www/html/storage/app/.private-restore -xzf /tmp/private-storage.tar.gz && test -d /var/www/html/storage/app/.private-restore/private && rm -rf /var/www/html/storage/app/private && mv /var/www/html/storage/app/.private-restore/private /var/www/html/storage/app/private && rmdir /var/www/html/storage/app/.private-restore && chown -R www-data:www-data /var/www/html/storage/app/private'
    Assert-NativeCommand "Privaten Dokumentenspeicher wiederherstellen"
    docker compose exec -T app rm -f /tmp/private-storage.tar.gz /tmp/private-storage.list
    Assert-NativeCommand "Temporäre Storage-Sicherung entfernen"

    docker compose exec -T app php artisan optimize:clear
    Assert-NativeCommand "Anwendungscaches leeren"
    docker compose exec -T app php artisan registry:doctor
    Assert-NativeCommand "Installation nach Restore prüfen"
    docker compose start web | Out-Null
    Assert-NativeCommand "Webdienst starten"
    $webStopped = $false
} catch {
    if ($webStopped) {
        Write-Warning "Restore fehlgeschlagen; der Webdienst bleibt zum Schutz vor einem inkonsistenten Zustand gestoppt."
    }
    throw
}

Write-Host "Restore abgeschlossen. Funktions- und Stichprobentest dokumentieren."
