$ErrorActionPreference = "Stop"

Write-Host "Starting isolated backend quality checks..."
docker compose --profile test run --rm app-test composer quality

if ($LASTEXITCODE -ne 0) {
    throw "Backend quality checks failed."
}

Write-Host "Starting frontend quality checks..."
docker compose run --rm node npm run check

if ($LASTEXITCODE -ne 0) {
    throw "Frontend quality checks failed."
}

Write-Host "All isolated quality checks passed."
