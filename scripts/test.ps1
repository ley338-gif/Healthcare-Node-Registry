param(
    [string]$Filter = ""
)

$ErrorActionPreference = "Stop"

$arguments = @(
    "compose",
    "--profile",
    "test",
    "run",
    "--rm",
    "app-test",
    "composer",
    "test"
)

if ($Filter) {
    $arguments += "--"
    $arguments += "--filter"
    $arguments += $Filter
}

docker @arguments

if ($LASTEXITCODE -ne 0) {
    throw "Tests failed."
}
