$ErrorActionPreference = 'Stop'
Push-Location $PSScriptRoot
try {
    $phpCommand = Get-Command php -ErrorAction SilentlyContinue
    $dashboardPhp = if ($phpCommand) { $phpCommand.Source } else { 'C:/xampp/php/php.exe' }
    if (-not (Test-Path -LiteralPath $dashboardPhp)) {
        throw 'PHP was not found. Install PHP 8.1+ or add php.exe to PATH.'
    }
    # Port 8001 belongs to FastAPI, not PHP.
    & $dashboardPhp -S localhost:8000 -t public/ public/router.php
    if ($LASTEXITCODE -ne 0) { throw 'Dashboard could not start. Check whether port 8000 is occupied.' }
} finally {
    Pop-Location
}
