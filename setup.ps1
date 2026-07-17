Write-Host ""
Write-Host "Preparing Python virtual environment..."

$pythonCommand = Get-Command python -All -ErrorAction SilentlyContinue |
    Where-Object {
        $_.Source -notmatch "\\msys64\\"
    } |
    Select-Object -First 1

if (-not $pythonCommand) {
    throw "Native Windows Python was not found. Install Python and add it to PATH."
}

$pythonExe = $pythonCommand.Source

Write-Host "Using Python:"
Write-Host $pythonExe

& $pythonExe --version

if (-not (Test-Path ".venv\Scripts\python.exe")) {
    & $pythonExe -m venv ".venv"

    if ($LASTEXITCODE -ne 0) {
        throw "Failed to create the Python virtual environment."
    }
}
else {
    Write-Host "Python virtual environment already exists."
}

Write-Host ""
Write-Host "Installing Python packages..."

& ".\.venv\Scripts\python.exe" -m pip install --upgrade pip

if ($LASTEXITCODE -ne 0) {
    throw "Failed to upgrade pip."
}

& ".\.venv\Scripts\python.exe" -m pip install `
    -r ".\python_service\requirements.txt"

if ($LASTEXITCODE -ne 0) {
    throw "Failed to install Python requirements."
}