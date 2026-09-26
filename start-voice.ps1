$ErrorActionPreference = 'Stop'
Push-Location $PSScriptRoot
try {
    $voicePython = Join-Path $PSScriptRoot 'transcription/.venv/Scripts/python.exe'
    if (-not (Test-Path -LiteralPath $voicePython)) {
        python -m venv transcription/.venv
        if ($LASTEXITCODE -ne 0) { throw 'Python 3.11+ is required.' }
        & $voicePython -m pip install -r transcription/requirements.txt
        if ($LASTEXITCODE -ne 0) { throw 'Could not install voice dependencies.' }
    }
    if (-not (Test-Path -LiteralPath 'transcription/.env')) {
        Copy-Item -LiteralPath 'transcription/.env.example' -Destination 'transcription/.env'
        Write-Host 'Set GEMINI_API_KEY in transcription/.env to enable transcription.'
    }
    # Browser default uses this same IPv4 loopback address, avoiding localhost/IPv6 ambiguity.
    & $voicePython -m uvicorn transcription.server:app --host 127.0.0.1 --port 8001 --ws-max-size 24000 --ws-max-queue 8
    if ($LASTEXITCODE -ne 0) { throw 'Voice server exited unsuccessfully. Check the error above and whether port 8001 is occupied.' }
} finally {
    Pop-Location
}
